<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';

// Only instructor coordinators can access this dashboard.
checkRole(ROLE_COORDINATOR);

$pageTitle = 'Instructor Coordinator Dashboard';
include __DIR__ . '/../includes/header.php';

sic_render_dashboard(
    'Instructor Coordinator Dashboard',
    'Manage instructor availability, additional task requests, urgent replacements, workload, and schedules.',
    sic_dashboard_cards('coordinator'),
    app_url('coordinator/additional_tasks.php'),
    'New Task'
);

include __DIR__ . '/../includes/footer.php';
?>