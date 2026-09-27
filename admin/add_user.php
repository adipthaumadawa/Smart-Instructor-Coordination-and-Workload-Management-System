<?php
/** Admin - Add New User */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';
checkRole(ROLE_ADMIN);

function sic_next_employee_id(PDO $pdo): string {
    $last = (int)$pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(employee_id, 4) AS UNSIGNED)), 0) FROM instructors WHERE employee_id REGEXP '^EMP[0-9]+$'")->fetchColumn();
    return 'EMP' . str_pad((string)($last + 1), 3, '0', STR_PAD_LEFT);
}

$error = '';
$existingUserId = 0;
$roles = $departments = $streams = [];
$generatedEmployeeId = '';
try {
    $roles = $pdo->query('SELECT id, role_name FROM roles ORDER BY role_name')->fetchAll(PDO::FETCH_ASSOC);
    $departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
    $streams = $pdo->query('SELECT id, name FROM academic_streams ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
    $generatedEmployeeId = sic_next_employee_id($pdo);
} catch (PDOException $e) {
    error_log('Add user form: ' . $e->getMessage());
    $error = 'Unable to load form options. Please try again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $fullName = trim(sanitize($_POST['full_name'] ?? ''));
    $username = trim(sanitize($_POST['username'] ?? ''));
    $email = trim(sanitize($_POST['email'] ?? ''));
    $phone = trim(sanitize($_POST['phone'] ?? ''));
    $password = $_POST['password'] ?? '';
    $roleId = (int)($_POST['role_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'active');
    // Designation is defined by the selected Instructor role, not by browser input.
    $designation = $roleId === ROLE_INSTRUCTOR ? 'Instructor' : '';
    $departmentId = (int)($_POST['department_id'] ?? 0);
    $streamId = (int)($_POST['academic_stream_id'] ?? 0);
    $hours = trim((string)($_POST['max_weekly_hours'] ?? '40.00'));
    $isInstructor = $roleId === ROLE_INSTRUCTOR;

    if ($fullName === '' || $username === '' || $email === '' || $password === '' || $roleId <= 0) {
        $error = 'Please fill all required user fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!in_array($status, ['active', 'inactive', 'suspended'], true)) {
        $error = 'Invalid account status.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($isInstructor && ($departmentId <= 0 || $streamId <= 0)) {
        $error = 'Please select department and academic stream.';
    } elseif ($isInstructor && (!is_numeric($hours) || (float)$hours < 1 || (float)$hours > 80)) {
        $error = 'Max weekly hours must be between 1 and 80.';
    } else {
        $locked = false;
        try {
            $duplicate = $pdo->prepare('SELECT id, status FROM users WHERE email = ? LIMIT 1');
            $duplicate->execute([$email]);
            $emailOwner = $duplicate->fetch(PDO::FETCH_ASSOC);
            $duplicate = $pdo->prepare('SELECT id, status FROM users WHERE username = ? LIMIT 1');
            $duplicate->execute([$username]);
            $usernameOwner = $duplicate->fetch(PDO::FETCH_ASSOC);
            if ($emailOwner) {
                $existingUserId = (int)$emailOwner['id'];
                $error = 'This email is already assigned to a user' . ($emailOwner['status'] === 'inactive' ? ' (inactive)' : '') . '. Edit that account or use a different email.';
            } elseif ($usernameOwner) {
                $existingUserId = (int)$usernameOwner['id'];
                $error = 'This username is already assigned to a user' . ($usernameOwner['status'] === 'inactive' ? ' (inactive)' : '') . '. Edit that account or use a different username.';
            } elseif (!in_array($roleId, array_map('intval', array_column($roles, 'id')), true)) {
                $error = 'Selected role does not exist.';
            } elseif ($isInstructor && (!in_array($departmentId, array_map('intval', array_column($departments, 'id')), true) || !in_array($streamId, array_map('intval', array_column($streams, 'id')), true))) {
                $error = 'Select a valid department and academic stream.';
            } else {
                if ($isInstructor) {
                    $locked = (int)$pdo->query("SELECT GET_LOCK('sic_instructor_employee_id', 10)")->fetchColumn() === 1;
                    if (!$locked) throw new RuntimeException('Employee ID generation is busy. Please try again.');
                }
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('INSERT INTO users (username, email, password, full_name, role_id, phone, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
                $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $fullName, $roleId, $phone, $status]);
                $newUserId = (int)$pdo->lastInsertId();
                if ($isInstructor) {
                    $employeeId = sic_next_employee_id($pdo);
                    $parts = preg_split('/\s+/', $fullName, 2);
                    $firstName = $parts[0];
                    $lastName = $parts[1] ?? $firstName;
                    $instructorStatus = $status === 'active' ? 'active' : 'inactive';
                    $stmt = $pdo->prepare('INSERT INTO instructors (user_id, employee_id, first_name, last_name, designation, department_id, academic_stream_id, max_weekly_hours, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
                    $stmt->execute([$newUserId, $employeeId, $firstName, $lastName, $designation, $departmentId, $streamId, (float)$hours, $instructorStatus]);
                }
                $pdo->commit();
                if ($locked) {
                    $pdo->query("SELECT RELEASE_LOCK('sic_instructor_employee_id')");
                    $locked = false;
                }
                if (function_exists('logActivity')) {
                    try { logActivity($_SESSION['user_id'] ?? null, 'Create User', "Created new user: {$fullName} (ID: {$newUserId})"); }
                    catch (Throwable $e) { error_log('Create User log: ' . $e->getMessage()); }
                }
                $_SESSION['success'] = 'User created successfully' . ($isInstructor ? " (Employee ID: {$employeeId})." : '.');
                header('Location: ' . app_url('admin/users.php'));
                exit;
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Create User: ' . $e->getMessage());
            $error = $e instanceof RuntimeException ? $e->getMessage() : 'Unable to create user. Please check the details and try again.';
        } finally {
            if ($locked) $pdo->query("SELECT RELEASE_LOCK('sic_instructor_employee_id')");
        }
    }
}

$pageTitle = 'Add New User';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-toolbar"><div><h1>Add New User</h1><p>Create an account and enter instructor details on this page.</p></div><a href="<?= app_url('admin/users.php') ?>" class="btn btn-outline-primary">Back to Users</a></div>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?><?php if ($existingUserId > 0): ?> <a href="<?= app_url('admin/edit_user.php?id=' . $existingUserId) ?>" class="alert-link">Edit existing user</a><?php endif; ?></div><?php endif; ?>
<form method="POST" action="">
  <style>#instructorProfileSection[hidden] { display: none !important; }</style>
  <div class="card admin-form-card mb-4"><div class="card-header admin-form-header"><div><h5>User Information</h5><p>Fields marked with * are required.</p></div></div><div class="card-body"><div class="row g-4">
    <div class="col-lg-6"><label class="form-label" for="full_name">Full Name *</label><input id="full_name" name="full_name" class="form-control" required maxlength="150" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"></div>
    <div class="col-lg-6"><label class="form-label" for="username">Username *</label><input id="username" name="username" class="form-control" required maxlength="50" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"></div>
    <div class="col-lg-6"><label class="form-label" for="email">Email Address *</label><input id="email" type="email" name="email" class="form-control" required maxlength="100" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"></div>
    <div class="col-lg-6"><label class="form-label" for="phone">Phone Number</label><input id="phone" name="phone" class="form-control" maxlength="20" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"></div>
    <div class="col-lg-6"><label class="form-label" for="role_id">Role *</label><select id="role_id" name="role_id" class="form-select" required><option value="">Select Role</option><?php foreach ($roles as $role): ?><option value="<?= (int)$role['id'] ?>" <?= (int)($_POST['role_id'] ?? 0) === (int)$role['id'] ? 'selected' : '' ?>><?= htmlspecialchars(ucwords(str_replace('_', ' ', $role['role_name']))) ?></option><?php endforeach; ?></select></div>
    <div class="col-lg-6"><label class="form-label" for="status">Account Status</label><select id="status" name="status" class="form-select"><?php foreach (['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended'] as $value => $label): ?><option value="<?= $value ?>" <?= ($_POST['status'] ?? 'active') === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
    <div class="col-lg-6"><label class="form-label" for="password">Password *</label><input id="password" type="password" name="password" class="form-control" required minlength="6"><div class="form-text">Minimum 6 characters.</div></div>
  </div></div></div>
  <div class="card admin-form-card mb-4" id="instructorProfileSection" hidden><div class="card-header admin-form-header"><div><h5>Instructor Profile Details</h5><p>Choose the department, academic stream, and maximum weekly hours. Employee ID and designation are assigned automatically.</p></div></div><div class="card-body"><div class="row g-4">
    <div class="col-lg-6"><label class="form-label" for="employee_id_preview">Employee ID</label><input id="employee_id_preview" class="form-control" value="<?= htmlspecialchars($generatedEmployeeId) ?>" readonly><div class="form-text">System automatically generated.</div></div>
    <div class="col-lg-6"><label class="form-label" for="designation">Designation</label><input id="designation" class="form-control" value="Instructor" readonly><div class="form-text">Set automatically from the selected role.</div></div>
    <div class="col-lg-4"><label class="form-label" for="department_id">Department *</label><select id="department_id" name="department_id" class="form-select"><option value="">Select Department</option><?php foreach ($departments as $department): ?><option value="<?= (int)$department['id'] ?>" <?= (int)($_POST['department_id'] ?? 0) === (int)$department['id'] ? 'selected' : '' ?>><?= htmlspecialchars($department['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-lg-4"><label class="form-label" for="academic_stream_id">Academic Stream *</label><select id="academic_stream_id" name="academic_stream_id" class="form-select"><option value="">Select Academic Stream</option><?php foreach ($streams as $stream): ?><option value="<?= (int)$stream['id'] ?>" <?= (int)($_POST['academic_stream_id'] ?? 0) === (int)$stream['id'] ? 'selected' : '' ?>><?= htmlspecialchars($stream['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-lg-4"><label class="form-label" for="max_weekly_hours">Max Weekly Hours *</label><input id="max_weekly_hours" type="number" name="max_weekly_hours" class="form-control" step="0.5" min="1" max="80" value="<?= htmlspecialchars($_POST['max_weekly_hours'] ?? '40.00') ?>"></div>
  </div></div></div>
  <div class="admin-form-actions mb-4"><a href="<?= app_url('admin/users.php') ?>" class="btn btn-outline-primary">Cancel</a><button type="submit" class="btn btn-primary">Save User</button></div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const role = document.getElementById('role_id');
  const instructor = document.getElementById('instructorProfileSection');
  const fields = ['department_id', 'academic_stream_id', 'max_weekly_hours'].map(id => document.getElementById(id));
  function updateRoleProfile() {
    const isInstructor = Number(role.value) === <?= (int)ROLE_INSTRUCTOR ?>;
    instructor.hidden = !isInstructor;
    fields.forEach(field => { field.required = isInstructor; field.disabled = !isInstructor; });
  }
  role.addEventListener('change', updateRoleProfile);
  updateRoleProfile();
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
