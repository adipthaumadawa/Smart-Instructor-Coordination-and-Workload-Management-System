<?php
/**
 * Project Coordinator - Presentation Panel Assignment
 * Smart Instructor Coordination and Workload Management System
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';

checkRole(ROLE_PROJECT_COORDINATOR);

$userId = (int)$_SESSION['user_id'];
$sessionId = (int)($_GET['session_id'] ?? 0);
$error = '';

if ($sessionId <= 0) {
    $_SESSION['error'] = 'No presentation session was specified.';
    header('Location: ' . app_url('project_coordinator/sessions.php'));
    exit;
}

$sessStmt = $pdo->prepare("SELECT * FROM presentation_sessions WHERE id = ?");
$sessStmt->execute([$sessionId]);
$session = $sessStmt->fetch();

if (!$session) {
    $_SESSION['error'] = 'Presentation session not found.';
    header('Location: ' . app_url('project_coordinator/sessions.php'));
    exit;
}

// ---------------------------------------------------------------
// Handle "Remove panel member" BEFORE header output
// ---------------------------------------------------------------
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $memberId = (int)$_GET['remove'];
    $memStmt = $pdo->prepare("
        SELECT ppm.*, u.id AS user_id FROM presentation_panel_members ppm
        JOIN instructors i ON ppm.instructor_id = i.id
        JOIN users u ON i.user_id = u.id
        WHERE ppm.id = ? AND ppm.presentation_session_id = ?
    ");
    $memStmt->execute([$memberId, $sessionId]);
    $member = $memStmt->fetch();

    if ($member) {
        $pdo->prepare("DELETE FROM presentation_panel_members WHERE id = ?")->execute([$memberId]);
        logActivity($userId, 'Remove Panel Member', "Removed panel member from session #{$sessionId}");
        createNotification($member['user_id'], 'Removed From Presentation Panel', "You have been removed from the panel for \"{$session['title']}\" on " . formatDate($session['session_date']) . ".", 'presentation', $sessionId);
        $_SESSION['success'] = 'Panel member removed.';
    } else {
        $_SESSION['error'] = 'Panel member not found.';
    }
    header('Location: ' . app_url('project_coordinator/panel.php?session_id=' . $sessionId));
    exit;
}

// ---------------------------------------------------------------
// Handle "Add panel member" BEFORE header output
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {
    $instructorId = (int)($_POST['instructor_id'] ?? 0);
    $role = sanitize($_POST['role_in_panel'] ?? 'Member');
    $validRoles = ['Chair', 'Member', 'Examiner'];

    if ($instructorId <= 0) {
        $error = 'Please select an instructor.';
    } elseif (!in_array($role, $validRoles, true)) {
        $error = 'Invalid panel role selected.';
    } else {
        $dupStmt = $pdo->prepare("SELECT id FROM presentation_panel_members WHERE presentation_session_id = ? AND instructor_id = ?");
        $dupStmt->execute([$sessionId, $instructorId]);

        if ($dupStmt->fetch()) {
            $error = 'This instructor is already on the panel for this session.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO presentation_panel_members (presentation_session_id, instructor_id, role_in_panel, assigned_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$sessionId, $instructorId, $role]);
                logActivity($userId, 'Assign Panel Member', "Assigned instructor #{$instructorId} as {$role} for session #{$sessionId}");

                $userStmt = $pdo->prepare("SELECT user_id FROM instructors WHERE id = ?");
                $userStmt->execute([$instructorId]);
                $instrUserId = $userStmt->fetchColumn();
                if ($instrUserId) {
                    createNotification($instrUserId, 'Presentation Panel Assignment', "You have been assigned as {$role} for \"{$session['title']}\" on " . formatDate($session['session_date']) . " at " . formatTime($session['start_time']) . ", venue: {$session['venue']}.", 'presentation', $sessionId);
                }

                $_SESSION['success'] = 'Panel member added and notified.';
                header('Location: ' . app_url('project_coordinator/panel.php?session_id=' . $sessionId));
                exit;
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// ---------------------------------------------------------------
// Current panel members
// ---------------------------------------------------------------
$panelStmt = $pdo->prepare("
    SELECT ppm.id, ppm.role_in_panel, ppm.assigned_at, i.id AS instructor_id, i.employee_id, u.full_name, ast.name AS stream_name
    FROM presentation_panel_members ppm
    JOIN instructors i ON ppm.instructor_id = i.id
    JOIN users u ON i.user_id = u.id
    LEFT JOIN academic_streams ast ON i.academic_stream_id = ast.id
    WHERE ppm.presentation_session_id = ?
    ORDER BY FIELD(ppm.role_in_panel,'Chair','Examiner','Member')
");
$panelStmt->execute([$sessionId]);
$panelMembers = $panelStmt->fetchAll();
$assignedIds = array_column($panelMembers, 'instructor_id');

// ---------------------------------------------------------------
// Suggested available instructors for this session's date/time
// (active, not on approved leave, no timetable/task conflict, not already on the panel)
// ---------------------------------------------------------------
$sql = "
    SELECT i.id, u.full_name, i.employee_id, i.designation, ast.name AS stream_name,
           (SELECT COALESCE(SUM(ta.duration_hours),0) FROM task_assignments ta
            WHERE ta.instructor_id = i.id AND ta.is_presentation_panel = 0
              AND ta.scheduled_date BETWEEN DATE_SUB(:d1, INTERVAL 7 DAY) AND :d2
              AND ta.status IN ('Assigned','Accepted','Completed')) AS recent_workload
    FROM instructors i
    JOIN users u ON i.user_id = u.id
    LEFT JOIN academic_streams ast ON i.academic_stream_id = ast.id
    WHERE i.status = 'active'
      AND NOT EXISTS (
          SELECT 1 FROM leave_records lr
          WHERE lr.instructor_id = i.id AND lr.status = 'Approved'
            AND :leave_date BETWEEN lr.start_date AND lr.end_date
      )
";
$params = [':d1' => $session['session_date'], ':d2' => $session['session_date'], ':leave_date' => $session['session_date']];

if (!empty($assignedIds)) {
    $exclNames = [];
    foreach ($assignedIds as $i => $aid) {
        $key = ':excl' . $i;
        $exclNames[] = $key;
        $params[$key] = $aid;
    }
    $sql .= " AND i.id NOT IN (" . implode(',', $exclNames) . ")";
}
$sql .= " ORDER BY recent_workload ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$candidateInstructors = $stmt->fetchAll();

// Filter out instructors with a timetable/task conflict at the exact session time
$availableInstructors = [];
foreach ($candidateInstructors as $c) {
    if (hasTimetableConflict($c['id'], $session['session_date'], $session['start_time'], $session['end_time'])) { continue; }
    if (hasTaskConflict($c['id'], $session['session_date'], $session['start_time'], $session['end_time'])) { continue; }
    $availableInstructors[] = $c;
}

$pageTitle = 'Panel Assignment';
include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1>Panel Assignment</h1>
                    <p><?= htmlspecialchars($session['title']) ?> — <?= formatDate($session['session_date']) ?>, <?= formatTime($session['start_time']) ?> - <?= formatTime($session['end_time']) ?> at <?= htmlspecialchars($session['venue']) ?></p>
                </div>
                <a href="<?= app_url('project_coordinator/sessions.php') ?>" class="btn btn-outline-primary">Back to Sessions</a>
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

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Current Panel</h5>
                            <span class="text-muted small"><?= count($panelMembers) ?> member(s)</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead><tr><th>Name</th><th>Role</th><th>Stream</th><th class="text-end">Actions</th></tr></thead>
                                    <tbody>
                                        <?php if (empty($panelMembers)): ?>
                                            <tr><td colspan="4" class="text-muted">No panel members assigned yet.</td></tr>
                                        <?php endif; ?>
                                        <?php foreach ($panelMembers as $pm): ?>
                                            <tr>
                                                <td data-label="Name"><?= htmlspecialchars($pm['full_name']) ?> <span class="text-muted small">(<?= htmlspecialchars($pm['employee_id']) ?>)</span></td>
                                                <td data-label="Role"><span class="badge bg-secondary"><?= htmlspecialchars($pm['role_in_panel']) ?></span></td>
                                                <td data-label="Stream"><?= htmlspecialchars($pm['stream_name'] ?? 'N/A') ?></td>
                                                <td data-label="Actions" class="text-end action-cell">
                                                    <a href="?session_id=<?= $sessionId ?>&remove=<?= (int)$pm['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this panel member?')">Remove</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header"><h5>Assign a Panel Member</h5></div>
                        <div class="card-body">
                            <?php if (empty($availableInstructors)): ?>
                                <p class="text-muted mb-0">No available instructors found for this exact date and time (all are either on leave or have a conflicting timetable/task).</p>
                            <?php else: ?>
                                <form method="POST" action="?session_id=<?= $sessionId ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Instructor <span class="text-danger">*</span></label>
                                        <select name="instructor_id" class="form-select" required>
                                            <option value="">Select an available instructor</option>
                                            <?php foreach ($availableInstructors as $ai): ?>
                                                <option value="<?= (int)$ai['id'] ?>">
                                                    <?= htmlspecialchars($ai['full_name']) ?> (<?= htmlspecialchars($ai['employee_id']) ?>) — <?= htmlspecialchars($ai['stream_name'] ?? 'N/A') ?>, <?= (float)$ai['recent_workload'] ?> hrs recent workload
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Panel Role <span class="text-danger">*</span></label>
                                        <select name="role_in_panel" class="form-select" required>
                                            <option value="Chair">Chair</option>
                                            <option value="Member" selected>Member</option>
                                            <option value="Examiner">Examiner</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="add_member" class="btn btn-primary">
                                        <span class="ui-dot" aria-hidden="true"></span>
                                        Add to Panel
                                    </button>
                                </form>
                            <?php endif; ?>
                            <p class="small text-muted mt-3 mb-0">Instructors are sorted by lowest recent workload first, and already exclude anyone on approved leave or with a clashing timetable/task at this exact time.</p>
                        </div>
                    </div>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
