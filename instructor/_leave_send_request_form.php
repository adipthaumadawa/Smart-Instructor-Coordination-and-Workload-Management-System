<?php
/**
 * Partial: "Send Request" button for one instructor row (search results
 * or smart suggestions) on instructor/leave.php.
 * Expects $sr (with instructor_id + name) and the draft leave fields /
 * $resumeLeaveId in scope from the parent script.
 */
?>
<form method="POST" action="" style="display:inline-block;">
    <?= csrf_field() ?>
    <input type="hidden" name="submit_leave_request" value="1">
    <input type="hidden" name="suggested_instructor_id" value="<?= (int)$sr['instructor_id'] ?>">
    <?php if ($resumeLeaveId > 0): ?>
        <input type="hidden" name="leave_id" value="<?= (int)$resumeLeaveId ?>">
    <?php else: ?>
        <input type="hidden" name="leave_type" value="<?= htmlspecialchars($draftLeaveType) ?>">
        <input type="hidden" name="start_date" value="<?= htmlspecialchars($draftStart) ?>">
        <input type="hidden" name="end_date" value="<?= htmlspecialchars($draftEnd) ?>">
        <input type="hidden" name="reason" value="<?= htmlspecialchars($draftReason) ?>">
    <?php endif; ?>
    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Send a replacement request to this instructor?')">
        <span class="ui-dot" aria-hidden="true"></span>Send Request
    </button>
</form>