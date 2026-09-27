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
        app_url('non_academic/timetable_records.php')   // now this
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
| SYNC WITH INSTRUCTOR COORDINATOR'S TIMETABLE REQUIREMENTS
|--------------------------------------------------------------------------
| Every slot a non-academic staff member posts here also needs to show
| up on coordinator/timetable_requirements.php so the Instructor
| Coordinator can assign an instructor to it. That page reads from
| timetable_requirements / timetable_slots, a separate pair of tables
| from the `timetables` table this page manages, so we keep a linked
| requirement row in sync (create on add, update on edit, delete on
| delete) via `timetables.requirement_id`.
*/
function sync_timetable_requirement(
    PDO $pdo,
    ?int $existingRequirementId,
    string $subjectName,
    string $course,
    string $dayName,
    string $startTime,
    string $endTime,
    string $room,
    string $semester,
    string $studentYear
): ?int {

    $streamStmt = $pdo->prepare(
        "SELECT id FROM academic_streams WHERE code = ? LIMIT 1"
    );
    $streamStmt->execute([$course]);
    $streamId = $streamStmt->fetchColumn();
    $streamId = $streamId !== false ? (int)$streamId : null;

    $studentYearInt = ctype_digit($studentYear) ? (int)$studentYear : null;

    if ($existingRequirementId) {
        $pdo->prepare("
            UPDATE timetable_requirements
            SET day_of_week = ?, start_time = ?, end_time = ?, subject = ?,
                location = ?, academic_stream_id = ?, student_year = ?, semester = ?
            WHERE id = ?
        ")->execute([
            $dayName, $startTime, $endTime, $subjectName,
            $room, $streamId, $studentYearInt, $semester,
            $existingRequirementId
        ]);
        refreshRequirementStatus($existingRequirementId);
        return $existingRequirementId;
    }

    $pdo->prepare("
        INSERT INTO timetable_requirements
            (day_of_week, start_time, end_time, subject, location,
             academic_stream_id, student_year, required_instructors,
             semester, academic_year, status, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, 'Open', ?)
    ")->execute([
        $dayName, $startTime, $endTime, $subjectName, $room,
        $streamId, $studentYearInt, $semester, DEFAULT_ACADEMIC_YEAR,
        $_SESSION['user_id'] ?? null
    ]);

    return (int)$pdo->lastInsertId();
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

        $pdo->beginTransaction();

        // Create the linked Timetable Requirement first, so the
        // coordinator's assignment panel can see this slot.
        $requirementId = sync_timetable_requirement(
            $pdo, null, $subjectName, $course, $dayName,
            $startTime, $endTime, $room, $semester, $academicYear
        );

        $stmt = $pdo->prepare("
            INSERT INTO timetables
            (
                requirement_id,
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
            (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $requirementId,
            $subjectName,
            $course,
            $dayName,
            $startTime,
            $endTime,
            $room,
            $semester,
            $academicYear
        ]);

        $pdo->commit();

        $_SESSION['success'] =
            'Timetable slot added successfully. It is now visible to the Instructor Coordinator for assignment.';

    } catch (PDOException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $_SESSION['error'] =
            'Unable to add timetable slot.';
    }


    timetable_redirect();
}


