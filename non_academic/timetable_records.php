<?php
/**
 * ============================================================
 * NON-ACADEMIC STAFF
 * TIMETABLE MANAGEMENT
 * Smart Instructor Coordination and Workload Management System
 * ============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';

checkRole(ROLE_NON_ACADEMIC);

$pageTitle = "Timetable Management";

/*
|--------------------------------------------------------------------------
| SESSION MESSAGES
|--------------------------------------------------------------------------
*/
$successMessage = $_SESSION['success'] ?? '';
$errorMessage   = $_SESSION['error'] ?? '';

unset($_SESSION['success'], $_SESSION['error']);


/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/
function timetable_redirect(): void
{
    header(
        'Location: ' .
        app_url('non_academic/timetable_management.php')
    );
    exit;
}


/*
|--------------------------------------------------------------------------
| YEAR CONVERSION
|--------------------------------------------------------------------------
*/
function normalize_year(string $year): string
{
    $map = [
        '1st Year' => '1',
        '2nd Year' => '2',
        '3rd Year' => '3',
        '4th Year' => '4'
    ];

    return $map[$year] ?? $year;
}


/*
|--------------------------------------------------------------------------
| ADD TIMETABLE
|--------------------------------------------------------------------------
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['add_timetable'])
) {

    $subjectName  = trim($_POST['subject_name'] ?? '');
    $course       = trim($_POST['course'] ?? '');
    $dayName      = trim($_POST['day_name'] ?? '');
    $startTime    = trim($_POST['start_time'] ?? '');
    $endTime      = trim($_POST['end_time'] ?? '');
    $room         = trim($_POST['room'] ?? '');
    $semester     = trim($_POST['semester'] ?? '');
    $academicYear = normalize_year(
        trim($_POST['academic_year'] ?? '')
    );


    if (
        $subjectName === '' ||
        $course === '' ||
        $dayName === '' ||
        $startTime === '' ||
        $endTime === '' ||
        $room === '' ||
        $semester === '' ||
        $academicYear === ''
    ) {

        $_SESSION['error'] =
            'Please fill in all timetable fields.';

        timetable_redirect();
    }


    if ($startTime >= $endTime) {

        $_SESSION['error'] =
            'End time must be later than start time.';

        timetable_redirect();
    }


    try {

        /*
        |--------------------------------------------------------------------------
        | CHECK DUPLICATE
        |--------------------------------------------------------------------------
        */

        $check = $pdo->prepare("
            SELECT id
            FROM timetables
            WHERE subject_name = ?
              AND course = ?
              AND day_name = ?
              AND start_time = ?
              AND end_time = ?
              AND room = ?
              AND semester = ?
              AND academic_year = ?
            LIMIT 1
        ");

        $check->execute([
            $subjectName,
            $course,
            $dayName,
            $startTime,
            $endTime,
            $room,
            $semester,
            $academicYear
        ]);


        if ($check->fetch(PDO::FETCH_ASSOC)) {

            $_SESSION['error'] =
                'This timetable record already exists.';

            timetable_redirect();
        }


        /*
        |--------------------------------------------------------------------------
        | INSERT
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            INSERT INTO timetables
            (
                subject_name,
                course,
                day_name,
                start_time,
                end_time,
                room,
                semester,
                academic_year
            )
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $subjectName,
            $course,
            $dayName,
            $startTime,
            $endTime,
            $room,
            $semester,
            $academicYear
        ]);


        $_SESSION['success'] =
            'Timetable slot added successfully.';

    } catch (PDOException $e) {

        $_SESSION['error'] =
            'Unable to add timetable slot.';
    }


    timetable_redirect();
}


/*
|--------------------------------------------------------------------------
| DELETE TIMETABLE
|--------------------------------------------------------------------------
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_timetable'])
) {

    $subjectName  = trim($_POST['delete_subject_name'] ?? '');
    $course       = trim($_POST['delete_course'] ?? '');
    $dayName      = trim($_POST['delete_day_name'] ?? '');
    $startTime    = trim($_POST['delete_start_time'] ?? '');
    $endTime      = trim($_POST['delete_end_time'] ?? '');
    $room         = trim($_POST['delete_room'] ?? '');
    $semester     = trim($_POST['delete_semester'] ?? '');
    $academicYear = normalize_year(
        trim($_POST['delete_academic_year'] ?? '')
    );


    if (
        $subjectName === '' ||
        $course === '' ||
        $dayName === '' ||
        $startTime === '' ||
        $endTime === '' ||
        $room === '' ||
        $semester === '' ||
        $academicYear === ''
    ) {

        $_SESSION['error'] =
            'Please provide all timetable details to delete.';

        timetable_redirect();
    }


    try {

        /*
        |--------------------------------------------------------------------------
        | FIND RECORD FIRST
        |--------------------------------------------------------------------------
        */

        $find = $pdo->prepare("
            SELECT id
            FROM timetables
            WHERE subject_name = ?
              AND course = ?
              AND day_name = ?
              AND start_time = ?
              AND end_time = ?
              AND room = ?
              AND semester = ?
              AND academic_year = ?
            LIMIT 1
        ");

        $find->execute([
            $subjectName,
            $course,
            $dayName,
            $startTime,
            $endTime,
            $room,
            $semester,
            $academicYear
        ]);

        $record = $find->fetch(PDO::FETCH_ASSOC);


        if (!$record) {

            $_SESSION['error'] =
                'No matching timetable slot was found.';

            timetable_redirect();
        }


        /*
        |--------------------------------------------------------------------------
        | DELETE
        |--------------------------------------------------------------------------
        */

        $delete = $pdo->prepare("
            DELETE FROM timetables
            WHERE id = ?
            LIMIT 1
        ");

        $delete->execute([
            $record['id']
        ]);


        if ($delete->rowCount() > 0) {

            $_SESSION['success'] =
                'Timetable slot deleted successfully.';

        } else {

            $_SESSION['error'] =
                'Unable to delete timetable slot.';
        }

    } catch (PDOException $e) {

        /*
        |--------------------------------------------------------------------------
        | FOREIGN KEY / OTHER DATABASE ERROR
        |--------------------------------------------------------------------------
        */

        if ((int)$e->errorInfo[1] === 1451) {

            $_SESSION['error'] =
                'This timetable slot cannot be deleted because it is already being used by another record.';

        } else {

            $_SESSION['error'] =
                'Unable to delete timetable slot.';
        }
    }


    timetable_redirect();
}


/*
|--------------------------------------------------------------------------
| UPDATE TIMETABLE
|--------------------------------------------------------------------------
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_timetable'])
) {

    /*
    |--------------------------------------------------------------------------
    | OLD VALUES
    |--------------------------------------------------------------------------
    */

    $oldSubject = trim($_POST['old_subject'] ?? '');
    $oldCourse  = trim($_POST['old_course'] ?? '');
    $oldDay     = trim($_POST['old_day'] ?? '');
    $oldStart   = trim($_POST['old_start'] ?? '');
    $oldEnd     = trim($_POST['old_end'] ?? '');
    $oldRoom    = trim($_POST['old_room'] ?? '');
    $oldSemester = trim($_POST['old_semester'] ?? '');
    $oldYear    = normalize_year(
        trim($_POST['old_year'] ?? '')
    );


    /*
    |--------------------------------------------------------------------------
    | NEW VALUES
    |--------------------------------------------------------------------------
    */

    $newSubject = trim($_POST['new_subject'] ?? '');
    $newCourse  = trim($_POST['new_course'] ?? '');
    $newDay     = trim($_POST['new_day'] ?? '');
    $newStart   = trim($_POST['new_start'] ?? '');
    $newEnd     = trim($_POST['new_end'] ?? '');
    $newRoom    = trim($_POST['new_room'] ?? '');
    $newSemester = trim($_POST['new_semester'] ?? '');
    $newYear    = normalize_year(
        trim($_POST['new_year'] ?? '')
    );


    /*
    |--------------------------------------------------------------------------
    | OLD VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $oldSubject === '' ||
        $oldCourse === '' ||
        $oldDay === '' ||
        $oldStart === '' ||
        $oldEnd === '' ||
        $oldRoom === '' ||
        $oldSemester === '' ||
        $oldYear === ''
    ) {

        $_SESSION['error'] =
            'Please complete all Old Timetable Slot fields.';

        timetable_redirect();
    }


    /*
    |--------------------------------------------------------------------------
    | NEW VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $newSubject === '' ||
        $newCourse === '' ||
        $newDay === '' ||
        $newStart === '' ||
        $newEnd === '' ||
        $newRoom === '' ||
        $newSemester === '' ||
        $newYear === ''
    ) {

        $_SESSION['error'] =
            'Please complete all New Timetable Slot fields.';

        timetable_redirect();
    }


    if ($newStart >= $newEnd) {

        $_SESSION['error'] =
            'New timetable end time must be later than start time.';

        timetable_redirect();
    }


    try {

        /*
        |--------------------------------------------------------------------------
        | FIND OLD RECORD
        |--------------------------------------------------------------------------
        */

        $findOld = $pdo->prepare("
            SELECT id
            FROM timetables
            WHERE subject_name = ?
              AND course = ?
              AND day_name = ?
              AND start_time = ?
              AND end_time = ?
              AND room = ?
              AND semester = ?
              AND academic_year = ?
            LIMIT 1
        ");

        $findOld->execute([
            $oldSubject,
            $oldCourse,
            $oldDay,
            $oldStart,
            $oldEnd,
            $oldRoom,
            $oldSemester,
            $oldYear
        ]);

        $oldRecord =
            $findOld->fetch(PDO::FETCH_ASSOC);


        if (!$oldRecord) {

            $_SESSION['error'] =
                'The Old Timetable Slot could not be found.';

            timetable_redirect();
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK NEW RECORD
        |--------------------------------------------------------------------------
        */

        $checkNew = $pdo->prepare("
            SELECT id
            FROM timetables
            WHERE subject_name = ?
              AND course = ?
              AND day_name = ?
              AND start_time = ?
              AND end_time = ?
              AND room = ?
              AND semester = ?
              AND academic_year = ?
              AND id <> ?
            LIMIT 1
        ");

        $checkNew->execute([
            $newSubject,
            $newCourse,
            $newDay,
            $newStart,
            $newEnd,
            $newRoom,
            $newSemester,
            $newYear,
            $oldRecord['id']
        ]);


        if ($checkNew->fetch(PDO::FETCH_ASSOC)) {

            $_SESSION['error'] =
                'The New Timetable Slot already exists.';

            timetable_redirect();
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE
        |--------------------------------------------------------------------------
        */

        $update = $pdo->prepare("
            UPDATE timetables
            SET
                subject_name = ?,
                course = ?,
                day_name = ?,
                start_time = ?,
                end_time = ?,
                room = ?,
                semester = ?,
                academic_year = ?
            WHERE id = ?
        ");

        $update->execute([
            $newSubject,
            $newCourse,
            $newDay,
            $newStart,
            $newEnd,
            $newRoom,
            $newSemester,
            $newYear,
            $oldRecord['id']
        ]);


        $_SESSION['success'] =
            'Timetable slot updated successfully.';

    } catch (PDOException $e) {

        $_SESSION['error'] =
            'Unable to update timetable slot.';
    }


    timetable_redirect();
}


/*
|--------------------------------------------------------------------------
| VIEW TIMETABLE
|--------------------------------------------------------------------------
*/

$viewRows = [];

$viewSemester = '';
$viewYear = '';

$viewSubmitted = false;


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['view_timetable'])
) {

    $viewSubmitted = true;

    $viewSemester =
        trim($_POST['view_semester'] ?? '');

    $viewYear =
        normalize_year(
            trim($_POST['view_academic_year'] ?? '')
        );


    if (
        $viewSemester === '' ||
        $viewYear === ''
    ) {

        $errorMessage =
            'Please select semester and year.';

    } else {

        try {

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    subject_name,
                    course,
                    day_name,
                    start_time,
                    end_time,
                    room,
                    semester,
                    academic_year
                FROM timetables
                WHERE semester = ?
                  AND academic_year = ?
                ORDER BY
                    FIELD(
                        day_name,
                        'Monday',
                        'Tuesday',
                        'Wednesday',
                        'Thursday',
                        'Friday',
                        'Saturday',
                        'Sunday'
                    ),
                    start_time ASC
            ");

            $stmt->execute([
                $viewSemester,
                $viewYear
            ]);

            $viewRows =
                $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {

            $errorMessage =
                'Unable to load timetable records.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| PAGE CONTENT
|--------------------------------------------------------------------------
|
| IMPORTANT:
| No sidebar.
| No <html>.
| No <body>.
| No separate header.
|
| header.php already provides the common dashboard layout.
|
*/

include __DIR__ . '/../includes/header.php';

?>


<style>

.timetable-page {
    width: 100%;
}

.timetable-page * {
    box-sizing: border-box;
}

.timetable-page .page-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.timetable-page .page-toolbar h1 {
    margin: 0;
    font-size: 28px;
    font-weight: 700;
}

.timetable-page .page-toolbar p {
    margin: 7px 0 0;
    color: #6b7280;
    font-size: 14px;
}

.timetable-page .card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    margin-bottom: 24px;
    overflow: hidden;
}

.timetable-page .card-body {
    padding: 25px;
}

.timetable-page .section-heading {
    margin-bottom: 22px;
}

.timetable-page .section-heading h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
}

.timetable-page .section-heading p {
    margin: 6px 0 0;
    color: #6b7280;
    font-size: 14px;
}

.timetable-page .form-grid {
    display: grid;
    grid-template-columns: repeat(
        auto-fit,
        minmax(180px, 1fr)
    );
    gap: 18px;
}

.timetable-page .form-group {
    min-width: 0;
}

.timetable-page .form-group label {
    display: block;
    margin-bottom: 7px;
    font-size: 14px;
    font-weight: 600;
    color: #111827;
}

.timetable-page .form-control {
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

.timetable-page .form-control:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(
        37,
        99,
        235,
        0.10
    );
}

.timetable-page .button-area {
    margin-top: 20px;
}

.timetable-page .btn {
    border: none;
    border-radius: 8px;
    padding: 11px 22px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

.timetable-page .btn-primary {
    background: #2563eb;
    color: #ffffff;
}

.timetable-page .btn-primary:hover {
    background: #1d4ed8;
}

.timetable-page .btn-danger {
    background: #dc2626;
    color: #ffffff;
}

.timetable-page .btn-danger:hover {
    background: #b91c1c;
}

.timetable-page .old-box,
.timetable-page .new-box {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 22px;
}

.timetable-page .old-box {
    background: #fafafa;
}

.timetable-page .new-box {
    background: #ffffff;
}

.timetable-page .box-title {
    margin: 0 0 20px;
    font-size: 17px;
    font-weight: 700;
    color: #111827;
}

.timetable-page .divider {
    height: 1px;
    background: #e5e7eb;
    margin: 24px 0;
}

.timetable-page .alert {
    border-radius: 9px;
    padding: 13px 16px;
    margin-bottom: 20px;
    font-size: 14px;
}

.timetable-page .alert-success {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #047857;
}

.timetable-page .alert-danger {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #b91c1c;
}

.timetable-page .table-wrapper {
    overflow-x: auto;
    margin-top: 24px;
}

.timetable-page table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

.timetable-page th {
    text-align: left;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    padding: 13px 14px;
    font-size: 13px;
    color: #374151;
}

.timetable-page td {
    padding: 14px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
    color: #374151;
}

.timetable-page tr:last-child td {
    border-bottom: none;
}

.timetable-page .empty-state {
    margin-top: 20px;
    border: 1px dashed #d1d5db;
    border-radius: 10px;
    padding: 30px;
    text-align: center;
}

.timetable-page .empty-state h3 {
    margin: 0;
    font-size: 17px;
}

.timetable-page .empty-state p {
    color: #6b7280;
    margin-bottom: 0;
}

@media (max-width: 700px) {

    .timetable-page .card-body {
        padding: 18px;
    }

    .timetable-page .page-toolbar h1 {
        font-size: 23px;
    }

}

</style>


<div class="timetable-page">


    <!-- ======================================================
         PAGE HEADER
    ======================================================= -->

    <div class="page-toolbar">

        <div>

            <h1>
                Timetable Management
            </h1>

            <p>
                Add, update, delete and view timetable records.
            </p>

        </div>

    </div>


    <!-- ======================================================
         MESSAGES
    ======================================================= -->

    <?php if ($successMessage !== ''): ?>

        <div class="alert alert-success">

            <?= htmlspecialchars($successMessage) ?>

        </div>

    <?php endif; ?>


    <?php if ($errorMessage !== ''): ?>

        <div class="alert alert-danger">

            <?= htmlspecialchars($errorMessage) ?>

        </div>

    <?php endif; ?>


    <!-- ======================================================
         ADD
    ======================================================= -->

    <div class="card">

        <div class="card-body">

            <div class="section-heading">

                <h2>
                    Add Timetable Slot
                </h2>

                <p>
                    Create a new timetable record.
                </p>

            </div>


            <form method="POST">

                <div class="form-grid">

                    <div class="form-group">

                        <label>
                            Subject
                        </label>

                        <input
                            type="text"
                            name="subject_name"
                            class="form-control"
                            placeholder="Subject"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            IS/CS
                        </label>

                        <select
                            name="course"
                            class="form-control"
                            required
                        >

                            <option
                                value=""
                                selected
                                disabled
                            >
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

                        <select
                            name="day_name"
                            class="form-control"
                            required
                        >

                            <option
                                value=""
                                selected
                                disabled
                            >
                                Select Day
                            </option>

                            <option value="Monday">
                                Monday
                            </option>

                            <option value="Tuesday">
                                Tuesday
                            </option>

                            <option value="Wednesday">
                                Wednesday
                            </option>

                            <option value="Thursday">
                                Thursday
                            </option>

                            <option value="Friday">
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
                            class="form-control"
                            required
                            step="3600"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            End Time
                        </label>

                        <input
                            type="time"
                            name="end_time"
                            class="form-control"
                            required
                            step="3600"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Room / Lab
                        </label>

                        <input
                            type="text"
                            name="room"
                            class="form-control"
                            placeholder="Room / Lab"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Semester
                        </label>

                        <select
                            name="semester"
                            class="form-control"
                            required
                        >

                            <option
                                value=""
                                selected
                                disabled
                            >
                                Semester
                            </option>

                            <option value="Semester 1">
                                Semester 1
                            </option>

                            <option value="Semester 2">
                                Semester 2
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>
                            Year
                        </label>

                        <select
                            name="academic_year"
                            class="form-control"
                            required
                        >

                            <option
                                value=""
                                selected
                                disabled
                            >
                                Select Year
                            </option>

                            <option value="1">
                                1st Year
                            </option>

                            <option value="2">
                                2nd Year
                            </option>

                            <option value="3">
                                3rd Year
                            </option>

                            <option value="4">
                                4th Year
                            </option>

                        </select>

                    </div>

                </div>


                <div class="button-area">

                    <button
                        type="submit"
                        name="add_timetable"
                        class="btn btn-primary"
                    >
                        Save
                    </button>

                </div>

            </form>

        </div>

    </div>


    <!-- ======================================================
         DELETE
    ======================================================= -->

    <div class="card">

        <div class="card-body">

            <div class="section-heading">

                <h2>
                    Delete Timetable Slot
                </h2>

                <p>
                    Enter the exact timetable details to delete.
                </p>

            </div>


            <form
                method="POST"
                onsubmit="
                    return confirm(
                        'Are you sure you want to delete this timetable slot?'
                    );
                "
            >

                <div class="form-grid">

                    <div class="form-group">

                        <label>
                            Subject
                        </label>

                        <input
                            type="text"
                            name="delete_subject_name"
                            class="form-control"
                            placeholder="Subject"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            IS/CS
                        </label>

                        <select
                            name="delete_course"
                            class="form-control"
                            required
                        >

                            <option
                                value=""
                                selected
                                disabled
                            >
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

                        <select
                            name="delete_day_name"
                            class="form-control"
                            required
                        >

                            <option
                                value=""
                                selected
                                disabled
                            >
                                Select Day
                            </option>

                            <option value="Monday">
                                Monday
                            </option>

                            <option value="Tuesday">
                                Tuesday
                            </option>

                            <option value="Wednesday">
                                Wednesday
                            </option>

                            <option value="Thursday">
                                Thursday
                            </option>

                            <option value="Friday">
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
                            name="delete_start_time"
                            class="form-control"
                            required
                            step="3600"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            End Time
                        </label>

                        <input
                            type="time"
                            name="delete_end_time"
                            class="form-control"
                            required
                            step="3600"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Room / Lab
                        </label>

                        <input
                            type="text"
                            name="delete_room"
                            class="form-control"
                            placeholder="Room / Lab"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Semester
                        </label>

                        <select
                            name="delete_semester"
                            class="form-control"
                            required
                        >

                            <option
                                value=""
                                selected
                                disabled
                            >
                                Semester
                            </option>

                            <option value="Semester 1">
                                Semester 1
                            </option>

                            <option value="Semester 2">
                                Semester 2
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>
                            Year
                        </label>

                        <select
                            name="delete_academic_year"
                            class="form-control"
                            required
                        >

                            <option
                                value=""
                                selected
                                disabled
                            >
                                Select Year
                            </option>

                            <option value="1">
                                1st Year
                            </option>

                            <option value="2">
                                2nd Year
                            </option>

                            <option value="3">
                                3rd Year
                            </option>

                            <option value="4">
                                4th Year
                            </option>

                        </select>

                    </div>

                </div>


                <div class="button-area">

                    <button
                        type="submit"
                        name="delete_timetable"
                        class="btn btn-danger"
                    >
                        Delete
                    </button>

                </div>

            </form>

        </div>

    </div>


    <!-- ======================================================
         UPDATE
    ======================================================= -->

    <div class="card">

        <div class="card-body">

            <div class="section-heading">

                <h2>
                    Update Timetable Slot
                </h2>

                <p>
                    Enter the old record and then enter the new record.
                </p>

            </div>


            <form method="POST">


                <!-- OLD -->

                <div class="old-box">

                    <h3 class="box-title">
                        Old Timetable Slot
                    </h3>


                    <div class="form-grid">

                        <div class="form-group">

                            <label>
                                Subject
                            </label>

                            <input
                                type="text"
                                name="old_subject"
                                class="form-control"
                                placeholder="Subject"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                IS/CS
                            </label>

                            <select
                                name="old_course"
                                class="form-control"
                                required
                            >

                                <option
                                    value=""
                                    selected
                                    disabled
                                >
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

                            <select
                                name="old_day"
                                class="form-control"
                                required
                            >

                                <option
                                    value=""
                                    selected
                                    disabled
                                >
                                    Select Day
                                </option>

                                <option value="Monday">
                                    Monday
                                </option>

                                <option value="Tuesday">
                                    Tuesday
                                </option>

                                <option value="Wednesday">
                                    Wednesday
                                </option>

                                <option value="Thursday">
                                    Thursday
                                </option>

                                <option value="Friday">
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
                                name="old_start"
                                class="form-control"
                                required
                                step="3600"
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                End Time
                            </label>

                            <input
                                type="time"
                                name="old_end"
                                class="form-control"
                                required
                                step="3600"
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Room / Lab
                            </label>

                            <input
                                type="text"
                                name="old_room"
                                class="form-control"
                                placeholder="Room / Lab"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Semester
                            </label>

                            <select
                                name="old_semester"
                                class="form-control"
                                required
                            >

                                <option
                                    value=""
                                    selected
                                    disabled
                                >
                                    Semester
                                </option>

                                <option value="Semester 1">
                                    Semester 1
                                </option>

                                <option value="Semester 2">
                                    Semester 2
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>
                                Year
                            </label>

                            <select
                                name="old_year"
                                class="form-control"
                                required
                            >

                                <option
                                    value=""
                                    selected
                                    disabled
                                >
                                    Select Year
                                </option>

                                <option value="1">
                                    1st Year
                                </option>

                                <option value="2">
                                    2nd Year
                                </option>

                                <option value="3">
                                    3rd Year
                                </option>

                                <option value="4">
                                    4th Year
                                </option>

                            </select>

                        </div>

                    </div>

                </div>


                <div class="divider"></div>


                <!-- NEW -->

                <div class="new-box">

                    <h3 class="box-title">
                        New Timetable Slot
                    </h3>


                    <div class="form-grid">

                        <div class="form-group">

                            <label>
                                Subject
                            </label>

                            <input
                                type="text"
                                name="new_subject"
                                class="form-control"
                                placeholder="Subject"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                IS/CS
                            </label>

                            <select
                                name="new_course"
                                class="form-control"
                                required
                            >

                                <option
                                    value=""
                                    selected
                                    disabled
                                >
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

                            <select
                                name="new_day"
                                class="form-control"
                                required
                            >

                                <option
                                    value=""
                                    selected
                                    disabled
                                >
                                    Select Day
                                </option>

                                <option value="Monday">
                                    Monday
                                </option>

                                <option value="Tuesday">
                                    Tuesday
                                </option>

                                <option value="Wednesday">
                                    Wednesday
                                </option>

                                <option value="Thursday">
                                    Thursday
                                </option>

                                <option value="Friday">
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
                                name="new_start"
                                class="form-control"
                                required
                                step="3600"
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                End Time
                            </label>

                            <input
                                type="time"
                                name="new_end"
                                class="form-control"
                                required
                                step="3600"
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Room / Lab
                            </label>

                            <input
                                type="text"
                                name="new_room"
                                class="form-control"
                                placeholder="Room / Lab"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Semester
                            </label>

                            <select
                                name="new_semester"
                                class="form-control"
                                required
                            >

                                <option
                                    value=""
                                    selected
                                    disabled
                                >
                                    Semester
                                </option>

                                <option value="Semester 1">
                                    Semester 1
                                </option>

                                <option value="Semester 2">
                                    Semester 2
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>
                                Year
                            </label>

                            <select
                                name="new_year"
                                class="form-control"
                                required
                            >

                                <option
                                    value=""
                                    selected
                                    disabled
                                >
                                    Select Year
                                </option>

                                <option value="1">
                                    1st Year
                                </option>

                                <option value="2">
                                    2nd Year
                                </option>

                                <option value="3">
                                    3rd Year
                                </option>

                                <option value="4">
                                    4th Year
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="button-area">

                        <button
                            type="submit"
                            name="update_timetable"
                            class="btn btn-primary"
                        >
                            Update
                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <!-- ======================================================
         VIEW
    ======================================================= -->

    <div class="card">

        <div class="card-body">

            <div class="section-heading">

                <h2>
                    View Timetable Slot
                </h2>

                <p>
                    Select a semester and year to view records.
                </p>

            </div>


            <form method="POST">

                <div class="form-grid">

                    <div class="form-group">

                        <label>
                            Semester
                        </label>

                        <select
                            name="view_semester"
                            class="form-control"
                            required
                        >

                            <option
                                value=""
                                disabled
                                <?= $viewSemester === ''
                                    ? 'selected'
                                    : '' ?>
                            >
                                Semester
                            </option>

                            <option
                                value="Semester 1"
                                <?= $viewSemester === 'Semester 1'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Semester 1
                            </option>

                            <option
                                value="Semester 2"
                                <?= $viewSemester === 'Semester 2'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Semester 2
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>
                            Year
                        </label>

                        <select
                            name="view_academic_year"
                            class="form-control"
                            required
                        >

                            <option
                                value=""
                                disabled
                                <?= $viewYear === ''
                                    ? 'selected'
                                    : '' ?>
                            >
                                Select Year
                            </option>

                            <option
                                value="1"
                                <?= $viewYear === '1'
                                    ? 'selected'
                                    : '' ?>
                            >
                                1st Year
                            </option>

                            <option
                                value="2"
                                <?= $viewYear === '2'
                                    ? 'selected'
                                    : '' ?>
                            >
                                2nd Year
                            </option>

                            <option
                                value="3"
                                <?= $viewYear === '3'
                                    ? 'selected'
                                    : '' ?>
                            >
                                3rd Year
                            </option>

                            <option
                                value="4"
                                <?= $viewYear === '4'
                                    ? 'selected'
                                    : '' ?>
                            >
                                4th Year
                            </option>

                        </select>

                    </div>

                </div>


                <div class="button-area">

                    <button
                        type="submit"
                        name="view_timetable"
                        class="btn btn-primary"
                    >
                        View
                    </button>

                </div>

            </form>


            <?php if ($viewSubmitted): ?>

                <div class="divider"></div>


                <h3>

                    <?= htmlspecialchars($viewSemester) ?>

                    -

                    <?= htmlspecialchars(
                        [
                            '1' => '1st Year',
                            '2' => '2nd Year',
                            '3' => '3rd Year',
                            '4' => '4th Year'
                        ][$viewYear] ?? $viewYear
                    ) ?>

                </h3>


                <?php if (empty($viewRows)): ?>

                    <div class="empty-state">

                        <h3>
                            No timetable records found
                        </h3>

                        <p>
                            No records were found for the
                            selected semester and year.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="table-wrapper">

                        <table>

                            <thead>

                                <tr>

                                    <th>
                                        Subject
                                    </th>

                                    <th>
                                        Course
                                    </th>

                                    <th>
                                        Day
                                    </th>

                                    <th>
                                        Start Time
                                    </th>

                                    <th>
                                        End Time
                                    </th>

                                    <th>
                                        Room / Lab
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                            <?php foreach ($viewRows as $row): ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $row['subject_name']
                                            ) ?>
                                        </strong>

                                    </td>


                                    <td>
                                        <?= htmlspecialchars(
                                            $row['course']
                                        ) ?>
                                    </td>


                                    <td>
                                        <?= htmlspecialchars(
                                            $row['day_name']
                                        ) ?>
                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            date(
                                                'h:i A',
                                                strtotime(
                                                    $row['start_time']
                                                )
                                            )
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            date(
                                                'h:i A',
                                                strtotime(
                                                    $row['end_time']
                                                )
                                            )
                                        ) ?>

                                    </td>


                                    <td>
                                        <?= htmlspecialchars(
                                            $row['room']
                                        ) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            <?php endif; ?>

        </div>

    </div>

</div>


<?php

include __DIR__ . '/../includes/footer.php';

?>