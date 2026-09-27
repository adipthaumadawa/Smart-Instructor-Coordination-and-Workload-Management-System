<?php
/**
 * Instructor - Lecture Room / Laboratory Booking
 * Smart Instructor Coordination and Workload Management System
 *
 * Lets an instructor check real venue availability (against
 * lecture_hall_bookings, the same table Project Coordinators and
 * Non-Academic Staff book into) and book a room directly, e.g. for
 * an extra consultation, makeup class or practical session that
 * isn't already on their recurring timetable.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';

checkRole(ROLE_INSTRUCTOR);

$userId = (int)$_SESSION['user_id'];
$error = '';

// ---------------------------------------------------------------
// Handle "Cancel booking" BEFORE header output
// ---------------------------------------------------------------
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $bookingId = (int)$_GET['cancel'];
    $stmt = $pdo->prepare("
        SELECT * FROM lecture_hall_bookings
        WHERE id = ? AND booked_by_user_id = ? AND status = 'Confirmed'
    ");
    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch();

    if ($booking) {
        $pdo->prepare("UPDATE lecture_hall_bookings SET status = 'Cancelled' WHERE id = ?")->execute([$bookingId]);
        logActivity($userId, 'Cancel Room Booking', "Cancelled booking #{$bookingId} for " . $booking['booking_date']);
        $_SESSION['success'] = 'Booking cancelled and the room released.';
    } else {
        $_SESSION['error'] = 'Booking not found or cannot be cancelled.';
    }
    header('Location: ' . app_url('instructor/room_booking.php'));
    exit;
}

// ---------------------------------------------------------------
// Handle "Book room" submission BEFORE header output
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_booking'])) {
    $date = sanitize($_POST['booking_date'] ?? '');
    $start = sanitize($_POST['start_time'] ?? '');
    $end = sanitize($_POST['end_time'] ?? '');
    $roomId = (int)($_POST['room_id'] ?? 0);
    $purpose = sanitize($_POST['purpose'] ?? '');

    if ($date === '' || $start === '' || $end === '' || $roomId <= 0 || $purpose === '') {
        $error = 'Please fill all required fields and select a room.';
    } elseif (strtotime($end) <= strtotime($start)) {
        $error = 'End time must be after the start time.';
    } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
        $error = 'Booking date cannot be in the past.';
    } else {
        $roomStmt = $pdo->prepare("SELECT room_name FROM lecture_rooms WHERE id = ? AND status = 'Available'");
        $roomStmt->execute([$roomId]);
        $roomName = $roomStmt->fetchColumn();

        if (!$roomName) {
            $error = 'Selected room is invalid.';
        } elseif (hasBookingConflict($roomId, $date, $start, $end)) {
            $error = 'That room was just booked for this time slot by someone else. Please check availability again.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO lecture_hall_bookings (room_id, booked_by_user_id, booking_date, start_time, end_time, purpose, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'Confirmed', NOW())
            ");
            $stmt->execute([$roomId, $userId, $date, $start, $end, $purpose]);
            logActivity($userId, 'Book Lecture Room', "Booked {$roomName} on {$date} ({$start}-{$end}): {$purpose}");
            $_SESSION['success'] = "Room booked successfully: {$roomName} on " . formatDate($date) . ".";
            header('Location: ' . app_url('instructor/room_booking.php'));
            exit;
        }
    }
}

// ---------------------------------------------------------------
// Step 1: Check room availability (GET, no side effects)
// ---------------------------------------------------------------
$checkDate = sanitize($_GET['date'] ?? '');
$checkStart = sanitize($_GET['start_time'] ?? '');
$checkEnd = sanitize($_GET['end_time'] ?? '');
$availableRooms = [];
$showBookForm = false;

if (isset($_GET['check_availability']) && $checkDate !== '' && $checkStart !== '' && $checkEnd !== '') {
    if (strtotime($checkEnd) <= strtotime($checkStart)) {
        $error = 'End time must be after the start time.';
    } elseif (strtotime($checkDate) < strtotime(date('Y-m-d'))) {
        $error = 'Date cannot be in the past.';
    } else {
        $availableRooms = getAvailableRooms($checkDate, $checkStart, $checkEnd);
        $showBookForm = true;
    }
}

// ---------------------------------------------------------------
// My bookings list
// ---------------------------------------------------------------
$view = $_GET['view'] ?? 'upcoming';
$allowedViews = ['upcoming', 'past', 'cancelled', 'all'];
if (!in_array($view, $allowedViews, true)) { $view = 'upcoming'; }

$sql = "
    SELECT bh.*, lr.room_name, lr.room_type, lr.capacity
    FROM lecture_hall_bookings bh
    JOIN lecture_rooms lr ON bh.room_id = lr.id
    WHERE bh.booked_by_user_id = :uid
";
if ($view === 'upcoming') {
    $sql .= " AND bh.booking_date >= CURDATE() AND bh.status = 'Confirmed'";
} elseif ($view === 'past') {
    $sql .= " AND bh.booking_date < CURDATE() AND bh.status = 'Confirmed'";
} elseif ($view === 'cancelled') {
    $sql .= " AND bh.status = 'Cancelled'";
}
$sql .= " ORDER BY bh.booking_date DESC, bh.start_time DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':uid' => $userId]);
$myBookings = $stmt->fetchAll();

$pageTitle = 'Room Booking';
include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1>Lecture Room Booking</h1>
                    <p>Check real-time availability and book a lecture room or laboratory for yourself.</p>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- STEP 1: Check availability -->
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header"><h5>Step 1 — Check Availability</h5></div>
                <div class="card-body">
                    <form method="GET" action="">
                        <input type="hidden" name="check_availability" value="1">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control" required min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($checkDate) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Start Time <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" class="form-control" required value="<?= htmlspecialchars($checkStart) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">End Time <span class="text-danger">*</span></label>
                                <input type="time" name="end_time" class="form-control" required value="<?= htmlspecialchars($checkEnd) ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary" style="width:100%;">Check</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- STEP 2: Available rooms + book -->
            <?php if ($showBookForm): ?>
                <div class="card" style="margin-bottom:20px;">
                    <div class="card-header"><h5>Step 2 — Select Room & Book</h5></div>
                    <div class="card-body">
                        <?php if (empty($availableRooms)): ?>
                            <p class="text-muted mb-0">No lecture halls or laboratories are free for <?= formatDate($checkDate) ?>, <?= formatTime($checkStart) ?> - <?= formatTime($checkEnd) ?>. Try a different time slot.</p>
                        <?php else: ?>
                            <form method="POST" action="">
                                <input type="hidden" name="booking_date" value="<?= htmlspecialchars($checkDate) ?>">
                                <input type="hidden" name="start_time" value="<?= htmlspecialchars($checkStart) ?>">
                                <input type="hidden" name="end_time" value="<?= htmlspecialchars($checkEnd) ?>">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Room / Laboratory <span class="text-danger">*</span></label>
                                        <select name="room_id" class="form-select" required>
                                            <option value="">Select an available room</option>
                                            <?php foreach ($availableRooms as $room): ?>
                                                <option value="<?= (int)$room['id'] ?>">
                                                    <?= htmlspecialchars($room['room_name']) ?> — <?= htmlspecialchars($room['room_type']) ?> (Capacity <?= (int)$room['capacity'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Date &amp; Time</label>
                                        <input type="text" class="form-control" disabled value="<?= formatDate($checkDate) ?>, <?= formatTime($checkStart) ?> - <?= formatTime($checkEnd) ?>">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Purpose <span class="text-danger">*</span></label>
                                        <input type="text" name="purpose" class="form-control" required placeholder="e.g. Extra consultation session for SCS2201">
                                    </div>
                                </div>
                                <button type="submit" name="create_booking" class="btn btn-primary mt-3">
                                    <span class="ui-dot" aria-hidden="true"></span>
                                    Book Room
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- My bookings -->
            <div class="card" style="margin-bottom:16px;">
                <div class="card-body d-flex gap-2 flex-wrap">
                    <a href="?view=upcoming" class="btn btn-sm <?= $view === 'upcoming' ? 'btn-primary' : 'btn-outline-secondary' ?>">Upcoming</a>
                    <a href="?view=past" class="btn btn-sm <?= $view === 'past' ? 'btn-primary' : 'btn-outline-secondary' ?>">Past</a>
                    <a href="?view=cancelled" class="btn btn-sm <?= $view === 'cancelled' ? 'btn-primary' : 'btn-outline-secondary' ?>">Cancelled</a>
                    <a href="?view=all" class="btn btn-sm <?= $view === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>My Bookings</h5>
                    <span class="text-muted small"><?= count($myBookings) ?> booking(s)</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr><th>Room / Laboratory</th><th>Date</th><th>Time</th><th>Purpose</th><th>Status</th><th class="text-end">Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($myBookings)): ?>
                                    <tr><td colspan="6" class="text-muted">No bookings found for this filter.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($myBookings as $b): ?>
                                    <tr>
                                        <td data-label="Room"><strong><?= htmlspecialchars($b['room_name']) ?></strong> <span class="text-muted small">(<?= htmlspecialchars($b['room_type']) ?>)</span></td>
                                        <td data-label="Date"><?= formatDate($b['booking_date']) ?></td>
                                        <td data-label="Time"><?= formatTime($b['start_time']) ?> - <?= formatTime($b['end_time']) ?></td>
                                        <td data-label="Purpose"><?= htmlspecialchars($b['purpose']) ?></td>
                                        <td data-label="Status"><?= getStatusBadge($b['status']) ?></td>
                                        <td data-label="Actions" class="text-end action-cell">
                                            <?php if ($b['status'] === 'Confirmed' && strtotime($b['booking_date']) >= strtotime(date('Y-m-d'))): ?>
                                                <a href="?cancel=<?= (int)$b['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this booking and release the room?')">Cancel</a>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>