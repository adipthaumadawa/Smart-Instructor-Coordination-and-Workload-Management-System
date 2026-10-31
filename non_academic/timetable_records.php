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

/* ============================================================
   PERSISTENT ACTIVE SEMESTER SETTING
============================================================ */
$pdo->exec("
    CREATE TABLE IF NOT EXISTS timetable_settings (
        id INT PRIMARY KEY,
        active_semester VARCHAR(20) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$pdo->exec("
    INSERT IGNORE INTO timetable_settings (id, active_semester)
    VALUES (1, NULL)
");

$activeSemesterStmt = $pdo->query("
    SELECT active_semester
    FROM timetable_settings
    WHERE id = 1
");
$activeSemester = $activeSemesterStmt->fetchColumn() ?: '';

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
| CHANGE ACTIVE SEMESTER
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_semester'])) {
    $newSemester = trim($_POST['selected_semester'] ?? '');
    $confirmed = ($_POST['confirm_delete'] ?? '') === '1';

    if (!in_array($newSemester, ['Semester 1', 'Semester 2'], true)) {
        $_SESSION['error'] = 'Please select a valid semester.';
        timetable_redirect();
    }

    try {
        $pdo->beginTransaction();

        $lock = $pdo->query("
            SELECT active_semester
            FROM timetable_settings
            WHERE id = 1
            FOR UPDATE
        ");
        $previousSemester = $lock->fetchColumn() ?: '';

        if ($previousSemester !== '' && $previousSemester !== $newSemester) {
            if (!$confirmed) {
                $pdo->rollBack();
                $_SESSION['error'] = 'Semester change cancelled. Existing timetable records were not deleted.';
                timetable_redirect();
            }

            $deletePrevious = $pdo->prepare("DELETE FROM timetables WHERE semester = ?");
            $deletePrevious->execute([$previousSemester]);
        }

        $saveSetting = $pdo->prepare("
            UPDATE timetable_settings
            SET active_semester = ?
            WHERE id = 1
        ");
        $saveSetting->execute([$newSemester]);

        $pdo->commit();
        $_SESSION['success'] = ($previousSemester === $newSemester)
            ? 'This semester is already active.'
            : 'Active semester saved successfully.';
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = 'Unable to change the active semester.';
    }

    timetable_redirect();
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
    $semester     = $activeSemester;

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

        if (
            isset($e->errorInfo[1]) &&
            (int)$e->errorInfo[1] === 1451
        ) {

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
    $oldSemester = $activeSemester;

    $oldYear = normalize_year(
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
    $newSemester = $activeSemester;

    $newYear = normalize_year(
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
*/

include __DIR__ . '/../includes/header.php';

?>


<style>

/* ============================================================
   GENERAL PAGE
============================================================ */

.timetable-page {
    width: 100%;
}

.timetable-page * {
    box-sizing: border-box;
}


/* ============================================================
   PAGE HEADER
============================================================ */

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


/* ============================================================
   CARD
============================================================ */

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


/* ============================================================
   SECTION HEADING
============================================================ */

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


/* ============================================================
   FORM
============================================================ */

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


/* ============================================================
   BUTTON
============================================================ */

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


/* ============================================================
   UPDATE BOXES
============================================================ */

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


/* ============================================================
   DIVIDER
============================================================ */

.timetable-page .divider {
    height: 1px;
    background: #e5e7eb;
    margin: 24px 0;
}


/* ============================================================
   ALERTS
============================================================ */

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


/* ============================================================
   OLD NORMAL TABLE
============================================================ */

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


/* ============================================================
   EMPTY STATE
============================================================ */

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


/* ============================================================
   VISUAL UCSC TIMETABLE
============================================================ */

.timetable-page .visual-timetable-wrapper {
    width: 100%;
    overflow-x: auto;
    margin-top: 20px;
}


/*
|--------------------------------------------------------------------------
| IMPORTANT
| Separate class so this does not interfere with other tables.
|--------------------------------------------------------------------------
*/

.timetable-page table.visual-timetable {
    width: 100%;
    min-width: 900px;
    border-collapse: collapse;
    table-layout: fixed;
    background: #ffffff;
    border: 1px solid #555555;
}


/* ============================================================
   ALL VISUAL TABLE CELLS
============================================================ */

.timetable-page table.visual-timetable th,
.timetable-page table.visual-timetable td {
    border: 1px solid #777777;
    padding: 0;
    text-align: center;
    vertical-align: middle;
}


/* ============================================================
   UNIVERSITY HEADER
============================================================ */

.timetable-page table.visual-timetable .university-header {
    height: 65px;
    padding: 5px 10px;
    background: #ffffff;
}

.timetable-page .visual-timetable .uni-name {
    font-size: 15px;
    font-weight: 700;
    line-height: 1.25;
}

.timetable-page .visual-timetable .programme {
    font-size: 12px;
    font-weight: 700;
    line-height: 1.25;
}

.timetable-page .visual-timetable .title {
    font-size: 12px;
    font-weight: 700;
    line-height: 1.25;
}


/* ============================================================
   HEADER ROWS
============================================================ */

.timetable-page .visual-timetable .time-main-header {
    width: 120px;
    height: 23px;
    font-size: 14px;
    font-weight: 700;
    background: #ffffff;
}

.timetable-page .visual-timetable .day-header {
    height: 23px;
    font-size: 14px;
    font-weight: 700;
    background: #ffffff;
}

.timetable-page .visual-timetable .course-header {
    height: 23px;
    font-size: 13px;
    font-weight: 700;
    background: #ffffff;
}


/* ============================================================
   TIME COLUMN
============================================================ */

.timetable-page .visual-timetable .time-cell {
    width: 120px;
    height: 45px;
    padding: 4px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
    background: #ffffff;
}


/* ============================================================
   SUBJECT CELLS
============================================================ */

.timetable-page .visual-timetable .slot-cell {
    height: 45px;
    padding: 3px;
    font-size: 10px;
    background: #ffffff;
}

.timetable-page .visual-timetable .slot-subject {
    font-size: 9px;
    font-weight: 700;
    line-height: 1.15;
    word-break: break-word;
}

.timetable-page .visual-timetable .slot-room {
    margin-top: 3px;
    font-size: 8px;
    line-height: 1.1;
    color: #333333;
    word-break: break-word;
}


/* ============================================================
   EMPTY CELL
============================================================ */

.timetable-page .visual-timetable .empty-slot {
    background: #ffffff;
}


/* ============================================================
   LUNCH BREAK
============================================================ */

.timetable-page .visual-timetable .lunch-break {
    height: 40px;
    font-size: 14px;
    font-weight: 700;
    background: #ffffff;
}


/* ============================================================
   RESULT TITLE
============================================================ */

.timetable-page .visual-result-title {
    margin: 0 0 5px;
    font-size: 18px;
    font-weight: 700;
}

.timetable-page .visual-result-subtitle {
    margin: 0 0 18px;
    color: #6b7280;
    font-size: 13px;
}


/* ============================================================
   MOBILE
============================================================ */

@media (max-width: 700px) {

    .timetable-page .card-body {
        padding: 18px;
    }

    .timetable-page .page-toolbar h1 {
        font-size: 23px;
    }

    .timetable-page table.visual-timetable {
        min-width: 900px;
    }

}


/* ============================================================
   PRINT
============================================================ */

@media print {

    .timetable-page .page-toolbar,
    .timetable-page .alert,
    .timetable-page .card:not(.view-card) {
        display: none !important;
    }

    .timetable-page .view-filter-area {
        display: none !important;
    }

    .timetable-page .card {
        border: none;
        margin: 0;
    }

    .timetable-page .card-body {
        padding: 0;
    }

    .timetable-page .visual-timetable-wrapper {
        overflow: visible;
    }

    .timetable-page table.visual-timetable {
        min-width: 0;
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
         ACTIVE SEMESTER
    ======================================================= -->

    <div class="card">
        <div class="card-body">
            <div class="section-heading">
                <h2>Active Semester</h2>
                <p>Choose the semester once. It remains selected after logout and login.</p>
            </div>

            <p>Current active semester:
                <strong><?= htmlspecialchars($activeSemester !== '' ? $activeSemester : 'Not selected') ?></strong>
            </p>

            <form method="POST" id="activeSemesterForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="selected_semester">Select Semester</label>
                        <select id="selected_semester" name="selected_semester" class="form-control" required>
                            <option value="Semester 1" <?= $activeSemester === 'Semester 1' ? 'selected' : '' ?>>Semester 1</option>
                            <option value="Semester 2" <?= $activeSemester === 'Semester 2' ? 'selected' : '' ?>>Semester 2</option>
                        </select>
                    </div>
                </div>
                <input type="hidden" name="confirm_delete" id="confirm_delete" value="">
                <div class="button-area">
                    <button type="submit" name="change_semester" value="1" class="btn btn-primary">Set Active Semester</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.getElementById('activeSemesterForm').addEventListener('submit', function (event) {
        const currentSemester = <?= json_encode($activeSemester) ?>;
        const selectedSemester = document.getElementById('selected_semester').value;
        const confirmationField = document.getElementById('confirm_delete');
        confirmationField.value = '';

        if (currentSemester && currentSemester !== selectedSemester) {
            const confirmed = window.confirm(
                'Changing from ' + currentSemester + ' to ' + selectedSemester +
                ' will permanently delete all timetable records for ' + currentSemester +
                '. Do you want to continue?'
            );

            if (!confirmed) {
                event.preventDefault();
                return;
            }

            confirmationField.value = '1';
        }
    });
    </script>


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
                        <input type="hidden" name="old_semester" value="<?= htmlspecialchars($activeSemester) ?>">


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
                        <input type="hidden" name="new_semester" value="<?= htmlspecialchars($activeSemester) ?>">


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

    <div class="card view-card">

        <div class="card-body">

            <div class="section-heading">

                <h2>
                    View Timetable
                </h2>

                <p>
                    Select semester and year to display the timetable.
                </p>

            </div>


            <!-- ==================================================
                 VIEW FILTER
            =================================================== -->

            <form
                method="POST"
                class="view-filter-area"
            >

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


                <?php

                /*
                |--------------------------------------------------------------------------
                | DISPLAY YEAR
                |--------------------------------------------------------------------------
                */

                $displayYear = [
                    '1' => '1st Year',
                    '2' => '2nd Year',
                    '3' => '3rd Year',
                    '4' => '4th Year'
                ][$viewYear] ?? $viewYear;


                /*
                |--------------------------------------------------------------------------
                | DAYS
                |--------------------------------------------------------------------------
                */

                $visualDays = [
                    'Monday',
                    'Tuesday',
                    'Wednesday',
                    'Thursday',
                    'Friday'
                ];


                /*
                |--------------------------------------------------------------------------
                | TIME ROWS
                |--------------------------------------------------------------------------
                */

                $visualTimeSlots = [

                    [
                        'start' => '08:00:00',
                        'end'   => '09:00:00',
                        'label' => '8 AM - 9 AM'
                    ],

                    [
                        'start' => '09:00:00',
                        'end'   => '10:00:00',
                        'label' => '9 AM - 10 AM'
                    ],

                    [
                        'start' => '10:00:00',
                        'end'   => '11:00:00',
                        'label' => '10 AM - 11 AM'
                    ],

                    [
                        'start' => '11:00:00',
                        'end'   => '12:00:00',
                        'label' => '11 AM - 12 PM'
                    ],

                    [
                        'start' => '13:00:00',
                        'end'   => '14:00:00',
                        'label' => '1 PM - 2 PM'
                    ],

                    [
                        'start' => '14:00:00',
                        'end'   => '15:00:00',
                        'label' => '2 PM - 3 PM'
                    ],

                    [
                        'start' => '15:00:00',
                        'end'   => '16:00:00',
                        'label' => '3 PM - 4 PM'
                    ],

                    [
                        'start' => '16:00:00',
                        'end'   => '17:00:00',
                        'label' => '4 PM - 5 PM'
                    ],

                    [
                        'start' => '17:00:00',
                        'end'   => '18:00:00',
                        'label' => '5 PM - 6 PM'
                    ],

                    [
                        'start' => '18:00:00',
                        'end'   => '19:00:00',
                        'label' => '6 PM - 7 PM'
                    ]

                ];


                /*
                |--------------------------------------------------------------------------
                | CREATE RECORD MAP
                |--------------------------------------------------------------------------
                */

                $visualMap = [];

                foreach ($visualDays as $day) {

                    $visualMap[$day] = [
                        'CS' => [],
                        'IS' => []
                    ];
                }


                foreach ($viewRows as $row) {

                    $rowDay =
                        trim($row['day_name']);

                    $rowCourse =
                        strtoupper(
                            trim($row['course'])
                        );


                    if (
                        isset($visualMap[$rowDay]) &&
                        isset($visualMap[$rowDay][$rowCourse])
                    ) {

                        $rowStart = date(
                            'H:i:s',
                            strtotime($row['start_time'])
                        );

                        $visualMap[$rowDay][$rowCourse][$rowStart]
                            = $row;
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | FIND NUMBER OF HOURS
                |--------------------------------------------------------------------------
                */

                function timetable_hours(
                    string $start,
                    string $end
                ): int {

                    $startTimestamp =
                        strtotime($start);

                    $endTimestamp =
                        strtotime($end);

                    $hours =
                        ($endTimestamp - $startTimestamp) / 3600;

                    return max(
                        1,
                        (int)$hours
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | CHECK WHETHER A SLOT IS INSIDE A PREVIOUS CLASS
                |--------------------------------------------------------------------------
                */

                $occupied = [];

                foreach ($visualDays as $day) {

                    $occupied[$day] = [
                        'CS' => [],
                        'IS' => []
                    ];
                }


                foreach ($viewRows as $row) {

                    $rowDay =
                        trim($row['day_name']);

                    $rowCourse =
                        strtoupper(
                            trim($row['course'])
                        );


                    if (
                        !isset($occupied[$rowDay]) ||
                        !isset($occupied[$rowDay][$rowCourse])
                    ) {
                        continue;
                    }


                    $start =
                        strtotime($row['start_time']);

                    $end =
                        strtotime($row['end_time']);


                    /*
                    |--------------------------------------------------------------------------
                    | Mark every hourly slot occupied by this class
                    |--------------------------------------------------------------------------
                    */

                    foreach ($visualTimeSlots as $slot) {

                        $slotStart =
                            strtotime($slot['start']);

                        $slotEnd =
                            strtotime($slot['end']);


                        if (
                            $slotStart >= $start &&
                            $slotStart < $end
                        ) {

                            $occupied[$rowDay][$rowCourse][
                                $slot['start']
                            ] = $row['id'];
                        }
                    }
                }

                ?>


                <?php if (empty($viewRows)): ?>

                    <div class="empty-state">

                        <h3>
                            No timetable records found
                        </h3>

                        <p>

                            No records were found for

                            <?= htmlspecialchars(
                                $viewSemester
                            ) ?>

                            -

                            <?= htmlspecialchars(
                                $displayYear
                            ) ?>.

                        </p>

                    </div>

                <?php else: ?>


                    <!-- ==================================================
                         TIMETABLE TITLE
                    =================================================== -->

                    <h3 class="visual-result-title">

                        University of Colombo School of Computing (UCSC)

                    </h3>


                    <p class="visual-result-subtitle">

                        <?= htmlspecialchars(
                            $viewSemester
                        ) ?>

                        -

                        <?= htmlspecialchars(
                            $displayYear
                        ) ?>

                    </p>


                    <!-- ==================================================
                         VISUAL TIMETABLE
                    =================================================== -->

                    <div class="visual-timetable-wrapper">

                        <table class="visual-timetable">


                            <!-- ==================================================
                                 UNIVERSITY HEADER
                            =================================================== -->

                            <thead>

                                <tr>

                                    <th
                                        colspan="11"
                                        class="university-header"
                                    >

                                        <div class="uni-name">
                                            University of Colombo School of Computing (UCSC)
                                        </div>

                                        <div class="programme">
                                            Bachelor of Science in Computer Science
                                            and Bachelor of Science in Information Systems
                                            Degree Programme
                                        </div>

                                        <div class="title">
                                            Lecture Time Table -
                                            <?= htmlspecialchars(
                                                $displayYear
                                            ) ?>
                                            -
                                            <?= htmlspecialchars(
                                                $viewSemester
                                            ) ?>
                                        </div>

                                    </th>

                                </tr>


                                <!-- DAY HEADERS -->

                                <tr>

                                    <th
                                        rowspan="2"
                                        class="time-main-header"
                                    >
                                        TIME
                                    </th>


                                    <?php foreach ($visualDays as $day): ?>

                                        <th
                                            colspan="2"
                                            class="day-header"
                                        >

                                            <?= htmlspecialchars(
                                                strtoupper($day)
                                            ) ?>

                                        </th>

                                    <?php endforeach; ?>

                                </tr>


                                <!-- CS / IS -->

                                <tr>

                                    <?php foreach ($visualDays as $day): ?>

                                        <th class="course-header">
                                            CS
                                        </th>

                                        <th class="course-header">
                                            IS
                                        </th>

                                    <?php endforeach; ?>

                                </tr>

                            </thead>


                            <!-- ==================================================
                                 TIMETABLE BODY
                            =================================================== -->

                            <tbody>


                            <?php foreach ($visualTimeSlots as $index => $slot): ?>


                                <?php if ($index === 4): ?>

                                    <!-- ==========================================
                                         LUNCH BREAK
                                    =========================================== -->

                                    <tr>

                                        <td
                                            class="time-cell"
                                        >
                                            12 PM - 1 PM
                                        </td>

                                        <td
                                            colspan="10"
                                            class="lunch-break"
                                        >
                                            Lunch Break
                                        </td>

                                    </tr>

                                <?php endif; ?>


                                <tr>


                                    <!-- TIME -->

                                    <td class="time-cell">

                                        <?= htmlspecialchars(
                                            $slot['label']
                                        ) ?>

                                    </td>


                                    <?php foreach ($visualDays as $day): ?>


                                        <?php foreach (
                                            ['CS', 'IS']
                                            as $course
                                        ): ?>


                                            <?php

                                            /*
                                            |--------------------------------------------------------------------------
                                            | CHECK IF THIS CELL IS ALREADY COVERED
                                            |--------------------------------------------------------------------------
                                            */

                                            $slotStart =
                                                $slot['start'];


                                            $recordId =
                                                $occupied[$day][$course][
                                                    $slotStart
                                                ] ?? null;


                                            /*
                                            |--------------------------------------------------------------------------
                                            | IF OCCUPIED, CHECK WHETHER THIS IS
                                            | THE START OF THE CLASS
                                            |--------------------------------------------------------------------------
                                            */

                                            if ($recordId !== null) {

                                                $record = null;

                                                foreach (
                                                    $viewRows
                                                    as $possibleRecord
                                                ) {

                                                    if (
                                                        (string)$possibleRecord['id']
                                                        ===
                                                        (string)$recordId
                                                    ) {

                                                        $record =
                                                            $possibleRecord;

                                                        break;
                                                    }
                                                }


                                                /*
                                                |--------------------------------------------------------------------------
                                                | If this is not the starting
                                                | time, do not print another cell.
                                                |--------------------------------------------------------------------------
                                                */

                                                $recordStart =
                                                    $record
                                                    ? date(
                                                        'H:i:s',
                                                        strtotime(
                                                            $record['start_time']
                                                        )
                                                    )
                                                    : '';


                                                if (
                                                    $record === null ||
                                                    $recordStart !== $slotStart
                                                ) {

                                                    continue;
                                                }


                                                /*
                                                |--------------------------------------------------------------------------
                                                | CALCULATE ROWSPAN
                                                |--------------------------------------------------------------------------
                                                */

                                                $rowspan =
                                                    timetable_hours(
                                                        $record['start_time'],
                                                        $record['end_time']
                                                    );


                                                /*
                                                |--------------------------------------------------------------------------
                                                | MAXIMUM ROWSPAN
                                                |--------------------------------------------------------------------------
                                                */

                                                $remainingRows =
                                                    count(
                                                        $visualTimeSlots
                                                    ) - $index;


                                                $rowspan =
                                                    min(
                                                        $rowspan,
                                                        $remainingRows
                                                    );

                                            ?>

                                                <td
                                                    class="slot-cell"
                                                    rowspan="<?= $rowspan ?>"
                                                >

                                                    <div class="slot-subject">

                                                        <?= htmlspecialchars(
                                                            $record['subject_name']
                                                        ) ?>

                                                    </div>


                                                    <div class="slot-room">

                                                        <?= htmlspecialchars(
                                                            $record['room']
                                                        ) ?>

                                                    </div>

                                                </td>


                                            <?php

                                            } else {

                                                /*
                                                |--------------------------------------------------------------------------
                                                | EMPTY CELL
                                                |--------------------------------------------------------------------------
                                                */

                                            ?>

                                                <td
                                                    class="slot-cell empty-slot"
                                                ></td>

                                            <?php

                                            }

                                            ?>


                                        <?php endforeach; ?>


                                    <?php endforeach; ?>


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