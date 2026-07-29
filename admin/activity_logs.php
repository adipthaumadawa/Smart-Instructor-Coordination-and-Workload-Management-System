<?php
/**
 * Admin - Activity Logs
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_ADMIN);

$pageTitle = "Activity Logs";
include __DIR__ . '/../includes/header.php';

// Get activity logs with user info
$logs = $pdo->query("
    SELECT al.*, u.full_name 
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC 
    LIMIT 100
")->fetchAll();
?>

            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><span class="ui-dot" aria-hidden="true"></span>Activity Logs</h1>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th>IP Address</th>
                                    <th>Date & Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $index => $log): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($log['full_name'] ?? 'System') ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($log['action']) ?></span></td>
                                    <td><?= htmlspecialchars($log['description']) ?></td>
                                    <td><code><?= htmlspecialchars($log['ip_address']) ?></code></td>
                                    <td><?= date('d M Y, h:i A', strtotime($log['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="mt-3 text-muted small">
                Showing last 100 activity logs. For full audit trail, check the database directly.
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>