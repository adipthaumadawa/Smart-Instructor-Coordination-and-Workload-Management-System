<?php
/**
 * Coordinator - Handle Replacement Requests
 * Smart Instructor Coordination and Workload Management System
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php'; // Required for sic_user_avatar() in topbar
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_COORDINATOR);

$pageTitle = "Replacement Requests";

$success = '';
$error = '';

// Handle accept/reject — POST only, CSRF-protected, so the action can't be
// triggered by a plain link, a crawler, or a forged cross-site request.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    csrf_verify();

    $reqId = (int)$_POST['id'];
    $action = $_POST['action'];
    $newStatus = $action === 'accept' ? 'Accepted' : ($action === 'reject' ? 'Rejected' : null);

    if ($reqId <= 0 || $newStatus === null) {
        $_SESSION['error'] = 'Invalid request.';
    } else {
        // Only update if the request is still Pending — guards against double
        // submission (e.g. double-click) or two coordinators acting at once.
        $stmt = $pdo->prepare("UPDATE replacement_requests SET status = ?, responded_by = ?, responded_at = NOW() WHERE id = ? AND status = 'Pending'");
        $stmt->execute([$newStatus, $_SESSION['user_id'], $reqId]);

        if ($stmt->rowCount() > 0) {
            logActivity($_SESSION['user_id'], 'Replacement ' . $newStatus, "Replacement request #$reqId marked $newStatus");
            $_SESSION['success'] = "Replacement request $newStatus.";
        } else {
            $_SESSION['error'] = 'This request was already handled by someone else.';
        }
    }

    header('Location: replacements.php');
    exit;
}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Get pending replacements
$replacements = $pdo->query("
    SELECT rr.*, 
           u.full_name as requested_by_name,
           ta.scheduled_date, ta.start_time, ta.end_time,
           i1.employee_id as requesting_emp
    FROM replacement_requests rr
    JOIN instructors i1 ON rr.requested_by_instructor_id = i1.id
    JOIN users u ON i1.user_id = u.id
    JOIN task_assignments ta ON rr.task_assignment_id = ta.id
    WHERE rr.status = 'Pending'
    ORDER BY rr.created_at DESC
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1>Replacement Requests</h1>
                    <p>Review and respond to pending instructor replacement requests.</p>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5>Pending Requests</h5>
                    <span class="text-muted small"><?= count($replacements) ?> pending</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Requested By</th>
                                    <th>Task Date</th>
                                    <th>Reason</th>
                                    <th>Requested On</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($replacements)): ?>
                                    <tr><td colspan="5" class="text-muted">No pending replacement requests.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($replacements as $req): ?>
                                <tr>
                                    <td data-label="Requested By"><strong><?= htmlspecialchars($req['requested_by_name']) ?></strong> <span class="text-muted small">(<?= htmlspecialchars($req['requesting_emp']) ?>)</span></td>
                                    <td data-label="Task Date"><?= formatDate($req['scheduled_date']) ?><br><span class="small text-muted"><?= formatTime($req['start_time']) ?> - <?= formatTime($req['end_time']) ?></span></td>
                                    <td data-label="Reason"><?= htmlspecialchars($req['reason']) ?></td>
                                    <td data-label="Requested On"><?= formatDate($req['created_at']) ?></td>
                                    <td data-label="Actions" class="text-end">
                                        <form method="post" style="display:inline-block;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$req['id'] ?>">
                                            <input type="hidden" name="action" value="accept">
                                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Accept this replacement request?')">
                                                <span class="ui-dot" aria-hidden="true"></span>Accept
                                            </button>
                                        </form>
                                        <form method="post" style="display:inline-block;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$req['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this replacement request?')">
                                                <span class="ui-dot" aria-hidden="true"></span>Reject
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>