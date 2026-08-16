<?php
/**
 * Director - Allocation Monitoring
 * Smart Instructor Coordination and Workload Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_DIRECTOR);

$pageTitle = "Allocation Monitoring";
include __DIR__ . '/../includes/header.php';

$rows = $pdo->query("
    SELECT ta.*, tt.name task_type, u.full_name instructor_name 
    FROM task_assignments ta 
    JOIN task_types tt ON ta.task_type_id = tt.id 
    JOIN instructors i ON ta.instructor_id = i.id 
    JOIN users u ON i.user_id = u.id 
    ORDER BY ta.scheduled_date DESC 
    LIMIT 100
")->fetchAll();
?>

<div class="page-toolbar">
    <div>
        <h1>Allocation Monitoring</h1>
        <p>Monitor instructor task allocations and schedules.</p>
    </div>
</div>

<div class="alert alert-info">
    <strong>Read-only:</strong> Director / Department Head can monitor allocation status only.
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Instructor</th>
                        <th>Task</th>
                        <th>Date</th>
                        <th>Hours</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No task allocations found.</td></tr>
                    <?php endif; ?>
                    <?php foreach($rows as $r): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($r['instructor_name']) ?></strong></td>
                        <td><?= htmlspecialchars($r['task_type']) ?></td>
                        <td><?= formatDate($r['scheduled_date']) ?></td>
                        <td><?= htmlspecialchars($r['duration_hours']) ?></td>
                        <td><?= getStatusBadge($r['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>