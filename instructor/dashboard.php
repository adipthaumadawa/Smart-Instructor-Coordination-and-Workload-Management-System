<?php
/**
 * Instructor Dashboard
 * Smart Instructor Coordination and Workload Management System
 * 
 * This dashboard is accessible only to users with the Instructor role (Role ID: 2).
 * Displays personalized workload, timetable, leave status, and task assignments.
 */

// Load required configuration and authentication
// NEW (correct for instructor/dashboard.php)
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/ui-polish.css') ?>">
    <style>
        :root {
            --primary: #000a1e;
            --secondary: #006a6a;
            --surface: #f7f9fb;
            --on-surface: #191c1e;
            --error: #ba1a1a;
            --green: #1a7f1a;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--surface);
            color: var(--on-surface);
        }
        
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
        }
        
        .dashboard-header {
            margin-bottom: 32px;
        }
        
        .dashboard-header h1 {
            font-size: 2rem;
            margin-bottom: 8px;
            color: var(--primary);
        }
        
        .dashboard-header .subtitle {
            color: #666;
            font-size: 0.95rem;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #e8eaed;
        }
        
        .card h3 {
            font-size: 0.95rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 16px;
            font-weight: 600;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin: 8px 0;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #999;
            margin-top: 4px;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e8eaed;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 12px;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--secondary);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .progress-fill.warning {
            background: #f57c00;
        }
        
        .progress-fill.critical {
            background: var(--error);
        }
        
        .alert-banner {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 16px;
            border-radius: 4px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        
        .alert-banner.success {
            background: #d4edda;
            border-left-color: var(--green);
        }
        
        .alert-banner.error {
            background: #f8d7da;
            border-left-color: var(--error);
        }
        
        .alert-icon {
            font-family: 'Material Symbols Outlined';
            font-size: 20px;
            flex-shrink: 0;
            margin-top: 2px;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        
        th {
            text-align: left;
            padding: 12px;
            background: #f5f5f5;
            border-bottom: 2px solid #e8eaed;
            font-weight: 600;
            color: #666;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #e8eaed;
        }
        
        tr:hover {
            background: #f9f9f9;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-badge.approved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.active {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 6px;
            border: none;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #001d3a;
            box-shadow: 0 4px 12px rgba(0,10,30,0.2);
        }
        
        .btn-secondary {
            background: var(--secondary);
            color: white;
        }
        
        .btn-secondary:hover {
            background: #004d4d;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        
        .empty-state-icon {
            font-family: 'Material Symbols Outlined';
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        .profile-section {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .profile-avatar {
            width: 48px;
            height: 48px;
            background: var(--secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-family: 'Material Symbols Outlined';
            font-size: 28px;
        }
        
        .profile-info h4 {
            margin: 0;
            font-size: 1rem;
        }
        
        .profile-info .role {
            font-size: 0.85rem;
            color: #666;
            margin-top: 2px;
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 16px;
            }
            
            .dashboard-header h1 {
                font-size: 1.5rem;
            }
            
            .stat-value {
                font-size: 2rem;
            }
            
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    
    <!-- Header Section -->
    <div class="dashboard-header">
        <div class="profile-section">
            <div class="profile-avatar">person</div>
            <div class="profile-info">
                <h4><?= htmlspecialchars($currentUser['full_name'] ?? 'Instructor') ?></h4>
                <div class="role">Instructor</div>
            </div>
        </div>
        <h1>Dashboard</h1>
        <p class="subtitle">Welcome back to your Smart Instructor workspace</p>
    </div>

    <!-- Alerts Section -->
    <?php if ($activeLeave): ?>
    <div class="alert-banner success">
        <span class="alert-icon">check_circle</span>
        <div>
            <strong>You are on approved leave</strong><br>
            <small><?= htmlspecialchars($activeLeave['leave_type']) ?> leave from <?= htmlspecialchars($activeLeave['start_date']) ?> to <?= htmlspecialchars($activeLeave['end_date']) ?></small>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($workloadPercentage > 100): ?>
    <div class="alert-banner error">
        <span class="alert-icon">warning</span>
        <div>
            <strong>Workload Exceeds Maximum</strong><br>
            <small>You have <?= number_format($weeklyWorkload, 1) ?> hours this week (max: <?= $maxWeeklyHours ?> hours)</small>
        </div>
    </div>
    <?php elseif ($workloadPercentage > 90): ?>
    <div class="alert-banner">
        <span class="alert-icon">info</span>
        <div>
            <strong>High Workload this Week</strong><br>
            <small>You have <?= number_format($weeklyWorkload, 1) ?> hours (<?= number_format($workloadPercentage, 0) ?>% of maximum)</small>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($pendingReplacements)): ?>
    <div class="alert-banner">
        <span class="alert-icon">info</span>
        <div>
            <strong>Pending Replacement Requests</strong><br>
            <small>You have <?= count($pendingReplacements) ?> pending replacement request(s) waiting for approval</small>
        </div>
    </div>
    <?php endif; ?>

    <!-- Key Statistics -->
    <div class="grid-2">
        
        <!-- Workload Card -->
        <div class="card">
            <h3>Weekly Workload</h3>
            <div class="stat-value"><?= number_format($weeklyWorkload, 1) ?></div>
            <div class="stat-label">hours out of <?= $maxWeeklyHours ?> hours max</div>
            <div class="progress-bar">
                <div class="progress-fill <?= $workloadPercentage > 100 ? 'critical' : ($workloadPercentage > 90 ? 'warning' : '') ?>" 
                     style="width: <?= min($workloadPercentage, 100) ?>%"></div>
            </div>
            <small style="color: #999; display: block; margin-top: 8px;">
                <?= number_format($workloadPercentage, 0) ?>% of capacity
            </small>
        </div>

        <!-- Upcoming Tasks Card -->
        <div class="card">
            <h3>Upcoming Tasks</h3>
            <div class="stat-value"><?= count($upcomingTasks) ?></div>
            <div class="stat-label">assignments scheduled</div>
            <?php if (count($upcomingTasks) > 0): ?>
            <small style="color: #999; display: block; margin-top: 12px;">
                Next: <?= htmlspecialchars($upcomingTasks[0]['task_name'] ?? 'N/A') ?> on <?= htmlspecialchars($upcomingTasks[0]['scheduled_date']) ?>
            </small>
            <?php endif; ?>
        </div>

        <!-- Leave Status Card -->
        <div class="card">
            <h3>Leave Status</h3>
            <div class="stat-value" style="font-size: 1.5rem; margin-top: 0;">
                <?= $activeLeave ? 'On Leave' : 'At Work' ?>
            </div>
            <?php if ($activeLeave): ?>
            <div class="stat-label">
                Until <?= htmlspecialchars($activeLeave['end_date']) ?>
            </div>
            <span class="status-badge active">Active</span>
            <?php else: ?>
            <div class="stat-label">No active leave</div>
            <span class="status-badge" style="background: #e8eaed; color: #666;">Not on Leave</span>
            <?php endif; ?>
        </div>

        <!-- Profile Card -->
        <div class="card">
            <h3>Profile</h3>
            <?php if ($instructorProfile): ?>
            <p style="margin: 0 0 8px 0; font-weight: 500;">
                <?= htmlspecialchars($instructorProfile['first_name'] ?? '') ?> 
                <?= htmlspecialchars($instructorProfile['last_name'] ?? '') ?>
            </p>
            <small style="display: block; color: #999; margin-bottom: 8px;">
                <?= htmlspecialchars($instructorProfile['designation'] ?? 'Instructor') ?>
            </small>
            <small style="display: block; color: #999; margin-bottom: 4px;">
                <strong>Department:</strong> <?= htmlspecialchars($instructorProfile['department_name'] ?? 'N/A') ?>
            </small>
            <small style="display: block; color: #999;">
                <strong>Stream:</strong> <?= htmlspecialchars($instructorProfile['stream_name'] ?? 'N/A') ?>
            </small>
            <?php else: ?>
            <p style="color: #999;">Profile information not available</p>
            <?php endif; ?>
        </div>

    </div>

    <!-- This Week's Timetable -->
    <div class="card" style="margin-bottom: 24px;">
        <h3>This Week's Schedule</h3>
        <?php if (!empty($weeklyTimetable)): ?>
        <div class="table-responsive">
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
        <div class="empty-state">
            <div class="empty-state-icon">schedule</div>
            <p>No timetable scheduled for this week</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Upcoming Task Assignments -->
    <div class="card" style="margin-bottom: 24px;">
        <h3>Task Assignments</h3>
        <?php if (!empty($upcomingTasks)): ?>
        <div class="table-responsive">
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
                        <td><span class="status-badge pending"><?= htmlspecialchars($task['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">assignment</div>
            <p>No upcoming task assignments</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Pending Replacement Requests -->
    <?php if (!empty($pendingReplacements)): ?>
    <div class="card" style="margin-bottom: 24px;">
        <h3>Pending Replacement Requests</h3>
        <div class="table-responsive">
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
                        <td><span class="status-badge pending"><?= htmlspecialchars($replacement['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div style="display: flex; gap: 12px; margin-top: 32px; flex-wrap: wrap;">
        <a href="<?= app_url('instructor/timetable.php') ?>" class="btn btn-primary">View Full Timetable</a>
        <a href="<?= app_url('instructor/workload.php') ?>" class="btn btn-secondary">View Workload Details</a>
        <a href="<?= app_url('instructor/leave.php') ?>" class="btn btn-secondary">Request Leave</a>
        <a href="<?= app_url('instructor/replacements.php') ?>" class="btn btn-secondary">Manage Replacements</a>
        <a href="<?= app_url('auth/logout.php') ?>" class="btn" style="background: #f5f5f5; color: var(--on-surface);">Logout</a>
    </div>

    <!-- Footer -->
    <div style="text-align: center; margin-top: 64px; padding-top: 24px; border-top: 1px solid #e8eaed; color: #999; font-size: 0.85rem;">
        <p>Smart Instructor Coordination and Workload Management System<br>
        University of Colombo School of Computing</p>
    </div>

</div>

</body>
</html>