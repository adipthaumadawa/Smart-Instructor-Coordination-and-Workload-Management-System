<?php
/** Request a single-use password reset link. */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/password_reset_mail.php';

header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
if (empty($_SESSION['reset_csrf'])) {
    $_SESSION['reset_csrf'] = bin2hex(random_bytes(32));
}
$error = '';
$success = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $csrf = (string)($_POST['csrf'] ?? '');
    if (!hash_equals($_SESSION['reset_csrf'], $csrf)) {
        $error = 'This form expired. Refresh the page and try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (!passwordResetMailReady()) {
        $error = 'Email recovery is not configured yet. Please contact the system administrator.';
    } else {
        try {
            $emailHash = hash('sha256', strtolower($email));
            $ipHash = hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            $recentEmail = $pdo->prepare('SELECT COUNT(*) FROM password_reset_requests WHERE email_hash = ? AND requested_at > NOW() - INTERVAL 60 SECOND');
            $recentEmail->execute([$emailHash]);
            $recentIp = $pdo->prepare('SELECT COUNT(*) FROM password_reset_requests WHERE ip_hash = ? AND requested_at > NOW() - INTERVAL 1 HOUR');
            $recentIp->execute([$ipHash]);
            $limited = (int)$recentEmail->fetchColumn() > 0 || (int)$recentIp->fetchColumn() >= 5;

            // Same response for known and unknown accounts, including rate-limited requests.
            if (!$limited) {
                $record = $pdo->prepare('INSERT INTO password_reset_requests (email_hash, ip_hash) VALUES (?, ?)');
                $record->execute([$emailHash, $ipHash]);
                $userStmt = $pdo->prepare("SELECT id, email FROM users WHERE email = ? AND status = 'active' LIMIT 1");
                $userStmt->execute([$email]);
                $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    $token = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $token);
                    $pdo->beginTransaction();
                    $delete = $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?');
                    $delete->execute([(int)$user['id']]);
                    $insert = $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))');
                    $insert->execute([(int)$user['id'], $tokenHash]);
                    $pdo->commit();
                    try {
                        sendPasswordResetLink($user['email'], $token);
                    } catch (Throwable $mailError) {
                        $remove = $pdo->prepare('DELETE FROM password_resets WHERE token_hash = ?');
                        $remove->execute([$tokenHash]);
                        error_log('Password reset email failed: ' . $mailError->getMessage());
                    }
                }
            }
            $success = 'If an active account uses that email, a reset link will be sent. Check your inbox and spam folder.';
            $email = '';
            $_SESSION['reset_csrf'] = bin2hex(random_bytes(32));
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Password reset request failed: ' . $e->getMessage());
            $error = 'Unable to process the request right now. Please try again later.';
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
  <title>Forgot Password | <?= htmlspecialchars(SITE_NAME) ?></title>
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
  <h1>Forgot Password</h1>
  <p>Enter your account email and we will send a link to create a new password.</p>
  <?php if ($error): ?><div class="portal-alert portal-alert-error" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="portal-alert portal-alert-success" role="status"><?= htmlspecialchars($success) ?></div><?php endif; ?>
  <form class="portal-form" method="post">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['reset_csrf']) ?>">
    <div class="portal-field"><label class="portal-label" for="email">Account Email</label><input class="portal-control" type="email" id="email" name="email" autocomplete="email" required maxlength="100" value="<?= htmlspecialchars($email) ?>"></div>
    <button class="portal-submit-btn" type="submit">Send Reset Link</button>
  </form>
  <a class="recovery-back" href="<?= app_url('auth/login.php') ?>">← Back to Login</a>
</main>
</body>
</html>
