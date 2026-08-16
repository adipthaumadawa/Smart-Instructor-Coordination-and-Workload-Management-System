<?php
/**
 * Coordinator - Leave Records
 * Smart Instructor Coordination and Workload Management System
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php'; // Required for sic_user_avatar() in topbar
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_COORDINATOR);a

$pageTitle = "Leave Records";
include __DIR__ . '/../includes/header.php';

$leaves = $pdo->query("
    SELECT lr.*, u.full_name,
           rr.status AS rr_status, su.full_name AS rr_suggested_name
    FROM leave_records lr
    JOIN instructors i ON lr.instructor_id = i.id
    JOIN users u ON i.user_id = u.id
    LEFT JOIN replacement_requests rr ON rr.id = (
        SELECT rr2.id FROM replacement_requests rr2
        WHERE rr2.leave_record_id = lr.id
        ORDER BY rr2.created_at DESC LIMIT 1
    )
    LEFT JOIN instructors si ON rr.suggested_instructor_id = si.id
    LEFT JOIN users su ON si.user_id = su.id
    ORDER BY lr.created_at DESC
")->fetchAll();
?>

            <div class="page-toolbar">
                <div>
                    <h1>Leave Records</h1>
                    <p>Track submitted leave requests and their current status.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>All Leave Records</h5>
                    <span class="text-muted small"><?= count($leaves) ?> records</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Instructor</th>
                                    <th>Type</th>
                                    <th>Dates</th>
                                    <th>Status</th>
                                    <th>Replacement</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($leaves)): ?>
                                    <tr><td colspan="5" class="text-muted">No leave records found.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($leaves as $leave): ?>
                                <tr>
                                    <td data-label="Instructor"><strong><?= htmlspecialchars($leave['full_name']) ?></strong></td>
                                    <td data-label="Type"><?= htmlspecialchars($leave['leave_type']) ?></td>
                                    <td data-label="Dates"><?= formatDate($leave['start_date']) ?> to <?= formatDate($leave['end_date']) ?></td>
                                    <td data-label="Status"><?= getLeaveStatusBadge($leave['status']) ?></td>
                                    <td data-label="Replacement">
                                        <?php if (!empty($leave['rr_suggested_name'])): ?>
                                            <?= htmlspecialchars($leave['rr_suggested_name']) ?> <?= getStatusBadge($leave['rr_status']) ?>
                                        <?php else: ?>
                                            <span class="text-muted small">Not yet assigned</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>