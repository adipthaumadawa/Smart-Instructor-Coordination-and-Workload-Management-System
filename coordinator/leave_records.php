<?php
/**
 * Coordinator - Leave Records
 * Smart Instructor Coordination and Workload Management System
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_COORDINATOR);

$pageTitle = "Leave Records";
include __DIR__ . '/../includes/header.php';

$leaves = $pdo->query("
    SELECT lr.*, u.full_name 
    FROM leave_records lr
    JOIN instructors i ON lr.instructor_id = i.id
    JOIN users u ON i.user_id = u.id
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($leaves)): ?>
                                    <tr><td colspan="4" class="text-muted">No leave records found.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($leaves as $leave): ?>
                                <tr>
                                    <td data-label="Instructor"><strong><?= htmlspecialchars($leave['full_name']) ?></strong></td>
                                    <td data-label="Type"><?= htmlspecialchars($leave['leave_type']) ?></td>
                                    <td data-label="Dates"><?= formatDate($leave['start_date']) ?> to <?= formatDate($leave['end_date']) ?></td>
                                    <td data-label="Status"><?= getStatusBadge($leave['status']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>