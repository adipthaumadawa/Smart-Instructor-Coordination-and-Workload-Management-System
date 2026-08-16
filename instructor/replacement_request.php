<?php
/**
 * Instructor - Replacement Requests
 * Smart Instructor Coordination and Workload Management System
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';

checkRole(ROLE_INSTRUCTOR);

$instructorId = sic_current_instructor_id();
if (!$instructorId) {
    $_SESSION['error'] = 'No instructor profile is linked to your account. Please contact the administrator.';
    header('Location: ' . app_url('instructor/dashboard.php'));
    exit;
}

$error = '';

// Handle Accept / Reject of a request where I am the suggested instructor.
// POST + CSRF protected so the decision can't be triggered by a plain link.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond'], $_POST['action'])) {
    csrf_verify();

    $reqId = (int)$_POST['respond'];
    $action = $_POST['action'] === 'accept' ? 'Accepted' : ($_POST['action'] === 'reject' ? 'Rejected' : null);

    if ($action) {
        $chk = $pdo->prepare("SELECT * FROM replacement_requests WHERE id = ? AND suggested_instructor_id = ? AND status = 'Pending'");
        $chk->execute([$reqId, $instructorId]);
        $reqRow = $chk->fetch();

        if ($reqRow) {
            try {
                $pdo->beginTransaction();

                $upd = $pdo->prepare("UPDATE replacement_requests SET status = ?, responded_by = ?, responded_at = NOW() WHERE id = ?");
                $upd->execute([$action, $_SESSION['user_id'], $reqId]);

                $requesterUserId = null;
                $requesterStmt = $pdo->prepare("SELECT user_id, id FROM instructors WHERE id = ?");
                $requesterStmt->execute([$reqRow['requested_by_instructor_id']]);
                $requesterRow = $requesterStmt->fetch();
                $requesterUserId = $requesterRow['user_id'] ?? null;

                if ($reqRow['leave_record_id']) {
                    // Leave-based request: this covers the requester's whole leave period,
                    // not just one task.
                    if ($action === 'Accepted') {
                        // The leave is now confirmed.
                        $confirmLeave = $pdo->prepare("UPDATE leave_records SET status = 'Approved' WHERE id = ?");
                        $confirmLeave->execute([$reqRow['leave_record_id']]);

                        // Reassign any of the requester's tasks that fall within the leave
                        // period to the accepting instructor. If they have none scheduled,
                        // nothing to reassign — the leave is still confirmed either way.
                        $leaveDates = $pdo->prepare("SELECT start_date, end_date FROM leave_records WHERE id = ?");
                        $leaveDates->execute([$reqRow['leave_record_id']]);
                        $ld = $leaveDates->fetch();
                        if ($ld) {
                            $reassign = $pdo->prepare("
                                UPDATE task_assignments
                                SET instructor_id = ?
                                WHERE instructor_id = ?
                                  AND status IN ('Assigned','Accepted')
                                  AND scheduled_date BETWEEN ? AND ?
                            ");
                            $reassign->execute([$instructorId, $reqRow['requested_by_instructor_id'], $ld['start_date'], $ld['end_date']]);
                        }

                        if ($requesterUserId) {
                            createNotification($requesterUserId, 'Leave Confirmed', ($_SESSION['full_name'] ?? 'An instructor') . " accepted to cover your leave. Your leave is now confirmed.", 'leave', $reqRow['leave_record_id']);
                        }
                    } else {
                        // Rejected — leave stays Pending; requester must pick another replacement.
                        if ($requesterUserId) {
                            createNotification($requesterUserId, 'Replacement Request Rejected', ($_SESSION['full_name'] ?? 'An instructor') . " declined to cover your leave. Please choose another replacement.", 'leave', $reqRow['leave_record_id']);
                        }
                    }
                } else {
                    // Task-based request (one specific task, not a full leave).
                    if ($action === 'Accepted' && $reqRow['task_assignment_id']) {
                        $reassign = $pdo->prepare("UPDATE task_assignments SET instructor_id = ? WHERE id = ?");
                        $reassign->execute([$instructorId, $reqRow['task_assignment_id']]);
                    }
                }

                logActivity($_SESSION['user_id'], 'Respond Replacement', "Replacement request #{$reqId} {$action}");

                // Notify coordinators of the outcome
                $notifyUsers = $pdo->prepare("SELECT id FROM users WHERE role_id IN (:coord, :chief) AND status = 'active'");
                $notifyUsers->execute([':coord' => ROLE_COORDINATOR, ':chief' => ROLE_CHIEF_COORDINATOR]);
                foreach ($notifyUsers->fetchAll(PDO::FETCH_COLUMN) as $uid) {
                    createNotification($uid, 'Replacement Response', "Replacement request #{$reqId} was {$action} by " . ($_SESSION['full_name'] ?? 'an instructor') . ".", 'replacement', $reqId);
                }

                $pdo->commit();
                $_SESSION['success'] = "Replacement request {$action}.";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $_SESSION['error'] = 'Database error: ' . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = 'Request not found or already handled.';
        }
    }
    header('Location: ' . app_url('instructor/replacement_request.php'));
    exit;
}

// Handle new replacement request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $taskId = (int)($_POST['task_assignment_id'] ?? 0);
    $reason = sanitize($_POST['reason'] ?? '');
    $suggestedId = (int)($_POST['suggested_instructor_id'] ?? 0);
    $suggestedId = $suggestedId > 0 ? $suggestedId : null;

    // Confirm the task belongs to this instructor and is still active
    $taskChk = $pdo->prepare("SELECT * FROM task_assignments WHERE id = ? AND instructor_id = ? AND status IN ('Assigned','Accepted')");
    $taskChk->execute([$taskId, $instructorId]);
    $taskRow = $taskChk->fetch();

    if (!$taskRow) {
        $error = 'Please select a valid, upcoming task of yours.';
    } elseif ($reason === '') {
        $error = 'Please provide a reason for the replacement request.';
    } elseif ($suggestedId === $instructorId) {
        $error = 'You cannot suggest yourself as the replacement.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO replacement_requests (task_assignment_id, requested_by_instructor_id, reason, suggested_instructor_id, status, created_at)
                VALUES (?, ?, ?, ?, 'Pending', NOW())
            ");
            $stmt->execute([$taskId, $instructorId, $reason, $suggestedId]);
            $newId = (int)$pdo->lastInsertId();

            logActivity($_SESSION['user_id'], 'Request Replacement', "Requested replacement for task assignment #{$taskId}");

            if ($suggestedId) {
                // Notify the suggested instructor directly
                $userStmt = $pdo->prepare("SELECT user_id FROM instructors WHERE id = ?");
                $userStmt->execute([$suggestedId]);
                $suggestedUserId = $userStmt->fetchColumn();
                if ($suggestedUserId) {
                    createNotification($suggestedUserId, 'Replacement Request', "You have been suggested as a replacement for a task on " . formatDate($taskRow['scheduled_date']) . ".", 'replacement', $newId);
                }
            } else {
                // Notify coordinators to find a suitable replacement
                $notifyUsers = $pdo->prepare("SELECT id FROM users WHERE role_id IN (:coord, :chief) AND status = 'active'");
                $notifyUsers->execute([':coord' => ROLE_COORDINATOR, ':chief' => ROLE_CHIEF_COORDINATOR]);
                foreach ($notifyUsers->fetchAll(PDO::FETCH_COLUMN) as $uid) {
                    createNotification($uid, 'Replacement Needed', ($_SESSION['full_name'] ?? 'An instructor') . " needs a replacement for a task on " . formatDate($taskRow['scheduled_date']) . ".", 'replacement', $newId);
                }
            }

            $_SESSION['success'] = 'Replacement request submitted successfully.';
            header('Location: ' . app_url('instructor/replacement_request.php'));
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// My upcoming tasks eligible for replacement
$eligibleStmt = $pdo->prepare("
    SELECT ta.id, ta.scheduled_date, ta.start_time, ta.end_time, COALESCE(atr.title, tt.name, 'Academic Task') AS task_title
    FROM task_assignments ta
    LEFT JOIN task_types tt ON ta.task_type_id = tt.id
    LEFT JOIN additional_task_requests atr ON ta.additional_task_request_id = atr.id
    WHERE ta.instructor_id = :iid AND ta.status IN ('Assigned','Accepted') AND ta.scheduled_date >= CURDATE()
    ORDER BY ta.scheduled_date ASC
");
$eligibleStmt->execute([':iid' => $instructorId]);
$eligibleTasks = $eligibleStmt->fetchAll();

// Other active instructors (for the suggestion dropdown)
$otherInstructors = array_values(array_filter(getAllActiveInstructors(), fn($i) => (int)$i['id'] !== $instructorId));

// My submitted requests (task-based AND leave-based)
$myReqStmt = $pdo->prepare("
    SELECT rr.*,
           COALESCE(ta.scheduled_date, lr.start_date) AS scheduled_date,
           ta.start_time, ta.end_time,
           lr.end_date AS leave_end_date,
           CASE WHEN rr.leave_record_id IS NOT NULL
                THEN CONCAT(lr.leave_type, ' Leave Cover')
                ELSE COALESCE(atr.title, tt.name, 'Academic Task')
           END AS task_title,
           su.full_name AS suggested_name
    FROM replacement_requests rr
    LEFT JOIN task_assignments ta ON rr.task_assignment_id = ta.id
    LEFT JOIN leave_records lr ON rr.leave_record_id = lr.id
    LEFT JOIN task_types tt ON ta.task_type_id = tt.id
    LEFT JOIN additional_task_requests atr ON ta.additional_task_request_id = atr.id
    LEFT JOIN instructors si ON rr.suggested_instructor_id = si.id
    LEFT JOIN users su ON si.user_id = su.id
    WHERE rr.requested_by_instructor_id = :iid
    ORDER BY rr.created_at DESC
");
$myReqStmt->execute([':iid' => $instructorId]);
$myRequests = $myReqStmt->fetchAll();

// Requests where I am the suggested replacement (task-based AND leave-based)
$forMeStmt = $pdo->prepare("
    SELECT rr.*,
           COALESCE(ta.scheduled_date, lr.start_date) AS scheduled_date,
           ta.start_time, ta.end_time,
           lr.end_date AS leave_end_date,
           CASE WHEN rr.leave_record_id IS NOT NULL
                THEN CONCAT(lr.leave_type, ' Leave Cover')
                ELSE COALESCE(atr.title, tt.name, 'Academic Task')
           END AS task_title,
           ru.full_name AS requester_name
    FROM replacement_requests rr
    LEFT JOIN task_assignments ta ON rr.task_assignment_id = ta.id
    LEFT JOIN leave_records lr ON rr.leave_record_id = lr.id
    LEFT JOIN task_types tt ON ta.task_type_id = tt.id
    LEFT JOIN additional_task_requests atr ON ta.additional_task_request_id = atr.id
    JOIN instructors ri ON rr.requested_by_instructor_id = ri.id
    JOIN users ru ON ri.user_id = ru.id
    WHERE rr.suggested_instructor_id = :iid
    ORDER BY rr.created_at DESC
");
$forMeStmt->execute([':iid' => $instructorId]);
$requestsForMe = $forMeStmt->fetchAll();

$preselectTask = isset($_GET['task_id']) ? (int)$_GET['task_id'] : 0;

$pageTitle = 'Replacement Requests';
include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1>Replacement Requests</h1>
                    <p>Request a replacement for a task you cannot perform, and respond to requests directed to you.</p>
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

            <div class="card" style="margin-bottom:20px;">
                <div class="card-header"><h5>New Replacement Request</h5></div>
                <div class="card-body">
                    <?php if (empty($eligibleTasks)): ?>
                        <p class="text-muted mb-0">You have no upcoming tasks eligible for a replacement request.</p>
                    <?php else: ?>
                        <form method="POST" action="">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Select Task <span class="text-danger">*</span></label>
                                    <select name="task_assignment_id" class="form-select" required>
                                        <option value="">Choose a task</option>
                                        <?php foreach ($eligibleTasks as $et): ?>
                                            <option value="<?= (int)$et['id'] ?>" <?= $preselectTask === (int)$et['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($et['task_title']) ?> — <?= formatDate($et['scheduled_date']) ?>, <?= formatTime($et['start_time']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Suggest a Replacement (optional)</label>
                                    <select name="suggested_instructor_id" class="form-select">
                                        <option value="">Let the coordinator decide</option>
                                        <?php foreach ($otherInstructors as $oi): ?>
                                            <option value="<?= (int)$oi['id'] ?>"><?= htmlspecialchars($oi['display_name']) ?> — <?= htmlspecialchars($oi['stream_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                                    <textarea name="reason" class="form-control" rows="3" required placeholder="Explain why you need a replacement"></textarea>
                                </div>
                            </div>
                            <button type="submit" name="submit_request" class="btn btn-primary mt-3">
                                <span class="ui-dot" aria-hidden="true"></span>
                                Submit Request
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($requestsForMe)): ?>
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header">
                    <h5>Requests Suggesting You as Replacement</h5>
                    <span class="text-muted small"><?= count($requestsForMe) ?> request(s)</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Task / Leave</th><th>Period</th><th>Requested By</th><th>Reason</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($requestsForMe as $r): ?>
                                    <tr>
                                        <td data-label="Task / Leave"><?= htmlspecialchars($r['task_title']) ?></td>
                                        <td data-label="Period">
                                            <?= formatDate($r['scheduled_date']) ?>
                                            <?php if (!empty($r['leave_end_date']) && $r['leave_end_date'] !== $r['scheduled_date']): ?>
                                                &ndash; <?= formatDate($r['leave_end_date']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Requested By"><?= htmlspecialchars($r['requester_name']) ?></td>
                                        <td data-label="Reason"><?= htmlspecialchars($r['reason']) ?></td>
                                        <td data-label="Status"><?= getStatusBadge($r['status']) ?></td>
                                        <td data-label="Actions" class="text-end action-cell">
                                            <?php if ($r['status'] === 'Pending'): ?>
                                                <form method="POST" action="" style="display:inline-block;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="respond" value="<?= (int)$r['id'] ?>">
                                                    <input type="hidden" name="action" value="accept">
                                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Accept this replacement request?')">Accept</button>
                                                </form>
                                                <form method="POST" action="" style="display:inline-block;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="respond" value="<?= (int)$r['id'] ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this replacement request?')">Reject</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5>My Submitted Requests</h5>
                    <span class="text-muted small"><?= count($myRequests) ?> request(s)</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Task / Leave</th><th>Period</th><th>Suggested Instructor</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php if (empty($myRequests)): ?>
                                    <tr><td colspan="4" class="text-muted">No replacement requests submitted yet.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($myRequests as $r): ?>
                                    <tr>
                                        <td data-label="Task / Leave"><?= htmlspecialchars($r['task_title']) ?></td>
                                        <td data-label="Period">
                                            <?= formatDate($r['scheduled_date']) ?>
                                            <?php if (!empty($r['leave_end_date']) && $r['leave_end_date'] !== $r['scheduled_date']): ?>
                                                &ndash; <?= formatDate($r['leave_end_date']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Suggested"><?= htmlspecialchars($r['suggested_name'] ?? 'Coordinator to decide') ?></td>
                                        <td data-label="Status"><?= getStatusBadge($r['status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>