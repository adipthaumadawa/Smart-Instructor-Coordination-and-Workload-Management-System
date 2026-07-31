<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1440, initial-scale=1.0">

    <title>Timetable Records - UCSC SIS</title>

    <link rel="stylesheet" href="css/timetable_records.css">
</head>

<body>

<div class="page">

    <!-- =========================
         TOP BAR
    ========================== -->

    <div class="top-bar">

        <!-- Graduation Cap -->
        <div class="graduation-cap">
            <img src="../assets/icons/graduation-cap.svg" alt="Graduation Cap">
        </div>

        <div class="system-name">
            UCSC SIS
        </div>


        <!-- User Area -->

        <div class="top-notification">
            <img src="../assets/icons/notification.svg" alt="Notifications">
        </div>

        <div class="profile-circle">
            M
        </div>

        <div class="profile-name">
            Mr. Rizan
        </div>

        <div class="profile-role">
            Non-Academic Staff
        </div>

        <div class="profile-arrow">
            ▼
        </div>

    </div>


    <!-- =========================
         SIDEBAR
    ========================== -->

    <aside class="sidebar">

        <!-- UCSC Logo -->

        <img
            class="ucsc-logo"
            src="../assets/images/ucsc-logo.png"
            alt="UCSC"
        >

        <div class="system-title">
            Smart Instructor System
        </div>

        <div class="university-name">
            University of Colombo<br>
            School of Computing
        </div>


        <div class="sidebar-divider"></div>


        <!-- Dashboard -->

        <a href="dashboard.php" class="sidebar-item dashboard-item">

            <img
                src="../assets/icons/dashboard.svg"
                alt="Dashboard"
            >

            <span>Dashboard</span>

        </a>


        <!-- Operations -->

        <div class="operations-title">
            OPERATIONS
        </div>


        <!-- Timetable -->

        <a
            href="timetable_records.php"
            class="sidebar-item timetable-item active"
        >

            <img
                src="../assets/icons/timetable.svg"
                alt="Timetable"
            >

            <span>Timetable Management</span>

        </a>


        <!-- Room Schedules -->

        <a
            href="room_schedules.php"
            class="sidebar-item room-item"
        >

            <img
                src="../assets/icons/room-schedule.svg"
                alt="Room Schedules"
            >

            <span>Room Schedules</span>

        </a>


        <!-- Lecture Hall -->

        <a
            href="lecture_hall_booking.php"
            class="sidebar-item lecture-item"
        >

            <img
                src="../assets/icons/lecture-hall.svg"
                alt="Lecture Hall"
            >

            <span>Lecture Hall Booking</span>

        </a>


        <!-- Notifications -->

        <a
            href="notifications.php"
            class="sidebar-item notification-item"
        >

            <img
                src="../assets/icons/notification.svg"
                alt="Notifications"
            >

            <span>Notifications</span>

        </a>


        <!-- Profile -->

        <a
            href="profile.php"
            class="sidebar-item profile-item"
        >

            <img
                src="../assets/icons/profile.svg"
                alt="Profile"
            >

            <span>Profile</span>

        </a>

    </aside>


    <!-- =========================
         MAIN CONTENT
    ========================== -->

    <main class="main-content">


        <!-- PAGE TITLE -->

        <div class="page-title-icon">

            <img
                src="../assets/icons/timetable.svg"
                alt="Timetable"
            >

        </div>

        <div class="page-title">
            Timetable Records
        </div>


        <!-- =========================
             ADD TIMETABLE
        ========================== -->

        <section class="add-section">

            <div class="section-title">
                Add Timetable Slot
            </div>

            <div class="section-box">

                <div class="section-line"></div>


                <div class="field subject-add">
                    Subject
                </div>

                <div class="field course-add">
                    IS/CS
                </div>

                <div class="field day-add">
                    Monday
                </div>

                <div class="field start-add">
                    Start Time
                </div>

                <div class="field end-add">
                    End Time
                </div>

                <div class="field room-add">
                    Room / Lab
                </div>

                <div class="field semester-add">
                    Semester
                </div>

                <div class="field year-add">
                    Year
                </div>


                <button class="save-button">
                    Save
                </button>

            </div>

        </section>


        <!-- =========================
             DELETE TIMETABLE
        ========================== -->

        <section class="delete-section">

            <div class="section-title">
                Delete Timetable Slot
            </div>

            <div class="section-box">

                <div class="section-line"></div>


                <div class="field subject-delete">
                    Subject
                </div>

                <div class="field course-delete">
                    IS/CS
                </div>

                <div class="field day-delete">
                    Monday
                </div>

                <div class="field start-delete">
                    Start Time
                </div>

                <div class="field end-delete">
                    End Time
                </div>

                <div class="field room-delete">
                    Room / Lab
                </div>

                <div class="field semester-delete">
                    Semester
                </div>

                <div class="field year-delete">
                    Year
                </div>


                <button class="delete-button">
                    Delete
                </button>

            </div>

        </section>


        <!-- =========================
             UPDATE TIMETABLE
        ========================== -->

        <section class="update-section">

            <div class="section-title">
                Update Timetable Slot
            </div>

            <div class="update-box">

                <div class="section-line"></div>


                <!-- OLD -->

                <div class="update-heading old-heading">
                    Old Timetable Slot
                </div>


                <div class="field subject-old">
                    Subject
                </div>

                <div class="field course-old">
                    IS/CS
                </div>

                <div class="field day-old">
                    Monday
                </div>

                <div class="field start-old">
                    Start Time
                </div>

                <div class="field end-old">
                    End Time
                </div>

                <div class="field room-old">
                    Room / Lab
                </div>

                <div class="field semester-old">
                    Semester
                </div>

                <div class="field year-old">
                    Year
                </div>


                <div class="second-section-line"></div>


                <!-- NEW -->

                <div class="update-heading new-heading">
                    New Timetable Slot
                </div>


                <div class="field subject-new">
                    Subject
                </div>

                <div class="field course-new">
                    IS/CS
                </div>

                <div class="field day-new">
                    Monday
                </div>

                <div class="field start-new">
                    Start Time
                </div>

                <div class="field end-new">
                    End Time
                </div>

                <div class="field room-new">
                    Room / Lab
                </div>

                <div class="field semester-new">
                    Semester
                </div>

                <div class="field year-new">
                    Year
                </div>


                <button class="update-button">
                    Update
                </button>

            </div>

        </section>


        <!-- =========================
             VIEW TIMETABLE
        ========================== -->

        <section class="view-section">

            <div class="view-title">
                View Timetable Slot
            </div>

            <div class="view-box">

                <div class="section-line"></div>


                <div class="field view-semester">
                    Semester
                </div>

                <div class="field view-year">
                    Year
                </div>


                <button class="view-button">
                    View
                </button>

            </div>

        </section>

    </main>

</div>

</body>
</html>