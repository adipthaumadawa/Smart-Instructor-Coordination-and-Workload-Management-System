<?php

$pageTitle = "Instructor Coordinator Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $pageTitle; ?></title>
  
  <!-- Font & Icon Assets matching Instructor UI -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  
  <style>
    /* ----- reset & base ----- */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background: #f7f9fb;
      color: #191c1e;
      display: flex;
      min-height: 100vh;
    }

    /* ----- design tokens ----- */
    :root {
      --sidebar-width: 260px;
      --primary: #00236f;
      --primary-container: #1e3a8a;
      --on-primary: #ffffff;
      --on-primary-container: #90a8ff;
      --secondary: #0051d5;
      --secondary-container: #316bf3;
      --on-secondary: #ffffff;
      --secondary-fixed: #dbe1ff;
      --secondary-fixed-dim: #b4c5ff;
      --surface: #f7f9fb;
      --surface-container-lowest: #ffffff;
      --surface-container-low: #f2f4f6;
      --surface-container: #eceef0;
      --surface-container-high: #e6e8ea;
      --surface-container-highest: #e0e3e5;
      --surface-dim: #d8dadc;
      --on-surface: #191c1e;
      --on-surface-variant: #444651;
      --outline: #757682;
      --outline-variant: #c5c5d3;
      --error: #ba1a1a;
      --error-container: #ffdad6;
      --tertiary: #1b2b3f;
      --tertiary-fixed: #d3e4fe;
      --radius-xl: 0.75rem;
      --radius-lg: 0.5rem;
      --shadow-sm: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03);
      --shadow-md: 0 4px 12px rgba(0,0,0,0.05);
    }

    /* ----- sidebar ----- */
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
      border-right: 1px solid var(--outline-variant);
      overflow-y: auto;
    }
    .sidebar-brand {
      margin-bottom: 24px;
      padding-left: 8px;
    }
    .sidebar-brand h1 {
      font-size: 24px;
      font-weight: 700;
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
      gap: 16px;
      padding: 12px 16px;
      border-radius: var(--radius-lg);
      font-weight: 500;
      font-size: 14px;
      color: var(--on-primary);
      text-decoration: none;
      transition: background 0.2s, transform 0.1s;
      cursor: pointer;
    }
    .sidebar-nav a:hover {
      background: var(--primary-container);
    }
    .sidebar-nav a.active {
      background: var(--secondary-container);
      color: #fefcff;
      border-left: 4px solid var(--secondary-fixed-dim);
      font-weight: 600;
      border-radius: 0 var(--radius-lg) var(--radius-lg) 0;
    }
    .sidebar-nav .material-symbols-outlined {
      font-size: 22px;
    }
    .sidebar-divider {
      margin: 16px 0 8px 0;
      border-top: 1px solid rgba(255,255,255,0.1);
    }
    .sidebar-footer {
      margin-top: auto;
      padding-top: 16px;
    }
    .sidebar-footer button, .sidebar-footer a.btn-link {
      width: 100%;
      background: var(--secondary);
      color: white;
      border: none;
      padding: 12px;
      border-radius: var(--radius-xl);
      font-weight: 600;
      font-size: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: opacity 0.2s, transform 0.1s;
      cursor: pointer;
      text-decoration: none;
    }
    .sidebar-footer button:hover, .sidebar-footer a.btn-link:hover {
      opacity: 0.9;
    }

    /* ----- topbar ----- */
    .topbar {
      position: fixed;
      left: var(--sidebar-width);
      right: 0;
      top: 0;
      height: 64px;
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
      font-size: 24px;
      font-weight: 600;
      color: var(--primary);
      letter-spacing: -0.01em;
    }
    .search-wrapper {
      position: relative;
      max-width: 320px;
      width: 100%;
      margin-left: 16px;
    }
    .search-wrapper .material-symbols-outlined {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--outline);
      font-size: 20px;
    }
    .search-wrapper input {
      width: 100%;
      padding: 8px 12px 8px 40px;
      background: var(--surface-container-low);
      border: 1px solid var(--outline-variant);
      border-radius: var(--radius-lg);
      font-size: 14px;
      font-family: 'Inter', sans-serif;
      transition: all 0.2s;
    }
    .search-wrapper input:focus {
      outline: none;
      border-color: var(--secondary);
      box-shadow: 0 0 0 3px rgba(49, 107, 243, 0.15);
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
      color: var(--on-surface-variant);
      cursor: pointer;
      transition: color 0.2s, background 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .topbar-right .icon-btn:hover {
      color: var(--primary);
      background: var(--surface-container-high);
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
      font-weight: 600;
      font-size: 14px;
    }
    .profile-text .role {
      font-size: 12px;
      color: var(--outline);
    }

    /* ----- main content ----- */
    .main {
      margin-left: var(--sidebar-width);
      margin-top: 64px;
      padding: 24px;
      flex: 1;
      min-height: calc(100vh - 64px);
      max-width: 1440px;
      width: 100%;
    }

    /* ----- welcome header ----- */
    .welcome-row {
      display: flex;
      flex-wrap: wrap;
      align-items: flex-end;
      justify-content: space-between;
      margin-bottom: 24px;
    }
    .welcome-row h3 {
      font-size: 32px;
      font-weight: 700;
      letter-spacing: -0.02em;
      color: var(--on-surface);
      line-height: 1.2;
    }
    .welcome-row p {
      font-size: 16px;
      color: var(--on-surface-variant);
    }
    .action-btns {
      display: flex;
      gap: 8px;
      margin-top: 8px;
    }
    .action-btns a, .action-btns button {
      padding: 8px 16px;
      border-radius: var(--radius-lg);
      font-size: 12px;
      font-weight: 500;
      border: none;
      background: var(--surface-container-high);
      color: var(--on-surface);
      display: flex;
      align-items: center;
      gap: 6px;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.2s;
    }
    .action-btns .primary-btn {
      background: var(--primary);
      color: white;
      box-shadow: var(--shadow-sm);
    }
    .action-btns .primary-btn:hover {
      opacity: 0.9;
    }

    /* ----- stat cards grid ----- */
    .stat-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 24px;
      margin-bottom: 24px;
    }
    .stat-card {
      background: var(--surface-container-lowest);
      border: 1px solid var(--outline-variant);
      border-radius: var(--radius-xl);
      padding: 16px 20px;
      display: flex;
      align-items: center;
      gap: 16px;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .stat-card:hover {
      box-shadow: var(--shadow-md);
      transform: translateY(-2px);
    }
    .stat-icon {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .stat-icon.primary { background: #dce1ff; color: var(--primary); }
    .stat-icon.secondary { background: var(--secondary-fixed); color: var(--secondary); }
    .stat-icon.error { background: var(--error-container); color: var(--error); }
    .stat-icon.tertiary { background: var(--tertiary-fixed); color: var(--tertiary); }
    .stat-content .label { font-size: 12px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; color: var(--outline); }
    .stat-content .value { font-size: 24px; font-weight: 600; line-height: 1.2; }
    .stat-content .sub { font-size: 12px; color: var(--on-surface-variant); }

    /* ----- grid layouts for section rows ----- */
    .three-card-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
      margin-bottom: 24px;
    }

    /* ----- reusable card style ----- */
    .card {
      background: var(--surface-container-lowest);
      border: 1px solid var(--outline-variant);
      border-radius: var(--radius-xl);
      padding: 20px;
      box-shadow: var(--shadow-sm);
      display: flex;
      flex-direction: column;
      justify-content: space-between; /* Ensures equal alignment at the end of last cards */
      height: 100%;
    }
    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
      border-bottom: 1px solid var(--outline-variant);
      padding-bottom: 12px;
    }
    .card-header h5 {
      font-size: 18px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--on-surface);
    }
    .card-header .badge {
      background: var(--primary-fixed, #dce1ff);
      color: var(--primary);
      padding: 2px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }
    .card-header .badge.danger {
      background: var(--error-container);
      color: var(--error);
    }

    .card-body-content {
      flex: 1;
    }

    .card-footer-action {
      margin-top: 16px;
      padding-top: 12px;
      border-top: 1px solid var(--outline-variant);
    }

    .btn-full {
      width: 100%;
      padding: 10px;
      border-radius: var(--radius-lg);
      background: var(--surface-container-high);
      border: none;
      color: var(--primary);
      font-weight: 600;
      font-size: 13px;
      cursor: pointer;
      text-align: center;
      text-decoration: none;
      display: block;
      transition: background 0.2s;
    }
    .btn-full:hover {
      background: var(--secondary-fixed);
    }

    /* Inner UI Components */
    .request-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px;
      background: var(--surface-container-low);
      border-radius: var(--radius-lg);
      margin-bottom: 8px;
    }
    .request-item .title { font-weight: 600; font-size: 14px; }
    .request-item .meta { font-size: 12px; color: var(--outline); }
    
    /* Doughnut Chart Mock Component */
    .chart-wrap {
      position: relative;
      width: 140px;
      height: 140px;
      margin: 0 auto 16px;
    }
    .chart-wrap svg { transform: rotate(-90deg); }
    .chart-center {
      position: absolute;
      inset: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }
    .chart-center .big { font-size: 22px; font-weight: 600; }
    .chart-center .small { font-size: 10px; color: var(--outline); }

    .legend-item {
      display: flex;
      justify-content: space-between;
      padding: 4px 0;
      font-size: 13px;
    }
    .legend-dot {
      display: inline-block;
      width: 10px;
      height: 10px;
      border-radius: 50%;
      margin-right: 8px;
    }

    /* Responsive */
    @media (max-width: 1024px) {
      .three-card-grid {
        grid-template-columns: 1fr;
      }
    }
    @media (max-width: 768px) {
      :root { --sidebar-width: 0px; }
      .sidebar { transform: translateX(-100%); }
      .topbar { left: 0; }
      .main { margin-left: 0; }
      .welcome-row h3 { font-size: 24px; }
      .stat-grid { grid-template-columns: 1fr 1fr; }
      .topbar-left h2 { font-size: 18px; }
      .search-wrapper { display: none; }
    }
  </style>
