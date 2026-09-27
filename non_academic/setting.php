<?php
/**
 * Non-Academic Staff - Profile Settings
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';

checkRole(ROLE_NON_ACADEMIC);

$userId = (int)($_SESSION['user_id'] ?? 0);
$error = '';

/* =========================
   UPDATE PROFILE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    $fullName = sanitize($_POST['full_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');

    if ($fullName === '') {
        $error = 'Full name cannot be empty.';
    } else {

        $stmt = $pdo->prepare(
            "SELECT avatar_url FROM users WHERE id = ?"
        );
        $stmt->execute([$userId]);

        $currentAvatar = $stmt->fetchColumn();
        $avatarPath = $currentAvatar;

        /* Upload avatar */
        if (
            isset($_FILES['avatar']) &&
            $_FILES['avatar']['error'] === UPLOAD_ERR_OK
        ) {

            $file = $_FILES['avatar'];

            $extension = strtolower(
                pathinfo($file['name'], PATHINFO_EXTENSION)
            );

            $allowedExtensions = [
                'jpg',
                'jpeg',
                'png',
                'webp'
            ];

            if (!in_array($extension, $allowedExtensions, true)) {

                $error = 'Invalid image format.';

            } elseif ($file['size'] > 2 * 1024 * 1024) {

                $error = 'Image must be smaller than 2MB.';

            } else {

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);

                $allowedMimes = [
                    'image/jpeg',
                    'image/png',
                    'image/webp'
                ];

                if (!in_array($mime, $allowedMimes, true)) {

                    $error = 'Invalid image file.';

                } else {

                    $uploadDir = __DIR__ . '/../uploads/avatars/';

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $fileName =
                        'avatar_' .
                        $userId .
                        '_' .
                        time() .
                        '.' .
                        $extension;

                    $destination = $uploadDir . $fileName;

                    $relativePath =
                        'uploads/avatars/' . $fileName;

                    if (move_uploaded_file(
                        $file['tmp_name'],
                        $destination
                    )) {

                        if (
                            !empty($currentAvatar) &&
                            file_exists(
                                __DIR__ . '/../' . $currentAvatar
                            )
                        ) {
                            @unlink(
                                __DIR__ . '/../' . $currentAvatar
                            );
                        }

                        $avatarPath = $relativePath;

                    } else {

                        $error = 'Failed to upload image.';
                    }
                }
            }
        }

        if ($error === '') {

            $stmt = $pdo->prepare(
                "UPDATE users
                 SET full_name = ?, phone = ?, avatar_url = ?
                 WHERE id = ?"
            );

            $stmt->execute([
                $fullName,
                $phone,
                $avatarPath,
                $userId
            ]);

            $_SESSION['full_name'] = $fullName;
            $_SESSION['avatar_url'] = $avatarPath;

            if (function_exists('logActivity')) {
                logActivity(
                    $userId,
                    'Update Profile',
                    'Updated non-academic staff profile'
                );
            }

            $_SESSION['success'] =
                'Profile updated successfully.';

            header(
                'Location: ' .
                app_url('non_academic/setting.php')
            );

            exit;
        }
    }
}


/* =========================
   CHANGE PASSWORD
========================= */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['change_password'])
) {

    $currentPassword =
        $_POST['current_password'] ?? '';

    $newPassword =
        $_POST['new_password'] ?? '';

    $confirmPassword =
        $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare(
        "SELECT password FROM users WHERE id = ?"
    );

    $stmt->execute([$userId]);

    $passwordHash = $stmt->fetchColumn();

    if (
        !$passwordHash ||
        !password_verify(
            $currentPassword,
            $passwordHash
        )
    ) {

        $error =
            'Current password is incorrect.';

    } elseif (strlen($newPassword) < 6) {

        $error =
            'New password must contain at least 6 characters.';

    } elseif ($newPassword !== $confirmPassword) {

        $error =
            'New password and confirmation do not match.';

    } else {

        $newHash =
            password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

        $stmt = $pdo->prepare(
            "UPDATE users
             SET password = ?
             WHERE id = ?"
        );

        $stmt->execute([
            $newHash,
            $userId
        ]);

        if (function_exists('logActivity')) {
            logActivity(
                $userId,
                'Change Password',
                'Changed account password'
            );
        }

        $_SESSION['success'] =
            'Password changed successfully.';

        header(
            'Location: ' .
            app_url('non_academic/setting.php')
        );

        exit;
    }
}


