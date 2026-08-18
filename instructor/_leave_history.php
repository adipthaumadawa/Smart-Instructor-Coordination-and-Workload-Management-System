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
                <thead><tr><th>Type</th><th>From</th><th>To</th><th>Status</th><th>Replacement</th></tr></thead>
                <tbody>
                    <?php if (empty($leaveRecords)): ?>
                        <tr><td colspan="5" class="text-muted">No leave records yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($leaveRecords as $lr): ?>
                        <tr>
                            <td data-label="Type"><?= htmlspecialchars($lr['leave_type']) ?></td>
                            <td data-label="From"><?= formatDate($lr['start_date']) ?></td>
                            <td data-label="To"><?= formatDate($lr['end_date']) ?></td>
                            <td data-label="Status"><?= getLeaveStatusBadge($lr['status']) ?></td>
                            <td data-label="Replacement">
                                <?php if (empty($lr['rr_id'])): ?>
                                    <span class="text-muted small">None</span>
                                <?php else: ?>
                                    <?= htmlspecialchars($lr['rr_suggested_name'] ?? 'Unknown') ?>
                                    <?= getStatusBadge($lr['rr_status']) ?>
                                    <?php if ($lr['status'] === 'Pending' && $lr['rr_status'] === 'Rejected'): ?>
                                        <br><a href="<?= app_url('instructor/leave.php') ?>?leave_id=<?= (int)$lr['id'] ?>" class="btn btn-sm btn-outline-primary" style="margin-top:4px;">Choose Another Replacement</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>