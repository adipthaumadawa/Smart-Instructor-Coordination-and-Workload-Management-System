<?php
/**
 * Forgot Password (Placeholder - For Academic Project)
 * In a real system this would send a reset link via email.
 */
session_start();
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password | <?= htmlspecialchars(SITE_NAME) ?></title>
  <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
</head>
<body class="login-page">

  <div class="login-card" style="max-width:520px; grid-template-columns:1fr;">
    <div class="login-form-panel">
      <h1 style="font-size:1.6rem; margin:0 0 8px;">🔑 Forgot Password</h1>
      <p style="color:var(--muted); margin:0 0 20px;">
        This is a placeholder page for the academic project.
      </p>

      <div class="message info" style="margin-bottom:20px;">
        <strong>For demo purposes:</strong><br>
        All test accounts use the password: <code>password123</code><br><br>
        In a real system, this page would allow users to request a password reset link via email.
      </div>

      <a class="btn btn-primary" href="<?= app_url('auth/login.php') ?>">← Back to Login</a>
    </div>
  </div>

</body>
</html>