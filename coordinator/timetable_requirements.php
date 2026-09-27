<?php
/**
 * Coordinator - Timetable Requirements
 * Smart Instructor Coordination and Workload Management System
 *
 * Non-academic staff post timetable requirements without selecting instructors.
 * The Instructor Coordinator allocates one or more eligible instructors here,
 * can auto-assign remaining places, and can mark an allocation complete.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_COORDINATOR);

/* -------------------------------------------------------------
 * Helpers
 * ------------------------------------------------------------- */
function normalizeAcademicYear($value): string
{
    $value = trim((string)$value);
    $lower = strtolower($value);

    if (preg_match('/(?:^|\b)1(?:st)?(?:\s*year)?\b/i', $value) || $lower === 'year 1') {
        return '1st Year';
    }
    if (preg_match('/(?:^|\b)2(?:nd)?(?:\s*year)?\b/i', $value) || $lower === 'year 2') {
        return '2nd Year';
    }
    if (preg_match('/(?:^|\b)3(?:rd)?(?:\s*year)?\b/i', $value) || $lower === 'year 3') {
        return '3rd Year';
    }
    if (preg_match('/(?:^|\b)4(?:th)?(?:\s*year)?\b/i', $value) || $lower === 'year 4') {
        return '4th Year';
    }

    return $value !== '' ? $value : 'Other';
}

function yearMatches(array $requirement, string $selectedYear): bool
{
    if ($selectedYear === 'Overall') {
        return true;
    }

    return normalizeAcademicYear($requirement['academic_year'] ?? '') === $selectedYear;
}

function timeSortRequirements(array &$requirements): void
{
    usort($requirements, function ($a, $b) {
        $aStart = (string)($a['start_time'] ?? '');
        $bStart = (string)($b['start_time'] ?? '');

        if ($aStart !== $bStart) {
            return strcmp($aStart, $bStart);
        }

        $aEnd = (string)($a['end_time'] ?? '');
        $bEnd = (string)($b['end_time'] ?? '');
        if ($aEnd !== $bEnd) {
            return strcmp($aEnd, $bEnd);
        }

        $dayOrder = [
            'Monday' => 1,
            'Tuesday' => 2,
            'Wednesday' => 3,
            'Thursday' => 4,
            'Friday' => 5,
            'Saturday' => 6,
            'Sunday' => 7,
        ];

        $aDay = $dayOrder[$a['day_of_week'] ?? ''] ?? 99;
        $bDay = $dayOrder[$b['day_of_week'] ?? ''] ?? 99;
        if ($aDay !== $bDay) {
            return $aDay <=> $bDay;
        }

        return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
    });
}

function flashAndRedirect(string $message): void
{
    $_SESSION['flash_coord_msg'] = $message;
    header('Location: timetable_requirements.php');
    exit;
}

