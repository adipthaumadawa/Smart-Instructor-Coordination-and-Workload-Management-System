<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_COORDINATOR);

$pageTitle = "All Instructors";
include __DIR__ . '/../includes/header.php';

// Fetch instructors registered in the system along with stream and department details
$instructors = $pdo->query("
    SELECT i.*, u.full_name, u.email, ast.name as stream, d.name as department
    FROM instructors i
    JOIN users u ON i.user_id = u.id
    JOIN academic_streams ast ON i.academic_stream_id = ast.id
    JOIN departments d ON i.department_id = d.id
    ORDER BY u.full_name
")->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2 mb-0">
                    <i class="fas fa-chalkboard-teacher me-2 text-primary"></i>All Instructors
                </h1>
                <span class="badge bg-secondary fs-6">Total: <?= count($instructors) ?></span>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="ps-3">Name</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Stream</th>
                                    <th scope="col">Department</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($instructors)): ?>
                                    <?php foreach ($instructors as $inst): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <strong><?= htmlspecialchars($inst['full_name']) ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($inst['email']) ?></td>
                                        <td><?= htmlspecialchars($inst['stream']) ?></td>
                                        <td><?= htmlspecialchars($inst['department']) ?></td>
                                        <td><?= getStatusBadge($inst['status']) ?></td>
                                        <td class="text-end pe-3">
                                            <a href="view_instructor.php?id=<?= $inst['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="View Workload">
                                                <i class="fas fa-eye"></i> View Workload
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="fas fa-info-circle me-1"></i> No instructors found in the system.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>