<?php
// Enable error reporting temporarily to diagnose issues
// Comment these out after fixing for production
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Load configuration files in correct order
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is already logged in and has valid session
if (!empty($_SESSION['user_id']) && !empty($_SESSION['role_id'])) {
    // Map roles to their respective dashboards
    $dashboardMap = [
        ROLE_ADMIN => app_url('admin/dashboard.php'),
        ROLE_INSTRUCTOR => app_url('instructor/dashboard.php'),
        ROLE_COORDINATOR => app_url('coordinator/dashboard.php'),
        ROLE_CHIEF_COORDINATOR => app_url('chief_coordinator/dashboard.php'),
        ROLE_NON_ACADEMIC => app_url('non_academic/dashboard.php'),
        ROLE_PROJECT_COORDINATOR => app_url('project_coordinator/dashboard.php'),
        ROLE_DIRECTOR => app_url('director/dashboard.php'),
    ];

    $roleId = (int) $_SESSION['role_id'];
    $redirectUrl = $dashboardMap[$roleId] ?? app_url('auth/login.php');
    header('Location: ' . $redirectUrl);
} else {
    // No active session, redirect to login
    header('Location: ' . app_url('auth/login.php'));
}
exit();
?>