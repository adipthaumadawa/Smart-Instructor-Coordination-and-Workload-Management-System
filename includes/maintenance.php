<?php
/**
 * Maintenance notice shown to non-admin users while maintenance mode is on.
 * Rendered standalone (no sidebar/navbar) from checkRole().
 */
$siteName = function_exists('sic_site_name') ? sic_site_name() : SITE_NAME;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Under Maintenance | <?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="icon" type="image/png" href="<?= app_url('assets/images/favicon.png') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
  <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
  <link rel="stylesheet" href="<?= app_url('assets/css/auth.css') ?>">
</head>
<body class="login-body recovery-body">
<main class="maintenance-card">
  <img src="<?= app_url('assets/images/ucsc-logo.png') ?>" alt="Logo">
  <h1>Under Maintenance</h1>
  <p class="maintenance-site"><?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?></p>

  <div class="portal-alert portal-alert-error" role="alert">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
    <span>The system is temporarily unavailable while administrators perform maintenance. Please try again later.</span>
  </div>

  <a class="portal-submit-btn" href="<?= app_url('auth/logout.php') ?>">
    <span>Sign Out</span>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
  </a>
</main>
</body>
</html>