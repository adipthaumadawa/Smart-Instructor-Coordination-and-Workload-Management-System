<?php
/**
 * Admin - Add New User
 * Smart Instructor Coordination and Workload Management System
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php'; // Required for sic_user_avatar() in navbar.php

checkRole(ROLE_ADMIN);

$error = '';

// Handle form submit BEFORE header/navbar output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $username  = sanitize($_POST['username'] ?? '');
    $email     = sanitize($_POST['email'] ?? '');
    $phone     = sanitize($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $role_id   = (int)($_POST['role_id'] ?? 0);
    $status    = sanitize($_POST['status'] ?? 'active');

    if ($full_name === '' || $username === '' || $email === '' || $password === '' || $role_id <= 0) {
        $error = 'Please fill all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!in_array($status, ['active', 'inactive', 'suspended'], true)) {
        $error = 'Invalid account status.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        try {
            $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? OR username = ?');
            $checkStmt->execute([$email, $username]);

            if ($checkStmt->fetch()) {
                $error = 'Email address or username already exists.';
            } else {
                $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE id = ?');
                $roleStmt->execute([$role_id]);

                if (!$roleStmt->fetch()) {
                    $error = 'Selected role does not exist.';
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $pdo->prepare('INSERT INTO users (username, email, password, full_name, role_id, phone, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
                    $stmt->execute([$username, $email, $hashedPassword, $full_name, $role_id, $phone, $status]);

                    $newUserId = (int)$pdo->lastInsertId();
                    if (function_exists('logActivity')) {
                        logActivity($_SESSION['user_id'] ?? null, 'Create User', "Created new user: {$full_name} (ID: {$newUserId})");
                    }

                    $_SESSION['success'] = 'User created successfully.';
                    header('Location: ' . app_url('admin/users.php'));
                    exit;
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

try {
    $roles = $pdo->query('SELECT id, role_name FROM roles ORDER BY role_name ASC')->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $roles = [];
    $error = 'Unable to load roles: ' . $e->getMessage();
}

$pageTitle = 'Add New User';
include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1>Add New User</h1>
                    <p>Create a new user account and assign a system access role.</p>
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
                        <p>Enter user details carefully. Fields marked with * are required.</p>
                    </div>
                </div>

                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" required placeholder="Enter full name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control" required placeholder="Enter username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required placeholder="example@ucsc.cmb.ac.lk" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control" placeholder="07XXXXXXXX" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role_id" class="form-select" required>
                                    <option value="">Select Role</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= (int)$role['id'] ?>" <?= ((int)($_POST['role_id'] ?? 0) === (int)$role['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $role['role_name']))) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Account Status</label>
                                <select name="status" class="form-select">
                                    <?php $selectedStatus = $_POST['status'] ?? 'active'; ?>
                                    <option value="active" <?= $selectedStatus === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $selectedStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    <option value="suspended" <?= $selectedStatus === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                </select>
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" required minlength="6" placeholder="Enter password">
                                <div class="form-text">Minimum 6 characters. Demo password can be password123.</div>
                            </div>
                        </div>

                        <div class="admin-form-actions">
                            <a href="<?= app_url('admin/users.php') ?>" class="btn btn-outline-primary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <span class="ui-dot" aria-hidden="true"></span>
                                Save User
                            </button>
                        </div>
                    </form>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>