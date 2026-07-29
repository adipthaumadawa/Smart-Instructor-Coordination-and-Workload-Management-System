<?php
/**
 * Instructor - My Timetable
 * Smart Instructor Coordination and Workload Management System
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';

checkRole(ROLE_INSTRUCTOR);

$instructorId = sic_current_instructor_id();
if (!$instructorId) {
    $_SESSION['error'] = 'No instructor profile is linked to your account. Please contact the administrator.';
    header('Location: ' . app_url('instructor/dashboard.php'));
    exit;
}

// Recurring weekly timetable slots (lectures/labs), grouped by day
$stmt = $pdo->prepare("
    SELECT * FROM timetable_slots
    WHERE instructor_id = :iid
    ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time ASC
");
$stmt->execute([':iid' => $instructorId]);
$slots = $stmt->fetchAll();

$byDay = [];
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
foreach ($days as $d) { $byDay[$d] = []; }
foreach ($slots as $s) { $byDay[$s['day_of_week']][] = $s; }

// Upcoming task assignments for the next 14 days (additional tasks, replacements, presentations)
$stmt2 = $pdo->prepare("
    SELECT ta.*, tt.name AS type_name, COALESCE(atr.title, tt.name, 'Academic Task') AS task_title
    FROM task_assignments ta
    LEFT JOIN task_types tt ON ta.task_type_id = tt.id
    LEFT JOIN additional_task_requests atr ON ta.additional_task_request_id = atr.id
    WHERE ta.instructor_id = :iid
      AND ta.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)
      AND ta.status IN ('Assigned','Accepted')
    ORDER BY ta.scheduled_date ASC, ta.start_time ASC
");
$stmt2->execute([':iid' => $instructorId]);
$upcomingTasks = $stmt2->fetchAll();

$pageTitle = 'My Timetable';
include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1>My Timetable</h1>
                    <p>Your recurring weekly schedule and upcoming assigned tasks.</p>
                </div>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <div class="card" style="margin-bottom:20px;">
                <div class="card-header">
                    <h5>Weekly Recurring Schedule</h5>
                    <span class="text-muted small"><?= count($slots) ?> slot(s)</span>
                </div>
                <div class="card-body">
                    <?php if (empty($slots)): ?>
                        <p class="text-muted mb-0">No recurring timetable slots have been recorded for you yet. These are maintained by Non-Academic Staff.</p>
                    <?php else: ?>
                        <?php foreach ($days as $day): if (empty($byDay[$day])) continue; ?>
                            <h6 class="mt-3 mb-2"><?= htmlspecialchars($day) ?></h6>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr><th>Subject</th><th>Time</th><th>Location</th><th>Semester</th><th>Academic Year</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($byDay[$day] as $s): ?>
                                            <tr>
                                                <td data-label="Subject"><?= htmlspecialchars($s['subject']) ?></td>
                                                <td data-label="Time"><?= formatTime($s['start_time']) ?> - <?= formatTime($s['end_time']) ?></td>
                                                <td data-label="Location"><?= htmlspecialchars($s['location'] ?: 'N/A') ?></td>
                                                <td data-label="Semester"><?= htmlspecialchars($s['semester']) ?></td>
                                                <td data-label="Year"><?= htmlspecialchars($s['academic_year']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>Upcoming Tasks (Next 14 Days)</h5>
                    <span class="text-muted small"><?= count($upcomingTasks) ?> task(s)</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr><th>Task</th><th>Type</th><th>Date</th><th>Time</th><th>Location</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($upcomingTasks)): ?>
                                    <tr><td colspan="6" class="text-muted">No upcoming tasks in this period.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($upcomingTasks as $t): ?>
                                    <tr>
                                        <td data-label="Task"><?= htmlspecialchars($t['task_title']) ?></td>
                                        <td data-label="Type"><?= htmlspecialchars($t['type_name'] ?? 'N/A') ?></td>
                                        <td data-label="Date"><?= formatDate($t['scheduled_date']) ?></td>
                                        <td data-label="Time"><?= formatTime($t['start_time']) ?> - <?= formatTime($t['end_time']) ?></td>
                                        <td data-label="Location"><?= htmlspecialchars($t['location'] ?: 'N/A') ?></td>
                                        <td data-label="Status"><?= getStatusBadge($t['status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
