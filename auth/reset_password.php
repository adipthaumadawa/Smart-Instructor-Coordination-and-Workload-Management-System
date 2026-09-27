<?php
/** Redeem a one-time reset token; never place passwords in links. */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
if (empty($_SESSION['reset_csrf'])) {
    $_SESSION['reset_csrf'] = bin2hex(random_bytes(32));
}
$token = (string)($_POST['token'] ?? $_GET['token'] ?? '');
$validFormat = (bool)preg_match('/\A[a-f0-9]{64}\z/D', $token);
$error = '';
$valid = false;
$tokenHash = $validFormat ? hash('sha256', $token) : '';

try {
    if ($validFormat) {
        $check = $pdo->prepare("SELECT pr.id FROM password_resets pr JOIN users u ON u.id = pr.user_id WHERE pr.token_hash = ? AND pr.used_at IS NULL AND pr.expires_at > NOW() AND u.status = 'active' LIMIT 1");
        $check->execute([$tokenHash]);
        $valid = (bool)$check->fetchColumn();
    }
} catch (Throwable $e) {
    error_log('Password reset token lookup: ' . $e->getMessage());
    $error = 'Password reset is temporarily unavailable.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    if (!hash_equals($_SESSION['reset_csrf'], (string)($_POST['csrf'] ?? ''))) {
        $error = 'This form expired. Open your reset link again.';
    } elseif (strlen($password) < 12) {
        $error = 'Choose a password with at least 12 characters.';
    } elseif (!hash_equals($password, $confirm)) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $pdo->beginTransaction();
            $lock = $pdo->prepare("SELECT pr.id, pr.user_id FROM password_resets pr JOIN users u ON u.id = pr.user_id WHERE pr.token_hash = ? AND pr.used_at IS NULL AND pr.expires_at > NOW() AND u.status = 'active' FOR UPDATE");
            $lock->execute([$tokenHash]);
            $row = $lock->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $pdo->rollBack();
                $valid = false;
            } else {
                $update = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                $update->execute([password_hash($password, PASSWORD_DEFAULT), (int)$row['user_id']]);
                $consume = $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL');
                $consume->execute([(int)$row['user_id']]);
                $pdo->commit();
                $_SESSION['reset_csrf'] = bin2hex(random_bytes(32));
                header('Location: ' . app_url('auth/login.php?reset=1'));
                exit;
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Password reset update failed: ' . $e->getMessage());
            $error = 'Unable to save your new password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="referrer" content="no-referrer">
  <title>Reset Password | <?= htmlspecialchars(SITE_NAME) ?></title>
  <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
  <style>
    .recovery-card { width:min(100% - 32px,480px); margin:auto; padding:36px; background:#fff; border-radius:18px; box-shadow:0 12px 36px rgba(13,35,62,.1); }
    .recovery-card h1 { margin:0 0 8px; color:#0b203d; }
    .recovery-card p { color:#64748b; line-height:1.55; }
    .recovery-card .portal-control { padding-left:14px; }
    .recovery-card .portal-form { margin-top:24px; }
    .recovery-back { display:inline-block; margin-top:18px; color:#087f8c; }
  </style>
</head>
<body class="login-body" style="display:flex;min-height:100vh;background:#f1f5f9;">
<main class="recovery-card">
  <h1>Reset Password</h1>
  <?php if ($error): ?><div class="portal-alert portal-alert-error" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if (!$valid): ?>
    <p>This reset link has expired or has already been used.</p>
    <a class="recovery-back" href="<?= app_url('auth/forgot_password.php') ?>">Request another reset link</a>
  <?php else: ?>
    <p>Choose a new password for your account.</p>
    <form class="portal-form" method="post" action="<?= app_url('auth/reset_password.php') ?>">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['reset_csrf']) ?>">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
      <div class="portal-field"><label class="portal-label" for="password">New Password</label><input class="portal-control" id="password" type="password" name="password" minlength="12" autocomplete="new-password" required></div>
      <div class="portal-field"><label class="portal-label" for="confirm_password">Confirm Password</label><input class="portal-control" id="confirm_password" type="password" name="confirm_password" minlength="12" autocomplete="new-password" required></div>
      <button class="portal-submit-btn" type="submit">Save New Password</button>
    </form>
  <?php endif; ?>
  <a class="recovery-back" href="<?= app_url('auth/login.php') ?>">← Back to Login</a>
</main>
</body>
</html>
