<?php
/**
 * Instructor - Workload Summary
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

// Instructor's own max weekly hours (falls back to system default)
$stmt = $pdo->prepare("SELECT max_weekly_hours FROM instructors WHERE id = ?");
$stmt->execute([$instructorId]);
$maxWeekly = (float)($stmt->fetchColumn() ?: DEFAULT_MAX_WEEKLY_HOURS);

// This week's workload (Mon-Sun containing today)
$weekStart = date('Y-m-d', strtotime('monday this week'));
$weekEnd = date('Y-m-d', strtotime('sunday this week'));
$weekHours = calculateWorkload($instructorId, $weekStart, $weekEnd);
$weekPercent = $maxWeekly > 0 ? min(100, round(($weekHours / $maxWeekly) * 100, 1)) : 0;

// This month's workload
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');
$monthHours = calculateWorkload($instructorId, $monthStart, $monthEnd);

// Breakdown by task type for this month
$stmt2 = $pdo->prepare("
    SELECT tt.name AS type_name, COUNT(ta.id) AS task_count, COALESCE(SUM(ta.duration_hours),0) AS total_hours
    FROM task_assignments ta
    JOIN task_types tt ON ta.task_type_id = tt.id
    WHERE ta.instructor_id = :iid
      AND ta.is_presentation_panel = 0
      AND ta.status IN ('Assigned','Accepted','Completed')
      AND ta.scheduled_date BETWEEN :from_date AND :to_date
    GROUP BY tt.id, tt.name
    ORDER BY total_hours DESC
");
$stmt2->execute([':iid' => $instructorId, ':from_date' => $monthStart, ':to_date' => $monthEnd]);
$breakdown = $stmt2->fetchAll();

// Daily hours this week (for the mini bar view)
$stmt3 = $pdo->prepare("
    SELECT scheduled_date, COALESCE(SUM(duration_hours),0) AS hours
    FROM task_assignments
    WHERE instructor_id = :iid
      AND is_presentation_panel = 0
      AND status IN ('Assigned','Accepted','Completed')
      AND scheduled_date BETWEEN :from_date AND :to_date
    GROUP BY scheduled_date
");
$stmt3->execute([':iid' => $instructorId, ':from_date' => $weekStart, ':to_date' => $weekEnd]);
$dailyRaw = $stmt3->fetchAll(PDO::FETCH_KEY_PAIR);
$dailyHours = [];
for ($i = 0; $i < 7; $i++) {
    $d = date('Y-m-d', strtotime($weekStart . " +{$i} day"));
    $dailyHours[$d] = (float)($dailyRaw[$d] ?? 0);
}
$maxDay = max(array_merge($dailyHours, [1]));

$pageTitle = 'Workload Summary';
include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1>Workload Summary</h1>
                    <p>Your teaching and task workload, calculated from assigned academic hours.</p>
                </div>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <div class="row g-4" style="margin-bottom:4px;">
                <div class="col-md-4">
                    <div class="card"><div class="card-body">
                        <p class="text-muted small mb-1">This Week</p>
                        <h3 class="mb-0"><?= $weekHours ?> / <?= $maxWeekly ?> hrs</h3>
                        <p class="small mt-1"><?= $weekPercent ?>% of your weekly capacity</p>
                    </div></div>
                </div>
                <div class="col-md-4">
                    <div class="card"><div class="card-body">
                        <p class="text-muted small mb-1">This Month</p>
                        <h3 class="mb-0"><?= $monthHours ?> hrs</h3>
                        <p class="small mt-1">Total assigned academic hours</p>
                    </div></div>
                </div>
                <div class="col-md-4">
                    <div class="card"><div class="card-body">
                        <p class="text-muted small mb-1">Status</p>
                        <h3 class="mb-0">
                            <?php if ($weekPercent >= 90): ?>
                                <span class="badge bg-danger">Overloaded</span>
                            <?php elseif ($weekPercent >= 70): ?>
                                <span class="badge bg-warning">High</span>
                            <?php else: ?>
                                <span class="badge bg-success">Balanced</span>
                            <?php endif; ?>
                        </h3>
                        <p class="small mt-1">Based on this week's load</p>
                    </div></div>
                </div>
            </div>

            <p class="small text-muted" style="margin-bottom:16px;">
                Note: Presentation panel duties are not counted toward normal workload calculations.
            </p>

            <div class="card" style="margin-bottom:20px;">
                <div class="card-header"><h5>Daily Hours This Week</h5></div>
                <div class="card-body">
                    <div class="d-flex align-items-end gap-3" style="height:140px;">
                        <?php foreach ($dailyHours as $date => $hrs): ?>
                            <div class="d-flex flex-column align-items-center" style="flex:1;">
                                <div style="width:100%;max-width:36px;background:var(--primary,#00236f);border-radius:6px 6px 0 0;height:<?= (int)round(($hrs / $maxDay) * 100) ?>px;min-height:2px;"></div>
                                <span class="small text-muted mt-1"><?= date('D', strtotime($date)) ?></span>
                                <span class="small fw-bold"><?= $hrs ?>h</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5>Breakdown by Task Type (This Month)</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Task Type</th><th>Number of Tasks</th><th>Total Hours</th></tr></thead>
                            <tbody>
                                <?php if (empty($breakdown)): ?>
                                    <tr><td colspan="3" class="text-muted">No workload recorded this month.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($breakdown as $b): ?>
                                    <tr>
                                        <td data-label="Type"><?= htmlspecialchars($b['type_name']) ?></td>
                                        <td data-label="Count"><?= (int)$b['task_count'] ?></td>
                                        <td data-label="Hours"><?= $b['total_hours'] ?> hrs</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
