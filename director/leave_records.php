<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_DIRECTOR);
$pageTitle = "Leave Records Monitoring";
include __DIR__ . '/../includes/header.php';

$rows=$pdo->query("SELECT lr.*, u.full_name FROM leave_records lr JOIN instructors i ON lr.instructor_id=i.id JOIN users u ON i.user_id=u.id ORDER BY lr.created_at DESC LIMIT 100")->fetchAll();
ob_start(); ?><div class="alert alert-info"><strong>Read-only:</strong> Leave is recorded for coordination. Official HR leave approval is outside scope.</div><div class="card shadow-sm"><div class="card-body p-0"><table class="table mb-0"><thead><tr><th>Instructor</th><th>Type</th><th>Period</th><th>Reason</th><th>Status</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?=htmlspecialchars($r['full_name'])?></td><td><?=htmlspecialchars($r['leave_type'])?></td><td><?=formatDate($r['start_date'])?> - <?=formatDate($r['end_date'])?></td><td><?=htmlspecialchars($r['reason'])?></td><td><?=getStatusBadge($r['status'])?></td></tr><?php endforeach; ?></tbody></table></div></div><?php $content=ob_get_clean();

?>
<div class="container-fluid"><div class="row"><?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom"><h1 class="h2"><i class="fas fa-file-medical me-2"></i>Leave Records Monitoring</h1></div>
<?php echo $content; ?>
</main></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>