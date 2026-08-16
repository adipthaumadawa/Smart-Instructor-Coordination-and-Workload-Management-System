<?php
/**
 * Project Coordinator - Presentation Sessions
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_PROJECT_COORDINATOR);

$pageTitle = "Presentation Sessions";
include __DIR__ . '/../includes/header.php';

$sessions = $pdo->prepare("
    SELECT ps.*, u.full_name as coordinator_name 
    FROM presentation_sessions ps
    JOIN users u ON ps.project_coordinator_id = u.id
    ORDER BY ps.session_date DESC
");
$sessions->execute();
$sessions = $sessions->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-presentation me-2"></i>Presentation Sessions</h1>
                <a href="schedule_session.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i> Schedule New Session
                </a>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Date & Time</th>
                                    <th>Venue</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sessions as $session): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($session['title']) ?></strong><br><small><?= htmlspecialchars($session['course_code']) ?></small></td>
                                    <td><?= formatDate($session['session_date']) ?><br><?= formatTime($session['start_time']) ?> - <?= formatTime($session['end_time']) ?></td>
                                    <td><?= htmlspecialchars($session['venue']) ?></td>
                                    <td><?= getStatusBadge($session['status']) ?></td>
                                    <td>
                                        <a href="panel_members.php?session_id=<?= $session['id'] ?>" class="btn btn-sm btn-outline-primary">Manage Panel</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($sessions)): ?>
                                <tr><td colspan="5" class="text-center text-muted">No presentation sessions found.</td></tr>
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