/* -------------------------------------------------------------
 * POST actions
 * ------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    /* Remove one instructor from a pending allocation. */
    if ($action === 'remove_slot') {
        $slotId = (int)($_POST['slot_id'] ?? 0);

        $reqStmt = $pdo->prepare("SELECT requirement_id FROM timetable_slots WHERE id = ?");
        $reqStmt->execute([$slotId]);
        $row = $reqStmt->fetch();

        if ($row) {
            $pdo->prepare("DELETE FROM timetable_slots WHERE id = ?")->execute([$slotId]);
            logActivity(
                $_SESSION['user_id'],
                'Timetable Assignment Removed',
                'Removed instructor from timetable slot #' . $slotId
            );

            if (!empty($row['requirement_id'])) {
                refreshRequirementStatus((int)$row['requirement_id']);
            }
        }

        header('Location: timetable_requirements.php');
        exit;
    }

    /* Assign one or more selected instructors. */
    if ($action === 'assign_instructors') {
        $requirementId = (int)($_POST['requirement_id'] ?? 0);
        $instructorIds = array_values(array_unique(array_filter(
            array_map('intval', $_POST['instructor_ids'] ?? [])
        )));

        $reqStmt = $pdo->prepare("SELECT * FROM timetable_requirements WHERE id = ?");
        $reqStmt->execute([$requirementId]);
        $requirement = $reqStmt->fetch();

        if (!$requirement) {
            flashAndRedirect('Timetable requirement was not found.');
        }

        if (empty($instructorIds)) {
            flashAndRedirect('Select at least one instructor to assign.');
        }

        /* Do not allow editing a completed requirement through this action. */
        if (!empty($requirement['finalized'])) {
            flashAndRedirect('This allocation is already completed. Click Update first to reopen it.');
        }

        $insertStmt = $pdo->prepare("
            INSERT INTO timetable_slots
                (instructor_id, requirement_id, day_of_week, start_time, end_time,
                 subject, location, semester, academic_year, auto_assigned, assigned_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)
        ");

        $assignedCount = 0;
        $skippedCount = 0;

        foreach ($instructorIds as $instructorId) {
            $alreadyIn = $pdo->prepare(
                "SELECT COUNT(*) c FROM timetable_slots WHERE requirement_id = ? AND instructor_id = ?"
            );
            $alreadyIn->execute([$requirementId, $instructorId]);

            $conflict = hasWeeklyTimetableConflict(
                $instructorId,
                $requirement['day_of_week'],
                $requirement['start_time'],
                $requirement['end_time']
            );

            if ((int)$alreadyIn->fetch()['c'] > 0 || $conflict) {
                $skippedCount++;
                continue;
            }

            $insertStmt->execute([
                $instructorId,
                $requirementId,
                $requirement['day_of_week'],
                $requirement['start_time'],
                $requirement['end_time'],
                $requirement['subject'],
                $requirement['location'],
                $requirement['semester'],
                $requirement['academic_year'],
                $_SESSION['user_id']
            ]);

            $assignedCount++;
        }

        refreshRequirementStatus($requirementId);

        if ($assignedCount > 0) {
            logActivity(
                $_SESSION['user_id'],
                'Timetable Assignment Edited',
                "Manually assigned $assignedCount instructor(s) to requirement #$requirementId"
            );
        }

        $msg = $assignedCount > 0
            ? "$assignedCount instructor(s) assigned."
            : 'No instructors were assigned.';

        if ($skippedCount > 0) {
            $msg .= " $skippedCount skipped (already assigned or a scheduling clash).";
        }

        flashAndRedirect($msg);
    }

    /* Auto-assign remaining instructors according to the existing workload engine. */
    if ($action === 'autofill') {
        $requirementId = (int)($_POST['requirement_id'] ?? 0);

        $reqStmt = $pdo->prepare("SELECT finalized FROM timetable_requirements WHERE id = ?");
        $reqStmt->execute([$requirementId]);
        $req = $reqStmt->fetch();

        if (!$req) {
            flashAndRedirect('Timetable requirement was not found.');
        }

        if (!empty($req['finalized'])) {
            flashAndRedirect('This allocation is already completed. Click Update first to reopen it.');
        }

        $filled = autoAssignTimetableRequirement($requirementId);
        flashAndRedirect(
            $filled > 0
                ? "Auto-Assign added $filled instructor(s)."
                : 'No eligible instructors were available to Auto-Assign.'
        );
    }

    /* Mark the requirement as completed. */
    if ($action === 'complete_requirement') {
        $requirementId = (int)($_POST['requirement_id'] ?? 0);

        $countStmt = $pdo->prepare("SELECT COUNT(*) c FROM timetable_slots WHERE requirement_id = ?");
        $countStmt->execute([$requirementId]);
        $filled = (int)$countStmt->fetch()['c'];

        if ($filled > 0) {
            $pdo->prepare("UPDATE timetable_requirements SET finalized = 1 WHERE id = ?")
                ->execute([$requirementId]);

            logActivity(
                $_SESSION['user_id'],
                'Timetable Allocation Completed',
                "Marked requirement #$requirementId as complete with $filled instructor(s)"
            );

            flashAndRedirect('Allocation marked as complete.');
        }

        flashAndRedirect('Assign at least one instructor before marking this complete.');
    }

    /* Reopen a completed requirement for editing. */
    if ($action === 'reopen_requirement') {
        $requirementId = (int)($_POST['requirement_id'] ?? 0);

        $pdo->prepare("UPDATE timetable_requirements SET finalized = 0 WHERE id = ?")
            ->execute([$requirementId]);

        logActivity(
            $_SESSION['user_id'],
            'Timetable Allocation Reopened',
            "Reopened requirement #$requirementId for editing"
        );

        flashAndRedirect('Allocation reopened. Update the instructors, then mark it complete again.');
    }

    /* Delete a completed requirement and its timetable slots. */
    if ($action === 'delete_requirement') {
        $requirementId = (int)($_POST['requirement_id'] ?? 0);

        $reqStmt = $pdo->prepare("SELECT subject FROM timetable_requirements WHERE id = ?");
        $reqStmt->execute([$requirementId]);
        $subject = $reqStmt->fetchColumn();

        $pdo->prepare("DELETE FROM timetable_requirements WHERE id = ?")
            ->execute([$requirementId]);

        logActivity(
            $_SESSION['user_id'],
            'Timetable Allocation Deleted',
            'Deleted requirement #' . $requirementId . ($subject ? " ($subject)" : '')
        );

        flashAndRedirect('Allocation deleted.');
    }
}

