<?php
/**
 * Coordinator - Urgency Replacements
 * Smart Instructor Coordination and Workload Management System
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_COORDINATOR);
$pageTitle = "Urgency Replacements";

$formErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $task = (int)($_POST['task_assignment_id'] ?? 0);
    $new = (int)($_POST['new_instructor_id'] ?? 0);
    $reason = trim((string)($_POST['reason'] ?? '')) ?: 'Urgent replacement';

    if ($task <= 0) {
        $formErrors[] = 'Please select the affected task.';
    }
    if ($new <= 0) {
        $formErrors[] = 'Please select a new instructor.';
    }

    // Confirm both referenced records actually exist and are in a valid state
    // before writing — the posted IDs come from <select> values, which a
    // client can tamper with.
    if (empty($formErrors)) {
        $taskCheck = $pdo->prepare("SELECT id, instructor_id FROM task_assignments WHERE id = ? AND status IN ('Assigned','Accepted')");
        $taskCheck->execute([$task]);
        $taskRow = $taskCheck->fetch();
        if (!$taskRow) {
            $formErrors[] = 'The selected task is no longer available for replacement.';
        }

        $instructorCheck = $pdo->prepare("SELECT id FROM instructors WHERE id = ? AND status = 'active'");
        $instructorCheck->execute([$new]);
        if (!$instructorCheck->fetch()) {
            $formErrors[] = 'The selected instructor is not active.';
        }

        if (empty($formErrors) && (int)$taskRow['instructor_id'] === $new) {
            $formErrors[] = 'That instructor is already assigned to this task.';
        }
    }

    if (empty($formErrors)) {
        try {
            $pdo->beginTransaction();

            $pdo->prepare("INSERT INTO urgency_replacements(task_assignment_id,handled_by_coordinator_id,new_instructor_id,reason,status,created_at) VALUES(?,?,?,?, 'Handled', NOW())")
                ->execute([$task, $_SESSION['user_id'], $new, $reason]);

            $pdo->prepare("UPDATE task_assignments SET instructor_id=?, status='Assigned' WHERE id=?")
                ->execute([$new, $task]);

            $pdo->commit();

            logActivity($_SESSION['user_id'], 'Urgency Replacement', "Task $task reassigned to instructor $new");
            $_SESSION['success'] = 'Urgent replacement saved.';
            header('Location: urgency_replacements.php');
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('Urgency replacement failed: ' . $e->getMessage());
            $formErrors[] = 'Something went wrong while saving the replacement. Please try again.';
        }
    }

    $_SESSION['form_errors'] = $formErrors;
    $_SESSION['old_input'] = $_POST;
    header('Location: urgency_replacements.php');
    exit;
}

$formErrors = $_SESSION['form_errors'] ?? [];
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['old_input']);

$tasks = $pdo->query("SELECT ta.id, tt.name task_type, u.full_name instructor_name, ta.scheduled_date, ta.start_time, ta.end_time FROM task_assignments ta JOIN task_types tt ON ta.task_type_id=tt.id JOIN instructors i ON ta.instructor_id=i.id JOIN users u ON i.user_id=u.id WHERE ta.status IN('Assigned','Accepted') ORDER BY ta.scheduled_date DESC LIMIT 50")->fetchAll();
$instructors = getAllActiveInstructors();
$history = $pdo->query("SELECT ur.*, ta.scheduled_date, u.full_name new_name FROM urgency_replacements ur JOIN task_assignments ta ON ur.task_assignment_id=ta.id JOIN instructors i ON ur.new_instructor_id=i.id JOIN users u ON i.user_id=u.id ORDER BY ur.created_at DESC LIMIT 20")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1>Urgency Replacements</h1>
                    <p>Immediately reassign a task to a new instructor when an urgent conflict arises.</p>
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

            <div class="card">
                <div class="card-header"><h5>Assign Urgent Replacement</h5></div>
                <div class="card-body">
                    <form method="post" class="row g-3">
                        <?= csrf_field() ?>
                        <div class="col-md-4">
                            <label class="form-label">Affected Task</label>
                            <select name="task_assignment_id" class="form-select" required>
                                <option value="">Select task</option>
                                <?php foreach ($tasks as $t): ?>
                                    <option value="<?= (int)$t['id'] ?>" <?= (isset($old['task_assignment_id']) && (int)$old['task_assignment_id'] === (int)$t['id']) ? 'selected' : '' ?>><?= htmlspecialchars($t['task_type'] . ' - ' . $t['instructor_name'] . ' - ' . $t['scheduled_date']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">New Instructor</label>
                            <select name="new_instructor_id" class="form-select" required>
                                <option value="">Select instructor</option>
                                <?php foreach ($instructors as $i): ?>
                                    <option value="<?= (int)$i['id'] ?>" <?= (isset($old['new_instructor_id']) && (int)$old['new_instructor_id'] === (int)$i['id']) ? 'selected' : '' ?>><?= htmlspecialchars($i['display_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Reason</label>
                            <input name="reason" class="form-control" placeholder="Reason for replacement" maxlength="255" value="<?= htmlspecialchars($old['reason'] ?? '') ?>">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-danger w-100" onclick="return confirm('Reassign this task now?')">
                                <span class="ui-dot" aria-hidden="true"></span>Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>Replacement History</h5>
                    <span class="text-muted small"><?= count($history) ?> records</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Task ID</th>
                                    <th>New Instructor</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($history)): ?>
                                    <tr><td colspan="5" class="text-muted">No urgency replacements recorded yet.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($history as $h): ?>
                                <tr>
                                    <td data-label="Task ID">#<?= (int)$h['task_assignment_id'] ?></td>
                                    <td data-label="New Instructor"><strong><?= htmlspecialchars($h['new_name']) ?></strong></td>
                                    <td data-label="Reason"><?= htmlspecialchars($h['reason']) ?></td>
                                    <td data-label="Status"><?= getStatusBadge($h['status']) ?></td>
                                    <td data-label="Date"><?= formatDate($h['created_at']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>