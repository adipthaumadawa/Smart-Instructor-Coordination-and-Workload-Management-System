<?php
/**
 * Project Coordinator - Dashboard
 * Smart Instructor Coordination and Workload Management System
<<<<<<< HEAD
=======
 * 
 * Access: Project Coordinator role (Role ID: 6)
 * Manages presentation sessions, panel assignments, and schedules.
>>>>>>> origin/main
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
<<<<<<< HEAD
require_once __DIR__ . '/../includes/dashboard_ui.php';

// Only Project Coordinators can access this dashboard.
=======
require_once __DIR__ . '/../includes/dashboard_ui.php'; // Resolves sic_user_avatar() in navbar.php

// Ensure user is logged in and authorized
requireLogin();
>>>>>>> origin/main
checkRole(ROLE_PROJECT_COORDINATOR);

$pageTitle = 'Project Coordinator Dashboard';
include __DIR__ . '/../includes/header.php';

sic_render_dashboard(
    'Project Coordinator Dashboard',
    'Create presentation sessions, assign evaluation panels, and manage venue bookings.',
    sic_dashboard_cards('project'),
    app_url('project_coordinator/sessions.php'),
    'New Session'
);

include __DIR__ . '/../includes/footer.php';
?>
<<<<<<< HEAD
=======
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
                <span><?= strtoupper(substr($currentUser['full_name'] ?? 'C', 0, 1)) ?></span>
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
    <div class="alert alert-info">
        <i>info</i>
        <div>
            <strong>Sessions Requiring Attention</strong><br>
            <small><?= count($sessionsNeedingAttention) ?> session(s) need panel member assignments or status updates</small>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($pendingSessions > 0): ?>
    <div class="alert alert-warning">
        <i>event</i>
        <div>
            <strong>Pending Presentation Sessions</strong><br>
            <small><?= $pendingSessions ?> session(s) are pending and need to be scheduled</small>
        </div>
    </div>
    <?php endif; ?>

    <!-- Key Statistics -->
    <div class="stats-grid">
        <!-- Total Sessions Card -->
        <div class="stat-card">
            <h3>Total Sessions</h3>
            <div class="stat-value"><?= $totalSessions ?></div>
            <div class="stat-label">all presentations managed</div>
        </div>

        <!-- Scheduled Sessions Card -->
        <div class="stat-card">
            <h3>Scheduled</h3>
            <div class="stat-value"><?= $scheduledSessions ?></div>
            <div class="stat-label">ready for presentations</div>
        </div>

        <!-- Completed Sessions Card -->
        <div class="stat-card">
            <h3>Completed</h3>
            <div class="stat-value"><?= $completedSessions ?></div>
            <div class="stat-label">sessions concluded</div>
        </div>

        <!-- Pending Sessions Card -->
        <div class="stat-card">
            <h3>Pending</h3>
            <div class="stat-value"><?= $pendingSessions ?></div>
            <div class="stat-label">awaiting scheduling</div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="dashboard-grid">

        <!-- Upcoming Presentations Card -->
        <div class="card">
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
                            <td><span><?= htmlspecialchars($session['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <a href="<?= app_url('project_coordinator/presentations.php') ?>">
                    <i>arrow_forward</i> View All Sessions
                </a>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i>event</i>
                <p>No upcoming presentation sessions</p>
                <a href="<?= app_url('project_coordinator/create_session.php') ?>" class="btn">
                    <i>add</i> Create New Session
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sessions Needing Attention Card -->
        <div class="card">
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
                                <span><?= (int)$session['panel_members_count'] ?> assigned</span>
                            </td>
                            <td>
                                <a href="<?= app_url('project_coordinator/assign_panel.php?id=' . urlencode($session['id'])) ?>" class="btn-sm">
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
                <i>check_circle</i>
                <p>All sessions are properly configured</p>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Recent Activity Card -->
    <div class="card">
        <h3>Recent Activity</h3>
        <?php if (!empty($recentActivity)): ?>
        <ul class="activity-list">
            <?php foreach ($recentActivity as $activity): ?>
            <li>
                <i>info</i>
                <div>
                    <div class="activity-title"><?= htmlspecialchars($activity['action']) ?></div>
                    <small><?= htmlspecialchars($activity['description'] ?? '') ?></small>
                    <small class="text-muted"><?= date('M d, Y H:i', strtotime($activity['created_at'])) ?></small>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <div class="empty-state">
            <p>No recent activity</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Quick Actions Section -->
    <div class="quick-actions">
        <h3>Quick Actions</h3>
        <div class="action-buttons">
            <a href="<?= app_url('project_coordinator/presentations.php') ?>" class="btn">
                <i>event</i> View All Sessions
            </a>
            <a href="<?= app_url('project_coordinator/create_session.php') ?>" class="btn">
                <i>add</i> Create New Session
            </a>
            <a href="<?= app_url('project_coordinator/assignments.php') ?>" class="btn">
                <i>group_add</i> Manage Panel Assignments
            </a>
            <a href="<?= app_url('project_coordinator/schedule.php') ?>" class="btn">
                <i>schedule</i> View Schedule
            </a>
            <a href="<?= app_url('auth/logout.php') ?>" class="btn btn-danger">
                <i>logout</i> Logout
            </a>
        </div>
    </div>

    <!-- Footer -->
    <div class="dashboard-footer">
        <p>Smart Instructor Coordination and Workload Management System<br>
        University of Colombo School of Computing</p>
    </div>

</main>

</body>
</html>
>>>>>>> origin/main
