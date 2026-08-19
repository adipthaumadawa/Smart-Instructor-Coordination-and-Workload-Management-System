<?php
/**
 * Non-Academic Staff - Leave Notifications
 * Smart Instructor Coordination and Workload Management System
 *
 * When an instructor records leave, Non-Academic Staff receive a
 * notification so they can update administrative records. They do not
 * approve or reject leave.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_NON_ACADEMIC);

$pageTitle = "Leave Notifications";
$currentUserId = (int)($_SESSION['user_id'] ?? 0);

// Mark all leave notifications for this user as read when page is opened
if ($currentUserId > 0) {
    try {
        $pdo->prepare("
            UPDATE notifications
               SET is_read = 1
             WHERE user_id = ? AND type = 'leave' AND is_read = 0
        ")->execute([$currentUserId]);
    } catch (Exception $e) {
        // non-fatal
    }
}

// Fetch leave notifications targeted at this Non-Academic Staff member
$stmt = $pdo->prepare("
    SELECT n.*,
           lr.leave_type, lr.start_date, lr.end_date, lr.reason, lr.status AS leave_status,
           iu.full_name AS instructor_name
    FROM notifications n
    LEFT JOIN leave_records lr ON n.related_id = lr.id AND n.type = 'leave'
    LEFT JOIN instructors i ON lr.instructor_id = i.id
    LEFT JOIN users iu ON i.user_id = iu.id
    WHERE n.user_id = :uid AND n.type = 'leave'
    ORDER BY n.created_at DESC
    LIMIT 50
");
$stmt->execute([':uid' => $currentUserId]);
$notifications = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1><i class="fas fa-bell me-2"></i>Leave Notifications</h1>
                    <p>Alerts when instructors record leave. Use these to keep administrative records up to date — no approval action is required from Non-Academic Staff.</p>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent leave alerts</h5>
                    <span class="text-muted small"><?= count($notifications) ?> notification<?= count($notifications) === 1 ? '' : 's' ?></span>
                </div>
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <p class="text-muted mb-0">No leave notifications yet. When an instructor records leave, you will be notified here.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($notifications as $notif): ?>
                                <div class="list-group-item px-0 py-3 border-bottom">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <strong><?= htmlspecialchars($notif['title']) ?></strong>
                                            <div class="text-muted small mt-1"><?= htmlspecialchars($notif['message']) ?></div>
                                            <?php if (!empty($notif['instructor_name'])): ?>
                                                <div class="mt-2 small">
                                                    <span class="me-3"><strong>Instructor:</strong> <?= htmlspecialchars($notif['instructor_name']) ?></span>
                                                    <?php if (!empty($notif['leave_type'])): ?>
                                                        <span class="me-3"><strong>Type:</strong> <?= htmlspecialchars($notif['leave_type']) ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($notif['start_date'])): ?>
                                                        <span class="me-3">
                                                            <strong>Duration:</strong>
                                                            <?= formatDate($notif['start_date']) ?>
                                                            <?php if (!empty($notif['end_date']) && $notif['end_date'] !== $notif['start_date']): ?>
                                                                – <?= formatDate($notif['end_date']) ?>
                                                            <?php endif; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($notif['leave_status'])): ?>
                                                        <span><?= getLeaveStatusBadge($notif['leave_status']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!empty($notif['reason'])): ?>
                                                    <div class="small text-muted mt-1"><strong>Remarks:</strong> <?= htmlspecialchars($notif['reason']) ?></div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end text-nowrap small text-muted">
                                            <?= formatDate($notif['created_at'], 'd M Y H:i') ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="small text-muted mb-0 mt-3">
                            <a href="<?= app_url('non_academic/leave_records.php') ?>">View full leave records →</a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
