<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_NON_ACADEMIC);

$pageTitle = "Leave Notifications";
include __DIR__ . '/../includes/header.php';

$notifications = $pdo->query("
    SELECT n.*, u.full_name 
    FROM notifications n
    JOIN users u ON n.user_id = u.id
    WHERE n.type = 'leave'
    ORDER BY n.created_at DESC LIMIT 30
")->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <h1 class="h2 mb-4"><i class="fas fa-bell me-2"></i>Leave Notifications</h1>

            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <p class="text-muted">No leave notifications yet.</p>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                            <div class="border-bottom py-2">
                                <strong><?= htmlspecialchars($notif['title']) ?></strong><br>
                                <small><?= htmlspecialchars($notif['message']) ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>