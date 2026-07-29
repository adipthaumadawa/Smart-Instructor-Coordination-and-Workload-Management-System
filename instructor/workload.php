<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Workload Summary · Academia Pro</title>
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
    .sidebar-brand p { font-size: 14px; font-weight: 500; opacity: 0.8; }
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
    .sidebar-footer {
      margin-top: auto;
      padding-top: 16px;
      border-top: 1px solid rgba(30,58,138,0.3);
    }
    .sidebar-footer .profile-row {
      display: flex; align-items: center; gap: 12px;
    }
    .sidebar-footer .avatar {
      width: 40px; height: 40px;
      border-radius: 50%;
      background: var(--secondary);
      overflow: hidden;
    }
    .sidebar-footer .avatar img { width: 100%; height: 100%; object-fit: cover; }
    .sidebar-footer .name { font-weight: 500; font-size: 14px; }
    .sidebar-footer .role { font-size: 10px; opacity: 0.7; }

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
    .topbar-left h2 {
      font-size: 24px; font-weight: 700;
      color: var(--primary);
      letter-spacing: -0.01em;
    }
    .topbar-right { display: flex; align-items: center; gap: 16px; }
    .search-wrap {
      display: flex; align-items: center;
      background: var(--surface-container-low);
      padding: 4px 12px;
      border-radius: var(--radius-lg);
      border: 1px solid var(--outline-variant);
    }
    .search-wrap .material-symbols-outlined { color: var(--outline); font-size: 20px; }
    .search-wrap input {
      border: none; background: transparent;
      padding: 6px 8px; font-size: 14px;
      font-family: 'Inter', sans-serif;
      width: 200px;
    }
    .search-wrap input:focus { outline: none; }
    .topbar-right .icon-btn {
      background: transparent; border: none;
      padding: 6px; border-radius: var(--radius-lg);
      color: var(--primary);
      cursor: pointer;
      transition: background 0.15s;
    }
    .topbar-right .icon-btn:hover { background: var(--surface-container-low); }

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

    /* glass card */
    .glass {
      background: rgba(255,255,255,0.92);
      backdrop-filter: blur(4px);
      border: 1px solid #e2e8f0;
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-sm);
      transition: box-shadow 0.2s;
    }
    .glass:hover { box-shadow: var(--shadow-md); }

    /* KPI grid */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 24px;
      margin-bottom: 24px;
    }
    .kpi-card {
      padding: 16px 20px;
      display: flex; flex-direction: column;
      justify-content: space-between;
    }
    .kpi-card .top {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 6px;
    }
    .kpi-card .top .label { font-size: 12px; font-weight: 500; color: var(--outline); text-transform: uppercase; letter-spacing: 0.04em; }
    .kpi-card .value { font-size: 32px; font-weight: 700; color: var(--primary); }
    .kpi-card .sub { font-size: 12px; }
    .kpi-card .sub.green { color: #15803d; }
    .kpi-card .sub.error { color: var(--error); }
    .kpi-card .bar {
      width: 100%; height: 6px;
      background: var(--surface-container-high);
      border-radius: 999px;
      margin-top: 8px;
      overflow: hidden;
    }
    .kpi-card .bar .fill {
      height: 100%; background: var(--primary);
      border-radius: 999px;
    }

    /* distribution + trend */
    .two-col {
      display: grid;
      grid-template-columns: 1fr 1.6fr;
      gap: 24px;
      margin-bottom: 24px;
    }
    @media (max-width: 1024px) { .two-col { grid-template-columns: 1fr; } }

    .donut-wrap {
      display: flex; flex-direction: column; align-items: center;
      padding: 20px;
    }
    .donut {
      position: relative;
      width: 160px; height: 160px;
      border-radius: 50%;
      background: conic-gradient(var(--primary) 0% 45%, #2563eb 45% 75%, #316bf3 75% 90%, #dce1ff 90% 100%);
      margin-bottom: 16px;
    }
    .donut-center {
      position: absolute; inset: 12px;
      background: white;
      border-radius: 50%;
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
    }
    .donut-center .big { font-size: 24px; font-weight: 700; color: var(--primary); }
    .donut-center .small { font-size: 12px; color: var(--outline); }
    .legend-grid {
      display: grid; grid-template-columns: 1fr 1fr;
      gap: 8px 16px;
      width: 100%;
    }
    .legend-item { display: flex; align-items: center; gap: 8px; font-size: 14px; }
    .legend-item .dot { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; }

    /* trend chart */
    .trend-chart {
      padding: 20px;
      position: relative;
      height: 100%;
      min-height: 240px;
    }
    .trend-header {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 16px;
    }
    .trend-header h3 { font-size: 20px; font-weight: 600; color: var(--primary); }
    .trend-toggle { display: flex; gap: 4px; }
    .trend-toggle button {
      padding: 4px 12px;
      border: none; border-radius: var(--radius-lg);
      font-size: 12px; font-weight: 500;
      background: transparent; color: var(--outline);
      cursor: pointer; transition: 0.15s;
    }
    .trend-toggle button.active {
      background: var(--surface-container-low);
      color: var(--primary);
    }
    .trend-toggle button:hover { background: var(--surface-container-high); }
    .chart-area {
      position: relative;
      height: 140px;
      width: 100%;
    }
    .chart-area svg { width: 100%; height: 100%; }

    /* table card */
    .table-card { padding: 0; overflow: hidden; }
    .table-card .hd {
      padding: 16px 20px;
      border-bottom: 1px solid var(--outline-variant);
    }
    .table-card .hd h3 { font-size: 20px; font-weight: 600; color: var(--primary); }
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th {
      text-align: left; padding: 12px 20px;
      font-size: 12px; font-weight: 600; text-transform: uppercase;
      letter-spacing: 0.04em;
      color: var(--outline);
      background: var(--surface-container-low);
    }
    td {
      padding: 14px 20px;
      border-top: 1px solid rgba(197,197,211,0.3);
      font-size: 14px;
    }
    tr:hover td { background: rgba(242,244,246,0.4); }
    .badge-cat {
      padding: 4px 12px; border-radius: 20px;
      font-size: 12px; font-weight: 500;
    }
    .badge-cat.teach { background: rgba(0,35,111,0.08); color: var(--primary); }
    .badge-cat.research { background: rgba(0,81,213,0.08); color: var(--secondary); }
    .badge-cat.admin { background: rgba(49,65,86,0.08); color: var(--tertiary-container); }
    .status-dot {
      display: inline-block; width: 8px; height: 8px;
      border-radius: 50%; margin-right: 6px;
    }
    .status-dot.green { background: #15803d; }
    .status-dot.yellow { background: #eab308; }
    .status-dot.blue { background: #3b82f6; }

    /* insights */
    .insight-card {
      padding: 16px 20px;
      background: var(--primary);
      color: white;
      border-radius: var(--radius-lg);
    }
    .insight-card .title { font-size: 20px; font-weight: 600; display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
    .insight-card .item {
      background: var(--primary-container);
      padding: 12px 16px;
      border-radius: var(--radius-lg);
      margin-bottom: 10px;
      border: 1px solid rgba(255,255,255,0.08);
    }
    .insight-card .item p:first-child { font-weight: 600; font-size: 14px; }
    .insight-card .item p:last-child { font-size: 12px; opacity: 0.8; }
    .insight-card .btn {
      width: 100%; padding: 10px;
      background: var(--secondary);
      color: white; border: none;
      border-radius: var(--radius-lg);
      font-weight: 600; font-size: 14px;
      cursor: pointer; transition: opacity 0.15s;
    }
    .insight-card .btn:hover { opacity: 0.9; }

    .alert-card {
      padding: 16px 20px;
      border-left: 4px solid var(--error);
      border-radius: var(--radius-lg);
      background: rgba(255,255,255,0.92);
      border: 1px solid #e2e8f0;
      border-left-width: 4px;
      display: flex; align-items: flex-start; gap: 12px;
    }
    .alert-card .icon { color: var(--error); }
    .alert-card .title { font-weight: 600; font-size: 14px; color: var(--primary); }
    .alert-card .desc { font-size: 12px; color: var(--outline); }

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
      .kpi-grid { grid-template-columns: 1fr 1fr; }
      .two-col { grid-template-columns: 1fr; }
      .search-wrap { display: none; }
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
      <a href="#"><span class="material-symbols-outlined">swap_horiz</span> Replacement Requests</a>
      <a href="#"><span class="material-symbols-outlined">event_busy</span> Leave Notifications</a>
      <a href="#" class="active"><span class="material-symbols-outlined" style="font-variation-settings:'FILL'1;">bar_chart</span> Workload Summary</a>
      <a href="#"><span class="material-symbols-outlined">settings</span> Settings</a>
    </nav>
    <div class="sidebar-footer">
      <div class="profile-row">
        <div class="avatar"><img src="https://lh3.googleusercontent.com/aida-public/AB6AXuBqnTZWaiYe1UKKRe7XeK0FZL4veayUr6geU45EotOaVJPy3hTKNiglSqU3ueo2ujv2BgPUFwGLb--Es7vgYCrxKpzOOQ_8MK_3XxSeFjhTuPRmNyoUNhfM3k-pR2eD472eAbguzzR1syaIMvr7AOUmEEuzhfLNRX-s3NN6eF-R5UkYAj2W3ujtyy8XVJ33JHenIDhrRwCpSrsoewWrlRCaAOfeL9c5mw1UtiKrIrNsT_Gfs4NUoi51pQ" alt="Dr. Julian Vance"></div>
        <div><div class="name">Dr. Julian Vance</div><div class="role">Senior Lecturer</div></div>
      </div>
    </div>
  </aside>

  <!-- TOPBAR -->
  <header class="topbar">
    <div class="topbar-left"><h2>Workload Summary</h2></div>
    <div class="topbar-right">
      <div class="search-wrap">
        <span class="material-symbols-outlined">search</span>
        <input type="text" placeholder="Search analytics...">
      </div>
      <button class="icon-btn"><span class="material-symbols-outlined">notifications</span></button>
      <button class="icon-btn"><span class="material-symbols-outlined">apps</span></button>
    </div>
  </header>

  <!-- MAIN -->
  <main class="main">

    <!-- KPI cards -->
    <div class="kpi-grid">
      <div class="glass kpi-card">
        <div class="top"><span class="label">Total Contact Hours</span><span class="material-symbols-outlined" style="color:var(--primary);">schedule</span></div>
        <div><div class="value">164.5</div><div class="sub green">↑ 12% vs last semester</div></div>
      </div>
      <div class="glass kpi-card">
        <div class="top"><span class="label">Research Points</span><span class="material-symbols-outlined" style="color:var(--primary);">biotech</span></div>
        <div><div class="value">820</div><div class="sub" style="color:var(--primary);">Target: 1,000 pts</div></div>
        <div class="bar"><div class="fill" style="width:82%;"></div></div>
      </div>
      <div class="glass kpi-card">
        <div class="top"><span class="label">Student Feedback</span><span class="material-symbols-outlined" style="color:var(--primary);">star</span></div>
        <div><div class="value">4.8<span style="font-size:20px;font-weight:400;color:var(--outline);">/5</span></div><div class="sub green">Top 5% of Department</div></div>
      </div>
      <div class="glass kpi-card">
        <div class="top"><span class="label">Admin Contribution</span><span class="material-symbols-outlined" style="color:var(--primary);">account_balance</span></div>
        <div><div class="value">24%</div><div class="sub error">Above standard limit (20%)</div></div>
      </div>
    </div>

    <!-- two col: donut + trend -->
    <div class="two-col">
      <!-- donut -->
      <div class="glass donut-wrap">
        <div style="display:flex;justify-content:space-between;width:100%;margin-bottom:12px;">
          <h3 style="font-size:20px;font-weight:600;color:var(--primary);">Workload Distribution</h3>
          <span class="material-symbols-outlined" style="color:var(--outline);">info</span>
        </div>
        <div class="donut">
          <div class="donut-center"><span class="big">100%</span><span class="small">Allocated</span></div>
        </div>
        <div class="legend-grid">
          <div class="legend-item"><span class="dot" style="background:var(--primary);"></span>Lectures (45%)</div>
          <div class="legend-item"><span class="dot" style="background:#2563eb;"></span>Research (30%)</div>
          <div class="legend-item"><span class="dot" style="background:#316bf3;"></span>Admin (15%)</div>
          <div class="legend-item"><span class="dot" style="background:#dce1ff;"></span>Other (10%)</div>
        </div>
      </div>

      <!-- trend -->
      <div class="glass trend-chart">
        <div class="trend-header">
          <h3>Monthly Semester Trend</h3>
          <div class="trend-toggle">
            <button class="active">Hours</button>
            <button>Points</button>
          </div>
        </div>
        <div class="chart-area">
          <svg viewBox="0 0 600 160" preserveAspectRatio="none">
            <defs><linearGradient id="grad" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#1e3a8a" stop-opacity="0.2"/><stop offset="100%" stop-color="#1e3a8a" stop-opacity="0"/></linearGradient></defs>
            <path d="M0,140 Q75,110 150,80 T300,50 T450,120 T600,60 L600,160 L0,160 Z" fill="url(#grad)"/>
            <path d="M0,140 Q75,110 150,80 T300,50 T450,120 T600,60" fill="none" stroke="#1e3a8a" stroke-width="2.5" stroke-linecap="round"/>
            <circle cx="150" cy="80" r="5" fill="#1e3a8a"/>
            <circle cx="300" cy="50" r="5" fill="#1e3a8a"/>
            <circle cx="450" cy="120" r="5" fill="#1e3a8a"/>
            <circle cx="600" cy="60" r="5" fill="#1e3a8a"/>
          </svg>
          <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--outline);margin-top:6px;">
            <span>Sep</span><span>Oct</span><span>Nov</span><span>Dec</span><span>Jan</span>
          </div>
        </div>
      </div>
    </div>

    <!-- table + insights -->
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;">
      <!-- table -->
      <div class="glass table-card">
        <div class="hd"><h3>Assigned Task Breakdown</h3></div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Task Identity</th><th>Category</th><th style="text-align:center;">Workload (H)</th><th>Status</th></tr></thead>
            <tbody>
              <tr>
                <td><div style="font-weight:600;color:var(--primary);">Advanced AI Ethics Lecture</div><div style="font-size:12px;color:var(--outline);">CS-402, Semester 1</div></td>
                <td><span class="badge-cat teach">Teaching</span></td>
                <td style="text-align:center;">45.0</td>
                <td><span class="status-dot green"></span><span style="font-size:12px;color:#15803d;">On Track</span></td>
              </tr>
              <tr>
                <td><div style="font-weight:600;color:var(--primary);">Neural Network Optimization Study</div><div style="font-size:12px;color:var(--outline);">Publication Phase</div></td>
                <td><span class="badge-cat research">Research</span></td>
                <td style="text-align:center;">60.0</td>
                <td><span class="status-dot yellow"></span><span style="font-size:12px;color:#ca8a04;">In Progress</span></td>
              </tr>
              <tr>
                <td><div style="font-weight:600;color:var(--primary);">Curriculum Review Committee</div><div style="font-size:12px;color:var(--outline);">Departmental Service</div></td>
                <td><span class="badge-cat admin">Admin</span></td>
                <td style="text-align:center;">12.5</td>
                <td><span class="status-dot blue"></span><span style="font-size:12px;color:#2563eb;">Completed</span></td>
              </tr>
              <tr>
                <td><div style="font-weight:600;color:var(--primary);">Master's Thesis Supervision (4 students)</div><div style="font-size:12px;color:var(--outline);">Individual Support</div></td>
                <td><span class="badge-cat teach">Teaching</span></td>
                <td style="text-align:center;">32.0</td>
                <td><span class="status-dot green"></span><span style="font-size:12px;color:#15803d;">On Track</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- insights column -->
      <div style="display:flex;flex-direction:column;gap:16px;">
        <div class="insight-card">
          <div class="title"><span class="material-symbols-outlined" style="font-variation-settings:'FILL'1;">lightbulb</span> Efficiency Insights</div>
          <div class="item"><p>High Workload Alert</p><p>Peak hours detected in October (140% of baseline). Consider shifting non-critical research prep to September.</p></div>
          <div class="item"><p>Feedback Correlation</p><p>Your student satisfaction scores correlate strongly with practical session availability. Maintain 12+ contact hours.</p></div>
          <button class="btn">Schedule Load Review</button>
        </div>
        <div class="alert-card">
          <span class="material-symbols-outlined icon">warning</span>
          <div><div class="title">Admin Overload</div><div class="desc">Admin tasks have increased by 8% this month. You may qualify for a teaching reduction request.</div></div>
        </div>
      </div>
    </div>

  </main>

  <!-- FAB -->
  <div class="fab">
    <button><span class="material-symbols-outlined">add</span></button>
  </div>

  <script>
    // toggle trend buttons
    document.querySelectorAll('.trend-toggle button').forEach(btn => {
      btn.addEventListener('click', function() {
        document.querySelectorAll('.trend-toggle button').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
      });
    });
    // prevent sidebar nav from reloading
    document.querySelectorAll('.sidebar-nav a').forEach(link => {
      link.addEventListener('click', (e) => { if (link.getAttribute('href') === '#') e.preventDefault(); });
    });
  </script>

</body>
</html>