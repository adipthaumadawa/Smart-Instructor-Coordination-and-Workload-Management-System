<?php
/**
 * Admin - Edit User
 * Smart Instructor Coordination and Workload Management System
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';

checkRole(ROLE_ADMIN);

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($userId <= 0) {
    header('Location: ' . app_url('admin/users.php'));
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['error'] = 'User not found.';
    header('Location: ' . app_url('admin/users.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $role_id = (int)($_POST['role_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'active');
    $phone = sanitize($_POST['phone'] ?? '');

    if ($full_name === '' || $username === '' || $email === '' || $role_id <= 0) {
        $error = 'Full name, username, email and role are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!in_array($status, ['active', 'inactive', 'suspended'], true)) {
        $error = 'Invalid account status.';
    } else {
        try {
            $duplicateStmt = $pdo->prepare('SELECT id FROM users WHERE (email = ? OR username = ?) AND id <> ?');
            $duplicateStmt->execute([$email, $username, $userId]);

            if ($duplicateStmt->fetch()) {
                $error = 'Email address or username already exists.';
            } else {
                $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE id = ?');
                $roleStmt->execute([$role_id]);

                if (!$roleStmt->fetch()) {
                    $error = 'Selected role does not exist.';
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET full_name = ?, username = ?, email = ?, role_id = ?, status = ?, phone = ? WHERE id = ?');
                    $stmt->execute([$full_name, $username, $email, $role_id, $status, $phone, $userId]);

                    if (function_exists('logActivity')) {
                        logActivity($_SESSION['user_id'] ?? null, 'Update User', "Updated user ID: {$userId}");
                    }

                    $_SESSION['success'] = 'User updated successfully.';
                    header('Location: ' . app_url('admin/users.php'));
                    exit;
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

$roles = $pdo->query('SELECT id, role_name FROM roles ORDER BY role_name ASC')->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Edit User';
include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1>Edit User</h1>
                    <p>Update user account details and system access role.</p>
                </div>
                <a href="<?= app_url('admin/users.php') ?>" class="btn btn-outline-primary">
                    <span class="ui-dot" aria-hidden="true"></span>
                    Back to Users
                </a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2">
                    <span class="ui-dot" aria-hidden="true"></span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <div class="card admin-form-card">
                <div class="card-header admin-form-header">
                    <div>
                        <h5>User Information</h5>
                        <p>Edit the selected user details carefully.</p>
                    </div>
                </div>

                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($_POST['full_name'] ?? $user['full_name']) ?>">
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($_POST['username'] ?? $user['username']) ?>">
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>">
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? ($user['phone'] ?? '')) ?>">
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role_id" class="form-select" required>
                                    <?php $selectedRole = (int)($_POST['role_id'] ?? $user['role_id']); ?>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= (int)$role['id'] ?>" <?= $selectedRole === (int)$role['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $role['role_name']))) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Account Status</label>
                                <select name="status" class="form-select">
                                    <?php $selectedStatus = $_POST['status'] ?? $user['status']; ?>
                                    <option value="active" <?= $selectedStatus === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $selectedStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    <option value="suspended" <?= $selectedStatus === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                </select>
                            </div>
                        </div>

                        <div class="admin-form-actions">
                            <a href="<?= app_url('admin/users.php') ?>" class="btn btn-outline-primary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <span class="ui-dot" aria-hidden="true"></span>
                                Update User
                            </button>
                        </div>
                    </form>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
