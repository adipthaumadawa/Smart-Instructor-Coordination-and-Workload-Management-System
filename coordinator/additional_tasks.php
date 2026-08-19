<?php
/**
 * Coordinator - Additional Task Requests
 * Smart Instructor Coordination and Workload Management System
 *
 * Flow: coordinator enters the task details and searches for available
 * instructors for that date/time. Selecting an instructor from the results
 * immediately creates the task request AND assigns it to that instructor in
 * one step (single POST) — there is no separate "create" then "assign" step.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_COORDINATOR);

$pageTitle = "Additional Tasks";

$allowedUrgency = ['Low', 'Medium', 'High', 'Urgent'];

// Coming from the Availability page's "Assign Task" menu carries the chosen
// instructor along as a hint. It pre-fills nothing in the DB by itself — the
// coordinator still has to enter the date/time and search, so real
// conflict/leave checks always run before anything is assigned.
$preferredInstructorId = (int)($_GET['preferred_instructor_id'] ?? $_GET['instructor_id'] ?? 0);
$preferredInstructorName = trim((string)($_GET['instructor_name'] ?? ''));

/**
 * Validates the shared task-detail fields (used by both the search step
 * and the final assign step). Returns [errors[], cleanValues[]].
 */
function validateTaskFields(array $data, PDO $pdo): array {
    global $allowedUrgency;
    $errors = [];

    $title         = trim((string)($data['title'] ?? ''));
    $description   = trim((string)($data['description'] ?? ''));
    $taskTypeId    = (int)($data['task_type_id'] ?? 0);
    $preferredDate = trim((string)($data['preferred_date'] ?? ''));
    $startTime     = trim((string)($data['start_time'] ?? ''));
    $endTime       = trim((string)($data['end_time'] ?? ''));
    $location      = trim((string)($data['location'] ?? ''));
    $urgency       = trim((string)($data['urgency'] ?? ''));
    $streamId      = (int)($data['stream_id'] ?? 0);
    $durationInput = $data['duration_hours'] ?? '';

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
    if (empty($errors)) {
        $checkType = $pdo->prepare("SELECT id FROM task_types WHERE id = ? AND is_presentation = 0");
        $checkType->execute([$taskTypeId]);
        if (!$checkType->fetch()) {
            $errors[] = 'The selected task type is not valid.';
        }
    }

    $duration = 2.0;
    if (empty($errors)) {
        $duration = round((strtotime($endTime) - strtotime($startTime)) / 3600, 2);
        if ($duration <= 0) {
            $duration = (float)$durationInput ?: 2;
        }
        if ($duration <= 0 || $duration > 24) {
            $duration = 2;
        }
    }

    return [$errors, [
        'title' => $title, 'description' => $description, 'task_type_id' => $taskTypeId,
        'preferred_date' => $preferredDate, 'start_time' => $startTime, 'end_time' => $endTime,
        'location' => $location, 'urgency' => $urgency, 'stream_id' => $streamId, 'duration' => $duration,
    ]];
}

$formErrors = [];
$clean = [];
$suggestions = [];
$searched = false;

// ---- Step 2: select an instructor from the results -> create + assign in one POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_instructor_id'])) {
    csrf_verify();

    [$formErrors, $clean] = validateTaskFields($_POST, $pdo);
    $instructorId = (int)$_POST['assign_instructor_id'];

    $instructor = null;
    if ($instructorId <= 0) {
        $formErrors[] = 'Please select an instructor to assign.';
    } elseif (empty($formErrors)) {
        $instrStmt = $pdo->prepare("SELECT i.id, i.user_id, u.full_name FROM instructors i JOIN users u ON i.user_id = u.id WHERE i.id = ? AND i.status = 'active'");
        $instrStmt->execute([$instructorId]);
        $instructor = $instrStmt->fetch();

        if (!$instructor) {
            $formErrors[] = 'The selected instructor is no longer available.';
        } elseif (hasTimetableConflict($instructorId, $clean['preferred_date'], $clean['start_time'], $clean['end_time'])
                || hasTaskConflict($instructorId, $clean['preferred_date'], $clean['start_time'], $clean['end_time'])) {
            // Re-checked at submit time in case another coordinator booked this
            // instructor in the moments between search and selection.
            $formErrors[] = 'That instructor was just booked for a conflicting task. Please search again.';
        }
    }

    if (empty($formErrors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO additional_task_requests(title,description,task_type_id,requested_by,preferred_date,start_time,end_time,duration_hours,location,urgency,status,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,'Assigned',NOW())");
            $stmt->execute([
                $clean['title'], $clean['description'], $clean['task_type_id'], $_SESSION['user_id'],
                $clean['preferred_date'], $clean['start_time'], $clean['end_time'], $clean['duration'],
                $clean['location'], $clean['urgency']
            ]);
            $requestId = (int)$pdo->lastInsertId();

            $stmt2 = $pdo->prepare("INSERT INTO task_assignments(additional_task_request_id,task_type_id,instructor_id,assigned_by,assignment_date,scheduled_date,start_time,end_time,duration_hours,location,status,created_at) VALUES(?,?,?,?,CURDATE(),?,?,?,?,?,'Assigned',NOW())");
            $stmt2->execute([
                $requestId, $clean['task_type_id'], $instructorId, $_SESSION['user_id'],
                $clean['preferred_date'], $clean['start_time'], $clean['end_time'],
                $clean['duration'], $clean['location']
            ]);

            $pdo->commit();

            createNotification(
                $instructor['user_id'],
                'New Task Assigned',
                "You've been assigned: {$clean['title']} on " . formatDate($clean['preferred_date']) . ' (' . formatTime($clean['start_time']) . ' - ' . formatTime($clean['end_time']) . ').',
                'task',
                $requestId
            );

            logActivity($_SESSION['user_id'], 'Assign Additional Task', "Assigned '{$clean['title']}' to {$instructor['full_name']}");
            $_SESSION['success'] = "Task assigned to {$instructor['full_name']} and they've been notified.";
            header('Location: additional_tasks.php');
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('Additional task assignment failed: ' . $e->getMessage());
            $formErrors[] = 'Something went wrong while assigning the task. Please try again.';
        }
    }

    $_SESSION['form_errors'] = $formErrors;
    $_SESSION['old_input'] = $_POST;
    header('Location: additional_tasks.php');
    exit;
}

