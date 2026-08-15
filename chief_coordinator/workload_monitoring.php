<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_CHIEF_COORDINATOR);

$pageTitle = "Workload Monitoring";
include __DIR__ . '/../includes/header.php';

$workloads = $pdo->query("
    SELECT i.id, u.full_name, 
           COALESCE(SUM(ta.duration_hours), 0) as total_hours
    FROM instructors i
    JOIN users u ON i.user_id = u.id
    LEFT JOIN task_assignments ta ON i.id = ta.instructor_id AND ta.is_presentation_panel = 0
    WHERE i.status = 'active'
    GROUP BY i.id
    ORDER BY total_hours DESC
")->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <h1 class="h2 mb-4"><i class="fas fa-chart-bar me-2"></i>Workload Monitoring</h1>

            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Instructor</th>
                                <th>Total Workload (hrs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($workloads as $w): ?>
                            <tr>
                                <td><?= htmlspecialchars($w['full_name']) ?></td>
                                <td><?= number_format($w['total_hours'], 1) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>