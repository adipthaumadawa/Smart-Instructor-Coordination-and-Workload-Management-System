<?php
/**
 * Non-Academic Staff - Lecture Room & Laboratory Schedules
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_NON_ACADEMIC);

$pageTitle = "Lecture Room & Laboratory Schedules";

// Handle form BEFORE header (so redirect works cleanly)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->prepare("
        INSERT INTO lecture_rooms (room_name, capacity, location, room_type, status)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([
        sanitize($_POST['room_name'] ?? ''),
        (int)($_POST['capacity'] ?? 50),
        sanitize($_POST['location'] ?? ''),
        sanitize($_POST['room_type'] ?? 'Lecture Hall'),
        sanitize($_POST['status'] ?? 'Available'),
    ]);
    logActivity($_SESSION['user_id'], 'Room Created', 'Created room ' . ($_POST['room_name'] ?? ''));
    header('Location: room_schedules.php');
    exit;
}

$rooms = $pdo->query("SELECT * FROM lecture_rooms ORDER BY room_name")->fetchAll();
$bookings = $pdo->query("
    SELECT bh.*, lr.room_name, u.full_name AS booked_by
    FROM lecture_hall_bookings bh
    JOIN lecture_rooms lr ON bh.room_id = lr.id
    JOIN users u ON bh.booked_by_user_id = u.id
    ORDER BY bh.booking_date DESC
    LIMIT 50
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-toolbar">
    <div>
        <h1>Lecture Room &amp; Laboratory Schedules</h1>
        <p>Manage rooms/labs and review recent bookings.</p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <strong>Add Room/Lab</strong>
    </div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <div class="col-md-3">
                <input name="room_name" class="form-control" placeholder="Room name" required>
            </div>
            <div class="col-md-2">
                <input type="number" name="capacity" class="form-control" placeholder="Capacity" value="50">
            </div>
            <div class="col-md-3">
                <input name="location" class="form-control" placeholder="Location">
            </div>
            <div class="col-md-2">
                <select name="room_type" class="form-select">
                    <option>Lecture Hall</option>
                    <option>Laboratory</option>
                    <option>Tutorial Room</option>
                    <option>Seminar Room</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option>Available</option>
                    <option>Under Maintenance</option>
                    <option>Booked</option>
                </select>
            </div>
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary">Save Room</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><strong>Rooms</strong></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Capacity</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rooms)): ?>
                            <tr><td colspan="4" class="text-muted">No rooms yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($rooms as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['room_name']) ?></td>
                            <td><?= htmlspecialchars($r['room_type']) ?></td>
                            <td><?= (int)$r['capacity'] ?></td>
                            <td><?= getStatusBadge($r['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><strong>Recent Bookings</strong></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Room</th>
                            <th>Date/Time</th>
                            <th>Purpose</th>
                            <th>Booked By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr><td colspan="4" class="text-muted">No bookings yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td><?= htmlspecialchars($b['room_name']) ?></td>
                            <td>
                                <?= formatDate($b['booking_date']) ?><br>
                                <small>
                                    <?= function_exists('formatTime') ? formatTime($b['start_time']) : substr($b['start_time'], 0, 5) ?>
                                    -
                                    <?= function_exists('formatTime') ? formatTime($b['end_time']) : substr($b['end_time'], 0, 5) ?>
                                </small>
                            </td>
                            <td><?= htmlspecialchars($b['purpose']) ?></td>
                            <td><?= htmlspecialchars($b['booked_by']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
