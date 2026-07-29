<?php
/**
 * Instructor - Profile Settings
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
$userId = (int)$_SESSION['user_id'];
$error = '';

// Handle profile update (name/phone) BEFORE header output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullName = sanitize($_POST['full_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');

    if ($fullName === '') {
        $error = 'Full name cannot be empty.';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
        $stmt->execute([$fullName, $phone, $userId]);
        $_SESSION['full_name'] = $fullName;
        logActivity($userId, 'Update Profile', 'Updated profile details');
        $_SESSION['success'] = 'Profile updated successfully.';
        header('Location: ' . app_url('instructor/setting.php'));
        exit;
    }
}

// Handle password change BEFORE header output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $hash = $stmt->fetchColumn();

    if (!$hash || !password_verify($currentPassword, $hash)) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New password and confirmation do not match.';
    } else {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd->execute([$newHash, $userId]);
        logActivity($userId, 'Change Password', 'Password changed successfully');
        $_SESSION['success'] = 'Password changed successfully.';
        header('Location: ' . app_url('instructor/setting.php'));
        exit;
    }
}

// Load profile + instructor details
$stmt = $pdo->prepare("
    SELECT u.full_name, u.username, u.email, u.phone,
           i.employee_id, i.designation, i.max_weekly_hours, i.status,
           d.name AS department_name, ast.name AS stream_name
    FROM users u
    LEFT JOIN instructors i ON i.user_id = u.id
    LEFT JOIN departments d ON i.department_id = d.id
    LEFT JOIN academic_streams ast ON i.academic_stream_id = ast.id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

$pageTitle = 'Settings';
include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1>Settings</h1>
                    <p>Manage your profile information and account security.</p>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header"><h5>Instructor Profile (Read-only)</h5></div>
                        <div class="card-body">
                            <p class="small mb-1"><strong>Employee ID:</strong> <?= htmlspecialchars($profile['employee_id'] ?? 'N/A') ?></p>
                            <p class="small mb-1"><strong>Designation:</strong> <?= htmlspecialchars($profile['designation'] ?? 'N/A') ?></p>
                            <p class="small mb-1"><strong>Department:</strong> <?= htmlspecialchars($profile['department_name'] ?? 'N/A') ?></p>
                            <p class="small mb-1"><strong>Academic Stream:</strong> <?= htmlspecialchars($profile['stream_name'] ?? 'N/A') ?></p>
                            <p class="small mb-1"><strong>Max Weekly Hours:</strong> <?= htmlspecialchars((string)($profile['max_weekly_hours'] ?? DEFAULT_MAX_WEEKLY_HOURS)) ?> hrs</p>
                            <p class="small mb-0"><strong>Status:</strong> <?= getStatusBadge($profile['status'] ?? 'active') ?></p>
                            <p class="small text-muted mt-2 mb-0">These fields are managed by the System Administrator.</p>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header"><h5>Edit Profile</h5></div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($profile['full_name'] ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($profile['username'] ?? '') ?>" disabled>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" disabled>
                                    <div class="form-text">Contact the administrator to change your email.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" name="phone" class="form-control" placeholder="07XXXXXXXX" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>">
                                </div>
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <span class="ui-dot" aria-hidden="true"></span>
                                    Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header"><h5>Change Password</h5></div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">Current Password <span class="text-danger">*</span></label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">New Password <span class="text-danger">*</span></label>
                                    <input type="password" name="new_password" class="form-control" required minlength="6">
                                    <div class="form-text">Minimum 6 characters.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                                </div>
                                <button type="submit" name="change_password" class="btn btn-outline-primary">
                                    <span class="ui-dot" aria-hidden="true"></span>
                                    Update Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
