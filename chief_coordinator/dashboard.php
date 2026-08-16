<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';

checkRole(ROLE_CHIEF_COORDINATOR);

$pageTitle = "Chief Instructor Coordinator Dashboard";
include __DIR__ . '/../includes/header.php';

sic_render_dashboard(
    "Chief Instructor Coordinator Dashboard",
    "Monitor instructor allocations, workload, replacement requests, reports, and department-wide coordination.",
    sic_dashboard_cards('chief'),
    app_url('chief_coordinator/reports.php'),
    "View Reports"
);

include __DIR__ . '/../includes/footer.php';
?>