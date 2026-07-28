<?php
/**
 * Instructor Dashboard
 * Smart Instructor Coordination and Workload Management System
 * 
 * This dashboard is accessible only to users with the Instructor role (Role ID: 2).
 * Displays personalized workload, timetable, leave status, and task assignments.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is logged in
requireLogin();

// Ensure user has Instructor role (Role ID: 2)
checkRole(ROLE_INSTRUCTOR);

// Get current user information
$currentUser = getCurrentUser();
$userId = $currentUser['id'];

// Fetch instructor profile information
try {
    $stmt = $pdo->prepare("
        SELECT i.*, d.name as department_name, s.name as stream_name
        FROM instructors i
        LEFT JOIN departments d ON i.department_id = d.id
        LEFT JOIN academic_streams s ON i.academic_stream_id = s.id
        WHERE i.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $instructorProfile = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $instructorProfile = null;
    error_log("Error fetching instructor profile: " . $e->getMessage());
}

// Fetch this week's timetable
try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM timetable_slots
        WHERE instructor_id = ?
        ORDER BY 
            CASE day_of_week
                WHEN 'Monday' THEN 1
                WHEN 'Tuesday' THEN 2
                WHEN 'Wednesday' THEN 3
                WHEN 'Thursday' THEN 4
                WHEN 'Friday' THEN 5
                WHEN 'Saturday' THEN 6
                WHEN 'Sunday' THEN 7
            END,
            start_time
        LIMIT 10
    ");
    $stmt->execute([$instructorProfile['id'] ?? 0]);
    $weeklyTimetable = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $weeklyTimetable = [];
    error_log("Error fetching timetable: " . $e->getMessage());
}

// Fetch upcoming task assignments
try {
    $stmt = $pdo->prepare("
        SELECT ta.*, t.name as task_name, t.weight as task_weight
        FROM task_assignments ta
        LEFT JOIN task_types t ON ta.task_type_id = t.id
        WHERE ta.instructor_id = ? AND ta.status = 'Assigned'
        ORDER BY ta.scheduled_date ASC
        LIMIT 5
    ");
    $stmt->execute([$instructorProfile['id'] ?? 0]);
    $upcomingTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $upcomingTasks = [];
    error_log("Error fetching task assignments: " . $e->getMessage());
}

// Fetch current leave status
try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM leave_records
        WHERE instructor_id = ? AND status = 'Approved'
        AND start_date <= CURDATE() AND end_date >= CURDATE()
        LIMIT 1
    ");
    $stmt->execute([$instructorProfile['id'] ?? 0]);
    $activeLeave = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $activeLeave = null;
    error_log("Error fetching leave status: " . $e->getMessage());
}

// Fetch pending replacement requests made by this instructor
try {
    $stmt = $pdo->prepare("
        SELECT rr.*, ta.scheduled_date, ta.start_time, ta.end_time, t.name as task_name
        FROM replacement_requests rr
        JOIN task_assignments ta ON rr.task_assignment_id = ta.id
        LEFT JOIN task_types t ON ta.task_type_id = t.id
        WHERE rr.requested_by_instructor_id = ? AND rr.status = 'Pending'
        ORDER BY ta.scheduled_date ASC
        LIMIT 3
    ");
    $stmt->execute([$instructorProfile['id'] ?? 0]);
    $pendingReplacements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pendingReplacements = [];
    error_log("Error fetching pending replacements: " . $e->getMessage());
}

// Calculate total workload this week
try {
    $stmt = $pdo->prepare("
        SELECT SUM(ta.duration_hours) as total_hours
        FROM task_assignments ta
        WHERE ta.instructor_id = ? 
        AND ta.scheduled_date >= DATE_SUB(CURDATE(), INTERVAL DAYOFWEEK(CURDATE())-2 DAY)
        AND ta.scheduled_date <= DATE_ADD(DATE_SUB(CURDATE(), INTERVAL DAYOFWEEK(CURDATE())-2 DAY), INTERVAL 6 DAY)
    ");
    $stmt->execute([$instructorProfile['id'] ?? 0]);
    $workloadResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $weeklyWorkload = (float)($workloadResult['total_hours'] ?? 0);
} catch (PDOException $e) {
    $weeklyWorkload = 0;
    error_log("Error calculating workload: " . $e->getMessage());
}

// Fetch pending notifications
try {
    $stmt = $pdo->prepare("
        SELECT * FROM notifications
        WHERE user_id = ? AND is_read = 0
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $notifications = [];
    error_log("Error fetching notifications: " . $e->getMessage());
}

// System settings for max workload
$maxWeeklyHours = DEFAULT_MAX_WEEKLY_HOURS; // 40 hours (from config)
$workloadPercentage = ($weeklyWorkload / $maxWeeklyHours) * 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard | Instructor | Smart Instructor System</title>
    <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
    
</head>
<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="dashboard-container">
    
    <!-- Header Section -->
    <div class="dashboard-header">
        <div>
            <div>
                <span><?= strtoupper(substr($currentUser['full_name'], 0, 1)) ?></span>
            </div>
            <div>
                <h4><?= htmlspecialchars($currentUser['full_name'] ?? 'Instructor') ?></h4>
                <div>Instructor</div>
            </div>
        </div>
        <h1>Dashboard</h1>
        <p>Welcome back to your Smart Instructor workspace</p>
    </div>

    <!-- Alerts Section -->
    <?php if ($activeLeave): ?>
    <div>
        <i>check_circle</i>
        <div>
            <strong>You are on approved leave</strong><br>
            <small><?= htmlspecialchars($activeLeave['leave_type']) ?> leave from <?= htmlspecialchars($activeLeave['start_date']) ?> to <?= htmlspecialchars($activeLeave['end_date']) ?></small>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($workloadPercentage > 100): ?>
    <div>
        <i>warning</i>
        <div>
            <strong>Workload Exceeds Maximum</strong><br>
            <small>You have <?= number_format($weeklyWorkload, 1) ?> hours this week (max: <?= $maxWeeklyHours ?> hours)</small>
        </div>
    </div>
    <?php elseif ($workloadPercentage > 90): ?>
    <div>
        <i>info</i>
        <div>
            <strong>High Workload this Week</strong><br>
            <small>You have <?= number_format($weeklyWorkload, 1) ?> hours (<?= number_format($workloadPercentage, 0) ?>% of maximum)</small>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($pendingReplacements)): ?>
    <div>
        <i>info</i>
        <div>
            <strong>Pending Replacement Requests</strong><br>
            <small>You have <?= count($pendingReplacements) ?> pending replacement request(s) waiting for approval</small>
        </div>
    </div>
    <?php endif; ?>

    <!-- Key Statistics -->
    <div>
        
        <!-- Workload Card -->
        <div>
            <h3>Weekly Workload</h3>
            <div><?= number_format($weeklyWorkload, 1) ?></div>
            <div>hours out of <?= $maxWeeklyHours ?> hours max</div>
            <div>
                <div style="width: <?= min($workloadPercentage, 100) ?>%"></div>
            </div>
            <small>
                <?= number_format($workloadPercentage, 0) ?>% of capacity
            </small>
        </div>

        <!-- Upcoming Tasks Card -->
        <div>
            <h3>Upcoming Tasks</h3>
            <div><?= count($upcomingTasks) ?></div>
            <div>assignments scheduled</div>
            <?php if (count($upcomingTasks) > 0): ?>
            <small>
                Next: <?= htmlspecialchars($upcomingTasks[0]['task_name'] ?? 'N/A') ?> on <?= htmlspecialchars($upcomingTasks[0]['scheduled_date']) ?>
            </small>
            <?php endif; ?>
        </div>

        <!-- Leave Status Card -->
        <div>
            <h3>Leave Status</h3>
            <div>
                <?= $activeLeave ? 'On Leave' : 'At Work' ?>
            </div>
            <?php if ($activeLeave): ?>
            <div>
                Until <?= htmlspecialchars($activeLeave['end_date']) ?>
            </div>
            <span>Active</span>
            <?php else: ?>
            <div>No active leave</div>
            <span>Not on Leave</span>
            <?php endif; ?>
        </div>

        <!-- Profile Card -->
        <div>
            <h3>Profile</h3>
            <?php if ($instructorProfile): ?>
            <p>
                <?= htmlspecialchars($instructorProfile['first_name'] ?? '') ?> 
                <?= htmlspecialchars($instructorProfile['last_name'] ?? '') ?>
            </p>
            <small>
                <?= htmlspecialchars($instructorProfile['designation'] ?? 'Instructor') ?>
            </small>
            <small>
                <strong>Department:</strong> <?= htmlspecialchars($instructorProfile['department_name'] ?? 'N/A') ?>
            </small>
            <small>
                <strong>Stream:</strong> <?= htmlspecialchars($instructorProfile['stream_name'] ?? 'N/A') ?>
            </small>
            <?php else: ?>
            <p>Profile information not available</p>
            <?php endif; ?>
        </div>

    </div>

    <!-- This Week's Timetable -->
    <div>
        <h3>This Week's Schedule</h3>
        <?php if (!empty($weeklyTimetable)): ?>
        <div>
            <table>
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Subject</th>
                        <th>Location</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($weeklyTimetable as $slot): ?>
                    <tr>
                        <td><?= htmlspecialchars($slot['day_of_week']) ?></td>
                        <td><?= htmlspecialchars(substr($slot['start_time'], 0, 5)) ?> - <?= htmlspecialchars(substr($slot['end_time'], 0, 5)) ?></td>
                        <td><?= htmlspecialchars($slot['subject']) ?></td>
                        <td><?= htmlspecialchars($slot['location'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div>
            <div>schedule</div>
            <p>No timetable scheduled for this week</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Upcoming Task Assignments -->
    <div>
        <h3>Task Assignments</h3>
        <?php if (!empty($upcomingTasks)): ?>
        <div>
            <table>
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Duration</th>
                        <th>Location</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcomingTasks as $task): ?>
                    <tr>
                        <td><?= htmlspecialchars($task['task_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($task['scheduled_date']) ?></td>
                        <td><?= htmlspecialchars(substr($task['start_time'], 0, 5)) ?></td>
                        <td><?= number_format($task['duration_hours'], 1) ?> hrs</td>
                        <td><?= htmlspecialchars($task['location'] ?? '-') ?></td>
                        <td><span><?= htmlspecialchars($task['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div>
            <div>assignment</div>
            <p>No upcoming task assignments</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Pending Replacement Requests -->
    <?php if (!empty($pendingReplacements)): ?>
    <div>
        <h3>Pending Replacement Requests</h3>
        <div>
            <table>
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Date</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingReplacements as $replacement): ?>
                    <tr>
                        <td><?= htmlspecialchars($replacement['task_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($replacement['scheduled_date']) ?></td>
                        <td><?= htmlspecialchars(substr($replacement['reason'], 0, 50)) ?>...</td>
                        <td><span><?= htmlspecialchars($replacement['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div>
        <a href="<?= app_url('instructor/timetable.php') ?>">View Full Timetable</a>
        <a href="<?= app_url('instructor/workload.php') ?>">View Workload Details</a>
        <a href="<?= app_url('instructor/leave.php') ?>">Request Leave</a>
        <a href="<?= app_url('instructor/replacements.php') ?>">Manage Replacements</a>
        <a href="<?= app_url('auth/logout.php') ?>">Logout</a>
    </div>

    <!-- Footer -->
    <div>
        <p>Smart Instructor Coordination and Workload Management System<br>
        University of Colombo School of Computing</p>
    </div>

</main>

</body>
</html>