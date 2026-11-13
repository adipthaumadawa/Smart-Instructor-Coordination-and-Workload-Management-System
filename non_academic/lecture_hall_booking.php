
<?php
/**
 * ============================================================
 * NON-ACADEMIC STAFF
 * LECTURE HALL BOOKING
 * Smart Instructor Coordination and Workload Management System
 *
 * UI ONLY
 * - Check Available UI
 * - Book Lecture Room / Laboratory UI
 * - Current Bookings UI
 * - Cancel Booking button is UI only
 * ============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';

checkRole(ROLE_NON_ACADEMIC);

$pageTitle = "Lecture Hall Booking";

include __DIR__ . '/../includes/header.php';
?>


<!-- ============================================================
     MAIN PAGE
============================================================ -->

<div class="lecture-booking-page">


    <!-- ========================================================
         PAGE HEADING
    ======================================================== -->

    <div class="lecture-booking-header">

        <h1>
            Lecture Hall Booking
        </h1>

        <p>
            Check lecture room availability and book lecture rooms
            and laboratories.
        </p>

    </div>


    <!-- ========================================================
         THIN DIVIDER
    ======================================================== -->

    <div class="lecture-booking-divider"></div>


    <!-- ========================================================
         CARD 1
         CHECK AVAILABLE
    ======================================================== -->

    <div class="booking-card">

        <div class="booking-card-header">

            <div class="booking-card-icon">

                <img
                    src="<?= app_url('assets/icons/calendar.svg') ?>"
                    alt="Check Available"
                >

            </div>

            <h2>
                Check Available
            </h2>

        </div>


        <div class="booking-card-line"></div>


        <div class="booking-card-body">

            <form method="POST">

                <div class="booking-form-grid">


                    <!-- DATE -->

                    <div class="booking-form-group">

                        <label for="check_date">
                            Date
                        </label>

                        <input
                            type="date"
                            id="check_date"
                            name="check_date"
                            class="booking-form-control"
                        >

                    </div>


                    <!-- START TIME -->

                    <div class="booking-form-group">

                        <label for="check_start_time">
                            Start Time
                        </label>

                        <input
                            type="time"
                            id="check_start_time"
                            name="check_start_time"
                            class="booking-form-control"
                        >

                    </div>


                    <!-- END TIME -->

                    <div class="booking-form-group">

                        <label for="check_end_time">
                            End Time
                        </label>

                        <input
                            type="time"
                            id="check_end_time"
                            name="check_end_time"
                            class="booking-form-control"
                        >

                    </div>


                    <!-- ROOM TYPE -->

                    <div class="booking-form-group">

                        <label for="check_room_type">
                            Room Type
                        </label>

                        <select
                            id="check_room_type"
                            name="check_room_type"
                            class="booking-form-control"
                        >

                            <option value="">
                                Select Room Type
                            </option>

                            <option value="Lecture Hall">
                                Lecture Hall
                            </option>

                            <option value="Laboratory">
                                Laboratory
                            </option>

                        </select>

                    </div>


                    <!-- CAPACITY -->

                    <div class="booking-form-group">

                        <label for="check_capacity">
                            Required Capacity
                        </label>

                        <input
                            type="number"
                            id="check_capacity"
                            name="check_capacity"
                            class="booking-form-control"
                            placeholder="Enter capacity"
                            min="1"
                        >

                    </div>


                    <!-- LOCATION -->

                    <div class="booking-form-group">

                        <label for="check_location">
                            Location
                        </label>

                        <input
                            type="text"
                            id="check_location"
                            name="check_location"
                            class="booking-form-control"
                            placeholder="Location"
                        >

                    </div>

                </div>


                <!-- BUTTON -->

                <div class="booking-button-area">

                    <button
                        type="button"
                        class="booking-button"
                    >
                        Check Available
                    </button>

                </div>

            </form>

        </div>

    </div>


    <!-- ========================================================
         CARD 2
         BOOK A LECTURE ROOM & LABORATORY
    ======================================================== -->

    <div class="booking-card book-room-card">

        <div class="booking-card-header">

            <div class="booking-card-icon">

                <img
                    src="<?= app_url('assets/icons/door.svg') ?>"
                    alt="Book Lecture Room"
                >

            </div>

            <h2>
                Book a Lecture Room &amp; Laboratory
            </h2>

        </div>


        <div class="booking-card-line"></div>


        <div class="booking-card-body">

            <form method="POST">

                <div class="booking-form-grid">


                    <!-- DATE -->

                    <div class="booking-form-group">

                        <label for="booking_date">
                            Date
                        </label>

                        <input
                            type="date"
                            id="booking_date"
                            name="booking_date"
                            class="booking-form-control"
                        >

                    </div>


                    <!-- START TIME -->

                    <div class="booking-form-group">

                        <label for="booking_start_time">
                            Start Time
                        </label>

                        <input
                            type="time"
                            id="booking_start_time"
                            name="booking_start_time"
                            class="booking-form-control"
                        >

                    </div>


                    <!-- END TIME -->

                    <div class="booking-form-group">

                        <label for="booking_end_time">
                            End Time
                        </label>

                        <input
                            type="time"
                            id="booking_end_time"
                            name="booking_end_time"
                            class="booking-form-control"
                        >

                    </div>


                    <!-- ROOM -->

                    <div class="booking-form-group">

                        <label for="booking_room">
                            Lecture Room / Laboratory
                        </label>

                        <select
                            id="booking_room"
                            name="booking_room"
                            class="booking-form-control"
                        >

                            <option value="">
                                Select Room / Laboratory
                            </option>

                            <option value="Room 01">
                                Room 01
                            </option>

                            <option value="Room 02">
                                Room 02
                            </option>

                            <option value="Laboratory 01">
                                Laboratory 01
                            </option>

                            <option value="Laboratory 02">
                                Laboratory 02
                            </option>

                        </select>

                    </div>


                    <!-- PURPOSE -->

                    <div class="booking-form-group">

                        <label for="booking_purpose">
                            Purpose
                        </label>

                        <input
                            type="text"
                            id="booking_purpose"
                            name="booking_purpose"
                            class="booking-form-control"
                            placeholder="Enter booking purpose"
                        >

                    </div>


                    <!-- CAPACITY -->

                    <div class="booking-form-group">

                        <label for="booking_capacity">
                            Required Capacity
                        </label>

                        <input
                            type="number"
                            id="booking_capacity"
                            name="booking_capacity"
                            class="booking-form-control"
                            placeholder="Enter capacity"
                            min="1"
                        >

                    </div>


                    <!-- REMARKS -->

                    <div class="booking-form-group booking-full-width">

                        <label for="booking_remarks">
                            Remarks
                        </label>

                        <textarea
                            id="booking_remarks"
                            name="booking_remarks"
                            class="booking-form-control"
                            placeholder="Additional remarks"
                            rows="4"
                        ></textarea>

                    </div>

                </div>


                <!-- BUTTON -->

                <div class="booking-button-area">

                    <button
                        type="button"
                        class="booking-button"
                    >
                        Book Lecture Room
                    </button>

                </div>

            </form>

        </div>

    </div>


    <!-- ========================================================
         CARD 3
         CURRENT BOOKINGS
    ======================================================== -->

    <div class="booking-card current-bookings-card">

        <div class="booking-card-header">

            <div class="booking-card-icon">

                <img
                    src="<?= app_url('assets/icons/calendar.svg') ?>"
                    alt="Current Bookings"
                >

            </div>

            <h2>
                Current Bookings
            </h2>

        </div>


        <div class="booking-card-line"></div>


        <div class="booking-card-body">

            <div class="bookings-table-wrapper">

                <table class="bookings-table">

                    <thead>

                        <tr>

                            <th>
                                Date
                            </th>

                            <th>
                                Time
                            </th>

                            <th>
                                Room / Laboratory
                            </th>

                            <th>
                                Purpose
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <!-- ==================================================
                             SAMPLE BOOKING 1
                             UI ONLY
                        ================================================== -->

                        <tr>

                            <td>
                                24 Sep 2026
                            </td>

                            <td>
                                09:00 AM - 11:00 AM
                            </td>

                            <td>

                                <span class="booking-room-name">
                                    Lecture Hall 01
                                </span>

                            </td>

                            <td>

                                <span class="booking-purpose">
                                    CS Lecture
                                </span>

                            </td>

                            <td>

                                <span class="booking-status">
                                    Booked
                                </span>

                            </td>

                            <td>

                                <button
                                    type="button"
                                    class="cancel-booking-button"
                                >
                                    Cancel Booking
                                </button>

                            </td>

                        </tr>


                        <!-- ==================================================
                             SAMPLE BOOKING 2
                             UI ONLY
                        ================================================== -->

                        <tr>

                            <td>
                                25 Sep 2026
                            </td>

                            <td>
                                01:00 PM - 03:00 PM
                            </td>

                            <td>

                                <span class="booking-room-name">
                                    Laboratory 02
                                </span>

                            </td>

                            <td>

                                <span class="booking-purpose">
                                    Programming Practical
                                </span>

                            </td>

                            <td>

                                <span class="booking-status">
                                    Booked
                                </span>

                            </td>

                            <td>

                                <button
                                    type="button"
                                    class="cancel-booking-button"
                                >
                                    Cancel Booking
                                </button>

                            </td>

                        </tr>


                        <!-- ==================================================
                             SAMPLE BOOKING 3
                             UI ONLY
                        ================================================== -->

                        <tr>

                            <td>
                                28 Sep 2026
                            </td>

                            <td>
                                10:00 AM - 12:00 PM
                            </td>

                            <td>

                                <span class="booking-room-name">
                                    Lecture Hall 02
                                </span>

                            </td>

                            <td>

                                <span class="booking-purpose">
                                    IS Lecture
                                </span>

                            </td>

                            <td>

                                <span class="booking-status">
                                    Booked
                                </span>

                            </td>

                            <td>

                                <button
                                    type="button"
                                    class="cancel-booking-button"
                                >
                                    Cancel Booking
                                </button>

                            </td>

                        </tr>


                    </tbody>

                </table>

            </div>

        </div>

    </div>


</div>


<?php
include __DIR__ . '/../includes/footer.php';
?>
```