// ---- Step 1: search for available instructors (read-only, GET) ----
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['title'])) {
    $searched = true;
    [$formErrors, $clean] = validateTaskFields($_GET, $pdo);
    $preferredInstructorId = (int)($_GET['preferred_instructor_id'] ?? $preferredInstructorId);

    if (empty($formErrors)) {
        $suggestions = getSmartSuggestions(
            $clean['task_type_id'] ?: null,
            $clean['preferred_date'],
            $clean['start_time'],
            $clean['end_time'],
            $clean['stream_id'] ?: null,
            8
        );

        // Bubble the requested instructor to the top of the results if they
        // made the cut, so the coordinator doesn't have to hunt for them.
        if ($preferredInstructorId > 0 && !empty($suggestions)) {
            usort($suggestions, function ($a, $b) use ($preferredInstructorId) {
                $aMatch = ((int)$a['instructor_id'] === $preferredInstructorId) ? 0 : 1;
                $bMatch = ((int)$b['instructor_id'] === $preferredInstructorId) ? 0 : 1;
                return $aMatch <=> $bMatch;
            });
        }
    }
}

if (empty($clean)) {
    $formErrors = $_SESSION['form_errors'] ?? $formErrors;
    $clean = $_SESSION['old_input'] ?? [];
}
unset($_SESSION['form_errors'], $_SESSION['old_input']);

