<?php

// Load configuration files
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!empty($_SESSION['user_id']) && !empty($_SESSION['role_id'])) {

    // Map roles to dashboards
    $dashboardMap = [
        ROLE_ADMIN => 'admin/dashboard.php',
        ROLE_INSTRUCTOR => 'instructor/dashboard.php',
        ROLE_COORDINATOR => 'coordinator/dashboard.php',
        ROLE_CHIEF_COORDINATOR => 'chief_coordinator/dashboard.php',
        ROLE_NON_ACADEMIC => 'non_academic/dashboard.php',
        ROLE_PROJECT_COORDINATOR => 'project_coordinator/dashboard.php',
        ROLE_DIRECTOR => 'director/dashboard.php',
    ];

    $roleId = (int) $_SESSION['role_id'];

    // Get dashboard URL
    $dashboard = $dashboardMap[$roleId] ?? 'auth/login.php';

    // Redirect
    header('Location: ' . app_url($dashboard));
    exit();

} else {

    // User is not logged in
    header('Location: ' . app_url('auth/login.php'));
    exit();
}
?>