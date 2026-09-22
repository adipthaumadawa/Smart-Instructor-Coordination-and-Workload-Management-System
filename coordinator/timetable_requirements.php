<?php
/**
 * Coordinator - Timetable Requirements
 * Smart Instructor Coordination and Workload Management System
 *
 * Non-academic staff post the semester timetable as requirements
 * (see non_academic/timetable_records.php) without picking instructors.
 * This page is where the Instructor Coordinator does the actual
 * allocation:
 *  - pick one or more instructors for a slot from a workload-filtered
 *    list (least-loaded / most available first), adding more pickers
 *    with the "+" button as needed
 *  - optionally let the system auto-fill remaining seats
 *  - remove an assigned instructor from a still-open (pending) slot
 *  - explicitly mark an allocation "Complete" once it's staffed the
 *    way the coordinator wants — this is a deliberate action, not
 *    just "required_instructors met", since the coordinator decides
 *    here how many instructors a slot really needs
 *  - completed allocations move to their own list, with Update
 *    (reopen for editing) and Delete actions
 *
 * Requires the `finalized` column on timetable_requirements:
 *   ALTER TABLE `timetable_requirements`
 *       ADD COLUMN `finalized` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`;
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

    if ($action === 'assign_instructors') {
        $requirementId = (int)$_POST['requirement_id'];
        $instructorIds = array_values(array_unique(array_filter(array_map('intval', $_POST['instructor_ids'] ?? []))));

        $reqStmt = $pdo->prepare("SELECT * FROM timetable_requirements WHERE id = ?");
        $reqStmt->execute([$requirementId]);
        $requirement = $reqStmt->fetch();

        if ($requirement && !empty($instructorIds)) {
            $insertStmt = $pdo->prepare("
                INSERT INTO timetable_slots
                    (instructor_id, requirement_id, day_of_week, start_time, end_time, subject, location, semester, academic_year, auto_assigned, assigned_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)
            ");
            $assignedCount = 0;
            $skippedCount = 0;

            foreach ($instructorIds as $instructorId) {
                $alreadyIn = $pdo->prepare("SELECT COUNT(*) c FROM timetable_slots WHERE requirement_id = ? AND instructor_id = ?");
                $alreadyIn->execute([$requirementId, $instructorId]);
                $conflict = hasWeeklyTimetableConflict($instructorId, $requirement['day_of_week'], $requirement['start_time'], $requirement['end_time']);

                if ((int)$alreadyIn->fetch()['c'] > 0 || $conflict) {
                    $skippedCount++;
                    continue;
                }

                $insertStmt->execute([
                    $instructorId, $requirementId, $requirement['day_of_week'], $requirement['start_time'], $requirement['end_time'],
                    $requirement['subject'], $requirement['location'], $requirement['semester'], $requirement['academic_year'],
                    $_SESSION['user_id']
                ]);
                $assignedCount++;
            }

            refreshRequirementStatus($requirementId);
            if ($assignedCount > 0) {
                logActivity($_SESSION['user_id'], 'Timetable Assignment Edited', "Manually assigned $assignedCount instructor(s) to requirement #$requirementId");
            }

            $msg = $assignedCount > 0 ? "$assignedCount instructor(s) assigned." : "No instructors were assigned.";
            if ($skippedCount > 0) {
                $msg .= " $skippedCount skipped (already assigned or a scheduling clash).";
            }
            $_SESSION['flash_coord_msg'] = $msg;
        } else {
            $_SESSION['flash_coord_msg'] = "Select at least one instructor to assign.";
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

    if ($action === 'complete_requirement') {
        $requirementId = (int)$_POST['requirement_id'];
        $countStmt = $pdo->prepare("SELECT COUNT(*) c FROM timetable_slots WHERE requirement_id = ?");
        $countStmt->execute([$requirementId]);
        $filled = (int)$countStmt->fetch()['c'];

        if ($filled > 0) {
            $pdo->prepare("UPDATE timetable_requirements SET finalized = 1 WHERE id = ?")->execute([$requirementId]);
            logActivity($_SESSION['user_id'], 'Timetable Allocation Completed', "Marked requirement #$requirementId as complete with $filled instructor(s)");
            $_SESSION['flash_coord_msg'] = "Allocation marked as complete.";
        } else {
            $_SESSION['flash_coord_msg'] = "Assign at least one instructor before marking this complete.";
        }
        header('Location: timetable_requirements.php');
        exit;
    }

    if ($action === 'reopen_requirement') {
        $requirementId = (int)$_POST['requirement_id'];
        $pdo->prepare("UPDATE timetable_requirements SET finalized = 0 WHERE id = ?")->execute([$requirementId]);
        logActivity($_SESSION['user_id'], 'Timetable Allocation Reopened', "Reopened requirement #$requirementId for editing");
        $_SESSION['flash_coord_msg'] = "Allocation reopened — update the instructors below, then mark it complete again.";
        header('Location: timetable_requirements.php');
        exit;
    }

    if ($action === 'delete_requirement') {
        $requirementId = (int)$_POST['requirement_id'];
        $reqStmt = $pdo->prepare("SELECT subject FROM timetable_requirements WHERE id = ?");
        $reqStmt->execute([$requirementId]);
        $subject = $reqStmt->fetchColumn();
        // timetable_slots rows cascade-delete with the requirement (FK ON DELETE CASCADE).
        $pdo->prepare("DELETE FROM timetable_requirements WHERE id = ?")->execute([$requirementId]);
        logActivity($_SESSION['user_id'], 'Timetable Allocation Deleted', 'Deleted requirement #' . $requirementId . ($subject ? " ($subject)" : ''));
        $_SESSION['flash_coord_msg'] = "Allocation deleted.";
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

// Split into pending (still being worked on) vs completed (coordinator
// has explicitly marked the allocation done) — this is what "finalized"
// tracks, separately from the Open/Partially/Fully Staffed badge, since
// the coordinator decides here how many instructors a slot actually
// needs rather than being locked to the number posted by non-academic
// staff.
$pendingRequirements = [];
$completedRequirements = [];
foreach ($requirements as $r) {
    if (!empty($r['finalized'])) {
        $completedRequirements[] = $r;
    } else {
        $pendingRequirements[] = $r;
    }
}

// Eligible instructors per pending requirement, least-loaded first —
// this feeds every instructor picker below, so what the coordinator
// sees matches what auto-assign would pick.
$eligibleByRequirement = [];
foreach ($pendingRequirements as $r) {
    $eligibleByRequirement[$r['id']] = getEligibleInstructorsForRequirement($r['id']);
}
?>

            <div class="page-toolbar">
                <div>
                    <h1>Timetable Requirements</h1>
                    <p>Assign one or more instructors to each posted requirement, then mark the allocation complete. Completed allocations move to their own list below.</p>
                </div>
            </div>

            <?php if ($flashMsg): ?>
                <div class="alert alert-info"><?= htmlspecialchars($flashMsg) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5>Pending Allocations</h5>
                    <span class="text-muted small"><?= count($pendingRequirements) ?> requirement(s)</span>
                </div>
                <div class="card-body">
                    <?php if (empty($pendingRequirements)): ?>
                        <p class="text-muted">Nothing pending — every posted requirement has been marked complete.</p>
                    <?php endif; ?>

                    <?php foreach ($pendingRequirements as $r):
                        $slots = $slotsByRequirement[$r['id']] ?? [];
                        $eligible = $eligibleByRequirement[$r['id']] ?? [];
                    ?>
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <strong><?= htmlspecialchars($r['subject']) ?></strong>
                                <span class="text-muted small">
                                    &middot; <?= htmlspecialchars($r['day_of_week']) ?>, <?= formatTime($r['start_time']) ?> - <?= formatTime($r['end_time']) ?>
                                    &middot; <?= htmlspecialchars($r['location']) ?>
                                    &middot; Stream: <?= htmlspecialchars($r['stream_name'] ?? 'Any') ?>
                                    &middot; Suggested <?= (int)$r['required_instructors'] ?>
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

                            <form method="post" class="assign-instructors-form mb-2" data-requirement-id="<?= $r['id'] ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="assign_instructors">
                                <input type="hidden" name="requirement_id" value="<?= $r['id'] ?>">
                                <div class="select-rows">
                                    <div class="select-row d-flex gap-2 align-items-center mb-2">
                                        <select name="instructor_ids[]" class="form-select form-select-sm instructor-select" style="min-width:280px" <?= empty($eligible) ? 'disabled' : '' ?>>
                                            <option value="">
                                                <?= empty($eligible) ? 'No eligible instructors available' : 'Select instructor (least-loaded first)...' ?>
                                            </option>
                                            <?php foreach ($eligible as $inst): ?>
                                                <option value="<?= $inst['id'] ?>">
                                                    <?= htmlspecialchars($inst['display_name']) ?> &mdash; <?= number_format($inst['weekly_hours'], 1) ?>h / <?= number_format($inst['max_weekly_hours'], 1) ?>h this week
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="btn btn-sm btn-outline-secondary add-instructor-btn" title="Add another instructor" <?= empty($eligible) ? 'disabled' : '' ?>>+</button>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="submit" class="btn btn-sm btn-primary" <?= empty($eligible) ? 'disabled' : '' ?>>Assign Selected</button>
                                </div>
                            </form>

                            <?php if (empty($eligible)): ?>
                                <p class="text-muted small mb-3">No active instructor is both free at this day/time and under their weekly hour limit &mdash; a slot may need to wait for capacity to open up, or the requirement's stream reviewed.</p>
                            <?php endif; ?>

                            <div class="d-flex flex-wrap gap-2">
                                <form method="post" class="mb-0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="autofill">
                                    <input type="hidden" name="requirement_id" value="<?= $r['id'] ?>">
                                    <button class="btn btn-sm btn-outline-primary" <?= empty($eligible) ? 'disabled' : '' ?>>Auto-Assign Remaining</button>
                                </form>
                                <form method="post" class="mb-0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="complete_requirement">
                                    <input type="hidden" name="requirement_id" value="<?= $r['id'] ?>">
                                    <button class="btn btn-sm btn-success" <?= empty($slots) ? 'disabled title="Assign at least one instructor first"' : '' ?>>Mark Complete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5>Completed Allocations</h5>
                    <span class="text-muted small"><?= count($completedRequirements) ?> requirement(s)</span>
                </div>
                <div class="card-body">
                    <?php if (empty($completedRequirements)): ?>
                        <p class="text-muted">No allocations have been marked complete yet.</p>
                    <?php endif; ?>

                    <?php foreach ($completedRequirements as $r): $slots = $slotsByRequirement[$r['id']] ?? []; ?>
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <strong><?= htmlspecialchars($r['subject']) ?></strong>
                                <span class="text-muted small">
                                    &middot; <?= htmlspecialchars($r['day_of_week']) ?>, <?= formatTime($r['start_time']) ?> - <?= formatTime($r['end_time']) ?>
                                    &middot; <?= htmlspecialchars($r['location']) ?>
                                    &middot; Stream: <?= htmlspecialchars($r['stream_name'] ?? 'Any') ?>
                                </span>
                            </div>
                            <span class="badge bg-success">Completed</span>
                        </div>
                        <div class="card-body">
                            <ul class="list-group mb-3">
                                <?php foreach ($slots as $s): ?>
                                    <li class="list-group-item">
                                        <?= htmlspecialchars($s['full_name']) ?>
                                        <span class="text-muted small">(<?= htmlspecialchars($s['employee_id']) ?>)</span>
                                        <?= $s['auto_assigned'] ? '<span class="badge bg-secondary ms-1">Auto-assigned</span>' : '<span class="badge bg-primary ms-1">Manual</span>' ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="d-flex gap-2">
                                <form method="post" class="mb-0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="reopen_requirement">
                                    <input type="hidden" name="requirement_id" value="<?= $r['id'] ?>">
                                    <button class="btn btn-sm btn-outline-primary">Update</button>
                                </form>
                                <form method="post" class="mb-0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_requirement">
                                    <input type="hidden" name="requirement_id" value="<?= $r['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this completed allocation and all its assigned slots? This cannot be undone.');">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

<script>
// "+" adds another instructor picker (cloned from the last row, so it
// keeps the same workload-filtered option list); "−" removes a row.
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('add-instructor-btn')) {
        const form = e.target.closest('.assign-instructors-form');
        const rows = form.querySelectorAll('.select-row');
        const lastRow = rows[rows.length - 1];
        const newRow = lastRow.cloneNode(true);

        newRow.querySelector('select').value = '';

        const btn = newRow.querySelector('.add-instructor-btn');
        btn.type = 'button';
        btn.textContent = '\u2212'; // minus sign
        btn.title = 'Remove this row';
        btn.classList.remove('add-instructor-btn', 'btn-outline-secondary');
        btn.classList.add('remove-instructor-btn', 'btn-outline-danger');

        form.querySelector('.select-rows').appendChild(newRow);
    }

    if (e.target.classList.contains('remove-instructor-btn')) {
        e.target.closest('.select-row').remove();
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>