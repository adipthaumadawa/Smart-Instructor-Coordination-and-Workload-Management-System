<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Replacement Requests · Academia Pro</title>
  <!-- fonts & icons (only external assets) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  <style>
    /* ---------- reset & base ---------- */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background: #f7f9fb;
      color: #191c1e;
      display: flex;
      min-height: 100vh;
    }
    /* ---------- design tokens ---------- */
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
      --tertiary-container: #314156;
      --tertiary-fixed: #d3e4fe;
      --radius-xl: 0.75rem;
      --radius-lg: 0.5rem;
      --shadow-sm: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.02);
      --shadow-md: 0 4px 12px rgba(0,0,0,0.05);
    }
    /* ---------- sidebar ---------- */
    .sidebar {
      position: fixed; left: 0; top: 0;
      width: var(--sidebar-width); height: 100vh;
      background: var(--primary);
      color: var(--on-primary);
      padding: 24px 16px;
      display: flex; flex-direction: column;
      z-index: 50;
      border-right: 1px solid var(--outline-variant);
      overflow-y: auto;
    }
    .sidebar-brand { margin-bottom: 24px; padding-left: 8px; }
    .sidebar-brand h1 { font-size: 24px; font-weight: 700; letter-spacing: -0.02em; color: white; }
    .sidebar-brand p { font-size: 14px; font-weight: 500; opacity: 0.7; color: var(--on-primary-container); }
    .sidebar-nav { flex: 1; display: flex; flex-direction: column; gap: 4px; margin-top: 8px; }
    .sidebar-nav a {
      display: flex; align-items: center; gap: 16px;
      padding: 12px 16px;
      border-radius: var(--radius-lg);
      font-weight: 500; font-size: 14px;
      color: rgba(255,255,255,0.75);
      text-decoration: none;
      transition: background 0.2s, color 0.2s;
      cursor: pointer;
    }
    .sidebar-nav a:hover { background: var(--primary-container); color: white; }
    .sidebar-nav a.active {
      background: rgba(30,58,138,0.25);
      color: white;
      border-left: 4px solid var(--secondary-container);
      border-radius: 0 var(--radius-lg) var(--radius-lg) 0;
      font-weight: 600;
    }
    .sidebar-nav .material-symbols-outlined { font-size: 22px; }
    .sidebar-footer { margin-top: auto; padding-top: 16px; }

    /* ---------- topbar ---------- */
    .topbar {
      position: fixed; left: var(--sidebar-width); right: 0; top: 0;
      height: 64px;
      background: var(--surface-container-lowest);
      border-bottom: 1px solid var(--surface-container-highest);
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 24px;
      z-index: 40;
      box-shadow: var(--shadow-sm);
    }
    .topbar-left { display: flex; align-items: center; gap: 16px; flex: 1; }
    .search-wrapper {
      position: relative;
      max-width: 320px; width: 100%;
    }
    .search-wrapper .material-symbols-outlined {
      position: absolute; left: 12px; top: 50%;
      transform: translateY(-50%);
      color: var(--outline);
      font-size: 20px;
    }
    .search-wrapper input {
      width: 100%; padding: 8px 12px 8px 40px;
      background: var(--surface-container-low);
      border: none;
      border-radius: 999px;
      font-size: 14px;
      font-family: 'Inter', sans-serif;
    }
    .search-wrapper input:focus { outline: none; box-shadow: 0 0 0 2px rgba(49,107,243,0.15); }
    .topbar-right { display: flex; align-items: center; gap: 12px; }
    .topbar-right .icon-btn {
      background: transparent; border: none;
      padding: 8px; border-radius: 50%;
      color: var(--on-surface-variant);
      cursor: pointer;
      transition: background 0.2s;
      position: relative;
    }
    .topbar-right .icon-btn:hover { background: var(--surface-container-low); }
    .badge-dot {
      position: absolute; top: 6px; right: 6px;
      width: 8px; height: 8px;
      background: var(--error); border-radius: 50%;
    }
    .divider { width: 1px; height: 32px; background: var(--outline-variant); margin: 0 8px; }
    .profile-wrap {
      display: flex; align-items: center; gap: 12px;
      padding-left: 8px;
    }
    .profile-text { text-align: right; line-height: 1.3; }
    .profile-text .name { font-weight: 600; font-size: 14px; }
    .profile-text .role { font-size: 12px; color: var(--outline); }
    .avatar {
      width: 40px; height: 40px;
      border-radius: 50%;
      border: 1px solid var(--outline-variant);
      object-fit: cover;
    }

    /* ---------- main ---------- */
    .main {
      margin-left: var(--sidebar-width);
      margin-top: 64px;
      padding: 24px;
      flex: 1;
      min-height: calc(100vh - 64px);
      max-width: 1440px;
      width: 100%;
    }

    /* page header */
    .page-header {
      display: flex; flex-wrap: wrap;
      justify-content: space-between;
      align-items: flex-start;
      gap: 16px;
      margin-bottom: 24px;
    }
    .page-header h2 {
      font-size: 32px; font-weight: 700;
      letter-spacing: -0.02em;
      color: var(--primary);
    }
    .page-header p { font-size: 16px; color: var(--on-surface-variant); }
    .btn-primary {
      background: var(--primary);
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: var(--radius-lg);
      font-weight: 600;
      font-size: 14px;
      display: flex; align-items: center; gap: 8px;
      cursor: pointer;
      transition: opacity 0.2s, transform 0.1s;
      box-shadow: var(--shadow-md);
    }
    .btn-primary:hover { opacity: 0.9; }
    .btn-primary:active { transform: scale(0.97); }

    /* stats */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 24px;
      margin-bottom: 24px;
    }
    .stat-card {
      background: var(--surface-container-lowest);
      border: 1px solid var(--outline-variant);
      padding: 20px;
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-sm);
      transition: box-shadow 0.2s;
      position: relative;
      overflow: hidden;
    }
    .stat-card:hover { box-shadow: var(--shadow-md); }
    .stat-card .top {
      display: flex; justify-content: space-between; align-items: flex-start;
      margin-bottom: 12px;
    }
    .stat-card .icon-wrap {
      padding: 8px; border-radius: var(--radius-lg);
      display: flex; align-items: center; justify-content: center;
    }
    .stat-card .icon-wrap.blue { background: rgba(0,81,213,0.1); color: var(--secondary); }
    .stat-card .icon-wrap.green { background: rgba(46,125,50,0.12); color: #2e7d32; }
    .stat-card .icon-wrap.gray { background: rgba(68,70,81,0.08); color: var(--on-surface-variant); }
    .stat-card .badge {
      font-size: 12px; font-weight: 700; text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .stat-card .badge.blue { color: var(--secondary); }
    .stat-card .badge.green { color: #2e7d32; }
    .stat-card .badge.gray { color: var(--on-surface-variant); }
    .stat-card .value { font-size: 32px; font-weight: 700; }
    .stat-card .label { font-size: 14px; color: var(--on-surface-variant); }
    .stat-card .bg-icon {
      position: absolute; right: -16px; bottom: -16px;
      font-size: 120px; opacity: 0.04;
      pointer-events: none;
      transition: transform 0.25s;
    }
    .stat-card:hover .bg-icon { transform: scale(1.08); }

    /* table card */
    .table-card {
      background: var(--surface-container-lowest);
      border: 1px solid var(--outline-variant);
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-sm);
      overflow: hidden;
      margin-bottom: 24px;
    }
    .table-header {
      padding: 16px 20px;
      border-bottom: 1px solid var(--outline-variant);
      display: flex; justify-content: space-between; align-items: center;
      background: rgba(236,238,240,0.15);
    }
    .table-header h3 { font-size: 20px; font-weight: 600; color: var(--primary); }
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th {
      text-align: left; padding: 14px 20px;
      font-size: 12px; font-weight: 600; text-transform: uppercase;
      letter-spacing: 0.04em;
      color: var(--on-surface-variant);
      background: var(--surface-container-low);
      border-bottom: 1px solid var(--outline-variant);
    }
    td {
      padding: 16px 20px;
      border-bottom: 1px solid var(--outline-variant);
      font-size: 14px;
    }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: rgba(242,244,246,0.4); }
    .course-code { font-weight: 600; color: var(--primary); }
    .course-name { font-size: 12px; color: var(--outline); }
    .replacement-pill {
      display: inline-flex; align-items: center; gap: 4px;
      padding: 2px 12px; border-radius: 20px;
      font-size: 12px; font-weight: 700;
    }
    .replacement-pill.pending { background: #fff3e0; color: #e65100; }
    .replacement-pill.approved { background: #e8f5e9; color: #2e7d32; }
    .replacement-pill.declined { background: var(--error-container); color: var(--error); }
    .status-dot {
      display: inline-block; width: 8px; height: 8px;
      border-radius: 50%; margin-right: 4px;
    }
    .status-dot.pending { background: #e65100; }
    .status-dot.approved { background: #2e7d32; }
    .status-dot.declined { background: var(--error); }
    .action-btn {
      background: transparent; border: none;
      padding: 6px; border-radius: var(--radius-lg);
      cursor: pointer;
      transition: background 0.15s;
      color: var(--on-surface-variant);
    }
    .action-btn:hover { background: rgba(0,81,213,0.08); color: var(--secondary); }
    .action-btn.danger:hover { background: rgba(186,26,26,0.08); color: var(--error); }

    /* history */
    .history-section { margin-top: 24px; }
    .history-header {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 12px;
    }
    .history-header h3 { font-size: 20px; font-weight: 600; }
    .history-header a { color: var(--primary); font-weight: 500; font-size: 14px; text-decoration: none; }
    .history-header a:hover { text-decoration: underline; }
    .history-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
    }
    .history-item {
      display: flex; align-items: center;
      padding: 12px 16px;
      background: rgba(242,244,246,0.4);
      border: 1px solid var(--outline-variant);
      border-radius: var(--radius-lg);
      gap: 12px;
    }
    .history-item .h-icon {
      padding: 6px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
    }
    .history-item .h-icon.green { background: #e8f5e9; color: #2e7d32; }
    .history-item .h-icon.red { background: var(--error-container); color: var(--error); }
    .history-item .h-content { flex: 1; }
    .history-item .h-content .title { font-weight: 600; font-size: 14px; }
    .history-item .h-content .meta { font-size: 12px; color: var(--outline); }
    .history-item .h-status { font-size: 12px; font-weight: 500; color: var(--on-surface-variant); }

    /* modal */
    .modal-overlay {
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.3);
      backdrop-filter: blur(4px);
      display: flex; align-items: center; justify-content: center;
      z-index: 60;
      opacity: 0; pointer-events: none;
      transition: opacity 0.25s;
    }
    .modal-overlay.open { opacity: 1; pointer-events: auto; }
    .modal {
      background: var(--surface-container-lowest);
      width: 100%; max-width: 640px;
      border-radius: 24px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.15);
      transform: scale(0.96);
      transition: transform 0.25s;
      overflow: hidden;
    }
    .modal-overlay.open .modal { transform: scale(1); }
    .modal-header {
      background: var(--primary); color: white;
      padding: 20px 24px;
      display: flex; justify-content: space-between; align-items: center;
    }
    .modal-header h3 { font-size: 20px; font-weight: 600; }
    .modal-header button {
      background: transparent; border: none;
      color: white; padding: 6px;
      border-radius: 50%; cursor: pointer;
      transition: background 0.15s;
    }
    .modal-header button:hover { background: rgba(255,255,255,0.12); }
    .modal-body { padding: 24px; }
    .modal-body form { display: flex; flex-direction: column; gap: 20px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .form-group label {
      display: block; font-weight: 600; font-size: 14px;
      color: var(--on-surface-variant); margin-bottom: 4px;
    }
    .form-group select, .form-group input, .form-group textarea {
      width: 100%; padding: 10px 14px;
      background: var(--surface-container-low);
      border: 1px solid var(--outline-variant);
      border-radius: var(--radius-lg);
      font-size: 14px; font-family: 'Inter', sans-serif;
    }
    .form-group select:focus, .form-group input:focus, .form-group textarea:focus {
      outline: none; border-color: var(--secondary);
      box-shadow: 0 0 0 3px rgba(49,107,243,0.12);
    }
    .form-group textarea { resize: vertical; min-height: 80px; }
    .info-banner {
      display: flex; align-items: center; gap: 8px;
      padding: 12px 16px;
      background: rgba(0,81,213,0.04);
      border: 1px solid rgba(0,81,213,0.12);
      border-radius: var(--radius-lg);
    }
    .info-banner .material-symbols-outlined { color: var(--secondary); }
    .info-banner p { font-size: 13px; color: var(--on-surface-variant); font-style: italic; }
    .modal-actions {
      display: flex; justify-content: flex-end; gap: 12px;
      padding-top: 12px;
    }
    .modal-actions .btn-outline {
      background: transparent; border: none;
      padding: 10px 20px; border-radius: var(--radius-lg);
      font-weight: 600; font-size: 14px;
      color: var(--on-surface-variant);
      cursor: pointer; transition: background 0.15s;
    }
    .modal-actions .btn-outline:hover { background: var(--surface-container-high); }
    .modal-actions .btn-submit {
      background: var(--primary); color: white;
      border: none; padding: 10px 32px;
      border-radius: var(--radius-lg);
      font-weight: 600; font-size: 14px;
      cursor: pointer; transition: opacity 0.15s;
    }
    .modal-actions .btn-submit:hover { opacity: 0.9; }

    /* responsive */
    @media (max-width: 1024px) {
      .history-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
      :root { --sidebar-width: 0px; }
      .sidebar { transform: translateX(-100%); }
      .topbar { left: 0; }
      .main { margin-left: 0; }
      .page-header h2 { font-size: 24px; }
      .stats-grid { grid-template-columns: 1fr 1fr; }
      .form-row { grid-template-columns: 1fr; }
      .profile-text { display: none; }
      .search-wrapper { display: none; }
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
      <a href="#"><span class="material-symbols-outlined">dashboard</span> Dashboard</a>
      <a href="#"><span class="material-symbols-outlined">calendar_today</span> Timetable</a>
      <a href="#"><span class="material-symbols-outlined">assignment</span> My Tasks</a>
      <a href="#" class="active"><span class="material-symbols-outlined">swap_horiz</span> Replacement Requests</a>
      <a href="#"><span class="material-symbols-outlined">group</span> Student Records</a>
      <div style="margin-top:auto;"><a href="#"><span class="material-symbols-outlined">settings</span> Settings</a></div>
    </nav>
  </aside>

  <!-- TOPBAR -->
  <header class="topbar">
    <div class="topbar-left">
      <div class="search-wrapper">
        <span class="material-symbols-outlined">search</span>
        <input type="text" placeholder="Search requests, instructors...">
      </div>
    </div>
    <div class="topbar-right">
      <button class="icon-btn"><span class="material-symbols-outlined">notifications</span><span class="badge-dot"></span></button>
      <button class="icon-btn"><span class="material-symbols-outlined">help_outline</span></button>
      <div class="divider"></div>
      <div class="profile-wrap">
        <div class="profile-text">
          <div class="name">Dr. Sarah Jenkins</div>
          <div class="role">Senior Lecturer</div>
        </div>
        <img class="avatar" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDEoiqm2exV4u2OTi3qNZOX3kEzaE-mREp4MpY9HYo-2DolRAtEsnxqUfAoAln7SUy4IjqHySt-BRJctPiGHPDM4kk10FQqJeZA_8wjRe75j6VdYNgilmlAcSa3kFq9Kzs_KlNII-YEzIjrTJR4RemcEzSfjxJ6gCB6ev-zPS5fP1C3xW4pC_G8ipo33QUC96jKE5drt955METhPBdRQVT4sMwP-iwPQ0yTlIpbCQiCKL7g602YOTgKMQ" alt="Dr. Sarah Jenkins">
      </div>
    </div>
  </header>

  <!-- MAIN -->
  <main class="main">

    <!-- page header -->
    <div class="page-header">
      <div>
        <h2>Replacement Requests</h2>
        <p>Manage and track your session swap requests with colleagues.</p>
      </div>
      <button class="btn-primary" onclick="openModal()">
        <span class="material-symbols-outlined">add</span> New Request
      </button>
    </div>

    <!-- stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="top">
          <div class="icon-wrap blue"><span class="material-symbols-outlined">pending_actions</span></div>
          <span class="badge blue">Active</span>
        </div>
        <div class="value">04</div>
        <div class="label">Pending Approvals</div>
        <span class="bg-icon material-symbols-outlined">hourglass_empty</span>
      </div>
      <div class="stat-card">
        <div class="top">
          <div class="icon-wrap green"><span class="material-symbols-outlined">check_circle</span></div>
          <span class="badge green">Success</span>
        </div>
        <div class="value">12</div>
        <div class="label">Approved Swaps (Monthly)</div>
        <span class="bg-icon material-symbols-outlined">verified</span>
      </div>
      <div class="stat-card">
        <div class="top">
          <div class="icon-wrap gray"><span class="material-symbols-outlined">history</span></div>
          <span class="badge gray">Archived</span>
        </div>
        <div class="value">38</div>
        <div class="label">Total History</div>
        <span class="bg-icon material-symbols-outlined">folder_shared</span>
      </div>
    </div>

    <!-- active requests table -->
    <div class="table-card">
      <div class="table-header">
        <h3>Active Requests</h3>
        <button class="action-btn"><span class="material-symbols-outlined">filter_list</span></button>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Course</th><th>Date &amp; Time</th><th>Replacement</th><th>Reason</th><th>Status</th><th style="text-align:right;">Actions</th></tr>
          </thead>
          <tbody>
            <tr>
              <td><div class="course-code">IS1205</div><div class="course-name">Advanced Algorithms</div></td>
              <td><div>Oct 24, 2023</div><div style="font-size:12px;color:var(--outline);">10:00 AM - 12:00 PM</div></td>
              <td><div style="display:flex;align-items:center;gap:8px;"><span style="width:32px;height:32px;border-radius:50%;background:rgba(0,81,213,0.12);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;color:var(--secondary);">RM</span> Prof. Robert Miller</div></td>
              <td>Medical Appointment</td>
              <td><span class="replacement-pill pending"><span class="status-dot pending"></span> Pending</span></td>
              <td style="text-align:right;">
                <button class="action-btn" title="View"><span class="material-symbols-outlined">visibility</span></button>
                <button class="action-btn danger" title="Cancel"><span class="material-symbols-outlined">cancel</span></button>
              </td>
            </tr>
            <tr>
              <td><div class="course-code">CS3302</div><div class="course-name">Database Systems</div></td>
              <td><div>Oct 26, 2023</div><div style="font-size:12px;color:var(--outline);">02:00 PM - 04:00 PM</div></td>
              <td><div style="display:flex;align-items:center;gap:8px;"><span style="width:32px;height:32px;border-radius:50%;background:rgba(30,58,138,0.12);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;color:var(--primary);">EL</span> Dr. Emily Low</div></td>
              <td>Conference Attendance</td>
              <td><span class="replacement-pill approved"><span class="status-dot approved"></span> Approved</span></td>
              <td style="text-align:right;">
                <button class="action-btn" title="View"><span class="material-symbols-outlined">visibility</span></button>
              </td>
            </tr>
            <tr>
              <td><div class="course-code">MA1001</div><div class="course-name">Calculus I</div></td>
              <td><div>Oct 21, 2023</div><div style="font-size:12px;color:var(--outline);">08:00 AM - 10:00 AM</div></td>
              <td><div style="display:flex;align-items:center;gap:8px;"><span style="width:32px;height:32px;border-radius:50%;background:rgba(68,70,81,0.12);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;color:var(--on-surface-variant);">JW</span> Mr. James Wong</div></td>
              <td>Personal Emergency</td>
              <td><span class="replacement-pill declined"><span class="status-dot declined"></span> Declined</span></td>
              <td style="text-align:right;">
                <button class="action-btn" title="Info"><span class="material-symbols-outlined">info</span></button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- history -->
    <div class="history-section">
      <div class="history-header">
        <h3>Recent History</h3>
        <a href="#">View All History</a>
      </div>
      <div class="history-grid">
        <div class="history-item">
          <div class="h-icon green"><span class="material-symbols-outlined">swap_horizontal_circle</span></div>
          <div class="h-content">
            <div class="title">CS4401 Class Swapped</div>
            <div class="meta">With Prof. Alan Turing • 2 days ago</div>
          </div>
          <div class="h-status">Completed</div>
        </div>
        <div class="history-item">
          <div class="h-icon red"><span class="material-symbols-outlined">close</span></div>
          <div class="h-content">
            <div class="title">IS2203 Request Rejected</div>
            <div class="meta">Session: Oct 15 • 1 week ago</div>
          </div>
          <div class="h-status">Closed</div>
        </div>
      </div>
    </div>
  </main>

  <!-- MODAL -->
  <div class="modal-overlay" id="modalOverlay">
    <div class="modal">
      <div class="modal-header">
        <h3>Submit Replacement Request</h3>
        <button onclick="closeModal()"><span class="material-symbols-outlined">close</span></button>
      </div>
      <div class="modal-body">
        <form>
          <div class="form-row">
            <div class="form-group">
              <label>Session Course</label>
              <select><option>Select a Course</option><option>IS1205 - Advanced Algorithms</option><option>CS3302 - Database Systems</option><option>MA1001 - Calculus I</option></select>
            </div>
            <div class="form-group">
              <label>Preferred Replacement</label>
              <select><option>Select Faculty Member</option><option>Dr. Emily Low</option><option>Prof. Robert Miller</option><option>Mr. James Wong</option></select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Date</label>
              <input type="date">
            </div>
            <div class="form-group">
              <label>Time Slot</label>
              <input type="time">
            </div>
          </div>
          <div class="form-group">
            <label>Reason for Request</label>
            <textarea placeholder="Briefly explain the reason for the replacement..."></textarea>
          </div>
          <div class="info-banner">
            <span class="material-symbols-outlined">info</span>
            <p>Requests must be submitted at least 48 hours before the scheduled session start time.</p>
          </div>
          <div class="modal-actions">
            <button class="btn-outline" type="button" onclick="closeModal()">Cancel</button>
            <button class="btn-submit" type="button" onclick="closeModal()">Send Request</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    function openModal() {
      document.getElementById('modalOverlay').classList.add('open');
    }
    function closeModal() {
      document.getElementById('modalOverlay').classList.remove('open');
    }
    // close on escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeModal();
    });
    // close on overlay click
    document.getElementById('modalOverlay').addEventListener('click', (e) => {
      if (e.target === e.currentTarget) closeModal();
    });
  </script>

</body>
</html>