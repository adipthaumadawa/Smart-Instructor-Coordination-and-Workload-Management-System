<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_PROJECT_COORDINATOR);
$pageTitle = "Schedule Presentation Session";
include __DIR__ . '/../includes/header.php';

if($_SERVER['REQUEST_METHOD']==='POST'){ $pdo->prepare("INSERT INTO presentation_sessions(title,course_code,session_date,start_time,end_time,venue,project_coordinator_id,status) VALUES(?,?,?,?,?,?,?, 'Scheduled')")->execute([sanitize($_POST['title']),sanitize($_POST['course_code']),$_POST['session_date'],$_POST['start_time'],$_POST['end_time'],sanitize($_POST['venue']),$_SESSION['user_id']]); logActivity($_SESSION['user_id'],'Presentation Session','Created presentation session'); $_SESSION['success']='Presentation session scheduled.'; header('Location: presentation_sessions.php'); exit; }
ob_start(); ?><div class="card shadow-sm"><div class="card-body"><form method="post" class="row g-3"><div class="col-md-6"><label class="form-label">Session Title</label><input name="title" class="form-control" required></div><div class="col-md-3"><label class="form-label">Course Code</label><input name="course_code" class="form-control"></div><div class="col-md-3"><label class="form-label">Venue</label><input name="venue" class="form-control"></div><div class="col-md-3"><label class="form-label">Date</label><input type="date" name="session_date" class="form-control" required></div><div class="col-md-3"><label class="form-label">Start Time</label><input type="time" name="start_time" class="form-control" required></div><div class="col-md-3"><label class="form-label">End Time</label><input type="time" name="end_time" class="form-control" required></div><div class="col-md-12"><button class="btn btn-primary">Schedule Session</button> <a href="presentation_sessions.php" class="btn btn-outline-secondary">Back</a></div></form></div></div><?php $content=ob_get_clean();

?>
<div class="container-fluid"><div class="row"><?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom"><h1 class="h2"><i class="fas fa-calendar-plus me-2"></i>Schedule Presentation Session</h1></div>
<?php echo $content; ?>
</main></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>