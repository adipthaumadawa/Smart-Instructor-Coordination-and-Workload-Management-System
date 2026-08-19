<?php
/**
 * Non-Academic Staff - Manage Instructor Attendance Records
 * Smart Instructor Coordination and Workload Management System
 *
 * Capabilities:
 *  - Record instructor attendance for a chosen date
 *  - Update existing attendance entries
 *  - View attendance history (filter by date / instructor / status)
 *  - Simple summary counts for the selected date
 *
 * Does NOT assign instructors or calculate workload from attendance.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_NON_ACADEMIC);

$pageTitle = "Manage Attendance Records";
$validStatuses = ['Present', 'Absent', 'Late', 'On Leave', 'Half Day'];
$error = '';
$success = '';

// Ensure attendance table exists (graceful if migration not run yet)
try {
    $pdo->query("SELECT 1 FROM instructor_attendance LIMIT 1");
} catch (Exception $e) {
    // Table missing — try to create it so the page is usable immediately
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS instructor_attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            instructor_id INT NOT NULL,
            attendance_date DATE NOT NULL,
            status ENUM('Present', 'Absent', 'Late', 'On Leave', 'Half Day') NOT NULL DEFAULT 'Present',
            check_in_time TIME NULL,
            check_out_time TIME NULL,
            notes TEXT NULL,
            recorded_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_instructor_date (instructor_id, attendance_date),
            FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE CASCADE,
            FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_attendance_date (attendance_date),
            INDEX idx_attendance_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

// Selected date for bulk view / recording (default: today)
$selectedDate = sanitize($_GET['date'] ?? date('Y-m-d'));
if (!DateTime::createFromFormat('Y-m-d', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}

// ------------------------------------------------------------------
// POST: save or update a single attendance record
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action = $_POST['action'] ?? 'save';
    $instructorId = (int)($_POST['instructor_id'] ?? 0);
    $attendanceDate = sanitize($_POST['attendance_date'] ?? $selectedDate);
    $status = sanitize($_POST['status'] ?? 'Present');
    $checkIn = sanitize($_POST['check_in_time'] ?? '') ?: null;
    $checkOut = sanitize($_POST['check_out_time'] ?? '') ?: null;
    $notes = sanitize($_POST['notes'] ?? '');
    $recordId = (int)($_POST['record_id'] ?? 0);

    if (!in_array($status, $validStatuses, true)) {
        $error = 'Invalid attendance status.';
    } elseif ($instructorId <= 0) {
        $error = 'Please select an instructor.';
    } elseif (!DateTime::createFromFormat('Y-m-d', $attendanceDate)) {
        $error = 'Invalid attendance date.';
    } else {
        // Confirm instructor exists and is active (or on_leave — still trackable)
        $chk = $pdo->prepare("SELECT id FROM instructors WHERE id = ? AND status IN ('active','on_leave')");
        $chk->execute([$instructorId]);
        if (!$chk->fetch()) {
            $error = 'Selected instructor was not found or is inactive.';
        } else {
            try {
                if ($action === 'update' && $recordId > 0) {
                    $stmt = $pdo->prepare("
                        UPDATE instructor_attendance
                           SET status = ?, check_in_time = ?, check_out_time = ?, notes = ?,
                               recorded_by = ?, updated_at = NOW()
                         WHERE id = ?
                    ");
                    $stmt->execute([$status, $checkIn, $checkOut, $notes, $_SESSION['user_id'], $recordId]);
                    logActivity($_SESSION['user_id'], 'Update Attendance', "Updated attendance #{$recordId} for instructor #{$instructorId} on {$attendanceDate}");
                    $success = 'Attendance record updated successfully.';
                } else {
                    // Insert or update on unique (instructor_id, attendance_date)
                    $stmt = $pdo->prepare("
                        INSERT INTO instructor_attendance
                            (instructor_id, attendance_date, status, check_in_time, check_out_time, notes, recorded_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            status = VALUES(status),
                            check_in_time = VALUES(check_in_time),
                            check_out_time = VALUES(check_out_time),
                            notes = VALUES(notes),
                            recorded_by = VALUES(recorded_by),
                            updated_at = NOW()
                    ");
                    $stmt->execute([$instructorId, $attendanceDate, $status, $checkIn, $checkOut, $notes, $_SESSION['user_id']]);
                    logActivity($_SESSION['user_id'], 'Record Attendance', "Recorded {$status} for instructor #{$instructorId} on {$attendanceDate}");
                    $success = 'Attendance recorded successfully.';
                }
                $selectedDate = $attendanceDate;
            } catch (Exception $e) {
                $error = 'Could not save attendance: ' . $e->getMessage();
            }
        }
    }
}

// ------------------------------------------------------------------
// Filters for history
// ------------------------------------------------------------------
$filterInstructor = (int)($_GET['instructor_id'] ?? 0);
$filterStatus = sanitize($_GET['status'] ?? '');
$filterFrom = sanitize($_GET['from'] ?? date('Y-m-d', strtotime('-30 days')));
$filterTo = sanitize($_GET['to'] ?? date('Y-m-d'));
if (!DateTime::createFromFormat('Y-m-d', $filterFrom)) $filterFrom = date('Y-m-d', strtotime('-30 days'));
if (!DateTime::createFromFormat('Y-m-d', $filterTo)) $filterTo = date('Y-m-d');

// Active instructors for the daily grid
$instructors = $pdo->query("
    SELECT i.id, i.employee_id, u.full_name, i.designation, i.status AS instructor_status
    FROM instructors i
    JOIN users u ON i.user_id = u.id
    WHERE i.status IN ('active', 'on_leave')
    ORDER BY u.full_name
")->fetchAll();

// Existing attendance for the selected date (keyed by instructor_id)
$dayStmt = $pdo->prepare("
    SELECT a.*, u.full_name AS recorded_by_name
    FROM instructor_attendance a
    LEFT JOIN users u ON a.recorded_by = u.id
    WHERE a.attendance_date = ?
");
$dayStmt->execute([$selectedDate]);
$dayRecords = [];
foreach ($dayStmt->fetchAll() as $row) {
    $dayRecords[(int)$row['instructor_id']] = $row;
}

// Summary counts for selected date
$summary = ['Present' => 0, 'Absent' => 0, 'Late' => 0, 'On Leave' => 0, 'Half Day' => 0, 'Not Recorded' => 0];
foreach ($instructors as $ins) {
    if (isset($dayRecords[(int)$ins['id']])) {
        $st = $dayRecords[(int)$ins['id']]['status'];
        if (isset($summary[$st])) $summary[$st]++;
    } else {
        $summary['Not Recorded']++;
    }
}

// History query
$historySql = "
    SELECT a.*, u.full_name AS instructor_name, i.employee_id,
           rec.full_name AS recorded_by_name
    FROM instructor_attendance a
    JOIN instructors i ON a.instructor_id = i.id
    JOIN users u ON i.user_id = u.id
    LEFT JOIN users rec ON a.recorded_by = rec.id
    WHERE a.attendance_date BETWEEN :from AND :to
";
$params = [':from' => $filterFrom, ':to' => $filterTo];
if ($filterInstructor > 0) {
    $historySql .= " AND a.instructor_id = :iid";
    $params[':iid'] = $filterInstructor;
}
if ($filterStatus !== '' && in_array($filterStatus, $validStatuses, true)) {
    $historySql .= " AND a.status = :st";
    $params[':st'] = $filterStatus;
}
$historySql .= " ORDER BY a.attendance_date DESC, u.full_name ASC LIMIT 200";
$histStmt = $pdo->prepare($historySql);
$histStmt->execute($params);
$history = $histStmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1><i class="fas fa-user-clock me-2"></i>Manage Attendance Records</h1>
                    <p>Record and update instructor/staff attendance. View history and daily summaries.</p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Date selector + summary -->
            <div class="card mb-4">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="mb-0">Daily Attendance</h5>
                    <form method="get" class="d-flex align-items-center gap-2">
                        <label class="form-label mb-0 small text-muted">Date</label>
                        <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($selectedDate) ?>" onchange="this.form.submit()">
                    </form>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <?php
                        $summaryColors = [
                            'Present' => 'success', 'Absent' => 'danger', 'Late' => 'warning',
                            'On Leave' => 'info', 'Half Day' => 'secondary', 'Not Recorded' => 'dark'
                        ];
                        foreach ($summary as $label => $count): ?>
                            <div class="col-6 col-md-2">
                                <div class="border rounded p-3 text-center">
                                    <div class="fs-4 fw-bold text-<?= $summaryColors[$label] ?? 'primary' ?>"><?= (int)$count ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($label) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Instructor</th>
                                    <th>Employee ID</th>
                                    <th>Status</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Notes</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($instructors)): ?>
                                    <tr><td colspan="7" class="text-muted">No active instructors found.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($instructors as $ins):
                                    $rec = $dayRecords[(int)$ins['id']] ?? null;
                                    $isEdit = $rec !== null;
                                    $formId = 'att-form-' . (int)$ins['id'];
                                ?>
                                <tr>
                                    <td data-label="Instructor">
                                        <strong><?= htmlspecialchars($ins['full_name']) ?></strong>
                                        <div class="small text-muted"><?= htmlspecialchars($ins['designation'] ?? '') ?></div>
                                        <form id="<?= $formId ?>" method="post" class="d-none">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="instructor_id" value="<?= (int)$ins['id'] ?>">
                                            <input type="hidden" name="attendance_date" value="<?= htmlspecialchars($selectedDate) ?>">
                                            <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'save' ?>">
                                            <?php if ($isEdit): ?>
                                                <input type="hidden" name="record_id" value="<?= (int)$rec['id'] ?>">
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                    <td data-label="Employee ID"><?= htmlspecialchars($ins['employee_id']) ?></td>
                                    <td data-label="Status">
                                        <select name="status" class="form-select form-select-sm" form="<?= $formId ?>">
                                            <?php foreach ($validStatuses as $st): ?>
                                                <option value="<?= $st ?>" <?= ($rec['status'] ?? 'Present') === $st ? 'selected' : '' ?>><?= $st ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td data-label="Check-in">
                                        <input type="time" name="check_in_time" class="form-control form-control-sm" form="<?= $formId ?>"
                                               value="<?= htmlspecialchars(substr($rec['check_in_time'] ?? '', 0, 5)) ?>">
                                    </td>
                                    <td data-label="Check-out">
                                        <input type="time" name="check_out_time" class="form-control form-control-sm" form="<?= $formId ?>"
                                               value="<?= htmlspecialchars(substr($rec['check_out_time'] ?? '', 0, 5)) ?>">
                                    </td>
                                    <td data-label="Notes">
                                        <input type="text" name="notes" class="form-control form-control-sm" form="<?= $formId ?>"
                                               value="<?= htmlspecialchars($rec['notes'] ?? '') ?>" placeholder="Optional notes">
                                    </td>
                                    <td data-label="Action">
                                        <button type="submit" class="btn btn-sm btn-primary" form="<?= $formId ?>">
                                            <?= $isEdit ? 'Update' : 'Save' ?>
                                        </button>
                                        <?php if ($isEdit): ?>
                                            <div class="small text-muted mt-1">by <?= htmlspecialchars($rec['recorded_by_name'] ?? '—') ?></div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Attendance history -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Attendance History</h5>
                </div>
                <div class="card-body">
                    <form method="get" class="row g-2 mb-3">
                        <input type="hidden" name="date" value="<?= htmlspecialchars($selectedDate) ?>">
                        <div class="col-md-3">
                            <label class="form-label small">From</label>
                            <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($filterFrom) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">To</label>
                            <input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($filterTo) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Instructor</label>
                            <select name="instructor_id" class="form-select form-select-sm">
                                <option value="0">All instructors</option>
                                <?php foreach ($instructors as $ins): ?>
                                    <option value="<?= (int)$ins['id'] ?>" <?= $filterInstructor === (int)$ins['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ins['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All</option>
                                <?php foreach ($validStatuses as $st): ?>
                                    <option value="<?= $st ?>" <?= $filterStatus === $st ? 'selected' : '' ?>><?= $st ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">Filter</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Instructor</th>
                                    <th>Employee ID</th>
                                    <th>Status</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Notes</th>
                                    <th>Recorded by</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($history)): ?>
                                    <tr><td colspan="8" class="text-muted">No attendance records match the selected filters.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($history as $h): ?>
                                <tr>
                                    <td data-label="Date"><?= formatDate($h['attendance_date']) ?></td>
                                    <td data-label="Instructor"><strong><?= htmlspecialchars($h['instructor_name']) ?></strong></td>
                                    <td data-label="Employee ID"><?= htmlspecialchars($h['employee_id']) ?></td>
                                    <td data-label="Status">
                                        <?php
                                        $badgeMap = [
                                            'Present' => 'success', 'Absent' => 'danger', 'Late' => 'warning',
                                            'On Leave' => 'info', 'Half Day' => 'secondary'
                                        ];
                                        $bc = $badgeMap[$h['status']] ?? 'primary';
                                        ?>
                                        <span class="badge bg-<?= $bc ?>"><?= htmlspecialchars($h['status']) ?></span>
                                    </td>
                                    <td data-label="Check-in"><?= $h['check_in_time'] ? substr($h['check_in_time'], 0, 5) : '—' ?></td>
                                    <td data-label="Check-out"><?= $h['check_out_time'] ? substr($h['check_out_time'], 0, 5) : '—' ?></td>
                                    <td data-label="Notes"><?= htmlspecialchars($h['notes'] ?: '—') ?></td>
                                    <td data-label="Recorded by"><?= htmlspecialchars($h['recorded_by_name'] ?? '—') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted mb-0">Showing up to 200 most recent matching records.</p>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>