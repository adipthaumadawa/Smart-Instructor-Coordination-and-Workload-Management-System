<?php
/**
 * Partial: "My Leave History" table.
 * Included from instructor/leave.php — expects $leaveRecords in scope.
 */
?>
<div class="card">
    <div class="card-header">
        <h5>My Leave History</h5>
        <span class="text-muted small"><?= count($leaveRecords) ?> record(s)</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Type</th><th>From</th><th>To</th><th>Status</th><th>Replacement</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($leaveRecords)): ?>
                        <tr><td colspan="6" class="text-muted">No leave records yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($leaveRecords as $lr): ?>
                        <tr>
                            <td data-label="Type"><?= htmlspecialchars($lr['leave_type']) ?></td>
                            <td data-label="From"><?= formatDate($lr['start_date']) ?></td>
                            <td data-label="To"><?= formatDate($lr['end_date']) ?></td>
                            <td data-label="Status"><?= getStatusBadge($lr['status']) ?></td>
                            <td data-label="Replacement">
                                <?php if (empty($lr['rr_id'])): ?>
                                    <span class="text-muted small">None</span>
                                <?php elseif ($lr['status'] === 'Cancelled'): ?>
                                    <span class="text-muted small"><?= htmlspecialchars($lr['rr_suggested_name'] ?? 'Unknown') ?></span>
                                <?php else: ?>
                                    <?= htmlspecialchars($lr['rr_suggested_name'] ?? 'Unknown') ?>
                                    <?= getStatusBadge($lr['rr_status']) ?>
                                    <?php if ($lr['status'] === 'Pending' && in_array($lr['rr_status'], ['Rejected', 'Cancelled'], true)): ?>
                                        <br><a href="<?= app_url('instructor/leave.php') ?>?leave_id=<?= (int)$lr['id'] ?>" class="btn btn-sm btn-outline-primary" style="margin-top:4px;">Choose Another Replacement</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td data-label="Actions" class="text-end action-cell">
                                <?php
                                $leaveNotEnded = strtotime($lr['end_date']) >= strtotime(date('Y-m-d'));
                                $canCancelLeave = in_array($lr['status'], ['Pending', 'Approved'], true) && $leaveNotEnded;
                                $canCancelRequest = $lr['status'] === 'Pending' && ($lr['rr_status'] ?? '') === 'Pending';
                                ?>
                                <?php if ($canCancelRequest): ?>
                                    <form method="POST" action="" style="display:inline-block;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="cancel_request" value="<?= (int)$lr['rr_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Cancel the pending replacement request? Your leave will stay pending until you choose another replacement.')">Cancel Request</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($canCancelLeave): ?>
                                    <form method="POST" action="" style="display:inline-block;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="cancel_leave" value="<?= (int)$lr['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= $lr['status'] === 'Approved' ? 'Cancel this confirmed leave? Tasks handed to your replacement will be returned to you and they will be notified.' : 'Cancel this leave? Any pending replacement request will be withdrawn.' ?>')">Cancel Leave</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>