<?php
/**
 * Project Coordinator - Panel Assignments overview
 * Smart Instructor Coordination and Workload Management System
 *
 * One place to see who sits on which presentation panel and which sessions
 * still need members. Adding / removing members happens in panel.php, which
 * checks leave, timetable and task conflicts.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';

checkRole(ROLE_PROJECT_COORDINATOR);

$minPanelSize = 3;

$view = $_GET['view'] ?? 'upcoming';
if (!in_array($view, ['upcoming', 'incomplete', 'past'], true)) { $view = 'upcoming'; }

$sql = "
    SELECT ps.id, ps.title, ps.course_code, ps.session_date, ps.start_time, ps.end_time, ps.venue, ps.status,
           COUNT(ppm.id) AS panel_count,
           GROUP_CONCAT(CONCAT(u.full_name, ' (', ppm.role_in_panel, ')')
                        ORDER BY FIELD(ppm.role_in_panel,'Chair','Examiner','Member') SEPARATOR '||') AS members
    FROM presentation_sessions ps
    LEFT JOIN presentation_panel_members ppm ON ppm.presentation_session_id = ps.id
    LEFT JOIN instructors i ON ppm.instructor_id = i.id
    LEFT JOIN users u ON i.user_id = u.id
    WHERE 1=1
";
if ($view === 'past') {
    $sql .= " AND (ps.session_date < CURDATE() OR ps.status = 'Completed') AND ps.status <> 'Cancelled'";
} else {
    $sql .= " AND ps.status = 'Scheduled' AND ps.session_date >= CURDATE()";
}
$sql .= " GROUP BY ps.id, ps.title, ps.course_code, ps.session_date, ps.start_time, ps.end_time, ps.venue, ps.status";
if ($view === 'incomplete') {
    $sql .= " HAVING COUNT(ppm.id) < " . (int)$minPanelSize;
}
$sql .= ($view === 'past')
    ? " ORDER BY ps.session_date DESC, ps.start_time DESC"
    : " ORDER BY ps.session_date ASC, ps.start_time ASC";

$sessions = $pdo->query($sql)->fetchAll();

$pageTitle = 'Panel Assignments';
include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1>Panel Assignments</h1>
                    <p>See who is on each presentation panel. A complete panel has at least <?= (int)$minPanelSize ?> members.</p>
                </div>
                <a href="<?= app_url('project_coordinator/sessions.php') ?>" class="btn btn-outline-primary">Sessions &amp; Venues</a>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <div class="card" style="margin-bottom:16px;">
                <div class="card-body d-flex gap-2 flex-wrap">
                    <a href="?view=upcoming" class="btn btn-sm <?= $view === 'upcoming' ? 'btn-primary' : 'btn-outline-secondary' ?>">Upcoming</a>
                    <a href="?view=incomplete" class="btn btn-sm <?= $view === 'incomplete' ? 'btn-primary' : 'btn-outline-secondary' ?>">Needs Panel</a>
                    <a href="?view=past" class="btn btn-sm <?= $view === 'past' ? 'btn-primary' : 'btn-outline-secondary' ?>">Past</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>Sessions &amp; Panels</h5>
                    <span class="text-muted small"><?= count($sessions) ?> session(s)</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr><th>Session</th><th>Date &amp; Time</th><th>Venue</th><th>Panel Members</th><th>Panel Status</th><th class="text-end">Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($sessions)): ?>
                                    <tr><td colspan="6" class="text-muted">No sessions found for this filter.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($sessions as $s):
                                    $count = (int)$s['panel_count'];
                                    if ($count >= $minPanelSize)      { $badge = '<span class="badge bg-success">Complete</span>'; }
                                    elseif ($count === 0)             { $badge = '<span class="badge bg-danger">No panel</span>'; }
                                    else                              { $badge = '<span class="badge bg-warning text-dark">' . $count . '/' . (int)$minPanelSize . '</span>'; }
                                    $members = $s['members'] !== null ? explode('||', $s['members']) : [];
                                ?>
                                    <tr>
                                        <td data-label="Session">
                                            <strong><?= htmlspecialchars($s['title']) ?></strong><br>
                                            <span class="text-muted small"><?= htmlspecialchars($s['course_code'] ?: 'N/A') ?></span>
                                        </td>
                                        <td data-label="Date &amp; Time"><?= formatDate($s['session_date']) ?><br><span class="text-muted small"><?= formatTime($s['start_time']) ?> - <?= formatTime($s['end_time']) ?></span></td>
                                        <td data-label="Venue"><?= htmlspecialchars($s['venue'] ?: 'N/A') ?></td>
                                        <td data-label="Panel Members">
                                            <?php if (empty($members)): ?>
                                                <span class="text-muted">None assigned</span>
                                            <?php else: foreach ($members as $m): ?>
                                                <div><?= htmlspecialchars($m) ?></div>
                                            <?php endforeach; endif; ?>
                                        </td>
                                        <td data-label="Panel Status"><?= $badge ?></td>
                                        <td data-label="Actions" class="text-end action-cell">
                                            <a href="<?= app_url('project_coordinator/panel.php?session_id=' . (int)$s['id']) ?>" class="btn btn-sm btn-outline-primary"><?= $view === 'past' ? 'View' : 'Manage Panel' ?></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>