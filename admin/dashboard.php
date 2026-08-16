<?php
/**
 * Admin Dashboard
 * Smart Instructor Coordination and Workload Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';

checkRole(ROLE_ADMIN);

$pageTitle = "Admin Dashboard";
include __DIR__ . '/../includes/header.php';

sic_render_dashboard(
    "Admin Dashboard",
    "System administration: users, roles, permissions, settings, and activity logs.",
    sic_dashboard_cards('admin'),
    app_url("admin/add_user.php"),
    "Add User"
);
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('globalSearch');
    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        const query = this.value.trim().toLowerCase();

        // 1. Filter table rows across any dashboard table views
        document.querySelectorAll('main table tbody tr, .act-table tbody tr, .admin-table tbody tr').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = (!query || text.includes(query)) ? '' : 'none';
        });

        // 2. Filter list stack items or schedule rows
        document.querySelectorAll('main .schedule-row, main .leave-row, main .alert-row, main .sched-item').forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = (!query || text.includes(query)) ? '' : 'none';
        });

        // 3. Dim or hide cards that do not match the search query
        document.querySelectorAll('main .card, main .ui-card, main .d-card, main .kpi-card').forEach(card => {
            if (card.closest('.topbar, .sidebar')) return;
            if (card.querySelectorAll('table, .schedule-list, .leave-list').length === 0) {
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