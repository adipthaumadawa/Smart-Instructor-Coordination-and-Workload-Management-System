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

$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';

unset($_SESSION['success'], $_SESSION['error']);


/* ============================================================
   ADD ROOM
============================================================ */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_room'])) {

    $roomName = trim($_POST['room_name'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 0);
    $location = trim($_POST['location'] ?? '');
    $roomType = trim($_POST['room_type'] ?? '');
    $status   = trim($_POST['status'] ?? 'Available');

    try {

        if ($roomName === '') {
            throw new Exception("Please enter the room name.");
        }

        if ($capacity <= 0) {
            throw new Exception("Capacity must be greater than 0.");
        }

        if ($location === '') {
            throw new Exception("Please enter the location.");
        }

        if (!in_array($roomType, ['Lecture Hall', 'Lecture Room', 'Laboratory'], true)) {
            throw new Exception("Invalid room type.");
        }

        if (!in_array($status, ['Available', 'Maintenance', 'Inactive'], true)) {
            throw new Exception("Invalid room status.");
        }


        /* Check duplicate room */

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM lecture_rooms
            WHERE room_name = ?
        ");

        $stmt->execute([$roomName]);

        if ((int)$stmt->fetchColumn() > 0) {
            throw new Exception("A room with this name already exists.");
        }


        /* Insert */

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
            (
                ?, ?, ?, ?, ?
            )
        ");

        $stmt->execute([
            $roomName,
            $capacity,
            $location,
            $roomType,
            $status
        ]);


        $_SESSION['success'] =
            "Room added successfully.";

    } catch (Throwable $e) {

        $_SESSION['error'] = $e->getMessage();
    }

    header("Location: room_schedules.php");
    exit;
}


/* ============================================================
   UPDATE ROOM
============================================================ */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_room'])) {

    $roomId   = (int)($_POST['room_id'] ?? 0);
    $roomName = trim($_POST['room_name'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 0);
    $location = trim($_POST['location'] ?? '');
    $roomType = trim($_POST['room_type'] ?? '');
    $status   = trim($_POST['status'] ?? '');

    try {

        if ($roomId <= 0) {
            throw new Exception("Invalid room selected.");
        }

        if ($roomName === '') {
            throw new Exception("Please enter the room name.");
        }

        if ($capacity <= 0) {
            throw new Exception("Capacity must be greater than 0.");
        }

        if ($location === '') {
            throw new Exception("Please enter the location.");
        }

        if (!in_array($roomType, ['Lecture Hall', 'Lecture Room', 'Laboratory'], true)) {
            throw new Exception("Invalid room type.");
        }

        if (!in_array($status, ['Available', 'Maintenance', 'Inactive'], true)) {
            throw new Exception("Invalid room status.");
        }


        /* Check duplicate room name */

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM lecture_rooms
            WHERE room_name = ?
            AND id != ?
        ");

        $stmt->execute([
            $roomName,
            $roomId
        ]);

        if ((int)$stmt->fetchColumn() > 0) {
            throw new Exception(
                "Another room already uses this name."
            );
        }


        /* Update */

        $stmt = $pdo->prepare("
            UPDATE lecture_rooms
            SET
                room_name = ?,
                capacity = ?,
                location = ?,
                room_type = ?,
                status = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $roomName,
            $capacity,
            $location,
            $roomType,
            $status,
            $roomId
        ]);


        $_SESSION['success'] =
            "Room updated successfully.";

    } catch (Throwable $e) {

        $_SESSION['error'] = $e->getMessage();
    }

    header("Location: room_schedules.php");
    exit;
}


/* ============================================================
   DELETE ROOM
============================================================ */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_room'])) {

    $roomId = (int)($_POST['room_id'] ?? 0);

    try {

        if ($roomId <= 0) {
            throw new Exception("Invalid room selected.");
        }


        /* Get room */

        $stmt = $pdo->prepare("
            SELECT
                id,
                room_name
            FROM lecture_rooms
            WHERE id = ?
        ");

        $stmt->execute([$roomId]);

        $room = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$room) {
            throw new Exception("Room not found.");
        }


        /* Delete */

        $stmt = $pdo->prepare("
            DELETE FROM lecture_rooms
            WHERE id = ?
        ");

        $stmt->execute([$roomId]);


        $_SESSION['success'] =
            "Room deleted successfully.";

    } catch (Throwable $e) {

        $_SESSION['error'] = $e->getMessage();
    }

    header("Location: room_schedules.php");
    exit;
}


/* ============================================================
   GET ROOM RECORDS
============================================================ */

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
        ORDER BY
            room_type ASC,
            room_name ASC
    ");

    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {

    $error = "Unable to load room schedules.";
}


