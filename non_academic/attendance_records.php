<?php
/**
 * ============================================================
 * INSTRUCTOR ATTENDANCE RECORDS
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

$pageTitle = "Attendance Records";


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
| RECORD ATTENDANCE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['save_attendance'])
) {

    $instructorId =
        (int)($_POST['instructor_id'] ?? 0);

    $attendanceDate =
        trim($_POST['attendance_date'] ?? '');

    $status =
        trim($_POST['status'] ?? '');

    $remarks =
        trim($_POST['remarks'] ?? '');


    try {

        /*
        |--------------------------------------------------------------------------
        | VALIDATE INSTRUCTOR
        |--------------------------------------------------------------------------
        */

        if ($instructorId <= 0) {

            throw new Exception(
                'Please select an instructor.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | VALIDATE DATE
        |--------------------------------------------------------------------------
        */

        if ($attendanceDate === '') {

            throw new Exception(
                'Please select the attendance date.'
            );
        }


        $dateObject =
            DateTime::createFromFormat(
                'Y-m-d',
                $attendanceDate
            );


        if (
            !$dateObject ||
            $dateObject->format('Y-m-d') !== $attendanceDate
        ) {

            throw new Exception(
                'Please enter a valid attendance date.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | VALIDATE STATUS
        |--------------------------------------------------------------------------
        */

        $allowedStatuses = [
            'Present',
            'Absent',
            'Late',
            'On Leave'
        ];


        if (
            !in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {

            throw new Exception(
                'Please select a valid attendance status.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK INSTRUCTOR
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT
                id,
                full_name
            FROM users
            WHERE id = ?
              AND role = 'instructor'
            LIMIT 1
        ");

        $stmt->execute([
            $instructorId
        ]);


        $instructor =
            $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$instructor) {

            throw new Exception(
                'The selected instructor could not be found.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK EXISTING ATTENDANCE
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT id
            FROM instructor_attendance
            WHERE instructor_id = ?
              AND attendance_date = ?
            LIMIT 1
        ");

        $stmt->execute([
            $instructorId,
            $attendanceDate
        ]);


        $existing =
            $stmt->fetch(PDO::FETCH_ASSOC);


        if ($existing) {

            throw new Exception(
                'Attendance for ' .
                $instructor['full_name'] .
                ' on ' .
                $attendanceDate .
                ' already exists. Use Update Attendance instead.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CURRENT LOGGED-IN USER
        |--------------------------------------------------------------------------
        */

        $recordedBy = null;

        if (isset($_SESSION['user_id'])) {

            $recordedBy =
                (int)$_SESSION['user_id'];

        } elseif (isset($_SESSION['id'])) {

            $recordedBy =
                (int)$_SESSION['id'];
        }


        /*
        |--------------------------------------------------------------------------
        | INSERT ATTENDANCE
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            INSERT INTO instructor_attendance
            (
                instructor_id,
                attendance_date,
                status,
                remarks,
                recorded_by
            )
            VALUES
            (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $instructorId,
            $attendanceDate,
            $status,
            $remarks !== '' ? $remarks : null,
            $recordedBy
        ]);


        $_SESSION['success'] =
            'Attendance recorded successfully for ' .
            $instructor['full_name'] .
            '.';

    } catch (Throwable $e) {

        $_SESSION['error'] =
            $e->getMessage();
    }


    header(
        'Location: attendance_records.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| UPDATE ATTENDANCE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_attendance'])
) {

    $attendanceId =
        (int)($_POST['attendance_id'] ?? 0);

    $status =
        trim($_POST['update_status'] ?? '');

    $remarks =
        trim($_POST['update_remarks'] ?? '');


    try {

        if ($attendanceId <= 0) {

            throw new Exception(
                'Invalid attendance record.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | VALIDATE STATUS
        |--------------------------------------------------------------------------
        */

        $allowedStatuses = [
            'Present',
            'Absent',
            'Late',
            'On Leave'
        ];


        if (
            !in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {

            throw new Exception(
                'Please select a valid attendance status.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK ATTENDANCE RECORD
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT
                ia.id,
                u.full_name,
                ia.attendance_date
            FROM instructor_attendance ia
            INNER JOIN users u
                ON u.id = ia.instructor_id
            WHERE ia.id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $attendanceId
        ]);


        $attendance =
            $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$attendance) {

            throw new Exception(
                'Attendance record not found.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            UPDATE instructor_attendance
            SET
                status = ?,
                remarks = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $status,
            $remarks !== '' ? $remarks : null,
            $attendanceId
        ]);


        $_SESSION['success'] =
            'Attendance updated successfully for ' .
            $attendance['full_name'] .
            '.';

    } catch (Throwable $e) {

        $_SESSION['error'] =
            $e->getMessage();
    }


    header(
        'Location: attendance_records.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| LOAD INSTRUCTORS
|--------------------------------------------------------------------------
*/

$instructors = [];


try {

    $stmt = $pdo->query("
        SELECT
            id,
            full_name
        FROM users
        WHERE role = 'instructor'
        ORDER BY full_name ASC
    ");


    $instructors =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {

    $error =
        'Unable to load instructor records.';
}


/*
|--------------------------------------------------------------------------
| FILTER VALUES
|--------------------------------------------------------------------------
*/

$filterDate =
    trim($_GET['attendance_date'] ?? '');

$filterInstructor =
    (int)($_GET['instructor_id'] ?? 0);

$filterStatus =
    trim($_GET['status'] ?? '');


/*
|--------------------------------------------------------------------------
| LOAD ATTENDANCE RECORDS
|--------------------------------------------------------------------------
*/

$attendanceRecords = [];


try {

    $sql = "
        SELECT
            ia.id,
            ia.instructor_id,
            ia.attendance_date,
            ia.status,
            ia.remarks,
            ia.recorded_by,
            ia.created_at,
            ia.updated_at,

            u.full_name AS instructor_name,

            recorder.full_name AS recorded_by_name

        FROM instructor_attendance ia

        INNER JOIN users u
            ON u.id = ia.instructor_id

        LEFT JOIN users recorder
            ON recorder.id = ia.recorded_by

        WHERE 1 = 1
    ";


    $params = [];


    /*
    |--------------------------------------------------------------------------
    | DATE FILTER
    |--------------------------------------------------------------------------
    */

    if ($filterDate !== '') {

        $sql .= "
            AND ia.attendance_date = ?
        ";

        $params[] =
            $filterDate;
    }


    /*
    |--------------------------------------------------------------------------
    | INSTRUCTOR FILTER
    |--------------------------------------------------------------------------
    */

    if ($filterInstructor > 0) {

        $sql .= "
            AND ia.instructor_id = ?
        ";

        $params[] =
            $filterInstructor;
    }


    /*
    |--------------------------------------------------------------------------
    | STATUS FILTER
    |--------------------------------------------------------------------------
    */

    if ($filterStatus !== '') {

        $sql .= "
            AND ia.status = ?
        ";

        $params[] =
            $filterStatus;
    }


    /*
    |--------------------------------------------------------------------------
    | ORDER
    |--------------------------------------------------------------------------
    */

    $sql .= "
        ORDER BY
            ia.attendance_date DESC,
            u.full_name ASC
    ";


    $stmt =
        $pdo->prepare($sql);


    $stmt->execute(
        $params
    );


    $attendanceRecords =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {

    $error =
        'Unable to load attendance records.';
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
   ATTENDANCE PAGE
   
============================================================ */

.attendance-page {

    width: 100%;
}

.attendance-page * {

    box-sizing: border-box;
}


/* ============================================================
   PAGE HEADER
============================================================ */

.attendance-page .page-toolbar {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 25px;
}

.attendance-page .page-toolbar h1 {

    margin: 0;

    font-size: 28px;

    font-weight: 700;
}

.attendance-page .page-toolbar p {

    margin: 7px 0 0;

    color: #6b7280;

    font-size: 14px;
}


/* ============================================================
   CARD
============================================================ */

.attendance-page .card {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    margin-bottom: 24px;

    overflow: hidden;
}

.attendance-page .card-body {

    padding: 25px;
}


/* ============================================================
   SECTION HEADING
============================================================ */

.attendance-page .section-heading {

    margin-bottom: 22px;
}

.attendance-page .section-heading h2 {

    margin: 0;

    font-size: 20px;

    font-weight: 700;
}

.attendance-page .section-heading p {

    margin: 6px 0 0;

    color: #6b7280;

    font-size: 14px;
}


/* ============================================================
   FORM GRID
============================================================ */

.attendance-page .form-grid {

    display: grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(180px, 1fr)
        );

    gap: 18px;
}

.attendance-page .form-group {

    min-width: 0;
}


/* ============================================================
   LABELS
============================================================ */

.attendance-page .form-group label {

    display: block;

    margin-bottom: 7px;

    font-size: 14px;

    font-weight: 600;

    color: #111827;
}


/* ============================================================
   FORM CONTROLS
============================================================ */

.attendance-page .form-control {

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

.attendance-page textarea.form-control {

    min-height: 90px;

    resize: vertical;
}

.attendance-page .form-control:focus {

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

.attendance-page .button-area {

    margin-top: 20px;
}


/* ============================================================
   BUTTONS
============================================================ */

.attendance-page .btn {

    border: none;

    border-radius: 8px;

    padding: 11px 22px;

    font-size: 14px;

    font-weight: 600;

    cursor: pointer;

    text-decoration: none;

    display: inline-block;
}


/* PRIMARY */

.attendance-page .btn-primary {

    background: #2563eb;

    color: #ffffff;
}

.attendance-page .btn-primary:hover {

    background: #1d4ed8;
}


/* SECONDARY */

.attendance-page .btn-secondary {

    background: #6b7280;

    color: #ffffff;
}

.attendance-page .btn-secondary:hover {

    background: #4b5563;
}


/* ============================================================
   ALERTS
============================================================ */

.attendance-page .alert {

    border-radius: 9px;

    padding: 13px 16px;

    margin-bottom: 20px;

    font-size: 14px;
}

.attendance-page .alert-success {

    background: #ecfdf5;

    border: 1px solid #a7f3d0;

    color: #047857;
}

.attendance-page .alert-danger {

    background: #fef2f2;

    border: 1px solid #fecaca;

    color: #b91c1c;
}


/* ============================================================
   DIVIDER
============================================================ */

.attendance-page .divider {

    height: 1px;

    background: #e5e7eb;

    margin: 24px 0;
}


/* ============================================================
   TABLE WRAPPER
============================================================ */

.attendance-page .table-wrapper {

    overflow-x: auto;

    margin-top: 24px;
}


/* ============================================================
   TABLE
============================================================ */

.attendance-page table {

    width: 100%;

    border-collapse: collapse;

    min-width: 900px;
}

.attendance-page th {

    text-align: left;

    background: #f9fafb;

    border-bottom: 1px solid #e5e7eb;

    padding: 13px 14px;

    font-size: 13px;

    color: #374151;
}

.attendance-page td {

    padding: 14px;

    border-bottom: 1px solid #f0f0f0;

    font-size: 14px;

    color: #374151;

    vertical-align: middle;
}

.attendance-page tr:last-child td {

    border-bottom: none;
}


/* ============================================================
   STATUS BADGES
============================================================ */

.attendance-page .status-badge {

    display: inline-flex;

    align-items: center;

    padding: 5px 10px;

    border-radius: 999px;

    font-size: 12px;

    font-weight: 600;
}


.attendance-page .status-present {

    background: #dcfce7;

    color: #166534;
}


.attendance-page .status-absent {

    background: #fee2e2;

    color: #991b1b;
}


.attendance-page .status-late {

    background: #fef3c7;

    color: #92400e;
}


.attendance-page .status-leave {

    background: #dbeafe;

    color: #1e40af;
}


/* ============================================================
   UPDATE BOX
============================================================ */

.attendance-page .update-box {

    background: #fafafa;

    border: 1px solid #e5e7eb;

    border-radius: 12px;

    padding: 22px;

    margin-top: 20px;
}


/* ============================================================
   EMPTY STATE
============================================================ */

.attendance-page .empty-state {

    margin-top: 20px;

    border: 1px dashed #d1d5db;

    border-radius: 10px;

    padding: 30px;

    text-align: center;
}

.attendance-page .empty-state h3 {

    margin: 0;

    font-size: 17px;
}

.attendance-page .empty-state p {

    color: #6b7280;

    margin-bottom: 0;
}


/* ============================================================
   RESPONSIVE
============================================================ */

@media (max-width: 700px) {

    .attendance-page .card-body {

        padding: 18px;
    }

    .attendance-page .page-toolbar h1 {

        font-size: 23px;
    }

}

</style>


<div class="attendance-page">


    <!-- ======================================================
         PAGE HEADER
    ======================================================= -->

    <div class="page-toolbar">

        <div>

            <h1>
                Attendance Records
            </h1>

            <p>
                Record, update and view instructor attendance.
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
         RECORD ATTENDANCE
    ======================================================= -->

    <div class="card">

        <div class="card-body">

            <div class="section-heading">

                <h2>
                    Record Instructor Attendance
                </h2>

                <p>
                    Record attendance for an instructor on a specific date.
                </p>

            </div>


            <form method="POST">


                <div class="form-grid">


                    <!-- INSTRUCTOR -->

                    <div class="form-group">

                        <label>
                            Instructor
                        </label>

                        <select
                            name="instructor_id"
                            class="form-control"
                            required
                        >

                            <option
                                value=""
                                selected
                                disabled
                            >
                                Select Instructor
                            </option>


                            <?php foreach (
                                $instructors
                                as $instructor
                            ): ?>

                                <option
                                    value="<?= (int)$instructor['id'] ?>"
                                >

                                    <?= htmlspecialchars(
                                        $instructor['full_name']
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- DATE -->

                    <div class="form-group">

                        <label>
                            Attendance Date
                        </label>

                        <input
                            type="date"
                            name="attendance_date"
                            class="form-control"
                            value="<?= date('Y-m-d') ?>"
                            required
                        >

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

                            <option value="Present">
                                Present
                            </option>

                            <option value="Absent">
                                Absent
                            </option>

                            <option value="Late">
                                Late
                            </option>

                            <option value="On Leave">
                                On Leave
                            </option>

                        </select>

                    </div>


                </div>


                <!-- REMARKS -->

                <div
                    class="form-group"
                    style="margin-top:18px;"
                >

                    <label>
                        Remarks
                    </label>

                    <textarea
                        name="remarks"
                        class="form-control"
                        placeholder="Optional remarks"
                    ></textarea>

                </div>


                <div class="button-area">

                    <button
                        type="submit"
                        name="save_attendance"
                        class="btn btn-primary"
                    >
                        Save Attendance
                    </button>

                </div>


            </form>

        </div>

    </div>


    <!-- ======================================================
         FILTER ATTENDANCE
    ======================================================= -->

    <div class="card">

        <div class="card-body">

            <div class="section-heading">

                <h2>
                    View Attendance History
                </h2>

                <p>
                    Filter instructor attendance records by date,
                    instructor or status.
                </p>

            </div>


            <form method="GET">


                <div class="form-grid">


                    <!-- DATE -->

                    <div class="form-group">

                        <label>
                            Attendance Date
                        </label>

                        <input
                            type="date"
                            name="attendance_date"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $filterDate
                            ) ?>"
                        >

                    </div>


                    <!-- INSTRUCTOR -->

                    <div class="form-group">

                        <label>
                            Instructor
                        </label>

                        <select
                            name="instructor_id"
                            class="form-control"
                        >

                            <option value="">
                                All Instructors
                            </option>


                            <?php foreach (
                                $instructors
                                as $instructor
                            ): ?>

                                <option
                                    value="<?= (int)$instructor['id'] ?>"
                                    <?= $filterInstructor ===
                                        (int)$instructor['id']
                                        ? 'selected'
                                        : '' ?>
                                >

                                    <?= htmlspecialchars(
                                        $instructor['full_name']
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

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
                        >

                            <option value="">
                                All Statuses
                            </option>

                            <option
                                value="Present"
                                <?= $filterStatus === 'Present'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Present
                            </option>

                            <option
                                value="Absent"
                                <?= $filterStatus === 'Absent'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Absent
                            </option>

                            <option
                                value="Late"
                                <?= $filterStatus === 'Late'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Late
                            </option>

                            <option
                                value="On Leave"
                                <?= $filterStatus === 'On Leave'
                                    ? 'selected'
                                    : '' ?>
                            >
                                On Leave
                            </option>

                        </select>

                    </div>


                </div>


                <div class="button-area">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        View Records
                    </button>


                    <a
                        href="attendance_records.php"
                        class="btn btn-secondary"
                    >
                        Clear Filters
                    </a>

                </div>


            </form>

        </div>

    </div>


    <!-- ======================================================
         ATTENDANCE RECORDS
    ======================================================= -->

    <div class="card">

        <div class="card-body">

            <div class="section-heading">

                <h2>
                    Attendance Records
                </h2>

                <p>
                    Instructor attendance history recorded by
                    Non-Academic Staff.
                </p>

            </div>


            <?php if (empty($attendanceRecords)): ?>

                <div class="empty-state">

                    <h3>
                        No Attendance Records
                    </h3>

                    <p>
                        No instructor attendance records were found
                        for the selected filters.
                    </p>

                </div>

            <?php else: ?>


                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Instructor
                                </th>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Remarks
                                </th>

                                <th>
                                    Recorded By
                                </th>

                                <th>
                                    Recorded At
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach (
                            $attendanceRecords
                            as $record
                        ): ?>

                            <tr>


                                <!-- INSTRUCTOR -->

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $record['instructor_name']
                                        ) ?>

                                    </strong>

                                </td>


                                <!-- DATE -->

                                <td>

                                    <?= htmlspecialchars(
                                        date(
                                            'd M Y',
                                            strtotime(
                                                $record[
                                                    'attendance_date'
                                                ]
                                            )
                                        )
                                    ) ?>

                                </td>


                                <!-- STATUS -->

                                <td>


                                    <?php if (
                                        $record['status'] ===
                                        'Present'
                                    ): ?>

                                        <span
                                            class="
                                                status-badge
                                                status-present
                                            "
                                        >
                                            Present
                                        </span>


                                    <?php elseif (
                                        $record['status'] ===
                                        'Absent'
                                    ): ?>

                                        <span
                                            class="
                                                status-badge
                                                status-absent
                                            "
                                        >
                                            Absent
                                        </span>


                                    <?php elseif (
                                        $record['status'] ===
                                        'Late'
                                    ): ?>

                                        <span
                                            class="
                                                status-badge
                                                status-late
                                            "
                                        >
                                            Late
                                        </span>


                                    <?php else: ?>

                                        <span
                                            class="
                                                status-badge
                                                status-leave
                                            "
                                        >
                                            On Leave
                                        </span>

                                    <?php endif; ?>


                                </td>


                                <!-- REMARKS -->

                                <td>

                                    <?php if (
                                        trim(
                                            $record['remarks'] ?? ''
                                        ) !== ''
                                    ): ?>

                                        <?= nl2br(
                                            htmlspecialchars(
                                                $record['remarks']
                                            )
                                        ) ?>

                                    <?php else: ?>

                                        <span
                                            style="color:#9ca3af;"
                                        >
                                            No remarks
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- RECORDED BY -->

                                <td>

                                    <?= htmlspecialchars(
                                        $record[
                                            'recorded_by_name'
                                        ] ?? 'System'
                                    ) ?>

                                </td>


                                <!-- CREATED -->

                                <td>

                                    <?= htmlspecialchars(
                                        date(
                                            'd M Y h:i A',
                                            strtotime(
                                                $record['created_at']
                                            )
                                        )
                                    ) ?>

                                </td>


                                <!-- UPDATE -->

                                <td>

                                    <button
                                        type="button"
                                        class="btn btn-primary"
                                        onclick="
                                            openAttendanceUpdate(
                                                <?= (int)$record['id'] ?>,
                                                '<?= htmlspecialchars(
                                                    $record['status'],
                                                    ENT_QUOTES
                                                ) ?>',
                                                '<?= htmlspecialchars(
                                                    $record['remarks'] ?? '',
                                                    ENT_QUOTES
                                                ) ?>'
                                            );
                                        "
                                    >
                                        Update
                                    </button>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>

                    </table>

                </div>


            <?php endif; ?>


        </div>

    </div>


    <!-- ======================================================
         UPDATE ATTENDANCE
    ======================================================= -->

    <div
        class="card"
        id="updateAttendanceCard"
        style="display:none;"
    >

        <div class="card-body">

            <div class="section-heading">

                <h2>
                    Update Attendance
                </h2>

                <p>
                    Change the attendance status or remarks.
                </p>

            </div>


            <form method="POST">


                <input
                    type="hidden"
                    name="attendance_id"
                    id="updateAttendanceId"
                >


                <div class="form-grid">


                    <!-- STATUS -->

                    <div class="form-group">

                        <label>
                            Status
                        </label>

                        <select
                            name="update_status"
                            id="updateAttendanceStatus"
                            class="form-control"
                            required
                        >

                            <option value="Present">
                                Present
                            </option>

                            <option value="Absent">
                                Absent
                            </option>

                            <option value="Late">
                                Late
                            </option>

                            <option value="On Leave">
                                On Leave
                            </option>

                        </select>

                    </div>


                    <!-- REMARKS -->

                    <div
                        class="form-group"
                        style="grid-column:1/-1;"
                    >

                        <label>
                            Remarks
                        </label>

                        <textarea
                            name="update_remarks"
                            id="updateAttendanceRemarks"
                            class="form-control"
                            placeholder="Optional remarks"
                        ></textarea>

                    </div>


                </div>


                <div class="button-area">

                    <button
                        type="submit"
                        name="update_attendance"
                        class="btn btn-primary"
                    >
                        Update Attendance
                    </button>


                    <button
                        type="button"
                        class="btn btn-secondary"
                        onclick="
                            closeAttendanceUpdate();
                        "
                    >
                        Cancel
                    </button>

                </div>


            </form>

        </div>

    </div>


</div>


<script>

/*
|--------------------------------------------------------------------------
| OPEN UPDATE FORM
|--------------------------------------------------------------------------
*/

function openAttendanceUpdate(
    id,
    status,
    remarks
) {

    document.getElementById(
        'updateAttendanceCard'
    ).style.display = 'block';


    document.getElementById(
        'updateAttendanceId'
    ).value = id;


    document.getElementById(
        'updateAttendanceStatus'
    ).value = status;


    document.getElementById(
        'updateAttendanceRemarks'
    ).value = remarks || '';


    document.getElementById(
        'updateAttendanceCard'
    ).scrollIntoView({
        behavior: 'smooth',
        block: 'start'
    });
}


/*
|--------------------------------------------------------------------------
| CLOSE UPDATE FORM
|--------------------------------------------------------------------------
*/

function closeAttendanceUpdate()
{

    document.getElementById(
        'updateAttendanceCard'
    ).style.display = 'none';

}

</script>


<?php

include __DIR__ . '/../includes/footer.php';

?>