$pageTitle = 'Timetable Requirements';
include __DIR__ . '/../includes/header.php';

$flashMsg = null;
if (!empty($_SESSION['flash_coord_msg'])) {
    $flashMsg = $_SESSION['flash_coord_msg'];
    unset($_SESSION['flash_coord_msg']);
}

/* -------------------------------------------------------------
 * Load requirements and assigned instructors.
 * Everything is sorted by start time so every year and Overall
 * view displays the non-academic timetable chronologically.
 * ------------------------------------------------------------- */
$requirements = $pdo->query("
    SELECT tr.*, ast.name AS stream_name
    FROM timetable_requirements tr
    LEFT JOIN academic_streams ast ON tr.academic_stream_id = ast.id
")->fetchAll();

timeSortRequirements($requirements);

$slotsByRequirement = [];
$slotRows = $pdo->query("
    SELECT
        ts.id AS slot_id,
        ts.requirement_id,
        ts.auto_assigned,
        ts.instructor_id,
        i.id AS instructor_table_id,
        u.full_name,
        i.employee_id
    FROM timetable_slots ts
    JOIN instructors i ON ts.instructor_id = i.id
    JOIN users u ON i.user_id = u.id
    WHERE ts.requirement_id IS NOT NULL
    ORDER BY ts.requirement_id, u.full_name
")->fetchAll();

foreach ($slotRows as $row) {
    $slotsByRequirement[$row['requirement_id']][] = $row;
}

$pendingRequirements = [];
$completedRequirements = [];

foreach ($requirements as $r) {
    if (!empty($r['finalized'])) {
        $completedRequirements[] = $r;
    } else {
        $pendingRequirements[] = $r;
    }
}

/*
 * IMPORTANT: The manual instructor selector must show ALL instructors in
 * the system, not only instructors who currently pass the workload/conflict
 * eligibility rules.
 *
 * The list is still sorted by current workload (lowest first). The server
 * side assignment code continues to protect against duplicate assignments
 * and timetable conflicts. Auto-Assign continues to use the existing
 * eligibility engine separately.
 */
$allInstructors = $pdo->query("
    SELECT
        i.id,
        i.employee_id,
        i.max_weekly_hours,
        i.status,
        u.full_name AS display_name,
        COALESCE((
            SELECT SUM(
                TIME_TO_SEC(TIMEDIFF(ts2.end_time, ts2.start_time)) / 3600
            )
            FROM timetable_slots ts2
            WHERE ts2.instructor_id = i.id
        ), 0) AS weekly_hours
    FROM instructors i
    JOIN users u ON i.user_id = u.id
    ORDER BY weekly_hours ASC, u.full_name ASC
")->fetchAll();

$eligibleByRequirement = [];
foreach ($pendingRequirements as $r) {
    /* Keep the same complete instructor list for every timetable requirement. */
    $eligibleByRequirement[$r['id']] = $allInstructors;
}

$yearButtons = ['Overall', '1st Year', '2nd Year', '3rd Year', '4th Year'];
?>

<style>
    .year-filter-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 14px;
    }

    .year-filter-btn {
        min-width: 92px;
        padding: 8px 16px;
        border-radius: 8px;
        border: 1px solid #d8e0ea;
        background: #fff;
        color: #344054;
        font-weight: 600;
        cursor: pointer;
        transition: .15s ease;
    }

    .year-filter-btn:hover {
        background: #f3f6fa;
    }

    .year-filter-btn.active {
        background: #0b2342;
        border-color: #0b2342;
        color: #fff;
    }

    .year-panel {
        display: none;
    }

    .year-panel.active {
        display: block;
    }

    .slot-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        flex-wrap: wrap;
    }

    .slot-meta {
        line-height: 1.65;
    }

    .slot-id-badge {
        display: inline-block;
        margin-right: 7px;
        padding: 3px 8px;
        border-radius: 6px;
        background: #eef2f7;
        color: #344054;
        font-size: .75rem;
        font-weight: 700;
    }

    .assigned-list {
        margin-bottom: 14px;
    }

    .assigned-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
    }

    .assigned-person {
        min-width: 0;
    }

    .assigned-actions {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 7px;
        flex-shrink: 0;
    }

    .instructor-number {
        display: inline-flex;
        width: 25px;
        height: 25px;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #edf2f7;
        color: #26364d;
        font-size: .78rem;
        font-weight: 700;
        margin-right: 7px;
    }

    .select-row {
        display: flex;
        gap: 8px;
        align-items: center;
        margin-bottom: 8px;
    }

    .select-row .instructor-select {
        flex: 1 1 auto;
        min-width: 280px;
    }

    .plus-btn,
    .minus-btn {
        width: 39px;
        height: 38px;
        flex: 0 0 39px;
        font-size: 1.1rem;
        font-weight: 700;
        padding: 0;
    }

    .assign-action-line {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 10px;
    }

    .assign-action-line .mark-complete-form {
        margin-left: auto;
    }

    .empty-year {
        padding: 22px;
        border: 1px dashed #d0d7e2;
        border-radius: 10px;
        color: #667085;
        background: #fafbfc;
    }

    @media (max-width: 700px) {
        .select-row .instructor-select {
            min-width: 0;
        }

        .assign-action-line .mark-complete-form {
            margin-left: 0;
            width: 100%;
        }

        .assign-action-line .mark-complete-form button {
            width: 100%;
        }

        .assigned-row {
            align-items: flex-start;
            flex-direction: column;
        }

        .assigned-actions {
            margin-left: 0;
        }
    }