/* ============================================================
   HEADER
============================================================ */

include __DIR__ . '/../includes/header.php';

?>


<!-- ============================================================
     PAGE HEADER
============================================================ -->

<div class="page-toolbar">

    <div>

        <h1>Room Schedules</h1>

        <p>
            Manage lecture halls, lecture rooms, and laboratories.
        </p>

    </div>

</div>


<!-- ============================================================
     ALERTS
============================================================ -->

<?php if ($success): ?>

    <div class="alert alert-success alert-dismissible fade show">

        <?= htmlspecialchars($success) ?>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert">
        </button>

    </div>

<?php endif; ?>


<?php if ($error): ?>

    <div class="alert alert-danger alert-dismissible fade show">

        <?= htmlspecialchars($error) ?>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert">
        </button>

    </div>

<?php endif; ?>


<!-- ============================================================
     ADD ROOM
============================================================ -->

<div class="card mb-4">

    <div class="card-header">

        <h5 class="mb-0">

            <i class="fas fa-plus-circle me-2"></i>

            Add Room

        </h5>

    </div>


    <div class="card-body">

        <form method="POST">

            <div class="row g-3">


                <!-- ROOM NAME -->

                <div class="col-md-3">

                    <label class="form-label">
                        Room Name
                    </label>

                    <input
                        type="text"
                        name="room_name"
                        class="form-control"
                        placeholder="e.g. LH 01"
                        required>

                </div>


                <!-- CAPACITY -->

                <div class="col-md-2">

                    <label class="form-label">
                        Capacity
                    </label>

                    <input
                        type="number"
                        name="capacity"
                        class="form-control"
                        min="1"
                        placeholder="50"
                        required>

                </div>


                <!-- LOCATION -->

                <div class="col-md-3">

                    <label class="form-label">
                        Location
                    </label>

                    <input
                        type="text"
                        name="location"
                        class="form-control"
                        placeholder="Main Building"
                        required>

                </div>


                <!-- ROOM TYPE -->

                <div class="col-md-2">

                    <label class="form-label">
                        Room Type
                    </label>

                    <select
                        name="room_type"
                        class="form-select"
                        required>

                        <option value="">
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

                <div class="col-md-2">

                    <label class="form-label">
                        Status
                    </label>

                    <select
                        name="status"
                        class="form-select"
                        required>

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


                <!-- SAVE -->

                <div class="col-12">

                    <button
                        type="submit"
                        name="add_room"
                        class="btn btn-primary">

                        <i class="fas fa-save me-1"></i>

                        Add Room

                    </button>

                </div>

            </div>

        </form>

    </div>

</div>


<!-- ============================================================
     ROOM LIST
============================================================ -->

