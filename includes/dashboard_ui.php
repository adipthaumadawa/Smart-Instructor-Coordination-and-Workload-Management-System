<?php
require_once __DIR__ . '/../config/db.php';

/* ─────────────────────────────────────────────────────────────────────────────
   DATABASE HELPERS
   ───────────────────────────────────────────────────────────────────────────── */

if (!function_exists('sic_count')) {
    function sic_count(string $sql): int {
        global $pdo;
        try { 
            return (int)$pdo->query($sql)->fetchColumn(); 
        } catch (Throwable $e) { 
            return 0; 
        }
    }
}

if (!function_exists('sic_scalar')) {
    function sic_scalar(string $sql, array $params = [], $default = 0) {
        global $pdo;
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $value = $stmt->fetchColumn();
            return ($value === false || $value === null) ? $default : $value;
        } catch (Throwable $e) { 
            return $default; 
        }
    }
}

if (!function_exists('sic_current_instructor_id')) {
    function sic_current_instructor_id(): ?int {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) { return null; }
        $id = sic_scalar("SELECT id FROM instructors WHERE user_id = :uid LIMIT 1", [':uid' => $userId], null);
        return $id ? (int)$id : null;
    }
}

if (!function_exists('sic_workload_alert_count')) {
    function sic_workload_alert_count(): int {
        global $pdo;
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM (
                SELECT i.id, i.max_weekly_hours, COALESCE(SUM(ta.duration_hours),0) AS week_hours
                FROM instructors i
                LEFT JOIN task_assignments ta ON ta.instructor_id = i.id
                    AND ta.is_presentation_panel = 0
                    AND ta.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                    AND ta.status IN ('Assigned','Accepted','Completed')
                WHERE i.status = 'active'
                GROUP BY i.id, i.max_weekly_hours
                HAVING week_hours >= (i.max_weekly_hours * 0.8)
            ) x");
            return (int)$stmt->fetchColumn();
        } catch (Throwable $e) { 
            return 0; 
        }
    }
}

if (!function_exists('sic_room_usage_percent')) {
    function sic_room_usage_percent(): string {
        $rooms = (int)sic_scalar("SELECT COUNT(*) FROM lecture_rooms", [], 0);
        if ($rooms <= 0) { return '0%'; }
        $bookings = (int)sic_scalar("SELECT COUNT(*) FROM lecture_hall_bookings WHERE booking_date = CURDATE() AND status IN ('Confirmed','Pending')", [], 0);
        return min(100, (int)round(($bookings / $rooms) * 100)) . '%';
    }
}

/* ─────────────────────────────────────────────────────────────────────────────
   AVATAR & USER CARD RENDERERS
   ───────────────────────────────────────────────────────────────────────────── */

if (!function_exists('sic_user_avatar')) {
    /**
     * Renders an avatar element: displays image if URL provided, else initial letter.
     */
    function sic_user_avatar(?string $imageUrl = null, string $name = 'User', string $class = 'avatar'): string {
        $name = trim($name) ?: 'User';
        $initial = strtoupper(mb_substr($name, 0, 1));
        
        $output = '<span class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">';
        if (!empty($imageUrl)) {
            $output .= '<img src="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" class="avatar-img">';
        } else {
            $output .= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8');
        }
        $output .= '</span>';
        
        return $output;
    }
}

if (!function_exists('sic_render_profile_button')) {
    /**
     * Helper to render the Topbar/Header profile button with dynamic user image support.
     */
    function sic_render_profile_button(?string $imageUrl = null, string $fullName = 'System Administrator', string $roleName = 'System Administrator'): void {
        ?>
        <div class="menu-wrap">
            <button type="button" class="profile-button" data-menu-button="profileMenu" aria-expanded="false">
                <?= sic_user_avatar($imageUrl, $fullName, 'avatar') ?>
                <span class="profile-copy">
                    <span class="profile-name"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="profile-role"><?= htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8') ?></span>
                </span>
                <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>
            <div id="profileMenu" class="dropdown-menu" hidden>
                <a href="<?= app_url('includes/profile.php') ?>" class="dropdown-item">Profile Settings</a>
                <a href="<?= app_url('auth/logout.php') ?>" class="dropdown-item text-danger">Logout</a>
            </div>
        </div>
        <?php
    }
}

/* ─────────────────────────────────────────────────────────────────────────────
   DASHBOARD CARDS & DATA FETCHING
   ───────────────────────────────────────────────────────────────────────────── */

if (!function_exists('sic_dashboard_cards')) {
    function sic_dashboard_cards(string $roleKey): array {
        $roleKey = strtolower($roleKey);
        $uid     = (int)($_SESSION['user_id'] ?? 0);
        $instructorId = sic_current_instructor_id();

        switch ($roleKey) {
            case 'admin':
                return [
                    ['Total Users',          sic_scalar("SELECT COUNT(*) FROM users"),                                          'All system accounts',  'users',     'purple', ''],
                    ['Active Users',         sic_scalar("SELECT COUNT(*) FROM users WHERE status = 'active'"),                  'Enabled accounts',     'user-check','teal',   ''],
                    ['System Roles',         sic_scalar("SELECT COUNT(*) FROM roles"),                                          'Access levels',        'shield',    'blue',   ''],
                    ['Activity Logs Today',  sic_scalar("SELECT COUNT(*) FROM activity_logs WHERE DATE(created_at) = CURDATE()"), 'Audit entries',      'history',   'coral',  ''],
                ];
            case 'instructor':
                return [
                    ["Today's Tasks",       $instructorId ? sic_scalar("SELECT COUNT(*) FROM task_assignments WHERE instructor_id = :iid AND scheduled_date = CURDATE() AND status IN ('Assigned','Accepted')", [':iid'=>$instructorId]) : 0, 'Scheduled for today', 'tasks',  'purple',''],
                    ['Weekly Workload',      ($instructorId ? sic_scalar("SELECT COALESCE(SUM(duration_hours),0) FROM task_assignments WHERE instructor_id = :iid AND is_presentation_panel = 0 AND scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status IN ('Assigned','Accepted','Completed')", [':iid'=>$instructorId]) : 0) . ' hrs', 'Next 7 days', 'chart','blue',''],
                    ['Replacement Requests',$instructorId ? sic_scalar("SELECT COUNT(*) FROM replacement_requests WHERE status = 'Pending' AND (requested_by_instructor_id = :iid OR suggested_instructor_id = :iid)", [':iid'=>$instructorId]) : 0, 'Waiting response', 'swap','coral','danger'],
                    ['Notifications',        sic_scalar("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0", [':uid'=>$uid]), 'Unread alerts', 'bell','teal',''],
                ];
            case 'coordinator':
                return [
                    ['Available Instructors', sic_scalar("SELECT COUNT(*) FROM instructors WHERE status = 'active'"), 'Ready for allocation', 'group','teal',''],
                    ['Pending Task Requests', sic_scalar("SELECT COUNT(*) FROM additional_task_requests WHERE status = 'Pending'"), 'Need assignment', 'clipboard','purple',''],
                    ['Urgent Replacements',   sic_scalar("SELECT COUNT(*) FROM replacement_requests WHERE status = 'Pending'") + sic_scalar("SELECT COUNT(*) FROM additional_task_requests WHERE urgency = 'Urgent' AND status = 'Pending'"), 'Requires action', 'warning','coral','danger'],
                    ['Total Workload Hours',  sic_scalar("SELECT COALESCE(SUM(duration_hours),0) FROM task_assignments WHERE is_presentation_panel = 0 AND scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status IN ('Assigned','Accepted','Completed')") . ' hrs', 'This week', 'clock','blue',''],
                ];
            case 'chief':
                return [
                    ['Total Instructors',   sic_scalar("SELECT COUNT(*) FROM instructors"), 'Registered instructors', 'instructor','purple',''],
                    ['Active Allocations',  sic_scalar("SELECT COUNT(*) FROM task_assignments WHERE status IN ('Assigned','Accepted')"), 'Current assignments', 'project','blue',''],
                    ['Pending Replacements',sic_scalar("SELECT COUNT(*) FROM replacement_requests WHERE status = 'Pending'"), 'Needs monitoring', 'swap','coral','danger'],
                    ['Workload Alerts',     sic_workload_alert_count(), 'High workload risk', 'gauge','teal',''],
                ];
            case 'non_academic':
                return [
                    ["Timetable Records Today", sic_scalar("SELECT COUNT(*) FROM timetable_slots WHERE day_of_week = DAYNAME(CURDATE())"), 'Official timetable', 'calendar','blue',''],
                    ['Room Bookings Today',      sic_scalar("SELECT COUNT(*) FROM lecture_hall_bookings WHERE booking_date = CURDATE() AND status IN ('Confirmed','Pending')"), 'Lecture halls/labs', 'building','purple',''],
                    ['Pending Attendance',      0, 'Attendance module', 'user-clock','coral',''],
                    ['Leave Notifications',      sic_scalar("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0 AND type = 'leave'", [':uid'=>$uid]), 'Unread leave alerts', 'bell','teal',''],
                ];
            case 'project':
                return [
                    ['Presentation Sessions', sic_scalar("SELECT COUNT(*) FROM presentation_sessions WHERE status = 'Scheduled'"), 'Scheduled sessions', 'display','purple',''],
                    ['Pending Panels',         sic_scalar("SELECT COUNT(*) FROM presentation_sessions ps WHERE ps.status = 'Scheduled' AND NOT EXISTS (SELECT 1 FROM presentation_panel_members ppm WHERE ppm.presentation_session_id = ps.id)"), 'Need panel members', 'users-gear','coral','danger'],
                    ['Available Instructors',  sic_scalar("SELECT COUNT(*) FROM instructors WHERE status = 'active'"), 'For panel selection', 'user-check','teal',''],
                    ['Booked Venues',          sic_scalar("SELECT COUNT(DISTINCT venue) FROM presentation_sessions WHERE status = 'Scheduled' AND venue IS NOT NULL AND venue <> ''"), 'Presentation venues', 'location','blue',''],
                ];
            case 'director':
                return [
                    ['Active Tasks',            sic_scalar("SELECT COUNT(*) FROM task_assignments WHERE status IN ('Assigned','Accepted')"), 'Operational view only', 'tasks','purple',''],
                    ['Instructors On Leave',    sic_scalar("SELECT COUNT(DISTINCT instructor_id) FROM leave_records WHERE status = 'Approved' AND CURDATE() BETWEEN start_date AND end_date"), 'Currently unavailable', 'user-minus','blue',''],
                    ['Pending Replacements',    sic_scalar("SELECT COUNT(*) FROM replacement_requests WHERE status = 'Pending'"), 'Monitoring only', 'swap','coral','danger'],
                    ['Room Usage Today',        sic_room_usage_percent(), 'Lecture room usage', 'pie','teal',''],
                ];
        }
        return [];
    }
}