</style>

<div class="page-toolbar">
    <div>
        <h1>Timetable Requirements</h1>
        <p>Assign one or more instructors to each posted requirement, then mark the allocation complete.</p>

        <!-- Academic-year navigation -->
        <div class="year-filter-wrap" id="yearFilter">
            <?php foreach ($yearButtons as $index => $year): ?>
                <button
                    type="button"
                    class="year-filter-btn <?= $index === 0 ? 'active' : '' ?>"
                    data-year="<?= htmlspecialchars($year) ?>"
                >
                    <?= htmlspecialchars($year) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if ($flashMsg): ?>
    <div class="alert alert-info"><?= htmlspecialchars($flashMsg) ?></div>
<?php endif; ?>

<?php foreach ($yearButtons as $yearIndex => $selectedYear): ?>
    <?php
    $yearPending = array_values(array_filter($pendingRequirements, function ($r) use ($selectedYear) {
        return yearMatches($r, $selectedYear);
    }));

    $yearCompleted = array_values(array_filter($completedRequirements, function ($r) use ($selectedYear) {
        return yearMatches($r, $selectedYear);
    }));

    timeSortRequirements($yearPending);
    timeSortRequirements($yearCompleted);
    ?>

    <div class="year-panel <?= $yearIndex === 0 ? 'active' : '' ?>" data-year-panel="<?= htmlspecialchars($selectedYear) ?>">

        <!-- PENDING ALLOCATIONS -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">Pending Allocations — <?= htmlspecialchars($selectedYear) ?></h5>
                <span class="text-muted small"><?= count($yearPending) ?> time slot(s)</span>
            </div>

            <div class="card-body">
                <?php if (empty($yearPending)): ?>
                    <div class="empty-year">No pending time slots for this view.</div>
                <?php endif; ?>

                <?php foreach ($yearPending as $pendingIndex => $r): ?>
                    <?php
                    $slots = $slotsByRequirement[$r['id']] ?? [];
                    $eligible = $eligibleByRequirement[$r['id']] ?? [];
                    $pendingDisplayId = 'P-' . ($pendingIndex + 1);
                    ?>

                    <div class="card mb-3 timetable-slot-card">
                        <div class="card-header slot-card-header">
                            <div class="slot-meta">
                                <div>
                                    <span class="slot-id-badge">Pending ID: <?= htmlspecialchars($pendingDisplayId) ?></span>
                                    <strong><?= htmlspecialchars($r['subject']) ?></strong>
                                </div>
                                <span class="text-muted small">
                                    <?= htmlspecialchars($r['day_of_week']) ?>,
                                    <?= formatTime($r['start_time']) ?> - <?= formatTime($r['end_time']) ?>
                                    &middot; <?= htmlspecialchars($r['location']) ?>
                                    &middot; Stream: <?= htmlspecialchars($r['stream_name'] ?? 'Any') ?>
                                    &middot; <?= htmlspecialchars(normalizeAcademicYear($r['academic_year'] ?? '')) ?>
                                    &middot; Suggested <?= (int)$r['required_instructors'] ?>
                                </span>
                            </div>
                            <?= getStatusBadge($r['status']) ?>
                        </div>

                        <div class="card-body">
                            <?php if (empty($slots)): ?>
                                <p class="text-muted small mb-3">No instructor assigned yet.</p>
                            <?php else: ?>
                                <ul class="list-group assigned-list">
                                    <?php foreach ($slots as $slotIndex => $s): ?>
                                        <li class="list-group-item">
                                            <div class="assigned-row">
                                                <div class="assigned-person">
                                                    <span class="instructor-number"><?= $slotIndex + 1 ?></span>
                                                    <strong><?= htmlspecialchars($s['full_name']) ?></strong>
                                                    <span class="text-muted small">(<?= htmlspecialchars($s['employee_id']) ?>)</span>
                                                    <?= $s['auto_assigned']
                                                        ? '<span class="badge bg-secondary ms-1">Auto-assigned</span>'
                                                        : '<span class="badge bg-primary ms-1">Manual</span>' ?>
                                                </div>
                                                <form method="post" class="mb-0">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="remove_slot">
                                                    <input type="hidden" name="slot_id" value="<?= (int)$s['slot_id'] ?>">
                                                    <button
                                                        type="submit"
                                                        class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('Remove this instructor from the slot?');"
                                                    >Remove</button>
                                                </form>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <!-- Multiple instructor assignment. Every row keeps a + button. -->
                            <form method="post" id="assign-form-<?= (int)$r['id'] ?>" class="assign-instructors-form mb-0" data-requirement-id="<?= (int)$r['id'] ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="assign_instructors">
                                <input type="hidden" name="requirement_id" value="<?= (int)$r['id'] ?>">

                                <div class="select-rows">
                                    <div class="select-row">
                                        <select
                                            name="instructor_ids[]"
                                            class="form-select form-select-sm instructor-select"
                                            <?= empty($eligible) ? 'disabled' : '' ?>
                                        >
                                            <option value="">
                                                Select instructor
                                            </option>
                                            <?php foreach ($eligible as $inst): ?>
                                                <option value="<?= (int)$inst['id'] ?>">
                                                    <?= htmlspecialchars($inst['display_name']) ?>
                                                    
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-secondary plus-btn add-instructor-btn"
                                            title="Add another instructor"
                                            <?= empty($eligible) ? 'disabled' : '' ?>
                                        >+</button>
                                    </div>
                                </div>

                            </form>

                            <div class="assign-action-line">
                                <button
                                    type="submit"
                                    form="assign-form-<?= (int)$r['id'] ?>"
                                    class="btn btn-sm btn-primary"
                                    <?= empty($eligible) ? 'disabled' : '' ?>
                                >Assign Selected</button>

                                <form method="post" class="mb-0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="autofill">
                                    <input type="hidden" name="requirement_id" value="<?= (int)$r['id'] ?>">
                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline-primary"
                                    >Auto - Assign</button>
                                </form>

                                <form method="post" class="mb-0 mark-complete-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="complete_requirement">
                                    <input type="hidden" name="requirement_id" value="<?= (int)$r['id'] ?>">
                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-success"
                                        <?= empty($slots) ? 'disabled title="Assign at least one instructor first"' : '' ?>
                                    >Mark Complete</button>
                                </form>
                            </div>

                            <?php if (empty($eligible)): ?>
                                <p class="text-muted small mt-3 mb-0">
                                    No active instructor is both free at this day/time and under the weekly hour limit.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- COMPLETED ALLOCATIONS -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">Completed Allocations — <?= htmlspecialchars($selectedYear) ?></h5>
                <span class="text-muted small"><?= count($yearCompleted) ?> time slot(s)</span>
            </div>

            <div class="card-body">
                <?php if (empty($yearCompleted)): ?>
                    <div class="empty-year">No completed allocations for this view.</div>
                <?php endif; ?>

                <?php foreach ($yearCompleted as $completedIndex => $r): ?>
                    <?php
                    $slots = $slotsByRequirement[$r['id']] ?? [];
                    $completedDisplayId = 'C-' . ($completedIndex + 1);
                    $lastSlotIndex = count($slots) - 1;
                    ?>

                    <div class="card mb-3 timetable-slot-card">
                        <div class="card-header slot-card-header">
                            <div class="slot-meta">
                                <div>
                                    <span class="slot-id-badge">Completed ID: <?= htmlspecialchars($completedDisplayId) ?></span>
                                    <strong><?= htmlspecialchars($r['subject']) ?></strong>
                                </div>
                                <span class="text-muted small">
                                    <?= htmlspecialchars($r['day_of_week']) ?>,
                                    <?= formatTime($r['start_time']) ?> - <?= formatTime($r['end_time']) ?>
                                    &middot; <?= htmlspecialchars($r['location']) ?>
                                    &middot; Stream: <?= htmlspecialchars($r['stream_name'] ?? 'Any') ?>
                                    &middot; <?= htmlspecialchars(normalizeAcademicYear($r['academic_year'] ?? '')) ?>
                                </span>
                            </div>
                            <span class="badge bg-success">Completed</span>
                        </div>

                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush assigned-list mb-0">
                                <?php foreach ($slots as $slotIndex => $s): ?>
                                    <li class="list-group-item px-3 py-3">
                                        <div class="assigned-row">
                                            <div class="assigned-person">
                                                <span class="instructor-number"><?= $slotIndex + 1 ?></span>
                                                <strong><?= htmlspecialchars($s['full_name']) ?></strong>
                                                <span class="text-muted small">(<?= htmlspecialchars($s['employee_id']) ?>)</span>
                                                <?= $s['auto_assigned']
                                                    ? '<span class="badge bg-secondary ms-1">Auto-assigned</span>'
                                                    : '<span class="badge bg-primary ms-1">Manual</span>' ?>
                                            </div>

                                            <!-- Update/Delete are aligned to the right of the LAST instructor row. -->
                                            <?php if ($slotIndex === $lastSlotIndex): ?>
                                                <div class="assigned-actions">
                                                    <form method="post" class="mb-0">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="reopen_requirement">
                                                        <input type="hidden" name="requirement_id" value="<?= (int)$r['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-primary">Update</button>
                                                    </form>

                                                    <form method="post" class="mb-0">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="delete_requirement">
                                                        <input type="hidden" name="requirement_id" value="<?= (int)$r['id'] ?>">
                                                        <button
                                                            type="submit"
                                                            class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Delete this completed allocation and all its assigned slots? This cannot be undone.');"
                                                        >Delete</button>
                                                    </form>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
