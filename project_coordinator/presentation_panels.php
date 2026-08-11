<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_PROJECT_COORDINATOR);
$pageTitle = "Presentation Panel Management";
include __DIR__ . '/../includes/header.php';

if($_SERVER['REQUEST_METHOD']==='POST'){ try{ $pdo->prepare("INSERT INTO presentation_panel_members(presentation_session_id,instructor_id,role_in_panel) VALUES(?,?,?)")->execute([(int)$_POST['session_id'],(int)$_POST['instructor_id'],sanitize($_POST['role_in_panel'])]); $session=$pdo->prepare("SELECT session_date,start_time,end_time,venue FROM presentation_sessions WHERE id=?"); $session->execute([(int)$_POST['session_id']]); $s=$session->fetch(); if($s){ $pdo->prepare("INSERT INTO task_assignments(additional_task_request_id,task_type_id,instructor_id,assigned_by,scheduled_date,start_time,end_time,duration_hours,location,status,is_presentation_panel,notes) VALUES(NULL,4,?,?,?,?,?,TIMESTAMPDIFF(MINUTE,?,?)/60,?,'Assigned',1,'Presentation panel assignment - excluded from normal workload')")->execute([(int)$_POST['instructor_id'],$_SESSION['user_id'],$s['session_date'],$s['start_time'],$s['end_time'],$s['start_time'],$s['end_time'],$s['venue']]); } $_SESSION['success']='Panel member assigned.'; }catch(Exception $e){ $_SESSION['error']='Could not assign panel member. Maybe already assigned.'; } header('Location: presentation_panels.php'); exit; }
$sessions=$pdo->query("SELECT * FROM presentation_sessions ORDER BY session_date DESC")->fetchAll(); $instructors=getAllActiveInstructors(); $rows=$pdo->query("SELECT ppm.*, ps.title, ps.session_date, u.full_name FROM presentation_panel_members ppm JOIN presentation_sessions ps ON ppm.presentation_session_id=ps.id JOIN instructors i ON ppm.instructor_id=i.id JOIN users u ON i.user_id=u.id ORDER BY ps.session_date DESC")->fetchAll();
ob_start(); ?><?php if(isset($_SESSION['success'])):?><div class="alert alert-success"><?=$_SESSION['success']; unset($_SESSION['success']);?></div><?php endif; ?><?php if(isset($_SESSION['error'])):?><div class="alert alert-danger"><?=$_SESSION['error']; unset($_SESSION['error']);?></div><?php endif; ?>
<div class="card shadow-sm mb-4"><div class="card-header bg-white"><strong>Assign Panel Member</strong></div><div class="card-body"><form method="post" class="row g-3"><div class="col-md-4"><select name="session_id" class="form-select" required><option value="">Select session</option><?php foreach($sessions as $s): ?><option value="<?=$s['id']?>"><?=htmlspecialchars($s['title'].' - '.$s['session_date'])?></option><?php endforeach; ?></select></div><div class="col-md-4"><select name="instructor_id" class="form-select" required><option value="">Select instructor</option><?php foreach($instructors as $i): ?><option value="<?=$i['id']?>"><?=htmlspecialchars($i['display_name'])?></option><?php endforeach; ?></select></div><div class="col-md-2"><select name="role_in_panel" class="form-select"><option>Chair</option><option>Member</option><option>Examiner</option></select></div><div class="col-md-2"><button class="btn btn-primary w-100">Assign</button></div></form><small class="text-muted">Panel assignments are copied to task assignments with presentation flag, so they are excluded from normal workload.</small></div></div>
<div class="card shadow-sm"><div class="card-body p-0"><table class="table mb-0"><thead><tr><th>Session</th><th>Date</th><th>Instructor</th><th>Panel Role</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?=htmlspecialchars($r['title'])?></td><td><?=formatDate($r['session_date'])?></td><td><?=htmlspecialchars($r['full_name'])?></td><td><?=htmlspecialchars($r['role_in_panel'])?></td></tr><?php endforeach; ?></tbody></table></div></div><?php $content=ob_get_clean();

?>
<div class="container-fluid"><div class="row"><?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom"><h1 class="h2"><i class="fas fa-users me-2"></i>Presentation Panel Management</h1></div>
<?php echo $content; ?>
</main></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>