if (!function_exists('sic_recent_tasks')) {
    function sic_recent_tasks(): array {
        global $pdo;
        try {
            return $pdo->query("
                SELECT ta.*, i.employee_id, u.full_name, u.avatar_url, tt.name AS type_name,
                       COALESCE(atr.title, tt.name, 'Academic Task') AS task_title
                FROM task_assignments ta
                LEFT JOIN instructors i ON ta.instructor_id = i.id
                LEFT JOIN users u ON i.user_id = u.id
                LEFT JOIN task_types tt ON ta.task_type_id = tt.id
                LEFT JOIN additional_task_requests atr ON ta.additional_task_request_id = atr.id
                ORDER BY ta.created_at DESC LIMIT 6
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch(Throwable $e) { return []; }
    }
}

if (!function_exists('sic_icon')) {
    function sic_icon(string $name, string $class = 'svg-icon'): string {
        $iconMap = [
            'users'=>'users','user-check'=>'user-check','shield'=>'shield','history'=>'history',
            'tasks'=>'briefcase-business','chart'=>'chart-column','swap'=>'history','bell'=>'bell',
            'group'=>'users','clipboard'=>'book-open','warning'=>'triangle-alert','clock'=>'clock',
            'instructor'=>'graduation-cap','project'=>'briefcase-business','gauge'=>'chart-column',
            'calendar'=>'calendar','building'=>'building-2','user-clock'=>'clock',
            'display'=>'chart-column','users-gear'=>'settings','location'=>'building-2',
            'user-minus'=>'user-minus','pie'=>'chart-column','bolt'=>'plus','search'=>'search',
            'plus'=>'plus','eye'=>'eye','edit'=>'square-pen','delete'=>'trash-2',
            'settings'=>'settings','profile'=>'circle-user-round','logout'=>'log-out',
            'course'=>'book-open','arrow-up'=>'trending-up','arrow-right'=>'arrow-right',
            'check'=>'check-circle','alert'=>'alert-circle',
        ];
        $fileName = $iconMap[$name] ?? $name;
        if (!preg_match('/^[a-z0-9-]+$/', $fileName)) { return ''; }
        $candidatePaths = [
            __DIR__ . '/../assets/icons/' . $fileName . '.svg',
            __DIR__ . '/../../assets/icons/' . $fileName . '.svg',
            __DIR__ . '/icons/' . $fileName . '.svg',
        ];
        $file = null;
        foreach ($candidatePaths as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) { $file = $candidate; break; }
        }
        if ($file === null) { return ''; }
        $svg = file_get_contents($file);
        if ($svg === false) { return ''; }
        $safeClass = trim(preg_replace('/[^a-zA-Z0-9 _-]/', '', $class));
        $classAttr = $safeClass !== '' ? ' class="' . htmlspecialchars($safeClass, ENT_QUOTES, 'UTF-8') . '"' : '';
        $svg = preg_replace('/<svg\b([^>]*)>/i', '<svg$1' . $classAttr . ' aria-hidden="true" focusable="false">', $svg, 1);
        return '<span class="sic-icon" aria-hidden="true">' . $svg . '</span>';
    }
}

/* ─────────────────────────────────────────────────────────────────────────────
   INLINE DASHBOARD & PROFILE CARD STYLES
   ───────────────────────────────────────────────────────────────────────────── */

if (!function_exists('sic_dashboard_styles')) {
    function sic_dashboard_styles(): void {
        static $printed = false;
        if ($printed) { return; }
        $printed = true;
        ?>
<style>
/* ── Dashboard design tokens ── */
:root {
    --d-navy:   #071a33;
    --d-navy2:  #0d2a50;
    --d-teal:   #00939e;
    --d-teal2:  #00b3c0;
    --d-blue:   #3b82f6;
    --d-purple: #7c5fe6;
    --d-coral:  #ef5350;
    --d-green:  #22c55e;
    --d-amber:  #f59e0b;
    --d-page:   #f1f5f9;
    --d-card:   #ffffff;
    --d-line:   #e2e8f0;
    --d-text:   #0f172a;
    --d-muted:  #64748b;
    --d-radius: 14px;
    --d-shadow: 0 1px 3px rgba(15,23,42,.06), 0 4px 16px rgba(15,23,42,.06);
    --d-shadow-hover: 0 4px 8px rgba(15,23,42,.08), 0 16px 32px rgba(15,23,42,.1);
}

/* ── Avatar & Profile Button Enhancements ── */
.avatar {
    width: 38px;
    height: 38px;
    flex: 0 0 38px;
    display: grid;
    place-items: center;
    border-radius: 11px;
    background: linear-gradient(145deg, var(--d-teal2), var(--d-teal));
    color: #ffffff;
    font-weight: 800;
    font-size: 15px;
    overflow: hidden;
    position: relative;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.profile-button {
    display: flex;
    align-items: center;
    gap: 10px;
    background: transparent;
    border: none;
    padding: 4px 8px;
    border-radius: 12px;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.profile-button:hover {
    background-color: rgba(15, 23, 42, 0.04);
}

.profile-button:hover .avatar {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
}

.profile-copy {
    display: flex;
    flex-direction: column;
    text-align: left;
}

.profile-name {
    font-size: 13.5px;
    font-weight: 800;
    color: var(--d-text);
    line-height: 1.2;
}

.profile-role {
    font-size: 11.5px;
    color: var(--d-muted);
    font-weight: 500;
}

.chevron {
    width: 16px;
    height: 16px;
    color: var(--d-muted);
    transition: transform 0.2s ease;
}

.profile-button[aria-expanded="true"] .chevron {
    transform: rotate(180deg);
}

/* ── Page wrapper ── */
.dash-page { padding: 24px; min-height: 100%; }

/* ── Hero / page header ── */
.dash-hero {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 24px;
}
.dash-title-wrap h1 {
    margin: 0 0 5px;
    font-size: 26px;
    font-weight: 800;
    letter-spacing: -.04em;
    color: var(--d-text);
    line-height: 1.15;
}
.dash-title-wrap p {
    margin: 0;
    color: var(--d-muted);
    font-size: 13.5px;
}
.dash-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
    flex-shrink: 0;
}
.date-chip {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border: 1.5px solid var(--d-line);
    border-radius: 10px;
    padding: 8px 13px;
    background: var(--d-card);
    font-size: 12.5px;
    font-weight: 600;
    color: var(--d-muted);
    white-space: nowrap;
}
.date-chip .sic-icon svg { color: var(--d-teal); }
.dash-primary-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--d-navy);
    color: #fff;
    border-radius: 11px;
    padding: 9px 18px;
    font-weight: 700;
    font-size: 13px;
    transition: transform .18s, box-shadow .18s;
    box-shadow: 0 6px 20px rgba(7,26,51,.2);
    white-space: nowrap;
}
.dash-primary-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 28px rgba(7,26,51,.28);
}
.dash-primary-action .sic-icon svg { width: 17px; height: 17px; }

/* ── KPI Grid ── */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}
.kpi-card {
    background: var(--d-card);
    border: 1px solid var(--d-line);
    border-radius: var(--d-radius);
    box-shadow: var(--d-shadow);
    transition: transform .2s ease, box-shadow .2s ease;
    display: flex;
    flex-direction: column;
}
.kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--d-shadow-hover);
}
.kpi-strip {
    height: 4px;
    border-radius: var(--d-radius) var(--d-radius) 0 0;
    flex-shrink: 0;
}
.kpi-strip.teal   { background: linear-gradient(90deg, var(--d-teal2), var(--d-teal)); }
.kpi-strip.blue   { background: linear-gradient(90deg, #60a5fa, var(--d-blue)); }
.kpi-strip.purple { background: linear-gradient(90deg, #a78bfa, var(--d-purple)); }
.kpi-strip.coral  { background: linear-gradient(90deg, #fb7185, var(--d-coral)); }
.kpi-strip.amber  { background: linear-gradient(90deg, #fbbf24, var(--d-amber)); }

.kpi-inner { padding: 18px 20px 20px; flex: 1; }
.kpi-row-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
}
.kpi-icon {
    width: 46px; height: 46px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
}
.kpi-icon.teal   { background: #ccf2f4; color: var(--d-teal); }
.kpi-icon.blue   { background: #dbeafe; color: var(--d-blue); }
.kpi-icon.purple { background: #ede9fe; color: var(--d-purple); }
.kpi-icon.coral  { background: #fee2e2; color: var(--d-coral); }
.kpi-icon.amber  { background: #fef3c7; color: var(--d-amber); }
.kpi-icon .sic-icon svg { width: 22px; height: 22px; }

.kpi-trend {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 9px;
    border-radius: 20px;
    background: #f0fdf4;
    color: #15803d;
    flex-shrink: 0;
    white-space: nowrap;
}
.kpi-trend.danger { background: #fff1f2; color: #be123c; }

.kpi-label {
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--d-muted);
    margin-bottom: 6px;
}
.kpi-number {
    font-size: 36px;
    font-weight: 900;
    line-height: 1.05;
    color: var(--d-text);
    letter-spacing: -.04em;
    margin-bottom: 5px;
    display: block;
}
.kpi-note {
    font-size: 12px;
    color: var(--d-muted);
    font-weight: 500;
}
.kpi-note.danger { color: var(--d-coral); font-weight: 700; }

.kpi-spark {
    padding: 0 20px 14px;
    height: 44px;
}
.kpi-spark svg {
    width: 100%;
    height: 100%;
    display: block;
    overflow: visible;
}

/* ── Section header ── */
.section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid var(--d-line);
    flex-shrink: 0;
}
.section-title {
    display: flex;
    align-items: center;
    gap: 9px;
    font-size: 14.5px;
    font-weight: 700;
    margin: 0;
    color: var(--d-text);
}
.section-title .sic-icon svg { width: 18px; height: 18px; color: var(--d-teal); }
.section-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 9px;
    background: #f0fdfa;
    color: var(--d-teal);
    border: 1px solid #a7f3d0;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}
.section-badge.live {
    background: #fef2f2;
    color: #dc2626;
    border-color: #fecaca;
    animation: live-pulse 2s infinite;
}
@keyframes live-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: .7; }
}
.section-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: var(--d-teal);
    font-size: 12.5px;
    font-weight: 700;
    transition: gap .15s;
}
.section-link:hover { gap: 8px; }
.section-link .sic-icon svg { width: 14px; height: 14px; }

/* ── D-Card base ── */
.d-card {
    background: var(--d-card);
    border: 1px solid var(--d-line);
    border-radius: var(--d-radius);
    box-shadow: var(--d-shadow);
    overflow: hidden;
    transition: transform .2s ease, box-shadow .2s ease;
}
.d-card:hover { transform: translateY(-2px); box-shadow: var(--d-shadow-hover); }

/* ── Dashboard main grids ── */
.dash-grid-row1 {
    display: grid;
    grid-template-columns: 2fr 1.1fr 1.3fr;
    gap: 16px;
    margin-bottom: 16px;
    align-items: start;
}

.chart-area { padding: 20px 20px 12px; }
.chart-bars {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 8px;
    height: 160px;
    position: relative;
    background-image:
        linear-gradient(to right, var(--d-line) 1px, transparent 1px),
        linear-gradient(to top, var(--d-line) 1px, transparent 1px);
    background-size: 100% 25%, 100% 25%;
    background-position: 0 100%, 0 0;
}
.bar-col {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    height: 100%;
    justify-content: flex-end;
}
.bar-wrap {
    width: 100%;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    flex: 1;
}
.chart-bar {
    width: clamp(24px, 70%, 36px);
    border-radius: 6px 6px 2px 2px;
    background: linear-gradient(180deg, var(--d-teal2) 0%, var(--d-teal) 100%);
    transition: opacity .2s;
    min-height: 6px;
}
.bar-col.today .chart-bar {
    background: linear-gradient(180deg, #60a5fa 0%, var(--d-blue) 100%);
    box-shadow: 0 4px 14px rgba(59,130,246,.35);
}
.bar-col:hover .chart-bar { opacity: .8; }
.bar-label {
    font-size: 11px;
    color: var(--d-muted);
    font-weight: 600;
    white-space: nowrap;
}
.bar-col.today .bar-label { color: var(--d-blue); font-weight: 700; }
.chart-legend {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid var(--d-line);
    font-size: 12px;
}
.legend-dot {
    display: inline-block;
    width: 10px; height: 10px;
    border-radius: 50%;
    margin-right: 5px;
    vertical-align: middle;
}
.chart-summary {
    margin-left: auto;
    font-size: 12px;
    font-weight: 600;
    color: var(--d-muted);
}
.chart-summary strong { color: var(--d-text); }

/* ── Donut / availability ── */
.avail-body { padding: 16px 18px; }
.donut-wrap {
    display: flex;
    justify-content: center;
    margin-bottom: 16px;
    position: relative;
}
.donut-wrap svg { width: 140px; height: 140px; }
.donut-center {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    line-height: 1.2;
}
.donut-center strong { font-size: 26px; font-weight: 900; color: var(--d-text); display: block; }
.donut-center small  { font-size: 10px; color: var(--d-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
.avail-legend-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 7px 0;
    border-bottom: 1px solid var(--d-line);
    font-size: 12.5px;
}
.avail-legend-item:last-child { border-bottom: 0; }
.avail-legend-name { display: flex; align-items: center; gap: 8px; }
.leg-dot {
    width: 10px; height: 10px;
    border-radius: 3px;
    display: inline-block;
    flex-shrink: 0;
}
.avail-legend-item strong { font-size: 13px; font-weight: 700; }
.avail-pct-bar {
    width: 48px; height: 4px;
    border-radius: 9px;
    background: var(--d-line);
    overflow: hidden;
    margin-left: 8px;
}
.avail-pct-fill { height: 100%; border-radius: 9px; }

/* ── Schedule card ── */
.schedule-list { padding: 0; }
.sched-item {
    display: grid;
    grid-template-columns: 54px 1fr auto;
    gap: 10px;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid var(--d-line);
    transition: background .15s;
}
.sched-item:last-child { border-bottom: 0; }
.sched-item:hover { background: #f8fafc; }
.sched-time { text-align: center; }
.sched-time-start {
    font-size: 13px;
    font-weight: 800;
    color: var(--d-text);
    display: block;
}
.sched-time-end {
    font-size: 10px;
    color: var(--d-muted);
    display: block;
    margin-top: 1px;
}
.sched-course {
    font-size: 13px;
    font-weight: 700;
    color: var(--d-text);
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.sched-meta {
    font-size: 11.5px;
    color: var(--d-muted);
    display: block;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ── Status Pills ── */
.s-pill {
    display: inline-flex;
    align-items: center;
    border-radius: 20px;
    padding: 4px 10px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
    letter-spacing: .01em;
}
.s-pill-green  { background: #dcfce7; color: #15803d; }
.s-pill-blue   { background: #dbeafe; color: #1d4ed8; }
.s-pill-purple { background: #f3e8ff; color: #7e22ce; }
.s-pill-orange { background: #fef3c7; color: #b45309; }
.s-pill-red    { background: #fee2e2; color: #b91c1c; }
.s-pill-teal   { background: #ccfbf1; color: #0f766e; }

/* ── Lower 3-column grid ── */
.dash-grid-row2 {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 16px;
}

/* ── Leave requests ── */
.leave-list { padding: 6px 0; }
.leave-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 16px;
    border-bottom: 1px solid var(--d-line);
    transition: background .15s;
}
.leave-item:last-child { border-bottom: 0; }
.leave-item:hover { background: #f8fafc; }
.lv-avatar {
    width: 36px; height: 36px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    font-size: 13px;
    font-weight: 800;
    color: #fff;
    flex-shrink: 0;
    overflow: hidden;
}
.lv-avatar img { width: 100%; height: 100%; object-fit: cover; }
.lv-info { flex: 1; min-width: 0; }
.lv-name {
    font-size: 13px;
    font-weight: 700;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: var(--d-text);
}
.lv-date {
    font-size: 11.5px;
    color: var(--d-muted);
    display: block;
    margin-top: 1px;
}
.lv-badges {
    display: flex;
    flex-direction: column;
    gap: 4px;
    align-items: flex-end;
    flex-shrink: 0;
}

/* ── Replacement alerts ── */
.alert-list { padding: 6px 0; }
.alert-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--d-line);
    transition: background .15s;
}
.alert-item:last-child { border-bottom: 0; }
.alert-item:hover { background: #fff9f9; }
.alert-icon-wrap {
    width: 36px; height: 36px;
    background: #fee2e2;
    border-radius: 10px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
}
.alert-icon-wrap .sic-icon svg { width: 18px; height: 18px; color: var(--d-coral); }
.alert-info { flex: 1; min-width: 0; }
.alert-course {
    font-size: 13px;
    font-weight: 700;
    display: block;
    color: var(--d-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.alert-meta {
    font-size: 11.5px;
    color: var(--d-muted);
    display: block;
    margin-top: 2px;
}

/* ── Venue bookings ── */
.venue-list { padding: 6px 0; }
.venue-item {
    padding: 12px 16px;
    border-bottom: 1px solid var(--d-line);
    transition: background .15s;
}
.venue-item:last-child { border-bottom: 0; }
.venue-item:hover { background: #f8fafc; }
.venue-row1 {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 7px;
}
.venue-name { font-size: 13px; font-weight: 700; color: var(--d-text); }
.venue-pct  { font-size: 12px; font-weight: 800; color: var(--d-text); }
.venue-row2 {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.venue-bar-wrap {
    flex: 1;
    height: 6px;
    background: var(--d-line);
    border-radius: 99px;
    overflow: hidden;
}
.venue-bar-fill {
    height: 100%;
    border-radius: 99px;
    background: linear-gradient(90deg, var(--d-teal2), var(--d-teal));
    transition: width .6s ease;
}
.venue-bar-fill.high { background: linear-gradient(90deg, #fb923c, #ef4444); }
.venue-sessions { font-size: 11.5px; color: var(--d-muted); font-weight: 600; white-space: nowrap; }

/* ── Activity table ── */
.act-table-wrap { overflow-x: auto; }
.act-table {
    width: 100%;
    border-collapse: collapse;
}
.act-table thead th {
    padding: 10px 14px;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--d-muted);
    background: #f8fafc;
    border-bottom: 1px solid var(--d-line);
    white-space: nowrap;
    text-align: left;
}
.act-table tbody td {
    padding: 13px 14px;
    font-size: 13px;
    border-bottom: 1px solid var(--d-line);
    color: var(--d-text);
    vertical-align: middle;
}
.act-table tbody tr:last-child td { border-bottom: 0; }
.act-table tbody tr:hover td { background: #f8fafc; }
.act-assignee {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.act-avatar {
    width: 30px; height: 30px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    font-size: 12px;
    font-weight: 800;
    color: #fff;
    flex-shrink: 0;
    background: linear-gradient(135deg, var(--d-teal2), var(--d-teal));
    overflow: hidden;
}
.act-avatar img { width: 100%; height: 100%; object-fit: cover; }
.act-name { font-size: 13px; font-weight: 600; }

/* ── Generic icons inline ── */
.sic-icon { display: inline-flex; align-items: center; line-height: 1; }
.svg-icon, .sic-icon svg { width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 1.9; stroke-linecap: round; stroke-linejoin: round; display: block; }

/* ── Responsive ── */
@media (max-width: 1150px) {
    .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    .dash-grid-row1 { grid-template-columns: 1fr 1fr; }
    .dash-grid-row1 > *:last-child { grid-column: 1 / -1; }
}
@media (max-width: 900px) {
    .dash-grid-row2 { grid-template-columns: 1fr 1fr; }
    .dash-grid-row2 > *:last-child { grid-column: 1 / -1; }
}
@media (max-width: 680px) {
    .dash-page { padding: 14px; }
    .dash-hero { flex-direction: column; align-items: flex-start; }
    .kpi-grid, .dash-grid-row1, .dash-grid-row2 { grid-template-columns: 1fr; }
    .dash-grid-row1 > *:last-child, .dash-grid-row2 > *:last-child { grid-column: auto; }
    .act-table thead { display: none; }
    .act-table, .act-table tbody, .act-table tr, .act-table td { display: block; width: 100%; }
    .act-table td { padding: 8px 14px; border: 0; }
    .act-table td::before { content: attr(data-label); display: block; font-size: 10px; color: var(--d-muted); font-weight: 800; text-transform: uppercase; margin-bottom: 3px; }
    .act-table tr { border-bottom: 1px solid var(--d-line); }
}
</style>
        <?php
    }
}

/* ─────────────────────────────────────────────────────────────────────────────
   MAIN RENDER FUNCTION
   ───────────────────────────────────────────────────────────────────────────── */

function sic_render_dashboard(string $heading, string $subtitle, array $cards = [], string $primaryActionUrl = '', string $primaryActionText = 'Quick Action') {
    sic_dashboard_styles();

    $activeInstructors    = sic_count("SELECT COUNT(*) FROM instructors WHERE status='active'");
    $todayClasses         = sic_count("SELECT COUNT(*) FROM timetable_slots WHERE day_of_week = DAYNAME(CURDATE())");
    $pendingReplacements  = sic_count("SELECT COUNT(*) FROM replacement_requests WHERE status='Pending'");
    $weeklyHours          = sic_count("SELECT COALESCE(SUM(duration_hours),0) FROM task_assignments WHERE scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND is_presentation_panel=0");

    if (empty($cards)) {
        $cards = [
            ['Active Instructors',   $activeInstructors,   '+5 this week',         'users',   'purple', ''],
            ["Today's Classes",      $todayClasses,         'Across lecture halls', 'calendar','blue',   ''],
            ['Pending Replacements', $pendingReplacements,  'Urgent attention',     'swap',    'coral',  'danger'],
            ['Weekly Workload Hrs',  $weeklyHours,          'Avg. hrs / instructor','clock',   'teal',   ''],
        ];
    }

    $tasks     = sic_recent_tasks();
    $weekStart = date('M d');
    $weekEnd   = date('M d, Y', strtotime('+6 days'));

    /* Bar chart data */
    $barData   = [
        ['Mon', 55, false], ['Tue', 76, false], ['Wed', 62, false],
        ['Thu', 42, false],
        ['Fri', 50, date('N') == 5],
        ['Sat', 70, false], ['Sun', 52, false],
    ];
    $todayIdx   = (int)date('N') - 1;
    foreach ($barData as $bi => &$bd) { $bd[2] = ($bi === $todayIdx); }
    unset($bd);
    $maxBar    = max(array_column($barData, 1));

    /* Donut SVG circle math */
    $donutR    = 52;
    $donutCirc = 2 * M_PI * $donutR;
    $segs      = [
        ['Available', 42, '#00b3c0'],
        ['Partial',   36, '#3b82f6'],
        ['Busy',      16, '#7c5fe6'],
        ['On Leave',   6, '#cbd5e1'],
    ];

    /* Avatar colours for default initial items */
    $avatarColors = ['#00939e','#3b82f6','#7c5fe6','#f59e0b','#ef5350','#22c55e'];
?>
<!-- ═══════════════════════════════════════════════════════
     DASHBOARD PAGE
═══════════════════════════════════════════════════════ -->
<section class="dash-page">

    <!-- Hero header -->
    <div class="dash-hero">
        <div class="dash-title-wrap">
            <h1><?= htmlspecialchars($heading) ?></h1>
            <p><?= htmlspecialchars($subtitle) ?></p>
        </div>
        <div class="dash-actions">
            <span class="date-chip"><?= sic_icon('calendar') ?><?= htmlspecialchars($weekStart) ?> – <?= htmlspecialchars($weekEnd) ?></span>
            <?php if ($primaryActionUrl): ?>
            <a class="dash-primary-action" href="<?= htmlspecialchars($primaryActionUrl) ?>">
                <?= sic_icon('plus') ?><?= htmlspecialchars($primaryActionText) ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── KPI Cards ── -->
    <div class="kpi-grid">
        <?php
        $accentMap   = ['teal'=>'teal','blue'=>'blue','purple'=>'purple','coral'=>'coral','amber'=>'amber'];
        $sparkPoints = [
            'teal'  => '0,28 16,18 32,24 48,14 64,20 80,10 100,16',
            'blue'  => '0,22 16,30 32,16 48,24 64,12 80,20 100,14',
            'purple'=> '0,30 16,20 32,26 48,16 64,22 80,12 100,18',
            'coral' => '0,24 16,32 32,20 48,28 64,16 80,24 100,18',
        ];
        $sparkStroke = ['teal'=>'#00b3c0','blue'=>'#3b82f6','purple'=>'#7c5fe6','coral'=>'#ef5350'];

        foreach ($cards as $idx => $c):
            $accent   = $accentMap[$c[4]] ?? 'teal';
            $isDanger = ($c[5] ?? '') === 'danger';
            $sPoints  = $sparkPoints[$c[4]] ?? $sparkPoints['teal'];
            $sColor   = $sparkStroke[$c[4]] ?? $sparkStroke['teal'];
        ?>
        <div class="kpi-card">
            <div class="kpi-strip <?= htmlspecialchars($accent) ?>"></div>
            <div class="kpi-inner">
                <div class="kpi-row-top">
                    <div class="kpi-icon <?= htmlspecialchars($c[4]) ?>"><?= sic_icon((string)$c[3]) ?></div>
                    <span class="kpi-trend <?= $isDanger ? 'danger' : '' ?>"><?= $isDanger ? '⚠ Alert' : '● Live' ?></span>
                </div>
                <div class="kpi-label"><?= htmlspecialchars($c[0]) ?></div>
                <span class="kpi-number"><?= htmlspecialchars((string)$c[1]) ?></span>
                <div class="kpi-note <?= $isDanger ? 'danger' : '' ?>"><?= htmlspecialchars($c[2]) ?></div>
            </div>
            <div class="kpi-spark">
                <svg viewBox="0 0 100 40" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                    <polyline points="<?= htmlspecialchars($sPoints) ?>"
                              fill="none"
                              stroke="<?= htmlspecialchars($sColor) ?>"
                              stroke-width="2"
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              opacity="0.5"/>
                </svg>
            </div>
        </div>
        <?php endforeach; ?>
    </div><!-- /.kpi-grid -->

    <!-- ── Row 1: Chart | Availability | Schedule ── -->
    <div class="dash-grid-row1">

        <!-- Workload Chart -->
        <div class="d-card">
            <div class="section-head">
                <h2 class="section-title"><?= sic_icon('chart') ?>Workload Overview <span style="font-weight:400;color:var(--d-muted);font-size:13px;">(Hours)</span></h2>
                <span class="section-badge">This Week</span>
            </div>
            <div class="chart-area">
                <div class="chart-bars">
                    <?php foreach ($barData as [$dayLabel, $barH, $isToday]): ?>
                    <div class="bar-col <?= $isToday ? 'today' : '' ?>">
                        <div class="bar-wrap">
                            <div class="chart-bar" style="height:<?= round(($barH / $maxBar) * 100) ?>%;" title="<?= $barH ?> hrs"></div>
                        </div>
                        <span class="bar-label"><?= htmlspecialchars($dayLabel) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="chart-legend">
                    <span><span class="legend-dot" style="background:var(--d-teal)"></span>Regular</span>
                    <span><span class="legend-dot" style="background:var(--d-blue)"></span>Today</span>
                    <div class="chart-summary">Total this week: <strong><?= $weeklyHours ?> hrs</strong></div>
                </div>
            </div>
        </div>

        <!-- Instructor Availability Donut -->
        <div class="d-card">
            <div class="section-head">
                <h2 class="section-title"><?= sic_icon('users') ?>Availability</h2>
                <span class="section-badge live">● Live</span>
            </div>
            <div class="avail-body">
                <div class="donut-wrap">
                    <svg viewBox="0 0 140 140" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="70" cy="70" r="<?= $donutR ?>" fill="none" stroke="#e2e8f0" stroke-width="18"/>
                        <?php
                        $cumulative = 0;
                        foreach ($segs as $seg):
                            $dashLen  = ($seg[1] / 100) * $donutCirc;
                            $gapLen   = $donutCirc - $dashLen;
                            $rotation = -90 + ($cumulative / 100) * 360;
                            $cumulative += $seg[1];
                        ?>
                        <circle cx="70" cy="70" r="<?= $donutR ?>" fill="none"
                                stroke="<?= htmlspecialchars($seg[2]) ?>"
                                stroke-width="18"
                                stroke-dasharray="<?= round($dashLen, 2) ?> <?= round($gapLen, 2) ?>"
                                stroke-linecap="butt"
                                transform="rotate(<?= round($rotation, 2) ?> 70 70)"/>
                        <?php endforeach; ?>
                        <circle cx="70" cy="70" r="42" fill="white"/>
                    </svg>
                    <div class="donut-center">
                        <strong><?= $activeInstructors ?: 100 ?></strong>
                        <small>Total</small>
                    </div>
                </div>
                <?php foreach ($segs as $seg): ?>
                <div class="avail-legend-item">
                    <div class="avail-legend-name">
                        <span class="leg-dot" style="background:<?= htmlspecialchars($seg[2]) ?>"></span>
                        <?= htmlspecialchars($seg[0]) ?>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div class="avail-pct-bar">
                            <div class="avail-pct-fill" style="width:<?= $seg[1] ?>%;background:<?= htmlspecialchars($seg[2]) ?>"></div>
                        </div>
                        <strong><?= $seg[1] ?>%</strong>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Upcoming Schedule -->
        <div class="d-card">
            <div class="section-head">
                <h2 class="section-title"><?= sic_icon('calendar') ?>Upcoming Schedule</h2>
                <a href="<?= app_url('system/unavailable.php') ?>" class="section-link"><?= sic_icon('eye') ?>View full</a>
            </div>
            <div class="schedule-list">
                <?php
                $sched = [
                    ['09:00','10:30','CO3210 – Database Systems',   'Dr. N. De Silva • LT-3','Lecture','s-pill-green'],
                    ['11:00','12:30','CO2220 – Data Structures',    'Dr. R. Fernando • LT-2','Tutorial','s-pill-blue'],
                    ['14:00','15:30','CO4230 – Software Eng.',      'Dr. A. Perera • LT-1',  'Lecture','s-pill-purple'],
                    ['16:00','17:30','CO5310 – AI & Applications',  'Dr. K. Jayawardena • LT-4','Lecture','s-pill-orange'],
                ];
                foreach ($sched as $s): ?>
                <div class="sched-item">
                    <div class="sched-time">
                        <span class="sched-time-start"><?= $s[0] ?></span>
                        <span class="sched-time-end"><?= $s[1] ?></span>
                    </div>
                    <div class="sched-info">
                        <span class="sched-course"><?= htmlspecialchars($s[2]) ?></span>
                        <span class="sched-meta"><?= htmlspecialchars($s[3]) ?></span>
                    </div>
                    <span class="s-pill <?= $s[5] ?>"><?= $s[4] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ── Row 2: Leave | Alerts | Venues ── -->
    <div class="dash-grid-row2">

        <!-- Recent Leave Requests -->
        <div class="d-card">
            <div class="section-head">
                <h2 class="section-title"><?= sic_icon('user-check') ?>Leave Requests</h2>
                <a href="<?= app_url('system/unavailable.php') ?>" class="section-link"><?= sic_icon('eye') ?>View all</a>
            </div>
            <div class="leave-list">
                <?php
                $leaves = [
                    ['C','Dr. Chamila Wijesooriya','May 19 – 21, 2025','Medical Leave','s-pill-blue','Pending','s-pill-orange', null],
                    ['I','Mr. Isuru Madushan',     'May 16, 2025 (1 day)','Casual Leave','s-pill-teal','Pending','s-pill-orange', null],
                    ['H','Dr. Harini Silva',        'May 23 – 24, 2025','Medical Leave','s-pill-blue','Approved','s-pill-green', null],
                    ['S','Mr. Sachintha Perera',    'May 15, 2025 (1 day)','Casual Leave','s-pill-teal','Declined','s-pill-red', null],
                ];
                foreach ($leaves as $li => $l):
                    $avatarBg = $avatarColors[$li % count($avatarColors)];
                    $imgUrl = $l[7] ?? null;
                ?>
                <div class="leave-item">
                    <div class="lv-avatar" style="background:<?= $avatarBg ?>">
                        <?php if ($imgUrl): ?>
                            <img src="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($l[1], ENT_QUOTES, 'UTF-8') ?>">
                        <?php else: ?>
                            <?= htmlspecialchars($l[0]) ?>
                        <?php endif; ?>
                    </div>
                    <div class="lv-info">
                        <span class="lv-name"><?= htmlspecialchars($l[1]) ?></span>
                        <span class="lv-date"><?= htmlspecialchars($l[2]) ?></span>
                    </div>
                    <div class="lv-badges">
                        <span class="s-pill <?= $l[4] ?>"><?= $l[3] ?></span>
                        <span class="s-pill <?= $l[6] ?>"><?= $l[5] ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Urgent Replacement Alerts -->
        <div class="d-card">
            <div class="section-head">
                <h2 class="section-title"><?= sic_icon('warning') ?>Replacement Alerts</h2>
                <a href="<?= app_url('system/unavailable.php') ?>" class="section-link"><?= sic_icon('eye') ?>View all</a>
            </div>
            <div class="alert-list">
                <?php foreach ([
                    ['CO3210 – Database Systems',   'May 14 • 10:30 – 12:00 • LT-3'],
                    ['CO2220 – Data Structures',    'May 15 • 09:00 – 10:30 • LT-2'],
                    ['CO4230 – Software Eng.',      'May 16 • 14:00 – 15:30 • LT-1'],
                ] as $a): ?>
                <div class="alert-item">
                    <div class="alert-icon-wrap"><?= sic_icon('warning') ?></div>
                    <div class="alert-info">
                        <span class="alert-course"><?= htmlspecialchars($a[0]) ?></span>
                        <span class="alert-meta"><?= htmlspecialchars($a[1]) ?></span>
                    </div>
                    <span class="s-pill s-pill-red">Urgent</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Lecture Hall Bookings -->
        <div class="d-card">
            <div class="section-head">
                <h2 class="section-title"><?= sic_icon('building') ?>Hall Bookings</h2>
                <a href="<?= app_url('system/unavailable.php') ?>" class="section-link"><?= sic_icon('eye') ?>View all</a>
            </div>
            <div class="venue-list">
                <?php foreach ([
                    ['LT-1 (120 seats)', '72%', '6 / 8', 72, false],
                    ['LT-2 (80 seats)',  '65%', '5 / 8', 65, false],
                    ['LT-3 (60 seats)',  '83%', '5 / 6', 83, true],
                    ['LT-4 (100 seats)', '40%', '2 / 5', 40, false],
                ] as $b): ?>
                <div class="venue-item">
                    <div class="venue-row1">
                        <span class="venue-name"><?= htmlspecialchars($b[0]) ?></span>
                        <span class="venue-pct"><?= $b[1] ?></span>
                    </div>
                    <div class="venue-row2">
                        <div class="venue-bar-wrap">
                            <div class="venue-bar-fill <?= $b[4] ? 'high' : '' ?>" style="width:<?= $b[3] ?>%"></div>
                        </div>
                        <span class="venue-sessions"><?= $b[2] ?> today</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ── Recent Activity Table ── -->
    <div class="d-card">
        <div class="section-head">
            <h2 class="section-title"><?= sic_icon('history') ?>Recent Task Allocations</h2>
            <a href="<?= app_url('system/unavailable.php') ?>" class="section-link"><?= sic_icon('eye') ?>View all</a>
        </div>
        <div class="act-table-wrap">
            <table class="act-table">
                <thead>
                    <tr>
                        <th>Task / Activity</th>
                        <th>Course / Module</th>
                        <th>Assigned To</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if (empty($tasks)) {
                    $tasks = [
                        ['task_title'=>'Lecture – Week 7',       'type_name'=>'CO3210 – Database Systems',    'full_name'=>'Dr. N. De Silva',  'scheduled_date'=>date('Y-m-d'),             'start_time'=>'09:00','end_time'=>'10:30','status'=>'Completed', 'avatar_url'=>null],
                        ['task_title'=>'Tutorial – Week 6',      'type_name'=>'CO2220 – Data Structures',    'full_name'=>'Mr. I. Madushan',  'scheduled_date'=>date('Y-m-d'),             'start_time'=>'11:00','end_time'=>'12:30','status'=>'Completed', 'avatar_url'=>null],
                        ['task_title'=>'Lecture – Week 7',       'type_name'=>'CO4230 – Software Eng.',      'full_name'=>'Dr. A. Perera',    'scheduled_date'=>date('Y-m-d'),             'start_time'=>'14:00','end_time'=>'15:30','status'=>'In Progress', 'avatar_url'=>null],
                        ['task_title'=>'Marking – Assignment 2', 'type_name'=>'CO2220 – Data Structures',    'full_name'=>'Dr. R. Fernando',  'scheduled_date'=>date('Y-m-d', strtotime('-1 day')), 'start_time'=>'','end_time'=>'','status'=>'Pending', 'avatar_url'=>null],
                        ['task_title'=>'Guest Lecture on AI',    'type_name'=>'CO5310 – AI & Applications',  'full_name'=>'Dr. K. Jayawardena','scheduled_date'=>date('Y-m-d', strtotime('+1 day')), 'start_time'=>'10:00','end_time'=>'12:00','status'=>'Assigned', 'avatar_url'=>null],
                    ];
                }
                $actColors = ['#00939e','#3b82f6','#7c5fe6','#f59e0b','#ef5350','#22c55e'];
                foreach ($tasks as $ti => $t):
                    $status = $t['status'] ?? 'Pending';
                    $cls = $status === 'Completed' ? 's-pill-green'
                         : ($status === 'In Progress' ? 's-pill-blue'
                         : ($status === 'Assigned' ? 's-pill-teal' : 's-pill-orange'));
                    $actBg = $actColors[$ti % count($actColors)];
                    $assigneeName = $t['full_name'] ?? 'Unassigned';
                    $userAvatar   = $t['avatar_url'] ?? null;
                ?>
                <tr>
                    <td data-label="Task"><?= htmlspecialchars($t['task_title'] ?? 'Academic Task') ?></td>
                    <td data-label="Course"><?= htmlspecialchars($t['type_name'] ?? 'Module') ?></td>
                    <td data-label="Assigned To">
                        <div class="act-assignee">
                            <div class="act-avatar" style="background:<?= $actBg ?>">
                                <?php if (!empty($userAvatar)): ?>
                                    <img src="<?= htmlspecialchars($userAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($assigneeName, ENT_QUOTES, 'UTF-8') ?>">
                                <?php else: ?>
                                    <?= strtoupper(substr((string)$assigneeName, 0, 1)) ?>
                                <?php endif; ?>
                            </div>
                            <span class="act-name"><?= htmlspecialchars($assigneeName) ?></span>
                        </div>
                    </td>
                    <td data-label="Date"><?= !empty($t['scheduled_date']) ? date('M d, Y', strtotime($t['scheduled_date'])) : '—' ?></td>
                    <td data-label="Time"><?= htmlspecialchars(trim(($t['start_time'] ?? '') . ' – ' . ($t['end_time'] ?? ''), ' –')) ?: '—' ?></td>
                    <td data-label="Status"><span class="s-pill <?= $cls ?>"><?= htmlspecialchars($status) ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</section>
<?php
}

/* ─────────────────────────────────────────────────────────────────────────────
   INSTRUCTOR-ONLY DASHBOARD
   Separate from sic_render_dashboard() (used by admin/coordinator/director etc.)
   so that instructor-specific privacy rules never leak into the shared layout,
   and vice-versa.
   ───────────────────────────────────────────────────────────────────────────── */
if (!function_exists('sic_render_instructor_dashboard')) {
    function sic_render_instructor_dashboard(string $heading, string $subtitle, array $cards, string $primaryActionUrl, string $primaryActionText) {
        global $pdo;
        sic_dashboard_styles();

        $instructorId = sic_current_instructor_id();
        $weekStart    = date('M d');
        $weekEnd      = date('M d, Y', strtotime('+6 days'));

        /* Upcoming schedule — this instructor's own upcoming tasks only */
        $upcoming = [];
        if ($instructorId) {
            try {
                $stmt = $pdo->prepare("
                    SELECT ta.scheduled_date, ta.start_time, ta.end_time, ta.location, tt.name AS type_name,
                           COALESCE(atr.title, tt.name, 'Academic Task') AS task_title
                    FROM task_assignments ta
                    LEFT JOIN task_types tt ON ta.task_type_id = tt.id
                    LEFT JOIN additional_task_requests atr ON ta.additional_task_request_id = atr.id
                    WHERE ta.instructor_id = :iid AND ta.scheduled_date >= CURDATE() AND ta.status IN ('Assigned','Accepted')
                    ORDER BY ta.scheduled_date ASC, ta.start_time ASC
                    LIMIT 6
                ");
                $stmt->execute([':iid' => $instructorId]);
                $upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $e) { $upcoming = []; }
        }

        /* Weekly task summary — this instructor's own tasks, next 7 days, grouped by type */
        $typeBreakdown = [];
        $weekTotal = 0;
        if ($instructorId) {
            try {
                $stmt = $pdo->prepare("
                    SELECT COALESCE(tt.name,'Other') AS type_name, COUNT(*) AS c
                    FROM task_assignments ta
                    LEFT JOIN task_types tt ON ta.task_type_id = tt.id
                    WHERE ta.instructor_id = :iid
                      AND ta.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 6 DAY)
                      AND ta.status IN ('Assigned','Accepted','Completed')
                    GROUP BY type_name
                    ORDER BY c DESC
                ");
                $stmt->execute([':iid' => $instructorId]);
                $typeBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($typeBreakdown as $row) { $weekTotal += (int)$row['c']; }
            } catch (Throwable $e) { $typeBreakdown = []; }
        }
        $typeColors = ['#00b3c0','#3b82f6','#7c5fe6','#f59e0b','#ef5350','#22c55e','#94a3b8'];

        /* Replacement alerts — ONLY requests this instructor is party to (requester or suggested replacement) */
        $myAlerts = [];
        if ($instructorId) {
            try {
                $stmt = $pdo->prepare("
                    SELECT rr.id, rr.status, rr.created_at, ta.scheduled_date, ta.start_time, ta.end_time,
                           COALESCE(atr.title, tt.name, 'Academic Task') AS task_title,
                           rr.requested_by_instructor_id, rr.suggested_instructor_id
                    FROM replacement_requests rr
                    JOIN task_assignments ta ON rr.task_assignment_id = ta.id
                    LEFT JOIN task_types tt ON ta.task_type_id = tt.id
                    LEFT JOIN additional_task_requests atr ON ta.additional_task_request_id = atr.id
                    WHERE rr.status = 'Pending' AND (rr.requested_by_instructor_id = :iid1 OR rr.suggested_instructor_id = :iid2)
                    ORDER BY rr.created_at DESC
                    LIMIT 5
                ");
                $stmt->execute([':iid1' => $instructorId, ':iid2' => $instructorId]);
                $myAlerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $e) { $myAlerts = []; }
        }

        /* Recent task allocations — this instructor's own tasks ONLY (no other instructor's assignments) */
        $myRecentTasks = [];
        if ($instructorId) {
            try {
                $stmt = $pdo->prepare("
                    SELECT ta.scheduled_date, ta.start_time, ta.end_time, ta.status,
                           COALESCE(atr.title, tt.name, 'Academic Task') AS task_title, tt.name AS type_name
                    FROM task_assignments ta
                    LEFT JOIN task_types tt ON ta.task_type_id = tt.id
                    LEFT JOIN additional_task_requests atr ON ta.additional_task_request_id = atr.id
                    WHERE ta.instructor_id = :iid
                    ORDER BY ta.created_at DESC
                    LIMIT 6
                ");
                $stmt->execute([':iid' => $instructorId]);
                $myRecentTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $e) { $myRecentTasks = []; }
        }
        ?>
<section class="dash-page">

    <!-- Hero header -->
    <div class="dash-hero">
        <div class="dash-title-wrap">
            <h1><?= htmlspecialchars($heading) ?></h1>
            <p><?= htmlspecialchars($subtitle) ?></p>
        </div>
        <div class="dash-actions">
            <span class="date-chip"><?= sic_icon('calendar') ?><?= htmlspecialchars($weekStart) ?> – <?= htmlspecialchars($weekEnd) ?></span>
            <?php if ($primaryActionUrl): ?>
            <a class="dash-primary-action" href="<?= htmlspecialchars($primaryActionUrl) ?>">
                <?= sic_icon('plus') ?><?= htmlspecialchars($primaryActionText) ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Upcoming Schedule — shown first, this is what an instructor needs to see immediately ── -->
    <div class="d-card">
        <div class="section-head">
            <h2 class="section-title"><?= sic_icon('calendar') ?>Your Upcoming Schedule</h2>
            <a href="<?= app_url('instructor/timetable.php') ?>" class="section-link"><?= sic_icon('eye') ?>View full timetable</a>
        </div>
        <div class="schedule-list">
            <?php if (empty($upcoming)): ?>
                <p class="text-muted" style="padding:8px 4px;">You have no upcoming tasks scheduled.</p>
            <?php else: foreach ($upcoming as $s): ?>
                <div class="sched-item">
                    <div class="sched-time">
                        <span class="sched-time-start"><?= htmlspecialchars(formatTime($s['start_time'])) ?></span>
                        <span class="sched-time-end"><?= htmlspecialchars(formatTime($s['end_time'])) ?></span>
                    </div>
                    <div class="sched-info">
                        <span class="sched-course"><?= htmlspecialchars($s['task_title']) ?></span>
                        <span class="sched-meta"><?= htmlspecialchars(formatDate($s['scheduled_date'])) ?><?= $s['location'] ? ' • ' . htmlspecialchars($s['location']) : '' ?></span>
                    </div>
                    <span class="s-pill s-pill-blue"><?= htmlspecialchars($s['type_name'] ?? 'Task') ?></span>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- ── KPI Cards (own data only) ── -->
    <div class="kpi-grid">
        <?php
        $accentMap   = ['teal'=>'teal','blue'=>'blue','purple'=>'purple','coral'=>'coral','amber'=>'amber'];
        foreach ($cards as $c):
            $accent   = $accentMap[$c[4]] ?? 'teal';
            $isDanger = ($c[5] ?? '') === 'danger';
        ?>
        <div class="kpi-card">
            <div class="kpi-strip <?= htmlspecialchars($accent) ?>"></div>
            <div class="kpi-inner">
                <div class="kpi-row-top">
                    <div class="kpi-icon <?= htmlspecialchars($c[4]) ?>"><?= sic_icon((string)$c[3]) ?></div>
                    <span class="kpi-trend <?= $isDanger ? 'danger' : '' ?>"><?= $isDanger ? '⚠ Alert' : '● Live' ?></span>
                </div>
                <div class="kpi-label"><?= htmlspecialchars($c[0]) ?></div>
                <span class="kpi-number"><?= htmlspecialchars((string)$c[1]) ?></span>
                <div class="kpi-note <?= $isDanger ? 'danger' : '' ?>"><?= htmlspecialchars($c[2]) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Row: Weekly Task Summary | Replacement Alerts (own only) ── -->
    <div class="dash-grid-row1">

        <!-- Weekly Task Summary (replaces the old global "Availability" widget) -->
        <div class="d-card">
            <div class="section-head">
                <h2 class="section-title"><?= sic_icon('chart') ?>My Weekly Task Summary</h2>
                <span class="section-badge">Next 7 days</span>
            </div>
            <div class="avail-body">
                <div class="donut-wrap">
                    <?php if ($weekTotal > 0):
                        $donutR2 = 52; $donutCirc2 = 2 * M_PI * $donutR2; $cumulative2 = 0;
                    ?>
                    <svg viewBox="0 0 140 140" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="70" cy="70" r="<?= $donutR2 ?>" fill="none" stroke="#e2e8f0" stroke-width="18"/>
                        <?php foreach ($typeBreakdown as $ti => $row):
                            $pct = ($row['c'] / $weekTotal) * 100;
                            $dashLen  = ($pct / 100) * $donutCirc2;
                            $gapLen   = $donutCirc2 - $dashLen;
                            $rotation = -90 + ($cumulative2 / 100) * 360;
                            $cumulative2 += $pct;
                            $color = $typeColors[$ti % count($typeColors)];
                        ?>
                        <circle cx="70" cy="70" r="<?= $donutR2 ?>" fill="none"
                                stroke="<?= htmlspecialchars($color) ?>" stroke-width="18"
                                stroke-dasharray="<?= round($dashLen, 2) ?> <?= round($gapLen, 2) ?>"
                                stroke-linecap="butt"
                                transform="rotate(<?= round($rotation, 2) ?> 70 70)"/>
                        <?php endforeach; ?>
                        <circle cx="70" cy="70" r="42" fill="white"/>
                    </svg>
                    <?php else: ?>
                    <svg viewBox="0 0 140 140" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="70" cy="70" r="52" fill="none" stroke="#e2e8f0" stroke-width="18"/>
                    </svg>
                    <?php endif; ?>
                    <div class="donut-center">
                        <strong><?= $weekTotal ?></strong>
                        <small>Tasks</small>
                    </div>
                </div>
                <?php if (empty($typeBreakdown)): ?>
                    <p class="text-muted" style="padding:8px 4px;">No tasks scheduled in the next 7 days.</p>
                <?php else: foreach ($typeBreakdown as $ti => $row):
                    $color = $typeColors[$ti % count($typeColors)];
                    $pct = $weekTotal > 0 ? round(($row['c'] / $weekTotal) * 100) : 0;
                ?>
                <div class="avail-legend-item">
                    <div class="avail-legend-name">
                        <span class="leg-dot" style="background:<?= htmlspecialchars($color) ?>"></span>
                        <?= htmlspecialchars($row['type_name']) ?>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div class="avail-pct-bar">
                            <div class="avail-pct-fill" style="width:<?= $pct ?>%;background:<?= htmlspecialchars($color) ?>"></div>
                        </div>
                        <strong><?= (int)$row['c'] ?></strong>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Replacement Alerts — only requests this instructor is actually part of -->
        <div class="d-card">
            <div class="section-head">
                <h2 class="section-title"><?= sic_icon('warning') ?>My Replacement Alerts</h2>
                <a href="<?= app_url('instructor/replacement_request.php') ?>" class="section-link"><?= sic_icon('eye') ?>View all</a>
            </div>
            <div class="alert-list">
                <?php if (empty($myAlerts)): ?>
                    <p class="text-muted" style="padding:8px 4px;">No pending replacement requests involving you.</p>
                <?php else: foreach ($myAlerts as $a):
                    $role = ((int)$a['requested_by_instructor_id'] === $instructorId) ? 'You requested' : 'Suggested for you';
                ?>
                <div class="alert-item">
                    <div class="alert-icon-wrap"><?= sic_icon('warning') ?></div>
                    <div class="alert-info">
                        <span class="alert-course"><?= htmlspecialchars($a['task_title']) ?></span>
                        <span class="alert-meta"><?= htmlspecialchars($role) ?> • <?= htmlspecialchars(formatDate($a['scheduled_date'])) ?></span>
                    </div>
                    <span class="s-pill s-pill-orange">Pending</span>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Recent Task Allocations — this instructor's own tasks ONLY ── -->
    <div class="d-card">
        <div class="section-head">
            <h2 class="section-title"><?= sic_icon('history') ?>My Recent Task Allocations</h2>
            <a href="<?= app_url('instructor/my_tasks.php') ?>" class="section-link"><?= sic_icon('eye') ?>View all</a>
        </div>
        <div class="act-table-wrap">
            <table class="act-table">
                <thead>
                    <tr><th>Task / Activity</th><th>Type</th><th>Date</th><th>Time</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php if (empty($myRecentTasks)): ?>
                    <tr><td colspan="5" class="text-muted">No task allocations yet.</td></tr>
                <?php else: foreach ($myRecentTasks as $t):
                    $status = $t['status'] ?? 'Pending';
                    $cls = $status === 'Completed' ? 's-pill-green'
                         : ($status === 'Cancelled' ? 's-pill-red'
                         : ($status === 'Accepted' ? 's-pill-teal' : 's-pill-orange'));
                ?>
                <tr>
                    <td data-label="Task"><?= htmlspecialchars($t['task_title'] ?? 'Academic Task') ?></td>
                    <td data-label="Type"><?= htmlspecialchars($t['type_name'] ?? '—') ?></td>
                    <td data-label="Date"><?= !empty($t['scheduled_date']) ? htmlspecialchars(formatDate($t['scheduled_date'])) : '—' ?></td>
                    <td data-label="Time"><?= htmlspecialchars(trim(formatTime($t['start_time'] ?? '') . ' – ' . formatTime($t['end_time'] ?? ''), ' –')) ?: '—' ?></td>
                    <td data-label="Status"><span class="s-pill <?= $cls ?>"><?= htmlspecialchars($status) ?></span></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</section>
<?php
    }
}