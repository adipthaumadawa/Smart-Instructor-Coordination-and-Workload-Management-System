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
                                            class="d-inline"
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