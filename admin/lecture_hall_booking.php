<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';

checkRole(ROLE_ADMIN);

$userId = (int)$_SESSION['user_id'];
$error = '';
$action = $_POST['action'] ?? '';
$today = date('Y-m-d');
$roomTypes = ['Lecture Hall', 'Laboratory', 'Tutorial Room', 'Seminar Room'];
$checkDate = trim($_POST['booking_date'] ?? $_GET['date'] ?? $today);
$checkStart = trim($_POST['start_time'] ?? $_GET['start_time'] ?? '');
$checkEnd = trim($_POST['end_time'] ?? $_GET['end_time'] ?? '');
$roomType = trim($_POST['room_type'] ?? $_GET['room_type'] ?? '');
$capacity = filter_var($_POST['capacity'] ?? $_GET['capacity'] ?? 0, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 0]
]);
$roomId = (int)($_POST['room_id'] ?? 0);
$purpose = trim($_POST['purpose'] ?? '');
$availableRooms = [];
$showBookForm = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if ($action === 'cancel_booking') {
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $stmt = $pdo->prepare("
            UPDATE lecture_hall_bookings SET status = 'Cancelled'
            WHERE id = ? AND booked_by_user_id = ? AND status = 'Confirmed'
              AND booking_date >= ?
        ");
        $stmt->execute([$bookingId, $userId, $today]);

        if ($stmt->rowCount() > 0) {
            logActivity($userId, 'Cancel Room Booking', "Cancelled booking #{$bookingId}");
            $_SESSION['success'] = 'Booking cancelled and the room released.';
        } else {
            $_SESSION['error'] = 'Booking not found or cannot be cancelled.';
        }
        header('Location: ' . app_url('admin/lecture_hall_booking.php'));
        exit;
    }
}

if (isset($_GET['check_availability']) || $action === 'book_room') {
    $parsedDate = DateTime::createFromFormat('!Y-m-d', $checkDate);

    if (!$parsedDate || $parsedDate->format('Y-m-d') !== $checkDate) {
        $error = 'Please select a valid booking date.';
    } elseif ($checkDate < $today) {
        $error = 'Booking date cannot be in the past.';
    } elseif (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $checkStart)
        || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $checkEnd)) {
        $error = 'Please enter valid start and end times.';
    } elseif ($checkEnd <= $checkStart) {
        $error = 'End time must be after the start time.';
    } elseif (($windowError = sic_booking_window_error($checkDate, $checkStart, $checkEnd)) !== null) {
        $error = $windowError;
    } elseif ($roomType !== '' && !in_array($roomType, $roomTypes, true)) {
        $error = 'Please select a valid room type.';
    } elseif ($capacity === false) {
        $error = 'Required capacity must be zero or a positive whole number.';
    } else {
        $showBookForm = true;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'book_room') {
            if ($roomId <= 0 || $purpose === '') {
                $error = 'Please select a room and enter a purpose.';
            } elseif (mb_strlen($purpose) > 200) {
                $error = 'Purpose must be 200 characters or fewer.';
            } else {
                try {
                    $pdo->beginTransaction();
                    // Serialize bookings made here for the same room before checking availability.
                    $stmt = $pdo->prepare("SELECT * FROM lecture_rooms WHERE id = ? FOR UPDATE");
                    $stmt->execute([$roomId]);
                    $room = $stmt->fetch();

                    if (!$room || $room['status'] !== 'Available') {
                        $error = 'Selected room is not available.';
                    } elseif ($roomType !== '' && $room['room_type'] !== $roomType) {
                        $error = 'Selected room does not match the requested room type.';
                    } elseif ((int)$room['capacity'] < $capacity) {
                        $error = 'Selected room does not have enough capacity.';
                    } elseif (hasBookingConflict($roomId, $checkDate, $checkStart, $checkEnd)) {
                        $error = 'That room is already booked for this time. Please select another room.';
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO lecture_hall_bookings
                                (room_id, booked_by_user_id, booking_date, start_time, end_time, purpose, status)
                            VALUES (?, ?, ?, ?, ?, ?, 'Confirmed')
                        ");
                        $stmt->execute([$roomId, $userId, $checkDate, $checkStart, $checkEnd, $purpose]);
                    }

                    if ($error !== '') {
                        $pdo->rollBack();
                    } else {
                        $pdo->commit();
                        logActivity($userId, 'Book Lecture Room', "Booked {$room['room_name']} on {$checkDate} ({$checkStart}-{$checkEnd}): {$purpose}");
                        $_SESSION['success'] = 'Room booked successfully: ' . $room['room_name'] . '.';
                        header('Location: ' . app_url('admin/lecture_hall_booking.php'));
                        exit;
                    }
                } catch (PDOException $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    error_log('Admin room booking: ' . $e->getMessage());
                    $error = 'Unable to book this room. Please try again.';
                }
            }
        }

        $availableRooms = array_values(array_filter(
            getAvailableRooms($checkDate, $checkStart, $checkEnd),
            fn($room) => ($roomType === '' || $room['room_type'] === $roomType)
                && (int)$room['capacity'] >= $capacity
        ));
    }
}

