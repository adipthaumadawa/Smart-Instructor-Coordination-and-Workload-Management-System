<?php
/**
 * Director - Reports
 * Smart Instructor Coordination and Workload Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_DIRECTOR);

$pageTitle = "Director Reports";
include __DIR__ . '/../includes/header.php';

$stats = [
    'Instructors' => $pdo->query("SELECT COUNT(*) FROM instructors")->fetchColumn(),
    'Active Tasks' => $pdo->query("SELECT COUNT(*) FROM task_assignments WHERE status IN('Assigned','Accepted')")->fetchColumn(),
    'Room Bookings' => $pdo->query("SELECT COUNT(*) FROM lecture_hall_bookings WHERE status='Confirmed'")->fetchColumn(),
    'Presentation Sessions' => $pdo->query("SELECT COUNT(*) FROM presentation_sessions")->fetchColumn()
];

// Fetch data for the preview sections
$workloadRows = $pdo->query("
    SELECT i.id, u.full_name, ast.name stream, 
           COALESCE(SUM(CASE WHEN ta.is_presentation_panel=0 AND ta.status IN('Assigned','Accepted','Completed') THEN ta.duration_hours ELSE 0 END),0) hours 
    FROM instructors i 
    JOIN users u ON i.user_id = u.id 
    JOIN academic_streams ast ON i.academic_stream_id = ast.id 
    LEFT JOIN task_assignments ta ON i.id = ta.instructor_id 
    WHERE i.status = 'active' 
    GROUP BY i.id, u.full_name, ast.name 
    ORDER BY hours DESC
")->fetchAll();

$allocationRows = $pdo->query("
    SELECT ta.*, tt.name task_type, u.full_name instructor_name 
    FROM task_assignments ta 
    JOIN task_types tt ON ta.task_type_id = tt.id 
    JOIN instructors i ON ta.instructor_id = i.id 
    JOIN users u ON i.user_id = u.id 
    ORDER BY ta.scheduled_date DESC 
    LIMIT 100
")->fetchAll();

$leaveRows = $pdo->query("
    SELECT lr.*, u.full_name 
    FROM leave_records lr 
    JOIN instructors i ON lr.instructor_id = i.id 
    JOIN users u ON i.user_id = u.id 
    ORDER BY lr.created_at DESC 
    LIMIT 100
")->fetchAll();
?>

<div class="page-toolbar">
    <div>
        <h1>Director Reports</h1>
        <p>Overview of system metrics, workload distribution, and administrative logs.</p>
    </div>
</div>

<div class="row g-3">
    <?php foreach($stats as $k => $v): ?>
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="text-muted"><?= htmlspecialchars($k) ?></h6>
                <h2><?= $v ?></h2>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-body">
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary report-tab-btn active" data-target="workload-preview">Workload Distribution</button> 
            <button type="button" class="btn btn-outline-primary report-tab-btn" data-target="allocations-preview">Allocations</button> 
            <button type="button" class="btn btn-outline-primary report-tab-btn" data-target="leave-preview">Leave Records</button>
        </div>
    </div>
</div>

<!-- Preview Container Sections -->
<div class="mt-4">
    <!-- Workload Distribution Preview -->
    <div id="workload-preview" class="report-preview-section">
        <div class="card shadow-sm">
            <div class="card-header"><h5>Workload Distribution Preview</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 report-search-table">
                        <thead class="table-light">
                            <tr>
                                <th>Instructor</th>
                                <th>Stream</th>
                                <th>Workload Hours</th>
                                <th>Usage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($workloadRows)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">No workload data found.</td></tr>
                            <?php endif; ?>
                            <?php foreach($workloadRows as $r): 
                                $pct = min(100, round(($r['hours'] / DEFAULT_MAX_WEEKLY_HOURS) * 100)); 
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['full_name']) ?></strong></td>
                                <td><?= htmlspecialchars($r['stream']) ?></td>
                                <td><?= htmlspecialchars($r['hours']) ?> hrs</td>
                                <td style="width: 25%;">
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar" role="progressbar" style="width: <?= $pct ?>%;"></div>
                                    </div>
                                    <small class="text-muted"><?= $pct ?>% capacity</small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Allocations Preview -->
    <div id="allocations-preview" class="report-preview-section" style="display: none;">
        <div class="alert alert-info"><strong>Read-only:</strong> Allocation status monitoring preview.</div>
        <div class="card shadow-sm">
            <div class="card-header"><h5>Allocations Preview</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 report-search-table">
                        <thead class="table-light">
                            <tr>
                                <th>Instructor</th>
                                <th>Task</th>
                                <th>Date</th>
                                <th>Hours</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allocationRows)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No task allocations found.</td></tr>
                            <?php endif; ?>
                            <?php foreach($allocationRows as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['instructor_name']) ?></strong></td>
                                <td><?= htmlspecialchars($r['task_type']) ?></td>
                                <td><?= formatDate($r['scheduled_date']) ?></td>
                                <td><?= htmlspecialchars($r['duration_hours']) ?></td>
                                <td><?= getStatusBadge($r['status']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Leave Records Preview -->
    <div id="leave-preview" class="report-preview-section" style="display: none;">
        <div class="alert alert-info"><strong>Read-only:</strong> Leave records monitoring preview.</div>
        <div class="card shadow-sm">
            <div class="card-header"><h5>Leave Records Preview</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 report-search-table">
                        <thead class="table-light">
                            <tr>
                                <th>Instructor</th>
                                <th>Type</th>
                                <th>Period</th>
                                <th>Reason</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($leaveRows)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No leave records found.</td></tr>
                            <?php endif; ?>
                            <?php foreach($leaveRows as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['full_name']) ?></strong></td>
                                <td><?= htmlspecialchars($r['leave_type']) ?></td>
                                <td><?= formatDate($r['start_date']) ?> - <?= formatDate($r['end_date']) ?></td>
                                <td><?= htmlspecialchars($r['reason'] ?? 'N/A') ?></td>
                                <td><?= getStatusBadge($r['status']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.report-tab-btn');
    const sections = document.querySelectorAll('.report-preview-section');

    buttons.forEach(btn => {
        btn.addEventListener('click', function() {
            buttons.forEach(b => {
                b.classList.remove('btn-primary');
                b.classList.add('btn-outline-primary');
            });
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary');

            const targetId = this.getAttribute('data-target');
            sections.forEach(sec => {
                if (sec.id === targetId) {
                    sec.style.display = 'block';
                } else {
                    sec.style.display = 'none';
                }
            });
        });
    });

    // Real-time search filter for active tab table rows
    const searchInput = document.getElementById('globalSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();
            // Target only the visible active tab section's table rows
            const activeSection = document.querySelector('.report-preview-section:not([style*="display: none"])');
            if (!activeSection) return;

            const rows = activeSection.querySelectorAll('.report-search-table tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = (!query || text.includes(query)) ? '' : 'none';
            });
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>