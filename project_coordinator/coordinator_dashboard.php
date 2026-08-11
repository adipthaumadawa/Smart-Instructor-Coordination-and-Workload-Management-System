<?php
<<<<<<< HEAD
=======
/**
 * Project Coordinator Dashboard
 * Smart Instructor Coordination and Workload Management System
 * 
 * This dashboard is accessible only to users with the Project Coordinator role (Role ID: 6).
 * Manages presentation sessions, assigns panel members, and tracks presentation schedules.
 */

>>>>>>> 1c8e2e93fbd5460b344258cfe02192e1cb4dad1a
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
<<<<<<< HEAD
require_once __DIR__ . '/../includes/dashboard_ui.php';

checkRole(ROLE_PROJECT_COORDINATOR);

$pageTitle = "Project Coordinator Dashboard";
include __DIR__ . '/../includes/header.php';

sic_render_dashboard(
    "Project Coordinator Dashboard",
    "Manage presentation sessions, panel members, instructor availability, venues, and schedules.",
    sic_dashboard_cards('project'),
    app_url('project_coordinator/schedule_session.php'),
    "Schedule Session"
);

include __DIR__ . '/../includes/footer.php';
?>
=======

// Ensure user is logged in
requireLogin();

// Ensure user has Project Coordinator role (Role ID: 6)
checkRole(ROLE_PROJECT_COORDINATOR);

// Get current user information
$currentUser = getCurrentUser();
$userId = $currentUser['id'];