/*
|--------------------------------------------------------------------------
| DELETE TIMETABLE - BY ID
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_timetable'])) {
    $timetableId = (int)($_POST['timetable_id'] ?? 0);

    try {
        if ($timetableId <= 0) {
            throw new Exception('Invalid timetable record selected.');
        }

        $find = $pdo->prepare("SELECT id, requirement_id FROM timetables WHERE id = ? LIMIT 1");
        $find->execute([$timetableId]);
        $record = $find->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            throw new Exception('Timetable record not found.');
        }

        $pdo->beginTransaction();

        $delete = $pdo->prepare("DELETE FROM timetables WHERE id = ?");
        $delete->execute([$timetableId]);

        if (!empty($record['requirement_id'])) {
            $pdo->prepare("DELETE FROM timetable_requirements WHERE id = ?")
                ->execute([(int)$record['requirement_id']]);
        }

        $pdo->commit();
        $_SESSION['success'] = 'Timetable record deleted successfully.';

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }

    timetable_redirect();
}


/*
|--------------------------------------------------------------------------
| UPDATE TIMETABLE - BY ID
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_timetable'])) {
    $timetableId = (int)($_POST['timetable_id'] ?? 0);

    $subjectName = trim($_POST['subject_name'] ?? '');
    $course      = trim($_POST['course'] ?? '');
    $dayName     = trim($_POST['day_name'] ?? '');
    $startTime   = trim($_POST['start_time'] ?? '');
    $endTime     = trim($_POST['end_time'] ?? '');
    $room        = trim($_POST['room'] ?? '');
    $semester    = trim($_POST['semester'] ?? '');
    $academicYear = normalize_year(trim($_POST['academic_year'] ?? ''));

    if ($timetableId <= 0 || $subjectName === '' || $course === '' || $dayName === '' ||
        $startTime === '' || $endTime === '' || $room === '' || $semester === '' || $academicYear === '') {
        $_SESSION['error'] = 'Please complete all timetable fields.';
        timetable_redirect();
    }

    if ($startTime >= $endTime) {
        $_SESSION['error'] = 'End time must be later than start time.';
        timetable_redirect();
    }

    try {
        $find = $pdo->prepare("SELECT id, requirement_id FROM timetables WHERE id = ? LIMIT 1");
        $find->execute([$timetableId]);
        $oldRecord = $find->fetch(PDO::FETCH_ASSOC);

        if (!$oldRecord) {
            throw new Exception('Timetable record not found.');
        }

        $check = $pdo->prepare("SELECT id FROM timetables
            WHERE subject_name = ? AND course = ? AND day_name = ? AND start_time = ?
              AND end_time = ? AND room = ? AND semester = ? AND academic_year = ? AND id <> ?
            LIMIT 1");
        $check->execute([$subjectName, $course, $dayName, $startTime, $endTime, $room,
                        $semester, $academicYear, $timetableId]);

        if ($check->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception('Another timetable record with the same details already exists.');
        }

        $pdo->beginTransaction();

        $existingRequirementId = !empty($oldRecord['requirement_id'])
            ? (int)$oldRecord['requirement_id'] : null;

        $requirementId = sync_timetable_requirement(
            $pdo, $existingRequirementId, $subjectName, $course, $dayName,
            $startTime, $endTime, $room, $semester, $academicYear
        );

        $update = $pdo->prepare("UPDATE timetables SET
            requirement_id = ?, subject_name = ?, course = ?, day_name = ?,
            start_time = ?, end_time = ?, room = ?, semester = ?, academic_year = ?
            WHERE id = ?");

        $update->execute([$requirementId, $subjectName, $course, $dayName, $startTime,
                          $endTime, $room, $semester, $academicYear, $timetableId]);

        $pdo->commit();
        $_SESSION['success'] = 'Timetable record updated successfully.';

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }

    timetable_redirect();
}


/*
|--------------------------------------------------------------------------
| GET RECORD FOR EDIT
|--------------------------------------------------------------------------
*/
$editTimetable = null;

if (isset($_GET['edit']) && (int)$_GET['edit'] > 0) {
    try {
        $stmt = $pdo->prepare("SELECT id, subject_name, course, day_name, start_time,
            end_time, room, semester, academic_year FROM timetables WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$_GET['edit']]);
        $editTimetable = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$editTimetable) $errorMessage = 'Timetable record not found.';
    } catch (Throwable $e) {
        $errorMessage = 'Unable to load the selected timetable record.';
    }
}


/*
|--------------------------------------------------------------------------
| VIEW FILTER - YEAR + SEMESTER
|--------------------------------------------------------------------------
*/
$viewYear = normalize_year(trim($_GET['year'] ?? ''));
$viewSemester = trim($_GET['semester'] ?? '');
$viewRequested = isset($_GET['view']);

$viewRows = [];

/* Rooms already registered by Room Schedules / Lecture Room Management. */
$lectureRooms = [];
try {
    $roomStmt = $pdo->query("SELECT id, room_name FROM lecture_rooms ORDER BY room_name");
    $lectureRooms = $roomStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $lectureRooms = [];
}

