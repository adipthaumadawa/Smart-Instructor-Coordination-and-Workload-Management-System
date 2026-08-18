<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';

// Only instructors can access this dashboard.
checkRole(ROLE_INSTRUCTOR);

$pageTitle = 'Instructor Dashboard';
include __DIR__ . '/../includes/header.php';

sic_render_instructor_dashboard(
    'Instructor Dashboard',
    'Your upcoming schedule, today\'s tasks, weekly workload, and requests that involve you.',
    sic_dashboard_cards('instructor'),
    app_url('instructor/replacement_request.php'),
    'Request Replacement'
);

include __DIR__ . '/../includes/footer.php';
?>