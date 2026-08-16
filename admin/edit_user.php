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
require_once __DIR__ . '/../includes/dashboard_ui.php'; // Required for sic_user_avatar() in navbar.php

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

// Fetch linked instructor profile data if applicable
$instructorStmt = $pdo->prepare('SELECT * FROM instructors WHERE user_id = ?');
$instructorStmt->execute([$userId]);
$instructorProfile = $instructorStmt->fetch(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $username  = sanitize($_POST['username'] ?? '');
    $email     = sanitize($_POST['email'] ?? '');
    $role_id   = (int)($_POST['role_id'] ?? 0);
    $status    = sanitize($_POST['status'] ?? 'active');
    $phone     = sanitize($_POST['phone'] ?? '');

    // Instructor profile specific inputs
    $employee_id        = sanitize($_POST['employee_id'] ?? '');
    $designation        = sanitize($_POST['designation'] ?? '');
    $department_id      = (int)($_POST['department_id'] ?? 0);
    $academic_stream_id = (int)($_POST['academic_stream_id'] ?? 0);
    $max_weekly_hours   = (float)($_POST['max_weekly_hours'] ?? 40);

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
                    $pdo->beginTransaction();

                    // Update main user account
                    $stmt = $pdo->prepare('UPDATE users SET full_name = ?, username = ?, email = ?, role_id = ?, status = ?, phone = ? WHERE id = ?');
                    $stmt->execute([$full_name, $username, $email, $role_id, $status, $phone, $userId]);

                    // If user has an instructor profile, update or create it
                    if ($instructorProfile) {
                        $updInst = $pdo->prepare('UPDATE instructors SET employee_id = ?, designation = ?, department_id = ?, academic_stream_id = ?, max_weekly_hours = ?, status = ? WHERE user_id = ?');
                        $updInst->execute([$employee_id, $designation, $department_id, $academic_stream_id, $max_weekly_hours, $status, $userId]);
                    } elseif ($employee_id !== '' && $department_id > 0 && $academic_stream_id > 0) {
                        // If they didn't have one before but admin filled it out, insert it
                        $insInst = $pdo->prepare('INSERT INTO instructors (user_id, employee_id, designation, department_id, academic_stream_id, max_weekly_hours, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
                        $insInst->execute([$userId, $employee_id, $designation, $department_id, $academic_stream_id, $max_weekly_hours, $status]);
                    }

                    $pdo->commit();

                    if (function_exists('logActivity')) {
                        logActivity($_SESSION['user_id'] ?? null, 'Update User', "Updated user ID: {$userId}");
                    }

                    $_SESSION['success'] = 'User updated successfully.';
                    header('Location: ' . app_url('admin/users.php'));
                    exit;
                }
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

$roles = $pdo->query('SELECT id, role_name FROM roles ORDER BY role_name ASC')->fetchAll(PDO::FETCH_ASSOC);
$departments = $pdo->query('SELECT id, name FROM departments ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
$streams = $pdo->query('SELECT id, name FROM academic_streams ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Edit User';
include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1>Edit User</h1>
                    <p>Update user account details, system access role, and instructor profile.</p>
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

            <form method="POST" action="">
                <div class="card admin-form-card mb-4">
                    <div class="card-header admin-form-header">
                        <div>
                            <h5>User Information</h5>
                            <p>Edit the selected user details carefully.</p>
                        </div>
                    </div>

                    <div class="card-body">
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
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Instructor Profile Details</h5>
                        <p class="text-muted small mb-0">Editable instructor workload and department parameters.</p>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <label class="form-label">Employee ID</label>
                                <input type="text" name="employee_id" class="form-control" placeholder="e.g. EMP001" value="<?= htmlspecialchars($_POST['employee_id'] ?? ($instructorProfile['employee_id'] ?? '')) ?>">
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Designation</label>
                                <input type="text" name="designation" class="form-control" placeholder="e.g. Senior Lecturer" value="<?= htmlspecialchars($_POST['designation'] ?? ($instructorProfile['designation'] ?? '')) ?>">
                            </div>

                            <div class="col-lg-4">
                                <label class="form-label">Department</label>
                                <select name="department_id" class="form-select">
                                    <option value="">Select Department</option>
                                    <?php $selectedDept = (int)($_POST['department_id'] ?? ($instructorProfile['department_id'] ?? 0)); ?>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= (int)$dept['id'] ?>" <?= $selectedDept === (int)$dept['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($dept['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-lg-4">
                                <label class="form-label">Academic Stream</label>
                                <select name="academic_stream_id" class="form-select">
                                    <option value="">Select Academic Stream</option>
                                    <?php $selectedStream = (int)($_POST['academic_stream_id'] ?? ($instructorProfile['academic_stream_id'] ?? 0)); ?>
                                    <?php foreach ($streams as $stream): ?>
                                        <option value="<?= (int)$stream['id'] ?>" <?= $selectedStream === (int)$stream['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($stream['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-lg-4">
                                <label class="form-label">Max Weekly Hours</label>
                                <input type="number" step="0.5" min="1" max="80" name="max_weekly_hours" class="form-control" value="<?= htmlspecialchars($_POST['max_weekly_hours'] ?? ($instructorProfile['max_weekly_hours'] ?? '40.00')) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="admin-form-actions mb-4" style="display: flex; justify-content: flex-end; gap: 10px;">
                    <a href="<?= app_url('admin/users.php') ?>" class="btn btn-outline-primary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <span class="ui-dot" aria-hidden="true"></span>
                        Update User Profile
                    </button>
                </div>
            </form>

<?php include __DIR__ . '/../includes/footer.php'; ?>