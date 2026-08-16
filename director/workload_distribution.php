<?php
/**
 * Director - Workload Distribution
 * Smart Instructor Coordination and Workload Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_DIRECTOR);

$pageTitle = "Workload Distribution";
include __DIR__ . '/../includes/header.php';

$rows = $pdo->query("
    SELECT i.id, u.full_name, ast.name stream, 
           COALESCE(SUM(CASE WHEN ta.is_presentation_panel=0 AND ta.status IN('Assigned','Accepted','Completed') THEN ta.duration_hours ELSE 0 END),0) hours 
    FROM instructors i 
    JOIN users u ON i.user_id = u.id 
    JOIN academic_streams ast ON i.academic_stream_id = ast.id 
    LEFT JOIN task_assignments ta ON i.id = ta.instructor_id 
    WHERE i.status = 'active' 
    GROUP BY i.id, u.full_name, ast.name 
    ORDER BY hours DESC
")->fetchAll();
?>

<div class="page-toolbar">
    <div>
        <h1>Workload Distribution</h1>
        <p>Monitor instructor capacity, weekly hours, and department distribution.</p>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Instructor</th>
                        <th>Stream</th>
                        <th>Workload Hours</th>
                        <th>Usage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No workload data found.</td></tr>
                    <?php endif; ?>
                    <?php foreach($rows as $r): 
                        $pct = min(100, round(($r['hours'] / DEFAULT_MAX_WEEKLY_HOURS) * 100)); 
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($r['full_name']) ?></strong></td>
                        <td><?= htmlspecialchars($r['stream']) ?></td>
                        <td><?= htmlspecialchars($r['hours']) ?> hrs</td>
                        <td style="width: 25%;">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar" role="progressbar" style="width: <?= $pct ?>%;" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted"><?= $pct ?>% capacity</small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>