<?php
/**
 * Admin - Manage Users
 * Smart Instructor Coordination and Workload Management System
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';

checkRole(ROLE_ADMIN);

// Handle deactivate BEFORE header/navbar output
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    if ($deleteId !== (int)($_SESSION['user_id'] ?? 0)) {
        $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
        $stmt->execute([$deleteId]);
        if (function_exists('logActivity')) {
            logActivity($_SESSION['user_id'] ?? null, 'Deactivate User', "Deactivated user ID: {$deleteId}");
        }
        $_SESSION['success'] = 'User deactivated successfully.';
    } else {
        $_SESSION['error'] = 'You cannot deactivate your own account.';
    }
    header('Location: ' . app_url('admin/users.php'));
    exit;
}

$users = $pdo->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Manage Users';
include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1>Manage Users</h1>
                    <p>Create, update, and deactivate user accounts.</p>
                </div>
                <a href="<?= app_url('admin/add_user.php') ?>" class="btn btn-primary">
                    <span class="ui-dot" aria-hidden="true"></span>
                    Add New User
                </a>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5>System Users</h5>
                    <span class="text-muted small"><?= count($users) ?> accounts</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle admin-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $index => $user): ?>
                                    <tr>
                                        <td data-label="#"><?= $index + 1 ?></td>
                                        <td data-label="User">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="avatar-mini"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></span>
                                                <strong><?= htmlspecialchars($user['full_name']) ?></strong>
                                            </div>
                                        </td>
                                        <td data-label="Username"><?= htmlspecialchars($user['username']) ?></td>
                                        <td data-label="Email"><?= htmlspecialchars($user['email']) ?></td>
                                        <td data-label="Role"><span class="status-pill pill-blue"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $user['role_name']))) ?></span></td>
                                        <td data-label="Status"><?= getStatusBadge($user['status']) ?></td>
                                        <td data-label="Created"><?= !empty($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : 'N/A' ?></td>
                                        <td data-label="Actions" class="text-end action-cell">
                                            <a href="<?= app_url('admin/edit_user.php?id=' . (int)$user['id']) ?>" class="btn btn-sm btn-outline-primary">
                                                <span class="ui-dot" aria-hidden="true"></span>
                                                Edit
                                            </a>
                                            <?php if ((int)$user['id'] !== (int)($_SESSION['user_id'] ?? 0)): ?>
                                                <a href="?delete=<?= (int)$user['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('This will deactivate the user, not permanently delete. Continue?')">
                                                    <span class="ui-dot" aria-hidden="true"></span>
                                                    Deactivate
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
