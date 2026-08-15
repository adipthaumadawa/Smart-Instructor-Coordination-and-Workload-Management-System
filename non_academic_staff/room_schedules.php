<?php

/**
 * ==================================================
 * ROOM SCHEDULES
 * Lecture Room & Laboratory
 * CRUD - Insert / Update / Delete / View
 * ==================================================
 */

/* ==================================================
   DATABASE CONNECTION
   db.php is inside ../config/
================================================== */

require_once __DIR__ . '/../config/db.php';


/* ==================================================
   INSERT / ADD
================================================== */

if (isset($_POST['save'])) {

    $room_name = trim($_POST['room_name']);
    $capacity  = trim($_POST['capacity']);
    $location  = trim($_POST['location']);
    $type      = trim($_POST['type']);

    if (
        $room_name !== '' &&
        $capacity !== '' &&
        $location !== '' &&
        $type !== ''
    ) {

        $sql = "INSERT INTO lecture_rooms
                (room_name, capacity, location, type)
                VALUES
                (:room_name, :capacity, :location, :type)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':room_name' => $room_name,
            ':capacity'  => $capacity,
            ':location'  => $location,
            ':type'      => $type
        ]);
    }

    header("Location: room_schedules.php");
    exit;
}


/* ==================================================
   DELETE
================================================== */

if (isset($_POST['delete'])) {

    $delete_room_name = trim($_POST['delete_room_name']);

    if ($delete_room_name !== '') {

        $sql = "DELETE FROM lecture_rooms
                WHERE room_name = :room_name";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':room_name' => $delete_room_name
        ]);
    }

    header("Location: room_schedules.php");
    exit;
}


/* ==================================================
   UPDATE
================================================== */

if (isset($_POST['update'])) {

    $old_room_name = trim($_POST['old_room_name']);

    $new_room_name = trim($_POST['new_room_name']);
    $new_capacity  = trim($_POST['new_capacity']);
    $new_location  = trim($_POST['new_location']);
    $new_type      = trim($_POST['new_type']);


    if (
        $old_room_name !== '' &&
        $new_room_name !== '' &&
        $new_capacity !== '' &&
        $new_location !== '' &&
        $new_type !== ''
    ) {

        $sql = "UPDATE lecture_rooms

                SET
                    room_name = :new_room_name,
                    capacity  = :new_capacity,
                    location  = :new_location,
                    type      = :new_type

                WHERE
                    room_name = :old_room_name";


        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':new_room_name' => $new_room_name,
            ':new_capacity'  => $new_capacity,
            ':new_location'  => $new_location,
            ':new_type'      => $new_type,
            ':old_room_name' => $old_room_name
        ]);
    }

    header("Location: room_schedules.php");
    exit;
}


/* ==================================================
   VIEW
================================================== */

$rooms = [];

if (isset($_POST['view'])) {

    $view_type = trim($_POST['view_type']);

    if ($view_type !== '') {

        $sql = "SELECT
                    room_name,
                    capacity,
                    location,
                    type

                FROM lecture_rooms

                WHERE type = :type

                ORDER BY room_name ASC";


        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':type' => $view_type
        ]);

        $rooms = $stmt->fetchAll();
    }
}

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Lecture Room & Laboratory
    </title>

    <!-- CORRECT CSS PATH -->
    <link
        rel="stylesheet"
        href="css/room_schedules.css"
    >

</head>


<body>


