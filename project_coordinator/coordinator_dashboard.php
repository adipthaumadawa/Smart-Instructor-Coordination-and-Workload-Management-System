<?php
/**
 * Project Coordinator - Dashboard
 * Smart Instructor Coordination and Workload Management System
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';

// Only Project Coordinators can access this dashboard.
checkRole(ROLE_PROJECT_COORDINATOR);

$pageTitle = 'Project Coordinator Dashboard';
include __DIR__ . '/../includes/header.php';

sic_render_dashboard(
    'Project Coordinator Dashboard',
    'Create presentation sessions, assign evaluation panels, and manage venue bookings.',
    sic_dashboard_cards('project'),
    app_url('project_coordinator/sessions.php'),
    'New Session'
);

include __DIR__ . '/../includes/footer.php';
?>
