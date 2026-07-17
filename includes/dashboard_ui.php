<?php
require_once __DIR__ . '/../config/db.php';

if (!function_exists('sic_count')) {
    function sic_count(string $sql): int {
        global $pdo;
        try { return (int)$pdo->query($sql)->fetchColumn(); } catch (Throwable $e) { return 0; }
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
            $stmt = $pdo->query("
                SELECT COUNT(*) FROM (
                    SELECT i.id, i.max_weekly_hours, COALESCE(SUM(ta.duration_hours),0) AS week_hours
                    FROM instructors i
                    LEFT JOIN task_assignments ta ON ta.instructor_id = i.id
                        AND ta.is_presentation_panel = 0
                        AND ta.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                        AND ta.status IN ('Assigned','Accepted','Completed')
                    WHERE i.status = 'active'
                    GROUP BY i.id, i.max_weekly_hours
                    HAVING week_hours >= (i.max_weekly_hours * 0.8)
                ) x
            ");
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

if (!function_exists('sic_dashboard_cards')) {
    function sic_dashboard_cards(string $roleKey): array {
        $roleKey = strtolower($roleKey);
        $uid = (int)($_SESSION['user_id'] ?? 0);
        $instructorId = sic_current_instructor_id();

        switch ($roleKey) {
            case 'admin':
                return [
                    ['Total Users', sic_scalar("SELECT COUNT(*) FROM users"), 'All system accounts', 'fas fa-users', 'purple', ''],
                    ['Active Users', sic_scalar("SELECT COUNT(*) FROM users WHERE status = 'active'"), 'Enabled accounts', 'fas fa-user-check', 'teal', ''],
                    ['System Roles', sic_scalar("SELECT COUNT(*) FROM roles"), 'Access levels', 'fas fa-user-shield', 'blue', ''],
                    ['Activity Logs Today', sic_scalar("SELECT COUNT(*) FROM activity_logs WHERE DATE(created_at) = CURDATE()"), 'Audit entries', 'fas fa-clock-rotate-left', 'coral', ''],
                ];

            case 'instructor':
                return [
                    ["Today’s Tasks", $instructorId ? sic_scalar("SELECT COUNT(*) FROM task_assignments WHERE instructor_id = :iid AND scheduled_date = CURDATE() AND status IN ('Assigned','Accepted')", [':iid'=>$instructorId]) : 0, 'Scheduled for today', 'fas fa-list-check', 'purple', ''],
                    ['Weekly Workload', ($instructorId ? sic_scalar("SELECT COALESCE(SUM(duration_hours),0) FROM task_assignments WHERE instructor_id = :iid AND is_presentation_panel = 0 AND scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status IN ('Assigned','Accepted','Completed')", [':iid'=>$instructorId]) : 0) . ' hrs', 'Next 7 days', 'fas fa-chart-line', 'blue', ''],
                    ['Replacement Requests', $instructorId ? sic_scalar("SELECT COUNT(*) FROM replacement_requests WHERE status = 'Pending' AND (requested_by_instructor_id = :iid OR suggested_instructor_id = :iid)", [':iid'=>$instructorId]) : 0, 'Waiting response', 'fas fa-arrow-right-arrow-left', 'coral', 'danger'],
                    ['Notifications', sic_scalar("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0", [':uid'=>$uid]), 'Unread alerts', 'fas fa-bell', 'teal', ''],
                ];

            case 'coordinator':
                return [
                    ['Available Instructors', sic_scalar("SELECT COUNT(*) FROM instructors WHERE status = 'active'"), 'Ready for allocation', 'fas fa-user-group', 'teal', ''],
                    ['Pending Task Requests', sic_scalar("SELECT COUNT(*) FROM additional_task_requests WHERE status = 'Pending'"), 'Need assignment', 'fas fa-clipboard-list', 'purple', ''],
                    ['Urgent Replacements', sic_scalar("SELECT COUNT(*) FROM replacement_requests WHERE status = 'Pending'") + sic_scalar("SELECT COUNT(*) FROM additional_task_requests WHERE urgency = 'Urgent' AND status = 'Pending'"), 'Requires action', 'fas fa-triangle-exclamation', 'coral', 'danger'],
                    ['Total Workload Hours', sic_scalar("SELECT COALESCE(SUM(duration_hours),0) FROM task_assignments WHERE is_presentation_panel = 0 AND scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status IN ('Assigned','Accepted','Completed')") . ' hrs', 'This week', 'fas fa-clock', 'blue', ''],
                ];

            case 'chief':
                return [
                    ['Total Instructors', sic_scalar("SELECT COUNT(*) FROM instructors"), 'Registered instructors', 'fas fa-chalkboard-user', 'purple', ''],
                    ['Active Allocations', sic_scalar("SELECT COUNT(*) FROM task_assignments WHERE status IN ('Assigned','Accepted')"), 'Current assignments', 'fas fa-diagram-project', 'blue', ''],
                    ['Pending Replacements', sic_scalar("SELECT COUNT(*) FROM replacement_requests WHERE status = 'Pending'"), 'Needs monitoring', 'fas fa-right-left', 'coral', 'danger'],
                    ['Workload Alerts', sic_workload_alert_count(), 'High workload risk', 'fas fa-gauge-high', 'teal', ''],
                ];

            case 'non_academic':
                return [
                    ["Timetable Records Today", sic_scalar("SELECT COUNT(*) FROM timetable_slots WHERE day_of_week = DAYNAME(CURDATE())"), 'Official timetable', 'fas fa-calendar-days', 'blue', ''],
                    ['Room Bookings Today', sic_scalar("SELECT COUNT(*) FROM lecture_hall_bookings WHERE booking_date = CURDATE() AND status IN ('Confirmed','Pending')"), 'Lecture halls/labs', 'fas fa-building', 'purple', ''],
                    ['Pending Attendance Updates', 0, 'Attendance module', 'fas fa-user-clock', 'coral', ''],
                    ['Leave Notifications', sic_scalar("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0 AND type = 'leave'", [':uid'=>$uid]), 'Unread leave alerts', 'fas fa-bell', 'teal', ''],
                ];

            case 'project':
                return [
                    ['Presentation Sessions', sic_scalar("SELECT COUNT(*) FROM presentation_sessions WHERE status = 'Scheduled'"), 'Scheduled sessions', 'fas fa-display', 'purple', ''],
                    ['Pending Panels', sic_scalar("SELECT COUNT(*) FROM presentation_sessions ps WHERE ps.status = 'Scheduled' AND NOT EXISTS (SELECT 1 FROM presentation_panel_members ppm WHERE ppm.presentation_session_id = ps.id)"), 'Need panel members', 'fas fa-users-gear', 'coral', 'danger'],
                    ['Available Instructors', sic_scalar("SELECT COUNT(*) FROM instructors WHERE status = 'active'"), 'For panel selection', 'fas fa-user-check', 'teal', ''],
                    ['Booked Venues', sic_scalar("SELECT COUNT(DISTINCT venue) FROM presentation_sessions WHERE status = 'Scheduled' AND venue IS NOT NULL AND venue <> ''"), 'Presentation venues', 'fas fa-location-dot', 'blue', ''],
                ];

            case 'director':
                return [
                    ['Active Tasks', sic_scalar("SELECT COUNT(*) FROM task_assignments WHERE status IN ('Assigned','Accepted')"), 'Operational view only', 'fas fa-list-check', 'purple', ''],
                    ['Instructors On Leave', sic_scalar("SELECT COUNT(DISTINCT instructor_id) FROM leave_records WHERE status = 'Approved' AND CURDATE() BETWEEN start_date AND end_date"), 'Currently unavailable', 'fas fa-user-minus', 'blue', ''],
                    ['Pending Replacements', sic_scalar("SELECT COUNT(*) FROM replacement_requests WHERE status = 'Pending'"), 'Monitoring only', 'fas fa-arrow-right-arrow-left', 'coral', 'danger'],
                    ['Room Usage Today', sic_room_usage_percent(), 'Lecture room usage', 'fas fa-chart-pie', 'teal', ''],
                ];
        }
        return [];
    }
}

if (!function_exists('sic_recent_tasks')) {
    function sic_recent_tasks(): array {
        global $pdo;
        try {
            return $pdo->query("\n                SELECT ta.*, i.employee_id, u.full_name, tt.name AS type_name,\n                       COALESCE(atr.title, tt.name, 'Academic Task') AS task_title\n                FROM task_assignments ta\n                LEFT JOIN instructors i ON ta.instructor_id = i.id\n                LEFT JOIN users u ON i.user_id = u.id\n                LEFT JOIN task_types tt ON ta.task_type_id = tt.id\n                LEFT JOIN additional_task_requests atr ON ta.additional_task_request_id = atr.id\n                ORDER BY ta.created_at DESC\n                LIMIT 4\n            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch(Throwable $e) { return []; }
    }
}

function sic_render_dashboard(string $heading, string $subtitle, array $cards = [], string $primaryActionUrl = '', string $primaryActionText = 'Quick Action') {
    $activeInstructors = sic_count("SELECT COUNT(*) FROM instructors WHERE status='active'");
    $todayClasses = sic_count("SELECT COUNT(*) FROM timetable_slots WHERE day_of_week = DAYNAME(CURDATE())");
    $pendingReplacements = sic_count("SELECT COUNT(*) FROM replacement_requests WHERE status='Pending'");
    $weeklyHours = sic_count("SELECT COALESCE(SUM(duration_hours),0) FROM task_assignments WHERE scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND is_presentation_panel=0");

    if (empty($cards)) {
        $cards = [
            ['Active Instructors', $activeInstructors, '+5 this week', 'fas fa-users', 'purple', ''],
            ["Today’s Classes", $todayClasses, 'Across lecture halls', 'fas fa-calendar-day', 'blue', ''],
            ['Pending Replacements', $pendingReplacements, 'Urgent attention', 'fas fa-arrow-right-arrow-left', 'coral', 'danger'],
            ['Weekly Workload Hours', $weeklyHours, 'Avg. hours / instructor', 'fas fa-clock', 'teal', ''],
        ];
    }
    $tasks = sic_recent_tasks();
    $weekStart = date('M d');
    $weekEnd = date('M d, Y', strtotime('+6 days'));
?>
<div class="container-fluid"><div class="row">
<?php include __DIR__ . '/sidebar.php'; ?>
<main class="main-content">
    <section class="dash-page">
        <div class="dash-hero">
            <div class="dash-title">
                <h1><?= htmlspecialchars($heading) ?></h1>
                <p><?= htmlspecialchars($subtitle) ?></p>
            </div>
            <div class="dash-actions">
                <span class="date-chip"><i class="fas fa-calendar-days"></i><?= htmlspecialchars($weekStart) ?> – <?= htmlspecialchars($weekEnd) ?></span>
                <?php if($primaryActionUrl): ?>
                    <a class="btn btn-primary dash-primary-action" href="<?= htmlspecialchars($primaryActionUrl) ?>"><i class="fas fa-bolt"></i><?= htmlspecialchars($primaryActionText) ?></a>
                <?php endif; ?>
            </div>
        </div>

        <div class="kpi-grid">
            <?php foreach($cards as $c): ?>
            <div class="ui-card kpi-card">
                <div class="kpi-left">
                    <div class="kpi-icon <?= htmlspecialchars($c[4]) ?>"><i class="<?= htmlspecialchars($c[3]) ?>"></i></div>
                    <div>
                        <div class="kpi-label"><?= htmlspecialchars($c[0]) ?></div>
                        <div class="kpi-number"><?= htmlspecialchars((string)$c[1]) ?></div>
                        <div class="kpi-note <?= ($c[5] ?? '') === 'danger' ? 'danger' : '' ?>"><?= htmlspecialchars($c[2]) ?></div>
                    </div>
                </div>
                <div class="spark <?= ($c[4] === 'coral') ? 'coral' : (($c[4] === 'teal') ? 'teal' : '') ?>"></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="dashboard-grid">
            <div class="ui-card dash-card workload-card">
                <div class="dash-card-head">
                    <h2 class="dash-card-title">Workload Overview <span class="text-muted fw-normal">(Hours)</span></h2>
                    <span class="sic-pill">This Week</span>
                </div>
                <div class="mini-chart" aria-label="Weekly workload chart">
                    <?php $bars=[55,76,62,42,50,70,52]; $days=['Mon','Tue','Wed','Thu','Fri','Sat','Sun']; foreach($bars as $i=>$h): ?>
                    <div class="chart-col"><div class="bar" style="--h:<?= $h ?>%"></div><span><?= $days[$i] ?></span></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="ui-card dash-card availability-card">
                <div class="dash-card-head">
                    <h2 class="dash-card-title">Instructor Availability</h2>
                    <span class="sic-pill">Live</span>
                </div>
                <div class="px-3 pb-3">
                    <div class="ring"><div class="ring-inner"><strong><?= $activeInstructors ?: 100 ?></strong><small>Instructors</small></div></div>
                    <div class="legend-item"><span><i class="dot" style="background:#31c78a"></i>Available</span><strong>42%</strong></div>
                    <div class="legend-item"><span><i class="dot" style="background:#78a7ff"></i>Partial</span><strong>36%</strong></div>
                    <div class="legend-item"><span><i class="dot" style="background:#a990ff"></i>Busy</span><strong>16%</strong></div>
                    <div class="legend-item"><span><i class="dot" style="background:#c8cfdd"></i>On Leave</span><strong>6%</strong></div>
                </div>
            </div>

            <div class="ui-card dash-card schedule-card">
                <div class="dash-card-head">
                    <h2 class="dash-card-title">Upcoming Schedule</h2>
                    <a href="<?= app_url('instructor/timetable.php') ?>" class="section-link">View full</a>
                </div>
                <div class="schedule-list">
                    <?php $sched=[['09:00','10:30','CO3210 – Database Systems','Dr. N. De Silva • LT-3','Lecture','pill-green'],['11:00','12:30','CO2220 – Data Structures','Dr. R. Fernando • LT-2','Tutorial','pill-blue'],['14:00','15:30','CO4230 – Software Engineering','Dr. A. Perera • LT-1','Lecture','pill-purple'],['16:00','17:30','CO5310 – AI & Applications','Dr. K. Jayawardena • LT-4','Lecture','pill-orange']]; foreach($sched as $s): ?>
                    <div class="schedule-row">
                        <div class="schedule-time"><?= $s[0] ?><br><?= $s[1] ?></div>
                        <div><strong><?= $s[2] ?></strong><small class="text-muted"><?= $s[3] ?></small></div>
                        <span class="status-pill <?= $s[5] ?>"><?= $s[4] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="lower-grid">
            <div class="ui-card lower-card">
                <div class="dash-card-head">
                    <h2 class="dash-card-title">Recent Leave Requests</h2>
                    <a href="<?= app_url('coordinator/leave_records.php') ?>" class="section-link">View all</a>
                </div>
                <div class="list-stack">
                    <?php $leaves=[['C','Dr. Chamila Wijesooriya','May 19 – May 21, 2025','Medical Leave','pill-blue','Pending','pill-orange'],['I','Mr. Isuru Madushan','May 16, 2025 (1 day)','Casual Leave','pill-green','Pending','pill-orange'],['H','Dr. Harini Silva','May 23 – May 24, 2025','Medical Leave','pill-blue','Approved','pill-green'],['S','Mr. Sachintha Perera','May 15, 2025 (1 day)','Casual Leave','pill-green','Declined','pill-red']]; foreach($leaves as $l): ?>
                    <div class="leave-row">
                        <span class="avatar-sm"><?= $l[0] ?></span>
                        <div><strong><?= $l[1] ?></strong><small class="text-muted d-block"><?= $l[2] ?></small></div>
                        <span class="status-pill <?= $l[4] ?>"><?= $l[3] ?></span>
                        <span class="status-pill <?= $l[6] ?>"><?= $l[5] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="ui-card lower-card">
                <div class="dash-card-head">
                    <h2 class="dash-card-title">Urgent Replacement Alerts</h2>
                    <a href="<?= app_url('coordinator/urgency_replacements.php') ?>" class="section-link">View all</a>
                </div>
                <div class="list-stack">
                    <?php foreach([['CO3210 – Database Systems','May 14, 10:30 – 12:00 • LT-3'],['CO2220 – Data Structures','May 15, 09:00 – 10:30 • LT-2'],['CO4230 – Software Engineering','May 16, 14:00 – 15:30 • LT-1']] as $a): ?>
                    <div class="alert-row">
                        <i class="fas fa-triangle-exclamation"></i>
                        <div><strong><?= $a[0] ?></strong><small class="text-muted d-block"><?= $a[1] ?></small></div>
                        <span class="status-pill pill-red">Urgent</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="ui-card lower-card">
                <div class="dash-card-head">
                    <h2 class="dash-card-title">Lecture Hall Bookings</h2>
                    <a href="<?= app_url('rooms/bookings.php') ?>" class="section-link">View all</a>
                </div>
                <div class="list-stack">
                    <?php foreach([['LT-1 (120)','72%','6 / 8','72%',''],['LT-2 (80)','65%','5 / 8','65%',''],['LT-3 (60)','83%','5 / 6','83%','orange'],['LT-4 (100)','40%','2 / 5','40%','']] as $b): ?>
                    <div class="booking-row">
                        <div><strong><?= $b[0] ?></strong><small class="d-block">Capacity</small></div>
                        <div><strong><?= $b[1] ?></strong><small class="ms-2">Occupied</small><div class="booking-line <?= $b[4] ?>" style="--w:<?= $b[3] ?>"><span></span></div></div>
                        <div class="text-end"><small>Today</small><br><strong><?= $b[2] ?></strong></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="ui-card activity-card">
            <div class="dash-card-head">
                <h2 class="dash-card-title">Recent Activity / Task Allocations</h2>
                <a href="<?= app_url('reports/workload_report.php') ?>" class="section-link">View all</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Task / Activity</th><th>Course / Module</th><th>Assigned To</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php if(empty($tasks)): $tasks=[['task_title'=>'Lecture – Week 7','type_name'=>'CO3210 – Database Systems','full_name'=>'Dr. N. De Silva','scheduled_date'=>date('Y-m-d'),'start_time'=>'09:00','end_time'=>'10:30','status'=>'Completed'],['task_title'=>'Tutorial – Week 6','type_name'=>'CO2220 – Data Structures','full_name'=>'Mr. I. Madushan','scheduled_date'=>date('Y-m-d'),'start_time'=>'11:00','end_time'=>'12:30','status'=>'Completed'],['task_title'=>'Lecture – Week 7','type_name'=>'CO4230 – Software Engineering','full_name'=>'Dr. A. Perera','scheduled_date'=>date('Y-m-d'),'start_time'=>'14:00','end_time'=>'15:30','status'=>'In Progress'],['task_title'=>'Marking – Assignment 2','type_name'=>'CO2220 – Data Structures','full_name'=>'Dr. R. Fernando','scheduled_date'=>date('Y-m-d',strtotime('-1 day')),'start_time'=>'','end_time'=>'','status'=>'Pending']]; endif; foreach($tasks as $t): $status=$t['status']??'Pending'; $cls=$status==='Completed'?'pill-green':($status==='In Progress'?'pill-blue':'pill-orange'); ?>
                            <tr>
                                <td data-label="Task / Activity"><?= htmlspecialchars($t['task_title'] ?? 'Academic Task') ?></td>
                                <td data-label="Course / Module"><?= htmlspecialchars($t['type_name'] ?? 'Module') ?></td>
                                <td data-label="Assigned To"><span class="avatar-sm me-2" style="width:30px;height:30px"><?= strtoupper(substr($t['full_name'] ?? 'U',0,1)) ?></span><?= htmlspecialchars($t['full_name'] ?? 'Unassigned') ?></td>
                                <td data-label="Date"><?= !empty($t['scheduled_date']) ? date('M d, Y', strtotime($t['scheduled_date'])) : '-' ?></td>
                                <td data-label="Time"><?= htmlspecialchars(trim(($t['start_time']??'').' – '.($t['end_time']??''),' –')) ?: '-' ?></td>
                                <td data-label="Status"><span class="status-pill <?= $cls ?>"><?= htmlspecialchars($status) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</main></div></div>
<?php } ?>
