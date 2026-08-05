<?php
/**
 * Coordinator - Instructor Availability
 * Smart Instructor Coordination and Workload Management System
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_COORDINATOR);

$pageTitle = "Instructor Availability";
include __DIR__ . '/../includes/header.php';

$instructors = $pdo->query("
    SELECT i.*, u.full_name, ast.name as stream
    FROM instructors i
    JOIN users u ON i.user_id = u.id
    JOIN academic_streams ast ON i.academic_stream_id = ast.id
    WHERE i.status = 'active'
    ORDER BY u.full_name
")->fetchAll();
?>

            <div class="page-toolbar">
                <div>
                    <h1>Instructor Availability</h1>
                    <p>Current status and weekly capacity for all active instructors.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>Active Instructors</h5>
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
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($instructors)): ?>
                                    <tr><td colspan="4" class="text-muted">No active instructors found.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($instructors as $inst): ?>
                                <tr>
                                    <td data-label="Instructor"><strong><?= htmlspecialchars($inst['full_name']) ?></strong></td>
                                    <td data-label="Stream"><?= htmlspecialchars($inst['stream']) ?></td>
                                    <td data-label="Max Hours"><?= $inst['max_weekly_hours'] ?> hrs</td>
                                    <td data-label="Status"><?= getStatusBadge($inst['status']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>