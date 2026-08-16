<?php
/**
 * Project Coordinator - Presentation Sessions & Venue Booking
 * Smart Instructor Coordination and Workload Management System
 *
 * Venue scheduling is handled here directly: creating a session checks
 * real room availability (lecture_hall_bookings) and books the chosen
 * room at the same time as the session is created, so the two can
 * never go out of sync.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';

checkRole(ROLE_PROJECT_COORDINATOR);

$error = '';
$userId = (int)$_SESSION['user_id'];

// ---------------------------------------------------------------
// Handle "Cancel session" BEFORE header output
// ---------------------------------------------------------------
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $sessionId = (int)$_GET['cancel'];
    $stmt = $pdo->prepare("SELECT * FROM presentation_sessions WHERE id = ? AND status = 'Scheduled'");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();

    if ($session) {
        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE presentation_sessions SET status = 'Cancelled' WHERE id = ?")->execute([$sessionId]);

            // Free up the matching room booking, if one exists
            $roomStmt = $pdo->prepare("SELECT id FROM lecture_rooms WHERE room_name = ?");
            $roomStmt->execute([$session['venue']]);
            $roomId = $roomStmt->fetchColumn();
            if ($roomId) {
                $bookStmt = $pdo->prepare("
                    UPDATE lecture_hall_bookings SET status = 'Cancelled'
                    WHERE room_id = ? AND booking_date = ? AND start_time = ? AND end_time = ? AND status = 'Confirmed'
                ");
                $bookStmt->execute([$roomId, $session['session_date'], $session['start_time'], $session['end_time']]);
            }

            $pdo->commit();
            logActivity($userId, 'Cancel Presentation Session', "Cancelled session #{$sessionId}: {$session['title']}");

            // Notify assigned panel members
            $panelStmt = $pdo->prepare("
                SELECT u.id FROM presentation_panel_members ppm
                JOIN instructors i ON ppm.instructor_id = i.id
                JOIN users u ON i.user_id = u.id
                WHERE ppm.presentation_session_id = ?
            ");
            $panelStmt->execute([$sessionId]);
            foreach ($panelStmt->fetchAll(PDO::FETCH_COLUMN) as $panelUserId) {
                createNotification($panelUserId, 'Presentation Session Cancelled', "The session \"{$session['title']}\" on " . formatDate($session['session_date']) . " has been cancelled.", 'presentation', $sessionId);
            }

            $_SESSION['success'] = 'Session cancelled and venue released.';
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Could not cancel the session: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = 'Session not found or already cancelled.';
    }
    header('Location: ' . app_url('project_coordinator/sessions.php'));
    exit;
}

// ---------------------------------------------------------------
// Handle "Create session" submission BEFORE header output
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_session'])) {
    $title = sanitize($_POST['title'] ?? '');
    $courseCode = sanitize($_POST['course_code'] ?? '');
    $date = sanitize($_POST['session_date'] ?? '');
    $start = sanitize($_POST['start_time'] ?? '');
    $end = sanitize($_POST['end_time'] ?? '');
    $roomId = (int)($_POST['room_id'] ?? 0);

    if ($title === '' || $date === '' || $start === '' || $end === '' || $roomId <= 0) {
        $error = 'Please fill all required fields and select a venue.';
    } elseif (strtotime($end) <= strtotime($start)) {
        $error = 'End time must be after the start time.';
    } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
        $error = 'Session date cannot be in the past.';
    } else {
        $roomStmt = $pdo->prepare("SELECT room_name FROM lecture_rooms WHERE id = ? AND status = 'Available'");
        $roomStmt->execute([$roomId]);
        $roomName = $roomStmt->fetchColumn();

        if (!$roomName) {
            $error = 'Selected venue is invalid.';
        } elseif (hasBookingConflict($roomId, $date, $start, $end)) {
            $error = 'That venue was just booked for this time slot by someone else. Please check availability again.';
        } else {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO presentation_sessions (title, course_code, session_date, start_time, end_time, venue, project_coordinator_id, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Scheduled', NOW())
                ");
                $stmt->execute([$title, $courseCode, $date, $start, $end, $roomName, $userId]);
                $sessionId = (int)$pdo->lastInsertId();

                $bookStmt = $pdo->prepare("
                    INSERT INTO lecture_hall_bookings (room_id, booked_by_user_id, booking_date, start_time, end_time, purpose, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'Confirmed', NOW())
                ");
                $bookStmt->execute([$roomId, $userId, $date, $start, $end, "Presentation Session: {$title}"]);

                $pdo->commit();
                logActivity($userId, 'Create Presentation Session', "Created session: {$title} on {$date} at {$roomName}");
                $_SESSION['success'] = 'Presentation session created and venue booked successfully. Now assign a panel.';
                header('Location: ' . app_url('project_coordinator/panel.php?session_id=' . $sessionId));
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Could not create the session: ' . $e->getMessage();
            }
        }
    }
}

// ---------------------------------------------------------------
// Step 1: Check venue availability (GET, no side effects)
// ---------------------------------------------------------------
$checkDate = sanitize($_GET['date'] ?? '');
$checkStart = sanitize($_GET['start_time'] ?? '');
$checkEnd = sanitize($_GET['end_time'] ?? '');
$availableRooms = [];
$showCreateForm = false;

if (isset($_GET['check_availability']) && $checkDate !== '' && $checkStart !== '' && $checkEnd !== '') {
    if (strtotime($checkEnd) <= strtotime($checkStart)) {
        $error = 'End time must be after the start time.';
    } else {
        $availableRooms = getAvailableRooms($checkDate, $checkStart, $checkEnd);
        $showCreateForm = true;
    }
}

// ---------------------------------------------------------------
// Session list
// ---------------------------------------------------------------
$view = $_GET['view'] ?? 'upcoming';
$allowedViews = ['upcoming', 'past', 'cancelled', 'all'];
if (!in_array($view, $allowedViews, true)) { $view = 'upcoming'; }

$sql = "
    SELECT ps.*, u.full_name AS coordinator_name,
           (SELECT COUNT(*) FROM presentation_panel_members ppm WHERE ppm.presentation_session_id = ps.id) AS panel_count
    FROM presentation_sessions ps
    LEFT JOIN users u ON ps.project_coordinator_id = u.id
    WHERE 1=1
";
if ($view === 'upcoming') {
    $sql .= " AND ps.session_date >= CURDATE() AND ps.status = 'Scheduled'";
} elseif ($view === 'past') {
    $sql .= " AND (ps.session_date < CURDATE() OR ps.status = 'Completed')";
} elseif ($view === 'cancelled') {
    $sql .= " AND ps.status = 'Cancelled'";
}
$sql .= " ORDER BY ps.session_date ASC, ps.start_time ASC";
$sessions = $pdo->query($sql)->fetchAll();

$pageTitle = 'Presentation Sessions';
include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1>Presentation Sessions</h1>
                    <p>Create presentation sessions with an integrated venue check, then assign an evaluation panel.</p>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- STEP 1: Check venue availability -->
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header"><h5>Step 1 — Check Venue Availability</h5></div>
                <div class="card-body">
                    <form method="GET" action="">
                        <input type="hidden" name="check_availability" value="1">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control" required min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($checkDate) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Start Time <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" class="form-control" required value="<?= htmlspecialchars($checkStart) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">End Time <span class="text-danger">*</span></label>
                                <input type="time" name="end_time" class="form-control" required value="<?= htmlspecialchars($checkEnd) ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary" style="width:100%;">Check</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- STEP 2: Available venues + create session -->
            <?php if ($showCreateForm): ?>
                <div class="card" style="margin-bottom:20px;">
                    <div class="card-header"><h5>Step 2 — Select Venue & Create Session</h5></div>
                    <div class="card-body">
                        <?php if (empty($availableRooms)): ?>
                            <p class="text-muted mb-0">No lecture halls or laboratories are free for <?= formatDate($checkDate) ?>, <?= formatTime($checkStart) ?> - <?= formatTime($checkEnd) ?>. Try a different time slot.</p>
                        <?php else: ?>
                            <form method="POST" action="">
                                <input type="hidden" name="session_date" value="<?= htmlspecialchars($checkDate) ?>">
                                <input type="hidden" name="start_time" value="<?= htmlspecialchars($checkStart) ?>">
                                <input type="hidden" name="end_time" value="<?= htmlspecialchars($checkEnd) ?>">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Session Title <span class="text-danger">*</span></label>
                                        <input type="text" name="title" class="form-control" required placeholder="e.g. Group Project I Proposal Defense">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Course Code</label>
                                        <input type="text" name="course_code" class="form-control" placeholder="e.g. SCS2202">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Venue <span class="text-danger">*</span></label>
                                        <select name="room_id" class="form-select" required>
                                            <option value="">Select an available venue</option>
                                            <?php foreach ($availableRooms as $room): ?>
                                                <option value="<?= (int)$room['id'] ?>">
                                                    <?= htmlspecialchars($room['room_name']) ?> — <?= htmlspecialchars($room['room_type']) ?> (Capacity <?= (int)$room['capacity'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Date &amp; Time</label>
                                        <input type="text" class="form-control" disabled value="<?= formatDate($checkDate) ?>, <?= formatTime($checkStart) ?> - <?= formatTime($checkEnd) ?>">
                                    </div>
                                </div>
                                <button type="submit" name="create_session" class="btn btn-primary mt-3">
                                    <span class="ui-dot" aria-hidden="true"></span>
                                    Create Session & Book Venue
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Session list -->
            <div class="card" style="margin-bottom:16px;">
                <div class="card-body d-flex gap-2 flex-wrap">
                    <a href="?view=upcoming" class="btn btn-sm <?= $view === 'upcoming' ? 'btn-primary' : 'btn-outline-secondary' ?>">Upcoming</a>
                    <a href="?view=past" class="btn btn-sm <?= $view === 'past' ? 'btn-primary' : 'btn-outline-secondary' ?>">Past / Completed</a>
                    <a href="?view=cancelled" class="btn btn-sm <?= $view === 'cancelled' ? 'btn-primary' : 'btn-outline-secondary' ?>">Cancelled</a>
                    <a href="?view=all" class="btn btn-sm <?= $view === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>Sessions</h5>
                    <span class="text-muted small"><?= count($sessions) ?> session(s)</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr><th>Title</th><th>Course</th><th>Date</th><th>Time</th><th>Venue</th><th>Panel</th><th>Status</th><th class="text-end">Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($sessions)): ?>
                                    <tr><td colspan="8" class="text-muted">No sessions found for this filter.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($sessions as $s): ?>
                                    <tr>
                                        <td data-label="Title"><strong><?= htmlspecialchars($s['title']) ?></strong></td>
                                        <td data-label="Course"><?= htmlspecialchars($s['course_code'] ?: 'N/A') ?></td>
                                        <td data-label="Date"><?= formatDate($s['session_date']) ?></td>
                                        <td data-label="Time"><?= formatTime($s['start_time']) ?> - <?= formatTime($s['end_time']) ?></td>
                                        <td data-label="Venue"><?= htmlspecialchars($s['venue'] ?: 'N/A') ?></td>
                                        <td data-label="Panel"><?= (int)$s['panel_count'] ?> member(s)</td>
                                        <td data-label="Status"><?= getStatusBadge($s['status']) ?></td>
                                        <td data-label="Actions" class="text-end action-cell">
                                            <a href="<?= app_url('project_coordinator/panel.php?session_id=' . (int)$s['id']) ?>" class="btn btn-sm btn-outline-primary">Manage Panel</a>
                                            <?php if ($s['status'] === 'Scheduled'): ?>
                                                <a href="?cancel=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this session and release the venue?')">Cancel</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
