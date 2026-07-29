<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Instructor Dashboard · Academia Pro</title>
  <!-- Font & Icon (only external assets, no framework) -->
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

    /* ----- design tokens (clean, no tailwind) ----- */
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

    /* ----- sidebar (fixed) ----- */
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
    .sidebar-footer button {
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
    }
    .sidebar-footer button:hover {
      opacity: 0.9;
    }
    .sidebar-footer button:active {
      transform: scale(0.97);
    }

    /* ----- top bar (fixed) ----- */
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
    .avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border: 2px solid var(--secondary-fixed-dim);
      object-fit: cover;
      background: var(--surface-container-high);
    }

    /* ----- main content (offset) ----- */
    .main {
      margin-left: var(--sidebar-width);
      margin-top: 64px;
      padding: 24px;
      flex: 1;
      min-height: calc(100vh - 64px);
      max-width: 1440px;
      width: 100%;
    }

    /* ----- welcome row ----- */
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
    .action-btns button {
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
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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

    /* ----- two column layout ----- */
    .two-col {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 24px;
    }
    @media (max-width: 1024px) {
      .two-col { grid-template-columns: 1fr; }
    }

    /* ----- card ----- */
    .card {
      background: var(--surface-container-lowest);
      border: 1px solid var(--outline-variant);
      border-radius: var(--radius-xl);
      padding: 20px;
      box-shadow: var(--shadow-sm);
      margin-bottom: 24px;
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
      font-size: 20px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .card-header .badge {
      background: var(--primary-fixed, #dce1ff);
      color: var(--primary);
      padding: 2px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th {
      text-align: left;
      padding: 12px 16px;
      background: var(--surface-container-low);
      font-size: 12px;
      font-weight: 600;
      color: var(--on-surface-variant);
      letter-spacing: 0.03em;
    }
    td {
      padding: 12px 16px;
      border-bottom: 1px solid var(--outline-variant);
      font-size: 14px;
    }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: var(--surface-container); }
    .course-code { font-weight: 600; color: var(--primary); }
    .course-name { font-size: 12px; color: var(--outline); }
    .chip {
      padding: 2px 10px;
      border-radius: 20px;
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      display: inline-block;
    }
    .chip.lecture { background: var(--secondary-fixed); color: #003ea8; }
    .chip.practical { background: var(--tertiary-fixed); color: #38485d; }
    .action-link { color: var(--primary); font-weight: 500; font-size: 12px; text-decoration: none; }
    .action-link:hover { text-decoration: underline; }

    /* request mini */
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
    .dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    .dot.error { background: var(--error); }

    /* doughnut placeholder */
    .chart-wrap {
      position: relative;
      width: 160px;
      height: 160px;
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
    .chart-center .big { font-size: 24px; font-weight: 600; }
    .chart-center .small { font-size: 10px; color: var(--outline); }

    .legend-item {
      display: flex;
      justify-content: space-between;
      padding: 6px 0;
      font-size: 14px;
    }
    .legend-dot {
      display: inline-block;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      margin-right: 8px;
    }

    /* notification */
    .notif-item {
      display: flex;
      gap: 12px;
      padding: 8px;
      border-radius: var(--radius-lg);
      cursor: pointer;
      transition: background 0.15s;
    }
    .notif-item:hover { background: var(--surface-container-low); }
    .notif-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--surface-container-high);
    }
    .notif-content .title { font-weight: 600; font-size: 14px; }
    .notif-content .desc { font-size: 12px; color: var(--outline); }

    /* FAB */
    .fab {
      position: fixed;
      bottom: 24px;
      right: 24px;
      z-index: 60;
    }
    .fab button {
      width: 56px;
      height: 56px;
      border-radius: 50%;
      background: var(--primary);
      color: white;
      border: none;
      box-shadow: 0 6px 20px rgba(0,0,0,0.15);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      cursor: pointer;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .fab button:hover { transform: scale(1.05); }
    .fab button:active { transform: scale(0.95); }

    /* responsive */
    @media (max-width: 768px) {
      :root { --sidebar-width: 0px; }
      .sidebar { transform: translateX(-100%); }
      .topbar { left: 0; }
      .main { margin-left: 0; }
      .welcome-row h3 { font-size: 24px; }
      .stat-grid { grid-template-columns: 1fr 1fr; }
      .topbar-left h2 { font-size: 18px; }
      .search-wrapper { display: none; }
      .profile-text { display: none; }
    }
  </style>
</head>
<body>

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <h1>Academia Pro</h1>
      <p>Instructor Portal</p>
    </div>
    <nav class="sidebar-nav">
      <a href="#" class="active"><span class="material-symbols-outlined">dashboard</span> Dashboard</a>
      <a href="#"><span class="material-symbols-outlined">calendar_today</span> Timetable</a>
      <a href="#"><span class="material-symbols-outlined">assignment</span> My Tasks</a>
      <a href="#"><span class="material-symbols-outlined">swap_horiz</span> Replacement Requests</a>
      <a href="#"><span class="material-symbols-outlined">event_busy</span> Leave Notifications</a>
      <a href="#"><span class="material-symbols-outlined">bar_chart</span> Workload Summary</a>
      <div class="sidebar-divider"></div>
      <a href="#"><span class="material-symbols-outlined">notifications</span> Notifications</a>
      <a href="#"><span class="material-symbols-outlined">person</span> Profile</a>
      <a href="#"><span class="material-symbols-outlined">settings</span> Settings</a>
    </nav>
    <div class="sidebar-footer">
      <button><span class="material-symbols-outlined">add</span> New Request</button>
    </div>
  </aside>

  <!-- TOPBAR -->
  <header class="topbar">
    <div class="topbar-left">
      <h2>Instructor Dashboard</h2>
      <div class="search-wrapper">
        <span class="material-symbols-outlined">search</span>
        <input type="text" placeholder="Search courses, students, or tasks...">
      </div>
    </div>
    <div class="topbar-right">
      <button class="icon-btn"><span class="material-symbols-outlined">notifications</span></button>
      <button class="icon-btn"><span class="material-symbols-outlined">help_outline</span></button>
      <div class="profile-wrap">
        <div class="profile-text">
          <div class="name">Dr. John Silva</div>
          <div class="role">Senior Lecturer</div>
        </div>
        <img class="avatar" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAbjyiEYBdZTefkHiy72JEc3vGBOan32B0RO3GfUmin-840vQpmHf6U_p_I-aYydyATdEf7B9pw5XRe348Fkknam0fvLaCbvFPeabxm8-6xLviNEM2Rhe0hLBwBoEgEvBfE4vv2w4eh2vq5R_jDxnoI9kzns_inu3WBlfD7Y5bQnX5mc8ETOX8XziYpLSpGd1HVthkOCifDbVCzvFRzcuwwS4pN2wjTHThj5ukX2mF-U3AHpe4xMkmioQ" alt="Dr. John Silva">
      </div>
    </div>
  </header>

  <!-- MAIN -->
  <main class="main">

    <!-- welcome -->
    <div class="welcome-row">
      <div>
        <h3>Welcome, Dr. John Silva</h3>
        <p>Monday, 10 August 2026</p>
      </div>
      <div class="action-btns">
        <button><span class="material-symbols-outlined" style="font-size:18px;">download</span> Export Report</button>
        <button class="primary-btn"><span class="material-symbols-outlined" style="font-size:18px;">calendar_add_on</span> Book Hall</button>
      </div>
    </div>

    <!-- stats -->
    <div class="stat-grid">
      <div class="stat-card">
        <div class="stat-icon primary"><span class="material-symbols-outlined">assignment</span></div>
        <div class="stat-content">
          <div class="label">Total Tasks</div>
          <div class="value">15</div>
          <div class="sub">Current Semester</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon secondary"><span class="material-symbols-outlined">schedule</span></div>
        <div class="stat-content">
          <div class="label">Today's Classes</div>
          <div class="value">3</div>
          <div class="sub">Scheduled Today</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon error"><span class="material-symbols-outlined">pending_actions</span></div>
        <div class="stat-content">
          <div class="label">Pending Requests</div>
          <div class="value">2</div>
          <div class="sub">Needs Action</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon tertiary"><span class="material-symbols-outlined">notifications_active</span></div>
        <div class="stat-content">
          <div class="label">Notifications</div>
          <div class="value">5</div>
          <div class="sub">Unread Messages</div>
        </div>
      </div>
    </div>

    <!-- two column -->
    <div class="two-col">
      <!-- left col -->
      <div>
        <!-- schedule -->
        <div class="card">
          <div class="card-header">
            <h5><span class="material-symbols-outlined" style="color:var(--primary);">view_list</span> Today's Schedule</h5>
            <span class="badge">3 Slots Remaining</span>
          </div>
          <div class="table-wrap">
            <table>
              <thead><tr><th>TIME</th><th>COURSE</th><th>TYPE</th><th>VENUE</th><th>ACTION</th></tr></thead>
              <tbody>
                <tr><td>08:00 – 10:00</td><td><div class="course-code">IS1205</div><div class="course-name">Information Systems</div></td><td><span class="chip lecture">Lecture</span></td><td>Hall A</td><td><a href="#" class="action-link">Manage</a></td></tr>
                <tr><td>10:00 – 12:00</td><td><div class="course-code">IS2202</div><div class="course-name">Database Management</div></td><td><span class="chip practical">Practical</span></td><td>Lab 4</td><td><a href="#" class="action-link">Manage</a></td></tr>
                <tr><td>14:00 – 16:00</td><td><div class="course-code">EN1202</div><div class="course-name">Professional English</div></td><td><span class="chip lecture">Lecture</span></td><td>Hall C</td><td><a href="#" class="action-link">Manage</a></td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- replacement + leave -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:24px;">
          <div class="card" style="margin-bottom:0;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
              <h6 style="font-size:20px; font-weight:600;">Replacement Requests</h6>
              <span class="material-symbols-outlined" style="color:var(--outline);">swap_horiz</span>
            </div>
            <div class="request-item"><div><div class="title">CS Practical</div><div class="meta">12 Aug • Pending</div></div><span class="dot error"></span></div>
            <div class="request-item"><div><div class="title">Lecture Replacement</div><div class="meta">05 Aug • Approved</div></div><span class="material-symbols-outlined" style="color:#2e7d32; font-size:18px;">check_circle</span></div>
            <a href="#" style="display:block; margin-top:12px; text-align:center; font-size:12px; font-weight:500; color:var(--primary);">View All Requests</a>
          </div>

          <div class="card" style="margin-bottom:0; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center;">
            <div style="width:64px; height:64px; border-radius:50%; background:var(--surface-container); display:flex; align-items:center; justify-content:center; margin-bottom:12px;">
              <span class="material-symbols-outlined" style="font-size:32px; color:var(--outline);">event_busy</span>
            </div>
            <h6 style="font-weight:600; font-size:14px;">Upcoming Leave</h6>
            <p style="font-size:12px; color:var(--outline); margin-top:4px;">No upcoming leave scheduled for this week.</p>
            <button style="margin-top:12px; padding:8px 20px; background:var(--surface-container-high); border:none; border-radius:var(--radius-lg); font-weight:500; font-size:12px; cursor:pointer;">Notify Leave</button>
          </div>
        </div>
      </div>

      <!-- right col -->
      <div>
        <!-- quick actions -->
        <div class="card">
          <h6 style="font-size:12px; font-weight:600; letter-spacing:0.05em; color:var(--outline); margin-bottom:16px;">QUICK ACTIONS</h6>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
            <button style="background:var(--primary-fixed, #dce1ff); color:var(--primary); border:none; padding:12px 8px; border-radius:var(--radius-xl); display:flex; flex-direction:column; align-items:center; gap:4px; font-size:11px; font-weight:500; cursor:pointer; transition:0.15s;"><span class="material-symbols-outlined">swap_vert</span> Request Replacement</button>
            <button style="background:var(--tertiary-fixed); color:var(--tertiary); border:none; padding:12px 8px; border-radius:var(--radius-xl); display:flex; flex-direction:column; align-items:center; gap:4px; font-size:11px; font-weight:500; cursor:pointer;"><span class="material-symbols-outlined">beach_access</span> Notify Leave</button>
            <button style="background:transparent; border:1px solid var(--outline-variant); padding:12px 8px; border-radius:var(--radius-xl); display:flex; flex-direction:column; align-items:center; gap:4px; font-size:11px; font-weight:500; cursor:pointer;"><span class="material-symbols-outlined">calendar_month</span> View Timetable</button>
            <button style="background:transparent; border:1px solid var(--outline-variant); padding:12px 8px; border-radius:var(--radius-xl); display:flex; flex-direction:column; align-items:center; gap:4px; font-size:11px; font-weight:500; cursor:pointer;"><span class="material-symbols-outlined">task</span> View Tasks</button>
          </div>
        </div>

        <!-- workload -->
        <div class="card">
          <h6 style="font-size:20px; font-weight:600; margin-bottom:16px;">Workload Summary</h6>
          <div class="chart-wrap">
            <svg viewBox="0 0 36 36" width="100%" height="100%">
              <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#c5c5d3" stroke-dasharray="100,100" stroke-width="3"/>
              <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#00236f" stroke-dasharray="40,100" stroke-width="3"/>
              <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#0051d5" stroke-dasharray="30,100" stroke-dashoffset="-40" stroke-width="3"/>
              <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#1b2b3f" stroke-dasharray="20,100" stroke-dashoffset="-70" stroke-width="3"/>
            </svg>
            <div class="chart-center"><span class="big">90%</span><span class="small">CAPACITY</span></div>
          </div>
          <div>
            <div class="legend-item"><span><span class="legend-dot" style="background:#00236f;"></span>Lectures</span><span>40%</span></div>
            <div class="legend-item"><span><span class="legend-dot" style="background:#0051d5;"></span>Practicals</span><span>30%</span></div>
            <div class="legend-item"><span><span class="legend-dot" style="background:#1b2b3f;"></span>Presentations</span><span>20%</span></div>
            <div class="legend-item"><span><span class="legend-dot" style="background:#c5c5d3;"></span>Others</span><span>10%</span></div>
          </div>
        </div>

        <!-- alerts -->
        <div class="card">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h6 style="font-size:20px; font-weight:600;">Recent Alerts</h6>
            <a href="#" style="font-size:12px; font-weight:500; color:var(--primary);">Mark Read</a>
          </div>
          <div class="notif-item"><div class="notif-icon" style="background:var(--secondary-fixed); color:var(--secondary);"><span class="material-symbols-outlined">verified</span></div><div><div class="title">Replacement Approved</div><div class="desc">Dean's office approved your Monday request.</div></div></div>
          <div class="notif-item"><div class="notif-icon" style="background:var(--primary-fixed, #dce1ff); color:var(--primary);"><span class="material-symbols-outlined">calendar_today</span></div><div><div class="title">New Timetable Published</div><div class="desc">Semester 2 schedule is now finalized.</div></div></div>
          <div class="notif-item"><div class="notif-icon" style="background:var(--tertiary-fixed); color:var(--tertiary);"><span class="material-symbols-outlined">groups</span></div><div><div class="title">Presentation Schedule</div><div class="desc">Group A-F updated for IS2202.</div></div></div>
        </div>
      </div>
    </div>
  </main>

  <!-- FAB -->
  <div class="fab">
    <button><span class="material-symbols-outlined">add</span></button>
  </div>

  <!-- tiny interactive script (no framework) -->
  <script>
    (function() {
      // hover effect on stat cards (already in CSS, but we add extra touch)
      const cards = document.querySelectorAll('.stat-card');
      cards.forEach(c => {
        c.addEventListener('mouseenter', () => {});
        c.addEventListener('mouseleave', () => {});
      });
      // search focus scaling
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