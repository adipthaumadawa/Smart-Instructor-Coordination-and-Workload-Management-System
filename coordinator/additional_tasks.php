<?php
/**
 * Coordinator - Additional Task Requests
 * Smart Instructor Coordination and Workload Management System
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php'; // Required for sic_user_avatar() in topbar
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_COORDINATOR);

$pageTitle = "Additional Tasks";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $title         = trim((string)($_POST['title'] ?? ''));
    $description   = trim((string)($_POST['description'] ?? ''));
    $taskTypeId    = (int)($_POST['task_type_id'] ?? 0);
    $preferredDate = trim((string)($_POST['preferred_date'] ?? ''));
    $startTime     = trim((string)($_POST['start_time'] ?? ''));
    $endTime       = trim((string)($_POST['end_time'] ?? ''));
    $location      = trim((string)($_POST['location'] ?? ''));
    $urgency       = trim((string)($_POST['urgency'] ?? ''));
    $durationInput = $_POST['duration_hours'] ?? '';

    $allowedUrgency = ['Low', 'Medium', 'High', 'Urgent'];
    $errors = [];

    if ($title === '' || mb_strlen($title) > 150) {
        $errors[] = 'Task title is required and must be under 150 characters.';
    }
    if ($taskTypeId <= 0) {
        $errors[] = 'Please select a task type.';
    }
    if ($preferredDate === '' || !DateTime::createFromFormat('Y-m-d', $preferredDate)) {
        $errors[] = 'A valid preferred date is required.';
    }
    if ($startTime === '' || !DateTime::createFromFormat('H:i', $startTime)) {
        $errors[] = 'A valid start time is required.';
    }
    if ($endTime === '' || !DateTime::createFromFormat('H:i', $endTime)) {
        $errors[] = 'A valid end time is required.';
    }
    if (!in_array($urgency, $allowedUrgency, true)) {
        $errors[] = 'Please select a valid urgency level.';
    }
    if (empty($errors) && strtotime($endTime) <= strtotime($startTime)) {
        $errors[] = 'End time must be after start time.';
    }

    // Re-check the task type actually exists — option values can be tampered with client-side.
    if (empty($errors)) {
        $checkType = $pdo->prepare("SELECT id FROM task_types WHERE id = ? AND is_presentation = 0");
        $checkType->execute([$taskTypeId]);
        if (!$checkType->fetch()) {
            $errors[] = 'The selected task type is not valid.';
        }
    }

    if (empty($errors)) {
        $duration = round((strtotime($endTime) - strtotime($startTime)) / 3600, 2);
        if ($duration <= 0) {
            $duration = (float)$durationInput ?: 2;
        }
        // Guard against absurd/negative manual duration overrides.
        if ($duration <= 0 || $duration > 24) {
            $duration = 2;
        }

        $stmt = $pdo->prepare("INSERT INTO additional_task_requests(title,description,task_type_id,requested_by,preferred_date,start_time,end_time,duration_hours,location,urgency,status,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,'Pending',NOW())");
        $stmt->execute([
            $title, $description, $taskTypeId, $_SESSION['user_id'],
            $preferredDate, $startTime, $endTime, $duration, $location, $urgency
        ]);

        logActivity($_SESSION['user_id'], 'Create Additional Task', 'Created additional task request: ' . $title);
        $_SESSION['success'] = 'Additional task request created.';
        header('Location: additional_tasks.php');
        exit;
    }

    // Validation failed — keep the entered values so the coordinator doesn't have to retype the form.
    $_SESSION['form_errors'] = $errors;
    $_SESSION['old_input'] = $_POST;
    header('Location: additional_tasks.php');
    exit;
}

$formErrors = $_SESSION['form_errors'] ?? [];
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['old_input']);

$taskTypes = $pdo->query("SELECT * FROM task_types WHERE is_presentation=0 ORDER BY name")->fetchAll();
$tasks = $pdo->query("SELECT atr.*, u.full_name as requested_by_name, tt.name as task_type FROM additional_task_requests atr JOIN users u ON atr.requested_by=u.id JOIN task_types tt ON atr.task_type_id=tt.id ORDER BY atr.created_at DESC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1>Additional Task Requests</h1>
                    <p>Create lecturer-requested tasks and track their assignment status.</p>
                </div>
                <a href="assign_task.php" class="btn btn-primary">
                    <span class="ui-dot" aria-hidden="true"></span>Assign Task
                </a>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <?php if (!empty($formErrors)): ?>
                <div class="alert alert-danger">
                    <div>
                        <strong>Please fix the following:</strong>
                        <ul style="margin:6px 0 0 18px; padding:0;">
                            <?php foreach ($formErrors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header"><h5>Create Lecturer Requested Task</h5></div>
                <div class="card-body">
                    <form method="post" class="row g-3">
                        <?= csrf_field() ?>
                        <div class="col-md-4">
                            <label class="form-label">Task Title</label>
                            <input name="title" class="form-control" placeholder="Task title / lecturer request" maxlength="150" value="<?= htmlspecialchars($old['title'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Task Type</label>
                            <select name="task_type_id" class="form-select" required>
                                <option value="">Select type</option>
                                <?php foreach ($taskTypes as $t): ?>
                                    <option value="<?= (int)$t['id'] ?>" <?= (isset($old['task_type_id']) && (int)$old['task_type_id'] === (int)$t['id']) ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Preferred Date</label>
                            <input type="date" name="preferred_date" class="form-control" value="<?= htmlspecialchars($old['preferred_date'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Start</label>
                            <input type="time" name="start_time" class="form-control" value="<?= htmlspecialchars($old['start_time'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">End</label>
                            <input type="time" name="end_time" class="form-control" value="<?= htmlspecialchars($old['end_time'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Urgency</label>
                            <select name="urgency" class="form-select">
                                <?php foreach (['Low', 'Medium', 'High', 'Urgent'] as $u): ?>
                                    <option <?= (isset($old['urgency']) && $old['urgency'] === $u) ? 'selected' : '' ?>><?= $u ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Location</label>
                            <input name="location" class="form-control" placeholder="Location" maxlength="150" value="<?= htmlspecialchars($old['location'] ?? '') ?>">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Hours</label>
                            <input type="number" step="0.25" min="0.25" max="24" name="duration_hours" class="form-control" value="<?= htmlspecialchars($old['duration_hours'] ?? '2') ?>">
                        </div>
                        <div class="col-md-9">
                            <label class="form-label">Description</label>
                            <input name="description" class="form-control" placeholder="Description" maxlength="500" value="<?= htmlspecialchars($old['description'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary">
                                <span class="ui-dot" aria-hidden="true"></span>Create Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>Task Requests</h5>
                    <span class="text-muted small"><?= count($tasks) ?> requests</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Requested By</th>
                                    <th>Date/Time</th>
                                    <th>Urgency</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tasks)): ?>
                                    <tr><td colspan="7" class="text-muted">No additional task requests yet.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td data-label="Title"><strong><?= htmlspecialchars($task['title']) ?></strong></td>
                                    <td data-label="Type"><?= htmlspecialchars($task['task_type']) ?></td>
                                    <td data-label="Requested By"><?= htmlspecialchars($task['requested_by_name']) ?></td>
                                    <td data-label="Date/Time"><?= formatDate($task['preferred_date']) ?><br><span class="small text-muted"><?= formatTime($task['start_time']) ?> - <?= formatTime($task['end_time']) ?></span></td>
                                    <td data-label="Urgency"><?= getStatusBadge($task['urgency']) ?></td>
                                    <td data-label="Status"><?= getStatusBadge($task['status']) ?></td>
                                    <td data-label="Action" class="text-end">
                                        <a class="btn btn-sm btn-outline-success" href="assign_task.php">Assign</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>