<div class="dashboard-container">


    <!-- ==================================================
         SIDEBAR
    ================================================== -->

    <aside class="sidebar">


        <div class="sidebar-logo">

            <img
                src="../assets/images/ucsc-logo.png"
                alt="UCSC Logo"
            >

            <h3>
                Smart Instructor System
            </h3>

            <p>
                University of Colombo<br>
                School of Computing
            </p>

        </div>


        <nav class="sidebar-navigation">


            <!-- DASHBOARD -->

            <a
                href="dashboard.php"
                class="nav-item"
            >

                <img
                    src="../assets/icons/dashboard.svg"
                    alt="Dashboard"
                >

                <span>
                    Dashboard
                </span>

            </a>


            <h4>
                OPERATIONS
            </h4>


            <!-- TIMETABLE MANAGEMENT -->

            <a
                href="timetable_management.php"
                class="nav-item"
            >

                <img
                    src="../assets/icons/timetable.svg"
                    alt="Timetable Management"
                >

                <span>
                    Timetable Management
                </span>

            </a>


            <!-- ROOM SCHEDULES -->

            <a
                href="room_schedules.php"
                class="nav-item active"
            >

                <img
                    src="../assets/icons/room-schedule.svg"
                    alt="Room Schedules"
                >

                <span>
                    Room Schedules
                </span>

            </a>


            <!-- LECTURE HALL BOOKING -->

            <a
                href="lecture_hall_booking.php"
                class="nav-item"
            >

                <img
                    src="../assets/icons/lecture-hall.svg"
                    alt="Lecture Hall Booking"
                >

                <span>
                    Lecture Hall Booking
                </span>

            </a>


            <!-- NOTIFICATIONS -->

            <a
                href="notifications.php"
                class="nav-item"
            >

                <img
                    src="../assets/icons/notification1.svg"
                    alt="Notifications"
                >

                <span>
                    Notifications
                </span>

            </a>


            <!-- PROFILE -->

            <a
                href="profile.php"
                class="nav-item"
            >

                <img
                    src="../assets/icons/profile1.svg"
                    alt="Profile"
                >

                <span>
                    Profile
                </span>

            </a>


        </nav>

    </aside>


    <!-- ==================================================
         MAIN CONTENT
    ================================================== -->

    <main class="main-content">


        <!-- ==================================================
             TOP HEADER
        ================================================== -->

        <header class="top-header">


            <div class="system-name">

                <img
                    src="../assets/icons/education1.svg"
                    alt="UCSC SIS"
                >

                <strong>
                    UCSC SIS
                </strong>

            </div>


            <div class="user-section">


                <img
                    src="../assets/icons/notification.svg"
                    class="top-notification"
                    alt="Notifications"
                >


                <div class="user-avatar">
                    M
                </div>


                <div class="user-details">

                    <strong>
                        Mr. Rizan
                    </strong>

                    <span>
                        Non-Academic Staff
                    </span>

                </div>


                <img
                    src="../assets/icons/dropdown.svg"
                    class="dropdown-icon"
                    alt="Dropdown"
                >


            </div>


        </header>


        <!-- ==================================================
             CONTENT
        ================================================== -->

        <section class="content">


            <!-- ==================================================
                 PAGE HEADING
            ================================================== -->

            <div class="page-heading">


                <div class="page-heading-icon">

                    <img
                        src="../assets/icons/room-schedule.svg"
                        alt="Room Schedules"
                    >

                </div>


                <h1>
                    Lecture Room & Laboratory
                </h1>


            </div>


            <!-- ==================================================
                 ADD
            ================================================== -->

            <div class="record-card add-card">


                <div class="card-title">
                    Add Lecture Room & Laboratory
                </div>


                <div class="card-body">


                    <form method="POST">


                        <div class="form-row">


                            <div class="form-group room-name-field">

                                <label>
                                    Room Name
                                </label>

                                <input
                                    type="text"
                                    name="room_name"
                                    placeholder="Room Name"
                                    required
                                >

                            </div>


                            <div class="form-group capacity-field">

                                <label>
                                    Capacity
                                </label>

                                <input
                                    type="number"
                                    name="capacity"
                                    placeholder="Capacity"
                                    min="1"
                                    required
                                >

                            </div>


                            <div class="form-group location-field">

                                <label>
                                    Location
                                </label>

                                <input
                                    type="text"
                                    name="location"
                                    placeholder="Location"
                                    required
                                >

                            </div>


                            <div class="form-group type-field">

                                <label>
                                    Type
                                </label>

                                <select
                                    name="type"
                                    required
                                >

                                    <option
                                        value=""
                                        selected
                                        disabled
                                    >
                                        Type
                                    </option>

                                    <option value="Lecture Room">
                                        Lecture Room
                                    </option>

                                    <option value="Laboratory">
                                        Laboratory
                                    </option>

                                </select>

                            </div>


                        </div>


                        <div class="button-area">

                            <button
                                type="submit"
                                name="save"
                                class="primary-button"
                            >
                                Save
                            </button>

                        </div>


                    </form>


                </div>


            </div>


            <!-- ==================================================
                 DELETE
            ================================================== -->

            <div class="record-card delete-card">


                <div class="card-title">
                    Delete Lecture Room & Laboratory
                </div>


                <div class="card-body">


                    <form method="POST">


                        <div class="delete-field">


                            <div class="form-group room-name-field">

                                <label>
                                    Room Name
                                </label>

                                <input
                                    type="text"
                                    name="delete_room_name"
                                    placeholder="Room Name"
                                    required
                                >

                            </div>


                        </div>


                        <div class="button-area">

                            <button
                                type="submit"
                                name="delete"
                                class="primary-button"
                            >
                                Delete
                            </button>

                        </div>


                    </form>


                </div>


            </div>


            <!-- ==================================================
                 UPDATE
            ================================================== -->

            <div class="record-card update-card">


                <div class="card-title">
                    Update Lecture Room & Laboratory
                </div>


                <div class="update-section">


                    <form method="POST">


                        <h2>
                            Old Lecture Room & Laboratory
                        </h2>


                        <div class="form-row">


                            <div class="form-group room-name-field">

                                <label>
                                    Room Name
                                </label>

                                <input
                                    type="text"
                                    name="old_room_name"
                                    placeholder="Room Name"
                                    required
                                >

                            </div>


                        </div>


                        <div class="update-divider"></div>


                        <h2>
                            New Lecture Room & Laboratory
                        </h2>


                        <div class="form-row">


                            <div class="form-group room-name-field">

                                <label>
                                    Room Name
                                </label>

                                <input
                                    type="text"
                                    name="new_room_name"
                                    placeholder="Room Name"
                                    required
                                >

                            </div>


                            <div class="form-group capacity-field">

                                <label>
                                    Capacity
                                </label>

                                <input
                                    type="number"
                                    name="new_capacity"
                                    placeholder="Capacity"
                                    min="1"
                                    required
                                >

                            </div>


                            <div class="form-group location-field">

                                <label>
                                    Location
                                </label>

                                <input
                                    type="text"
                                    name="new_location"
                                    placeholder="Location"
                                    required
                                >

                            </div>


                            <div class="form-group type-field">

                                <label>
                                    Type
                                </label>

                                <select
                                    name="new_type"
                                    required
                                >

                                    <option
                                        value=""
                                        selected
                                        disabled
                                    >
                                        Type
                                    </option>

                                    <option value="Lecture Room">
                                        Lecture Room
                                    </option>

                                    <option value="Laboratory">
                                        Laboratory
                                    </option>

                                </select>

                            </div>


                        </div>


                        <div class="button-area">

                            <button
                                type="submit"
                                name="update"
                                class="primary-button"
                            >
                                Update
                            </button>

                        </div>


                    </form>


                </div>


            </div>


            <!-- ==================================================
                 VIEW
            ================================================== -->

            <div class="record-card view-card">


                <div class="card-title">
                    View Lecture Room & Laboratory
                </div>


                <div class="view-body">


                    <form method="POST">


                        <div class="view-fields">


                            <div class="form-group type-field">

                                <label>
                                    Type
                                </label>

                                <select
                                    name="view_type"
                                    required
                                >

                                    <option
                                        value=""
                                        selected
                                        disabled
                                    >
                                        Type
                                    </option>

                                    <option value="Lecture Room">
                                        Lecture Room
                                    </option>

                                    <option value="Laboratory">
                                        Laboratory
                                    </option>

                                </select>

                            </div>


                        </div>


                        <div class="button-area">

                            <button
                                type="submit"
                                name="view"
                                class="primary-button"
                            >
                                View
                            </button>

                        </div>


                    </form>


                    <?php if (!empty($rooms)): ?>


                        <div class="results-table">


                            <table>


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
                                            Type
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>


                                    <?php foreach ($rooms as $room): ?>


                                        <tr>

                                            <td>
                                                <?= htmlspecialchars($room['room_name']) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($room['capacity']) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($room['location']) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($room['type']) ?>
                                            </td>

                                        </tr>


                                    <?php endforeach; ?>


                                </tbody>


                            </table>


                        </div>


                    <?php endif; ?>


                </div>


            </div>


        </section>


    </main>


</div>


</body>

</html>