<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_NON_ACADEMIC);
$pageTitle = "Lecture Room & Laboratory Schedules";
include __DIR__ . '/../includes/header.php';

if($_SERVER['REQUEST_METHOD']==='POST'){ $pdo->prepare("INSERT INTO lecture_rooms(room_name,capacity,location,room_type,status) VALUES(?,?,?,?,?)")->execute([sanitize($_POST['room_name']),(int)$_POST['capacity'],sanitize($_POST['location']),sanitize($_POST['room_type']),sanitize($_POST['status'])]); logActivity($_SESSION['user_id'],'Room Created','Created room '.$_POST['room_name']); header('Location: room_schedules.php'); exit; }
$rooms=$pdo->query("SELECT * FROM lecture_rooms ORDER BY room_name")->fetchAll(); $bookings=$pdo->query("SELECT bh.*, lr.room_name, u.full_name booked_by FROM lecture_hall_bookings bh JOIN lecture_rooms lr ON bh.room_id=lr.id JOIN users u ON bh.booked_by_user_id=u.id ORDER BY bh.booking_date DESC LIMIT 50")->fetchAll();
ob_start(); ?><div class="card shadow-sm mb-4"><div class="card-header bg-white"><strong>Add Room/Lab</strong></div><div class="card-body"><form method="post" class="row g-3"><div class="col-md-3"><input name="room_name" class="form-control" placeholder="Room name" required></div><div class="col-md-2"><input type="number" name="capacity" class="form-control" placeholder="Capacity" value="50"></div><div class="col-md-3"><input name="location" class="form-control" placeholder="Location"></div><div class="col-md-2"><select name="room_type" class="form-select"><option>Lecture Hall</option><option>Laboratory</option><option>Tutorial Room</option><option>Seminar Room</option></select></div><div class="col-md-2"><select name="status" class="form-select"><option>Available</option><option>Under Maintenance</option><option>Booked</option></select></div><div class="col-md-12"><button class="btn btn-primary">Save Room</button></div></form></div></div>
<div class="row"><div class="col-md-5"><div class="card shadow-sm"><div class="card-header bg-white"><strong>Rooms</strong></div><div class="card-body p-0"><table class="table mb-0"><thead><tr><th>Name</th><th>Type</th><th>Capacity</th><th>Status</th></tr></thead><tbody><?php foreach($rooms as $r): ?><tr><td><?=htmlspecialchars($r['room_name'])?></td><td><?=htmlspecialchars($r['room_type'])?></td><td><?=$r['capacity']?></td><td><?=getStatusBadge($r['status'])?></td></tr><?php endforeach; ?></tbody></table></div></div></div><div class="col-md-7"><div class="card shadow-sm"><div class="card-header bg-white"><strong>Recent Bookings</strong></div><div class="card-body p-0"><table class="table mb-0"><thead><tr><th>Room</th><th>Date/Time</th><th>Purpose</th><th>Booked By</th></tr></thead><tbody><?php foreach($bookings as $b): ?><tr><td><?=htmlspecialchars($b['room_name'])?></td><td><?=formatDate($b['booking_date'])?><br><small><?=formatTime($b['start_time'])?> - <?=formatTime($b['end_time'])?></small></td><td><?=htmlspecialchars($b['purpose'])?></td><td><?=htmlspecialchars($b['booked_by'])?></td></tr><?php endforeach; ?></tbody></table></div></div></div></div><?php $content=ob_get_clean();

?>
<div class="container-fluid"><div class="row"><?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom"><h1 class="h2"><i class="fas fa-door-open me-2"></i>Lecture Room & Laboratory Schedules</h1></div>
<?php echo $content; ?>
</main></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>