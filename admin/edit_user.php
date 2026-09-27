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
require_once __DIR__ . '/../includes/dashboard_ui.php';

checkRole(ROLE_ADMIN);

function sic_next_edit_employee_id(PDO $pdo): string
{
    $number = (int)$pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(employee_id, 4) AS UNSIGNED)), 0) FROM instructors WHERE employee_id REGEXP '^EMP[0-9]+$'")->fetchColumn();
    return 'EMP' . str_pad((string)($number + 1), 3, '0', STR_PAD_LEFT);
}

$userId = (int)($_GET['id'] ?? 0);
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

$stmt = $pdo->prepare('SELECT * FROM instructors WHERE user_id = ?');
$stmt->execute([$userId]);
$instructorProfile = $stmt->fetch(PDO::FETCH_ASSOC);
$error = '';

try {
    $roles = $pdo->query('SELECT id, role_name FROM roles ORDER BY role_name')->fetchAll(PDO::FETCH_ASSOC);
    $departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
    $streams = $pdo->query('SELECT id, name FROM academic_streams ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
    $employeeIdShown = $instructorProfile['employee_id'] ?? sic_next_edit_employee_id($pdo);
} catch (PDOException $e) {
    error_log('Edit User form: ' . $e->getMessage());
    $error = 'Unable to load form options. Please try again.';
    $roles = $departments = $streams = [];
    $employeeIdShown = $instructorProfile['employee_id'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $fullName = trim(sanitize($_POST['full_name'] ?? ''));
    $username = trim(sanitize($_POST['username'] ?? ''));
    $email = trim(sanitize($_POST['email'] ?? ''));
    $phone = trim(sanitize($_POST['phone'] ?? ''));
    $roleId = (int)($_POST['role_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'active');
    $departmentId = (int)($_POST['department_id'] ?? 0);
    $streamId = (int)($_POST['academic_stream_id'] ?? 0);
    $hours = trim((string)($_POST['max_weekly_hours'] ?? '40.00'));
    $isInstructor = $roleId === ROLE_INSTRUCTOR;

    if ($fullName === '' || $username === '' || $email === '' || $roleId <= 0) {
        $error = 'Full name, username, email and role are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!in_array($status, ['active', 'inactive', 'suspended'], true)) {
        $error = 'Invalid account status.';
    } elseif ($isInstructor && ($departmentId <= 0 || $streamId <= 0)) {
        $error = 'Please select department and academic stream.';
    } elseif ($isInstructor && (!is_numeric($hours) || (float)$hours < 1 || (float)$hours > 80)) {
        $error = 'Max weekly hours must be between 1 and 80.';
    } else {
        $locked = false;
        try {
            $duplicate = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
            $duplicate->execute([$email, $userId]);
            $emailTaken = (bool)$duplicate->fetchColumn();
            $duplicate = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
            $duplicate->execute([$username, $userId]);
            $usernameTaken = (bool)$duplicate->fetchColumn();
            if ($emailTaken) {
                $error = 'This email is already assigned to another user.';
            } elseif ($usernameTaken) {
                $error = 'This username is already assigned to another user.';
            } elseif (!in_array($roleId, array_map('intval', array_column($roles, 'id')), true)) {
                $error = 'Selected role does not exist.';
            } elseif ($isInstructor && (!in_array($departmentId, array_map('intval', array_column($departments, 'id')), true) || !in_array($streamId, array_map('intval', array_column($streams, 'id')), true))) {
                $error = 'Select a valid department and academic stream.';
            } else {
                if ($isInstructor && !$instructorProfile) {
                    $locked = (int)$pdo->query("SELECT GET_LOCK('sic_instructor_employee_id', 10)")->fetchColumn() === 1;
                    if (!$locked) {
                        throw new RuntimeException('Employee ID generation is busy. Please try again.');
                    }
                }
                $pdo->beginTransaction();
                $updateUser = $pdo->prepare('UPDATE users SET full_name = ?, username = ?, email = ?, role_id = ?, status = ?, phone = ? WHERE id = ?');
                $updateUser->execute([$fullName, $username, $email, $roleId, $status, $phone, $userId]);

                if ($isInstructor) {
                    $parts = preg_split('/\s+/', $fullName, 2);
                    $firstName = $parts[0];
                    $lastName = $parts[1] ?? $firstName;
                    $instructorStatus = $status === 'active' ? 'active' : 'inactive';
                    if ($instructorProfile) {
                        // Employee ID is stable and cannot be changed from a submitted form.
                        $updateInstructor = $pdo->prepare('UPDATE instructors SET first_name = ?, last_name = ?, designation = ?, department_id = ?, academic_stream_id = ?, max_weekly_hours = ?, status = ? WHERE user_id = ?');
                        $updateInstructor->execute([$firstName, $lastName, 'Instructor', $departmentId, $streamId, (float)$hours, $instructorStatus, $userId]);
                    } else {
                        $employeeId = sic_next_edit_employee_id($pdo);
                        $insertInstructor = $pdo->prepare('INSERT INTO instructors (user_id, employee_id, first_name, last_name, designation, department_id, academic_stream_id, max_weekly_hours, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
                        $insertInstructor->execute([$userId, $employeeId, $firstName, $lastName, 'Instructor', $departmentId, $streamId, (float)$hours, $instructorStatus]);
                    }
                } elseif ($instructorProfile) {
                    // Keep linked task and leave history when an Instructor changes role.
                    $deactivateInstructor = $pdo->prepare("UPDATE instructors SET status = 'inactive' WHERE user_id = ?");
                    $deactivateInstructor->execute([$userId]);
                }

                $pdo->commit();
                if ($locked) {
                    $pdo->query("SELECT RELEASE_LOCK('sic_instructor_employee_id')");
                    $locked = false;
                }
                if (function_exists('logActivity')) {
                    try {
                        logActivity($_SESSION['user_id'] ?? null, 'Update User', "Updated user ID: {$userId}");
                    } catch (Throwable $logError) {
                        error_log('Edit User activity log: ' . $logError->getMessage());
                    }
                }
                $_SESSION['success'] = 'User updated successfully.';
                header('Location: ' . app_url('admin/users.php'));
                exit;
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Edit User failed: ' . $e->getMessage());
            $error = $e instanceof RuntimeException ? $e->getMessage() : 'Unable to update user. Please check the details and try again.';
        } finally {
            if ($locked) {
                $pdo->query("SELECT RELEASE_LOCK('sic_instructor_employee_id')");
            }
        }
    }
}

$pageTitle = 'Edit User';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-toolbar">
    <div><h1>Edit User</h1><p>Update account details and the instructor profile when applicable.</p></div>
    <a href="<?= app_url('admin/users.php') ?>" class="btn btn-outline-primary"><span class="ui-dot" aria-hidden="true"></span> Back to Users</a>
</div>
<?php if ($error): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2"><span class="ui-dot" aria-hidden="true"></span><span><?= htmlspecialchars($error) ?></span></div>
<?php endif; ?>
<form method="POST" action="">
    <style>#instructorProfileSection[hidden] { display: none !important; }</style>
    <div class="card admin-form-card mb-4">
        <div class="card-header admin-form-header"><div><h5>User Information</h5><p>Edit the selected user details carefully.</p></div></div>
        <div class="card-body"><div class="row g-4">
            <div class="col-lg-6"><label class="form-label" for="full_name">Full Name <span class="text-danger">*</span></label><input id="full_name" type="text" name="full_name" class="form-control" maxlength="150" required value="<?= htmlspecialchars($_POST['full_name'] ?? $user['full_name']) ?>"></div>
            <div class="col-lg-6"><label class="form-label" for="username">Username <span class="text-danger">*</span></label><input id="username" type="text" name="username" class="form-control" maxlength="50" required value="<?= htmlspecialchars($_POST['username'] ?? $user['username']) ?>"></div>
            <div class="col-lg-6"><label class="form-label" for="email">Email Address <span class="text-danger">*</span></label><input id="email" type="email" name="email" class="form-control" maxlength="100" required value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>"></div>
            <div class="col-lg-6"><label class="form-label" for="phone">Phone Number</label><input id="phone" type="text" name="phone" class="form-control" maxlength="20" value="<?= htmlspecialchars($_POST['phone'] ?? ($user['phone'] ?? '')) ?>"></div>
            <div class="col-lg-6"><label class="form-label" for="role_id">Role <span class="text-danger">*</span></label><select id="role_id" name="role_id" class="form-select" required><?php $selectedRole = (int)($_POST['role_id'] ?? $user['role_id']); foreach ($roles as $role): ?><option value="<?= (int)$role['id'] ?>" <?= $selectedRole === (int)$role['id'] ? 'selected' : '' ?>><?= htmlspecialchars(ucwords(str_replace('_', ' ', $role['role_name']))) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-6"><label class="form-label" for="status">Account Status</label><select id="status" name="status" class="form-select"><?php $selectedStatus = $_POST['status'] ?? $user['status']; foreach (['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended'] as $value => $label): ?><option value="<?= $value ?>" <?= $selectedStatus === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
        </div></div>
    </div>

    <div class="card admin-form-card mb-4" id="instructorProfileSection" <?= $selectedRole === ROLE_INSTRUCTOR ? '' : 'hidden' ?>>
        <div class="card-header admin-form-header"><div><h5>Instructor Profile Details</h5><p>Department, academic stream, and workload. Employee ID and designation are set by the system.</p></div></div>
        <div class="card-body"><div class="row g-4">
            <div class="col-lg-6"><label class="form-label" for="employee_id_preview">Employee ID</label><input id="employee_id_preview" type="text" class="form-control" readonly value="<?= htmlspecialchars($employeeIdShown) ?>"><div class="form-text">System automatically generated.</div></div>
            <div class="col-lg-6"><label class="form-label" for="designation">Designation</label><input id="designation" type="text" class="form-control" readonly value="Instructor"><div class="form-text">Set automatically from the Instructor role.</div></div>
            <div class="col-lg-4"><label class="form-label" for="department_id">Department <span class="text-danger">*</span></label><select id="department_id" name="department_id" class="form-select"><option value="">Select Department</option><?php $selectedDepartment = (int)($_POST['department_id'] ?? ($instructorProfile['department_id'] ?? 0)); foreach ($departments as $department): ?><option value="<?= (int)$department['id'] ?>" <?= $selectedDepartment === (int)$department['id'] ? 'selected' : '' ?>><?= htmlspecialchars($department['name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-4"><label class="form-label" for="academic_stream_id">Academic Stream <span class="text-danger">*</span></label><select id="academic_stream_id" name="academic_stream_id" class="form-select"><option value="">Select Academic Stream</option><?php $selectedStream = (int)($_POST['academic_stream_id'] ?? ($instructorProfile['academic_stream_id'] ?? 0)); foreach ($streams as $stream): ?><option value="<?= (int)$stream['id'] ?>" <?= $selectedStream === (int)$stream['id'] ? 'selected' : '' ?>><?= htmlspecialchars($stream['name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-4"><label class="form-label" for="max_weekly_hours">Max Weekly Hours <span class="text-danger">*</span></label><input id="max_weekly_hours" type="number" step="0.5" min="1" max="80" name="max_weekly_hours" class="form-control" value="<?= htmlspecialchars($_POST['max_weekly_hours'] ?? ($instructorProfile['max_weekly_hours'] ?? '40.00')) ?>"></div>
        </div></div>
    </div>
    <div class="admin-form-actions mb-4">
        <a href="<?= app_url('admin/users.php') ?>" class="btn btn-outline-primary">Cancel</a>
        <button type="submit" class="btn btn-primary"><span class="ui-dot" aria-hidden="true"></span> Update User Profile</button>
    </div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const role = document.getElementById('role_id');
    const profile = document.getElementById('instructorProfileSection');
    const fields = ['department_id', 'academic_stream_id', 'max_weekly_hours'].map(id => document.getElementById(id));
    function updateInstructorProfile() {
        const isInstructor = Number(role.value) === <?= (int)ROLE_INSTRUCTOR ?>;
        profile.hidden = !isInstructor;
        fields.forEach(field => { field.required = isInstructor; field.disabled = !isInstructor; });
    }
    role.addEventListener('change', updateInstructorProfile);
    updateInstructorProfile();
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
