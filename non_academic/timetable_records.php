<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_NON_ACADEMIC);
$pageTitle = "Timetable Records";
include __DIR__ . '/../includes/header.php';

if($_SERVER['REQUEST_METHOD']==='POST'){ $pdo->prepare("INSERT INTO timetable_slots(instructor_id,day_of_week,start_time,end_time,subject,location,semester,academic_year) VALUES(?,?,?,?,?,?,?,?)")->execute([(int)$_POST['instructor_id'],sanitize($_POST['day_of_week']),$_POST['start_time'],$_POST['end_time'],sanitize($_POST['subject']),sanitize($_POST['location']),sanitize($_POST['semester']),sanitize($_POST['academic_year'])]); logActivity($_SESSION['user_id'],'Timetable Record','Created timetable slot'); header('Location: timetable_records.php'); exit; }
$instructors=getAllActiveInstructors(); $rows=$pdo->query("SELECT ts.*, u.full_name FROM timetable_slots ts JOIN instructors i ON ts.instructor_id=i.id JOIN users u ON i.user_id=u.id ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time")->fetchAll();
ob_start(); ?><div class="card shadow-sm mb-4"><div class="card-header bg-white"><strong>Add Timetable Slot</strong></div><div class="card-body"><form method="post" class="row g-3"><div class="col-md-3"><select name="instructor_id" class="form-select" required><option value="">Instructor</option><?php foreach($instructors as $i): ?><option value="<?=$i['id']?>"><?=htmlspecialchars($i['display_name'])?></option><?php endforeach; ?></select></div><div class="col-md-2"><select name="day_of_week" class="form-select"><option>Monday</option><option>Tuesday</option><option>Wednesday</option><option>Thursday</option><option>Friday</option><option>Saturday</option><option>Sunday</option></select></div><div class="col-md-1"><input type="time" name="start_time" class="form-control" required></div><div class="col-md-1"><input type="time" name="end_time" class="form-control" required></div><div class="col-md-2"><input name="subject" class="form-control" placeholder="Subject" required></div><div class="col-md-1"><input name="location" class="form-control" placeholder="Room"></div><div class="col-md-1"><input name="semester" class="form-control" value="Semester 1"></div><div class="col-md-1"><input name="academic_year" class="form-control" value="2025/2026"></div><div class="col-md-12"><button class="btn btn-primary">Save Slot</button></div></form></div></div>
<div class="card shadow-sm"><div class="card-body p-0"><table class="table mb-0"><thead><tr><th>Instructor</th><th>Day</th><th>Time</th><th>Subject</th><th>Location</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?=htmlspecialchars($r['full_name'])?></td><td><?=htmlspecialchars($r['day_of_week'])?></td><td><?=formatTime($r['start_time'])?> - <?=formatTime($r['end_time'])?></td><td><?=htmlspecialchars($r['subject'])?></td><td><?=htmlspecialchars($r['location'])?></td></tr><?php endforeach; ?></tbody></table></div></div><?php $content=ob_get_clean();

?>
<div class="container-fluid"><div class="row"><?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom"><h1 class="h2"><i class="fas fa-calendar me-2"></i>Timetable Records</h1></div>
<?php echo $content; ?>
</main></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>