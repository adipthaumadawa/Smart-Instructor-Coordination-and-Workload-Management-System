<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Tasks · Academia Pro</title>
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
      background: var(--primary-container);
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
      background: var(--surface);
      border-bottom: 1px solid var(--outline-variant);
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
      color: var(--on-surface-variant);
      font-size: 20px;
    }
    .search-wrapper input {
      width: 100%; padding: 8px 12px 8px 40px;
      background: var(--surface-container-low);
      border: none;
      border-radius: var(--radius-lg);
      font-size: 14px;
      font-family: 'Inter', sans-serif;
    }
    .search-wrapper input:focus { outline: none; box-shadow: 0 0 0 2px rgba(49,107,243,0.12); }
    .topbar-right { display: flex; align-items: center; gap: 16px; }
    .topbar-right .icon-btn {
      background: transparent; border: none;
      padding: 6px; border-radius: 50%;
      color: var(--on-surface-variant);
      cursor: pointer; position: relative;
      transition: color 0.2s;
    }
    .topbar-right .icon-btn:hover { color: var(--secondary); }
    .topbar-right .badge-dot {
      position: absolute; top: 2px; right: 2px;
      width: 8px; height: 8px;
      background: var(--error); border-radius: 50%;
    }
    .profile-label {
      display: flex; align-items: center; gap: 6px;
      font-size: 12px; font-weight: 500;
      color: var(--on-surface-variant);
      cursor: pointer;
    }
    .profile-label:hover { color: var(--secondary); }

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
    .main-inner {
      display: flex; gap: 24px;
    }
    @media (max-width: 1200px) { .main-inner { flex-direction: column; } }

    .tasks-col { flex: 1; display: flex; flex-direction: column; gap: 24px; }

    /* page header */
    .page-header {
      display: flex; justify-content: space-between; align-items: center;
      flex-wrap: wrap; gap: 8px;
    }
    .page-header h2 {
      font-size: 32px; font-weight: 700;
      letter-spacing: -0.02em;
      color: var(--primary);
    }
    .page-header p { font-size: 16px; color: var(--on-surface-variant); }

    /* metrics */
    .metrics {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
      gap: 16px;
    }
    .metric-card {
      background: var(--surface);
      border: 1px solid var(--outline-variant);
      padding: 12px 16px;
      border-radius: var(--radius-lg);
      display: flex; align-items: center; gap: 12px;
      box-shadow: var(--shadow-sm);
    }
    .metric-card .icon {
      width: 48px; height: 48px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .metric-card .icon.primary { background: var(--primary-fixed, #dce1ff); color: var(--primary); }
    .metric-card .icon.green { background: #dcfce7; color: #15803d; }
    .metric-card .icon.blue { background: #dbeafe; color: #2563eb; }
    .metric-card .icon.red { background: #fee2e2; color: #dc2626; }
    .metric-card .label { font-size: 12px; font-weight: 500; color: var(--on-surface-variant); }
    .metric-card .value { font-size: 20px; font-weight: 600; color: var(--primary); }

    /* task card */
    .task-card {
      background: var(--surface);
      border: 1px solid var(--outline-variant);
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-sm);
      overflow: hidden;
    }
    .task-card .filters {
      padding: 8px 16px;
      border-bottom: 1px solid var(--outline-variant);
      background: var(--surface-container-low);
      display: flex; justify-content: space-between; align-items: center;
      flex-wrap: wrap; gap: 8px;
    }
    .task-card .filters .tabs { display: flex; gap: 16px; }
    .task-card .filters .tabs button {
      background: transparent; border: none;
      padding: 8px 0; font-size: 14px; font-weight: 600;
      color: var(--on-surface-variant);
      cursor: pointer;
      border-bottom: 2px solid transparent;
      transition: 0.15s;
    }
    .task-card .filters .tabs button.active {
      color: var(--secondary);
      border-bottom-color: var(--secondary);
    }
    .task-card .filters .actions { display: flex; gap: 4px; }
    .task-card .filters .actions button {
      background: transparent; border: none;
      padding: 6px; border-radius: var(--radius-lg);
      color: var(--on-surface-variant);
      cursor: pointer; transition: background 0.15s;
    }
    .task-card .filters .actions button:hover { background: var(--surface-container-high); }

    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th {
      text-align: left; padding: 10px 16px;
      font-size: 12px; font-weight: 600; text-transform: uppercase;
      letter-spacing: 0.04em;
      color: var(--on-surface-variant);
      background: var(--surface-container-low);
      border-bottom: 1px solid var(--outline-variant);
    }
    td {
      padding: 12px 16px;
      border-bottom: 1px solid var(--outline-variant);
      font-size: 14px;
    }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: var(--surface-container-low); }
    .task-check { margin-right: 8px; accent-color: var(--secondary); }
    .task-name { font-weight: 500; }
    .task-name.done { text-decoration: line-through; opacity: 0.5; }
    .category-badge {
      padding: 2px 12px; border-radius: 20px;
      font-size: 12px; font-weight: 600;
    }
    .category-badge.teach { background: #dbeafe; color: #2563eb; }
    .category-badge.research { background: #f3e8ff; color: #7c3aed; }
    .category-badge.admin { background: #f3f4f6; color: #4b5563; }
    .priority-high { color: #dc2626; display: flex; align-items: center; gap: 4px; }
    .priority-med { color: #d97706; display: flex; align-items: center; gap: 4px; }
    .priority-low { color: #16a34a; display: flex; align-items: center; gap: 4px; }
    .status-pill {
      display: flex; align-items: center; gap: 6px;
      font-size: 12px; font-weight: 500;
    }
    .status-pill .dot { width: 8px; height: 8px; border-radius: 50%; }
    .status-pill .dot.blue { background: #2563eb; }
    .status-pill .dot.gray { background: var(--outline-variant); }
    .status-pill .dot.green { background: #16a34a; }
    .more-btn {
      background: transparent; border: none;
      padding: 4px; border-radius: var(--radius-lg);
      color: var(--on-surface-variant);
      cursor: pointer; opacity: 0;
      transition: opacity 0.15s;
    }
    tr:hover .more-btn { opacity: 1; }
    .more-btn:hover { color: var(--primary); }

    .pagination {
      padding: 12px 16px;
      border-top: 1px solid var(--outline-variant);
      display: flex; justify-content: space-between; align-items: center;
      background: var(--surface);
    }
    .pagination .info { font-size: 12px; color: var(--on-surface-variant); }
    .pagination .pages { display: flex; gap: 4px; }
    .pagination .pages button {
      padding: 4px 8px; border: 1px solid var(--outline-variant);
      border-radius: var(--radius-lg);
      background: transparent; font-size: 14px;
      cursor: pointer; transition: 0.15s;
    }
    .pagination .pages button:hover { background: var(--surface-container-high); }
    .pagination .pages button:disabled { opacity: 0.4; cursor: not-allowed; }

    /* bento extra */
    .bento-grid {
      display: grid; grid-template-columns: 1fr 1fr;
      gap: 16px;
    }
    @media (max-width: 600px) { .bento-grid { grid-template-columns: 1fr; } }
    .bento-card {
      padding: 16px 20px;
      border-radius: var(--radius-xl);
      position: relative; overflow: hidden;
    }
    .bento-card.primary {
      background: var(--primary); color: white;
    }
    .bento-card.primary .btn-secondary {
      background: var(--secondary); color: white;
      border: none; padding: 8px 20px;
      border-radius: var(--radius-lg);
      font-weight: 600; font-size: 14px;
      cursor: pointer; transition: opacity 0.15s;
      margin-top: 8px;
    }
    .bento-card.primary .btn-secondary:hover { opacity: 0.85; }
    .bento-card.light {
      background: var(--surface-container-high);
      border: 1px solid var(--outline-variant);
    }
    .bento-card .bg-icon {
      position: absolute; right: -16px; bottom: -16px;
      font-size: 100px; opacity: 0.08;
      pointer-events: none;
    }
    .bento-card .bg-icon.dark { opacity: 0.15; }

    /* right sidebar */
    .right-sidebar {
      width: 320px;
      display: flex; flex-direction: column;
      gap: 24px;
      flex-shrink: 0;
    }
    @media (max-width: 1200px) { .right-sidebar { width: 100%; flex-direction: row; flex-wrap: wrap; } }
    .right-sidebar .block {
      background: var(--surface);
      border: 1px solid var(--outline-variant);
      border-radius: var(--radius-xl);
      padding: 16px 20px;
      box-shadow: var(--shadow-sm);
    }
    .block .title {
      font-size: 12px; font-weight: 600; text-transform: uppercase;
      letter-spacing: 0.04em; color: var(--primary);
      margin-bottom: 12px;
    }
    .progress-bar {
      width: 100%; height: 6px;
      background: var(--surface-container-high);
      border-radius: 999px;
      overflow: hidden;
      margin: 4px 0 8px;
    }
    .progress-bar .fill { height: 100%; background: var(--secondary); border-radius: 999px; }
    .mini-chart {
      display: flex; align-items: flex-end; gap: 4px;
      height: 60px; padding: 4px 0;
    }
    .mini-chart .bar {
      flex: 1; border-radius: 2px 2px 0 0;
      background: var(--primary);
      opacity: 0.15;
    }
    .mini-chart .bar.high { opacity: 0.7; background: var(--secondary); }
    .deadline-item {
      display: flex; gap: 12px;
      padding: 8px 0 8px 12px;
      border-left: 2px solid var(--error);
      margin-bottom: 12px;
    }
    .deadline-item.amber { border-left-color: #f59e0b; }
    .deadline-item.blue { border-left-color: #3b82f6; }
    .deadline-item .label { font-weight: 600; font-size: 14px; }
    .deadline-item .meta { font-size: 12px; color: var(--outline); }
    .map-card .map-img {
      height: 80px;
      background: var(--surface-variant);
      position: relative;
      overflow: hidden;
      margin: 0 -20px;
    }
    .map-card .map-img img {
      width: 100%; height: 100%; object-fit: cover;
      filter: grayscale(0.6); opacity: 0.6;
    }
    .map-card .map-dot {
      position: absolute; top: 50%; left: 50%;
      transform: translate(-50%, -50%);
      width: 12px; height: 12px;
      background: var(--secondary);
      border-radius: 50%;
      box-shadow: 0 0 0 4px rgba(0,81,213,0.2);
    }

    /* FAB */
    .fab {
      position: fixed; bottom: 24px; right: 24px;
      z-index: 60;
    }
    .fab button {
      width: 56px; height: 56px;
      border-radius: 50%;
      background: var(--primary);
      color: white;
      border: none;
      box-shadow: 0 8px 24px rgba(0,35,111,0.25);
      display: flex; align-items: center; justify-content: center;
      font-size: 28px;
      cursor: pointer;
      transition: transform 0.2s;
    }
    .fab button:hover { transform: scale(1.06); }
    .fab button:active { transform: scale(0.95); }

    /* responsive */
    @media (max-width: 768px) {
      :root { --sidebar-width: 0px; }
      .sidebar { transform: translateX(-100%); }
      .topbar { left: 0; }
      .main { margin-left: 0; }
      .page-header h2 { font-size: 24px; }
      .metrics { grid-template-columns: 1fr 1fr; }
      .right-sidebar { flex-direction: column; }
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
      <a href="#" class="active"><span class="material-symbols-outlined">assignment</span> My Tasks</a>
      <a href="#"><span class="material-symbols-outlined">swap_horiz</span> Replacement Requests</a>
      <a href="#"><span class="material-symbols-outlined">event_busy</span> Leave Notifications</a>
      <a href="#"><span class="material-symbols-outlined">analytics</span> Workload Summary</a>
      <div style="margin-top:auto;"><a href="#"><span class="material-symbols-outlined">settings</span> Settings</a></div>
    </nav>
  </aside>

  <!-- TOPBAR -->
  <header class="topbar">
    <div class="topbar-left">
      <div class="search-wrapper">
        <span class="material-symbols-outlined">search</span>
        <input type="text" placeholder="Search tasks, students, or documents...">
      </div>
    </div>
    <div class="topbar-right">
      <button class="icon-btn"><span class="material-symbols-outlined">notifications</span><span class="badge-dot"></span></button>
      <div class="profile-label"><span class="material-symbols-outlined">account_circle</span> Prof. Anderson</div>
    </div>
  </header>

  <!-- MAIN -->
  <main class="main">
    <div class="main-inner">

      <!-- TASKS COLUMN -->
      <div class="tasks-col">

        <!-- page header -->
        <div class="page-header">
          <div>
            <h2>My Tasks</h2>
            <p>Manage your academic and administrative responsibilities.</p>
          </div>
        </div>

        <!-- metrics -->
        <div class="metrics">
          <div class="metric-card">
            <div class="icon primary"><span class="material-symbols-outlined">assignment</span></div>
            <div><div class="label">Total Tasks</div><div class="value">12</div></div>
          </div>
          <div class="metric-card">
            <div class="icon green"><span class="material-symbols-outlined">check_circle</span></div>
            <div><div class="label">Completed</div><div class="value">8</div></div>
          </div>
          <div class="metric-card">
            <div class="icon blue"><span class="material-symbols-outlined">pending</span></div>
            <div><div class="label">In Progress</div><div class="value">3</div></div>
          </div>
          <div class="metric-card">
            <div class="icon red"><span class="material-symbols-outlined">priority_high</span></div>
            <div><div class="label">High Priority</div><div class="value">1</div></div>
          </div>
        </div>

        <!-- task table -->
        <div class="task-card">
          <div class="filters">
            <div class="tabs">
              <button class="active">All</button>
              <button>Teaching</button>
              <button>Research</button>
              <button>Admin</button>
            </div>
            <div class="actions">
              <button><span class="material-symbols-outlined">filter_list</span></button>
              <button><span class="material-symbols-outlined">sort</span></button>
            </div>
          </div>
          <div class="table-wrap">
            <table>
              <thead><tr><th>Task Name</th><th>Category</th><th>Priority</th><th>Due Date</th><th>Status</th><th></th></tr></thead>
              <tbody>
                <tr>
                  <td><input class="task-check" type="checkbox"> <span class="task-name">Prepare Mid-term AI Ethics Paper</span></td>
                  <td><span class="category-badge teach">Teaching</span></td>
                  <td><span class="priority-high"><span class="material-symbols-outlined" style="font-size:18px;">signal_cellular_alt</span> High</span></td>
                  <td>Oct 24, 2023</td>
                  <td><span class="status-pill"><span class="dot blue"></span> In Progress</span></td>
                  <td><button class="more-btn"><span class="material-symbols-outlined">more_vert</span></button></td>
                </tr>
                <tr>
                  <td><input class="task-check" type="checkbox"> <span class="task-name">Grade Neural Network Assignments</span></td>
                  <td><span class="category-badge teach">Teaching</span></td>
                  <td><span class="priority-med"><span class="material-symbols-outlined" style="font-size:18px;">signal_cellular_alt_2_bar</span> Medium</span></td>
                  <td>Oct 26, 2023</td>
                  <td><span class="status-pill"><span class="dot gray"></span> Not Started</span></td>
                  <td><button class="more-btn"><span class="material-symbols-outlined">more_vert</span></button></td>
                </tr>
                <tr>
                  <td><input class="task-check" type="checkbox"> <span class="task-name">Finalize Thesis Review for Sarah J.</span></td>
                  <td><span class="category-badge research">Research</span></td>
                  <td><span class="priority-med"><span class="material-symbols-outlined" style="font-size:18px;">signal_cellular_alt_2_bar</span> Medium</span></td>
                  <td>Oct 28, 2023</td>
                  <td><span class="status-pill"><span class="dot blue"></span> In Progress</span></td>
                  <td><button class="more-btn"><span class="material-symbols-outlined">more_vert</span></button></td>
                </tr>
                <tr>
                  <td><input class="task-check" type="checkbox"> <span class="task-name">Department Faculty Meeting</span></td>
                  <td><span class="category-badge admin">Admin</span></td>
                  <td><span class="priority-low"><span class="material-symbols-outlined" style="font-size:18px;">signal_cellular_alt_1_bar</span> Low</span></td>
                  <td>Oct 30, 2023</td>
                  <td><span class="status-pill"><span class="dot gray"></span> Not Started</span></td>
                  <td><button class="more-btn"><span class="material-symbols-outlined">more_vert</span></button></td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="pagination">
            <span class="info">Showing 1-4 of 12 tasks</span>
            <div class="pages">
              <button disabled><span class="material-symbols-outlined" style="font-size:18px;">chevron_left</span></button>
              <button><span class="material-symbols-outlined" style="font-size:18px;">chevron_right</span></button>
            </div>
          </div>
        </div>

        <!-- bento extra -->
        <div class="bento-grid">
          <div class="bento-card primary">
            <h4 style="font-size:20px;font-weight:600;margin-bottom:4px;">Academic Assistant</h4>
            <p style="opacity:0.8;font-size:14px;">You have 3 grading tasks due by Friday. Would you like to schedule a focus block?</p>
            <button class="btn-secondary">Suggest Slots</button>
            <span class="bg-icon dark material-symbols-outlined">psychology</span>
          </div>
          <div class="bento-card light">
            <h4 style="font-size:20px;font-weight:600;color:var(--primary);margin-bottom:4px;">Thesis Track</h4>
            <p style="font-size:14px;color:var(--on-surface-variant);margin-bottom:8px;">Sarah Jenkins has uploaded her final draft for review.</p>
            <div style="display:flex;align-items:center;gap:12px;">
              <div style="width:40px;height:40px;border-radius:50%;border:2px solid white;overflow:hidden;box-shadow:var(--shadow-sm);">
                <img style="width:100%;height:100%;object-fit:cover;" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBatiKgOaTlly9Bza1yoM4wbagqK9rDaQDgiGCWSq2yGJKU4oDFFpDCkKqCheWp4qNMiTz-trX_kJORCVfUUATWCQDs0J7h36koxKjXLcaVFX4Z1RBc0RiWAxTkFLiqUfcpfdDXd_OsTDPa-99I3e3e1aYzmlP7qyfU8fvrha3MpEoHiBVVUH0lQoq9VEyNFPq1oUOfIfFgs7La71TrRRn21_79dbugX1Xaq7J5grzq0EG-uQkVWAWUFA" alt="Sarah J">
              </div>
              <a href="#" style="color:var(--secondary);font-weight:500;font-size:14px;text-decoration:none;">Review Document →</a>
            </div>
            <span class="bg-icon material-symbols-outlined">menu_book</span>
          </div>
        </div>
      </div>

      <!-- RIGHT SIDEBAR -->
      <aside class="right-sidebar">

        <button style="width:100%;padding:16px;background:var(--primary);color:white;border:none;border-radius:var(--radius-lg);font-weight:600;font-size:14px;display:flex;align-items:center;justify-content:center;gap:8px;cursor:pointer;transition:opacity 0.15s;box-shadow:var(--shadow-md);">
          <span class="material-symbols-outlined">add_task</span> Add New Task
        </button>

        <div class="block">
          <div class="title">Weekly Progress</div>
          <div style="display:flex;justify-content:space-between;font-size:14px;"><span>Completed vs Target</span><span style="font-weight:700;color:var(--primary);">66%</span></div>
          <div class="progress-bar"><div class="fill" style="width:66%;"></div></div>
          <p style="font-size:12px;color:var(--on-surface-variant);font-style:italic;">"You're 15% more productive than last week, Professor."</p>
          <div class="mini-chart">
            <div class="bar" style="height:20%;"></div>
            <div class="bar" style="height:40%;"></div>
            <div class="bar" style="height:60%;"></div>
            <div class="bar high" style="height:90%;"></div>
            <div class="bar" style="height:70%;"></div>
            <div class="bar" style="height:45%;"></div>
            <div class="bar" style="height:25%;"></div>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--on-surface-variant);font-weight:500;">
            <span>MON</span><span>TUE</span><span>WED</span><span>THU</span><span>FRI</span><span>SAT</span><span>SUN</span>
          </div>
        </div>

        <div class="block">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <div class="title" style="margin-bottom:0;">Critical Deadlines</div>
            <span class="material-symbols-outlined" style="color:var(--error);font-size:20px;">notification_important</span>
          </div>
          <div class="deadline-item"><div><div class="label">Mid-term Papers</div><div class="meta">In 2 days</div></div></div>
          <div class="deadline-item amber"><div><div class="label">Faculty Survey</div><div class="meta">In 4 days</div></div></div>
          <div class="deadline-item blue"><div><div class="label">Research Journal Submission</div><div class="meta">Next week</div></div></div>
        </div>

        <div class="block map-card">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
            <span class="material-symbols-outlined" style="color:var(--secondary);">location_on</span>
            <span style="font-weight:700;font-size:14px;">Main Campus - Hall A</span>
          </div>
          <div class="map-img">
            <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuCzQAX8i-EyyzsYM0UbjzTkw_EuTFf_b5Ki9t-Kb9mDgk-PDn0TChiO2TxjRRh3t4E-O1D6xFkT7Bpd3tRw0DoVbnX1AzPAxAW2Q2JU2R6rxSrNfNIgfMdXu--WiXNi_ETZj9wnDbt88Lffg5aEp61Vu-tUlQlCLhdXIeOwsQVn9qHj7MygtRDKihBInjpSoPCDkSY2fhLMiUBC8_b-nj20o3OuskDczi18dfDp8ZXOFeoF8xv_3GnFVw" alt="campus map">
            <div class="map-dot"></div>
          </div>
          <p style="font-size:14px;color:var(--on-surface-variant);margin-top:8px;">Next meeting: <span style="font-weight:700;color:var(--primary);">Department Hall, 2:00 PM</span></p>
        </div>

      </aside>
    </div>
  </main>

  <!-- FAB -->
  <div class="fab">
    <button><span class="material-symbols-outlined">add</span></button>
  </div>

  <script>
    // checkbox toggle strikethrough
    document.querySelectorAll('.task-check').forEach(cb => {
      cb.addEventListener('change', function() {
        const name = this.closest('tr').querySelector('.task-name');
        if (this.checked) {
          name.classList.add('done');
        } else {
          name.classList.remove('done');
        }
      });
    });

    // filter tabs
    document.querySelectorAll('.tabs button').forEach(btn => {
      btn.addEventListener('click', function() {
        document.querySelectorAll('.tabs button').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
      });
    });

    // prevent nav reload
    document.querySelectorAll('.sidebar-nav a').forEach(link => {
      link.addEventListener('click', (e) => { if (link.getAttribute('href') === '#') e.preventDefault(); });
    });
  </script>

</body>
</html>