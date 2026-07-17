<?php
require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['user_id']) && !empty($_SESSION['role_id'])) {
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
    header('Location: ' . ($dashboardMap[$roleId] ?? app_url('auth/login.php')));
} else {
    header('Location: ' . app_url('auth/login.php'));
}
exit();
?>