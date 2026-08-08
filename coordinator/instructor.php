<?php
/**
 * Coordinator - All Instructors
 * Smart Instructor Coordination and Workload Management System
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_COORDINATOR);

$pageTitle = "All Instructors";
include __DIR__ . '/../includes/header.php';

$instructors = $pdo->query("
    SELECT i.*, u.full_name, u.email, ast.name as stream, d.name as department
    FROM instructors i
    JOIN users u ON i.user_id = u.id
    JOIN academic_streams ast ON i.academic_stream_id = ast.id
    JOIN departments d ON i.department_id = d.id
    ORDER BY u.full_name
")->fetchAll();
?>

            <div class="page-toolbar">
                <div>
                    <h1>All Instructors</h1>
                    <p>Directory of every instructor registered in the system.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>Instructor Directory</h5>
                    <span class="text-muted small"><?= count($instructors) ?> instructors</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Stream</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($instructors)): ?>
                                    <tr><td colspan="5" class="text-muted">No instructors found.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($instructors as $inst): ?>
                                <tr>
                                    <td data-label="Name"><strong><?= htmlspecialchars($inst['full_name']) ?></strong></td>
                                    <td data-label="Email"><?= htmlspecialchars($inst['email']) ?></td>
                                    <td data-label="Stream"><?= htmlspecialchars($inst['stream']) ?></td>
                                    <td data-label="Department"><?= htmlspecialchars($inst['department']) ?></td>
                                    <td data-label="Status"><?= getStatusBadge($inst['status']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>