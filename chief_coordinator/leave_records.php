<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_CHIEF_COORDINATOR);

$pageTitle = "Leave Records";
include __DIR__ . '/../includes/header.php';

$leaves = $pdo->query("
    SELECT lr.*, u.full_name 
    FROM leave_records lr
    JOIN instructors i ON lr.instructor_id = i.id
    JOIN users u ON i.user_id = u.id
    ORDER BY lr.created_at DESC LIMIT 50
")->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <h1 class="h2 mb-4"><i class="fas fa-calendar-alt me-2"></i>Leave Records (Monitoring)</h1>

            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Instructor</th>
                                <th>Dates</th>
                                <th>Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaves as $leave): ?>
                            <tr>
                                <td><?= htmlspecialchars($leave['full_name']) ?></td>
                                <td><?= formatDate($leave['start_date']) ?> → <?= formatDate($leave['end_date']) ?></td>
                                <td><?= htmlspecialchars($leave['leave_type']) ?></td>
                                <td><?= getStatusBadge($leave['status']) ?></td>
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