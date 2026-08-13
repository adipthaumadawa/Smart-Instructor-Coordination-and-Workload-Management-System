<?php
/**
 * Instructor - Notifications
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

// Handle "mark all notifications read" BEFORE header output
if (isset($_GET['mark_read'])) {
    $mr = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $mr->execute([$_SESSION['user_id']]);
    header('Location: ' . app_url('instructor/notifications.php'));
    exit;
}

// My notifications
$notifStmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30");
$notifStmt->execute([$_SESSION['user_id']]);
$notifications = $notifStmt->fetchAll();
$unreadCount = 0;
foreach ($notifications as $n) { if (!$n['is_read']) $unreadCount++; }

$pageTitle = 'Notifications';
include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1>Notifications</h1>
                    <p>Stay up to date with alerts and updates related to you.</p>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>

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

<?php include __DIR__ . '/../includes/footer.php'; ?>