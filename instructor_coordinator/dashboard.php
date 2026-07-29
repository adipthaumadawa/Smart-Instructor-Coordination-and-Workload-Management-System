<?php
// Include dynamic DB helpers & UI renderer components from dashboard_ui.php
require_once __DIR__ . '/../includes/dashboard_ui.php';

$pageTitle = "Instructor Coordinator Dashboard";
$roleKey   = $_SESSION['role'] ?? 'coordinator';

// Retrieve dynamic KPI cards based on the user's role
$cards = sic_dashboard_cards($roleKey);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  
  <!-- Font & Icon Assets -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  
  <style>
    /* ----- Reset & Base ----- */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background: var(--d-page, #f1f5f9);
      color: var(--d-text, #0f172a);
      display: flex;
      min-height: 100vh;
    }

    /* ----- Layout Design Tokens ----- */
    :root {
      --sidebar-width: 260px;
      --topbar-height: 64px;
      --primary: #071a33;
      --primary-container: #0d2a50;
      --on-primary: #ffffff;
      --on-primary-container: #90a8ff;
      --secondary: #00939e;
      --secondary-container: #00b3c0;
      --outline-variant: #e2e8f0;
      --surface: #ffffff;
      --surface-container-low: #f8fafc;
      --radius-xl: 14px;
      --radius-lg: 8px;
    }

    /* ----- Sidebar ----- */
    .sidebar {
      position: fixed;
      left: 0;
      top: 0;
      width: var(--sidebar-width);
      height: 100vh;
      background: var(--primary);
      color: var(--on-primary);
      padding: 24px 16px;
      display: flex;
      flex-direction: column;
      z-index: 50;
      border-right: 1px solid var(--primary-container);
      overflow-y: auto;
    }
    .sidebar-brand {
      margin-bottom: 24px;
      padding-left: 8px;
    }
    .sidebar-brand h1 {
      font-size: 20px;
      font-weight: 800;
      line-height: 1.2;
      letter-spacing: -0.02em;
      color: white;
      margin-bottom: 4px;
    }
    .sidebar-brand p {
      font-size: 12px;
      opacity: 0.8;
      color: var(--on-primary-container);
    }
    .sidebar-nav {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 4px;
      margin-top: 8px;
    }
    .sidebar-nav a {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 10px 14px;
      border-radius: var(--radius-lg);
      font-weight: 500;
      font-size: 13.5px;
      color: var(--on-primary);
      text-decoration: none;
      transition: background 0.2s;
    }
    .sidebar-nav a:hover {
      background: var(--primary-container);
    }
    .sidebar-nav a.active {
      background: var(--secondary);
      color: #ffffff;
      font-weight: 700;
    }
    .sidebar-nav .material-symbols-outlined {
      font-size: 20px;
    }
    .sidebar-divider {
      margin: 16px 0 8px 0;
      border-top: 1px solid rgba(255,255,255,0.1);
    }
    .sidebar-footer {
      margin-top: auto;
      padding-top: 16px;
    }
    .sidebar-footer a.btn-link {
      width: 100%;
      background: var(--secondary);
      color: white;
      border: none;
      padding: 10px 14px;
      border-radius: var(--radius-lg);
      font-weight: 600;
      font-size: 13.5px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: opacity 0.2s;
      text-decoration: none;
    }
    .sidebar-footer a.btn-link:hover {
      opacity: 0.9;
    }

    /* ----- Topbar ----- */
    .topbar {
      position: fixed;
      left: var(--sidebar-width);
      right: 0;
      top: 0;
      height: var(--topbar-height);
      background: var(--surface);
      border-bottom: 1px solid var(--outline-variant);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 24px;
      z-index: 40;
    }
    .topbar-left {
      display: flex;
      align-items: center;
      gap: 16px;
      flex: 1;
    }
    .topbar-left h2 {
      font-size: 18px;
      font-weight: 700;
      color: var(--primary);
      letter-spacing: -0.01em;
    }
    .search-wrapper {
      position: relative;
      max-width: 320px;
      width: 100%;
      margin-left: 16px;
      transition: transform 0.2s ease;
    }
    .search-wrapper .material-symbols-outlined {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: #64748b;
      font-size: 18px;
    }
    .search-wrapper input {
      width: 100%;
      padding: 8px 12px 8px 38px;
      background: var(--surface-container-low);
      border: 1px solid var(--outline-variant);
      border-radius: var(--radius-lg);
      font-size: 13px;
      font-family: 'Inter', sans-serif;
      transition: all 0.2s;
    }
    .search-wrapper input:focus {
      outline: none;
      border-color: var(--secondary);
      box-shadow: 0 0 0 3px rgba(0, 147, 158, 0.15);
    }
    .topbar-right {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .topbar-right .icon-btn {
      background: transparent;
      border: none;
      padding: 8px;
      border-radius: 50%;
      color: #64748b;
      cursor: pointer;
      transition: color 0.2s, background 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .topbar-right .icon-btn:hover {
      color: var(--primary);
      background: var(--surface-container-low);
    }
    .profile-wrap {
      display: flex;
      align-items: center;
      gap: 12px;
      padding-left: 16px;
      border-left: 1px solid var(--outline-variant);
    }
    .profile-text {
      text-align: right;
      line-height: 1.3;
    }
    .profile-text .name {
      font-weight: 700;
      font-size: 13.5px;
    }
    .profile-text .role {
      font-size: 11.5px;
      color: #64748b;
    }

    /* ----- Main Content Shell ----- */
    .main {
      margin-left: var(--sidebar-width);
      margin-top: var(--topbar-height);
      flex: 1;
      min-height: calc(100vh - var(--topbar-height));
      width: calc(100% - var(--sidebar-width));
    }

    /* Responsive Breakdown */
    @media (max-width: 900px) {
      :root { --sidebar-width: 0px; }
      .sidebar { transform: translateX(-100%); }
      .topbar { left: 0; }
      .main { margin-left: 0; width: 100%; }
      .search-wrapper { display: none; }
    }
  </style>
</head>
<body>

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <h1>Coordinator Portal</h1>
      <p>Academic Operations</p>
    </div>
    <nav class="sidebar-nav">
      <a href="#" class="active"><span class="material-symbols-outlined">dashboard</span> Dashboard</a>
      <a href="#"><span class="material-symbols-outlined">assignment</span> Additional Tasks</a>
      <a href="#"><span class="material-symbols-outlined">swap_horiz</span> Urgent Replacements</a>
      <a href="#"><span class="material-symbols-outlined">event_busy</span> Leave Requests</a>
      <a href="#"><span class="material-symbols-outlined">meeting_room</span> Hall Bookings</a>
      <a href="#"><span class="material-symbols-outlined">bar_chart</span> Workload Overview</a>
      <div class="sidebar-divider"></div>
      <a href="#"><span class="material-symbols-outlined">notifications</span> Notifications</a>
      <a href="#"><span class="material-symbols-outlined">person</span> Profile</a>
      <a href="#"><span class="material-symbols-outlined">settings</span> Settings</a>
    </nav>
    <div class="sidebar-footer">
      <a href="/smart-instructor-system/coordinator/additional_tasks.php" class="btn-link">
        <span class="material-symbols-outlined">add</span> New Task
      </a>
    </div>
  </aside>

  <!-- TOPBAR -->
  <header class="topbar">
    <div class="topbar-left">
      <h2>Instructor Coordinator Dashboard</h2>
      <div class="search-wrapper">
        <span class="material-symbols-outlined">search</span>
        <input type="text" placeholder="Search instructors, tasks, rooms...">
      </div>
    </div>
    <div class="topbar-right">
      <button class="icon-btn"><span class="material-symbols-outlined">notifications</span></button>
      <button class="icon-btn"><span class="material-symbols-outlined">help_outline</span></button>
      <div class="profile-wrap">
        <div class="profile-text">
          <div class="name">Coordinator Panel</div>
          <div class="role">Academic Operations</div>
        </div>
      </div>
    </div>
  </header>

  <!-- MAIN DYNAMIC CONTENT -->
  <main class="main">
    <?php
      // Render the dynamic dashboard contents using dashboard_ui.php framework
      sic_render_dashboard(
        "Instructor Coordinator Dashboard",
        "Instructor availability, task requests, urgent replacements, workload monitoring, and reports.",
        $cards,
        "/smart-instructor-system/instructor_coordinator/additional_tasks.php",
        "New Task"
      );
    ?>
  </main>

  <!-- Interactive Search Script -->
  <script>
    (function() {
      const input = document.querySelector('.search-wrapper input');
      const wrap = document.querySelector('.search-wrapper');
      if (input && wrap) {
        input.addEventListener('focus', () => wrap.style.transform = 'scale(1.02)');
        input.addEventListener('blur', () => wrap.style.transform = 'scale(1)');
      }
    })();
  </script>

</body>
</html>