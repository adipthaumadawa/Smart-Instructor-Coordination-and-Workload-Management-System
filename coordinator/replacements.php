<?php
/**
 * Coordinator - Replacement Requests
 * Smart Instructor Coordination and Workload Management System
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_COORDINATOR);

$pageTitle = "Replacement Requests";
include __DIR__ . '/../includes/header.php';

// Fetch all replacement requests with requester details, target instructor, task schedule, and status
$requests = $pdo->query("
    SELECT rr.*, 
           req_u.full_name AS requester_name, 
           req_i.employee_id AS requester_emp_id,
           sug_u.full_name AS target_instructor_name,
           ta.scheduled_date, ta.start_time, ta.end_time
    FROM replacement_requests rr
    JOIN instructors req_i ON rr.requested_by_instructor_id = req_i.id
    JOIN users req_u ON req_i.user_id = req_u.id
    JOIN task_assignments ta ON rr.task_assignment_id = ta.id
    LEFT JOIN instructors sug_i ON rr.suggested_instructor_id = sug_i.id
    LEFT JOIN users sug_u ON sug_i.user_id = sug_u.id
    ORDER BY rr.created_at DESC
")->fetchAll();
?>

            <div class="page-toolbar">
                <div>
                    <h1>Replacement Requests</h1>
                    <p>Monitor instructor replacement requests and their current status.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>All Replacement Requests</h5>
                    <span class="text-muted small"><?= count($requests) ?> records</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Requested By</th>
                                    <th>Replacement For</th>
                                    <th>Task Date</th>
                                    <th>Reason</th>
                                    <th>Requested On</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($requests)): ?>
                                    <tr><td colspan="6" class="text-muted">No replacement requests found.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td data-label="Requested By">
                                        <strong><?= htmlspecialchars($req['requester_name']) ?></strong>
                                        <span class="text-muted small">(<?= htmlspecialchars($req['requester_emp_id']) ?>)</span>
                                    </td>
                                    <td data-label="Replacement For">
                                        <?php if (!empty($req['target_instructor_name'])): ?>
                                            <?= htmlspecialchars($req['target_instructor_name']) ?>
                                        <?php else: ?>
                                            <span class="text-muted small">Open Request</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Task Date">
                                        <?= formatDate($req['scheduled_date']) ?><br>
                                        <small class="text-muted"><?= date('h:i A', strtotime($req['start_time'])) ?> - <?= date('h:i A', strtotime($req['end_time'])) ?></small>
                                    </td>
                                    <td data-label="Reason"><?= htmlspecialchars($req['reason']) ?></td>
                                    <td data-label="Requested On"><?= formatDate($req['created_at']) ?></td>
                                    <td data-label="Status"><?= getStatusBadge($req['status']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>