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
.timetable-page .button-area{margin-top:20px;display:flex;gap:10px;flex-wrap:wrap}.timetable-page .btn{display:inline-block;border:0;border-radius:8px;padding:11px 18px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none}
.timetable-page .btn-primary{background:#000a1e;color:#fff}.timetable-page .btn-danger{background:#8f1414;color:#fff}.timetable-page .btn-outline{background:#fff;border:1px solid #d1d5db;color:#374151}
.timetable-page .old-box{background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:22px}.timetable-page .new-box{background:#fff;border:1px solid #dbeafe;border-radius:12px;padding:22px}.timetable-page .box-title{margin:0 0 18px;font-size:17px;font-weight:700}.timetable-page .divider{height:1px;background:#e5e7eb;margin:22px 0}
.timetable-page .alert{border-radius:9px;padding:13px 16px;margin-bottom:20px;font-size:14px}.timetable-page .alert-success{background:#ecfdf5;border:1px solid #a7f3d0;color:#047857}.timetable-page .alert-danger{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c}
.timetable-page .table-wrapper{overflow-x:auto;margin-top:20px}.timetable-page table{width:100%;border-collapse:collapse;min-width:1000px}.timetable-page th{text-align:left;background:#f9fafb;border-bottom:1px solid #e5e7eb;padding:13px 14px;font-size:13px;color:#374151}.timetable-page td{padding:14px;border-bottom:1px solid #f0f0f0;font-size:14px;color:#374151;vertical-align:middle}.timetable-page .action-buttons{display:flex;gap:8px;flex-wrap:wrap}.timetable-page .action-buttons .btn{padding:8px 14px;font-size:13px}.timetable-page .empty-state{border:1px dashed #d1d5db;border-radius:10px;padding:30px;text-align:center}.timetable-page .empty-state h3{margin:0;font-size:17px}.timetable-page .empty-state p{color:#6b7280;margin-bottom:0}
</style>

<div class="timetable-page">
<div class="page-toolbar"><h1>Timetable Management</h1><p>Manage timetable records.</p></div>

<?php if ($successMessage !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div><?php endif; ?>
<?php if ($errorMessage !== ''): ?><div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div><?php endif; ?>

<!-- ADD: unchanged logic -->
<div class="card"><div class="card-body">
<div class="section-heading"><h2>Add Timetable Record</h2><p>Create a new timetable record.</p></div>
<form method="POST"><div class="form-grid">
<div><label>Subject</label><input type="text" name="subject_name" class="form-control" required></div>
<div><label>Course</label><select name="course" class="form-control" required><option value="" disabled selected>Select Course</option><option value="IS">IS</option><option value="CS">CS</option><option value="SE">SE</option></select></div>
<div><label>Day</label><select name="day_name" class="form-control" required><option value="" disabled selected>Select Day</option><?php foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d): ?><option value="<?= $d ?>"><?= $d ?></option><?php endforeach; ?></select></div>
<div><label>Start Time</label><input type="time" name="start_time" class="form-control" required></div>
<div><label>End Time</label><input type="time" name="end_time" class="form-control" required></div>
<div><label>Room / Lab</label><input type="text" name="room" class="form-control" placeholder="e.g. S104" required></div>
<div><label>Semester</label><select name="semester" class="form-control" required><option value="" disabled selected>Select Semester</option><option value="Semester 1">Semester 1</option><option value="Semester 2">Semester 2</option></select></div>
<div><label>Year</label><select name="academic_year" class="form-control" required><option value="" disabled selected>Select Year</option><option value="1">1st Year</option><option value="2">2nd Year</option><option value="3">3rd Year</option><option value="4">4th Year</option></select></div>
</div><div class="button-area"><button type="submit" name="add_timetable" class="btn btn-primary">Add Timetable</button></div></form>
</div></div>

<!-- VIEW FILTER -->
<div class="card"><div class="card-body">
<div class="section-heading"><h2>View Timetable</h2><p>Select year and semester, then click View.</p></div>
<form method="GET"><div class="form-grid">
<div><label>Academic Year</label><select name="year" class="form-control" required><option value="" disabled <?= $viewYear === '' ? 'selected' : '' ?>>Select Year</option><?php for($y=1;$y<=4;$y++): ?><option value="<?= $y ?>" <?= $viewYear===(string)$y?'selected':'' ?>><?= $y ?><?= $y===1?'st':($y===2?'nd':($y===3?'rd':'th')) ?> Year</option><?php endfor; ?></select></div>
<div><label>Semester</label><select name="semester" class="form-control" required><option value="" disabled <?= $viewSemester === '' ? 'selected' : '' ?>>Select Semester</option><option value="Semester 1" <?= $viewSemester==='Semester 1'?'selected':'' ?>>Semester 1</option><option value="Semester 2" <?= $viewSemester==='Semester 2'?'selected':'' ?>>Semester 2</option></select></div>
</div><div class="button-area"><button type="submit" name="view" value="1" class="btn btn-primary">View</button></div></form>
</div></div>

<!-- UPDATE: room_schedules-style old/current + new/editable -->
<?php if ($editTimetable): ?>
<div class="card"><div class="card-body">
<div class="section-heading"><h2>Update Timetable</h2><p>Current details are shown first. Edit the details below.</p></div>
<form method="POST">

<div class="old-box">
<h3 class="box-title">Current Timetable Details</h3>
<div class="form-grid">
<div><label>Subject</label><input class="form-control readonly-control" value="<?= htmlspecialchars($editTimetable['subject_name']) ?>" readonly></div>
<div><label>Course</label><input class="form-control readonly-control" value="<?= htmlspecialchars($editTimetable['course']) ?>" readonly></div>
<div><label>Day</label><input class="form-control readonly-control" value="<?= htmlspecialchars($editTimetable['day_name']) ?>" readonly></div>
<div><label>Time</label><input class="form-control readonly-control" value="<?= htmlspecialchars(substr($editTimetable['start_time'],0,5).' - '.substr($editTimetable['end_time'],0,5)) ?>" readonly></div>
<div><label>Room / Lab</label><input class="form-control readonly-control" value="<?= htmlspecialchars($editTimetable['room']) ?>" readonly></div>
<div><label>Semester</label><input class="form-control readonly-control" value="<?= htmlspecialchars($editTimetable['semester']) ?>" readonly></div>
<div><label>Academic Year</label><input class="form-control readonly-control" value="<?= htmlspecialchars(['1'=>'1st Year','2'=>'2nd Year','3'=>'3rd Year','4'=>'4th Year'][(string)$editTimetable['academic_year']] ?? $editTimetable['academic_year']) ?>" readonly></div>
</div></div>

<div class="divider"></div>

<div class="new-box">
<h3 class="box-title">New Timetable Details</h3>
<input type="hidden" name="timetable_id" value="<?= (int)$editTimetable['id'] ?>">
<div class="form-grid">
<div><label>Subject</label><input type="text" name="subject_name" class="form-control" value="<?= htmlspecialchars($editTimetable['subject_name']) ?>" required></div>
<div><label>Course</label><select name="course" class="form-control" required><?php foreach(['IS','CS','SE'] as $c): ?><option value="<?= $c ?>" <?= $editTimetable['course']===$c?'selected':'' ?>><?= $c ?></option><?php endforeach; ?></select></div>
<div><label>Day</label><select name="day_name" class="form-control" required><?php foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d): ?><option value="<?= $d ?>" <?= $editTimetable['day_name']===$d?'selected':'' ?>><?= $d ?></option><?php endforeach; ?></select></div>
<div><label>Start Time</label><input type="time" name="start_time" class="form-control" value="<?= htmlspecialchars(substr($editTimetable['start_time'],0,5)) ?>" required></div>
<div><label>End Time</label><input type="time" name="end_time" class="form-control" value="<?= htmlspecialchars(substr($editTimetable['end_time'],0,5)) ?>" required></div>
<div><label>Room / Lab</label><input type="text" name="room" class="form-control" value="<?= htmlspecialchars($editTimetable['room']) ?>" required></div>
<div><label>Semester</label><select name="semester" class="form-control" required><option value="Semester 1" <?= $editTimetable['semester']==='Semester 1'?'selected':'' ?>>Semester 1</option><option value="Semester 2" <?= $editTimetable['semester']==='Semester 2'?'selected':'' ?>>Semester 2</option></select></div>
<div><label>Academic Year</label><select name="academic_year" class="form-control" required><?php for($y=1;$y<=4;$y++): ?><option value="<?= $y ?>" <?= (string)$editTimetable['academic_year']===(string)$y?'selected':'' ?>><?= $y ?><?= $y===1?'st':($y===2?'nd':($y===3?'rd':'th')) ?> Year</option><?php endfor; ?></select></div>
</div>
<div class="button-area"><button type="submit" name="update_timetable" class="btn btn-primary">Update</button></div>
</div>
</form>
<div class="button-area"><a href="timetable_records.php" class="btn btn-outline">Cancel</a></div>
</div></div>
<?php endif; ?>

<!-- ONLY SHOW RECORDS AFTER VIEW -->
<?php if ($viewRequested && $viewYear !== '' && $viewSemester !== ''): ?>
<div class="card"><div class="card-body">
<div class="section-heading"><h2>Timetable Records</h2><p><?= htmlspecialchars(['1'=>'1st Year','2'=>'2nd Year','3'=>'3rd Year','4'=>'4th Year'][$viewYear] ?? $viewYear) ?> - <?= htmlspecialchars($viewSemester) ?></p></div>
<?php if (empty($viewRows)): ?>
<div class="empty-state"><h3>No Timetable Records</h3><p>No records found for the selected year and semester.</p></div>
<?php else: ?>
<div class="table-wrapper"><table><thead><tr><th>Subject</th><th>Course</th><th>Day</th><th>Time</th><th>Room</th><th>Semester</th><th>Year</th><th>Actions</th></tr></thead><tbody>
<?php foreach($viewRows as $row): ?>
<tr>
<td><strong><?= htmlspecialchars($row['subject_name']) ?></strong></td>
<td><?= htmlspecialchars($row['course']) ?></td><td><?= htmlspecialchars($row['day_name']) ?></td>
<td><?= htmlspecialchars(substr($row['start_time'],0,5)) ?> - <?= htmlspecialchars(substr($row['end_time'],0,5)) ?></td>
<td><?= htmlspecialchars($row['room']) ?></td><td><?= htmlspecialchars($row['semester']) ?></td>
<td><?= htmlspecialchars(['1'=>'1st Year','2'=>'2nd Year','3'=>'3rd Year','4'=>'4th Year'][(string)$row['academic_year']] ?? $row['academic_year']) ?></td>
<td><div class="action-buttons">
<a href="timetable_records.php?edit=<?= (int)$row['id'] ?>&view=1&year=<?= urlencode($viewYear) ?>&semester=<?= urlencode($viewSemester) ?>" class="btn btn-primary">Edit</a>
<form method="POST" style="margin:0" onsubmit="return confirm('Are you sure you want to delete this timetable record?');"><input type="hidden" name="timetable_id" value="<?= (int)$row['id'] ?>"><button type="submit" name="delete_timetable" class="btn btn-danger">Delete</button></form>
</div></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></div>
<?php endif; ?>

</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
