<?php
/**
 * ============================================================
 * COORDINATOR
 * LECTURE HALL BOOKING
 * Smart Instructor Coordination and Workload Management System
 *
 * Features:
 * - Check room availability
 * - Book lecture hall / laboratory
 * - View current bookings
 * - Cancel own booking
 * ============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';

checkRole(ROLE_COORDINATOR);

$pageTitle = "Lecture Hall Booking";

$userId = (int)($_SESSION['user_id'] ?? 0);

$error = '';
$success = '';

/*
|--------------------------------------------------------------------------
| Handle POST requests
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | BOOK ROOM
    |--------------------------------------------------------------------------
    */

    if ($action === 'book_room') {

        $booking_date       = $_POST['booking_date'] ?? '';
        $start_time         = $_POST['booking_start_time'] ?? '';
        $end_time           = $_POST['booking_end_time'] ?? '';
        $room_id            = (int)($_POST['booking_room'] ?? 0);
        $purpose            = trim($_POST['booking_purpose'] ?? '');
        $capacity           = (int)($_POST['booking_capacity'] ?? 0);
        $remarks            = trim($_POST['booking_remarks'] ?? '');

        if (
            $booking_date === '' ||
            $start_time === '' ||
            $end_time === '' ||
            $room_id <= 0 ||
            $purpose === '' ||
            $capacity <= 0
        ) {

            $error = 'Please fill all required fields.';

        } elseif ($end_time <= $start_time) {

            $error = 'End time must be later than start time.';

        } else {

            try {

                /*
                 * Check whether the room is already booked
                 * during the selected time.
                 */
                $checkStmt = $pdo->prepare("
                    SELECT id
                    FROM lecture_hall_bookings
                    WHERE room_id = ?
                      AND booking_date = ?
                      AND status = 'Booked'
                      AND start_time < ?
                      AND end_time > ?
                    LIMIT 1
                ");

                $checkStmt->execute([
                    $room_id,
                    $booking_date,
                    $end_time,
                    $start_time
                ]);

                if ($checkStmt->fetch()) {

                    $error = 'The selected room is already booked during this time.';

                } else {

                    /*
                     * Get room capacity.
                     */
                    $roomStmt = $pdo->prepare("
                        SELECT capacity
                        FROM lecture_halls
                        WHERE id = ?
                        LIMIT 1
                    ");

                    $roomStmt->execute([$room_id]);

                    $room = $roomStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$room) {

                        $error = 'Selected room does not exist.';

                    } elseif ((int)$room['capacity'] < $capacity) {

                        $error = 'The selected room does not have enough capacity.';

                    } else {

                        $insertStmt = $pdo->prepare("
                            INSERT INTO lecture_hall_bookings
                            (
                                room_id,
                                booked_by,
                                booking_date,
                                start_time,
                                end_time,
                                purpose,
                                required_capacity,
                                remarks,
                                status,
                                created_at
                            )
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Booked', NOW())
                        ");

                        $insertStmt->execute([
                            $room_id,
                            $userId,
                            $booking_date,
                            $start_time,
                            $end_time,
                            $purpose,
                            $capacity,
                            $remarks
                        ]);

                        $success = 'Lecture room booked successfully.';
                    }
                }

            } catch (PDOException $e) {

                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CANCEL BOOKING
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'cancel_booking') {

        $bookingId = (int)($_POST['booking_id'] ?? 0);

        if ($bookingId <= 0) {

            $error = 'Invalid booking.';

        } else {

            try {

                /*
                 * Coordinator can cancel only their own booking.
                 */
                $cancelStmt = $pdo->prepare("
                    UPDATE lecture_hall_bookings
                    SET status = 'Cancelled'
                    WHERE id = ?
                      AND booked_by = ?
                      AND status = 'Booked'
                ");

                $cancelStmt->execute([
                    $bookingId,
                    $userId
                ]);

                if ($cancelStmt->rowCount() > 0) {

                    $success = 'Booking cancelled successfully.';

                } else {

                    $error = 'Booking could not be cancelled.';
                }

            } catch (PDOException $e) {

                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| CHECK AVAILABLE ROOMS
|--------------------------------------------------------------------------
*/

$availableRooms = [];

if (
    isset($_POST['action']) &&
    $_POST['action'] === 'check_available'
) {

    $checkDate     = $_POST['check_date'] ?? '';
    $checkStart    = $_POST['check_start_time'] ?? '';
    $checkEnd      = $_POST['check_end_time'] ?? '';
    $roomType      = $_POST['check_room_type'] ?? '';
    $requiredCap   = (int)($_POST['check_capacity'] ?? 0);

    if (
        $checkDate === '' ||
        $checkStart === '' ||
        $checkEnd === ''
    ) {

        $error = 'Please enter date and start/end time.';

    } elseif ($checkEnd <= $checkStart) {

        $error = 'End time must be later than start time.';

    } else {

        try {

            $sql = "
                SELECT
                    lh.id,
                    lh.room_name,
                    lh.room_type,
                    lh.capacity,
                    lh.location
                FROM lecture_halls lh
                WHERE lh.status = 'Available'
                  AND lh.capacity >= ?
            ";

            $params = [$requiredCap];

            if ($roomType !== '') {

                $sql .= " AND lh.room_type = ?";
                $params[] = $roomType;
            }

            $sql .= "
                AND NOT EXISTS (
                    SELECT 1
                    FROM lecture_hall_bookings lhb
                    WHERE lhb.room_id = lh.id
                      AND lhb.booking_date = ?
                      AND lhb.status = 'Booked'
                      AND lhb.start_time < ?
                      AND lhb.end_time > ?
                )
                ORDER BY lh.capacity ASC, lh.room_name ASC
            ";

            $params[] = $checkDate;
            $params[] = $checkEnd;
            $params[] = $checkStart;

            $availableStmt = $pdo->prepare($sql);
            $availableStmt->execute($params);

            $availableRooms = $availableStmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {

            $error = 'Unable to check room availability.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| GET ROOMS
|--------------------------------------------------------------------------
*/

$rooms = [];

try {

    $roomStmt = $pdo->query("
        SELECT
            id,
            room_name,
            room_type,
            capacity,
            location
        FROM lecture_halls
        WHERE status = 'Available'
        ORDER BY room_name ASC
    ");

    $rooms = $roomStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $rooms = [];
}


/*
|--------------------------------------------------------------------------
| GET CURRENT BOOKINGS
|--------------------------------------------------------------------------
*/

$bookings = [];

try {

    $bookingStmt = $pdo->prepare("
        SELECT
            lhb.id,
            lhb.booking_date,
            lhb.start_time,
            lhb.end_time,
            lhb.purpose,
            lhb.required_capacity,
            lhb.remarks,
            lhb.status,
            lh.room_name,
            lh.room_type,
            lh.location
        FROM lecture_hall_bookings lhb
        JOIN lecture_halls lh
            ON lhb.room_id = lh.id
        WHERE lhb.booked_by = ?
          AND lhb.status = 'Booked'
        ORDER BY
            lhb.booking_date ASC,
            lhb.start_time ASC
    ");

    $bookingStmt->execute([$userId]);

    $bookings = $bookingStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $bookings = [];
}


include __DIR__ . '/../includes/header.php';
?>


<div class="page-toolbar">
    <div>
        <h1>Lecture Hall Booking</h1>
        <p>Check lecture room availability and book lecture rooms and laboratories for academic activities.</p>
    </div>
</div>

<?php if ($success !== ''): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>


<!-- ============================================================
     CHECK AVAILABLE
============================================================ -->

<div class="card mb-4">
    <div class="card-header">
        <h5>Check Available Lecture Rooms</h5>
    </div>

    <div class="card-body">

        <form method="POST">

            <input type="hidden" name="action" value="check_available">

            <div class="row g-3">

                <div class="col-md-4">
                    <label for="check_date" class="form-label fw-bold">
                        Date
                    </label>

                    <input
                        type="date"
                        id="check_date"
                        name="check_date"
                        class="form-control"
                        value="<?= htmlspecialchars($_POST['check_date'] ?? '') ?>"
                    >
                </div>


                <div class="col-md-4">
                    <label for="check_start_time" class="form-label fw-bold">
                        Start Time
                    </label>

                    <input
                        type="time"
                        id="check_start_time"
                        name="check_start_time"
                        class="form-control"
                        value="<?= htmlspecialchars($_POST['check_start_time'] ?? '') ?>"
                    >
                </div>


                <div class="col-md-4">
                    <label for="check_end_time" class="form-label fw-bold">
                        End Time
                    </label>

                    <input
                        type="time"
                        id="check_end_time"
                        name="check_end_time"
                        class="form-control"
                        value="<?= htmlspecialchars($_POST['check_end_time'] ?? '') ?>"
                    >
                </div>


                <div class="col-md-4">
                    <label for="check_room_type" class="form-label fw-bold">
                        Room Type
                    </label>

                    <select
                        id="check_room_type"
                        name="check_room_type"
                        class="form-select"
                    >
                        <option value="">All Room Types</option>

                        <option
                            value="Lecture Hall"
                            <?= (($_POST['check_room_type'] ?? '') === 'Lecture Hall') ? 'selected' : '' ?>
                        >
                            Lecture Hall
                        </option>

                        <option
                            value="Laboratory"
                            <?= (($_POST['check_room_type'] ?? '') === 'Laboratory') ? 'selected' : '' ?>
                        >
                            Laboratory
                        </option>
                    </select>
                </div>


                <div class="col-md-4">
                    <label for="check_capacity" class="form-label fw-bold">
                        Required Capacity
                    </label>

                    <input
                        type="number"
                        id="check_capacity"
                        name="check_capacity"
                        class="form-control"
                        placeholder="Enter capacity"
                        min="1"
                        value="<?= htmlspecialchars($_POST['check_capacity'] ?? '') ?>"
                    >
                </div>


                <div class="col-md-4">
                    <label for="check_location" class="form-label fw-bold">
                        Location
                    </label>

                    <input
                        type="text"
                        id="check_location"
                        name="check_location"
                        class="form-control"
                        placeholder="Location"
                    >
                </div>

            </div>


            <div class="row mt-4">
                <div class="col-12 text-end">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Check Available
                    </button>

                </div>
            </div>

        </form>


        <?php if (!empty($availableRooms)): ?>

            <div class="mt-4">

                <div class="card">
                    <div class="card-header">
                        <h5>Available Rooms</h5>
                        <span class="text-muted small">
                            <?= count($availableRooms) ?> rooms
                        </span>
                    </div>

                    <div class="card-body">

                        <div class="table-responsive">

                            <table class="table table-hover align-middle">

                                <thead>
                                    <tr>
                                        <th>Room</th>
                                        <th>Room Type</th>
                                        <th>Capacity</th>
                                        <th>Location</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <?php foreach ($availableRooms as $room): ?>

                                        <tr>

                                            <td>
                                                <strong>
                                                    <?= htmlspecialchars($room['room_name']) ?>
                                                </strong>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($room['room_type']) ?>
                                            </td>

                                            <td>
                                                <?= (int)$room['capacity'] ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($room['location']) ?>
                                            </td>

                                            <td>

                                                <button
                                                    type="button"
                                                    class="btn btn-primary btn-sm"
                                                    onclick="selectRoom(<?= (int)$room['id'] ?>)"
                                                >
                                                    Select
                                                </button>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    </div>
                </div>

            </div>

        <?php elseif (
            isset($_POST['action']) &&
            $_POST['action'] === 'check_available' &&
            $error === ''
        ): ?>

            <div class="alert alert-info mt-4">
                No rooms are available for the selected time.
            </div>

        <?php endif; ?>

    </div>
</div>


<!-- ============================================================
     BOOK A LECTURE ROOM
============================================================ -->

<div class="card mb-4">

    <div class="card-header">
        <h5>Book a Lecture Room &amp; Laboratory</h5>
    </div>

    <div class="card-body">

        <form method="POST">

            <input
                type="hidden"
                name="action"
                value="book_room"
            >

            <div class="row g-3">

                <div class="col-md-4">
                    <label for="booking_date" class="form-label fw-bold">
                        Date
                    </label>

                    <input
                        type="date"
                        id="booking_date"
                        name="booking_date"
                        class="form-control"
                        required
                    >
                </div>


                <div class="col-md-4">
                    <label for="booking_start_time" class="form-label fw-bold">
                        Start Time
                    </label>

                    <input
                        type="time"
                        id="booking_start_time"
                        name="booking_start_time"
                        class="form-control"
                        required
                    >
                </div>


                <div class="col-md-4">
                    <label for="booking_end_time" class="form-label fw-bold">
                        End Time
                    </label>

                    <input
                        type="time"
                        id="booking_end_time"
                        name="booking_end_time"
                        class="form-control"
                        required
                    >
                </div>


                <div class="col-md-4">
                    <label for="booking_room" class="form-label fw-bold">
                        Lecture Room / Laboratory
                    </label>

                    <select
                        id="booking_room"
                        name="booking_room"
                        class="form-select"
                        required
                    >
                        <option value="">
                            Select Room / Laboratory
                        </option>

                        <?php foreach ($rooms as $room): ?>

                            <option value="<?= (int)$room['id'] ?>">

                                <?= htmlspecialchars($room['room_name']) ?>

                                -
                                <?= htmlspecialchars($room['room_type']) ?>

                                -
                                Capacity:
                                <?= (int)$room['capacity'] ?>

                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>


                <div class="col-md-4">
                    <label for="booking_purpose" class="form-label fw-bold">
                        Purpose
                    </label>

                    <input
                        type="text"
                        id="booking_purpose"
                        name="booking_purpose"
                        class="form-control"
                        placeholder="Enter booking purpose"
                        required
                    >
                </div>


                <div class="col-md-4">
                    <label for="booking_capacity" class="form-label fw-bold">
                        Required Capacity
                    </label>

                    <input
                        type="number"
                        id="booking_capacity"
                        name="booking_capacity"
                        class="form-control"
                        placeholder="Enter capacity"
                        min="1"
                        required
                    >
                </div>


                <div class="col-12">

                    <label for="booking_remarks" class="form-label fw-bold">
                        Remarks
                    </label>

                    <textarea
                        id="booking_remarks"
                        name="booking_remarks"
                        class="form-control"
                        placeholder="Additional remarks"
                        rows="4"
                        style="resize: vertical;"
                    ></textarea>

                </div>

            </div>


            <div class="row mt-4">

                <div class="col-12 text-end">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Book Lecture Room
                    </button>

                </div>

            </div>

        </form>

    </div>

</div>


<!-- ============================================================
     CURRENT BOOKINGS
============================================================ -->

<div class="card">

    <div class="card-header">

        <h5>Current Bookings</h5>

        <span class="text-muted small">
            <?= count($bookings) ?> records
        </span>

    </div>


    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>

                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Room / Laboratory</th>
                        <th>Purpose</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>

                </thead>


                <tbody>

                    <?php if (!empty($bookings)): ?>

                        <?php foreach ($bookings as $booking): ?>

                            <tr>

                                <td data-label="Date">

                                    <?= htmlspecialchars(
                                        date(
                                            'd M Y',
                                            strtotime($booking['booking_date'])
                                        )
                                    ) ?>

                                </td>


                                <td data-label="Time">

                                    <?= htmlspecialchars(
                                        date(
                                            'h:i A',
                                            strtotime($booking['start_time'])
                                        )
                                    ) ?>

                                    -

                                    <?= htmlspecialchars(
                                        date(
                                            'h:i A',
                                            strtotime($booking['end_time'])
                                        )
                                    ) ?>

                                </td>


                                <td data-label="Room / Laboratory">

                                    <strong>
                                        <?= htmlspecialchars(
                                            $booking['room_name']
                                        ) ?>
                                    </strong>

                                    <br>

                                    <small class="text-muted">
                                        <?= htmlspecialchars(
                                            $booking['room_type']
                                        ) ?>
                                    </small>

                                </td>


                                <td data-label="Purpose">

                                    <?= htmlspecialchars(
                                        $booking['purpose']
                                    ) ?>

                                </td>


                                <td data-label="Capacity">

                                    <?= (int)$booking['required_capacity'] ?>

                                </td>


                                <td data-label="Status">

                                    <span class="badge bg-secondary">
                                        <?= htmlspecialchars(
                                            $booking['status']
                                        ) ?>
                                    </span>

                                </td>


                                <td data-label="Action">

                                    <form
                                        method="POST"
                                        onsubmit="return confirm(
                                            'Are you sure you want to cancel this booking?'
                                        );"
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="cancel_booking"
                                        >

                                        <input
                                            type="hidden"
                                            name="booking_id"
                                            value="<?= (int)$booking['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-danger btn-sm"
                                        >
                                            Cancel
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="7"
                                class="text-muted text-center py-3"
                            >
                                You currently have no active lecture hall bookings.
                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>



<script>

function selectRoom(roomId) {

    const roomSelect =
        document.getElementById('booking_room');

    if (!roomSelect) {
        return;
    }

    roomSelect.value = roomId;

    const bookingCard =
        document.querySelector('.book-room-card');

    if (bookingCard) {

        bookingCard.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });

    }

}

</script>


<?php
include __DIR__ . '/../includes/footer.php';
?>