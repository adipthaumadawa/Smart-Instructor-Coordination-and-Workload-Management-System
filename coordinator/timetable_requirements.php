<?php
/**
 * Coordinator - Timetable Requirements
 * Smart Instructor Coordination and Workload Management System
 *
 * Non-academic staff post the semester timetable as requirements
 * (see non_academic/timetable_records.php), and the system tries to
 * auto-assign instructors to each one straight away. This page is
 * where the Instructor Coordinator reviews those assignments and can:
 *  - remove an assigned instructor from a slot
 *  - manually assign a different instructor to an open seat
 *  - re-run auto-assign to try to fill any seats still open
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_COORDINATOR);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'remove_slot') {
        $slotId = (int)$_POST['slot_id'];
        $reqStmt = $pdo->prepare("SELECT requirement_id FROM timetable_slots WHERE id = ?");
        $reqStmt->execute([$slotId]);
        $row = $reqStmt->fetch();
        if ($row) {
            $pdo->prepare("DELETE FROM timetable_slots WHERE id = ?")->execute([$slotId]);
            logActivity($_SESSION['user_id'], 'Timetable Assignment Removed', 'Removed instructor from timetable slot #' . $slotId);
            if ($row['requirement_id']) {
                refreshRequirementStatus($row['requirement_id']);
            }
        }
        header('Location: timetable_requirements.php');
        exit;
    }

    if ($action === 'assign_instructor') {
        $requirementId = (int)$_POST['requirement_id'];
        $instructorId = (int)$_POST['instructor_id'];

        $reqStmt = $pdo->prepare("SELECT * FROM timetable_requirements WHERE id = ?");
        $reqStmt->execute([$requirementId]);
        $requirement = $reqStmt->fetch();

        if ($requirement) {
            $alreadyIn = $pdo->prepare("SELECT COUNT(*) c FROM timetable_slots WHERE requirement_id = ? AND instructor_id = ?");
            $alreadyIn->execute([$requirementId, $instructorId]);
            $conflict = hasWeeklyTimetableConflict($instructorId, $requirement['day_of_week'], $requirement['start_time'], $requirement['end_time']);

            if ((int)$alreadyIn->fetch()['c'] > 0) {
                $_SESSION['flash_coord_msg'] = "That instructor is already assigned to this requirement.";
            } elseif ($conflict) {
                $_SESSION['flash_coord_msg'] = "Couldn't assign — that instructor already has a clashing timetable slot at that day/time.";
            } else {
                $pdo->prepare("
                    INSERT INTO timetable_slots
                        (instructor_id, requirement_id, day_of_week, start_time, end_time, subject, location, semester, academic_year, auto_assigned, assigned_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)
                ")->execute([
                    $instructorId, $requirementId, $requirement['day_of_week'], $requirement['start_time'], $requirement['end_time'],
                    $requirement['subject'], $requirement['location'], $requirement['semester'], $requirement['academic_year'],
                    $_SESSION['user_id']
                ]);
                refreshRequirementStatus($requirementId);
                logActivity($_SESSION['user_id'], 'Timetable Assignment Edited', "Manually assigned instructor #$instructorId to requirement #$requirementId");
                $_SESSION['flash_coord_msg'] = "Instructor assigned.";
            }
        }
        header('Location: timetable_requirements.php');
        exit;
    }

    if ($action === 'autofill') {
        $requirementId = (int)$_POST['requirement_id'];
        $filled = autoAssignTimetableRequirement($requirementId);
        $_SESSION['flash_coord_msg'] = $filled > 0
            ? "Auto-assign filled $filled more seat(s)."
            : "No eligible instructors were available to auto-fill the remaining seats — assign manually below.";
        header('Location: timetable_requirements.php');
        exit;
    }
}

$pageTitle = "Timetable Requirements";
include __DIR__ . '/../includes/header.php';

$flashMsg = null;
if (!empty($_SESSION['flash_coord_msg'])) {
    $flashMsg = $_SESSION['flash_coord_msg'];
    unset($_SESSION['flash_coord_msg']);
}

$requirements = $pdo->query("
    SELECT tr.*, ast.name AS stream_name
    FROM timetable_requirements tr
    LEFT JOIN academic_streams ast ON tr.academic_stream_id = ast.id
    ORDER BY FIELD(tr.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), tr.start_time
")->fetchAll();

// All assigned slots, grouped by requirement, in one query rather than N+1.
$slotsByRequirement = [];
$slotRows = $pdo->query("
    SELECT ts.id AS slot_id, ts.requirement_id, ts.auto_assigned, i.id AS instructor_id, u.full_name, i.employee_id
    FROM timetable_slots ts
    JOIN instructors i ON ts.instructor_id = i.id
    JOIN users u ON i.user_id = u.id
    WHERE ts.requirement_id IS NOT NULL
")->fetchAll();
foreach ($slotRows as $row) {
    $slotsByRequirement[$row['requirement_id']][] = $row;
}

$allInstructors = getAllActiveInstructors();
?>

            <div class="page-toolbar">
                <div>
                    <h1>Timetable Requirements</h1>
                    <p>Review the instructors the system auto-assigned to each posted requirement. Remove or add an instructor, or re-run auto-assign for any seats still open.</p>
                </div>
            </div>

            <?php if ($flashMsg): ?>
                <div class="alert alert-info"><?= htmlspecialchars($flashMsg) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5>All Requirements</h5>
                    <span class="text-muted small"><?= count($requirements) ?> requirements</span>
                </div>
                <div class="card-body">
                    <?php if (empty($requirements)): ?>
                        <p class="text-muted">No timetable requirements have been posted yet.</p>
                    <?php endif; ?>

                    <?php foreach ($requirements as $r): $slots = $slotsByRequirement[$r['id']] ?? []; ?>
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <strong><?= htmlspecialchars($r['subject']) ?></strong>
                                <span class="text-muted small">
                                    &middot; <?= htmlspecialchars($r['day_of_week']) ?>, <?= formatTime($r['start_time']) ?> - <?= formatTime($r['end_time']) ?>
                                    &middot; <?= htmlspecialchars($r['location']) ?>
                                    &middot; Stream: <?= htmlspecialchars($r['stream_name'] ?? 'Any') ?>
                                    &middot; Needs <?= (int)$r['required_instructors'] ?>
                                </span>
                            </div>
                            <?= getStatusBadge($r['status']) ?>
                        </div>
                        <div class="card-body">
                            <?php if (empty($slots)): ?>
                                <p class="text-muted small">No instructor assigned yet.</p>
                            <?php else: ?>
                                <ul class="list-group mb-3">
                                    <?php foreach ($slots as $s): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>
                                                <?= htmlspecialchars($s['full_name']) ?>
                                                <span class="text-muted small">(<?= htmlspecialchars($s['employee_id']) ?>)</span>
                                                <?= $s['auto_assigned'] ? '<span class="badge bg-secondary ms-1">Auto-assigned</span>' : '<span class="badge bg-primary ms-1">Manual</span>' ?>
                                            </span>
                                            <form method="post" class="mb-0">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="remove_slot">
                                                <input type="hidden" name="slot_id" value="<?= $s['slot_id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this instructor from the slot?');">Remove</button>
                                            </form>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <?php if ($r['status'] !== 'Fully Staffed'): ?>
                                <div class="d-flex flex-wrap gap-2 align-items-start">
                                    <form method="post" class="d-flex gap-2 flex-wrap align-items-center">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="assign_instructor">
                                        <input type="hidden" name="requirement_id" value="<?= $r['id'] ?>">
                                        <select name="instructor_id" class="form-select form-select-sm" style="min-width:220px" required>
                                            <option value="">Assign instructor manually...</option>
                                            <?php foreach ($allInstructors as $inst): ?>
                                                <option value="<?= $inst['id'] ?>"><?= htmlspecialchars($inst['display_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-sm btn-primary">Assign</button>
                                    </form>
                                    <form method="post" class="mb-0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="autofill">
                                        <input type="hidden" name="requirement_id" value="<?= $r['id'] ?>">
                                        <button class="btn btn-sm btn-outline-primary">Re-run Auto-Assign</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