/* =========================
   LOAD USER
========================= */
$stmt = $pdo->prepare(
    "SELECT
        full_name,
        username,
        email,
        phone,
        avatar_url
     FROM users
     WHERE id = ?"
);

$stmt->execute([$userId]);

$profile = $stmt->fetch();

$pageTitle = 'Settings';

include __DIR__ . '/../includes/header.php';
?>

<div class="page-toolbar">
    <div>
        <h1>Settings</h1>
        <p>
            Manage your profile information and account security.
        </p>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>

    <div class="alert alert-success">
        <?= htmlspecialchars($_SESSION['success']) ?>
        <?php unset($_SESSION['success']); ?>
    </div>

<?php endif; ?>

<?php if ($error): ?>

    <div class="alert alert-danger">
        <?= htmlspecialchars($error) ?>
    </div>

<?php endif; ?>


<div class="row g-4">

    <!-- PROFILE -->
    <div class="col-md-6">

        <div class="card">

            <div class="card-header">
                <h5>Non-Academic Staff Profile</h5>
            </div>

            <div class="card-body">

                <p class="small mb-1">
                    <strong>Role:</strong>
                    Non-Academic Staff
                </p>

                <p class="small mb-1">
                    <strong>Username:</strong>
                    <?= htmlspecialchars(
                        $profile['username'] ?? ''
                    ) ?>
                </p>

                <p class="small mb-0">
                    <strong>Email:</strong>
                    <?= htmlspecialchars(
                        $profile['email'] ?? ''
                    ) ?>
                </p>

            </div>

        </div>


        <div class="card mt-4">

            <div class="card-header">
                <h5>Edit Profile</h5>
            </div>

            <div class="card-body">

                <form
                    method="POST"
                    enctype="multipart/form-data"
                >

                    <!-- AVATAR -->
                    <div class="mb-4">

                        <label class="form-label">
                            Profile Picture
                        </label>

                        <input
                            type="file"
                            name="avatar"
                            class="form-control"
                            accept="image/png,image/jpeg,image/webp"
                        >

                        <div class="form-text">
                            JPG, PNG or WEBP. Maximum 2MB.
                        </div>

                    </div>


                    <!-- NAME -->
                    <div class="mb-3">

                        <label class="form-label">
                            Full Name
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            name="full_name"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars(
                                $profile['full_name'] ?? ''
                            ) ?>"
                        >

                    </div>


                    <!-- USERNAME -->
                    <div class="mb-3">

                        <label class="form-label">
                            Username
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            disabled
                            value="<?= htmlspecialchars(
                                $profile['username'] ?? ''
                            ) ?>"
                        >

                    </div>


                    <!-- EMAIL -->
                    <div class="mb-3">

                        <label class="form-label">
                            Email
                        </label>

                        <input
                            type="email"
                            class="form-control"
                            disabled
                            value="<?= htmlspecialchars(
                                $profile['email'] ?? ''
                            ) ?>"
                        >

                        <div class="form-text">
                            Contact the administrator
                            to change your email.
                        </div>

                    </div>


                    <!-- PHONE -->
                    <div class="mb-3">

                        <label class="form-label">
                            Phone Number
                        </label>

                        <input
                            type="text"
                            name="phone"
                            class="form-control"
                            placeholder="07XXXXXXXX"
                            value="<?= htmlspecialchars(
                                $profile['phone'] ?? ''
                            ) ?>"
                        >

                    </div>


                    <button
                        type="submit"
                        name="update_profile"
                        class="btn btn-primary"
                    >
                        Save Changes
                    </button>

                </form>

            </div>

        </div>

    </div>


    <!-- PASSWORD -->
    <div class="col-md-6">

        <div class="card">

            <div class="card-header">
                <h5>Change Password</h5>
            </div>

            <div class="card-body">

                <form method="POST">

                    <div class="mb-3">

                        <label class="form-label">
                            Current Password
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="password"
                            name="current_password"
                            class="form-control"
                            required
                        >

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            New Password
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="password"
                            name="new_password"
                            class="form-control"
                            minlength="6"
                            required
                        >

                        <div class="form-text">
                            Minimum 6 characters.
                        </div>

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Confirm New Password
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="password"
                            name="confirm_password"
                            class="form-control"
                            minlength="6"
                            required
                        >

                    </div>


                    <button
                        type="submit"
                        name="change_password"
                        class="btn btn-outline-primary"
                    >
                        Update Password
                    </button>

                </form>

            </div>

        </div>

    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>