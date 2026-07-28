<?php
/**
 * Project Coordinator Dashboard
 * Smart Instructor Coordination and Workload Management System
 * 
 * This dashboard is accessible only to users with the Project Coordinator role (Role ID: 6).
 * Manages presentation sessions, assigns panel members, and tracks presentation schedules.
 */

// Load required configuration and authentication
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';

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
            --warning: #f57c00;
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
        
        .grid-4 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #e8eaed;
        }
        
        .card-lg {
            padding: 24px;
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
        
        .stat-small {
            font-size: 1.5rem;
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
        
        .alert-banner.info {
            background: #d1ecf1;
            border-left-color: #0c5460;
            color: #0c5460;
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
        
        .status-badge.scheduled {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-badge.completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
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
            gap: 8px;
            display: inline-flex;
            align-items: center;
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
        
        .btn-small {
            padding: 6px 12px;
            font-size: 0.8rem;
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
        
        .panel-member-item {
            background: #f5f5f5;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 6px;
            font-size: 0.85rem;
        }
        
        .badge-primary {
            display: inline-block;
            background: var(--secondary);
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
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
            
            .grid-4, .grid-2 {
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
                <h4><?= htmlspecialchars($currentUser['full_name'] ?? 'Coordinator') ?></h4>
                <div class="role">Project Coordinator</div>
            </div>
        </div>
        <h1>Presentation Management Dashboard</h1>
        <p class="subtitle">Manage final year project presentations and panel assignments</p>
    </div>

    <!-- Alerts Section -->
    <?php if (count($sessionsNeedingAttention) > 0): ?>
    <div class="alert-banner info">
        <span class="alert-icon">info</span>
        <div>
            <strong>Sessions Requiring Attention</strong><br>
            <small><?= count($sessionsNeedingAttention) ?> session(s) need panel member assignments or status updates</small>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($pendingSessions > 0): ?>
    <div class="alert-banner">
        <span class="alert-icon">event</span>
        <div>
            <strong>Pending Presentation Sessions</strong><br>
            <small><?= $pendingSessions ?> session(s) are pending and need to be scheduled</small>
        </div>
    </div>
    <?php endif; ?>

    <!-- Key Statistics -->
    <div class="grid-4">
        
        <!-- Total Sessions Card -->
        <div class="card">
            <h3>Total Sessions</h3>
            <div class="stat-value stat-small"><?= $totalSessions ?></div>
            <div class="stat-label">all presentations managed</div>
        </div>

        <!-- Scheduled Sessions Card -->
        <div class="card">
            <h3>Scheduled</h3>
            <div class="stat-value stat-small"><?= $scheduledSessions ?></div>
            <div class="stat-label">ready for presentations</div>
        </div>

        <!-- Completed Sessions Card -->
        <div class="card">
            <h3>Completed</h3>
            <div class="stat-value stat-small"><?= $completedSessions ?></div>
            <div class="stat-label">sessions concluded</div>
        </div>

        <!-- Pending Sessions Card -->
        <div class="card">
            <h3>Pending</h3>
            <div class="stat-value stat-small" style="color: var(--warning);"><?= $pendingSessions ?></div>
            <div class="stat-label">awaiting scheduling</div>
        </div>

    </div>

    <!-- Main Content Grid -->
    <div class="grid-2">

        <!-- Upcoming Presentations Card -->
        <div class="card card-lg">
            <h3>Upcoming Presentations</h3>
            <?php if (!empty($upcomingSessions)): ?>
            <div class="table-responsive">
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
                            <td><span class="status-badge scheduled"><?= htmlspecialchars($session['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 16px;">
                <a href="<?= app_url('project_coordinator/presentations.php') ?>" class="btn btn-primary btn-small">
                    <span style="font-family: 'Material Symbols Outlined';">arrow_forward</span>
                    View All Sessions
                </a>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">event</div>
                <p>No upcoming presentation sessions</p>
                <a href="<?= app_url('project_coordinator/presentations.php') ?>" class="btn btn-primary btn-small" style="margin-top: 12px;">
                    <span style="font-family: 'Material Symbols Outlined';">add</span>
                    Create New Session
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sessions Needing Attention Card -->
        <div class="card card-lg">
            <h3>Sessions Needing Attention</h3>
            <?php if (!empty($sessionsNeedingAttention)): ?>
            <div class="table-responsive">
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
                                <span class="badge-primary"><?= (int)$session['panel_members_count'] ?> assigned</span>
                            </td>
                            <td>
                                <a href="<?= app_url('project_coordinator/assign_panel.php?id=' . urlencode($session['id'])) ?>" 
                                   class="btn btn-secondary btn-small">
                                   Manage
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">check_circle</div>
                <p>All sessions are properly configured</p>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Recent Activity Card -->
    <div class="card card-lg" style="margin-bottom: 24px;">
        <h3>Recent Activity</h3>
        <?php if (!empty($recentActivity)): ?>
        <div style="list-style: none; padding: 0; margin: 0;">
            <?php foreach ($recentActivity as $activity): ?>
            <div style="padding: 12px 0; border-bottom: 1px solid #e8eaed; display: flex; align-items: flex-start; gap: 12px;">
                <div style="font-family: 'Material Symbols Outlined'; flex-shrink: 0; margin-top: 2px; color: #999;">info</div>
                <div style="flex: 1;">
                    <div style="font-weight: 500; font-size: 0.9rem;">
                        <?= htmlspecialchars($activity['action']) ?>
                    </div>
                    <small style="color: #999; display: block; margin-top: 2px;">
                        <?= htmlspecialchars($activity['description'] ?? '') ?>
                    </small>
                    <small style="color: #bbb; display: block; margin-top: 4px;">
                        <?= date('M d, Y H:i', strtotime($activity['created_at'])) ?>
                    </small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state" style="padding: 20px;">
            <p>No recent activity</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Quick Actions Section -->
    <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e8eaed; margin-bottom: 24px;">
        <h3 style="font-size: 0.95rem; color: #666; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px; font-weight: 600;">Quick Actions</h3>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="<?= app_url('project_coordinator/presentations.php') ?>" class="btn btn-primary">
                <span style="font-family: 'Material Symbols Outlined';">event</span>
                View All Sessions
            </a>
            <a href="<?= app_url('project_coordinator/create_session.php') ?>" class="btn btn-primary">
                <span style="font-family: 'Material Symbols Outlined';">add</span>
                Create New Session
            </a>
            <a href="<?= app_url('project_coordinator/assignments.php') ?>" class="btn btn-secondary">
                <span style="font-family: 'Material Symbols Outlined';">group_add</span>
                Manage Panel Assignments
            </a>
            <a href="<?= app_url('project_coordinator/schedule.php') ?>" class="btn btn-secondary">
                <span style="font-family: 'Material Symbols Outlined';">schedule</span>
                View Schedule
            </a>
            <a href="<?= app_url('auth/logout.php') ?>" class="btn" style="background: #f5f5f5; color: var(--on-surface);">
                <span style="font-family: 'Material Symbols Outlined';">logout</span>
                Logout
            </a>
        </div>
    </div>

    <!-- Footer -->
    <div style="text-align: center; margin-top: 64px; padding-top: 24px; border-top: 1px solid #e8eaed; color: #999; font-size: 0.85rem;">
        <p>Smart Instructor Coordination and Workload Management System<br>
        University of Colombo School of Computing</p>
    </div>

</div>

</body>
</html>