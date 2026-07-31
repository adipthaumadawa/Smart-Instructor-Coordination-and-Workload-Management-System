<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Timetable Records - Non-Academic Staff</title>

    <link rel="stylesheet" href="css/timetable_records.css">

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
                class="nav-item active"
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
                class="nav-item"
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
             TIMETABLE RECORDS CONTENT
        ================================================== -->

        <section class="content">


            <!-- PAGE TITLE -->

            <div class="page-heading">

                <div class="page-heading-icon">

                    <img
                        src="../assets/icons/timetable.svg"
                        alt="Timetable"
                    >

                </div>

                <h1>
                    Timetable Records
                </h1>

            </div>


            <!-- ==================================================
                 ADD TIMETABLE SLOT
            ================================================== -->

            <div class="record-card">

                <div class="card-title">
                    Add Timetable Slot
                </div>


                <div class="card-body">

                    <div class="form-row">

                        <div class="form-group subject-field">

                            <label>
                                Subject
                            </label>

                            <input
                                type="text"
                                name="subject"
                                placeholder="Subject"
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                IS/CS
                            </label>

                            <select name="course_type">

                                <option value="" selected disabled>
                                    IS/CS
                                </option>

                                <option value="IS">
                                    IS
                                </option>

                                <option value="CS">
                                    CS
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>
                                Day
                            </label>

                            <select name="day">

                                <option value="">
                                    Monday
                                </option>

                                <option>
                                    Tuesday
                                </option>

                                <option>
                                    Wednesday
                                </option>

                                <option>
                                    Thursday
                                </option>

                                <option>
                                    Friday
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>
                                Start Time
                            </label>

                            <input
                                type="time"
                                name="start_time"
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                End Time
                            </label>

                            <input
                                type="time"
                                name="end_time"
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Room / Lab
                            </label>

                            <input
                                type="text"
                                name="room"
                                placeholder="Room / Lab"
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Semester
                            </label>

                            <select name="semester">

                                <option value=""selected disabled>
                                    Semester
                                </option>

                                <option>
                                    Semester 1
                                </option>

                                <option>
                                    Semester 2
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>
                                Year
                            </label>

                           <select id="year" name="year" required>
                            <option value="" selected disabled>Select Year</option>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>

                        </div>

                    </div>


                    <div class="button-area">

                        <button
                            type="button"
                            class="primary-button"
                        >
                            Save
                        </button>

                    </div>

                </div>

            </div>


            <!-- ==================================================
                 DELETE TIMETABLE SLOT
            ================================================== -->

            <div class="record-card">

                <div class="card-title">
                    Delete Timetable Slot
                </div>


                <div class="card-body">

                    <div class="form-row">

                        <div class="form-group">

                            <label>
                                Subject
                            </label>

                            <input
                                type="text"
                                placeholder="Subject"
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                IS/CS
                            </label>

                            <select>

                                <option selected disabled>
                                    IS/CS
                                </option>

                                <option>
                                    IS
                                </option>

                                <option>
                                    CS
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>
                                Day
                            </label>

                            <select name="day">

                                <option value="">
                                    Monday
                                </option>

                                <option>
                                    Tuesday
                                </option>

                                <option>
                                    Wednesday
                                </option>

                                <option>
                                    Thursday
                                </option>

                                <option>
                                    Friday
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>
                                Start Time
                            </label>

                            <input
                                type="time"
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                End Time
                            </label>

                            <input
                                type="time"
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Room / Lab
                            </label>

                            <input
                                type="text"
                                placeholder="Room / Lab"
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Semester
                            </label>

                            <select>

                                <option selected disabled>
                                    Semester
                                </option>

                                <option>
                                    Semester 1
                                </option>

                                <option>
                                    Semester 2
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>
                                Year
                            </label>

                            <select id="year" name="year" required>
                            <option value="" selected disabled>Select Year</option>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>

                        </div>

                    </div>


                    <div class="button-area">

                        <button
                            type="button"
                            class="primary-button"
                        >
                            Delete
                        </button>

                    </div>

                </div>

            </div>


            <!-- ==================================================
                 UPDATE TIMETABLE SLOT
            ================================================== -->

            <div class="record-card update-card">

                <div class="card-title">
                    Update Timetable Slot
                </div>


                <div class="update-section">

                    <h2>
                        Old Timetable Slot
                    </h2>


                    <div class="form-row">

                        <div class="form-group">

                            <label>
                                Subject
                            </label>

                            <input
                                type="text"
                                placeholder="Subject"
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                IS/CS
                            </label>

                              <select>

                                <option selected disabled>
                                    IS/CS
                                </option>

                                <option>
                                    IS
                                </option>

                                <option>
                                    CS
                                </option>

                            </select>

                        </select>

                        </div>


                        <div class="form-group">

                            <label>
                                Day
                            </label>

                            <select name="day">

                                <option value="">
                                    Monday
                                </option>

                                <option>
                                    Tuesday
                                </option>

                                <option>
                                    Wednesday
                                </option>

                                <option>
                                    Thursday
                                </option>

                                <option>
                                    Friday
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>
                                Start Time
                            </label>

                            <input type="time">

                        </div>


                        <div class="form-group">

                            <label>
                                End Time
                            </label>

                            <input type="time">

                        </div>


                        <div class="form-group">

                            <label>
                                Room / Lab
                            </label>

                            <input
                                type="text"
                                placeholder="Room / Lab"
                            >

                        </div>

                    </div>


                    <div class="update-divider"></div>


                    <h2>
                        New Timetable Slot
                    </h2>


                    <div class="form-row">

                        <div class="form-group">

                            <label>
                                Subject
                            </label>

                            <input
                                type="text"
                                placeholder="Subject"
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                IS/CS
                            </label>

                              <select>

                                <option selected disabled>
                                    IS/CS
                                </option>

                                <option>
                                    IS
                                </option>

                                <option>
                                    CS
                                </option>

                            </select>

                           

                        

                        </div>


                        <div class="form-group">

                            <label>
                                Day
                            </label>

                            <select name="day">

                                <option value="">
                                    Monday
                                </option>

                                <option>
                                    Tuesday
                                </option>

                                <option>
                                    Wednesday
                                </option>

                                <option>
                                    Thursday
                                </option>

                                <option>
                                    Friday
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>
                                Start Time
                            </label>

                            <input type="time">

                        </div>


                        <div class="form-group">

                            <label>
                                End Time
                            </label>

                            <input type="time">

                        </div>


                        <div class="form-group">

                            <label>
                                Room / Lab
                            </label>

                            <input
                                type="text"
                                placeholder="Room / Lab"
                            >

                        </div>

                    </div>


                    <div class="button-area">

                        <button
                            type="button"
                            class="primary-button"
                        >
                            Update
                        </button>

                    </div>

                </div>

            </div>


            <!-- ==================================================
                 VIEW TIMETABLE SLOT
            ================================================== -->

            <div class="record-card view-card">

                <div class="card-title">
                    View Timetable Slot
                </div>


                <div class="view-body">

                    <div class="view-fields">

                        <div class="form-group">

                            <label>
                                Semester
                            </label>

                            <select>

                                <option selected disabled>
                                    Semester
                                </option>

                                <option>
                                    Semester 1
                                </option>

                                <option>
                                    Semester 2
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>
                                Year
                            </label>

                    

                        <select id="year" name="year" required>
                            <option value="" selected disabled>Select Year</option>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                        </div>

                    </div>


                    <div class="button-area">

                        <button
                            type="button"
                            class="primary-button"
                        >
                            View
                        </button>

                    </div>

                </div>

            </div>


        </section>

    </main>

</div>

</body>

</html>