$taskTypes = $pdo->query("SELECT * FROM task_types WHERE is_presentation=0 ORDER BY name")->fetchAll();
$streams = $pdo->query("SELECT * FROM academic_streams ORDER BY name")->fetchAll();
$tasks = $pdo->query("
    SELECT atr.*, u.full_name as requested_by_name, tt.name as task_type,
           iu.full_name as assigned_instructor_name
    FROM additional_task_requests atr
    JOIN users u ON atr.requested_by = u.id
    JOIN task_types tt ON atr.task_type_id = tt.id
    LEFT JOIN task_assignments ta ON ta.additional_task_request_id = atr.id
    LEFT JOIN instructors i ON ta.instructor_id = i.id
    LEFT JOIN users iu ON i.user_id = iu.id
    ORDER BY atr.created_at DESC
")->fetchAll();

include __DIR__ . '/../includes/header.php';

function taskFieldValue($clean, $key) {
    return htmlspecialchars((string)($clean[$key] ?? ''));
}
?>

            <div class="page-toolbar">
                <div>
                    <h1>Additional Task Requests</h1>
                    <p>Enter task details, find available instructors, and assign one directly.</p>
                </div>
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

            <?php if ($preferredInstructorId > 0 && !$searched): ?>
                <div class="alert alert-info">
                    Assigning for <strong><?= htmlspecialchars($preferredInstructorName ?: 'the selected instructor') ?></strong> — enter the task date &amp; time below and search to confirm they're free for that slot.
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header"><h5>Task Details</h5></div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <?php if ($preferredInstructorId > 0): ?>
                            <input type="hidden" name="preferred_instructor_id" value="<?= (int)$preferredInstructorId ?>">
                        <?php endif; ?>
                        <div class="col-md-4">
                            <label class="form-label">Task Title</label>
                            <input name="title" class="form-control" placeholder="Task title / lecturer request" maxlength="150" value="<?= taskFieldValue($clean, 'title') ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Task Type</label>
                            <select name="task_type_id" class="form-select" required>
                                <option value="">Select type</option>
                                <?php foreach ($taskTypes as $t): ?>
                                    <option value="<?= (int)$t['id'] ?>" <?= (isset($clean['task_type_id']) && (int)$clean['task_type_id'] === (int)$t['id']) ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Preferred Date</label>
                            <input type="date" name="preferred_date" class="form-control" value="<?= taskFieldValue($clean, 'preferred_date') ?>" required>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Start</label>
                            <input type="time" name="start_time" class="form-control" value="<?= taskFieldValue($clean, 'start_time') ?>" required>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">End</label>
                            <input type="time" name="end_time" class="form-control" value="<?= taskFieldValue($clean, 'end_time') ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Urgency</label>
                            <select name="urgency" class="form-select">
                                <?php foreach ($allowedUrgency as $u): ?>
                                    <option <?= (isset($clean['urgency']) && $clean['urgency'] === $u) ? 'selected' : '' ?>><?= $u ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Location</label>
                            <input name="location" class="form-control" placeholder="Location" maxlength="150" value="<?= taskFieldValue($clean, 'location') ?>">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Hours</label>
                            <input type="number" step="0.25" min="0.25" max="24" name="duration_hours" class="form-control" value="<?= $clean['duration_hours'] ?? '2' ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Preferred Stream</label>
                            <select name="stream_id" class="form-select">
                                <option value="">Any Stream</option>
                                <?php foreach ($streams as $s): ?>
                                    <option value="<?= (int)$s['id'] ?>" <?= (isset($clean['stream_id']) && (int)$clean['stream_id'] === (int)$s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Description</label>
                            <input name="description" class="form-control" placeholder="Description" maxlength="500" value="<?= taskFieldValue($clean, 'description') ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <span class="ui-dot" aria-hidden="true"></span>Find Available Instructors
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($searched && empty($formErrors)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5>Available Instructors</h5>
                        <span class="text-muted small">Sorted by lowest workload</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($suggestions)): ?>
                            <div class="alert alert-warning">No available instructors found for this date/time. Adjust the details and search again.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Instructor</th>
                                            <th>Employee ID</th>
                                            <th>Stream</th>
                                            <th>Current Workload (hrs)</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($suggestions as $sug): ?>
                                        <?php $isPreferred = $preferredInstructorId > 0 && (int)$sug['instructor_id'] === $preferredInstructorId; ?>
                                        <tr<?= $isPreferred ? ' style="background:var(--soft);"' : '' ?>>
                                            <td data-label="Instructor">
                                                <strong><?= htmlspecialchars($sug['name']) ?></strong>
                                                <?php if ($isPreferred): ?><span class="badge bg-primary" style="margin-left:6px;">Requested</span><?php endif; ?>
                                                <br><span class="small text-muted"><?= htmlspecialchars($sug['designation']) ?></span>
                                            </td>
                                            <td data-label="Employee ID"><?= htmlspecialchars($sug['employee_id']) ?></td>
                                            <td data-label="Stream"><?= htmlspecialchars($sug['stream']) ?></td>
                                            <td data-label="Workload">
                                                <span class="badge <?= $sug['current_workload'] > 30 ? 'bg-danger' : ($sug['current_workload'] > 15 ? 'bg-warning' : 'bg-success') ?>">
                                                    <?= htmlspecialchars((string)$sug['current_workload']) ?>
                                                </span>
                                            </td>
                                            <td data-label="Action" class="text-end">
                                                <form method="post" style="display:inline-block;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="title" value="<?= taskFieldValue($clean, 'title') ?>">
                                                    <input type="hidden" name="description" value="<?= taskFieldValue($clean, 'description') ?>">
                                                    <input type="hidden" name="task_type_id" value="<?= (int)($clean['task_type_id'] ?? 0) ?>">
                                                    <input type="hidden" name="preferred_date" value="<?= taskFieldValue($clean, 'preferred_date') ?>">
                                                    <input type="hidden" name="start_time" value="<?= taskFieldValue($clean, 'start_time') ?>">
                                                    <input type="hidden" name="end_time" value="<?= taskFieldValue($clean, 'end_time') ?>">
                                                    <input type="hidden" name="location" value="<?= taskFieldValue($clean, 'location') ?>">
                                                    <input type="hidden" name="urgency" value="<?= taskFieldValue($clean, 'urgency') ?>">
                                                    <input type="hidden" name="duration_hours" value="<?= taskFieldValue($clean, 'duration') ?>">
                                                    <input type="hidden" name="assign_instructor_id" value="<?= (int)$sug['instructor_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Assign this task to <?= htmlspecialchars(addslashes($sug['name'])) ?>?')">
                                                        Select &amp; Assign
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="alert alert-info" style="margin-top:14px;">
                                Selecting an instructor immediately creates the task request, assigns it to them, and sends them a notification — no further steps needed.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

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
                                    <th>Assigned To</th>
                                    <th>Date/Time</th>
                                    <th>Urgency</th>
                                    <th>Status</th>
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
                                    <td data-label="Assigned To"><?= $task['assigned_instructor_name'] ? htmlspecialchars($task['assigned_instructor_name']) : '<span class="text-muted">— Unassigned —</span>' ?></td>
                                    <td data-label="Date/Time"><?= formatDate($task['preferred_date']) ?><br><span class="small text-muted"><?= formatTime($task['start_time']) ?> - <?= formatTime($task['end_time']) ?></span></td>
                                    <td data-label="Urgency"><?= getStatusBadge($task['urgency']) ?></td>
                                    <td data-label="Status"><?= getStatusBadge($task['status']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>