<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Timetable Records</title>

    <link rel="stylesheet" href="css/non_acedemic_staff.css">
    <link rel="stylesheet" href="css/timetable_records.css">
</head>

<body>

<div class="page">

    <!-- ================= SIDEBAR ================= -->

    <aside class="sidebar">

        <div class="sidebar-logo">

            <img
                src="../assets/images/ucsc-logo.png"
                alt="UCSC Logo"
            >

            <div class="system-title">
                Smart Instructor System
            </div>

            <div class="university-name">
                University of Colombo<br>
                School of Computing
            </div>

        </div>


        <div class="sidebar-divider"></div>


        <nav class="sidebar-menu">

            <a href="dashboard.php" class="menu-item">

                <img
                    src="../assets/icons/dashboard.svg"
                    alt="Dashboard"
                >

                <span>Dashboard</span>

            </a>


            <div class="menu-heading">
                OPERATIONS
            </div>


            <a
                href="timetable_records.php"
                class="menu-item active"
            >

                <img
                    src="../assets/icons/timetable.svg"
                    alt="Timetable"
                >

                <span>Timetable Management</span>

            </a>


            <a href="room_schedules.php" class="menu-item">

                <img
                    src="../assets/icons/room-schedule.svg"
                    alt="Room Schedules"
                >

                <span>Room Schedules</span>

            </a>


            <a href="lecture_hall_booking.php" class="menu-item">

                <img
                    src="../assets/icons/lecture-hall.svg"
                    alt="Lecture Hall"
                >

                <span>Lecture Hall Booking</span>

            </a>


            <a href="notifications.php" class="menu-item">

                <img
                    src="../assets/icons/notification1.svg"
                    alt="Notifications"
                >

                <span>Notifications</span>

            </a>


            <a href="profile.php" class="menu-item">

                <img
                    src="../assets/icons/profile1.svg"
                    alt="Profile"
                >

                <span>Profile</span>

            </a>

        </nav>

    </aside>


    <!-- ================= MAIN AREA ================= -->

    <main class="main-area">


        <!-- TOP BAR -->

        <header class="top-bar">

            <div class="brand">

                <img
                    src="../assets/icons/education1.svg"
                    alt="Graduation Cap"
                >

                <span>UCSC SIS</span>

            </div>


            <div class="user-area">

                <img
                    src="../assets/icons/notification.svg"
                    class="top-notification"
                    alt="Notifications"
                >


                <div class="avatar">
                    M
                </div>


                <div class="user-info">

                    <strong>Mr. Rizan</strong>

                    <span>Non-Academic Staff</span>

                </div>


                <span class="arrow">
                    ▼
                </span>

            </div>

        </header>


        <!-- ================= CONTENT ================= -->

        <section class="content">


            <!-- PAGE TITLE -->

            <div class="page-title">

                <div class="page-title-icon">

                    <img
                        src="../assets/icons/timetable.svg"
                        alt="Timetable"
                    >

                </div>

                <h1>
                    Timetable Records
                </h1>

            </div>


            <!-- ================= ADD ================= -->

            <section class="record-section">

                <h2>
                    Add Timetable Slot
                </h2>


                <div class="section-line"></div>


                <div class="fields-row">

                    <div class="field">
                        Subject
                    </div>

                    <div class="field small-field">
                        IS/CS
                    </div>

                    <div class="field">
                        Monday
                    </div>

                    <div class="field">
                        Start Time
                    </div>

                    <div class="field">
                        End Time
                    </div>

                    <div class="field">
                        Room / Lab
                    </div>

                    <div class="field">
                        Semester
                    </div>

                    <div class="field">
                        Year
                    </div>

                </div>


                <button class="action-button">
                    Save
                </button>

            </section>


            <!-- ================= DELETE ================= -->

            <section class="record-section">

                <h2>
                    Delete Timetable Slot
                </h2>


                <div class="section-line"></div>


                <div class="fields-row">

                    <div class="field">
                        Subject
                    </div>

                    <div class="field small-field">
                        IS/CS
                    </div>

                    <div class="field">
                        Monday
                    </div>

                    <div class="field">
                        Start Time
                    </div>

                    <div class="field">
                        End Time
                    </div>

                    <div class="field">
                        Room / Lab
                    </div>

                    <div class="field">
                        Semester
                    </div>

                    <div class="field">
                        Year
                    </div>

                </div>


                <button class="action-button">
                    Delete
                </button>

            </section>


            <!-- ================= UPDATE ================= -->

            <section class="record-section update-section">

                <h2>
                    Update Timetable Slot
                </h2>


                <div class="section-line"></div>


                <h3>
                    Old Timetable Slot
                </h3>


                <div class="fields-row">

                    <div class="field">
                        Subject
                    </div>

                    <div class="field small-field">
                        IS/CS
                    </div>

                    <div class="field">
                        Monday
                    </div>

                    <div class="field">
                        Start Time
                    </div>

                    <div class="field">
                        End Time
                    </div>

                    <div class="field">
                        Room / Lab
                    </div>

                    <div class="field">
                        Semester
                    </div>

                    <div class="field">
                        Year
                    </div>

                </div>


                <div class="section-line second-line"></div>


                <h3>
                    New Timetable Slot
                </h3>


                <div class="fields-row">

                    <div class="field">
                        Subject
                    </div>

                    <div class="field small-field">
                        IS/CS
                    </div>

                    <div class="field">
                        Monday
                    </div>

                    <div class="field">
                        Start Time
                    </div>

                    <div class="field">
                        End Time
                    </div>

                    <div class="field">
                        Room / Lab
                    </div>

                    <div class="field">
                        Semester
                    </div>

                    <div class="field">
                        Year
                    </div>

                </div>


                <button class="action-button">
                    Update
                </button>

            </section>


            <!-- ================= VIEW ================= -->

            <section class="view-section">

                <h2>
                    View Timetable Slot
                </h2>


                <div class="section-line"></div>


                <div class="view-fields">

                    <div class="field">
                        Semester
                    </div>

                    <div class="field">
                        Year
                    </div>

                </div>


                <button class="action-button">
                    View
                </button>

            </section>


        </section>

    </main>

</div>

</body>
</html>