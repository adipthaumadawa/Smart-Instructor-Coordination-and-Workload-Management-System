<?php
/**
 * ============================================================
 * ROOM SCHEDULES
 * Smart Instructor Coordination and Workload Management System
 * Non-Academic Staff
 * ============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';

checkRole(ROLE_NON_ACADEMIC);

$pageTitle = "Room Schedules";


/*
|--------------------------------------------------------------------------
| SESSION MESSAGES
|--------------------------------------------------------------------------
*/

$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';

unset($_SESSION['success']);
unset($_SESSION['error']);


/*
|--------------------------------------------------------------------------
| ADD ROOM
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['save_room'])
) {

    $roomName = trim($_POST['room_name'] ?? '');
    $capacity = trim($_POST['capacity'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $roomType = trim($_POST['room_type'] ?? '');
    $status   = trim($_POST['status'] ?? 'Available');

    try {

        if ($roomName === '') {
            throw new Exception(
                'Please enter the room name.'
            );
        }

        if (
            $capacity === '' ||
            !is_numeric($capacity) ||
            (int)$capacity <= 0
        ) {
            throw new Exception(
                'Please enter a valid capacity.'
            );
        }

        if ($location === '') {
            throw new Exception(
                'Please enter the location.'
            );
        }

        if (
            !in_array(
                $roomType,
                [
                    'Lecture Hall',
                    'Lecture Room',
                    'Laboratory'
                ],
                true
            )
        ) {
            throw new Exception(
                'Please select a valid room type.'
            );
        }

        if (
            !in_array(
                $status,
                [
                    'Available',
                    'Maintenance',
                    'Inactive'
                ],
                true
            )
        ) {
            throw new Exception(
                'Please select a valid status.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK DUPLICATE ROOM NAME
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM lecture_rooms
            WHERE room_name = ?
        ");

        $stmt->execute([
            $roomName
        ]);

        if ((int)$stmt->fetchColumn() > 0) {

            throw new Exception(
                'A room with this name already exists.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | INSERT
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            INSERT INTO lecture_rooms
            (
                room_name,
                capacity,
                location,
                room_type,
                status
            )
            VALUES
            (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $roomName,
            (int)$capacity,
            $location,
            $roomType,
            $status
        ]);


        $_SESSION['success'] =
            'Room added successfully.';

    } catch (Throwable $e) {

        $_SESSION['error'] =
            $e->getMessage();
    }


    header(
        'Location: room_schedules.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| UPDATE ROOM
| UPDATE IS IDENTIFIED BY OLD ROOM NAME
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_room'])
) {

    $oldRoomName =
        trim($_POST['old_room_name'] ?? '');

    $newRoomName =
        trim($_POST['new_room_name'] ?? '');

    $newCapacity =
        trim($_POST['new_capacity'] ?? '');

    $newLocation =
        trim($_POST['new_location'] ?? '');

    $newRoomType =
        trim($_POST['new_room_type'] ?? '');

    $newStatus =
        trim($_POST['new_status'] ?? '');


    try {

        if ($oldRoomName === '') {

            throw new Exception(
                'Old room name is missing.'
            );
        }

        if ($newRoomName === '') {

            throw new Exception(
                'Please enter the new room name.'
            );
        }

        if (
            $newCapacity === '' ||
            !is_numeric($newCapacity) ||
            (int)$newCapacity <= 0
        ) {

            throw new Exception(
                'Please enter a valid capacity.'
            );
        }

        if ($newLocation === '') {

            throw new Exception(
                'Please enter the new location.'
            );
        }

        if (
            !in_array(
                $newRoomType,
                [
                    'Lecture Hall',
                    'Lecture Room',
                    'Laboratory'
                ],
                true
            )
        ) {

            throw new Exception(
                'Please select a valid room type.'
            );
        }

        if (
            !in_array(
                $newStatus,
                [
                    'Available',
                    'Maintenance',
                    'Inactive'
                ],
                true
            )
        ) {

            throw new Exception(
                'Please select a valid status.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK OLD ROOM
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT id
            FROM lecture_rooms
            WHERE room_name = ?
            LIMIT 1
        ");

        $stmt->execute([
            $oldRoomName
        ]);

        $oldRoom =
            $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$oldRoom) {

            throw new Exception(
                'The selected room could not be found.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK DUPLICATE NEW ROOM NAME
        |--------------------------------------------------------------------------
        */

        if ($newRoomName !== $oldRoomName) {

            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM lecture_rooms
                WHERE room_name = ?
            ");

            $stmt->execute([
                $newRoomName
            ]);

            if ((int)$stmt->fetchColumn() > 0) {

                throw new Exception(
                    'Another room already uses this name.'
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE USING OLD ROOM NAME
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            UPDATE lecture_rooms
            SET
                room_name = ?,
                capacity = ?,
                location = ?,
                room_type = ?,
                status = ?
            WHERE room_name = ?
        ");

        $stmt->execute([
            $newRoomName,
            (int)$newCapacity,
            $newLocation,
            $newRoomType,
            $newStatus,
            $oldRoomName
        ]);


        $_SESSION['success'] =
            'Room updated successfully.';

    } catch (Throwable $e) {

        $_SESSION['error'] =
            $e->getMessage();
    }


    header(
        'Location: room_schedules.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| DELETE ROOM
| DELETE IS IDENTIFIED BY ROOM NAME
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_room'])
) {

    $roomName =
        trim($_POST['room_name'] ?? '');


    try {

        if ($roomName === '') {

            throw new Exception(
                'Invalid room selected.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | FIND ROOM ID
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT id
            FROM lecture_rooms
            WHERE room_name = ?
            LIMIT 1
        ");

        $stmt->execute([
            $roomName
        ]);

        $room =
            $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$room) {

            throw new Exception(
                'Room not found.'
            );
        }


        $roomId =
            (int)$room['id'];


        /*
        |--------------------------------------------------------------------------
        | CHECK LECTURE HALL BOOKINGS
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM lecture_hall_bookings
            WHERE room_id = ?
        ");

        $stmt->execute([
            $roomId
        ]);

        $bookingCount =
            (int)$stmt->fetchColumn();


        /*
        |--------------------------------------------------------------------------
        | DO NOT DELETE IF BOOKINGS EXIST
        |--------------------------------------------------------------------------
        */

        if ($bookingCount > 0) {

            throw new Exception(
                'Cannot delete "' .
                $roomName .
                '". This room has ' .
                $bookingCount .
                ' existing lecture hall booking(s). ' .
                'Please change the room status to Inactive instead.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | DELETE
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            DELETE FROM lecture_rooms
            WHERE room_name = ?
        ");

        $stmt->execute([
            $roomName
        ]);


        $_SESSION['success'] =
            'Room "' .
            $roomName .
            '" deleted successfully.';

    } catch (Throwable $e) {

        $_SESSION['error'] =
            $e->getMessage();
    }


    header(
        'Location: room_schedules.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| GET ROOM FOR EDIT
|--------------------------------------------------------------------------
*/

$editRoom = null;


if (
    isset($_GET['edit']) &&
    trim($_GET['edit']) !== ''
) {

    $editRoomName =
        trim($_GET['edit']);


    try {

        $stmt = $pdo->prepare("
            SELECT
                id,
                room_name,
                capacity,
                location,
                room_type,
                status,
                created_at
            FROM lecture_rooms
            WHERE room_name = ?
            LIMIT 1
        ");

        $stmt->execute([
            $editRoomName
        ]);


        $editRoom =
            $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$editRoom) {

            $error =
                'Room not found.';
        }

    } catch (Throwable $e) {

        $error =
            'Unable to load the selected room.';
    }
}


/*
|--------------------------------------------------------------------------
| GET ALL ROOMS
|--------------------------------------------------------------------------
*/

$rooms = [];


try {

    $stmt = $pdo->query("
        SELECT
            id,
            room_name,
            capacity,
            location,
            room_type,
            status,
            created_at
        FROM lecture_rooms
        ORDER BY room_name ASC
    ");


    $rooms =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {

    $error =
        'Unable to load room records.';
}


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

include __DIR__ . '/../includes/header.php';

?>


<style>

/* ============================================================
   ROOM SCHEDULE PAGE
   Styled to match Timetable Management
============================================================ */

.room-page {
    width: 100%;
}

.room-page * {
    box-sizing: border-box;
}


/* ============================================================
   PAGE HEADER
============================================================ */

.room-page .page-toolbar {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 25px;
}

.room-page .page-toolbar h1 {

    margin: 0;

    font-size: 28px;

    font-weight: 700;
}

.room-page .page-toolbar p {

    margin: 7px 0 0;

    color: #6b7280;

    font-size: 14px;
}


/* ============================================================
   CARD
============================================================ */

.room-page .card {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    margin-bottom: 24px;

    overflow: hidden;
}

.room-page .card-body {

    padding: 25px;
}


/* ============================================================
   SECTION HEADING
============================================================ */

.room-page .section-heading {

    margin-bottom: 22px;
}

.room-page .section-heading h2 {

    margin: 0;

    font-size: 20px;

    font-weight: 700;
}

.room-page .section-heading p {

    margin: 6px 0 0;

    color: #6b7280;

    font-size: 14px;
}


/* ============================================================
   FORM GRID
============================================================ */

.room-page .form-grid {

    display: grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(180px, 1fr)
        );

    gap: 18px;
}

.room-page .form-group {

    min-width: 0;
}


/* ============================================================
   LABELS
============================================================ */

.room-page .form-group label {

    display: block;

    margin-bottom: 7px;

    font-size: 14px;

    font-weight: 600;

    color: #111827;
}


/* ============================================================
   FORM CONTROLS
============================================================ */

.room-page .form-control {

    width: 100%;

    min-height: 44px;

    padding: 10px 12px;

    border: 1px solid #d1d5db;

    border-radius: 9px;

    background: #ffffff;

    color: #111827;

    font-size: 14px;

    outline: none;
}

.room-page .form-control:focus {

    border-color: #2563eb;

    box-shadow:
        0 0 0 3px
        rgba(
            37,
            99,
            235,
            0.10
        );
}


/* ============================================================
   BUTTON AREA
============================================================ */

.room-page .button-area {

    margin-top: 20px;
}


/* ============================================================
   BUTTONS
============================================================ */

.room-page .btn {

    border: none;

    border-radius: 8px;

    padding: 11px 22px;

    font-size: 14px;

    font-weight: 600;

    cursor: pointer;
}


/* PRIMARY */

.room-page .btn-primary {

    background: #2563eb;

    color: #ffffff;
}

.room-page .btn-primary:hover {

    background: #1d4ed8;
}


/* DANGER */

.room-page .btn-danger {

    background: #dc2626;

    color: #ffffff;
}

.room-page .btn-danger:hover {

    background: #b91c1c;
}


/* SECONDARY */

.room-page .btn-secondary {

    background: #6b7280;

    color: #ffffff;
}

.room-page .btn-secondary:hover {

    background: #4b5563;
}


/* OUTLINE */

.room-page .btn-outline {

    background: #ffffff;

    border: 1px solid #d1d5db;

    color: #374151;
}

.room-page .btn-outline:hover {

    background: #f9fafb;
}


/* ============================================================
   OLD / NEW BOX
============================================================ */

.room-page .old-box,
.room-page .new-box {

    border: 1px solid #e5e7eb;

    border-radius: 12px;

    padding: 22px;
}

.room-page .old-box {

    background: #fafafa;
}

.room-page .new-box {

    background: #ffffff;
}


/* ============================================================
   BOX TITLE
============================================================ */

.room-page .box-title {

    margin: 0 0 20px;

    font-size: 17px;

    font-weight: 700;

    color: #111827;
}


/* ============================================================
   DIVIDER
============================================================ */

.room-page .divider {

    height: 1px;

    background: #e5e7eb;

    margin: 24px 0;
}


/* ============================================================
   ALERTS
============================================================ */

.room-page .alert {

    border-radius: 9px;

    padding: 13px 16px;

    margin-bottom: 20px;

    font-size: 14px;
}

.room-page .alert-success {

    background: #ecfdf5;

    border: 1px solid #a7f3d0;

    color: #047857;
}

.room-page .alert-danger {

    background: #fef2f2;

    border: 1px solid #fecaca;

    color: #b91c1c;
}


/* ============================================================
   READONLY INPUT
============================================================ */

.room-page .readonly-control {

    background: #f3f4f6;

    color: #6b7280;

    cursor: not-allowed;
}


/* ============================================================
   TABLE WRAPPER
============================================================ */

.room-page .table-wrapper {

    overflow-x: auto;

    margin-top: 24px;
}


/* ============================================================
   TABLE
============================================================ */

.room-page table {

    width: 100%;

    border-collapse: collapse;

    min-width: 800px;
}

.room-page th {

    text-align: left;

    background: #f9fafb;

    border-bottom: 1px solid #e5e7eb;

    padding: 13px 14px;

    font-size: 13px;

    color: #374151;
}

.room-page td {

    padding: 14px;

    border-bottom: 1px solid #f0f0f0;

    font-size: 14px;

    color: #374151;
}

.room-page tr:last-child td {

    border-bottom: none;
}


/* ============================================================
   TABLE ACTIONS
============================================================ */

.room-page .action-buttons {

    display: flex;

    align-items: center;

    gap: 8px;

    flex-wrap: wrap;
}

.room-page .action-buttons .btn {

    padding: 8px 14px;

    font-size: 13px;
}


/* ============================================================
   EMPTY STATE
============================================================ */

.room-page .empty-state {

    margin-top: 20px;

    border: 1px dashed #d1d5db;

    border-radius: 10px;

    padding: 30px;

    text-align: center;
}

.room-page .empty-state h3 {

    margin: 0;

    font-size: 17px;
}

.room-page .empty-state p {

    color: #6b7280;

    margin-bottom: 0;
}


/* ============================================================
   STATUS BADGES
============================================================ */

.room-page .status-badge {

    display: inline-flex;

    align-items: center;

    padding: 5px 10px;

    border-radius: 999px;

    font-size: 12px;

    font-weight: 600;
}

.room-page .status-available {

    background: #dcfce7;

    color: #166534;
}

.room-page .status-maintenance {

    background: #fef3c7;

    color: #92400e;
}

.room-page .status-inactive {

    background: #e5e7eb;

    color: #374151;
}


/* ============================================================
   RESPONSIVE
============================================================ */

@media (max-width: 700px) {

    .room-page .card-body {

        padding: 18px;
    }

    .room-page .page-toolbar h1 {

        font-size: 23px;
    }

    .room-page .old-box,
    .room-page .new-box {

        padding: 18px;
    }

    .room-page .action-buttons {

        flex-direction: column;

        align-items: flex-start;
    }

}

</style>


<div class="room-page">


    <!-- ======================================================
         PAGE HEADER
    ======================================================= -->

    <div class="page-toolbar">

        <div>

            <h1>
                Room Schedules
            </h1>

            <p>
                Manage lecture halls, lecture rooms and laboratories.
            </p>

        </div>

    </div>


    <!-- ======================================================
         ALERTS
    ======================================================= -->

    <?php if ($success !== ''): ?>

        <div class="alert alert-success">

            <?= htmlspecialchars($success) ?>

        </div>

    <?php endif; ?>


    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <!-- ======================================================
         ADD ROOM
    ======================================================= -->

    <div class="card">

        <div class="card-body">

            <div class="section-heading">

                <h2>
                    Add Room
                </h2>

                <p>
                    Create a new lecture hall, lecture room or laboratory.
                </p>

            </div>


            <form method="POST">


                <div class="form-grid">


                    <!-- ROOM NAME -->

                    <div class="form-group">

                        <label>
                            Room Name
                        </label>

                        <input
                            type="text"
                            name="room_name"
                            class="form-control"
                            placeholder="Enter room name"
                            required
                        >

                    </div>


                    <!-- CAPACITY -->

                    <div class="form-group">

                        <label>
                            Capacity
                        </label>

                        <input
                            type="number"
                            name="capacity"
                            class="form-control"
                            placeholder="Capacity"
                            min="1"
                            required
                        >

                    </div>


                    <!-- LOCATION -->

                    <div class="form-group">

                        <label>
                            Location
                        </label>

                        <input
                            type="text"
                            name="location"
                            class="form-control"
                            placeholder="Enter location"
                            required
                        >

                    </div>


                    <!-- ROOM TYPE -->

                    <div class="form-group">

                        <label>
                            Room Type
                        </label>

                        <select
                            name="room_type"
                            class="form-control"
                            required
                        >

                            <option
                                value=""
                                selected
                                disabled
                            >
                                Select Type
                            </option>

                            <option value="Lecture Hall">
                                Lecture Hall
                            </option>

                            <option value="Lecture Room">
                                Lecture Room
                            </option>

                            <option value="Laboratory">
                                Laboratory
                            </option>

                        </select>

                    </div>


                    <!-- STATUS -->

                    <div class="form-group">

                        <label>
                            Status
                        </label>

                        <select
                            name="status"
                            class="form-control"
                            required
                        >

                            <option value="Available">
                                Available
                            </option>

                            <option value="Maintenance">
                                Maintenance
                            </option>

                            <option value="Inactive">
                                Inactive
                            </option>

                        </select>

                    </div>

                </div>


                <div class="button-area">

                    <button
                        type="submit"
                        name="save_room"
                        class="btn btn-primary"
                    >
                        Save
                    </button>

                </div>

            </form>

        </div>

    </div>


    <!-- ======================================================
         UPDATE ROOM
    ======================================================= -->

    <?php if ($editRoom): ?>

        <div class="card">

            <div class="card-body">

                <div class="section-heading">

                    <h2>
                        Update Room
                    </h2>

                    <p>
                        Compare the old room details with the new room details.
                    </p>

                </div>


                <form method="POST">


                    <!-- ==================================================
                         OLD ROOM
                    =================================================== -->

                    <div class="old-box">

                        <h3 class="box-title">
                            Old Room
                        </h3>


                        <div class="form-grid">


                            <!-- OLD ROOM NAME -->

                            <div class="form-group">

                                <label>
                                    Room Name
                                </label>

                                <input
                                    type="text"
                                    class="form-control readonly-control"
                                    value="<?= htmlspecialchars(
                                        $editRoom['room_name']
                                    ) ?>"
                                    readonly
                                >

                            </div>


                            <!-- OLD CAPACITY -->

                            <div class="form-group">

                                <label>
                                    Capacity
                                </label>

                                <input
                                    type="text"
                                    class="form-control readonly-control"
                                    value="<?= (int)$editRoom['capacity'] ?>"
                                    readonly
                                >

                            </div>


                            <!-- OLD LOCATION -->

                            <div class="form-group">

                                <label>
                                    Location
                                </label>

                                <input
                                    type="text"
                                    class="form-control readonly-control"
                                    value="<?= htmlspecialchars(
                                        $editRoom['location']
                                    ) ?>"
                                    readonly
                                >

                            </div>


                            <!-- OLD ROOM TYPE -->

                            <div class="form-group">

                                <label>
                                    Room Type
                                </label>

                                <input
                                    type="text"
                                    class="form-control readonly-control"
                                    value="<?= htmlspecialchars(
                                        $editRoom['room_type']
                                    ) ?>"
                                    readonly
                                >

                            </div>


                            <!-- OLD STATUS -->

                            <div class="form-group">

                                <label>
                                    Status
                                </label>

                                <input
                                    type="text"
                                    class="form-control readonly-control"
                                    value="<?= htmlspecialchars(
                                        $editRoom['status']
                                    ) ?>"
                                    readonly
                                >

                            </div>

                        </div>

                    </div>


                    <div class="divider"></div>


                    <!-- ==================================================
                         NEW ROOM
                    =================================================== -->

                    <div class="new-box">

                        <h3 class="box-title">
                            New Room
                        </h3>


                        <input
                            type="hidden"
                            name="old_room_name"
                            value="<?= htmlspecialchars(
                                $editRoom['room_name']
                            ) ?>"
                        >


                        <div class="form-grid">


                            <!-- NEW ROOM NAME -->

                            <div class="form-group">

                                <label>
                                    Room Name
                                </label>

                                <input
                                    type="text"
                                    name="new_room_name"
                                    class="form-control"
                                    value="<?= htmlspecialchars(
                                        $editRoom['room_name']
                                    ) ?>"
                                    required
                                >

                            </div>


                            <!-- NEW CAPACITY -->

                            <div class="form-group">

                                <label>
                                    Capacity
                                </label>

                                <input
                                    type="number"
                                    name="new_capacity"
                                    class="form-control"
                                    value="<?= (int)$editRoom['capacity'] ?>"
                                    min="1"
                                    required
                                >

                            </div>


                            <!-- NEW LOCATION -->

                            <div class="form-group">

                                <label>
                                    Location
                                </label>

                                <input
                                    type="text"
                                    name="new_location"
                                    class="form-control"
                                    value="<?= htmlspecialchars(
                                        $editRoom['location']
                                    ) ?>"
                                    required
                                >

                            </div>


                            <!-- NEW ROOM TYPE -->

                            <div class="form-group">

                                <label>
                                    Room Type
                                </label>

                                <select
                                    name="new_room_type"
                                    class="form-control"
                                    required
                                >

                                    <option
                                        value="Lecture Hall"
                                        <?= $editRoom['room_type'] ===
                                            'Lecture Hall'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Lecture Hall
                                    </option>

                                    <option
                                        value="Lecture Room"
                                        <?= $editRoom['room_type'] ===
                                            'Lecture Room'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Lecture Room
                                    </option>

                                    <option
                                        value="Laboratory"
                                        <?= $editRoom['room_type'] ===
                                            'Laboratory'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Laboratory
                                    </option>

                                </select>

                            </div>


                            <!-- NEW STATUS -->

                            <div class="form-group">

                                <label>
                                    Status
                                </label>

                                <select
                                    name="new_status"
                                    class="form-control"
                                    required
                                >

                                    <option
                                        value="Available"
                                        <?= $editRoom['status'] ===
                                            'Available'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Available
                                    </option>

                                    <option
                                        value="Maintenance"
                                        <?= $editRoom['status'] ===
                                            'Maintenance'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Maintenance
                                    </option>

                                    <option
                                        value="Inactive"
                                        <?= $editRoom['status'] ===
                                            'Inactive'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Inactive
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="button-area">

                            <button
                                type="submit"
                                name="update_room"
                                class="btn btn-primary"
                            >
                                Update
                            </button>

                        </div>

                    </div>


                </form>


                <!-- CANCEL -->

                <div class="button-area">

                    <a
                        href="room_schedules.php"
                        class="btn btn-outline"
                    >
                        Cancel
                    </a>

                </div>

            </div>

        </div>

    <?php endif; ?>


    <!-- ======================================================
         ROOM RECORDS
    ======================================================= -->

    <div class="card">

        <div class="card-body">

            <div class="section-heading">

                <h2>
                    Room Records
                </h2>

                <p>
                    View and manage all registered rooms.
                </p>

            </div>


            <?php if (empty($rooms)): ?>

                <div class="empty-state">

                    <h3>
                        No Room Records
                    </h3>

                    <p>
                        No lecture rooms or laboratories have been added.
                    </p>

                </div>

            <?php else: ?>


                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Room
                                </th>

                                <th>
                                    Capacity
                                </th>

                                <th>
                                    Location
                                </th>

                                <th>
                                    Type
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach ($rooms as $room): ?>

                            <tr>


                                <!-- ROOM -->

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $room['room_name']
                                        ) ?>

                                    </strong>

                                </td>


                                <!-- CAPACITY -->

                                <td>

                                    <?= (int)$room['capacity'] ?>

                                </td>


                                <!-- LOCATION -->

                                <td>

                                    <?= htmlspecialchars(
                                        $room['location']
                                    ) ?>

                                </td>


                                <!-- TYPE -->

                                <td>

                                    <?= htmlspecialchars(
                                        $room['room_type']
                                    ) ?>

                                </td>


                                <!-- STATUS -->

                                <td>


                                    <?php if (
                                        $room['status'] ===
                                        'Available'
                                    ): ?>

                                        <span
                                            class="
                                                status-badge
                                                status-available
                                            "
                                        >
                                            Available
                                        </span>


                                    <?php elseif (
                                        $room['status'] ===
                                        'Maintenance'
                                    ): ?>

                                        <span
                                            class="
                                                status-badge
                                                status-maintenance
                                            "
                                        >
                                            Maintenance
                                        </span>


                                    <?php else: ?>

                                        <span
                                            class="
                                                status-badge
                                                status-inactive
                                            "
                                        >
                                            Inactive
                                        </span>

                                    <?php endif; ?>


                                </td>


                                <!-- ACTIONS -->

                                <td>

                                    <div class="action-buttons">


                                        <!-- EDIT -->

                                        <a
                                            href="
                                                room_schedules.php?edit=<?= urlencode(
                                                    $room['room_name']
                                                )
                                            ?>"
                                            class="btn btn-primary"
                                        >
                                            Edit
                                        </a>


                                        <!-- DELETE -->

                                        <form
                                            method="POST"
                                            style="display:inline;"
                                            onsubmit="
                                                return confirm(
                                                    'Are you sure you want to delete this room?'
                                                );
                                            "
                                        >

                                            <input
                                                type="hidden"
                                                name="room_name"
                                                value="<?= htmlspecialchars(
                                                    $room['room_name']
                                                ) ?>"
                                            >


                                            <button
                                                type="submit"
                                                name="delete_room"
                                                class="btn btn-danger"
                                            >
                                                Delete
                                            </button>

                                        </form>


                                    </div>

                                </td>


                            </tr>

                        <?php endforeach; ?>


                        </tbody>

                    </table>

                </div>


            <?php endif; ?>


        </div>

    </div>


</div>


<?php

include __DIR__ . '/../includes/footer.php';

?>