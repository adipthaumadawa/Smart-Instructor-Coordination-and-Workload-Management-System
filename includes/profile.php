<?php
// Adjust path to root directory files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';

requireLogin();

$userId  = (int)$_SESSION['user_id'];
$success = '';
$error   = '';

// Fetch current user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitize($_POST['full_name'] ?? '');
    $phone    = sanitize($_POST['phone'] ?? '');
    
    // File upload processing
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file     = $_FILES['avatar'];
        $fileName = $file['name'];
        $fileSize = $file['size'];
        $fileTmp  = $file['tmp_name'];
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Validation 1: Allowed extension check
        if (!in_array($ext, $allowedExtensions, true)) {
            $error = 'Invalid file format. Allowed formats: JPG, PNG, WEBP.';
        }
        // Validation 2: File size limit (2MB)
        elseif ($fileSize > 2 * 1024 * 1024) {
            $error = 'File size exceeds maximum limit of 2MB.';
        }
        // Validation 3: MIME type verification
        else {
            $finfo        = new finfo(FILEINFO_MIME_TYPE);
            $mimeType     = $finfo->file($fileTmp);
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

            if (!in_array($mimeType, $allowedMimes, true)) {
                $error = 'Uploaded file is not a valid image.';
            } else {
                // Set path relative to project root (/uploads/avatars/)
                $uploadDir = __DIR__ . '/../uploads/avatars/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $newFileName  = 'avatar_' . $userId . '_' . time() . '.' . $ext;
                $destination  = $uploadDir . $newFileName;
                $relativePath = 'uploads/avatars/' . $newFileName;

                if (move_uploaded_file($fileTmp, $destination)) {
                    // Remove previous avatar file if exists
                    if (!empty($user['avatar_url']) && file_exists(__DIR__ . '/../' . $user['avatar_url'])) {
                        @unlink(__DIR__ . '/../' . $user['avatar_url']);
                    }

                    $user['avatar_url'] = $relativePath;
                } else {
                    $error = 'Failed to save uploaded image file.';
                }
            }
        }
    }

    if (empty($error)) {
        try {
            $updateStmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, avatar_url = ? WHERE id = ?");
            $updateStmt->execute([$fullName, $phone, $user['avatar_url'] ?? null, $userId]);

            // Update session values
            $_SESSION['full_name']  = $fullName;
            $_SESSION['avatar_url'] = $user['avatar_url'] ?? null;

            logActivity($userId, 'Update Profile', 'Updated profile information and avatar');
            $success = 'Profile updated successfully!';
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Profile Settings';
include __DIR__ . '/../includes/header.php';
?>

<div class="dash-page">
    <div class="dash-hero">
        <div class="dash-title-wrap">
            <h1>Profile Settings</h1>
            <p>Update your personal details and avatar image</p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="d-card" style="max-width: 680px; padding: 24px;">
        <form method="POST" action="" enctype="multipart/form-data">
            
            <!-- Avatar Upload Row -->
            <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 24px;">
                <div>
                    <?= sic_user_avatar($user['avatar_url'] ?? null, $user['full_name'], 'avatar') ?>
                </div>
                <div>
                    <label class="form-label" for="avatar">Profile Picture</label>
                    <input type="file" name="avatar" id="avatar" class="form-control" accept="image/png, image/jpeg, image/webp">
                    <div class="form-text">Allowed formats: JPG, PNG, WEBP (Max 2MB).</div>
                </div>
            </div>

            <!-- Profile Info Form Fields -->
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                <div class="form-text">System email address is managed by Administrator.</div>
            </div>

            <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
            </div>

            <div style="margin-top: 20px; text-align: right;">
                <button type="submit" class="btn btn-primary">Save Profile</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>