</head>
<body>

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <h1>Coordinator Portal</h1>
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

  <!-- MAIN CONTENT -->
  <main class="main">

    <!-- Welcome Section -->
    <div class="welcome-row">
      <div>
        <h3>Instructor Coordinator Dashboard</h3>
        <p>Instructor availability, task requests, urgent replacements, workload monitoring, and reports.</p>
      </div>
      <div class="action-btns">
        <a href="/smart-instructor-system/instructor_coordinator/additional_tasks.php" class="primary-btn">
          <span class="material-symbols-outlined" style="font-size:18px;">add</span> New Task
        </a>
      </div>
    </div>

    <!-- Stat Cards Row -->
    <div class="stat-grid">
      <div class="stat-card">
        <div class="stat-icon primary"><span class="material-symbols-outlined">group</span></div>
        <div class="stat-content">
          <div class="label">Total Instructors</div>
          <div class="value">24</div>
          <div class="sub">Active Department Staff</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon secondary"><span class="material-symbols-outlined">task</span></div>
        <div class="stat-content">
          <div class="label">Active Tasks</div>
          <div class="value">12</div>
          <div class="sub">Pending Allocation</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon error"><span class="material-symbols-outlined">warning</span></div>
        <div class="stat-content">
          <div class="label">Replacements</div>
          <div class="value">3</div>
          <div class="sub">Requires Attention</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon tertiary"><span class="material-symbols-outlined">event_seat</span></div>
        <div class="stat-content">
          <div class="label">Hall Requests</div>
          <div class="value">8</div>
          <div class="sub">Scheduled This Week</div>
        </div>
      </div>
    </div>

    <!-- SECTION ROW 1: Leave Requests, Urgent Replacement Alerts, Lecture Hall Bookings -->
    <div class="three-card-grid">
      
      <!-- Recent Leave Requests -->
      <div class="card">
        <div>
          <div class="card-header">
            <h5><span class="material-symbols-outlined" style="color:var(--primary);">event_busy</span> Recent Leave Requests</h5>
            <span class="badge">2 Pending</span>
          </div>
          <div class="card-body-content">
            <div class="request-item">
              <div>
                <div class="title">Dr. John Silva</div>
                <div class="meta">12 Aug – 14 Aug • Medical Leave</div>
              </div>
              <span class="material-symbols-outlined" style="color:var(--error); font-size:20px;">pending</span>
            </div>
            <div class="request-item">
              <div>
                <div class="title">Prof. Sarah Connor</div>
                <div class="meta">18 Aug • Academic Leave</div>
              </div>
              <span class="material-symbols-outlined" style="color:var(--error); font-size:20px;">pending</span>
            </div>
          </div>
        </div>
        <div class="card-footer-action">
          <a href="#" class="btn-full">Review All Leave Requests</a>
        </div>
      </div>

      <!-- Urgent Replacement Alerts -->
      <div class="card">
        <div>
          <div class="card-header">
            <h5><span class="material-symbols-outlined" style="color:var(--error);">warning</span> Urgent Replacements</h5>
            <span class="badge danger">1 Alert</span>
          </div>
          <div class="card-body-content">
            <div class="request-item" style="border-left: 3px solid var(--error);">
              <div>
                <div class="title">IS2202 Database Practical</div>
                <div class="meta">Today, 10:00 AM • Lab 4 (Unassigned)</div>
              </div>
            </div>
            <p style="font-size:12px; color:var(--outline); margin-top:8px;">Instructor absent on medical leave. Re-allocation required immediately.</p>
          </div>
        </div>
        <div class="card-footer-action">
          <a href="#" class="btn-full" style="color:var(--error);">Assign Immediate Replacement</a>
        </div>
      </div>

      <!-- Lecture Hall Bookings -->
      <div class="card">
        <div>
          <div class="card-header">
            <h5><span class="material-symbols-outlined" style="color:var(--primary);">meeting_room</span> Lecture Hall Bookings</h5>
            <span class="badge">Today</span>
          </div>
          <div class="card-body-content">
            <div class="request-item">
              <div>
                <div class="title">Main Auditorium</div>
                <div class="meta">IS1205 Lecture • 08:00 - 10:00 AM</div>
              </div>
            </div>
            <div class="request-item">
              <div>
                <div class="title">Hall C</div>
                <div class="meta">EN1202 Session • 02:00 - 04:00 PM</div>
              </div>
            </div>
          </div>
        </div>
        <div class="card-footer-action">
          <a href="#" class="btn-full">Manage Hall Schedules</a>
        </div>
      </div>

    </div>

    <!-- SECTION ROW 2: Workload Overview, Instructor Availability, Upcoming Schedule -->
    <div class="three-card-grid">
      
      <!-- Workload Overview -->
      <div class="card">
        <div>
          <div class="card-header">
            <h5><span class="material-symbols-outlined" style="color:var(--primary);">pie_chart</span> Workload Overview</h5>
          </div>
          <div class="card-body-content">
            <div class="chart-wrap">
              <svg viewBox="0 0 36 36" width="100%" height="100%">
                <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#c5c5d3" stroke-dasharray="100,100" stroke-width="3"/>
                <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#00236f" stroke-dasharray="45,100" stroke-width="3"/>
                <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#0051d5" stroke-dasharray="30,100" stroke-dashoffset="-45" stroke-width="3"/>
              </svg>
              <div class="chart-center">
                <span class="big">75%</span>
                <span class="small">ALLOCATED</span>
              </div>
            </div>
            <div class="legend-item"><span><span class="legend-dot" style="background:#00236f;"></span>Teaching Hours</span><span>45%</span></div>
            <div class="legend-item"><span><span class="legend-dot" style="background:#0051d5;"></span>Practical Sessions</span><span>30%</span></div>
            <div class="legend-item"><span><span class="legend-dot" style="background:#c5c5d3;"></span>Free Capacity</span><span>25%</span></div>
          </div>
        </div>
        <div class="card-footer-action">
          <a href="#" class="btn-full">Full Workload Report</a>
        </div>
      </div>

      <!-- Instructor Availability -->
      <div class="card">
        <div>
          <div class="card-header">
            <h5><span class="material-symbols-outlined" style="color:var(--primary);">check_circle</span> Instructor Availability</h5>
            <span class="badge">18 Available</span>
          </div>
          <div class="card-body-content">
            <div class="request-item">
              <div>
                <div class="title">Available for Duty</div>
                <div class="meta">18 Senior Lecturers & TAs</div>
              </div>
              <span class="material-symbols-outlined" style="color:#2e7d32;">check</span>
            </div>
            <div class="request-item">
              <div>
                <div class="title">On Approved Leave</div>
                <div class="meta">4 Faculty Members</div>
              </div>
              <span class="material-symbols-outlined" style="color:var(--outline);">person_off</span>
            </div>
            <div class="request-item">
              <div>
                <div class="title">Fully Booked Slot</div>
                <div class="meta">2 Instructors</div>
              </div>
              <span class="material-symbols-outlined" style="color:var(--error);">block</span>
            </div>
          </div>
        </div>
        <div class="card-footer-action">
          <a href="#" class="btn-full">Check Availability Grid</a>
        </div>
      </div>

      <!-- Upcoming Schedule -->
      <div class="card">
        <div>
          <div class="card-header">
            <h5><span class="material-symbols-outlined" style="color:var(--primary);">calendar_month</span> Upcoming Schedule</h5>
          </div>
          <div class="card-body-content">
            <div class="request-item">
              <div>
                <div class="title">IS1205 Practical Exam</div>
                <div class="meta">Tomorrow • 09:00 AM</div>
              </div>
            </div>
            <div class="request-item">
              <div>
                <div class="title">Department Review Meeting</div>
                <div class="meta">12 Aug • 02:00 PM</div>
              </div>
            </div>
            <div class="request-item">
              <div>
                <div class="title">End Semester Presentations</div>
                <div class="meta">15 Aug • All Day</div>
              </div>
            </div>
          </div>
        </div>
        <div class="card-footer-action">
          <a href="#" class="btn-full">View Complete Timetable</a>
        </div>
      </div>

    </div>

  </main>

  <!-- Interactive script matching design tokens -->
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