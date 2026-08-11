<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_CHIEF_COORDINATOR);
$pageTitle = "Instructor Allocations";
include __DIR__ . '/../includes/header.php';

$rows = $pdo->query("SELECT ta.*, tt.name task_type, u.full_name instructor_name, ub.full_name assigned_by_name
FROM task_assignments ta
JOIN task_types tt ON ta.task_type_id=tt.id
JOIN instructors i ON ta.instructor_id=i.id
JOIN users u ON i.user_id=u.id
JOIN users ub ON ta.assigned_by=ub.id
ORDER BY ta.scheduled_date DESC, ta.start_time")->fetchAll();
ob_start(); ?>
<div class="card shadow-sm"><div class="card-body p-0"><table class="table table-hover mb-0"><thead><tr><th>Instructor</th><th>Task</th><th>Date/Time</th><th>Assigned By</th><th>Workload</th><th>Status</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?=htmlspecialchars($r['instructor_name'])?></td><td><?=htmlspecialchars($r['task_type'])?></td><td><?=formatDate($r['scheduled_date'])?><br><small><?=formatTime($r['start_time'])?> - <?=formatTime($r['end_time'])?></small></td><td><?=htmlspecialchars($r['assigned_by_name'])?></td><td><?= $r['is_presentation_panel'] ? '<span class="badge bg-info">Excluded Panel</span>' : htmlspecialchars($r['duration_hours']).' hrs' ?></td><td><?=getStatusBadge($r['status'])?></td></tr><?php endforeach; ?>
<?php if(empty($rows)): ?><tr><td colspan="6" class="text-center text-muted py-4">No allocations found.</td></tr><?php endif; ?>
</tbody></table></div></div><?php $content=ob_get_clean();

?>
<div class="container-fluid"><div class="row"><?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom"><h1 class="h2"><i class="fas fa-tasks me-2"></i>Instructor Allocations</h1></div>
<?php echo $content; ?>
</main></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>