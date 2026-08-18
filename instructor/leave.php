<?php
/**
 * Instructor - Record Leave & Assign a Replacement
 * Smart Instructor Coordination and Workload Management System
 *
 * Workflow:
 *  1. Instructor fills in leave details (type, dates, reason).
 *  2. Before the leave can be saved, the instructor MUST pick another
 *     instructor to cover for them for that whole period — either by
 *     searching for one, or by picking one of the smart suggestions.
 *     This applies even if the instructor has no task scheduled during
 *     the leave; someone still has to be on record as covering for them.
 *  3. Submitting sends a replacement request directly to the chosen
 *     instructor. The leave stays "Pending" until that instructor
 *     accepts (see instructor/replacement_request.php). Only once they
 *     accept does the leave become "Confirmed".
 *  4. If the chosen instructor rejects, the leave stays Pending and the
 *     requester can pick another replacement for the same leave record.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';

checkRole(ROLE_INSTRUCTOR);

$instructorId = sic_current_instructor_id();
if (!$instructorId) {
    $_SESSION['error'] = 'No instructor profile is linked to your account. Please contact the administrator.';
    header('Location: ' . app_url('instructor/dashboard.php'));
    exit;
}

$validLeaveTypes = ['Casual', 'Medical', 'Duty', 'Other'];
$error = '';

/**
 * Notify a user and log the action in one place, so both the "new leave"
 * path and the "pick another replacement" path stay in sync.
 */
