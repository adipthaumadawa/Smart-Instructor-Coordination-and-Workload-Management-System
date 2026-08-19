<?php
/**
 * Coordinator - Urgency Replacements
 * Smart Instructor Coordination and Workload Management System
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_COORDINATOR);

$pageTitle = "Urgency Replacements";

// Handle form submission
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_replacement'])) {
    $task_id = intval($_POST['task_assignment_id'] ?? 0);
    $new_instructor_id = intval($_POST['new_instructor_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $coordinator_user_id = $_SESSION['user_id'] ?? 1;

    if ($task_id > 0 && $new_instructor_id > 0) {
        try {
            $pdo->beginTransaction();

            // 1. Record Urgency Replacement
            $stmt = $pdo->prepare("
                INSERT INTO urgency_replacements (task_assignment_id, handled_by_coordinator_id, new_instructor_id, reason, status)
                VALUES (?, ?, ?, ?, 'Handled')
            ");
            $stmt->execute([$task_id, $coordinator_user_id, $new_instructor_id, $reason]);

            // 2. Reassign the Task Assignment to the New Instructor
            $stmtUpdate = $pdo->prepare("
                UPDATE task_assignments 
                SET instructor_id = ?, notes = CONCAT(IFNULL(notes,''), ' [Urgent replacement assigned]')
                WHERE id = ?
            ");
            $stmtUpdate->execute([$new_instructor_id, $task_id]);

            // 3. Fetch Task and New Instructor Details for Notification
            $stmtTask = $pdo->prepare("
                SELECT ta.*, tt.name AS task_type_name, i.user_id AS new_user_id
                FROM task_assignments ta
                JOIN task_types tt ON ta.task_type_id = tt.id
                JOIN instructors i ON i.id = ?
                WHERE ta.id = ?
            ");
            $stmtTask->execute([$new_instructor_id, $task_id]);
            $taskData = $stmtTask->fetch();

            // 4. Send Notification to New Instructor
            if ($taskData && !empty($taskData['new_user_id'])) {
                $notifTitle = "Urgent Task Assignment";
                $notifMsg = "You have been urgently assigned to a " . $taskData['task_type_name'] . " on " . formatDate($taskData['scheduled_date']) . " (" . date('h:i A', strtotime($taskData['start_time'])) . " - " . date('h:i A', strtotime($taskData['end_time'])) . "). Reason: " . ($reason ?: 'Urgent coordination requirement');
                
                $stmtNotif = $pdo->prepare("
                    INSERT INTO notifications (user_id, title, message, type, is_read, related_id)
                    VALUES (?, ?, ?, 'warning', 0, ?)
                ");
                $stmtNotif->execute([$taskData['new_user_id'], $notifTitle, $notifMsg, $task_id]);
            }

            $pdo->commit();
            $success_msg = "Urgent replacement assigned successfully and notification sent!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = "Failed to assign replacement: " . $e->getMessage();
        }
    } else {
        $error_msg = "Please select a task and a new instructor.";
    }
}

// Fetch all assigned tasks
$tasks = $pdo->query("
    SELECT ta.id, ta.scheduled_date, ta.start_time, ta.end_time, ta.instructor_id,
           tt.name AS task_type_name, u.full_name AS current_instructor_name,
           DAYNAME(ta.scheduled_date) AS day_name
    FROM task_assignments ta
    JOIN task_types tt ON ta.task_type_id = tt.id
    JOIN instructors i ON ta.instructor_id = i.id
    JOIN users u ON i.user_id = u.id
    WHERE ta.status IN ('Assigned', 'Accepted')
    ORDER BY ta.scheduled_date ASC, ta.start_time ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all active instructors
$instructors = $pdo->query("
    SELECT i.id, i.employee_id, u.full_name
    FROM instructors i
    JOIN users u ON i.user_id = u.id
    WHERE i.status = 'active'
    ORDER BY u.full_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch busy records for availability check
$busyTasks = $pdo->query("
    SELECT instructor_id, scheduled_date, start_time, end_time FROM task_assignments WHERE status IN ('Assigned', 'Accepted')
")->fetchAll(PDO::FETCH_ASSOC);

$busyLeaves = $pdo->query("
    SELECT instructor_id, start_date, end_date FROM leave_records WHERE status = 'Approved'
")->fetchAll(PDO::FETCH_ASSOC);

$busySlots = $pdo->query("
    SELECT instructor_id, day_of_week, start_time, end_time FROM timetable_slots
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Urgency Replacement History
$history = $pdo->query("
    SELECT ur.*, ta.id AS task_id, ta.scheduled_date, ta.start_time, ta.end_time,
           u_inst.full_name AS new_instructor_name, tt.name AS task_type_name
    FROM urgency_replacements ur
    JOIN task_assignments ta ON ur.task_assignment_id = ta.id
    JOIN task_types tt ON ta.task_type_id = tt.id
    JOIN instructors i ON ur.new_instructor_id = i.id
    JOIN users u_inst ON i.user_id = u_inst.id
    ORDER BY ur.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1>Urgency Replacements</h1>
                    <p>Immediately reassign a task to a new instructor when an urgent conflict arises.</p>
                </div>
            </div>

            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($success_msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error_msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Assign Form Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Assign Urgent Replacement</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="assign_replacement" value="1">
                        
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Affected Task</label>
                                <select name="task_assignment_id" id="taskSelect" class="form-select" required onchange="filterInstructors()">
                                    <option value="">Select task</option>
                                    <?php foreach ($tasks as $t): ?>
                                        <option value="<?= $t['id'] ?>" 
                                                data-date="<?= $t['scheduled_date'] ?>" 
                                                data-day="<?= $t['day_name'] ?>" 
                                                data-start="<?= $t['start_time'] ?>" 
                                                data-end="<?= $t['end_time'] ?>" 
                                                data-current-inst="<?= $t['instructor_id'] ?>">
                                            <?= htmlspecialchars($t['task_type_name']) ?> - <?= formatDate($t['scheduled_date']) ?> (<?= date('h:i A', strtotime($t['start_time'])) ?> - <?= date('h:i A', strtotime($t['end_time'])) ?>) [Curr: <?= htmlspecialchars($t['current_instructor_name']) ?>]
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold">New Instructor</label>
                                <select name="new_instructor_id" id="instructorSelect" class="form-select" required>
                                    <option value="">Select task first</option>
                                    <?php foreach ($instructors as $inst): ?>
                                        <option value="<?= $inst['id'] ?>"><?= htmlspecialchars($inst['full_name']) ?> (<?= htmlspecialchars($inst['employee_id']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Reason</label>
                                <input type="text" name="reason" class="form-control" placeholder="Reason for replacement" required>
                            </div>
                        </div>

                        <!-- Separate row below with vertical top margin -->
                        <div class="row mt-4">
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-danger px-4 py-2 fw-bold">Assign</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- History Card -->
            <div class="card">
                <div class="card-header">
                    <h5>Urgency Replacement History</h5>
                    <span class="text-muted small"><?= count($history) ?> records</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Task ID</th>
                                    <th>Task Type & Time</th>
                                    <th>New Instructor</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Date Handled</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($history)): ?>
                                    <tr><td colspan="6" class="text-muted text-center py-3">No urgency replacements recorded yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($history as $h): ?>
                                    <tr>
                                        <td data-label="Task ID">#<?= $h['task_id'] ?></td>
                                        <td data-label="Task Type & Time">
                                            <strong><?= htmlspecialchars($h['task_type_name']) ?></strong><br>
                                            <small class="text-muted"><?= formatDate($h['scheduled_date']) ?> | <?= date('h:i A', strtotime($h['start_time'])) ?> - <?= date('h:i A', strtotime($h['end_time'])) ?></small>
                                        </td>
                                        <td data-label="New Instructor"><strong><?= htmlspecialchars($h['new_instructor_name']) ?></strong></td>
                                        <td data-label="Reason"><?= htmlspecialchars($h['reason']) ?></td>
                                        <td data-label="Status"><?= getStatusBadge($h['status']) ?></td>
                                        <td data-label="Date Handled"><?= formatDate($h['created_at']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

<script>
// Availability datasets injected from PHP
const busyTasks = <?= json_encode($busyTasks) ?>;
const busyLeaves = <?= json_encode($busyLeaves) ?>;
const busySlots = <?= json_encode($busySlots) ?>;
const allInstructors = <?= json_encode($instructors) ?>;

function filterInstructors() {
    const taskSelect = document.getElementById('taskSelect');
    const instructorSelect = document.getElementById('instructorSelect');
    const selectedOpt = taskSelect.options[taskSelect.selectedIndex];

    instructorSelect.innerHTML = '';

    if (!taskSelect.value) {
        instructorSelect.innerHTML = '<option value="">Select task first</option>';
        return;
    }

    const tDate = selectedOpt.getAttribute('data-date');
    const tDay = selectedOpt.getAttribute('data-day');
    const tStart = selectedOpt.getAttribute('data-start');
    const tEnd = selectedOpt.getAttribute('data-end');
    const currentInstId = parseInt(selectedOpt.getAttribute('data-current-inst'));

    let availableCount = 0;

    allInstructors.forEach(inst => {
        if (inst.id === currentInstId) return;

        let isBusy = false;

        busyTasks.forEach(bt => {
            if (parseInt(bt.instructor_id) === inst.id && bt.scheduled_date === tDate) {
                if (tStart < bt.end_time && tEnd > bt.start_time) isBusy = true;
            }
        });

        if (!isBusy) {
            busyLeaves.forEach(bl => {
                if (parseInt(bl.instructor_id) === inst.id) {
                    if (tDate >= bl.start_date && tDate <= bl.end_date) isBusy = true;
                }
            });
        }

        if (!isBusy) {
            busySlots.forEach(bs => {
                if (parseInt(bs.instructor_id) === inst.id && bs.day_of_week === tDay) {
                    if (tStart < bs.end_time && tEnd > bs.start_time) isBusy = true;
                }
            });
        }

        if (!isBusy) {
            const opt = document.createElement('option');
            opt.value = inst.id;
            opt.textContent = `${inst.full_name} (${inst.employee_id})`;
            instructorSelect.appendChild(opt);
            availableCount++;
        }
    });

    if (availableCount === 0) {
        instructorSelect.innerHTML = '<option value="">No available instructors for this time slot</option>';
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>