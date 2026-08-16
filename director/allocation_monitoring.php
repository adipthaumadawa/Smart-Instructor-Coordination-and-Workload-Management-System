<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_DIRECTOR);
$pageTitle = "Allocation Monitoring";
include __DIR__ . '/../includes/header.php';

$rows=$pdo->query("SELECT ta.*, tt.name task_type, u.full_name instructor_name FROM task_assignments ta JOIN task_types tt ON ta.task_type_id=tt.id JOIN instructors i ON ta.instructor_id=i.id JOIN users u ON i.user_id=u.id ORDER BY ta.scheduled_date DESC LIMIT 100")->fetchAll();
ob_start(); ?><div class="alert alert-info"><strong>Read-only:</strong> Director / Department Head can monitor allocation status only.</div><div class="card shadow-sm"><div class="card-body p-0"><table class="table table-hover mb-0"><thead><tr><th>Instructor</th><th>Task</th><th>Date</th><th>Hours</th><th>Status</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?=htmlspecialchars($r['instructor_name'])?></td><td><?=htmlspecialchars($r['task_type'])?></td><td><?=formatDate($r['scheduled_date'])?></td><td><?=$r['duration_hours']?></td><td><?=getStatusBadge($r['status'])?></td></tr><?php endforeach; ?></tbody></table></div></div><?php $content=ob_get_clean();

?>
<div class="container-fluid"><div class="row"><?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom"><h1 class="h2"><i class="fas fa-eye me-2"></i>Allocation Monitoring</h1></div>
<?php echo $content; ?>
</main></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>