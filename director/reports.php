<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_DIRECTOR);
$pageTitle = "Director Reports";
include __DIR__ . '/../includes/header.php';

$stats=['Instructors'=>$pdo->query("SELECT COUNT(*) FROM instructors")->fetchColumn(),'Active Tasks'=>$pdo->query("SELECT COUNT(*) FROM task_assignments WHERE status IN('Assigned','Accepted')")->fetchColumn(),'Room Bookings'=>$pdo->query("SELECT COUNT(*) FROM lecture_hall_bookings WHERE status='Confirmed'")->fetchColumn(),'Presentation Sessions'=>$pdo->query("SELECT COUNT(*) FROM presentation_sessions")->fetchColumn()];
ob_start(); ?><div class="row g-3"><?php foreach($stats as $k=>$v): ?><div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><h6 class="text-muted"><?=htmlspecialchars($k)?></h6><h2><?=$v?></h2></div></div></div><?php endforeach; ?></div><div class="card shadow-sm mt-4"><div class="card-body"><a href="workload_distribution.php" class="btn btn-primary">Workload Distribution</a> <a href="allocation_monitoring.php" class="btn btn-outline-primary">Allocations</a> <a href="leave_records.php" class="btn btn-outline-primary">Leave Records</a></div></div><?php $content=ob_get_clean();

?>
<div class="container-fluid"><div class="row"><?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom"><h1 class="h2"><i class="fas fa-file-alt me-2"></i>Director Reports</h1></div>
<?php echo $content; ?>
</main></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>