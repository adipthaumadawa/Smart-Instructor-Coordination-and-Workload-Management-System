<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_DIRECTOR);
$pageTitle = "Workload Distribution";
include __DIR__ . '/../includes/header.php';

$rows=$pdo->query("SELECT i.id, u.full_name, ast.name stream, COALESCE(SUM(CASE WHEN ta.is_presentation_panel=0 AND ta.status IN('Assigned','Accepted','Completed') THEN ta.duration_hours ELSE 0 END),0) hours FROM instructors i JOIN users u ON i.user_id=u.id JOIN academic_streams ast ON i.academic_stream_id=ast.id LEFT JOIN task_assignments ta ON i.id=ta.instructor_id WHERE i.status='active' GROUP BY i.id,u.full_name,ast.name ORDER BY hours DESC")->fetchAll();
ob_start(); ?><div class="card shadow-sm"><div class="card-body p-0"><table class="table table-hover mb-0"><thead><tr><th>Instructor</th><th>Stream</th><th>Workload Hours</th><th>Usage</th></tr></thead><tbody><?php foreach($rows as $r): $pct=min(100,round(($r['hours']/DEFAULT_MAX_WEEKLY_HOURS)*100)); ?><tr><td><?=htmlspecialchars($r['full_name'])?></td><td><?=htmlspecialchars($r['stream'])?></td><td><?=$r['hours']?></td><td><div class="progress"><div class="progress-bar" style="width: <?=$pct?>%"><?=$pct?>%</div></div></td></tr><?php endforeach; ?></tbody></table></div></div><?php $content=ob_get_clean();

?>
<div class="container-fluid"><div class="row"><?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom"><h1 class="h2"><i class="fas fa-chart-pie me-2"></i>Workload Distribution</h1></div>
<?php echo $content; ?>
</main></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>