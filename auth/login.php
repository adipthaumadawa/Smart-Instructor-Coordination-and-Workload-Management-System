<?php
/**
 * Login page
 * Framework-free: no Bootstrap / Font Awesome / Material / Google Fonts CDN
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';

if (isLoggedIn()) {
    header('Location: ' . getDashboardPath($_SESSION['role_id']));
    exit;
}

$error = '';
$success = isset($_GET['logged_out']) ? 'You have been logged out successfully.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } elseif (loginUser($email, $password)) {
        header('Location: ' . getDashboardPath($_SESSION['role_id']));
        exit;
    } else {
        $error = 'Invalid email or password, or account is inactive.';
    }
}

$demoAccounts = [
    'admin@example.com'            => 'Admin',
    'coordinator@example.com'      => 'Coordinator',
    'instructor@example.com'       => 'Instructor',
    'chief@example.com'            => 'Chief',
    'nonacademic@example.com'      => 'Non-Academic',
    'projectcoordinator@example.com' => 'Project',
    'director@example.com'         => 'Director',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In | UCSC Smart Instructor System</title>
  <meta name="description" content="Sign in to the UCSC Smart Instructor Coordination System">
  <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
</head>
<body class="login-body">

<div class="portal-root">
  <!-- Left Authentication Panel -->
  <aside class="portal-auth-panel">
    <div class="portal-auth-container">
      
      <div class="portal-brand-header">
        <img src="<?= app_url('assets/images/ucsc-logo.png') ?>" alt="UCSC Logo" class="portal-logo">
        <div class="portal-title">Smart Instructor</div>
        <div class="portal-subtitle">University of Colombo School of Computing</div>
      </div>

      <?php if ($error): ?>
        <div class="portal-alert portal-alert-error" role="alert">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="portal-alert portal-alert-success" role="alert">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
          <span><?= htmlspecialchars($success) ?></span>
        </div>
      <?php endif; ?>

      <form method="post" autocomplete="off" class="portal-form" id="loginForm">
        <div class="portal-field">
          <label class="portal-label" for="email">Institutional Email / Username</label>
          <div class="portal-input-wrap">
            <span class="portal-input-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </span>
            <input
              type="email"
              id="email"
              name="email"
              class="portal-control"
              placeholder="e.g. coordinator@example.com"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
              required
              autocomplete="email">
          </div>
        </div>

        <div class="portal-field">
          <div class="portal-label-row">
            <label class="portal-label" for="password">Password</label>
            <a href="<?= app_url('auth/forgot_password.php') ?>" class="portal-forgot">Forgot password?</a>
          </div>
          <div class="portal-input-wrap">
            <span class="portal-input-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            </span>
            <input
              type="password"
              id="password"
              name="password"
              class="portal-control portal-control-pr"
              placeholder="Enter your account password"
              required
              autocomplete="current-password">
            <button type="button" class="portal-pw-toggle" id="togglePwBtn" aria-label="Show password">Show</button>
          </div>
        </div>

        <div class="portal-checkbox-row">
          <input type="checkbox" id="remember" name="remember">
          <label for="remember">Remember me on this browser</label>
        </div>

        <button type="submit" class="portal-submit-btn" id="submitBtn">
          <span>Sign In to Portal</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
        </button>
      </form>

      <div class="portal-footer-sec">
        <!-- Demo Accounts Quick Bar -->
        <div class="portal-demo-card">
          <div class="portal-demo-title">Quick Demo Login (<small>Pass: <code>password123</code></small>)</div>
          <div class="portal-demo-grid">
            <?php foreach ($demoAccounts as $demoEmail => $demoRole): ?>
              <button type="button" class="portal-demo-chip" data-email="<?= htmlspecialchars($demoEmail) ?>">
                <?= htmlspecialchars($demoRole) ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="portal-security-note">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
          <span>Restricted to authorized UCSC academic staff. All sessions are monitored.</span>
        </div>
      </div>

    </div>
  </aside>

  <!-- Right Immersive Institutional Banner -->
  <main class="portal-hero-banner" aria-hidden="true">
    <div class="portal-banner-bg" style="background-image: url('<?= app_url('assets/images/ucsc-logo.png') ?>');"></div>
    <div class="portal-hero-content">
      <div class="portal-hero-crest">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>
      </div>
      <h1 class="portal-hero-title">Academic Coordination &amp; Workload Portal</h1>
      <p class="portal-hero-text">
        The official enterprise platform powering intelligent resource allocation, automated instructor replacement, and lecture hall management for the University of Colombo School of Computing.
      </p>
    </div>
  </main>
</div>

<script>
(function () {
  // Clear ?logged_out from URL to prevent message persistence on refresh
  if (window.history.replaceState) {
    var cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
    window.history.replaceState({path: cleanUrl}, '', cleanUrl);
  }

  var pw = document.getElementById('password');
  var btn = document.getElementById('togglePwBtn');
  var emailInput = document.getElementById('email');

  if (btn && pw) {
    btn.addEventListener('click', function () {
      if (pw.type === 'password') {
        pw.type = 'text';
        btn.textContent = 'Hide';
        btn.setAttribute('aria-label', 'Hide password');
      } else {
        pw.type = 'password';
        btn.textContent = 'Show';
        btn.setAttribute('aria-label', 'Show password');
      }
    });
  }

  var form = document.getElementById('loginForm');
  var submitBtn = document.getElementById('submitBtn');
  if (form && submitBtn) {
    form.addEventListener('submit', function () {
      submitBtn.disabled = true;
      submitBtn.querySelector('span').textContent = 'Authenticating...';
    });
  }

  // Quick fill demo accounts on pill click
  document.querySelectorAll('.portal-demo-chip').forEach(function (pill) {
    pill.addEventListener('click', function () {
      if (emailInput) {
        emailInput.value = this.getAttribute('data-email');
      }
      if (pw) {
        pw.value = 'password123';
      }
    });
  });
})();
</script>
</body>
</html>