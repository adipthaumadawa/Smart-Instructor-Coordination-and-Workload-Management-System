<?php
/**
 * Director - Leave Records Monitoring
 * Smart Instructor Coordination and Workload Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php'; 
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_DIRECTOR);

$pageTitle = "Leave Records Monitoring";
include __DIR__ . '/../includes/header.php';

$rows = $pdo->query("
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
        <h1>Leave Records Monitoring</h1>
        <p>Monitor instructor leave periods and coordination schedules.</p>
    </div>
</div>

<div class="alert alert-info">
    <strong>Read-only:</strong> Leave is recorded for coordination. Official HR leave approval is outside scope.
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="leaveRecordsTable">
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
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No leave records found.</td></tr>
                    <?php endif; ?>
                    <?php foreach($rows as $r): ?>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('globalSearch');
    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        const query = this.value.trim().toLowerCase();
        const rows = document.querySelectorAll('#leaveRecordsTable tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = (!query || text.includes(query)) ? '' : 'none';
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>