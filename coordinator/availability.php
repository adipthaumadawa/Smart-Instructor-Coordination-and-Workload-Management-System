<?php
/**
 * Coordinator - Instructor Availability
 * Smart Instructor Coordination and Workload Management System
 *
 * Shows only instructors who are active AND not on an approved leave for
 * today's date. Leave records are maintained by non-academic staff in
 * leave_records — this page reads directly from that table, so once staff
 * approve/log a leave for today, the instructor drops off this list
 * automatically.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_COORDINATOR);

$pageTitle = "Instructor Availability";
include __DIR__ . '/../includes/header.php';

$today = date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT i.*, u.full_name, ast.name as stream
    FROM instructors i
    JOIN users u ON i.user_id = u.id
    JOIN academic_streams ast ON i.academic_stream_id = ast.id
    WHERE i.status = 'active'
      AND NOT EXISTS (
          SELECT 1 FROM leave_records lr
          WHERE lr.instructor_id = i.id
            AND lr.status = 'Approved'
            AND :today BETWEEN lr.start_date AND lr.end_date
      )
    ORDER BY u.full_name
");
$stmt->execute([':today' => $today]);
$instructors = $stmt->fetchAll();
?>

            <div class="page-toolbar">
                <div>
                    <h1>Instructor Availability</h1>
                    <p>Instructors available today (<?= formatDate($today) ?>) — anyone on approved leave for today is hidden automatically.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>Available Today</h5>
                    <span class="text-muted small"><?= count($instructors) ?> instructors</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Instructor</th>
                                    <th>Stream</th>
                                    <th>Max Hours</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($instructors)): ?>
                                    <tr><td colspan="4" class="text-muted">No instructors are available today — everyone active is either inactive or on approved leave.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($instructors as $inst): ?>
                                <tr>
                                    <td data-label="Instructor"><strong><?= htmlspecialchars($inst['full_name']) ?></strong></td>
                                    <td data-label="Stream"><?= htmlspecialchars($inst['stream']) ?></td>
                                    <td data-label="Max Hours"><?= htmlspecialchars((string)$inst['max_weekly_hours']) ?> hrs</td>
                                    <td data-label="Action" class="text-end">
                                        <div class="menu-wrap">
                                            <button class="btn btn-sm btn-primary" type="button" data-menu-button="assignMenu<?= (int)$inst['id'] ?>" aria-expanded="false">
                                                <span class="ui-dot" aria-hidden="true"></span>Assign Task
                                            </button>
                                            <div class="dropdown-menu" id="assignMenu<?= (int)$inst['id'] ?>" hidden>
                                                <a href="<?= app_url('coordinator/additional_tasks.php') ?>?instructor_id=<?= (int)$inst['id'] ?>&instructor_name=<?= urlencode($inst['full_name']) ?>">
                                                    <strong>Additional Task</strong>
                                                    <small>Search a slot and assign a lecturer-requested task</small>
                                                </a>
                                                <a href="<?= app_url('coordinator/urgency_replacements.php') ?>?new_instructor_id=<?= (int)$inst['id'] ?>">
                                                    <strong>Urgency Replacement</strong>
                                                    <small>Reassign an existing task to this instructor now</small>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>