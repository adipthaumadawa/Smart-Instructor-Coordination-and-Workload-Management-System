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
            $threshold = max(1, min(100, (float) getSetting('workload_warning_threshold', 80))) / 100;
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM (
                SELECT i.id, i.max_weekly_hours, COALESCE(SUM(ta.duration_hours),0) AS week_hours
                FROM instructors i
                LEFT JOIN task_assignments ta ON ta.instructor_id = i.id
                    AND ta.is_presentation_panel = 0
                    AND ta.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                    AND ta.status IN ('Assigned','Accepted','Completed')
                WHERE i.status = 'active'
                GROUP BY i.id, i.max_weekly_hours
                HAVING week_hours >= (i.max_weekly_hours * :threshold)
            ) x");
            $stmt->execute([':threshold' => $threshold]);
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
        // Strip titles to get exact initial
        $clean = trim(preg_replace('/^(Dr\.|Prof\.|Mr\.|Mrs\.|Ms\.)\s+/i', '', $name));
        $initial = strtoupper(mb_substr($clean, 0, 1));
        
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
                // Named placeholders can't be reused when PDO emulation is off, so use two.
                $pendingReplacements = $instructorId ? (int)sic_scalar(
                    "SELECT COUNT(*) FROM replacement_requests
                     WHERE status = 'Pending'
                       AND (requested_by_instructor_id = :iid1 OR suggested_instructor_id = :iid2)",
                    [':iid1'=>$instructorId, ':iid2'=>$instructorId]
                ) : 0;
                return [
                    ["Today's Tasks",       $instructorId ? sic_scalar("SELECT COUNT(*) FROM task_assignments WHERE instructor_id = :iid AND scheduled_date = CURDATE() AND status IN ('Assigned','Accepted')", [':iid'=>$instructorId]) : 0, 'Scheduled for today', 'tasks',  'purple',''],
                    ['Weekly Workload',      ($instructorId ? sic_scalar("SELECT COALESCE(SUM(duration_hours),0) FROM task_assignments WHERE instructor_id = :iid AND is_presentation_panel = 0 AND scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 6 DAY) AND status IN ('Assigned','Accepted','Completed')", [':iid'=>$instructorId]) : 0) . ' hrs', 'Next 7 days', 'chart','blue',''],
                    ['Replacement Requests',$pendingReplacements, 'Waiting response', 'swap','coral', $pendingReplacements > 0 ? 'danger' : ''],
                    ['Notifications',        sic_scalar("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0", [':uid'=>$uid]), 'Unread alerts', 'bell','teal',''],
                ];
            case 'coordinator':
                return [
                    ['Available Instructors', sic_scalar("SELECT COUNT(*) FROM instructors WHERE status = 'active'"), 'Ready for allocation', 'group','teal',''],
                    ['Pending Task Requests', sic_scalar("SELECT COUNT(*) FROM additional_task_requests WHERE status = 'Pending'"), 'Need assignment', 'clipboard','purple',''],
                    ['Urgent Replacements',   sic_scalar("SELECT COUNT(*) FROM replacement_requests WHERE status = 'Pending'") + sic_scalar("SELECT COUNT(*) FROM additional_task_requests WHERE urgency = 'Urgent' AND status = 'Pending'"), 'Requires action', 'bell','coral','danger'],
                    ['Total Workload Hours',  sic_scalar("SELECT COALESCE(SUM(duration_hours),0) FROM task_assignments WHERE is_presentation_panel = 0 AND scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status IN ('Assigned','Accepted','Completed')") . ' hrs', 'This semester', 'gauge','blue',''],
                ];
            case 'chief':
                return [
                    ['Total Instructors',   sic_scalar("SELECT COUNT(*) FROM instructors"), 'Registered instructors', 'instructor','purple',''],
                    ['Active Allocations',  sic_scalar("SELECT COUNT(*) FROM task_assignments WHERE status IN ('Assigned','Accepted')"), 'Current assignments', 'project','blue',''],
                    ['Pending Replacements',sic_scalar("SELECT COUNT(*) FROM replacement_requests WHERE status = 'Pending'"), 'Needs monitoring', 'swap','coral','danger'],
                    ['Workload Alerts',     sic_workload_alert_count(), 'High workload risk', 'gauge','teal',''],
                ];
            case 'non_academic':
                // Instructors still missing a today's attendance row (table may not exist yet)
                $pendingAttendance = 0;
                try {
                    $pendingAttendance = (int)sic_scalar("
                        SELECT COUNT(*) FROM instructors i
                        WHERE i.status IN ('active','on_leave')
                          AND NOT EXISTS (
                              SELECT 1 FROM instructor_attendance a
                              WHERE a.instructor_id = i.id AND a.attendance_date = CURDATE()
                          )
                    ");
                } catch (Exception $e) {
                    $pendingAttendance = 0;
                }
                return [
                    ["Timetable Records Today", sic_scalar("SELECT COUNT(*) FROM timetable_slots WHERE day_of_week = DAYNAME(CURDATE())"), 'Official timetable', 'calendar','blue',''],
                    ['Room Bookings Today',      sic_scalar("SELECT COUNT(*) FROM lecture_hall_bookings WHERE booking_date = CURDATE() AND status IN ('Confirmed','Pending')"), 'Lecture halls/labs', 'building','purple',''],
                    ['Pending Attendance',      $pendingAttendance, 'Not yet recorded today', 'user-check','coral', $pendingAttendance > 0 ? 'danger' : ''],
                    ['Leave Notifications',      sic_scalar("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0 AND type = 'leave'", [':uid'=>$uid]), 'Unread leave alerts', 'bell','teal',''],
                ];
            case 'project':
                $upcomingSessions = (int)sic_scalar("SELECT COUNT(*) FROM presentation_sessions WHERE status = 'Scheduled' AND session_date >= CURDATE()");
                $incompletePanels = (int)sic_scalar("SELECT COUNT(*) FROM (SELECT ps.id FROM presentation_sessions ps LEFT JOIN presentation_panel_members ppm ON ppm.presentation_session_id = ps.id WHERE ps.status = 'Scheduled' AND ps.session_date >= CURDATE() GROUP BY ps.id HAVING COUNT(ppm.id) < 3) t");
                $availableToday   = (int)sic_scalar("SELECT COUNT(*) FROM instructors i WHERE i.status = 'active' AND NOT EXISTS (SELECT 1 FROM leave_records lr WHERE lr.instructor_id = i.id AND lr.status = 'Approved' AND CURDATE() BETWEEN lr.start_date AND lr.end_date)");
                $bookedVenues     = (int)sic_scalar("SELECT COUNT(DISTINCT venue) FROM presentation_sessions WHERE status = 'Scheduled' AND session_date >= CURDATE() AND venue IS NOT NULL AND venue <> ''");
                return [
                    ['Upcoming Sessions',     $upcomingSessions, 'Scheduled from today',       'calendar',  'purple', ''],
                    ['Incomplete Panels',     $incompletePanels, 'Fewer than 3 members',       'users',     'coral',  $incompletePanels > 0 ? 'danger' : ''],
                    ['Available Instructors', $availableToday,   'Active and not on leave today','user-check','teal',   ''],
                    ['Venues Booked',         $bookedVenues,     'For upcoming sessions',      'building',  'blue',   ''],
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
   MAIN RENDER FUNCTION
   ───────────────────────────────────────────────────────────────────────────── */

function sic_render_dashboard(string $heading, string $subtitle, array $cards = [], string $primaryActionUrl = '', string $primaryActionText = 'Quick Action', bool $kpiOnly = false) {

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

   /* ─────────────────────────────────────────────────────────────
   WORKLOAD BAR CHART
   Coordinator / Chief Coordinator / Director:
       Current-semester workload for each instructor.

   Instructor dashboard:
       NOT affected here because instructors use
       sic_render_instructor_dashboard().
   ───────────────────────────────────────────────────────────── */

/* ─────────────────────────────────────────────────────────────
   MANAGEMENT SEMESTER WORKLOAD
   Coordinator / Chief Coordinator / Director
   ───────────────────────────────────────────────────────────── */

$roleHeading = strtolower(trim($heading));

$isManagementWorkloadView =
    ($roleHeading === 'instructor coordinator dashboard') ||
    ($roleHeading === 'admin dashboard') ||
    ($roleHeading === 'director / department head dashboard') ||
    ($roleHeading === 'chief instructor coordinator dashboard');

$barData = [];
$maxBar  = 1;

$currentAcademicYear = '';
$currentSemester     = '';
$totalSemesterHours  = 0;

global $pdo;

if ($isManagementWorkloadView) {

    try {

        /*
         * Find the academic year + semester currently represented
         * in timetable_slots.
         *
         * We first prefer records for the current calendar year.
         */
        $periodStmt = $pdo->prepare("
            SELECT
                academic_year,
                semester,
                COUNT(*) AS slot_count
            FROM timetable_slots
            WHERE academic_year IS NOT NULL
              AND TRIM(academic_year) <> ''
              AND semester IS NOT NULL
              AND TRIM(semester) <> ''
            GROUP BY academic_year, semester
            ORDER BY
                CASE
                    WHEN academic_year LIKE CONCAT('%', YEAR(CURDATE()), '%')
                    THEN 0
                    ELSE 1
                END,
                academic_year DESC,
                semester DESC
            LIMIT 1
        ");

        $periodStmt->execute();

        $period = $periodStmt->fetch(PDO::FETCH_ASSOC);

        if ($period) {
            $currentAcademicYear = trim((string)$period['academic_year']);
            $currentSemester     = trim((string)$period['semester']);
        }

        /*
         * Fallback if there are currently no timetable records.
         */
        if ($currentAcademicYear === '') {
            $currentAcademicYear = getSetting('academic_year', DEFAULT_ACADEMIC_YEAR);
        }

        if ($currentSemester === '') {
            $currentSemester =
                (date('n') <= 6)
                    ? 'Semester 1'
                    : 'Semester 2';
        }

        /*
         * Get every active instructor.
         *
         * Employee ID is displayed on the horizontal axis.
         *
         * Workload is calculated only from timetable_slots
         * belonging to the selected academic year + semester.
         */
        $stmt = $pdo->prepare("
            SELECT
                i.id,
                i.employee_id,
                u.full_name,

                COALESCE(
                    SUM(
                        TIME_TO_SEC(
                            TIMEDIFF(
                                ts.end_time,
                                ts.start_time
                            )
                        ) / 3600
                    ),
                    0
                ) AS semester_hours

            FROM instructors i

            INNER JOIN users u
                ON u.id = i.user_id

            LEFT JOIN timetable_slots ts
                ON ts.instructor_id = i.id
                AND TRIM(ts.academic_year) = :academic_year
                AND LOWER(TRIM(ts.semester))
                    = LOWER(TRIM(:semester))

            WHERE i.status = 'active'

            GROUP BY
                i.id,
                i.employee_id,
                u.full_name

            ORDER BY
                semester_hours DESC,
                i.employee_id ASC
        ");

        $stmt->execute([
            ':academic_year' => $currentAcademicYear,
            ':semester'      => $currentSemester
        ]);

        $semesterWorkloads = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($semesterWorkloads as $row) {

            $employeeId = trim(
                (string)($row['employee_id'] ?? '')
            );

            if ($employeeId === '') {
                $employeeId = 'N/A';
            }

            $hours = round(
                (float)($row['semester_hours'] ?? 0),
                1
            );

            /*
             * Store:
             *   0 = employee ID
             *   1 = workload hours
             *   2 = instructor name
             */
            $barData[] = [
                $employeeId,
                $hours,
                $row['full_name'] ?? ''
            ];

            $totalSemesterHours += $hours;
        }

        $maxBar = !empty($barData)
            ? max(
                1,
                max(array_column($barData, 1))
            )
            : 1;

    } catch (Throwable $e) {

        $barData = [];
        $maxBar = 1;
        $totalSemesterHours = 0;

    }

} else {

    /*
     * Normal weekly chart for other dashboards.
     */
    $barData = [
        ['Mon', 55, false],
        ['Tue', 76, false],
        ['Wed', 62, false],
        ['Thu', 42, false],
        ['Fri', 50, false],
        ['Sat', 70, false],
        ['Sun', 52, false],
    ];

    $todayIdx = (int)date('N') - 1;

    foreach ($barData as $bi => &$bd) {
        $bd[2] = ($bi === $todayIdx);
    }

    unset($bd);

    $maxBar = max(
        1,
        max(array_column($barData, 1))
    );
}

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

        foreach ($cards as $idx => $c):
            $accent   = $accentMap[$c[4]] ?? 'teal';
            $isDanger = ($c[5] ?? '') === 'danger';
        ?>
        <div class="kpi-card">
            <div class="kpi-strip <?= htmlspecialchars($accent) ?>"></div>
            <div class="kpi-inner">
                <div class="kpi-row-top">
                    <div class="kpi-icon <?= htmlspecialchars($c[4]) ?>"><?= sic_icon((string)$c[3]) ?></div>
                    <?php if ($isDanger): ?>
                        <span class="kpi-trend danger">Alert</span>
                    <?php endif; ?>
                </div>
                <div class="kpi-label"><?= htmlspecialchars($c[0]) ?></div>
                <span class="kpi-number"><?= htmlspecialchars((string)$c[1]) ?></span>
                <div class="kpi-note <?= $isDanger ? 'danger' : '' ?>"><?= htmlspecialchars($c[2]) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div><!-- /.kpi-grid -->

    <?php if ($kpiOnly) { echo '</section>'; return; } /* role-specific pages draw their own widgets */ ?>
    <?php if ((int)($_SESSION['role_id'] ?? 0) !== ROLE_NON_ACADEMIC): ?>
    <!-- ── Row 1: Chart | Availability | Schedule ── -->
    <div class="dash-grid-row1">

<!-- ─────────────────────────────────────────────────────────────
     WORKLOAD OVERVIEW
     ───────────────────────────────────────────────────────────── -->

<div class="d-card workload-chart-card <?= $isManagementWorkloadView ? 'management-workload-full-row' : '' ?>">

    <div class="section-head">

        <h2 class="section-title">
            <?= sic_icon('chart') ?>

            Workload Overview

            <span class="section-title-note">
                (Hours)
            </span>
        </h2>

        <?php if ($isManagementWorkloadView): ?>

            <span class="section-badge">
                <?= htmlspecialchars($currentAcademicYear) ?>
                &nbsp;•&nbsp;
                <?= htmlspecialchars($currentSemester) ?>
            </span>

        <?php else: ?>

            <span class="section-badge">
                This Week
            </span>

        <?php endif; ?>

    </div>


    <div class="chart-area">

        <?php if ($isManagementWorkloadView): ?>

            <?php if (empty($barData)): ?>

                <div class="workload-empty-state">

                    No instructor workload data available for
                    <?= htmlspecialchars($currentAcademicYear) ?>
                    -
                    <?= htmlspecialchars($currentSemester) ?>.

                </div>

            <?php else: ?>

                <div class="management-workload-wrapper">

                    <div class="chart-bars management-workload-bars">

                        <?php foreach ($barData as $bar): ?>

                            <?php

                            $employeeId = $bar[0];
                            $barH      = (float)$bar[1];
                            $fullName  = $bar[2];

                            $barHeight = $barH > 0
                                ? max(
                                    6,
                                    round(($barH / $maxBar) * 100)
                                )
                                : 3;

                            ?>

                            <div
                                class="bar-col management-bar-col"
                                title="<?= htmlspecialchars(
                                    $fullName . ' (' . $employeeId . ')'
                                ) ?>"
                            >

                                <!-- Workload value -->
                                <div class="bar-value">
                                    <?= number_format($barH, 1) ?> hrs
                                </div>

                                <!-- Bar -->
                                <div class="bar-wrap">

                                    <div
                                        class="chart-bar management-chart-bar"
                                        style="--bar-height:<?= $barHeight ?>%"
                                        title="<?= htmlspecialchars(
                                            $fullName
                                            . ' - '
                                            . number_format($barH, 1)
                                            . ' hrs'
                                        ) ?>"
                                    ></div>

                                </div>

                                <!-- Employee ID on horizontal axis -->
                                <span
                                    class="bar-label management-bar-label"
                                    title="<?= htmlspecialchars($employeeId) ?>"
                                >
                                    <?= htmlspecialchars($employeeId) ?>
                                </span>

                            </div>

                        <?php endforeach; ?>

                    </div>


                    <!-- Bottom summary -->
                    <div class="chart-legend management-workload-footer">

                        <div class="workload-period">

                            <span class="legend-dot teal"></span>

                            Semester Workload of Instructor

                        </div>


                        <div class="chart-summary">

                            <span>
                                Total semester workload:
                            </span>

                            <strong>
                                <?= number_format(
                                    $totalSemesterHours,
                                    1
                                ) ?>
                                hrs
                            </strong>

                        </div>

                    </div>

                </div>

            <?php endif; ?>


        <?php else: ?>

            <!-- Normal weekly workload chart -->

            <div class="chart-bars">

                <?php foreach ($barData as $bar): ?>

                    <?php
                    $dayLabel = $bar[0];
                    $barH     = $bar[1];
                    $isToday  = $bar[2];
                    ?>

                    <div
                        class="bar-col <?= $isToday ? 'today' : '' ?>"
                    >

                        <div class="bar-wrap">

                            <div
                                class="chart-bar"
                                style="--bar-height:<?= round(
                                    ($barH / $maxBar) * 100
                                ) ?>%"
                                title="<?= $barH ?> hrs"
                            ></div>

                        </div>

                        <span class="bar-label">
                            <?= htmlspecialchars($dayLabel) ?>
                        </span>

                    </div>

                <?php endforeach; ?>

            </div>


            <div class="chart-legend">

                <span>
                    <span class="legend-dot teal"></span>

                    Regular
                </span>

                <span>
                    <span class="legend-dot blue"></span>

                    Today
                </span>

                <div class="chart-summary">

                    Total this week:

                    <strong>
                        <?= $weeklyHours ?> hrs
                    </strong>

                </div>

            </div>

        <?php endif; ?>

    </div>

</div>

        <!-- Instructor Availability Donut -->
        <div class="d-card">
            <div class="section-head">
                <h2 class="section-title"><?= sic_icon('users') ?>Availability</h2>
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
                        <span class="leg-dot" style="--dot-color:<?= htmlspecialchars($seg[2]) ?>"></span>
                        <?= htmlspecialchars($seg[0]) ?>
                    </div>
                    <div class="avail-legend-value">
                        <div class="avail-pct-bar">
                            <div class="avail-pct-fill" style="--value:<?= $seg[1] ?>%;--fill-color:<?= htmlspecialchars($seg[2]) ?>"></div>
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
            <a href="<?= app_url('system/unavailable.php') ?>" class="section-link">
                <?= sic_icon('eye') ?>View all
            </a>
        </div>

        <div class="leave-list">
            <?php
            $leaves = [
                ['C','Dr. Chamila Wijesooriya','May 19 – 21, 2025','Medical Leave','s-pill-blue','Pending','s-pill-orange', null],
                ['I','Mr. Isuru Madushan','May 16, 2025 (1 day)','Casual Leave','s-pill-teal','Pending','s-pill-orange', null],
                ['H','Dr. Harini Silva','May 23 – 24, 2025','Medical Leave','s-pill-blue','Approved','s-pill-green', null],
                ['S','Mr. Sachintha Perera','May 15, 2025 (1 day)','Casual Leave','s-pill-teal','Declined','s-pill-red', null],
            ];

            foreach ($leaves as $li => $l):
                $avatarBg = $avatarColors[$li % count($avatarColors)];
                $imgUrl = $l[7] ?? null;
            ?>

            <div class="leave-item">

                <div class="lv-avatar" style="--avatar-bg:<?= $avatarBg ?>">
                    <?php if ($imgUrl): ?>
                        <img
                            src="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>"
                            alt="<?= htmlspecialchars($l[1], ENT_QUOTES, 'UTF-8') ?>"
                        >
                    <?php else: ?>
                        <?= htmlspecialchars($l[0]) ?>
                    <?php endif; ?>
                </div>

                <div class="lv-info">
                    <span class="lv-name">
                        <?= htmlspecialchars($l[1]) ?>
                    </span>

                    <span class="lv-date">
                        <?= htmlspecialchars($l[2]) ?>
                    </span>
                </div>

                <div class="lv-badges">
                    <span class="s-pill <?= $l[4] ?>">
                        <?= $l[3] ?>
                    </span>

                    <span class="s-pill <?= $l[6] ?>">
                        <?= $l[5] ?>
                    </span>
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
            <h2 class="section-title">
                <?= sic_icon('building') ?>Hall Bookings
            </h2>

            <a href="<?= app_url('system/unavailable.php') ?>" class="section-link">
                <?= sic_icon('eye') ?>View all
            </a>
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
                    <span class="venue-name">
                        <?= htmlspecialchars($b[0]) ?>
                    </span>

                    <span class="venue-pct">
                        <?= $b[1] ?>
                    </span>
                </div>

                <div class="venue-row2">

                    <div class="venue-bar-wrap">
                        <div
                            class="venue-bar-fill <?= $b[4] ? 'high' : '' ?>"
                            style="--value:<?= $b[3] ?>%"
                        ></div>
                    </div>

                    <span class="venue-sessions">
                        <?= $b[2] ?> today
                    </span>

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
                    $userAvatar   = !empty($t['avatar_url']) ? app_url($t['avatar_url']) : null;
                ?>
                <tr>
                    <td data-label="Task"><?= htmlspecialchars($t['task_title'] ?? 'Academic Task') ?></td>
                    <td data-label="Course"><?= htmlspecialchars($t['type_name'] ?? 'Module') ?></td>
                    <td data-label="Assigned To">
                        <div class="act-assignee">
                            <div class="act-avatar" style="--avatar-bg:<?= $actBg ?>">
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
    <?php endif; ?>
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
                      AND ta.is_presentation_panel = 0
                      AND ta.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 6 DAY)
                      AND ta.status IN ('Assigned','Accepted','Completed')
                    GROUP BY type_name
                    ORDER BY c DESC
                ");
                $stmt->execute([':iid' => $instructorId]);
                $typeBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($typeBreakdown as $row) { $weekTotal += (int)$row['c']; }

                // PLACEHOLDER: timetable_slots (lectures/labs) has no per-date rows or duration to
                // total properly, so this just counts this instructor's weekly slots that fall on a
                // day within the next 7 days, as one more slice, until workload/timetable data is unified.
                $slotCount = (int)sic_scalar(
                    "SELECT COUNT(*) FROM timetable_slots
                     WHERE instructor_id = :iid
                       AND day_of_week IN (
                           SELECT DAYNAME(DATE_ADD(CURDATE(), INTERVAL n DAY))
                           FROM (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6) days
                       )",
                    [':iid' => $instructorId]
                );
                if ($slotCount > 0) {
                    $typeBreakdown[] = ['type_name' => 'Lectures/Labs', 'c' => $slotCount];
                    $weekTotal += $slotCount;
                }

                // PLACEHOLDER: presentation panels are excluded from the query above
                // (is_presentation_panel = 0), same as the workload cards. Add them back
                // here as their own slice so panel assignments are visible somewhere.
                $panelCount = (int)sic_scalar(
                    "SELECT COUNT(*) FROM task_assignments
                     WHERE instructor_id = :iid
                       AND is_presentation_panel = 1
                       AND scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 6 DAY)
                       AND status IN ('Assigned','Accepted','Completed')",
                    [':iid' => $instructorId]
                );
                if ($panelCount > 0) {
                    $typeBreakdown[] = ['type_name' => 'Presentation Panels', 'c' => $panelCount];
                    $weekTotal += $panelCount;
                }
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
                <p class="text-muted list-empty">You have no upcoming tasks scheduled.</p>
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
                    <p class="text-muted list-empty">No tasks scheduled in the next 7 days.</p>
                <?php else: foreach ($typeBreakdown as $ti => $row):
                    $color = $typeColors[$ti % count($typeColors)];
                    $pct = $weekTotal > 0 ? round(($row['c'] / $weekTotal) * 100) : 0;
                ?>
                <div class="avail-legend-item">
                    <div class="avail-legend-name">
                        <span class="leg-dot" style="--dot-color:<?= htmlspecialchars($color) ?>"></span>
                        <?= htmlspecialchars($row['type_name']) ?>
                    </div>
                    <div class="avail-legend-value">
                        <div class="avail-pct-bar">
                            <div class="avail-pct-fill" style="--value:<?= $pct ?>%;--fill-color:<?= htmlspecialchars($color) ?>"></div>
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
                    <p class="text-muted list-empty">No pending replacement requests involving you.</p>
                <?php else: foreach ($myAlerts as $a):
                    $role = ((int)$a['requested_by_instructor_id'] === $instructorId) ? 'You requested' : 'Suggested for you';
                ?>
                <div class="alert-item">
                    <div class="alert-icon-wrap"><?= sic_icon('swap') ?></div>
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