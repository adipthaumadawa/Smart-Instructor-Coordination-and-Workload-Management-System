<?php
/**
 * Non-Academic Staff - Leave Records (view only)
 * Smart Instructor Coordination and Workload Management System
 *
 * Non-Academic Staff can view instructor leave records for administrative
 * awareness. They do NOT approve or reject leave — leave is recorded by
 * instructors and relevant parties are notified.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_NON_ACADEMIC);

$pageTitle = "Leave Records";

// Optional filters
$filterStatus = sanitize($_GET['status'] ?? '');
$filterType = sanitize($_GET['leave_type'] ?? '');
$validStatuses = ['Pending', 'Approved', 'Rejected'];
$validTypes = ['Casual', 'Medical', 'Duty', 'Other'];

$sql = "
    SELECT lr.*, u.full_name AS instructor_name, i.employee_id,
           lr.created_at AS recorded_at
    FROM leave_records lr
    JOIN instructors i ON lr.instructor_id = i.id
    JOIN users u ON i.user_id = u.id
    WHERE 1=1
";
$params = [];
if ($filterStatus !== '' && in_array($filterStatus, $validStatuses, true)) {
    $sql .= " AND lr.status = :status";
    $params[':status'] = $filterStatus;
}
if ($filterType !== '' && in_array($filterType, $validTypes, true)) {
    $sql .= " AND lr.leave_type = :ltype";
    $params[':ltype'] = $filterType;
}
$sql .= " ORDER BY lr.created_at DESC LIMIT 150";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leaves = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1><i class="fas fa-history me-2"></i>Leave Records</h1>
                    <p>View instructor leave records. Leave is recorded by instructors; Non-Academic Staff are notified for administrative updates only (no approval required).</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="mb-0">All Leave Records</h5>
                    <span class="text-muted small"><?= count($leaves) ?> record<?= count($leaves) === 1 ? '' : 's' ?></span>
                </div>
                <div class="card-body">
                    <form method="get" class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All statuses</option>
                                <?php foreach ($validStatuses as $st): ?>
                                    <option value="<?= $st ?>" <?= $filterStatus === $st ? 'selected' : '' ?>><?= $st === 'Approved' ? 'Confirmed' : $st ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Leave type</label>
                            <select name="leave_type" class="form-select form-select-sm">
                                <option value="">All types</option>
                                <?php foreach ($validTypes as $t): ?>
                                    <option value="<?= $t ?>" <?= $filterType === $t ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Instructor</th>
                                    <th>Employee ID</th>
                                    <th>Leave type</th>
                                    <th>Start date</th>
                                    <th>End date</th>
                                    <th>Reason / notes</th>
                                    <th>Recorded on</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($leaves)): ?>
                                    <tr><td colspan="8" class="text-muted">No leave records found.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($leaves as $leave): ?>
                                <tr>
                                    <td data-label="Instructor"><strong><?= htmlspecialchars($leave['instructor_name']) ?></strong></td>
                                    <td data-label="Employee ID"><?= htmlspecialchars($leave['employee_id']) ?></td>
                                    <td data-label="Leave type"><?= htmlspecialchars($leave['leave_type']) ?></td>
                                    <td data-label="Start date"><?= formatDate($leave['start_date']) ?></td>
                                    <td data-label="End date"><?= formatDate($leave['end_date']) ?></td>
                                    <td data-label="Reason / notes">
                                        <?php
                                        $reason = trim((string)($leave['reason'] ?? ''));
                                        echo $reason !== '' ? htmlspecialchars($reason) : '<span class="text-muted">—</span>';
                                        ?>
                                    </td>
                                    <td data-label="Recorded on"><?= formatDate($leave['recorded_at'], 'd M Y H:i') ?></td>
                                    <td data-label="Status"><?= getLeaveStatusBadge($leave['status']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted mb-0 mt-2">
                        Non-Academic Staff have view-only access. Leave approval is handled through the instructor replacement workflow.
                    </p>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