function sic_send_replacement_request($pdo, $instructorId, $leaveId, $leaveType, $startDate, $endDate, $suggestedId) {
    $stmt = $pdo->prepare("
        INSERT INTO replacement_requests (task_assignment_id, leave_record_id, requested_by_instructor_id, reason, suggested_instructor_id, status, created_at)
        VALUES (NULL, ?, ?, ?, ?, 'Pending', NOW())
    ");
    $reason = "Cover for {$leaveType} leave from {$startDate} to {$endDate}.";
    $stmt->execute([$leaveId, $instructorId, $reason, $suggestedId]);
    $requestId = (int)$pdo->lastInsertId();

    logActivity($_SESSION['user_id'], 'Request Leave Replacement', "Requested replacement for leave #{$leaveId} from instructor #{$suggestedId}");

    // Notify the chosen instructor directly — they must accept before the leave is confirmed.
    $userStmt = $pdo->prepare("SELECT user_id FROM instructors WHERE id = ?");
    $userStmt->execute([$suggestedId]);
    $suggestedUserId = $userStmt->fetchColumn();
    $requesterName = $_SESSION['full_name'] ?? 'An instructor';
    if ($suggestedUserId) {
        createNotification(
            $suggestedUserId,
            'Replacement Request for Leave',
            "{$requesterName} has asked you to cover their leave from " . formatDate($startDate) . " to " . formatDate($endDate) . ". Please accept or reject.",
            'replacement',
            $requestId
        );
    }

    // Notify coordinators for visibility (informational only — they are not asked to act).
    $notifyUsers = $pdo->prepare("SELECT id FROM users WHERE role_id IN (:coord, :chief) AND status = 'active'");
    $notifyUsers->execute([':coord' => ROLE_COORDINATOR, ':chief' => ROLE_CHIEF_COORDINATOR]);
    foreach ($notifyUsers->fetchAll(PDO::FETCH_COLUMN) as $uid) {
        createNotification($uid, 'Leave Replacement Requested', "{$requesterName} recorded leave and requested a replacement — awaiting their response.", 'leave', $leaveId);
    }

    // Notify Non-Academic Staff so they can update administrative records (view-only; no approval).
    $naUsers = $pdo->prepare("SELECT id FROM users WHERE role_id = :na AND status = 'active'");
    $naUsers->execute([':na' => ROLE_NON_ACADEMIC]);
    $leaveMsg = "{$requesterName} recorded {$leaveType} leave from " . formatDate($startDate) . " to " . formatDate($endDate) . ".";
    foreach ($naUsers->fetchAll(PDO::FETCH_COLUMN) as $uid) {
        createNotification($uid, 'Instructor Leave Recorded', $leaveMsg, 'leave', $leaveId);
    }

    return $requestId;
}

// -----------------------------------------------------------------
// Handle final submission: creates (or reuses) the leave record and
// sends the replacement request to the chosen instructor.
// -----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_leave_request'])) {
    csrf_verify();

    $leaveType = sanitize($_POST['leave_type'] ?? 'Casual');
    $startDate = sanitize($_POST['start_date'] ?? '');
    $endDate = sanitize($_POST['end_date'] ?? '');
    $reason = sanitize($_POST['reason'] ?? '');
    $resumeLeaveId = (int)($_POST['leave_id'] ?? 0);
    $suggestedId = (int)($_POST['suggested_instructor_id'] ?? 0);

    if ($suggestedId <= 0) {
        $error = 'Please choose a replacement instructor before sending the request.';
    } elseif ($suggestedId === $instructorId) {
        $error = 'You cannot assign yourself as your own replacement.';
    } else {
        // Confirm the chosen instructor is a real, active instructor.
        $repChk = $pdo->prepare("SELECT id FROM instructors WHERE id = ? AND status = 'active'");
        $repChk->execute([$suggestedId]);
        if (!$repChk->fetch()) {
            $error = 'The selected replacement instructor is not available. Please choose another.';
        }
    }

    if ($error === '' && $resumeLeaveId > 0) {
        // Re-sending a request for an existing Pending leave (previous replacement rejected).
        $leaveChk = $pdo->prepare("SELECT * FROM leave_records WHERE id = ? AND instructor_id = ? AND status = 'Pending'");
        $leaveChk->execute([$resumeLeaveId, $instructorId]);
        $leaveRow = $leaveChk->fetch();

        if (!$leaveRow) {
            $error = 'That leave record could not be found or is no longer pending.';
        } else {
            $pendingChk = $pdo->prepare("SELECT id FROM replacement_requests WHERE leave_record_id = ? AND status = 'Pending'");
            $pendingChk->execute([$resumeLeaveId]);
            if ($pendingChk->fetch()) {
                $error = 'A replacement request for this leave is already pending a response.';
            } else {
                sic_send_replacement_request($pdo, $instructorId, $resumeLeaveId, $leaveRow['leave_type'], $leaveRow['start_date'], $leaveRow['end_date'], $suggestedId);
                $_SESSION['success'] = 'Replacement request sent. Your leave will be confirmed once they accept.';
                header('Location: ' . app_url('instructor/leave.php'));
                exit;
            }
        }
    } elseif ($error === '') {
        // Brand new leave — validate the details again (defense in depth).
        if (!in_array($leaveType, $validLeaveTypes, true)) {
            $error = 'Invalid leave type selected.';
        } elseif ($startDate === '' || $endDate === '' || !DateTime::createFromFormat('Y-m-d', $startDate) || !DateTime::createFromFormat('Y-m-d', $endDate)) {
            $error = 'Please select both start and end dates.';
        } elseif (strtotime($endDate) < strtotime($startDate)) {
            $error = 'End date cannot be before the start date.';
        } elseif ($reason === '') {
            $error = 'Please provide a reason for the leave.';
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    INSERT INTO leave_records (instructor_id, leave_type, start_date, end_date, reason, status, created_at)
                    VALUES (?, ?, ?, ?, ?, 'Pending', NOW())
                ");
                $stmt->execute([$instructorId, $leaveType, $startDate, $endDate, $reason]);
                $leaveId = (int)$pdo->lastInsertId();

                sic_send_replacement_request($pdo, $instructorId, $leaveId, $leaveType, $startDate, $endDate, $suggestedId);

                $pdo->commit();

                $_SESSION['success'] = 'Leave recorded and a replacement request has been sent. Your leave will be confirmed once they accept.';
                header('Location: ' . app_url('instructor/leave.php'));
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// -----------------------------------------------------------------
// Work out whether to show Step 1 (leave details) or Step 2
// (choose a replacement), and with what draft values.
// -----------------------------------------------------------------
$resumeLeaveId = isset($_GET['leave_id']) ? (int)$_GET['leave_id'] : 0;
$showStep2 = false;
$draftLeaveType = 'Casual';
$draftStart = '';
$draftEnd = '';
$draftReason = '';
$excludeSuggestedIds = [];

if ($resumeLeaveId > 0) {
    $leaveChk = $pdo->prepare("SELECT * FROM leave_records WHERE id = ? AND instructor_id = ? AND status = 'Pending'");
    $leaveChk->execute([$resumeLeaveId, $instructorId]);
    $leaveRow = $leaveChk->fetch();

    if ($leaveRow) {
        $showStep2 = true;
        $draftLeaveType = $leaveRow['leave_type'];
        $draftStart = $leaveRow['start_date'];
        $draftEnd = $leaveRow['end_date'];
        $draftReason = $leaveRow['reason'];

        // Don't re-suggest instructors who already rejected this leave.
        $rejectedStmt = $pdo->prepare("SELECT suggested_instructor_id FROM replacement_requests WHERE leave_record_id = ? AND status = 'Rejected'");
        $rejectedStmt->execute([$resumeLeaveId]);
        $excludeSuggestedIds = array_map('intval', $rejectedStmt->fetchAll(PDO::FETCH_COLUMN));
    } else {
        $error = $error ?: 'That leave record could not be found or is no longer pending.';
    }
} elseif (isset($_GET['find_replacement'])) {
    $leaveType = sanitize($_GET['leave_type'] ?? 'Casual');
    $startDate = sanitize($_GET['start_date'] ?? '');
    $endDate = sanitize($_GET['end_date'] ?? '');
    $reason = sanitize($_GET['reason'] ?? '');

    if (!in_array($leaveType, $validLeaveTypes, true)) {
        $error = 'Invalid leave type selected.';
    } elseif ($startDate === '' || $endDate === '' || !DateTime::createFromFormat('Y-m-d', $startDate) || !DateTime::createFromFormat('Y-m-d', $endDate)) {
        $error = 'Please select both start and end dates.';
    } elseif (strtotime($endDate) < strtotime($startDate)) {
        $error = 'End date cannot be before the start date.';
    } elseif ($reason === '') {
        $error = 'Please provide a reason for the leave.';
    } else {
        $showStep2 = true;
        $draftLeaveType = $leaveType;
        $draftStart = $startDate;
        $draftEnd = $endDate;
        $draftReason = $reason;
    }
}

$smartSuggestions = [];
$searchResults = [];
$searchTerm = trim((string)($_GET['q'] ?? ''));

if ($showStep2) {
    $myStream = $pdo->prepare("SELECT academic_stream_id FROM instructors WHERE id = ?");
    $myStream->execute([$instructorId]);
    $myStreamId = (int)$myStream->fetchColumn();

    $smartSuggestions = getSmartLeaveReplacementSuggestions($instructorId, $draftStart, $draftEnd, $myStreamId ?: null, 8);
    if (!empty($excludeSuggestedIds)) {
        $smartSuggestions = array_values(array_filter($smartSuggestions, fn($s) => !in_array((int)$s['instructor_id'], $excludeSuggestedIds, true)));
    }

    if ($searchTerm !== '') {
        $searchStmt = $pdo->prepare("
            SELECT i.id AS instructor_id, u.full_name AS name, i.employee_id, ast.name AS stream, d.name AS department, i.designation
            FROM instructors i
            JOIN users u ON i.user_id = u.id
            JOIN academic_streams ast ON i.academic_stream_id = ast.id
            JOIN departments d ON i.department_id = d.id
            WHERE i.status = 'active'
              AND i.id != :self_id
              AND (u.full_name LIKE :term OR i.employee_id LIKE :term)
            ORDER BY u.full_name
            LIMIT 15
        ");
        $searchStmt->execute([':self_id' => $instructorId, ':term' => '%' . $searchTerm . '%']);
        $searchResults = $searchStmt->fetchAll();
        if (!empty($excludeSuggestedIds)) {
            $searchResults = array_values(array_filter($searchResults, fn($s) => !in_array((int)$s['instructor_id'], $excludeSuggestedIds, true)));
        }
    }
}

// My leave history, with the latest replacement request attached to each leave.
$leaveStmt = $pdo->prepare("
    SELECT lr.*,
           rr.id AS rr_id, rr.status AS rr_status,
           su.full_name AS rr_suggested_name
    FROM leave_records lr
    LEFT JOIN replacement_requests rr ON rr.id = (
        SELECT rr2.id FROM replacement_requests rr2
        WHERE rr2.leave_record_id = lr.id
        ORDER BY rr2.created_at DESC LIMIT 1
    )
    LEFT JOIN instructors si ON rr.suggested_instructor_id = si.id
    LEFT JOIN users su ON si.user_id = su.id
    WHERE lr.instructor_id = ?
    ORDER BY lr.created_at DESC
");
$leaveStmt->execute([$instructorId]);
$leaveRecords = $leaveStmt->fetchAll();

$pageTitle = 'Leave';
include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1>Leave</h1>
                    <p>Record your leave and assign a replacement instructor for that period.</p>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!$showStep2): ?>
                <!-- STEP 1: Leave details -->
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header"><h5>Record Leave</h5></div>
                            <div class="card-body">
                                <p class="text-muted small">A replacement instructor must be assigned to cover this period before the leave is confirmed — even if you have no task scheduled during it.</p>
                                <form method="GET" action="">
                                    <input type="hidden" name="find_replacement" value="1">
                                    <div class="mb-3">
                                        <label class="form-label">Leave Type</label>
                                        <select name="leave_type" class="form-select">
                                            <?php foreach ($validLeaveTypes as $lt): ?>
                                                <option value="<?= $lt ?>"><?= $lt ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                            <input type="date" name="start_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">End Date <span class="text-danger">*</span></label>
                                            <input type="date" name="end_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3 mt-3">
                                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                                        <textarea name="reason" class="form-control" rows="3" required placeholder="Briefly explain the reason for leave"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <span class="ui-dot" aria-hidden="true"></span>
                                        Continue — Choose a Replacement
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <?php include __DIR__ . '/_leave_history.php'; ?>
                    </div>
                </div>

            <?php else: ?>
                <!-- STEP 2: Choose a replacement instructor -->
                <div class="card" style="margin-bottom:20px;">
                    <div class="card-header">
                        <h5>Leave Summary</h5>
                    </div>
                    <div class="card-body">
                        <p>
                            <strong><?= htmlspecialchars($draftLeaveType) ?> Leave</strong>
                            &nbsp;&middot;&nbsp; <?= formatDate($draftStart) ?> to <?= formatDate($draftEnd) ?>
                            <br><span class="text-muted small"><?= htmlspecialchars($draftReason) ?></span>
                        </p>
                        <a href="<?= app_url('instructor/leave.php') ?>" class="btn btn-sm btn-outline-secondary">&larr; Change Leave Details</a>
                    </div>
                </div>

                <div class="card" style="margin-bottom:20px;">
                    <div class="card-header"><h5>Search for a Replacement</h5></div>
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <?php if ($resumeLeaveId > 0): ?>
                                <input type="hidden" name="leave_id" value="<?= (int)$resumeLeaveId ?>">
                            <?php else: ?>
                                <input type="hidden" name="find_replacement" value="1">
                                <input type="hidden" name="leave_type" value="<?= htmlspecialchars($draftLeaveType) ?>">
                                <input type="hidden" name="start_date" value="<?= htmlspecialchars($draftStart) ?>">
                                <input type="hidden" name="end_date" value="<?= htmlspecialchars($draftEnd) ?>">
                                <input type="hidden" name="reason" value="<?= htmlspecialchars($draftReason) ?>">
                            <?php endif; ?>
                            <div class="col-md-9">
                                <label class="form-label">Instructor Name or Employee ID</label>
                                <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Search by name or employee ID">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">Search</button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($searchTerm !== ''): ?>
                <div class="card" style="margin-bottom:20px;">
                    <div class="card-header">
                        <h5>Search Results</h5>
                        <span class="text-muted small"><?= count($searchResults) ?> found</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead><tr><th>Instructor</th><th>Employee ID</th><th>Stream</th><th class="text-end">Action</th></tr></thead>
                                <tbody>
                                    <?php if (empty($searchResults)): ?>
                                        <tr><td colspan="4" class="text-muted">No matching active instructors found.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($searchResults as $sr): ?>
                                        <tr>
                                            <td data-label="Instructor"><strong><?= htmlspecialchars($sr['name']) ?></strong><br><span class="small text-muted"><?= htmlspecialchars($sr['designation']) ?></span></td>
                                            <td data-label="Employee ID"><?= htmlspecialchars($sr['employee_id']) ?></td>
                                            <td data-label="Stream"><?= htmlspecialchars($sr['stream']) ?></td>
                                            <td data-label="Action" class="text-end">
                                                <?php include __DIR__ . '/_leave_send_request_form.php'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h5>Smart Suggestions</h5>
                        <span class="text-muted small">Ranked by lowest workload for this period</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead><tr><th>#</th><th>Instructor</th><th>Employee ID</th><th>Stream</th><th>Workload (hrs)</th><th class="text-end">Action</th></tr></thead>
                                <tbody>
                                    <?php if (empty($smartSuggestions)): ?>
                                        <tr><td colspan="6" class="text-muted">No suitable instructors found for this period.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($smartSuggestions as $index => $sug): $sr = $sug; ?>
                                        <tr>
                                            <td data-label="#"><?= $index + 1 ?></td>
                                            <td data-label="Instructor"><strong><?= htmlspecialchars($sug['name']) ?></strong><br><span class="small text-muted"><?= htmlspecialchars($sug['designation']) ?></span></td>
                                            <td data-label="Employee ID"><?= htmlspecialchars($sug['employee_id']) ?></td>
                                            <td data-label="Stream"><?= htmlspecialchars($sug['stream']) ?></td>
                                            <td data-label="Workload">
                                                <span class="badge <?= $sug['current_workload'] > 30 ? 'bg-danger' : ($sug['current_workload'] > 15 ? 'bg-warning' : 'bg-success') ?>">
                                                    <?= htmlspecialchars((string)$sug['current_workload']) ?>
                                                </span>
                                            </td>
                                            <td data-label="Action" class="text-end">
                                                <?php include __DIR__ . '/_leave_send_request_form.php'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row g-4" style="margin-top:4px;">
                    <div class="col-md-12">
                        <?php include __DIR__ . '/_leave_history.php'; ?>
                    </div>
                </div>
            <?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>