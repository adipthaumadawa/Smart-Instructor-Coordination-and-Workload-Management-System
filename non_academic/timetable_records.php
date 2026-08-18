<?php
/**
 * Non-Academic Staff - Timetable Requirements
 * Smart Instructor Coordination and Workload Management System
 *
 * This is where the semester timetable the academic department hands
 * over gets entered: day/time/subject/location and how many instructors
 * that slot needs. No instructor is picked here — as soon as a
 * requirement is saved, the system automatically tries to fill it with
 * the best-ranked available instructors (see autoAssignTimetableRequirement()
 * in includes/functions.php). The Instructor Coordinator can review and
 * adjust those assignments on the "Timetable Requirements" page.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php'; // Required for sic_user_avatar() in navbar.php
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_NON_ACADEMIC);
$pageTitle = "Timetable Requirements";
include __DIR__ . '/../includes/header.php';

$autoAssignMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $requiredInstructors = max(1, (int)($_POST['required_instructors'] ?? 1));
    $streamId = !empty($_POST['academic_stream_id']) ? (int)$_POST['academic_stream_id'] : null;

    $stmt = $pdo->prepare("
        INSERT INTO timetable_requirements
            (day_of_week, start_time, end_time, subject, location, academic_stream_id, required_instructors, semester, academic_year, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        sanitize($_POST['day_of_week']),
        $_POST['start_time'],
        $_POST['end_time'],
        sanitize($_POST['subject']),
        sanitize($_POST['location']),
        $streamId,
        $requiredInstructors,
        sanitize($_POST['semester']),
        sanitize($_POST['academic_year'])
    ]);
    $newRequirementId = (int)$pdo->lastInsertId();

    logActivity($_SESSION['user_id'], 'Timetable Requirement', 'Posted timetable requirement: ' . $_POST['subject']);

    // Try to auto-fill it immediately.
    $filled = autoAssignTimetableRequirement($newRequirementId);
    $_SESSION['flash_timetable_msg'] = $filled >= $requiredInstructors
        ? "Requirement posted and fully staffed automatically ($filled instructor" . ($filled === 1 ? '' : 's') . " assigned)."
        : "Requirement posted. Only $filled of $requiredInstructors instructor(s) could be auto-assigned — the coordinator will need to fill the rest.";

    header('Location: timetable_records.php');
    exit;
}

if (!empty($_SESSION['flash_timetable_msg'])) {
    $autoAssignMessage = $_SESSION['flash_timetable_msg'];
    unset($_SESSION['flash_timetable_msg']);
}

$streams = $pdo->query("SELECT * FROM academic_streams ORDER BY name")->fetchAll();

$requirements = $pdo->query("
    SELECT tr.*, ast.name AS stream_name,
           (SELECT COUNT(*) FROM timetable_slots ts WHERE ts.requirement_id = tr.id) AS assigned_count,
           (SELECT GROUP_CONCAT(u.full_name SEPARATOR ', ')
              FROM timetable_slots ts
              JOIN instructors i ON ts.instructor_id = i.id
              JOIN users u ON i.user_id = u.id
             WHERE ts.requirement_id = tr.id) AS assigned_names
    FROM timetable_requirements tr
    LEFT JOIN academic_streams ast ON tr.academic_stream_id = ast.id
    ORDER BY FIELD(tr.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), tr.start_time
")->fetchAll();
?>

            <div class="page-toolbar">
                <div>
                    <h1>Timetable Requirements</h1>
                    <p>Enter the semester timetable from the academic department. The system automatically assigns instructors to each slot based on workload, qualification and availability &mdash; the Instructor Coordinator can adjust any assignment afterwards.</p>
                </div>
            </div>

            <?php if ($autoAssignMessage): ?>
                <div class="alert alert-info"><?= htmlspecialchars($autoAssignMessage) ?></div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header"><strong>Post a Timetable Requirement</strong></div>
                <div class="card-body">
                    <form method="post" class="row g-3">
                        <?= csrf_field() ?>
                        <div class="col-md-2">
                            <select name="day_of_week" class="form-select" required>
                                <option>Monday</option><option>Tuesday</option><option>Wednesday</option>
                                <option>Thursday</option><option>Friday</option><option>Saturday</option><option>Sunday</option>
                            </select>
                        </div>
                        <div class="col-md-2"><input type="time" name="start_time" class="form-control" required></div>
                        <div class="col-md-2"><input type="time" name="end_time" class="form-control" required></div>
                        <div class="col-md-3"><input name="subject" class="form-control" placeholder="Lecture / subject" required></div>
                        <div class="col-md-3"><input name="location" class="form-control" placeholder="Room"></div>

                        <div class="col-md-3">
                            <select name="academic_stream_id" class="form-select">
                                <option value="">Any stream (no restriction)</option>
                                <?php foreach ($streams as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="required_instructors" class="form-control" min="1" value="1" placeholder="Instructors needed" required>
                        </div>
                        <div class="col-md-2"><input name="semester" class="form-control" value="Semester 1"></div>
                        <div class="col-md-2"><input name="academic_year" class="form-control" value="2025/2026"></div>
                        <div class="col-md-3"><button class="btn btn-primary w-100">Save &amp; Auto-Assign</button></div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>Posted Requirements</h5>
                    <span class="text-muted small"><?= count($requirements) ?> requirements</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Day</th><th>Time</th><th>Subject</th><th>Location</th>
                                    <th>Stream</th><th>Needed</th><th>Assigned</th><th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($requirements)): ?>
                                    <tr><td colspan="8" class="text-muted">No timetable requirements posted yet.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($requirements as $r): ?>
                                <tr>
                                    <td data-label="Day"><?= htmlspecialchars($r['day_of_week']) ?></td>
                                    <td data-label="Time"><?= formatTime($r['start_time']) ?> - <?= formatTime($r['end_time']) ?></td>
                                    <td data-label="Subject"><?= htmlspecialchars($r['subject']) ?></td>
                                    <td data-label="Location"><?= htmlspecialchars($r['location']) ?></td>
                                    <td data-label="Stream"><?= htmlspecialchars($r['stream_name'] ?? 'Any') ?></td>
                                    <td data-label="Needed"><?= (int)$r['required_instructors'] ?></td>
                                    <td data-label="Assigned"><?= $r['assigned_names'] ? htmlspecialchars($r['assigned_names']) : '<span class="text-muted small">None yet</span>' ?></td>
                                    <td data-label="Status"><?= getStatusBadge($r['status']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-muted small mt-2">Requirements shown as <em>Open</em> or <em>Partially Staffed</em> still need attention from the Instructor Coordinator.</p>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
