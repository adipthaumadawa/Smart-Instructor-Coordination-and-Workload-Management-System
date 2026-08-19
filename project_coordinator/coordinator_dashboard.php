<?php
/**
 * Project Coordinator - Dashboard
 * Smart Instructor Coordination and Workload Management System
 *
 * Access: Project Coordinator role (Role ID: 6)
 * Manages presentation sessions, panel assignments, and schedules.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';

// Check authentication and authorization
requireLogin();
checkRole(ROLE_PROJECT_COORDINATOR);

// Get current user from session (getCurrentUser() is the app's real session helper)
$currentUser = getCurrentUser() ?: [];

// Initialize data variables
$upcomingSessions = [];
$sessionsNeedingAttention = [];
$recentActivity = [];

try {
    // Upcoming sessions (next 5 scheduled presentations)
    $upcomingQuery = "
        SELECT
            ps.id,
            ps.course_code,
            ps.title,
            ps.session_date,
            ps.start_time,
            ps.end_time,
            ps.venue,
            ps.status
        FROM presentation_sessions ps
        WHERE ps.status = 'Scheduled'
            AND ps.session_date >= CURDATE()
        ORDER BY ps.session_date ASC, ps.start_time ASC
        LIMIT 5
    ";
    $result = $pdo->query($upcomingQuery);
    if ($result) {
        $upcomingSessions = $result->fetchAll();
    }

    // Sessions needing attention (scheduled sessions with fewer than 3 panel members)
    $attentionQuery = "
        SELECT
            ps.id,
            ps.course_code,
            ps.title,
            ps.session_date,
            COUNT(ppm.id) AS panel_members_count
        FROM presentation_sessions ps
        LEFT JOIN presentation_panel_members ppm ON ps.id = ppm.presentation_session_id
        WHERE ps.status = 'Scheduled'
        GROUP BY ps.id, ps.course_code, ps.title, ps.session_date
        HAVING panel_members_count < 3
        ORDER BY ps.session_date ASC
        LIMIT 5
    ";
    $result = $pdo->query($attentionQuery);
    if ($result) {
        $sessionsNeedingAttention = $result->fetchAll();
    }

    // Recent activity for this coordinator
    $activityQuery = "
        SELECT action, description, created_at
        FROM activity_logs
        WHERE user_id = :uid
        ORDER BY created_at DESC
        LIMIT 10
    ";
    $stmt = $pdo->prepare($activityQuery);
    $stmt->execute([':uid' => $currentUser['id'] ?? 0]);
    $recentActivity = $stmt->fetchAll();

} catch (Throwable $e) {
    // Log error and continue with defaults
    error_log("Dashboard data loading error: " . $e->getMessage());
}

$pageTitle = 'Project Coordinator Dashboard';
include __DIR__ . '/../includes/header.php';

// Renders the hero header + KPI cards using the app's shared dashboard design system
sic_render_dashboard(
    'Presentation Management Dashboard',
    'Manage final year project presentations and panel assignments',
    sic_dashboard_cards('project'),
    app_url('project_coordinator/sessions.php'),
    'Create New Session'
);
?>

<section class="dash-page" style="padding-top:0;">
    <div class="dash-grid-row1" style="grid-template-columns: 1fr 1fr;">

        <!-- Upcoming Presentations -->
        <div class="d-card">
            <div class="section-head">
                <h2 class="section-title"><?= sic_icon('calendar') ?>Upcoming Presentations</h2>
                <a href="<?= app_url('project_coordinator/sessions.php') ?>" class="section-link"><?= sic_icon('eye') ?>View all</a>
            </div>
            <div class="schedule-list">
                <?php if (empty($upcomingSessions)): ?>
                    <p class="text-muted" style="padding:14px 16px;">No upcoming presentation sessions.</p>
                <?php else: foreach ($upcomingSessions as $s): ?>
                    <div class="sched-item">
                        <div class="sched-time">
                            <span class="sched-time-start"><?= htmlspecialchars(formatTime($s['start_time'])) ?></span>
                            <span class="sched-time-end"><?= htmlspecialchars(formatTime($s['end_time'])) ?></span>
                        </div>
                        <div class="sched-info">
                            <span class="sched-course"><?= htmlspecialchars($s['title']) ?></span>
                            <span class="sched-meta">
                                <?= htmlspecialchars($s['course_code'] ?? '') ?>
                                <?= !empty($s['venue']) ? ' • ' . htmlspecialchars($s['venue']) : '' ?>
                                • <?= htmlspecialchars(formatDate($s['session_date'])) ?>
                            </span>
                        </div>
                        <span class="s-pill s-pill-blue">Scheduled</span>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Sessions Needing Attention -->
        <div class="d-card">
            <div class="section-head">
                <h2 class="section-title"><?= sic_icon('warning') ?>Sessions Needing Attention</h2>
                <a href="<?= app_url('project_coordinator/presentation_panels.php') ?>" class="section-link"><?= sic_icon('eye') ?>Manage panels</a>
            </div>
            <div class="alert-list">
                <?php if (empty($sessionsNeedingAttention)): ?>
                    <p class="text-muted" style="padding:14px 16px;">All scheduled sessions have a full panel assigned.</p>
                <?php else: foreach ($sessionsNeedingAttention as $s): ?>
                    <div class="alert-item">
                        <div class="alert-icon-wrap"><?= sic_icon('warning') ?></div>
                        <div class="alert-info">
                            <span class="alert-course"><?= htmlspecialchars($s['title']) ?></span>
                            <span class="alert-meta">
                                <?= htmlspecialchars($s['course_code'] ?? '') ?>
                                • <?= htmlspecialchars(formatDate($s['session_date'])) ?>
                                • <?= (int)$s['panel_members_count'] ?>/3 panel members
                            </span>
                        </div>
                        <span class="s-pill s-pill-red">Needs Panel</span>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="d-card">
        <div class="section-head">
            <h2 class="section-title"><?= sic_icon('history') ?>Recent Activity</h2>
        </div>
        <div class="schedule-list">
            <?php if (empty($recentActivity)): ?>
                <p class="text-muted" style="padding:14px 16px;">No recent activity.</p>
            <?php else: foreach ($recentActivity as $a): ?>
                <div class="sched-item" style="grid-template-columns: 1fr auto;">
                    <div class="sched-info">
                        <span class="sched-course"><?= htmlspecialchars($a['action']) ?></span>
                        <?php if (!empty($a['description'])): ?>
                            <span class="sched-meta"><?= htmlspecialchars($a['description']) ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="sched-meta"><?= htmlspecialchars(formatDate($a['created_at'], 'd M Y, h:i A')) ?></span>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>