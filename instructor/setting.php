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

// Handle profile update (name/phone/avatar) BEFORE header output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullName = sanitize($_POST['full_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $avatarPath = null;

    if ($fullName === '') {
        $error = 'Full name cannot be empty.';
    } else {
        // Fetch current user details to manage old avatar
        $currStmt = $pdo->prepare("SELECT avatar_url FROM users WHERE id = ?");
        $currStmt->execute([$userId]);
        $currentAvatar = $currStmt->fetchColumn();

        // Handle Avatar File Upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar'];
            $fileSize = $file['size'];
            $fileTmp = $file['tmp_name'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $allowedExtensions, true)) {
                $error = 'Invalid file format. Allowed formats: JPG, JPEG, PNG, WEBP.';
            } elseif ($fileSize > 2 * 1024 * 1024) {
                $error = 'File size exceeds maximum limit of 2MB.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($fileTmp);
                $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

                if (!in_array($mimeType, $allowedMimes, true)) {
                    $error = 'Uploaded file is not a valid image.';
                } else {
                    $uploadDir = __DIR__ . '/../uploads/avatars/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $newFileName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
                    $destination = $uploadDir . $newFileName;
                    $relativePath = 'uploads/avatars/' . $newFileName;

                    if (move_uploaded_file($fileTmp, $destination)) {
                        // Delete old avatar if present
                        if (!empty($currentAvatar) && file_exists(__DIR__ . '/../' . $currentAvatar)) {
                            @unlink(__DIR__ . '/../' . $currentAvatar);
                        }
                        $avatarPath = $relativePath;
                    } else {
                        $error = 'Failed to save uploaded image.';
                    }
                }
            }
        }

        if (empty($error)) {
            if ($avatarPath === null) {
                $avatarPath = $currentAvatar;
            }

            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, avatar_url = ? WHERE id = ?");
            $stmt->execute([$fullName, $phone, $avatarPath, $userId]);
            
            $_SESSION['full_name'] = $fullName;
            $_SESSION['avatar_url'] = $avatarPath;

            logActivity($userId, 'Update Profile', 'Updated profile details and avatar');
            $_SESSION['success'] = 'Profile updated successfully.';
            header('Location: ' . app_url('instructor/setting.php'));
            exit;
        }
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
    SELECT u.full_name, u.username, u.email, u.phone, u.avatar_url,
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

<style>
/* CSS fix for avatar preview box */
.avatar-preview .avatar {
    width: 48px !important;
    height: 48px !important;
    flex: 0 0 48px !important;
    border-radius: 12px !important;
    display: grid !important;
    place-items: center !important;
    overflow: hidden !important;
    font-size: 18px !important;
    font-weight: 800 !important;
    text-align: center !important;
}
.avatar-preview .avatar img {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
}
</style>

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
                            <form method="POST" action="" enctype="multipart/form-data">
                                
                                <!-- Profile Picture Upload Field -->
                                <div class="mb-3 d-flex align-items-center gap-3">
                                    <div class="avatar-preview">
                                        <?php 
                                            // Extract title prefix and grab ONLY the first letter
                                            $cleanName = trim(preg_replace('/^(Dr\.|Prof\.|Mr\.|Mrs\.|Ms\.)\s+/i', '', $profile['full_name'] ?? 'U'));
                                            $firstInitial = mb_substr($cleanName, 0, 1);
                                            $avatarUrl = !empty($profile['avatar_url']) ? app_url($profile['avatar_url']) : null;
                                        ?>
                                        <?= sic_user_avatar($avatarUrl, $firstInitial, 'avatar') ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <label class="form-label mb-1" for="avatar">Profile Picture</label>
                                        <input type="file" name="avatar" id="avatar" class="form-control" accept="image/png, image/jpeg, image/webp">
                                        <div class="form-text">JPG, PNG, or WEBP (Max 2MB).</div>
                                    </div>
                                </div>

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