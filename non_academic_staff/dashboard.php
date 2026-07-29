<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Non-Academic Staff Dashboard</title>

    <!-- Non-Academic Staff CSS -->
    <link
        rel="stylesheet"
        href="css/non_academic_dashboard.css"
    >

</head>


<body>


<div class="dashboard-container">


    <!-- ==================================================
         SIDEBAR
    =================================================== -->

    <aside class="sidebar">


        <!-- LOGO AREA -->

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



        <!-- SIDEBAR NAVIGATION -->

        <nav class="sidebar-navigation">


            <!-- DASHBOARD -->

            <a
                href="dashboard.php"
                class="nav-item active"
            >

                <img
                    src="../assets/icons/dashboard.svg"
                    alt="Dashboard"
                >

                <span>
                    Dashboard
                </span>

            </a>



            <!-- SECTION TITLE -->

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
                    src="../assets/icons/notification.svg"
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
                    src="../assets/icons/profile.svg"
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
    =================================================== -->

    <main class="main-content">



        <!-- ==================================================
             TOP HEADER
        =================================================== -->

        <header class="top-header">


            <!-- SYSTEM NAME -->

            <div class="system-name">

                <img
                    src="../assets/icons/graduation-cap.svg"
                    alt="UCSC SIS"
                >

                <strong>
                    UCSC SIS
                </strong>

            </div>



            <!-- USER SECTION -->

            <div class="user-section">


                <!-- NOTIFICATION -->

                <img
                    src="../assets/icons/notification.svg"
                    class="top-notification"
                    alt="Notifications"
                >



                <!-- USER AVATAR -->

                <div class="user-avatar">

                    M

                </div>



                <!-- USER DETAILS -->

                <div class="user-details">

                    <strong>
                        Mr. Rizan
                    </strong>

                    <span>
                        Non-Academic Staff
                    </span>

                </div>



                <!-- DROPDOWN -->

                <img
                    src="../assets/icons/dropdown.svg"
                    class="dropdown-icon"
                    alt="Dropdown"
                >


            </div>


        </header>




        <!-- ==================================================
             PAGE CONTENT
        =================================================== -->

        <section class="content">



            <!-- ==================================================
                 DASHBOARD HEADER
            =================================================== -->

            <div class="dashboard-title">


                <div>

                    <h1>
                        Non-Academic Staff Dashboard
                    </h1>

                    <p>
                        Timetable records, attendance updates,
                        lecture room/lab schedules, and leave notifications.
                    </p>

                </div>



                <!-- PROFILE ICON -->

                <img
                    src="../assets/icons/profile.svg"
                    class="large-profile-icon"
                    alt="Profile"
                >


            </div>




            <!-- ==================================================
                 DASHBOARD CARDS
            =================================================== -->

            <div class="dashboard-grid">



                <!-- HALL & LAB OCCUPANCY -->

                <div class="stat-card">

                    <h2>
                        Hall & Lab Occupancy
                    </h2>

                    <div class="stat-number">
                        82%
                    </div>

                </div>




                <!-- LEAVE ALERTS -->

                <div class="stat-card">

                    <h2>
                        Leave Alerts (Today)
                    </h2>

                    <div class="stat-number">
                        3 Received
                    </div>

                </div>




                <!-- CANCELLATIONS -->

                <div class="cancellation-card">


                    <h2>
                        Booking Cancels & Lectures Cancels
                    </h2>



                    <!-- CANCELLATION 1 -->

                    <div class="cancel-item">

                        <strong>
                            E 401 - Database Systems
                        </strong>

                        <span>
                            May 14, 10:00 – 12:00
                        </span>

                    </div>



                    <!-- CANCELLATION 2 -->

                    <div class="cancel-item">

                        <strong>
                            W002 - Presentation
                        </strong>

                        <span>
                            May 15, 10:00 – 12:00
                        </span>

                    </div>



                    <!-- CANCELLATION 3 -->

                    <div class="cancel-item">

                        <strong>
                            S 104 - Software Engineering
                        </strong>

                        <span>
                            May 13, 10:00 – 12:00
                        </span>

                    </div>


                </div>


            </div>




            <!-- ==================================================
                 ROOM & LAB SCHEDULE
            =================================================== -->

            <div class="schedule-section">


                <h2>
                    Today's Room & Lab Schedule Overview
                </h2>



                <div class="table-container">


                    <table>


                        <thead>

                            <tr>

                                <th>
                                    Room/Lab
                                </th>

                                <th>
                                    Time
                                </th>

                                <th>
                                    Booked By
                                </th>

                                <th>
                                    Purpose
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>



                        <tbody>


                            <!-- ROW 1 -->

                            <tr>

                                <td>
                                    E 401
                                </td>

                                <td>
                                    08:00 – 10:00
                                </td>

                                <td>
                                    M.A.U. Abedeera
                                </td>

                                <td>
                                    Lecture
                                </td>

                                <td>

                                    <span class="status confirmed">
                                        Confirmed
                                    </span>

                                </td>

                            </tr>



                            <!-- ROW 2 -->

                            <tr>

                                <td>
                                    W002
                                </td>

                                <td>
                                    10:00 – 12:00
                                </td>

                                <td>
                                    A.A.T. Aloka
                                </td>

                                <td>
                                    Lecture
                                </td>

                                <td>

                                    <span class="status pending">
                                        Pending
                                    </span>

                                </td>

                            </tr>



                            <!-- ROW 3 -->

                            <tr>

                                <td>
                                    Electronic Lab
                                </td>

                                <td>
                                    13:00 – 15:00
                                </td>

                                <td>
                                    M.R. Amarasekara
                                </td>

                                <td>
                                    Presentation
                                </td>

                                <td>

                                    <span class="status confirmed">
                                        Confirmed
                                    </span>

                                </td>

                            </tr>


                        </tbody>


                    </table>


                </div>


            </div>


        </section>


    </main>


</div>


</body>

</html>