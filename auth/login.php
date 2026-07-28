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
    'admin@example.com',
    'coordinator@example.com',
    'instructor@example.com',
    'chief@example.com',
    'nonacademic@example.com',
    'projectcoordinator@example.com',
    'director@example.com',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | UCSC Smart Instructor System</title>
  <meta name="description" content="Sign in to the UCSC Smart Instructor Coordination System">
  <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
  <style>
    :root {
      --primary: #000a1e;
      --primary-mid: #002147;
      --secondary: #006a6a;
      --sec-container: #90efef;
      --surface: #f7f9fb;
      --surface-card: #ffffff;
      --surface-low: #f2f4f6;
      --on-surface: #191c1e;
      --on-surf-var: #44474e;
      --outline: #c4c6cf;
      --outline-str: #74777f;
      --error: #ba1a1a;
      --err-container: #ffdad6;
      --err-on: #93000a;
      --green: #1a7f1a;
      --green-soft: #e6f4e6;
      --ff: "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
      --r-md: 12px;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body {
      height: 100%;
      font-family: var(--ff);
      font-size: 14px;
      background: var(--surface);
      color: var(--on-surface);
      -webkit-font-smoothing: antialiased;
    }
    .login-root { display: flex; min-height: 100vh; }
    .login-left {
      width: 100%;
      max-width: 480px;
      flex: 0 0 480px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 56px;
      background: var(--surface-card);
      box-shadow: 2px 0 12px rgba(0,0,0,.06);
      overflow-y: auto;
      z-index: 2;
    }
    .login-right {
      flex: 1;
      background: linear-gradient(160deg, var(--primary) 0%, var(--primary-mid) 100%);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      padding: 64px 48px;
      position: relative;
      overflow: hidden;
    }
    .login-right::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: url('<?= app_url('assets/images/ucsc-logo.png') ?>');
      background-size: 340px auto;
      background-position: center 38%;
      background-repeat: no-repeat;
      opacity: .04;
      pointer-events: none;
    }
    .login-right-inner { position: relative; z-index: 1; max-width: 460px; }
    .login-logo-wrap {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      margin-bottom: 36px;
    }
    .login-logo { height: 80px; width: auto; margin-bottom: 20px; object-fit: contain; }
    .login-app-name {
      font-size: 1.75rem;
      font-weight: 700;
      letter-spacing: -0.03em;
      color: var(--primary);
      line-height: 1.1;
      margin-bottom: 4px;
    }
    .login-system-name {
      font-size: .8rem;
      font-weight: 600;
      letter-spacing: .06em;
      text-transform: uppercase;
      color: var(--on-surf-var);
    }
    .login-form { display: flex; flex-direction: column; gap: 20px; }
    .field-group { display: flex; flex-direction: column; gap: 6px; }
    .field-label {
      font-size: .78rem;
      font-weight: 700;
      letter-spacing: .05em;
      text-transform: uppercase;
      color: var(--on-surf-var);
    }
    .field-label-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .field-label-row a {
      font-size: .78rem;
      font-weight: 600;
      color: var(--secondary);
      text-decoration: none;
    }
    .field-label-row a:hover { opacity: .75; }
    .input-wrap { position: relative; }
    .input-icon {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 14px;
      color: var(--outline-str);
      line-height: 1;
      pointer-events: none;
    }
    .login-input {
      width: 100%;
      padding: 11px 14px 11px 40px;
      border: 1px solid var(--outline);
      border-radius: var(--r-md);
      background: var(--surface);
      font-family: var(--ff);
      font-size: .9rem;
      color: var(--on-surface);
      transition: border-color .15s ease, box-shadow .15s ease;
      outline: none;
    }
    .login-input::placeholder { color: var(--outline-str); }
    .login-input:focus {
      border-color: var(--secondary);
      box-shadow: 0 0 0 3px rgba(0,106,106,.12);
      background: #fff;
    }
    .login-input.pr { padding-right: 44px; }
    .toggle-pw {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      padding: 4px 6px;
      color: var(--outline-str);
      font-size: 13px;
      font-family: var(--ff);
    }
    .toggle-pw:hover { color: var(--on-surface); }
    .remember-row { display: flex; align-items: center; gap: 8px; }
    .remember-row input[type="checkbox"] {
      width: 16px;
      height: 16px;
      accent-color: var(--secondary);
      cursor: pointer;
    }
    .remember-row label { font-size: .875rem; color: var(--on-surf-var); cursor: pointer; }
    .btn-login {
      width: 100%;
      padding: 13px 20px;
      background: var(--primary);
      color: #fff;
      border: none;
      border-radius: var(--r-md);
      font-family: var(--ff);
      font-size: .875rem;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: background .15s ease, transform .1s ease;
    }
    .btn-login:hover { background: #001a3d; transform: translateY(-1px); }
    .btn-login:disabled { opacity: .75; cursor: wait; transform: none; }
    .login-alert {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      padding: 12px 14px;
      border-radius: var(--r-md);
      font-size: .875rem;
      font-weight: 500;
      line-height: 1.4;
      margin-bottom: 20px;
    }
    .alert-error { background: var(--err-container); color: var(--err-on); border: 1px solid #f5b7b7; }
    .alert-success { background: var(--green-soft); color: var(--green); border: 1px solid #a7d7a7; }
    .login-footer {
      margin-top: 28px;
      padding-top: 20px;
      border-top: 1px solid rgba(196,198,207,.4);
    }
    .notice-box {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      background: var(--surface-low);
      border-radius: var(--r-md);
      padding: 14px;
    }
    .notice-text { font-size: .8rem; color: var(--on-surf-var); line-height: 1.55; }
    .notice-contact {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      margin-top: 6px;
      font-size: .78rem;
      font-weight: 600;
      color: var(--secondary);
      text-decoration: none;
    }
    .notice-contact:hover { opacity: .75; }
    .demo-box {
      margin-top: 12px;
      background: var(--surface-low);
      border: 1px solid var(--outline);
      border-radius: var(--r-md);
      padding: 12px 14px;
      font-size: .78rem;
      color: var(--on-surf-var);
    }
    .demo-box strong { color: var(--secondary); font-weight: 700; }
    .right-icon {
      font-size: 56px;
      color: var(--sec-container);
      line-height: 1;
      margin-bottom: 24px;
      opacity: .9;
    }
    .right-title {
      font-size: 2.25rem;
      font-weight: 700;
      letter-spacing: -0.03em;
      line-height: 1.15;
      color: #fff;
      margin-bottom: 16px;
    }
    .right-desc {
      font-size: 1rem;
      color: rgba(255,255,255,.75);
      line-height: 1.65;
    }
    .right-dots {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-top: 40px;
    }
    .right-dots span:first-child {
      width: 56px; height: 4px; border-radius: 999px; background: var(--sec-container);
    }
    .right-dots span {
      width: 16px; height: 4px; border-radius: 999px; background: rgba(255,255,255,.25);
    }
    @media (max-width: 768px) {
      .login-right { display: none; }
      .login-left { max-width: 100%; flex: 1; padding: 40px 28px; }
      .login-logo { height: 60px; }
      .login-app-name { font-size: 1.4rem; }
    }
  </style>
</head>
<body>
<div class="login-root">

  <aside class="login-left">
    <div>
      <div class="login-logo-wrap">
        <img src="<?= app_url('assets/images/ucsc-logo.png') ?>" alt="UCSC Logo" class="login-logo">
        <div class="login-app-name">Smart Instructor</div>
        <div class="login-system-name">University of Colombo School of Computing</div>
      </div>

      <?php if ($error): ?>
        <div class="login-alert alert-error" role="alert">
          <span aria-hidden="true">⚠</span>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="login-alert alert-success" role="alert">
          <span aria-hidden="true">✓</span>
          <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>

      <form method="post" autocomplete="off" class="login-form" id="loginForm">
        <div class="field-group">
          <label class="field-label" for="email">Institutional Email / Username</label>
          <div class="input-wrap">
            <span class="input-icon" aria-hidden="true">👤</span>
            <input
              type="email"
              id="email"
              name="email"
              class="login-input"
              placeholder="e.g. jdoe@ucsc.cmb.ac.lk"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
              required
              autocomplete="email">
          </div>
        </div>

        <div class="field-group">
          <div class="field-label-row">
            <label class="field-label" for="password">Password</label>
            <a href="<?= app_url('auth/forgot_password.php') ?>">Forgot password?</a>
          </div>
          <div class="input-wrap">
            <span class="input-icon" aria-hidden="true">🔒</span>
            <input
              type="password"
              id="password"
              name="password"
              class="login-input pr"
              placeholder="Enter your password"
              required
              autocomplete="current-password">
            <button type="button" class="toggle-pw" id="togglePwBtn" aria-label="Show password">Show</button>
          </div>
        </div>

        <div class="remember-row">
          <input type="checkbox" id="remember" name="remember">
          <label for="remember">Remember me for 30 days</label>
        </div>

        <button type="submit" class="btn-login" id="submitBtn">
          Sign In →
        </button>
      </form>

      <div class="login-footer">
        <div class="notice-box">
          <span aria-hidden="true">ℹ</span>
          <div>
            <div class="notice-text">
              This system is for authorized UCSC personnel only.
              Unauthorized access is strictly prohibited.
            </div>
            <a href="mailto:admin@ucsc.cmb.ac.lk" class="notice-contact">✉ Contact Administrator</a>
          </div>
        </div>
        <div class="demo-box">
          <strong>Demo password:</strong> password123<br>
          <span style="opacity:.75;"><?= htmlspecialchars(implode(' • ', $demoAccounts)) ?></span>
        </div>
      </div>
    </div>
  </aside>

  <main class="login-right" aria-hidden="true">
    <div class="login-right-inner">
      <div class="right-icon">🏛</div>
      <h1 class="right-title">Streamlining Academic Coordination</h1>
      <p class="right-desc">
        The UCSC Smart Instructor system provides a centralized platform
        for managing workload, scheduling, and academic resources with
        uncompromising efficiency.
      </p>
      <div class="right-dots">
        <span></span><span></span><span></span>
      </div>
    </div>
  </main>

</div>

<script>
(function () {
  var pw = document.getElementById('password');
  var btn = document.getElementById('togglePwBtn');
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
      submitBtn.textContent = 'Signing in…';
    });
  }
})();
</script>
</body>
</html>