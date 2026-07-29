<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Timetable · Academia Pro</title>
  <!-- fonts & icons (only external assets, no frameworks) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  <style>
    /* ---------- reset & base ---------- */
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
    /* ---------- sidebar (fixed) ---------- */
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
    .sidebar-brand { margin-bottom: 24px; padding-left: 8px; }
    .sidebar-brand h1 { font-size: 24px; font-weight: 700; letter-spacing: -0.02em; color: white; }
    .sidebar-brand p { font-size: 14px; font-weight: 500; opacity: 0.7; color: var(--on-primary-container); }
    .sidebar-nav { flex: 1; display: flex; flex-direction: column; gap: 4px; margin-top: 8px; }
    .sidebar-nav a {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 12px 16px;
      border-radius: var(--radius-lg);
      font-weight: 500;
      font-size: 14px;
      color: rgba(255,255,255,0.75);
      text-decoration: none;
      transition: background 0.2s, color 0.2s;
      cursor: pointer;
    }
    .sidebar-nav a:hover { background: var(--primary-container); color: white; }
    .sidebar-nav a.active {
      background: var(--secondary-container);
      color: #fefcff;
      border-left: 4px solid var(--secondary-fixed-dim);
      border-radius: 0 var(--radius-lg) var(--radius-lg) 0;
      font-weight: 600;
    }
    .sidebar-nav .material-symbols-outlined { font-size: 22px; }
    .sidebar-footer { margin-top: auto; padding-top: 16px; }
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
      cursor: pointer;
      transition: opacity 0.2s;
    }
    .sidebar-footer button:hover { opacity: 0.9; }

    /* ---------- top bar ---------- */
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
    .topbar-left { display: flex; align-items: center; gap: 16px; flex: 1; }
    .search-wrapper {
      position: relative;
      max-width: 320px;
      width: 100%;
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
      border-radius: 999px;
      font-size: 14px;
      font-family: 'Inter', sans-serif;
      transition: all 0.2s;
    }
    .search-wrapper input:focus {
      outline: none;
      border-color: var(--secondary);
      box-shadow: 0 0 0 3px rgba(49,107,243,0.12);
    }
    .topbar-right { display: flex; align-items: center; gap: 12px; }
    .topbar-right .icon-btn {
      background: transparent;
      border: none;
      padding: 8px;
      border-radius: 50%;
      color: var(--on-surface-variant);
      cursor: pointer;
      transition: background 0.2s, color 0.2s;
    }
    .topbar-right .icon-btn:hover { background: var(--surface-container-high); color: var(--primary); }
    .profile-wrap {
      display: flex;
      align-items: center;
      gap: 12px;
      padding-left: 16px;
      border-left: 1px solid var(--outline-variant);
    }
    .profile-text { text-align: right; line-height: 1.3; }
    .profile-text .name { font-weight: 700; font-size: 14px; }
    .profile-text .role { font-size: 12px; color: var(--outline); }
    .avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border: 2px solid var(--primary-container);
      object-fit: cover;
      background: var(--surface-container-high);
    }

    /* ---------- main content ---------- */
    .main {
      margin-left: var(--sidebar-width);
      margin-top: 64px;
      padding: 24px;
      flex: 1;
      min-height: calc(100vh - 64px);
      max-width: 1440px;
      width: 100%;
    }

    /* header row */
    .page-header {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: flex-end;
      gap: 16px;
      margin-bottom: 24px;
    }
    .page-header h2 {
      font-size: 32px;
      font-weight: 700;
      letter-spacing: -0.02em;
      color: var(--primary);
    }
    .page-header p { font-size: 14px; color: var(--on-surface-variant); }
    .view-toggle {
      display: flex;
      background: var(--surface-container-high);
      padding: 4px;
      border-radius: var(--radius-lg);
      border: 1px solid var(--outline-variant);
    }
    .view-toggle button {
      padding: 6px 16px;
      border: none;
      background: transparent;
      border-radius: calc(var(--radius-lg) - 2px);
      font-weight: 500;
      font-size: 14px;
      color: var(--on-surface-variant);
      cursor: pointer;
      transition: 0.15s;
    }
    .view-toggle button.active {
      background: white;
      box-shadow: var(--shadow-sm);
      color: var(--primary);
      font-weight: 700;
    }
    .action-group { display: flex; gap: 12px; flex-wrap: wrap; }
    .action-group button {
      padding: 10px 20px;
      border: 1px solid var(--outline-variant);
      border-radius: var(--radius-xl);
      background: white;
      font-weight: 500;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 6px;
      cursor: pointer;
      transition: background 0.15s;
    }
    .action-group .primary-btn {
      background: var(--primary);
      color: white;
      border: none;
    }
    .action-group .primary-btn:hover { opacity: 0.92; }

    /* filters */
    .filters {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr auto;
      gap: 16px;
      background: white;
      padding: 16px 20px;
      border: 1px solid var(--outline-variant);
      border-radius: var(--radius-xl);
      margin-bottom: 24px;
      align-items: end;
    }
    .filter-group label { display: block; font-size: 12px; font-weight: 500; color: var(--on-surface-variant); margin-bottom: 4px; }
    .filter-group select {
      width: 100%;
      padding: 8px 12px;
      background: var(--surface-container-low);
      border: 1px solid var(--outline-variant);
      border-radius: var(--radius-lg);
      font-size: 14px;
      font-family: 'Inter', sans-serif;
    }
    .filter-group select:focus { outline: none; border-color: var(--secondary); }
    .clear-filters {
      background: transparent;
      border: none;
      color: var(--secondary);
      font-weight: 500;
      font-size: 14px;
      cursor: pointer;
      padding: 8px 0;
    }
    .clear-filters:hover { text-decoration: underline; }

    /* timetable grid */
    .timetable-wrap {
      background: white;
      border: 1px solid var(--outline-variant);
      border-radius: var(--radius-xl);
      overflow: hidden;
      box-shadow: var(--shadow-sm);
    }
    .grid-header {
      display: grid;
      grid-template-columns: 80px repeat(5, 1fr);
      background: var(--surface-container-low);
      border-bottom: 1px solid var(--outline-variant);
    }
    .grid-header > div {
      padding: 12px 8px;
      text-align: center;
      border-right: 1px solid var(--outline-variant);
    }
    .grid-header > div:last-child { border-right: none; }
    .grid-header .day-label { font-size: 12px; color: var(--on-surface-variant); }
    .grid-header .day-num { font-size: 20px; font-weight: 600; color: var(--primary); }
    .grid-header .highlight .day-label { color: var(--secondary); font-weight: 700; }
    .grid-header .highlight .day-num { color: var(--secondary); }

    .grid-scroll {
      position: relative;
      overflow-y: auto;
      max-height: 720px;
    }

    .timetable-grid {
      display: grid;
      grid-template-columns: 80px repeat(5, 1fr);
      grid-template-rows: repeat(10, 80px);
      position: relative;
    }

    /* time column */
    .time-col {
      display: flex;
      flex-direction: column;
      border-right: 1px solid var(--outline-variant);
      background: var(--surface-container-lowest);
    }
    .time-slot {
      height: 80px;
      display: flex;
      align-items: flex-start;
      justify-content: center;
      padding-top: 8px;
      font-size: 12px;
      color: var(--on-surface-variant);
      border-bottom: 1px solid var(--outline-variant);
    }
    .time-slot:last-child { border-bottom: none; }

    /* grid cells background */
    .grid-bg {
      grid-column: 2 / 7;
      grid-row: 1 / 11;
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      grid-template-rows: repeat(10, 80px);
      pointer-events: none;
    }
    .grid-bg > div {
      border-right: 1px solid rgba(197,197,211,0.25);
      border-bottom: 1px solid rgba(197,197,211,0.25);
    }
    .grid-bg > div:nth-child(5n) { border-right: none; }
    .grid-bg > div:nth-child(n+46) { border-bottom: none; }

    /* events overlay */
    .event {
      padding: 4px 6px;
      position: relative;
      z-index: 5;
    }
    .event-card {
      height: 100%;
      border-radius: var(--radius-lg);
      padding: 12px 12px 10px;
      box-shadow: var(--shadow-sm);
      cursor: pointer;
      transition: transform 0.15s;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      background: rgba(49,107,243,0.06);
      border-left: 4px solid var(--secondary-container);
    }
    .event-card:hover { transform: scale(1.02); }
    .event-card .top-row {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
    }
    .event-tag {
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      padding: 2px 10px;
      border-radius: 20px;
      color: white;
    }
    .event-tag.lecture { background: var(--secondary-container); }
    .event-tag.practical { background: var(--primary-container); }
    .event-tag.meeting { background: var(--tertiary-container); }
    .event-tag.research { background: var(--outline); }
    .event-title { font-weight: 700; font-size: 14px; color: var(--primary); margin-top: 4px; }
    .event-sub { font-size: 12px; color: var(--on-surface-variant); }
    .event-location {
      font-size: 12px;
      display: flex;
      align-items: center;
      gap: 4px;
      margin-top: 6px;
      color: var(--secondary-container);
    }
    .event-card.practical { background: rgba(30,58,138,0.07); border-left-color: var(--primary-container); }
    .event-card.meeting { background: rgba(49,65,86,0.07); border-left-color: var(--tertiary-container); }
    .event-card.research { background: rgba(117,118,130,0.08); border-left-color: var(--outline); }

    /* time indicator */
    .time-line {
      position: absolute;
      left: 80px;
      right: 0;
      border-top: 2px solid #ef4444;
      pointer-events: none;
      z-index: 25;
      display: none;
    }
    .time-line::before {
      content: '';
      position: absolute;
      left: -4px;
      top: -4px;
      width: 8px;
      height: 8px;
      background: #ef4444;
      border-radius: 50%;
    }

    /* footer stats */
    .stats-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 24px;
      margin-top: 24px;
    }
    .stat-card {
      background: white;
      padding: 16px 20px;
      border: 1px solid var(--outline-variant);
      border-radius: var(--radius-xl);
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .stat-icon {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      background: rgba(49,107,243,0.1);
      color: var(--secondary);
    }
    .stat-icon.primary-bg { background: rgba(0,35,111,0.08); color: var(--primary); }
    .stat-icon.tertiary-bg { background: rgba(49,65,86,0.08); color: var(--tertiary); }
    .stat-info .label { font-size: 12px; color: var(--on-surface-variant); }
    .stat-info .value { font-size: 20px; font-weight: 700; color: var(--primary); }

    /* responsive */
    @media (max-width: 1024px) {
      .filters { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 768px) {
      :root { --sidebar-width: 0px; }
      .sidebar { transform: translateX(-100%); }
      .topbar { left: 0; }
      .main { margin-left: 0; }
      .page-header h2 { font-size: 24px; }
      .filters { grid-template-columns: 1fr; }
      .grid-header { grid-template-columns: 60px repeat(5,1fr); }
      .timetable-grid { grid-template-columns: 60px repeat(5,1fr); }
      .time-slot { font-size: 10px; }
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
      <a href="#" class="active"><span class="material-symbols-outlined">calendar_month</span> Timetable</a>
      <a href="#"><span class="material-symbols-outlined">task_alt</span> My Tasks</a>
      <a href="#"><span class="material-symbols-outlined">swap_horiz</span> Replacement Requests</a>
      <a href="#"><span class="material-symbols-outlined">group</span> Student Records</a>
      <a href="#" style="margin-top:auto;"><span class="material-symbols-outlined">settings</span> Settings</a>
    </nav>
    <div class="sidebar-footer">
      <button><span class="material-symbols-outlined">add</span> New Request</button>
    </div>
  </aside>

  <!-- TOPBAR -->
  <header class="topbar">
    <div class="topbar-left">
      <div class="search-wrapper">
        <span class="material-symbols-outlined">search</span>
        <input type="text" placeholder="Search sessions, rooms, students...">
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
        <img class="avatar" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDr5QzPwJG4hKeg1lZ3ewUqHvbg-LNPm8nXM5an5xkjure0YJ0iFNkwN49zAUTRPoz0F2KOck4sxsli41SJVMOpTxJCMP_JJVwPrFahUmV70P-1N0G63xw0niZ1s498YJS5UPIOWKVI1yBh_l6wpGUQqyc4B1xY0fZzfe1sSAe3KWKzbTOe424BecR60hAfISnO9lqZCtb63y5ffH37Iq9hFut-FZojgaQKKrOKFc0htwoc5EaqQ9tOfA" alt="Dr. John Silva">
      </div>
    </div>
  </header>

  <!-- MAIN -->
  <main class="main">

    <!-- header -->
    <div class="page-header">
      <div>
        <h2>Timetable</h2>
        <p>Managing academic schedules for the Semester 1, 2024</p>
      </div>
      <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
        <div class="view-toggle">
          <button>Day</button>
          <button class="active">Week</button>
          <button>Month</button>
        </div>
        <div class="action-group">
          <button><span class="material-symbols-outlined" style="font-size:18px;">print</span> Print</button>
          <button class="primary-btn"><span class="material-symbols-outlined" style="font-size:18px;">ios_share</span> Export CSV</button>
        </div>
      </div>
    </div>

    <!-- filters -->
    <div class="filters">
      <div class="filter-group">
        <label>Course Filter</label>
        <select><option>All Courses</option><option>IS1205 - Database Systems</option><option>IS2202 - Algorithms</option><option>EN1202 - Communication</option></select>
      </div>
      <div class="filter-group">
        <label>Venue Filter</label>
        <select><option>All Venues</option><option>Hall A (Main Block)</option><option>Lab 4 (CS Wing)</option><option>Seminar Room 2</option></select>
      </div>
      <div class="filter-group">
        <label>Session Type</label>
        <select><option>All Types</option><option>Lecture</option><option>Practical</option><option>Meeting</option></select>
      </div>
      <button class="clear-filters">Clear All Filters</button>
    </div>

    <!-- timetable -->
    <div class="timetable-wrap">
      <!-- header days -->
      <div class="grid-header">
        <div><span class="material-symbols-outlined" style="color:var(--on-surface-variant);">schedule</span></div>
        <div><div class="day-label">MON</div><div class="day-num">12</div></div>
        <div class="highlight"><div class="day-label">TUE</div><div class="day-num">13</div></div>
        <div><div class="day-label">WED</div><div class="day-num">14</div></div>
        <div><div class="day-label">THU</div><div class="day-num">15</div></div>
        <div><div class="day-label">FRI</div><div class="day-num">16</div></div>
      </div>

      <!-- scrollable body -->
      <div class="grid-scroll">
        <div class="timetable-grid" style="position:relative;">

          <!-- time column -->
          <div class="time-col" style="grid-row:1/11; grid-column:1;">
            <div class="time-slot">08:00 AM</div><div class="time-slot">09:00 AM</div>
            <div class="time-slot">10:00 AM</div><div class="time-slot">11:00 AM</div>
            <div class="time-slot">12:00 PM</div><div class="time-slot">01:00 PM</div>
            <div class="time-slot">02:00 PM</div><div class="time-slot">03:00 PM</div>
            <div class="time-slot">04:00 PM</div><div class="time-slot">05:00 PM</div>
          </div>

          <!-- grid background lines -->
          <div class="grid-bg">
            <div></div><div></div><div></div><div></div><div></div>
            <div></div><div></div><div></div><div></div><div></div>
            <div></div><div></div><div></div><div></div><div></div>
            <div></div><div></div><div></div><div></div><div></div>
            <div></div><div></div><div></div><div></div><div></div>
            <div></div><div></div><div></div><div></div><div></div>
            <div></div><div></div><div></div><div></div><div></div>
            <div></div><div></div><div></div><div></div><div></div>
            <div></div><div></div><div></div><div></div><div></div>
            <div></div><div></div><div></div><div></div><div></div>
          </div>

          <!-- current time line -->
          <div class="time-line" id="timeLine"></div>

          <!-- EVENTS -->
          <!-- MON 9-11 (row 2-3) -->
          <div class="event" style="grid-column:2; grid-row:2/4;">
            <div class="event-card">
              <div><div class="top-row"><span class="event-tag lecture">Lecture</span><span class="material-symbols-outlined" style="font-size:18px; color:var(--secondary);">school</span></div></div>
              <div><div class="event-title">IS1205</div><div class="event-sub">Database Systems</div></div>
              <div class="event-location"><span class="material-symbols-outlined" style="font-size:14px;">location_on</span> Hall A</div>
            </div>
          </div>
          <!-- TUE 10-12 (row 3-5) practical -->
          <div class="event" style="grid-column:3; grid-row:3/5;">
            <div class="event-card practical">
              <div><div class="top-row"><span class="event-tag practical">Practical</span><span class="material-symbols-outlined" style="font-size:18px; color:var(--primary-container);">terminal</span></div></div>
              <div><div class="event-title">IS2202</div><div class="event-sub">Algorithms Lab</div></div>
              <div class="event-location"><span class="material-symbols-outlined" style="font-size:14px;">location_on</span> Lab 4</div>
            </div>
          </div>
          <!-- WED 1-2 (row 6) meeting -->
          <div class="event" style="grid-column:4; grid-row:6/7;">
            <div class="event-card meeting">
              <div><div class="top-row"><span class="event-tag meeting">Meeting</span><span class="material-symbols-outlined" style="font-size:18px; color:var(--tertiary-container);">groups</span></div></div>
              <div><div class="event-title">Faculty Sync</div></div>
              <div class="event-location"><span class="material-symbols-outlined" style="font-size:14px;">location_on</span> Conf. Room 3</div>
            </div>
          </div>
          <!-- THU 2-4 (row 7-9) lecture -->
          <div class="event" style="grid-column:5; grid-row:7/9;">
            <div class="event-card">
              <div><div class="top-row"><span class="event-tag lecture">Lecture</span><span class="material-symbols-outlined" style="font-size:18px; color:var(--secondary);">school</span></div></div>
              <div><div class="event-title">EN1202</div><div class="event-sub">Communication Skills</div></div>
              <div class="event-location"><span class="material-symbols-outlined" style="font-size:14px;">location_on</span> Hall B</div>
            </div>
          </div>
          <!-- FRI 4-5:30 (row 9-11) spans 1.5 rows, we do row 9-11 with h-full but grid rows are fixed, we use row 9/11 -->
          <div class="event" style="grid-column:6; grid-row:9/11;">
            <div class="event-card research" style="height:100%;">
              <div><div class="top-row"><span class="event-tag research">Research</span><span class="material-symbols-outlined" style="font-size:18px; color:var(--outline);">biotech</span></div></div>
              <div><div class="event-title">PhD Supervision</div></div>
              <div class="event-location"><span class="material-symbols-outlined" style="font-size:14px;">location_on</span> Office 302</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon"><span class="material-symbols-outlined">history_edu</span></div>
        <div class="stat-info"><div class="label">Total Teaching Hours</div><div class="value">18h / Week</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon primary-bg"><span class="material-symbols-outlined">meeting_room</span></div>
        <div class="stat-info"><div class="label">Rooms Utilized</div><div class="value">4 Venues</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon tertiary-bg"><span class="material-symbols-outlined">group_add</span></div>
        <div class="stat-info"><div class="label">Avg. Student Attendance</div><div class="value">92%</div></div>
      </div>
    </div>

  </main>

  <script>
    (function() {
      // current time line updater
      function updateTimeLine() {
        const now = new Date();
        const hours = now.getHours();
        const minutes = now.getMinutes();
        const line = document.getElementById('timeLine');
        if (!line) return;
        // grid starts at 8:00 AM, each slot = 80px for 60min
        if (hours >= 8 && hours < 18) {
          const totalMin = (hours - 8) * 60 + minutes;
          const topPx = (totalMin / 60) * 80; // 80px per hour
          line.style.top = topPx + 'px';
          line.style.display = 'block';
        } else {
          line.style.display = 'none';
        }
      }
      updateTimeLine();
      setInterval(updateTimeLine, 30000);

      // view toggle
      const toggleBtns = document.querySelectorAll('.view-toggle button');
      toggleBtns.forEach(btn => {
        btn.addEventListener('click', function() {
          toggleBtns.forEach(b => b.classList.remove('active'));
          this.classList.add('active');
        });
      });
    })();
  </script>

</body>
</html>