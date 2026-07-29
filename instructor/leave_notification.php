<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Leave Notifications · Academia Pro</title>
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
      border-right: 1px solid rgba(197,197,211,0.2);
      overflow-y: auto;
    }
    .sidebar-brand { margin-bottom: 24px; padding-left: 8px; }
    .sidebar-brand h1 { font-size: 24px; font-weight: 700; letter-spacing: -0.02em; color: white; }
    .sidebar-brand p { font-size: 14px; font-weight: 500; opacity: 0.6; }
    .sidebar-nav { flex: 1; display: flex; flex-direction: column; gap: 4px; margin-top: 8px; }
    .sidebar-nav a {
      display: flex; align-items: center; gap: 16px;
      padding: 12px 16px;
      border-radius: var(--radius-lg);
      font-weight: 500; font-size: 14px;
      color: rgba(255,255,255,0.65);
      text-decoration: none;
      transition: background 0.2s, color 0.2s;
      cursor: pointer;
    }
    .sidebar-nav a:hover { background: var(--primary-container); color: white; }
    .sidebar-nav a.active {
      background: var(--secondary-container);
      color: white;
      border-left: 4px solid var(--secondary-fixed-dim);
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
      border-bottom: 1px solid var(--surface-container-high);
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 24px;
      z-index: 40;
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
      transition: box-shadow 0.2s;
    }
    .search-wrapper input:focus { outline: none; box-shadow: 0 0 0 2px rgba(49,107,243,0.15); }
    .topbar-right { display: flex; align-items: center; gap: 12px; }
    .topbar-right .icon-btn {
      background: transparent; border: none;
      padding: 8px; border-radius: 50%;
      color: var(--on-surface-variant);
      cursor: pointer;
      transition: background 0.2s;
    }
    .topbar-right .icon-btn:hover { background: var(--surface-container-high); }
    .profile-wrap {
      display: flex; align-items: center; gap: 12px;
      padding-left: 16px; border-left: 1px solid var(--surface-container-high);
    }
    .profile-text { text-align: right; line-height: 1.3; }
    .profile-text .name { font-weight: 600; font-size: 14px; }
    .profile-text .role { font-size: 12px; color: var(--outline); }
    .avatar {
      width: 40px; height: 40px;
      border-radius: 50%;
      border: 2px solid rgba(0,81,213,0.15);
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
      align-items: flex-end;
      gap: 16px;
      margin-bottom: 24px;
    }
    .page-header h2 {
      font-size: 32px; font-weight: 700;
      letter-spacing: -0.02em;
      color: var(--primary);
      margin-bottom: 4px;
    }
    .breadcrumb {
      display: flex; align-items: center; gap: 4px;
      font-size: 12px; color: var(--on-surface-variant);
    }
    .breadcrumb a { color: var(--on-surface-variant); text-decoration: none; }
    .breadcrumb a:hover { color: var(--primary); }
    .breadcrumb .sep { font-size: 14px; }
    .breadcrumb .current { color: var(--primary); font-weight: 700; }
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
      transition: background 0.2s, transform 0.1s;
      box-shadow: 0 4px 12px rgba(0,35,111,0.2);
    }
    .btn-primary:hover { background: var(--primary-container); }
    .btn-primary:active { transform: scale(0.97); }

    /* KPI cards */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 24px;
      margin-bottom: 24px;
    }
    .kpi-card {
      background: var(--surface-container-lowest);
      border: 1px solid rgba(197,197,211,0.3);
      padding: 16px 20px;
      border-radius: var(--radius-xl);
      display: flex; align-items: center; gap: 16px;
    }
    .kpi-icon {
      width: 48px; height: 48px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .kpi-icon.primary { background: rgba(0,35,111,0.05); color: var(--primary); }
    .kpi-icon.error { background: rgba(186,26,26,0.08); color: var(--error); }
    .kpi-icon.secondary { background: rgba(0,81,213,0.06); color: var(--secondary); }
    .kpi-icon.tertiary { background: rgba(27,43,63,0.06); color: var(--tertiary); }
    .kpi-label { font-size: 12px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.04em; color: var(--on-surface-variant); }
    .kpi-value { font-size: 20px; font-weight: 600; color: var(--primary); }
    .kpi-value .sub { font-size: 12px; font-weight: 400; color: var(--on-surface-variant); }

    /* bento grid */
    .bento {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 24px;
      margin-bottom: 24px;
    }
    @media (max-width: 1024px) { .bento { grid-template-columns: 1fr; } }

    /* card */
    .card {
      background: var(--surface-container-lowest);
      border: 1px solid rgba(197,197,211,0.3);
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-sm);
      overflow: hidden;
    }
    .card-header {
      padding: 16px 20px;
      border-bottom: 1px solid var(--surface-container-high);
      display: flex; justify-content: space-between; align-items: center;
    }
    .card-header h3 {
      font-size: 20px; font-weight: 600;
      display: flex; align-items: center; gap: 8px;
      color: var(--primary);
    }
    .card-header .badge {
      padding: 4px 12px;
      background: rgba(0,81,213,0.08);
      color: var(--secondary);
      border-radius: 20px;
      font-size: 12px; font-weight: 500;
    }
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th {
      text-align: left; padding: 12px 20px;
      font-size: 12px; font-weight: 600; text-transform: uppercase;
      letter-spacing: 0.04em;
      color: var(--on-surface-variant);
      background: var(--surface-container-low);
    }
    td {
      padding: 14px 20px;
      border-top: 1px solid var(--surface-container-high);
      font-size: 14px;
    }
    tr:hover td { background: rgba(242,244,246,0.4); }
    .status-pill {
      display: inline-block;
      padding: 2px 12px;
      border-radius: 20px;
      font-size: 12px; font-weight: 600;
    }
    .status-pill.pending { background: #fef3c7; color: #92400e; }
    .status-pill.approved { background: #d1fae5; color: #065f46; }
    .status-pill.rejected { background: #fee2e2; color: #991b1b; }
    .action-group { display: flex; gap: 4px; justify-content: flex-end; }
    .action-btn {
      background: transparent; border: none;
      padding: 6px; border-radius: var(--radius-lg);
      cursor: pointer; color: var(--on-surface-variant);
      transition: background 0.15s, color 0.15s;
    }
    .action-btn:hover { background: rgba(0,81,213,0.08); color: var(--secondary); }
    .action-btn.danger:hover { background: rgba(186,26,26,0.08); color: var(--error); }

    /* mini calendar */
    .mini-cal { padding: 20px; }
    .cal-header {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 16px;
    }
    .cal-header h4 { font-size: 18px; font-weight: 600; color: var(--primary); }
    .cal-nav { display: flex; gap: 4px; }
    .cal-nav button {
      background: transparent; border: none;
      padding: 4px 8px; border-radius: var(--radius-lg);
      cursor: pointer; color: var(--on-surface-variant);
      transition: background 0.15s;
    }
    .cal-nav button:hover { background: var(--surface-container-high); }
    .cal-grid {
      display: grid; grid-template-columns: repeat(7, 1fr);
      gap: 2px; text-align: center;
    }
    .cal-grid .day-label {
      font-size: 12px; font-weight: 600;
      color: var(--on-surface-variant); padding: 4px 0;
    }
    .cal-grid .day {
      aspect-ratio: 1;
      display: flex; align-items: center; justify-content: center;
      font-size: 14px; border-radius: var(--radius-lg);
      color: var(--on-surface);
    }
    .cal-grid .day.other { color: rgba(68,70,81,0.3); }
    .cal-grid .day.highlight {
      background: rgba(245,158,11,0.08);
      color: #b45309;
      font-weight: 700;
      border: 1px solid rgba(245,158,11,0.2);
    }
    .cal-legend { margin-top: 16px; display: flex; flex-direction: column; gap: 6px; }
    .cal-legend .item { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--on-surface-variant); }
    .cal-legend .dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; }
    .cal-legend .dot.amber { background: #f59e0b; }
    .cal-legend .dot.blue { background: var(--secondary); }

    /* history section */
    .history-section { margin-top: 24px; }
    .history-toolbar {
      display: flex; flex-wrap: wrap;
      justify-content: space-between; align-items: center;
      gap: 12px; margin-bottom: 16px;
    }
    .history-toolbar h3 {
      font-size: 20px; font-weight: 600;
      display: flex; align-items: center; gap: 8px;
      color: var(--primary);
    }
    .filter-group { display: flex; gap: 8px; flex-wrap: wrap; }
    .filter-group select {
      padding: 6px 28px 6px 12px;
      background: var(--surface);
      border: 1px solid var(--outline-variant);
      border-radius: var(--radius-lg);
      font-size: 12px; font-family: 'Inter', sans-serif;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23444651' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 8px center;
      cursor: pointer;
    }
    .filter-group select:focus { outline: none; border-color: var(--secondary); }
    .filter-btn {
      display: flex; align-items: center; gap: 6px;
      padding: 6px 14px;
      border: 1px solid var(--outline-variant);
      border-radius: var(--radius-lg);
      background: transparent;
      font-size: 12px; font-weight: 500;
      color: var(--on-surface-variant);
      cursor: pointer;
      transition: background 0.15s;
    }
    .filter-btn:hover { background: var(--surface-container-high); }

    .history-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 16px;
    }
    .history-item {
      background: var(--surface);
      border: 1px solid rgba(197,197,211,0.3);
      border-radius: var(--radius-lg);
      padding: 16px;
      transition: border-color 0.2s;
    }
    .history-item:hover { border-color: rgba(0,81,213,0.2); }
    .history-item .top-row {
      display: flex; justify-content: space-between; align-items: flex-start;
      margin-bottom: 8px;
    }
    .history-item .top-row .type {
      display: flex; align-items: center; gap: 6px;
      font-weight: 600; font-size: 14px;
    }
    .history-item .top-row .status-badge {
      font-size: 10px; font-weight: 700; text-transform: uppercase;
      padding: 2px 10px; border-radius: 20px;
    }
    .history-item .top-row .status-badge.approved { background: #d1fae5; color: #065f46; }
    .history-item .top-row .status-badge.rejected { background: #fee2e2; color: #991b1b; }
    .history-item .top-row .status-badge.cancelled { background: #f3f4f6; color: #4b5563; }
    .history-item .date { font-size: 12px; color: var(--outline); }
    .history-item .reason { font-size: 14px; margin: 4px 0 8px; }
    .history-item .footer {
      display: flex; justify-content: space-between; align-items: center;
      padding-top: 10px; border-top: 1px solid rgba(197,197,211,0.2);
      font-size: 12px; color: var(--on-surface-variant);
    }
    .history-item .footer .detail-link {
      color: var(--secondary); text-decoration: none;
      font-weight: 500; display: flex; align-items: center; gap: 2px;
    }
    .history-item .footer .detail-link:hover { text-decoration: underline; }

    /* pagination */
    .pagination {
      display: flex; justify-content: space-between; align-items: center;
      margin-top: 16px;
    }
    .pagination .info { font-size: 12px; color: var(--on-surface-variant); }
    .pagination .pages { display: flex; gap: 4px; }
    .pagination .pages button {
      width: 32px; height: 32px;
      border: 1px solid var(--outline-variant);
      border-radius: var(--radius-lg);
      background: transparent;
      font-size: 14px; font-weight: 500;
      cursor: pointer; transition: background 0.15s;
    }
    .pagination .pages button:hover { background: var(--surface-container-high); }
    .pagination .pages button.active {
      background: var(--primary); color: white;
      border-color: var(--primary);
    }

    /* FAB */
    .fab {
      position: fixed; bottom: 24px; right: 24px;
      z-index: 60;
    }
    .fab button {
      width: 56px; height: 56px;
      border-radius: 50%;
      background: var(--secondary);
      color: white;
      border: none;
      box-shadow: 0 8px 24px rgba(0,81,213,0.3);
      display: flex; align-items: center; justify-content: center;
      font-size: 28px;
      cursor: pointer;
      transition: transform 0.2s;
    }
    .fab button:hover { transform: scale(1.08); }
    .fab button:active { transform: scale(0.95); }

    /* responsive */
    @media (max-width: 768px) {
      :root { --sidebar-width: 0px; }
      .sidebar { transform: translateX(-100%); }
      .topbar { left: 0; }
      .main { margin-left: 0; }
      .page-header h2 { font-size: 24px; }
      .kpi-grid { grid-template-columns: 1fr 1fr; }
      .bento { grid-template-columns: 1fr; }
      .history-grid { grid-template-columns: 1fr; }
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
      <a href="#"><span class="material-symbols-outlined">calendar_month</span> Timetable</a>
      <a href="#"><span class="material-symbols-outlined">assignment</span> My Tasks</a>
      <a href="#"><span class="material-symbols-outlined">swap_horiz</span> Replacement Requests</a>
      <a href="#" class="active"><span class="material-symbols-outlined">event_busy</span> Leave Notifications</a>
      <a href="#"><span class="material-symbols-outlined">analytics</span> Workload Summary</a>
      <div style="margin-top:auto;"><a href="#"><span class="material-symbols-outlined">settings</span> Settings</a></div>
    </nav>
  </aside>

  <!-- TOPBAR -->
  <header class="topbar">
    <div class="topbar-left">
      <div class="search-wrapper">
        <span class="material-symbols-outlined">search</span>
        <input type="text" placeholder="Search leave records...">
      </div>
    </div>
    <div class="topbar-right">
      <button class="icon-btn"><span class="material-symbols-outlined">notifications</span></button>
      <button class="icon-btn"><span class="material-symbols-outlined">help</span></button>
      <div class="profile-wrap">
        <div class="profile-text">
          <div class="name">Dr. Sarah Jenkins</div>
          <div class="role">Senior Professor</div>
        </div>
        <img class="avatar" src="https://lh3.googleusercontent.com/aida-public/AB6AXuA4boAkz6wP3_-z0TlsIkJfJSBRTu9f-bLiTDOL7203Hkg4tEzw4i0OCNaiQxPbsTeY8Sjb3B-okRpjuzWjVp1Egvvv39WMdSeSfsjzdf9VndmJ0dAb35bcDJUVI_pO31QLOqn_BQmD_U6Rwq0_80XizNB8W7DB5JqSnrXM67c1rMnV4lD1gmHrfvC3K-2SJU1If-xaykf1cwgrtgiJXaRq1CH0aJDgIG7_o6EWy3ocr39jqFiKkwnhfQ" alt="Dr. Sarah Jenkins">
      </div>
    </div>
  </header>

  <!-- MAIN -->
  <main class="main">

    <!-- page header -->
    <div class="page-header">
      <div>
        <h2>Leave Notifications</h2>
        <div class="breadcrumb">
          <a href="#">Instructor Portal</a>
          <span class="sep material-symbols-outlined" style="font-size:14px;">chevron_right</span>
          <span class="current">Leave Management</span>
        </div>
      </div>
      <button class="btn-primary">
        <span class="material-symbols-outlined">add</span> + New Leave Request
      </button>
    </div>

    <!-- KPI cards -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-icon primary"><span class="material-symbols-outlined">calendar_today</span></div>
        <div><div class="kpi-label">Annual Leave</div><div class="kpi-value">12 Days <span class="sub">/ 25</span></div></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon error"><span class="material-symbols-outlined">medical_services</span></div>
        <div><div class="kpi-label">Medical Leave</div><div class="kpi-value">05 Days <span class="sub">used</span></div></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon secondary"><span class="material-symbols-outlined">event</span></div>
        <div><div class="kpi-label">Casual Leave</div><div class="kpi-value">03 Days <span class="sub">left</span></div></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon tertiary"><span class="material-symbols-outlined">history</span></div>
        <div><div class="kpi-label">Carry Forward</div><div class="kpi-value">02 Days <span class="sub">accrued</span></div></div>
      </div>
    </div>

    <!-- bento: pending + calendar -->
    <div class="bento">
      <!-- pending requests -->
      <div class="card">
        <div class="card-header">
          <h3><span class="material-symbols-outlined" style="color:var(--primary);">pending_actions</span> Pending Requests</h3>
          <span class="badge">3 Pending</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Leave Type</th><th>Duration</th><th>Reason</th><th>Status</th><th style="text-align:right;">Actions</th></tr></thead>
            <tbody>
              <tr>
                <td><div style="font-weight:600;">Annual Leave</div><div style="font-size:12px;color:var(--outline);">#LR-4492</div></td>
                <td><div>Oct 24 - Oct 26, 2023</div><div style="font-size:12px;color:var(--outline);">3 Days Total</div></td>
                <td>Family wedding event out of state.</td>
                <td><span class="status-pill pending">Pending Review</span></td>
                <td style="text-align:right;">
                  <div class="action-group">
                    <button class="action-btn" title="Edit"><span class="material-symbols-outlined" style="font-size:20px;">edit</span></button>
                    <button class="action-btn danger" title="Cancel"><span class="material-symbols-outlined" style="font-size:20px;">cancel</span></button>
                  </div>
                </td>
              </tr>
              <tr>
                <td><div style="font-weight:600;">Casual Leave</div><div style="font-size:12px;color:var(--outline);">#LR-4501</div></td>
                <td><div>Nov 12, 2023</div><div style="font-size:12px;color:var(--outline);">1 Day Total</div></td>
                <td>Personal administration work.</td>
                <td><span class="status-pill pending">Pending Review</span></td>
                <td style="text-align:right;">
                  <div class="action-group">
                    <button class="action-btn" title="Edit"><span class="material-symbols-outlined" style="font-size:20px;">edit</span></button>
                    <button class="action-btn danger" title="Cancel"><span class="material-symbols-outlined" style="font-size:20px;">cancel</span></button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- mini calendar -->
      <div class="card">
        <div class="mini-cal">
          <div class="cal-header">
            <h4>October 2023</h4>
            <div class="cal-nav">
              <button><span class="material-symbols-outlined">chevron_left</span></button>
              <button><span class="material-symbols-outlined">chevron_right</span></button>
            </div>
          </div>
          <div class="cal-grid">
            <div class="day-label">Su</div><div class="day-label">Mo</div><div class="day-label">Tu</div><div class="day-label">We</div><div class="day-label">Th</div><div class="day-label">Fr</div><div class="day-label">Sa</div>
            <div class="day other">26</div><div class="day other">27</div><div class="day other">28</div><div class="day other">29</div><div class="day other">30</div>
            <div class="day">1</div><div class="day">2</div>
            <div class="day">3</div><div class="day">4</div><div class="day">5</div><div class="day">6</div><div class="day">7</div><div class="day">8</div><div class="day">9</div>
            <div class="day">10</div><div class="day">11</div><div class="day">12</div><div class="day">13</div><div class="day">14</div><div class="day">15</div><div class="day">16</div>
            <div class="day">17</div><div class="day">18</div><div class="day">19</div><div class="day">20</div><div class="day">21</div><div class="day">22</div><div class="day">23</div>
            <div class="day highlight">24</div><div class="day highlight">25</div><div class="day highlight">26</div>
            <div class="day">27</div><div class="day">28</div><div class="day">29</div><div class="day">30</div>
          </div>
          <div class="cal-legend">
            <div class="item"><span class="dot amber"></span> Pending Leaves (Oct 24-26)</div>
            <div class="item"><span class="dot blue"></span> Approved Academic Break</div>
          </div>
        </div>
      </div>
    </div>

    <!-- history section -->
    <div class="history-section">
      <div class="history-toolbar">
        <h3><span class="material-symbols-outlined" style="color:var(--primary);">history</span> Past Requests</h3>
        <div class="filter-group">
          <select><option>All Leave Types</option><option>Annual</option><option>Medical</option><option>Casual</option></select>
          <select><option>Status: Approved</option><option>Status: Rejected</option><option>Status: Cancelled</option></select>
          <button class="filter-btn"><span class="material-symbols-outlined" style="font-size:16px;">filter_list</span> More Filters</button>
        </div>
      </div>

      <div class="history-grid">
        <div class="history-item">
          <div class="top-row">
            <div class="type"><span class="material-symbols-outlined" style="font-size:18px;color:var(--secondary);">calendar_today</span> Annual Leave</div>
            <span class="status-badge approved">Approved</span>
          </div>
          <div class="date">Aug 15 - Aug 18, 2023</div>
          <div class="reason">Summer vacation trip to the coast.</div>
          <div class="footer">
            <span>Applied: Aug 02</span>
            <a href="#" class="detail-link">View Details <span class="material-symbols-outlined" style="font-size:14px;">arrow_forward</span></a>
          </div>
        </div>
        <div class="history-item">
          <div class="top-row">
            <div class="type"><span class="material-symbols-outlined" style="font-size:18px;color:var(--error);">medical_services</span> Medical Leave</div>
            <span class="status-badge approved">Approved</span>
          </div>
          <div class="date">Jun 10, 2023</div>
          <div class="reason">Routine dental surgery procedure.</div>
          <div class="footer">
            <span>Applied: Jun 08</span>
            <a href="#" class="detail-link">View Details <span class="material-symbols-outlined" style="font-size:14px;">arrow_forward</span></a>
          </div>
        </div>
        <div class="history-item">
          <div class="top-row">
            <div class="type"><span class="material-symbols-outlined" style="font-size:18px;color:var(--tertiary);">event</span> Casual Leave</div>
            <span class="status-badge rejected">Rejected</span>
          </div>
          <div class="date">May 05, 2023</div>
          <div class="reason">Personal errand day.</div>
          <div class="footer">
            <span>Applied: May 01</span>
            <a href="#" class="detail-link">View Reason <span class="material-symbols-outlined" style="font-size:14px;">arrow_forward</span></a>
          </div>
        </div>
      </div>

      <!-- pagination -->
      <div class="pagination">
        <span class="info">Showing 1 to 3 of 24 records</span>
        <div class="pages">
          <button><span class="material-symbols-outlined" style="font-size:18px;">chevron_left</span></button>
          <button class="active">1</button>
          <button>2</button>
          <button>3</button>
          <button><span class="material-symbols-outlined" style="font-size:18px;">chevron_right</span></button>
        </div>
      </div>
    </div>

  </main>

  <!-- FAB -->
  <div class="fab">
    <button><span class="material-symbols-outlined">post_add</span></button>
  </div>

  <script>
    // subtle row interaction
    document.querySelectorAll('tbody tr').forEach(row => {
      row.addEventListener('click', () => {
        // just a placeholder for demo
      });
    });
    // search focus ring
    const search = document.querySelector('.search-wrapper input');
    if (search) {
      search.addEventListener('focus', () => {
        search.style.boxShadow = '0 0 0 2px rgba(49,107,243,0.15)';
      });
      search.addEventListener('blur', () => {
        search.style.boxShadow = 'none';
      });
    }
  </script>

</body>
</html>