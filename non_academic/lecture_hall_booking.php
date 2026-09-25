
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

<style>

/* ============================================================
   PAGE
============================================================ */

.lecture-booking-page {
    width: 100%;
    max-width: 1250px;
    margin: 0 auto;
    padding-bottom: 40px;
}


/* ============================================================
   PAGE HEADER
============================================================ */

.lecture-booking-header {
    padding: 5px 0 20px 0;
}

.lecture-booking-header h1 {
    margin: 0;
    font-size: 30px;
    font-weight: 700;
    color: #111827;
}

.lecture-booking-header p {
    margin: 8px 0 0;
    font-size: 15px;
    color: #6b7280;
}


/* ============================================================
   THIN DIVIDER
============================================================ */

.lecture-booking-divider {
    width: 100%;
    height: 1px;
    background: #1f2937;
    margin-bottom: 28px;
}


/* ============================================================
   CARDS
============================================================ */

.booking-card {
    background: #ffffff;
    border: 1px solid #1f2937;
    border-radius: 16px;
    margin-bottom: 28px;
    overflow: hidden;
}


/* ============================================================
   CARD HEADER
============================================================ */

.booking-card-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 22px 25px;
}

.booking-card-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;

    /* System dark-navy theme */
    background: #eef2f7;

    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.booking-card-icon img {
    width: 25px;
    height: 25px;
    object-fit: contain;
}

.booking-card-header h2 {
    margin: 0;
    font-size: 21px;
    font-weight: 700;
    color: #111827;
}


/* ============================================================
   CARD HEADER DIVIDER
============================================================ */

.booking-card-line {
    width: 100%;
    height: 1px;
    background: #d1d5db;
}


/* ============================================================
   CARD BODY
============================================================ */

.booking-card-body {
    padding: 28px 25px 30px;
}


/* ============================================================
   FORM GRID
============================================================ */

.booking-form-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 22px;
}


/* ============================================================
   FORM GROUP
============================================================ */

.booking-form-group {
    display: flex;
    flex-direction: column;
}

.booking-form-group label {
    margin-bottom: 8px;
    font-size: 15px;
    font-weight: 600;
    color: #111827;
}


/* ============================================================
   INPUTS
============================================================ */

.booking-form-control {
    width: 100%;
    min-height: 48px;

    padding: 11px 14px;
    box-sizing: border-box;

    background: #ffffff;

    border: 1px solid #243b64;
    border-radius: 9px;

    color: #111827;
    font-size: 15px;

    outline: none;

    transition:
        border-color 0.2s ease,
        box-shadow 0.2s ease;
}

.booking-form-control:focus {
    border-color: #000a1e;
    box-shadow: 0 0 0 3px rgba(0, 10, 30, 0.10);
}

.booking-form-control::placeholder {
    color: #9ca3af;
}

select.booking-form-control {
    cursor: pointer;
}


/* ============================================================
   FULL WIDTH FIELD
============================================================ */

.booking-full-width {
    grid-column: 1 / -1;
}


/* ============================================================
   BUTTON AREA
============================================================ */

.booking-button-area {
    margin-top: 26px;

    display: flex;
    justify-content: flex-end;
}


/* ============================================================
   NORMAL BUTTON
   Same dark-navy style as the other system users
============================================================ */

.booking-button {
    min-width: 145px;
    min-height: 48px;

    padding: 11px 24px;

    border: 1px solid #000a1e;
    border-radius: 9px;

    background: #000a1e;
    color: #ffffff;

    font-size: 15px;
    font-weight: 700;

    cursor: pointer;

    transition:
        background 0.2s ease,
        border-color 0.2s ease,
        transform 0.2s ease;
}

.booking-button:hover {
    background: #001a3d;
    border-color: #001a3d;
    transform: translateY(-1px);
}


/* ============================================================
   CURRENT BOOKINGS
============================================================ */

.current-bookings-card {
    margin-top: 30px;
}


/* ============================================================
   BOOKINGS TABLE WRAPPER
============================================================ */

.bookings-table-wrapper {
    width: 100%;
    overflow-x: auto;
}


/* ============================================================
   BOOKINGS TABLE
============================================================ */

.bookings-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

.bookings-table th {
    padding: 14px 15px;

    background: #f3f5f8;

    border-bottom: 1px solid #cbd5e1;

    color: #111827;

    font-size: 13px;
    font-weight: 700;

    text-align: left;
    white-space: nowrap;
}

.bookings-table td {
    padding: 16px 15px;

    border-bottom: 1px solid #e5e7eb;

    color: #374151;

    font-size: 14px;

    vertical-align: middle;
}

.bookings-table tbody tr:hover {
    background: #f8fafc;
}

.bookings-table tbody tr:last-child td {
    border-bottom: none;
}


/* ============================================================
   ROOM NAME
============================================================ */

.booking-room-name {
    font-weight: 700;
    color: #111827;
}


/* ============================================================
   PURPOSE
============================================================ */

.booking-purpose {
    color: #374151;
}


/* ============================================================
   STATUS
============================================================ */

.booking-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    min-width: 75px;

    padding: 6px 11px;

    border-radius: 999px;

    background: #e7f5ec;
    color: #166534;

    font-size: 12px;
    font-weight: 700;
}


/* ============================================================
   CANCEL BUTTON
   UI ONLY
============================================================ */

.cancel-booking-button {
    min-height: 38px;

    padding: 8px 15px;

    border: 1px solid #8f1414;
    border-radius: 8px;

    background: #8f1414;
    color: #ffffff;

    font-size: 13px;
    font-weight: 600;

    cursor: pointer;

    transition:
        background 0.2s ease,
        border-color 0.2s ease;
}

.cancel-booking-button:hover {
    background: #6f0f0f;
    border-color: #6f0f0f;
}


/* ============================================================
   NO BOOKINGS
============================================================ */

.no-bookings {
    padding: 35px 20px;

    text-align: center;

    color: #6b7280;

    font-size: 14px;
}


/* ============================================================
   RESPONSIVE
============================================================ */

@media (max-width: 1000px) {

    .booking-form-grid {
        grid-template-columns: repeat(2, 1fr);
    }

}


@media (max-width: 650px) {

    .lecture-booking-page {
        padding-left: 15px;
        padding-right: 15px;
    }

    .booking-form-grid {
        grid-template-columns: 1fr;
    }

    .booking-card-header {
        padding: 18px;
    }

    .booking-card-body {
        padding: 22px 18px 25px;
    }

    .booking-button-area {
        justify-content: stretch;
    }

    .booking-button {
        width: 100%;
    }

    .bookings-table {
        min-width: 800px;
    }

}

</style>


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
                            style="resize:vertical;"
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