$view = $_GET['view'] ?? 'upcoming';
if (!in_array($view, ['upcoming', 'past', 'cancelled', 'all'], true)) {
    $view = 'upcoming';
}
$sql = "
    SELECT bh.*, lr.room_name, lr.room_type, lr.capacity, lr.location
    FROM lecture_hall_bookings bh
    JOIN lecture_rooms lr ON bh.room_id = lr.id
    WHERE bh.booked_by_user_id = ?
";
if ($view === 'upcoming') {
    $sql .= " AND bh.booking_date >= CURDATE() AND bh.status = 'Confirmed'";
} elseif ($view === 'past') {
    $sql .= " AND bh.booking_date < CURDATE() AND bh.status = 'Confirmed'";
} elseif ($view === 'cancelled') {
    $sql .= " AND bh.status = 'Cancelled'";
}
$sql .= ' ORDER BY bh.booking_date DESC, bh.start_time DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$bookings = $stmt->fetchAll();

$pageTitle = 'Lecture Hall Booking';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-toolbar">
    <div>
        <h1>Lecture Hall Booking</h1>
        <p>Check availability and book lecture rooms or laboratories for academic activities.</p>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success" role="status"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header"><h5>Check Availability</h5></div>
    <div class="card-body">
        <form method="GET" action="<?= htmlspecialchars(app_url('admin/lecture_hall_booking.php')) ?>">
            <input type="hidden" name="check_availability" value="1">
            <div class="row">
                <div class="col-md-4">
                    <label for="check_date" class="form-label">Date</label>
                    <input type="date" id="check_date" name="date" class="form-control" required min="<?= $today ?>" value="<?= htmlspecialchars($checkDate) ?>">
                </div>
                <div class="col-md-4">
                    <label for="check_start" class="form-label">Start Time</label>
                    <input type="time" id="check_start" name="start_time" class="form-control" required value="<?= htmlspecialchars($checkStart) ?>">
                </div>
                <div class="col-md-4">
                    <label for="check_end" class="form-label">End Time</label>
                    <input type="time" id="check_end" name="end_time" class="form-control" required value="<?= htmlspecialchars($checkEnd) ?>">
                </div>
                <div class="col-md-4">
                    <label for="check_type" class="form-label">Room Type</label>
                    <select id="check_type" name="room_type" class="form-select">
                        <option value="">All Room Types</option>
                        <?php foreach ($roomTypes as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>" <?= $roomType === $type ? 'selected' : '' ?>><?= htmlspecialchars($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="check_capacity" class="form-label">Required Capacity</label>
                    <input type="number" id="check_capacity" name="capacity" class="form-control" min="0" step="1" value="<?= $capacity === false ? '' : (int)$capacity ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Check</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($showBookForm): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5>Book a Room</h5>
            <span class="text-muted small"><?= count($availableRooms) ?> available</span>
        </div>
        <div class="card-body">
            <?php if (empty($availableRooms)): ?>
                <p class="text-muted mb-0">No rooms match this date, time and capacity. Try a different time or room type.</p>
            <?php else: ?>
                <form method="POST" action="<?= htmlspecialchars(app_url('admin/lecture_hall_booking.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="book_room">
                    <input type="hidden" name="booking_date" value="<?= htmlspecialchars($checkDate) ?>">
                    <input type="hidden" name="start_time" value="<?= htmlspecialchars($checkStart) ?>">
                    <input type="hidden" name="end_time" value="<?= htmlspecialchars($checkEnd) ?>">
                    <input type="hidden" name="room_type" value="<?= htmlspecialchars($roomType) ?>">
                    <input type="hidden" name="capacity" value="<?= (int)$capacity ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="booking_room" class="form-label">Room / Laboratory</label>
                            <select id="booking_room" name="room_id" class="form-select" required>
                                <option value="">Select an available room</option>
                                <?php foreach ($availableRooms as $room): ?>
                                    <option value="<?= (int)$room['id'] ?>" <?= $roomId === (int)$room['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($room['room_name']) ?> — <?= htmlspecialchars($room['room_type']) ?> (Capacity <?= (int)$room['capacity'] ?>)<?= $room['location'] ? ' — ' . htmlspecialchars($room['location']) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="booking_time" class="form-label">Date &amp; Time</label>
                            <input type="text" id="booking_time" class="form-control" readonly value="<?= formatDate($checkDate) ?>, <?= formatTime($checkStart) ?> - <?= formatTime($checkEnd) ?>">
                        </div>
                        <div class="col-12">
                            <label for="booking_purpose" class="form-label">Purpose</label>
                            <input type="text" id="booking_purpose" name="purpose" class="form-control" required maxlength="200" value="<?= htmlspecialchars($purpose) ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Book</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body d-flex gap-2 flex-wrap">
        <?php foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'cancelled' => 'Cancelled', 'all' => 'All'] as $filter => $label): ?>
            <a href="?view=<?= $filter ?>" class="btn btn-sm <?= $view === $filter ? 'btn-primary' : 'btn-outline-secondary' ?>" <?= $view === $filter ? 'aria-current="page"' : '' ?>><?= $label ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5>My Bookings</h5>
        <span class="text-muted small"><?= count($bookings) ?> booking(s)</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr><th>Room / Laboratory</th><th>Date</th><th>Time</th><th>Purpose</th><th>Status</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr><td colspan="6" class="text-muted">No bookings found for this filter.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td data-label="Room"><strong><?= htmlspecialchars($booking['room_name']) ?></strong><br><span class="text-muted small"><?= htmlspecialchars($booking['room_type']) ?></span></td>
                            <td data-label="Date"><?= formatDate($booking['booking_date']) ?></td>
                            <td data-label="Time"><?= formatTime($booking['start_time']) ?> - <?= formatTime($booking['end_time']) ?></td>
                            <td data-label="Purpose"><?= htmlspecialchars($booking['purpose']) ?></td>
                            <td data-label="Status"><?= getStatusBadge($booking['status']) ?></td>
                            <td data-label="Actions" class="text-end action-cell">
                                <?php if ($booking['status'] === 'Confirmed' && $booking['booking_date'] >= $today): ?>
                                    <form method="POST" action="<?= htmlspecialchars(app_url('admin/lecture_hall_booking.php')) ?>" onsubmit="return confirm('Cancel this booking and release the room?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="cancel_booking">
                                        <input type="hidden" name="booking_id" value="<?= (int)$booking['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                                    </form>
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
