<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';

checkRole(ROLE_PROJECT_COORDINATOR);

$pageTitle = "Project Coordinator Dashboard";
include __DIR__ . '/../includes/header.php';

sic_render_dashboard(
    "Project Coordinator Dashboard",
    "Manage presentation sessions, panel members, instructor availability, venues, and schedules.",
    sic_dashboard_cards('project'),
    app_url('project_coordinator/schedule_session.php'),
    "Schedule Session"
);

include __DIR__ . '/../includes/footer.php';
?>