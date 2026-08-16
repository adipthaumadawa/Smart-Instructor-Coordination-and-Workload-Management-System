<?php
/**
 * Director / Department Head Dashboard
 * Smart Instructor Coordination and Workload Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';

checkRole(ROLE_DIRECTOR);

// Define active page context for the header menu sidebar navigation
$activeRole = 'director';
$currentPage = 'dashboard';

$pageTitle = "Director / Department Head Dashboard";
include __DIR__ . '/../includes/header.php';
?>

<div class="search-filterable-container">
    <?php
    sic_render_dashboard(
        "Director / Department Head Dashboard",
        "Read-only monitoring of active tasks, leave records, replacements, room usage, reports, and allocation status.",
        sic_dashboard_cards('director'),
        "",
        ""
    );
    ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('globalSearch');
    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        const query = this.value.trim().toLowerCase();

        // 1. Filter table rows
        document.querySelectorAll('main table tbody tr').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = (!query || text.includes(query)) ? '' : 'none';
        });

        // 2. Filter schedule items, leave request rows, and alert list items
        document.querySelectorAll('.sched-item, .leave-item, .alert-item, .venue-item').forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = (!query || text.includes(query)) ? '' : 'none';
        });

        // 3. Filter KPI cards and widget boxes
        document.querySelectorAll('.kpi-card, .d-card').forEach(card => {
            if (card.querySelectorAll('table, .sched-item, .leave-item').length === 0) {
                const text = card.textContent.toLowerCase();
                card.style.opacity = (!query || text.includes(query)) ? '1' : '0.3';
            }
        });
    });
});
</script>

<?php
include __DIR__ . '/../includes/footer.php';
?>