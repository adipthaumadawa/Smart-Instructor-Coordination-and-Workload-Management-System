<?php
/**
 * Instructor - My Tasks
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

// Handle "Accept task" action BEFORE header/navbar output
if (isset($_GET['accept']) && is_numeric($_GET['accept'])) {
    $taskId = (int)$_GET['accept'];
    $stmt = $pdo->prepare("SELECT * FROM task_assignments WHERE id = ? AND instructor_id = ? AND status = 'Assigned'");
    $stmt->execute([$taskId, $instructorId]);
    $task = $stmt->fetch();

    if ($task) {
        $upd = $pdo->prepare("UPDATE task_assignments SET status = 'Accepted' WHERE id = ?");
        $upd->execute([$taskId]);
        logActivity($_SESSION['user_id'] ?? null, 'Accept Task', "Accepted task assignment ID: {$taskId}");
        $_SESSION['success'] = 'Task accepted successfully.';
    } else {
        $_SESSION['error'] = 'Task not found or cannot be accepted.';
    }
    header('Location: ' . app_url('instructor/my_tasks.php'));
    exit;
}

// Filter (All / Upcoming / Completed)
$filter = $_GET['filter'] ?? 'upcoming';
$allowedFilters = ['all', 'upcoming', 'completed'];
if (!in_array($filter, $allowedFilters, true)) { $filter = 'upcoming'; }

$sql = "
    SELECT ta.*, tt.name AS type_name, tt.is_presentation,
           COALESCE(atr.title, tt.name, 'Academic Task') AS task_title
    FROM task_assignments ta
    LEFT JOIN task_types tt ON ta.task_type_id = tt.id
    LEFT JOIN additional_task_requests atr ON ta.additional_task_request_id = atr.id
    WHERE ta.instructor_id = :iid
";
if ($filter === 'upcoming') {
    $sql .= " AND ta.scheduled_date >= CURDATE() AND ta.status IN ('Assigned','Accepted')";
} elseif ($filter === 'completed') {
    $sql .= " AND ta.status = 'Completed'";
}
$sql .= " ORDER BY ta.scheduled_date ASC, ta.start_time ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([':iid' => $instructorId]);
$tasks = $stmt->fetchAll();

$pageTitle = 'My Tasks';
include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1>My Tasks</h1>
                    <p>All academic and additional tasks assigned to you.</p>
                </div>
                <a href="<?= app_url('instructor/replacement_request.php') ?>" class="btn btn-outline-primary">
                    <span class="ui-dot" aria-hidden="true"></span>
                    Request Replacement
                </a>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <div class="card" style="margin-bottom:16px;">
                <div class="card-body d-flex gap-2 flex-wrap">
                    <a href="?filter=upcoming" class="btn btn-sm <?= $filter === 'upcoming' ? 'btn-primary' : 'btn-outline-secondary' ?>">Upcoming</a>
                    <a href="?filter=completed" class="btn btn-sm <?= $filter === 'completed' ? 'btn-primary' : 'btn-outline-secondary' ?>">Completed</a>
                    <a href="?filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>Task List</h5>
                    <span class="text-muted small"><?= count($tasks) ?> task(s)</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Duration</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tasks)): ?>
                                    <tr><td colspan="8" class="text-muted">No tasks found for this filter.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($tasks as $t): ?>
                                    <tr>
                                        <td data-label="Task">
                                            <strong><?= htmlspecialchars($t['task_title']) ?></strong>
                                            <?php if ((int)$t['is_presentation'] === 1): ?>
                                                <div class="text-muted small">Presentation panel (not counted in workload)</div>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Type"><?= htmlspecialchars($t['type_name'] ?? 'N/A') ?></td>
                                        <td data-label="Date"><?= formatDate($t['scheduled_date']) ?></td>
                                        <td data-label="Time"><?= formatTime($t['start_time']) ?> - <?= formatTime($t['end_time']) ?></td>
                                        <td data-label="Duration"><?= htmlspecialchars($t['duration_hours']) ?> hrs</td>
                                        <td data-label="Location"><?= htmlspecialchars($t['location'] ?: 'N/A') ?></td>
                                        <td data-label="Status"><?= getStatusBadge($t['status']) ?></td>
                                        <td data-label="Actions" class="text-end action-cell">
                                            <?php if ($t['status'] === 'Assigned'): ?>
                                                <a href="?accept=<?= (int)$t['id'] ?>" class="btn btn-sm btn-success">Accept</a>
                                            <?php endif; ?>
                                            <?php if (in_array($t['status'], ['Assigned', 'Accepted'], true) && strtotime($t['scheduled_date']) >= strtotime(date('Y-m-d'))): ?>
                                                <a href="<?= app_url('instructor/replacement_request.php?task_id=' . (int)$t['id']) ?>" class="btn btn-sm btn-outline-danger">Replacement</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