(function () {
    /* ---------------------------------------------------------
     * Academic-year tabs
     * --------------------------------------------------------- */
    const filterButtons = document.querySelectorAll('.year-filter-btn');
    const yearPanels = document.querySelectorAll('.year-panel');

    filterButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const year = button.dataset.year;

            filterButtons.forEach(function (btn) {
                btn.classList.toggle('active', btn === button);
            });

            yearPanels.forEach(function (panel) {
                panel.classList.toggle('active', panel.dataset.yearPanel === year);
            });
        });
    });

    /* ---------------------------------------------------------
     * + button: every row has its own + button.
     * Clicking + creates another independent selector with the
     * same workload-ordered instructor list.
     * --------------------------------------------------------- */
    document.addEventListener('click', function (event) {
        const addButton = event.target.closest('.add-instructor-btn');
        if (addButton) {
            const form = addButton.closest('.assign-instructors-form');
            if (!form) return;

            const rowsContainer = form.querySelector('.select-rows');
            const lastRow = rowsContainer.querySelector('.select-row:last-child');
            if (!lastRow) return;

            const newRow = lastRow.cloneNode(true);
            const newSelect = newRow.querySelector('.instructor-select');
            const newButton = newRow.querySelector('.add-instructor-btn');

            if (newSelect) {
                newSelect.value = '';
                newSelect.disabled = false;
            }

            if (newButton) {
                newButton.disabled = false;
                newButton.classList.remove('remove-instructor-btn', 'btn-outline-danger', 'minus-btn');
                newButton.classList.add('add-instructor-btn', 'btn-outline-secondary', 'plus-btn');
                newButton.textContent = '+';
                newButton.title = 'Add another instructor';
            }

            rowsContainer.appendChild(newRow);
            return;
        }

        /* Optional remove button is created with the keyboard-safe minus action. */
        const removeButton = event.target.closest('.remove-instructor-btn');
        if (removeButton) {
            const row = removeButton.closest('.select-row');
            if (row) row.remove();
        }
    });

    /* ---------------------------------------------------------
     * Prevent assigning the same instructor twice in one form.
     * The server also checks this, so this is only a convenience.
     * --------------------------------------------------------- */
    document.addEventListener('change', function (event) {
        if (!event.target.classList.contains('instructor-select')) return;

        const form = event.target.closest('.assign-instructors-form');
        if (!form) return;

        const selects = Array.from(form.querySelectorAll('.instructor-select'));
        const selectedValues = selects
            .map(function (select) { return select.value; })
            .filter(Boolean);

        selects.forEach(function (select) {
            Array.from(select.options).forEach(function (option) {
                if (!option.value) return;
                option.disabled = selectedValues.includes(option.value) && option.value !== select.value;
            });
        });
    });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