// Fetch all presentation sessions managed by this coordinator
try {
    $stmt = $pdo->prepare("
        SELECT ps.*
        FROM presentation_sessions ps
        WHERE ps.project_coordinator_id = ?
        ORDER BY ps.session_date DESC
    ");
    $stmt->execute([$userId]);
    $allSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $allSessions = [];
    error_log("Error fetching presentation sessions: " . $e->getMessage());
}

// Fetch upcoming presentation sessions
try {
    $stmt = $pdo->prepare("
        SELECT ps.*
        FROM presentation_sessions ps
        WHERE ps.project_coordinator_id = ? AND ps.session_date >= CURDATE()
        ORDER BY ps.session_date ASC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $upcomingSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $upcomingSessions = [];
    error_log("Error fetching upcoming sessions: " . $e->getMessage());
}

// Fetch sessions by status
try {
    $stmt = $pdo->prepare("
        SELECT ps.status, COUNT(*) as count
        FROM presentation_sessions ps
        WHERE ps.project_coordinator_id = ?
        GROUP BY ps.status
    ");
    $stmt->execute([$userId]);
    $statusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $statusCounts = [];
    error_log("Error fetching status counts: " . $e->getMessage());
}

// Fetch panel assignments needing attention (incomplete panels)
try {
    $stmt = $pdo->prepare("
        SELECT ps.*, COUNT(ppm.id) as panel_members_count
        FROM presentation_sessions ps
        LEFT JOIN presentation_panel_members ppm ON ps.id = ppm.presentation_session_id
        WHERE ps.project_coordinator_id = ? AND ps.status IN ('Scheduled', 'Pending')
        GROUP BY ps.id
        ORDER BY ps.session_date ASC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $sessionsNeedingAttention = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $sessionsNeedingAttention = [];
    error_log("Error fetching sessions needing attention: " . $e->getMessage());
}

// Fetch available instructors for panel assignment
try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.full_name, d.name as department, i.designation
        FROM users u
        LEFT JOIN instructors i ON u.id = i.user_id
        LEFT JOIN departments d ON i.department_id = d.id
        WHERE u.role_id IN (?, ?, ?)
        AND u.status = 'active'
        ORDER BY u.full_name
        LIMIT 20
    ");
    $stmt->execute([ROLE_INSTRUCTOR, ROLE_COORDINATOR, ROLE_CHIEF_COORDINATOR]);
    $availableInstructors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $availableInstructors = [];
    error_log("Error fetching instructors: " . $e->getMessage());
}

// Calculate statistics
$totalSessions = count($allSessions);
$scheduledSessions = $statusCounts['Scheduled'] ?? 0;
$completedSessions = $statusCounts['Completed'] ?? 0;
$pendingSessions = $statusCounts['Pending'] ?? 0;

// Fetch recent activity
try {
    $stmt = $pdo->prepare("
        SELECT al.*
        FROM activity_logs al
        WHERE al.user_id = ?
        ORDER BY al.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recentActivity = [];
    error_log("Error fetching activity logs: " . $e->getMessage());
}

// Fetch notifications
try {
    $stmt = $pdo->prepare("
        SELECT * FROM notifications
        WHERE user_id = ? AND is_read = 0
        ORDER BY created_at DESC
        LIMIT 3
    ");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $notifications = [];
    error_log("Error fetching notifications: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Dashboard | Project Coordinator | Smart Instructor System</title>
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
                <h4><?= htmlspecialchars($currentUser['full_name'] ?? 'Coordinator') ?></h4>
                <div>Project Coordinator</div>
            </div>
        </div>
        <h1>Presentation Management Dashboard</h1>
        <p>Manage final year project presentations and panel assignments</p>
    </div>

    <!-- Alerts Section -->
    <?php if (count($sessionsNeedingAttention) > 0): ?>
    <div>
        <i>info</i>
        <div>
            <strong>Sessions Requiring Attention</strong><br>
            <small><?= count($sessionsNeedingAttention) ?> session(s) need panel member assignments or status updates</small>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($pendingSessions > 0): ?>
    <div>
        <i>event</i>
        <div>
            <strong>Pending Presentation Sessions</strong><br>
            <small><?= $pendingSessions ?> session(s) are pending and need to be scheduled</small>
        </div>
    </div>
    <?php endif; ?>

    <!-- Key Statistics -->
    <div>
        
        <!-- Total Sessions Card -->
        <div>
            <h3>Total Sessions</h3>
            <div><?= $totalSessions ?></div>
            <div>all presentations managed</div>
        </div>

        <!-- Scheduled Sessions Card -->
        <div>
            <h3>Scheduled</h3>
            <div><?= $scheduledSessions ?></div>
            <div>ready for presentations</div>
        </div>

        <!-- Completed Sessions Card -->
        <div>
            <h3>Completed</h3>
            <div><?= $completedSessions ?></div>
            <div>sessions concluded</div>
        </div>

        <!-- Pending Sessions Card -->
        <div>
            <h3>Pending</h3>
            <div><?= $pendingSessions ?></div>
            <div>awaiting scheduling</div>
        </div>

    </div>

    <!-- Main Content Grid -->
    <div>

        <!-- Upcoming Presentations Card -->
        <div>
            <h3>Upcoming Presentations</h3>
            <?php if (!empty($upcomingSessions)): ?>
            <div>
                <table>
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Date</th>
                            <th>Venue</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcomingSessions as $session): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($session['course_code']) ?></strong><br>
                                <small><?= htmlspecialchars(substr($session['title'], 0, 40)) ?>...</small>
                            </td>
                            <td><?= htmlspecialchars($session['session_date']) ?></td>
                            <td><?= htmlspecialchars($session['venue']) ?></td>
                            <td><span><?= htmlspecialchars($session['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div>
                <a href="<?= app_url('project_coordinator/presentations.php') ?>">
                    <i>arrow_forward</i>
                    View All Sessions
                </a>
            </div>
            <?php else: ?>
            <div>
                <div>event</div>
                <p>No upcoming presentation sessions</p>
                <a href="<?= app_url('project_coordinator/presentations.php') ?>">
                    <i>add</i>
                    Create New Session
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sessions Needing Attention Card -->
        <div>
            <h3>Sessions Needing Attention</h3>
            <?php if (!empty($sessionsNeedingAttention)): ?>
            <div>
                <table>
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Date</th>
                            <th>Panel Members</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessionsNeedingAttention as $session): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($session['course_code']) ?></strong><br>
                                <small><?= htmlspecialchars(substr($session['title'], 0, 30)) ?>...</small>
                            </td>
                            <td><?= htmlspecialchars($session['session_date']) ?></td>
                            <td>
                                <span><?= (int)$session['panel_members_count'] ?> assigned</span>
                            </td>
                            <td>
                                <a href="<?= app_url('project_coordinator/assign_panel.php?id=' . urlencode($session['id'])) ?>">
                                   Manage
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div>
                <div>check_circle</div>
                <p>All sessions are properly configured</p>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Recent Activity Card -->
    <div>
        <h3>Recent Activity</h3>
        <?php if (!empty($recentActivity)): ?>
        <ul>
            <?php foreach ($recentActivity as $activity): ?>
            <li>
                <i>info</i>
                <div>
                    <div>
                        <?= htmlspecialchars($activity['action']) ?>
                    </div>
                    <small>
                        <?= htmlspecialchars($activity['description'] ?? '') ?>
                    </small>
                    <small>
                        <?= date('M d, Y H:i', strtotime($activity['created_at'])) ?>
                    </small>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <div>
            <p>No recent activity</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Quick Actions Section -->
    <div>
        <h3>Quick Actions</h3>
        <div>
            <a href="<?= app_url('project_coordinator/presentations.php') ?>">
                <i>event</i>
                View All Sessions
            </a>
            <a href="<?= app_url('project_coordinator/create_session.php') ?>">
                <i>add</i>
                Create New Session
            </a>
            <a href="<?= app_url('project_coordinator/assignments.php') ?>">
                <i>group_add</i>
                Manage Panel Assignments
            </a>
            <a href="<?= app_url('project_coordinator/schedule.php') ?>">
                <i>schedule</i>
                View Schedule
            </a>
            <a href="<?= app_url('auth/logout.php') ?>">
                <i>logout</i>
                Logout
            </a>
        </div>
    </div>

    <!-- Footer -->
    <div>
        <p>Smart Instructor Coordination and Workload Management System<br>
        University of Colombo School of Computing</p>
    </div>

</main>

</body>
</html>
>>>>>>> 1c8e2e93fbd5460b344258cfe02192e1cb4dad1a