<div class="card">

    <div class="card-header d-flex justify-content-between align-items-center">

        <h5 class="mb-0">

            <i class="fas fa-door-open me-2"></i>

            Room Records

        </h5>


        <span class="badge bg-primary">

            <?= count($rooms) ?> Records

        </span>

    </div>


    <div class="card-body p-0">


        <?php if (empty($rooms)): ?>

            <div class="empty-state p-5 text-center">

                <div class="empty-state-icon">

                    <?= function_exists('sic_icon')
                        ? sic_icon('building')
                        : '<i class="fas fa-building fa-2x"></i>' ?>

                </div>

                <h3>
                    No Room Records
                </h3>

                <p>
                    No lecture rooms or laboratories have been added yet.
                </p>

            </div>

        <?php else: ?>


            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead>

                        <tr>

                            <th>
                                Room Name
                            </th>

                            <th>
                                Capacity
                            </th>

                            <th>
                                Location
                            </th>

                            <th>
                                Room Type
                            </th>

                            <th>
                                Status
                            </th>

                            <th class="text-end">
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

                                <?php if ($room['room_type'] === 'Laboratory'): ?>

                                    <span class="badge bg-info">
                                        Laboratory
                                    </span>

                                <?php elseif ($room['room_type'] === 'Lecture Hall'): ?>

                                    <span class="badge bg-primary">
                                        Lecture Hall
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary">
                                        Lecture Room
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <?php if ($room['status'] === 'Available'): ?>

                                    <span class="badge bg-success">
                                        Available
                                    </span>

                                <?php elseif ($room['status'] === 'Maintenance'): ?>

                                    <span class="badge bg-warning text-dark">
                                        Maintenance
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary">
                                        Inactive
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- ACTIONS -->

                            <td class="text-end">


                                <!-- EDIT -->

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editRoom<?= (int)$room['id'] ?>">

                                    <i class="fas fa-edit"></i>

                                    Edit

                                </button>


                                <!-- DELETE -->

                                <form
                                    method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('Are you sure you want to delete this room?');">

                                    <input
                                        type="hidden"
                                        name="room_id"
                                        value="<?= (int)$room['id'] ?>">

                                    <button
                                        type="submit"
                                        name="delete_room"
                                        class="btn btn-sm btn-outline-danger">

                                        <i class="fas fa-trash"></i>

                                        Delete

                                    </button>

                                </form>


                            </td>

                        </tr>


                        <!-- ==================================================
                             EDIT MODAL
                        ================================================== -->

                        <div
                            class="modal fade"
                            id="editRoom<?= (int)$room['id'] ?>"
                            tabindex="-1"
                            aria-hidden="true">

                            <div class="modal-dialog">

                                <div class="modal-content">


                                    <form method="POST">


                                        <div class="modal-header">

                                            <h5 class="modal-title">

                                                <i class="fas fa-edit me-2"></i>

                                                Edit Room

                                            </h5>

                                            <button
                                                type="button"
                                                class="btn-close"
                                                data-bs-dismiss="modal">
                                            </button>

                                        </div>


                                        <div class="modal-body">

                                            <input
                                                type="hidden"
                                                name="room_id"
                                                value="<?= (int)$room['id'] ?>">


                                            <!-- ROOM NAME -->

                                            <div class="mb-3">

                                                <label class="form-label">
                                                    Room Name
                                                </label>

                                                <input
                                                    type="text"
                                                    name="room_name"
                                                    class="form-control"
                                                    value="<?= htmlspecialchars($room['room_name']) ?>"
                                                    required>

                                            </div>


                                            <!-- CAPACITY -->

                                            <div class="mb-3">

                                                <label class="form-label">
                                                    Capacity
                                                </label>

                                                <input
                                                    type="number"
                                                    name="capacity"
                                                    class="form-control"
                                                    value="<?= (int)$room['capacity'] ?>"
                                                    min="1"
                                                    required>

                                            </div>


                                            <!-- LOCATION -->

                                            <div class="mb-3">

                                                <label class="form-label">
                                                    Location
                                                </label>

                                                <input
                                                    type="text"
                                                    name="location"
                                                    class="form-control"
                                                    value="<?= htmlspecialchars($room['location']) ?>"
                                                    required>

                                            </div>


                                            <!-- ROOM TYPE -->

                                            <div class="mb-3">

                                                <label class="form-label">
                                                    Room Type
                                                </label>

                                                <select
                                                    name="room_type"
                                                    class="form-select"
                                                    required>

                                                    <option
                                                        value="Lecture Hall"
                                                        <?= $room['room_type'] === 'Lecture Hall' ? 'selected' : '' ?>>

                                                        Lecture Hall

                                                    </option>

                                                    <option
                                                        value="Lecture Room"
                                                        <?= $room['room_type'] === 'Lecture Room' ? 'selected' : '' ?>>

                                                        Lecture Room

                                                    </option>

                                                    <option
                                                        value="Laboratory"
                                                        <?= $room['room_type'] === 'Laboratory' ? 'selected' : '' ?>>

                                                        Laboratory

                                                    </option>

                                                </select>

                                            </div>


                                            <!-- STATUS -->

                                            <div class="mb-3">

                                                <label class="form-label">
                                                    Status
                                                </label>

                                                <select
                                                    name="status"
                                                    class="form-select"
                                                    required>

                                                    <option
                                                        value="Available"
                                                        <?= $room['status'] === 'Available' ? 'selected' : '' ?>>

                                                        Available

                                                    </option>

                                                    <option
                                                        value="Maintenance"
                                                        <?= $room['status'] === 'Maintenance' ? 'selected' : '' ?>>

                                                        Maintenance

                                                    </option>

                                                    <option
                                                        value="Inactive"
                                                        <?= $room['status'] === 'Inactive' ? 'selected' : '' ?>>

                                                        Inactive

                                                    </option>

                                                </select>

                                            </div>

                                        </div>


                                        <div class="modal-footer">

                                            <button
                                                type="button"
                                                class="btn btn-secondary"
                                                data-bs-dismiss="modal">

                                                Cancel

                                            </button>


                                            <button
                                                type="submit"
                                                name="update_room"
                                                class="btn btn-primary">

                                                <i class="fas fa-save me-1"></i>

                                                Update Room

                                            </button>

                                        </div>


                                    </form>

                                </div>

                            </div>

                        </div>


                    <?php endforeach; ?>


                    </tbody>

                </table>

            </div>


        <?php endif; ?>

    </div>

</div>


<?php

include __DIR__ . '/../includes/footer.php';

?>