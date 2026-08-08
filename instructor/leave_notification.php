<?php
/**
 * Instructor - Record Leave & View Notifications
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

// Handle "mark all notifications read" BEFORE header output
if (isset($_GET['mark_read'])) {
    $mr = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $mr->execute([$_SESSION['user_id']]);
    header('Location: ' . app_url('instructor/leave_notification.php'));
    exit;
}

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
            header('Location: ' . app_url('instructor/leave_notification.php'));
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

// My notifications
$notifStmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30");
$notifStmt->execute([$_SESSION['user_id']]);
$notifications = $notifStmt->fetchAll();
$unreadCount = 0;
foreach ($notifications as $n) { if (!$n['is_read']) $unreadCount++; }

$pageTitle = 'Leave & Notifications';
include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1>Leave & Notifications</h1>
                    <p>Record your leave and keep track of your recent notifications.</p>
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

                    <div class="card mt-4">
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

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Notifications <?php if ($unreadCount > 0): ?><span class="badge bg-danger"><?= $unreadCount ?> new</span><?php endif; ?></h5>
                            <?php if ($unreadCount > 0): ?>
                                <a href="?mark_read=1" class="btn btn-sm btn-outline-secondary">Mark all as read</a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (empty($notifications)): ?>
                                <p class="text-muted mb-0">No notifications yet.</p>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-2">
                                    <?php foreach ($notifications as $n): ?>
                                        <div class="p-3" style="border:1px solid var(--line,#e6e9ee); border-radius:10px; <?= !$n['is_read'] ? 'background:#f7f9fc;' : '' ?>">
                                            <div class="d-flex justify-content-between">
                                                <strong><?= htmlspecialchars($n['title']) ?></strong>
                                                <?php if (!$n['is_read']): ?><span class="badge bg-secondary">New</span><?php endif; ?>
                                            </div>
                                            <p class="small mb-1" style="margin-top:4px;"><?= htmlspecialchars($n['message']) ?></p>
                                            <span class="small text-muted"><?= formatDate($n['created_at'], 'd M Y, h:i A') ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