if ($viewRequested && $viewYear !== '' && $viewSemester !== '') {
    try {
        $stmt = $pdo->prepare("
            SELECT id, subject_name, course, day_name, start_time, end_time,
                   room, semester, academic_year
            FROM timetables
            WHERE academic_year = ? AND semester = ?
            ORDER BY FIELD(day_name,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
                     start_time, subject_name
        ");
        $stmt->execute([$viewYear, $viewSemester]);
        $viewRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $errorMessage = 'Unable to load timetable records.';
    }
}

/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/
include __DIR__ . '/../includes/header.php';
?>

<style>
.timetable-page{width:100%}.timetable-page *{box-sizing:border-box}
.timetable-page .page-toolbar{margin-bottom:25px}.timetable-page h1{margin:0;font-size:28px;font-weight:700}.timetable-page .page-toolbar p{margin:7px 0 0;color:#6b7280;font-size:14px}
.timetable-page .card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;margin-bottom:24px;overflow:hidden}.timetable-page .card-body{padding:25px}
.timetable-page .section-heading{margin-bottom:22px}.timetable-page .section-heading h2{margin:0;font-size:20px;font-weight:700}.timetable-page .section-heading p{margin:6px 0 0;color:#6b7280;font-size:14px}
.timetable-page .form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:18px}.timetable-page label{display:block;margin-bottom:7px;font-size:14px;font-weight:600;color:#111827}
.timetable-page .form-control{width:100%;min-height:44px;padding:10px 12px;border:1px solid #d1d5db;border-radius:9px;background:#fff;color:#111827;font-size:14px}.timetable-page .readonly-control{background:#f3f4f6;color:#4b5563}
.timetable-page .room-warning{display:block;margin-top:7px;color:#b45309;font-size:12px}.timetable-page .button-area{margin-top:20px;display:flex;gap:10px;flex-wrap:wrap}.timetable-page .btn{display:inline-block;border:0;border-radius:8px;padding:11px 18px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none}.timetable-page .btn-primary{background:#000a1e;color:#fff}.timetable-page .btn-danger{background:#8f1414;color:#fff}.timetable-page .btn-secondary{background:#6b7280;color:#fff}.timetable-page .btn-outline{background:#fff;border:1px solid #d1d5db;color:#374151}
.timetable-page .old-box,.timetable-page .new-box{border-radius:12px;padding:22px}.timetable-page .old-box{background:#f8fafc;border:1px solid #e5e7eb}.timetable-page .new-box{background:#fff;border:1px solid #dbe3ee}.timetable-page .box-title{margin:0 0 18px;font-size:17px}.timetable-page .divider{height:18px}
.timetable-page .empty-state{border:1px dashed #d1d5db;border-radius:10px;padding:30px;text-align:center}.timetable-page .empty-state h3{margin:0;font-size:17px}.timetable-page .empty-state p{color:#6b7280;margin-bottom:0}

/* UCSC-style weekly timetable */
.schedule-wrap{width:100%;overflow-x:auto}.schedule-table{width:100%;min-width:1120px;border-collapse:collapse;table-layout:fixed;background:#fff;border:1px solid #111}.schedule-table col.time-col{width:110px}.schedule-table col.slot-col{width:auto}.schedule-table th,.schedule-table td{border:1px solid #111;padding:0;text-align:center;vertical-align:middle}.schedule-table thead th{height:30px;background:#fff;font-size:11px;font-weight:700}.schedule-table thead .day-head{height:30px;font-size:12px}.schedule-table .time-cell{width:110px;height:58px;font-size:11px;font-weight:700;line-height:1.25;padding:4px}.schedule-table .empty-cell{height:58px;background:#fff}.schedule-table .event-cell{height:58px;padding:3px;vertical-align:middle}.schedule-table .slot-event{width:100%;height:100%;min-height:52px;border:0;background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:4px;cursor:pointer}.schedule-table .slot-event:hover{background:#eef2f7}.schedule-table .slot-event .subject{font-weight:700;font-size:11px;line-height:1.2;overflow-wrap:anywhere}.schedule-table .slot-event .room{font-size:10px;line-height:1.2;margin-top:3px}.schedule-table .slot-event .course-tag{font-size:9px;line-height:1.2;margin-top:3px;color:#4b5563}.schedule-table .slot-actions{display:none;gap:4px;margin-top:6px;justify-content:center;flex-wrap:wrap}.schedule-table .slot-event.selected .slot-actions{display:flex}.schedule-table .slot-actions a,.schedule-table .slot-actions button{border:0;border-radius:4px;padding:4px 7px;font-size:10px;font-weight:600;text-decoration:none;cursor:pointer}.schedule-table .slot-actions a{background:#000a1e;color:#fff}.schedule-table .slot-actions button{background:#8f1414;color:#fff}.schedule-table .lunch-label{height:34px;font-size:11px;font-weight:700}.schedule-table .lunch{height:34px;font-size:12px;font-weight:700}.schedule-title{text-align:center;margin-bottom:12px}.schedule-title h3{margin:0;font-size:17px}.schedule-title p{margin:4px 0 0;font-size:12px;color:#6b7280}
@media(max-width:800px){.timetable-page .card-body{padding:18px}.timetable-page h1{font-size:23px}}
</style>

<div class="timetable-page">
<div class="page-toolbar"><h1>Timetable Management</h1><p>Add timetable records and view the weekly timetable by academic year and semester.</p></div>

<?php if ($successMessage !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div><?php endif; ?>
<?php if ($errorMessage !== ''): ?><div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div><?php endif; ?>

<div class="card"><div class="card-body">
<div class="section-heading"><h2>Add Timetable Record</h2><p>Create a timetable record using a lecture room already registered in Room Schedules.</p></div>
<form method="POST"><div class="form-grid">
<div><label>Subject</label><input type="text" name="subject_name" class="form-control" required></div>
<div><label>Course</label><select name="course" class="form-control" required><option value="" disabled selected>Select Course</option><option value="IS">IS</option><option value="CS">CS</option><option value="SE">SE</option></select></div>
<div><label>Day</label><select name="day_name" class="form-control" required><option value="" disabled selected>Select Day</option><?php foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d): ?><option value="<?= $d ?>"><?= $d ?></option><?php endforeach; ?></select></div>
<div><label>Start Time</label><input type="time" name="start_time" class="form-control" required></div>
<div><label>End Time</label><input type="time" name="end_time" class="form-control" required></div>
<div><label>Lecture Room</label><select name="room" class="form-control" required <?= empty($lectureRooms) ? 'disabled' : '' ?>><option value="" disabled selected>Select Lecture Room</option><?php foreach($lectureRooms as $lr): ?><option value="<?= htmlspecialchars($lr['room_name']) ?>"><?= htmlspecialchars($lr['room_name']) ?></option><?php endforeach; ?></select><?php if(empty($lectureRooms)): ?><small class="room-warning">No rooms found. Add a lecture room in Room Schedules first.</small><?php endif; ?></div>
<div><label>Semester</label><select name="semester" class="form-control" required><option value="" disabled selected>Select Semester</option><option value="Semester 1">Semester 1</option><option value="Semester 2">Semester 2</option></select></div>
<div><label>Academic Year</label><select name="academic_year" class="form-control" required><option value="" disabled selected>Select Year</option><option value="1">1st Year</option><option value="2">2nd Year</option><option value="3">3rd Year</option><option value="4">4th Year</option></select></div>
</div><div class="button-area"><button type="submit" name="add_timetable" class="btn btn-primary" <?= empty($lectureRooms) ? 'disabled' : '' ?>>Add Timetable</button></div></form>
</div></div>

<div class="card"><div class="card-body">
<div class="section-heading"><h2>View Timetable</h2><p>Select the academic year and semester, then click View.</p></div>
<form method="GET"><div class="form-grid">
<div><label>Academic Year</label><select name="year" class="form-control" required><option value="" disabled <?= $viewYear===''?'selected':'' ?>>Select Year</option><?php for($y=1;$y<=4;$y++): ?><option value="<?= $y ?>" <?= $viewYear===(string)$y?'selected':'' ?>><?= $y ?><?= $y===1?'st':($y===2?'nd':($y===3?'rd':'th')) ?> Year</option><?php endfor; ?></select></div>
<div><label>Semester</label><select name="semester" class="form-control" required><option value="" disabled <?= $viewSemester===''?'selected':'' ?>>Select Semester</option><option value="Semester 1" <?= $viewSemester==='Semester 1'?'selected':'' ?>>Semester 1</option><option value="Semester 2" <?= $viewSemester==='Semester 2'?'selected':'' ?>>Semester 2</option></select></div>
</div><div class="button-area"><button type="submit" name="view" value="1" class="btn btn-primary">View</button></div></form>
</div></div>

<?php if($editTimetable): ?>
<div class="card"><div class="card-body">
<div class="section-heading"><h2>Update Timetable</h2><p>Current details are shown first. Edit the details below.</p></div>
<form method="POST">
<div class="old-box"><h3 class="box-title">Current Timetable Details</h3><div class="form-grid">
<div><label>Subject</label><input class="form-control readonly-control" value="<?= htmlspecialchars($editTimetable['subject_name']) ?>" readonly></div>
<div><label>Course</label><input class="form-control readonly-control" value="<?= htmlspecialchars($editTimetable['course']) ?>" readonly></div>
<div><label>Day</label><input class="form-control readonly-control" value="<?= htmlspecialchars($editTimetable['day_name']) ?>" readonly></div>
<div><label>Time</label><input class="form-control readonly-control" value="<?= htmlspecialchars(substr($editTimetable['start_time'],0,5).' - '.substr($editTimetable['end_time'],0,5)) ?>" readonly></div>
<div><label>Lecture Room</label><input class="form-control readonly-control" value="<?= htmlspecialchars($editTimetable['room']) ?>" readonly></div>
<div><label>Semester</label><input class="form-control readonly-control" value="<?= htmlspecialchars($editTimetable['semester']) ?>" readonly></div>
<div><label>Academic Year</label><input class="form-control readonly-control" value="<?= htmlspecialchars(['1'=>'1st Year','2'=>'2nd Year','3'=>'3rd Year','4'=>'4th Year'][(string)$editTimetable['academic_year']] ?? $editTimetable['academic_year']) ?>" readonly></div>
</div></div><div class="divider"></div>
<div class="new-box"><h3 class="box-title">New Timetable Details</h3><input type="hidden" name="timetable_id" value="<?= (int)$editTimetable['id'] ?>"><div class="form-grid">
<div><label>Subject</label><input type="text" name="subject_name" class="form-control" value="<?= htmlspecialchars($editTimetable['subject_name']) ?>" required></div>
<div><label>Course</label><select name="course" class="form-control" required><?php foreach(['IS','CS','SE'] as $c): ?><option value="<?= $c ?>" <?= $editTimetable['course']===$c?'selected':'' ?>><?= $c ?></option><?php endforeach; ?></select></div>
<div><label>Day</label><select name="day_name" class="form-control" required><?php foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d): ?><option value="<?= $d ?>" <?= $editTimetable['day_name']===$d?'selected':'' ?>><?= $d ?></option><?php endforeach; ?></select></div>
<div><label>Start Time</label><input type="time" name="start_time" class="form-control" value="<?= htmlspecialchars(substr($editTimetable['start_time'],0,5)) ?>" required></div>
<div><label>End Time</label><input type="time" name="end_time" class="form-control" value="<?= htmlspecialchars(substr($editTimetable['end_time'],0,5)) ?>" required></div>
<div><label>Lecture Room</label><select name="room" class="form-control" required><?php foreach($lectureRooms as $lr): ?><option value="<?= htmlspecialchars($lr['room_name']) ?>" <?= $editTimetable['room']===$lr['room_name']?'selected':'' ?>><?= htmlspecialchars($lr['room_name']) ?></option><?php endforeach; ?></select></div>
<div><label>Semester</label><select name="semester" class="form-control" required><option value="Semester 1" <?= $editTimetable['semester']==='Semester 1'?'selected':'' ?>>Semester 1</option><option value="Semester 2" <?= $editTimetable['semester']==='Semester 2'?'selected':'' ?>>Semester 2</option></select></div>
<div><label>Academic Year</label><select name="academic_year" class="form-control" required><?php for($y=1;$y<=4;$y++): ?><option value="<?= $y ?>" <?= (string)$editTimetable['academic_year']===(string)$y?'selected':'' ?>><?= $y ?><?= $y===1?'st':($y===2?'nd':($y===3?'rd':'th')) ?> Year</option><?php endfor; ?></select></div>
</div><div class="button-area"><button type="submit" name="update_timetable" class="btn btn-primary">Update</button><a href="timetable_records.php?view=1&year=<?= urlencode($viewYear) ?>&semester=<?= urlencode($viewSemester) ?>" class="btn btn-outline">Cancel</a></div></div>
</form></div></div>
<?php endif; ?>

<?php if($viewRequested && $viewYear!=='' && $viewSemester!==''): ?>
<div class="card"><div class="card-body">
<div class="schedule-title"><h3>University of Colombo School of Computing (UCSC)</h3><p>Bachelor of Science in Computer Science and Bachelor of Science in Information Systems Degree Programme</p><p><strong>Lecture Time Table - <?= htmlspecialchars(['1'=>'First','2'=>'Second','3'=>'Third','4'=>'Fourth'][(string)$viewYear] ?? $viewYear) ?> Year - 2026 (<?= htmlspecialchars($viewSemester) ?>)</strong></p></div>
<?php if(empty($viewRows)): ?>
<div class="empty-state"><h3>No Timetable Records</h3><p>No records found for the selected year and semester.</p></div>
<?php else:
    $hours = [8,9,10,11,13,14,15,16,17,18];
    $days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
    $courses = ['IS','CS'];
    $events = [];
    foreach($viewRows as $r){
        $start = (int)substr($r['start_time'],0,2);
        $end = (int)substr($r['end_time'],0,2);
        $startMin = ((int)substr($r['start_time'],0,2))*60 + (int)substr($r['start_time'],3,2);
        $endMin = ((int)substr($r['end_time'],0,2))*60 + (int)substr($r['end_time'],3,2);
        $events[] = ['row'=>$r,'start'=>$start,'end'=>$end,'startMin'=>$startMin,'endMin'=>$endMin];
    }
    $ord = function($h){ return array_search($h,[8,9,10,11,12,13,14,15,16,17,18],true); };
?>
<div class="schedule-wrap">
<table class="schedule-table">
<colgroup><col class="time-col"><?php for($i=0;$i<10;$i++): ?><col class="slot-col"><?php endfor; ?></colgroup>
<thead>
<tr><th rowspan="2">TIME</th><?php foreach($days as $d): ?><th class="day-head" colspan="2"><?= strtoupper($d) ?></th><?php endforeach; ?></tr>
<tr><?php foreach($days as $d): foreach($courses as $c): ?><th><?= $c ?></th><?php endforeach; endforeach; ?></tr>
</thead>
<tbody>
<?php
$occupied = [];
foreach($hours as $h):
    if($h===13): continue; endif;
    $nextH = $h + 1;
    $label = sprintf('%d.00 %s - %d.00 %s',$h>12?$h-12:$h,$h>=12?'pm':'am',$nextH>12?$nextH-12:$nextH,$nextH>=12?'pm':'am');
?>
<tr><td class="time-cell"><?= $label ?></td>
<?php foreach($days as $day): foreach($courses as $course):
    $key = $day.'|'.$course.'|'.$h;
    if(isset($occupied[$key])) continue;
    $found = null;
    foreach($events as $ev){
        $r = $ev['row'];
        if($r['day_name']!==$day || $r['course']!==$course) continue;
        $sh=(int)substr($r['start_time'],0,2);
        $eh=(int)substr($r['end_time'],0,2);
        if($sh===$h){$found=$ev;break;}
    }
    if(is_array($found)):
        $r=$found['row'];
        $duration=max(1,(int)ceil(($found['endMin']-$found['startMin'])/60));
        for($hh=$h+1;$hh<$h+$duration;$hh++){ $occupied[$day.'|'.$course.'|'.$hh]=true; }
?>
<td class="event-cell" rowspan="<?= $duration ?>"><div class="slot-event" onclick="toggleTimetableActions(this)"><div class="subject"><?= htmlspecialchars($r['subject_name']) ?></div><div class="room"><?= htmlspecialchars($r['room']) ?></div><div class="course-tag"><?= htmlspecialchars($r['course']) ?> · <?= htmlspecialchars(substr($r['start_time'],0,5).' - '.substr($r['end_time'],0,5)) ?></div><div class="slot-actions"><a href="timetable_records.php?edit=<?= (int)$r['id'] ?>&view=1&year=<?= urlencode($viewYear) ?>&semester=<?= urlencode($viewSemester) ?>">Edit</a><form method="POST" onsubmit="return confirm('Are you sure you want to delete this timetable record?');"><input type="hidden" name="timetable_id" value="<?= (int)$r['id'] ?>"><button type="submit" name="delete_timetable">Delete</button></form></div></div></td>
<?php else: ?><td class="empty-cell"></td><?php endif; ?>
<?php endforeach; endforeach; ?>
</tr>
<?php if($h===11): ?><tr><td class="lunch-label">12.00 noon - 1.00 pm</td><td class="lunch" colspan="10">Lunch Break</td></tr><?php endif; ?>
<?php endforeach; ?>
</tbody></table></div>
<p style="margin-top:12px;color:#6b7280;font-size:12px">Click a subject or lecture room in the timetable to show its Edit and Delete options.</p>
<?php endif; ?>
</div></div>
<?php endif; ?>

</div>
<script>
function toggleTimetableActions(el){
    document.querySelectorAll('.slot-event.selected').forEach(function(item){
        if(item !== el) item.classList.remove('selected');
    });
    el.classList.toggle('selected');
}
document.addEventListener('click', function(e){
    if(!e.target.closest('.slot-event')){
        document.querySelectorAll('.slot-event.selected').forEach(function(item){item.classList.remove('selected');});
    }
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
