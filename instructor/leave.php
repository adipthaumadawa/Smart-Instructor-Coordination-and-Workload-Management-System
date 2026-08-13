<?php
/**
 * Instructor - Record & View Leave
 * Smart Instructor Coordination and Workload Management System
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

$error = '';

// Handle leave submission BEFORE header output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_leave'])) {
    $leaveType = sanitize($_POST['leave_type'] ?? 'Casual');
    $startDate = sanitize($_POST['start_date'] ?? '');
    $endDate = sanitize($_POST['end_date'] ?? '');
    $reason = sanitize($_POST['reason'] ?? '');

    $validTypes = ['Casual', 'Medical', 'Duty', 'Other'];
    if (!in_array($leaveType, $validTypes, true)) {
        $error = 'Invalid leave type selected.';
    } elseif ($startDate === '' || $endDate === '') {
        $error = 'Please select both start and end dates.';
    } elseif (strtotime($endDate) < strtotime($startDate)) {
        $error = 'End date cannot be before the start date.';
    } elseif ($reason === '') {
        $error = 'Please provide a reason for the leave.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO leave_records (instructor_id, leave_type, start_date, end_date, reason, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'Pending', NOW())
            ");
            $stmt->execute([$instructorId, $leaveType, $startDate, $endDate, $reason]);
            $leaveId = (int)$pdo->lastInsertId();

            logActivity($_SESSION['user_id'], 'Record Leave', "Recorded {$leaveType} leave from {$startDate} to {$endDate}");

            // Notify all Instructor Coordinators and Chief Instructor Coordinator so they can plan replacements
            $notifyUsers = $pdo->prepare("SELECT id FROM users WHERE role_id IN (:coord, :chief) AND status = 'active'");
            $notifyUsers->execute([':coord' => ROLE_COORDINATOR, ':chief' => ROLE_CHIEF_COORDINATOR]);
            $recipients = $notifyUsers->fetchAll(PDO::FETCH_COLUMN);

            $instrName = $_SESSION['full_name'] ?? 'An instructor';
            foreach ($recipients as $uid) {
                createNotification(
                    $uid,
                    'New Leave Recorded',
                    "{$instrName} recorded {$leaveType} leave from {$startDate} to {$endDate}.",
                    'leave',
                    $leaveId
                );
            }

            $_SESSION['success'] = 'Leave recorded successfully. Coordinators have been notified.';
            header('Location: ' . app_url('instructor/leave.php'));
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// My leave history
$leaveStmt = $pdo->prepare("SELECT * FROM leave_records WHERE instructor_id = ? ORDER BY created_at DESC");
$leaveStmt->execute([$instructorId]);
$leaveRecords = $leaveStmt->fetchAll();

$pageTitle = 'Leave';
include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1>Leave</h1>
                    <p>Record your leave and track the status of your leave requests.</p>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header"><h5>Record Leave</h5></div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">Leave Type</label>
                                    <select name="leave_type" class="form-select">
                                        <option value="Casual">Casual</option>
                                        <option value="Medical">Medical</option>
                                        <option value="Duty">Duty</option>
                                        <option value="Other">Other</option>
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
                                <button type="submit" name="submit_leave" class="btn btn-primary">
                                    <span class="ui-dot" aria-hidden="true"></span>
                                    Submit Leave
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>My Leave History</h5>
                            <span class="text-muted small"><?= count($leaveRecords) ?> record(s)</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead><tr><th>Type</th><th>From</th><th>To</th><th>Status</th></tr></thead>
                                    <tbody>
                                        <?php if (empty($leaveRecords)): ?>
                                            <tr><td colspan="4" class="text-muted">No leave records yet.</td></tr>
                                        <?php endif; ?>
                                        <?php foreach ($leaveRecords as $lr): ?>
                                            <tr>
                                                <td data-label="Type"><?= htmlspecialchars($lr['leave_type']) ?></td>
                                                <td data-label="From"><?= formatDate($lr['start_date']) ?></td>
                                                <td data-label="To"><?= formatDate($lr['end_date']) ?></td>
                                                <td data-label="Status"><?= getStatusBadge($lr['status']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>