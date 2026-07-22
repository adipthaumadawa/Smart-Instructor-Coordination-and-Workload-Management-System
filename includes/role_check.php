<?php
/** Role-Based Access Control */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';

function checkRole($allowedRoles, $redirect = true) {
    requireLogin();
    $currentRole = getCurrentRoleId();
    $allowedRoles = is_array($allowedRoles) ? array_map('intval', $allowedRoles) : [(int)$allowedRoles];
    $hasAccess = in_array((int)$currentRole, $allowedRoles, true);
    if (!$hasAccess) {
        if ($redirect) {
            $_SESSION['error'] = 'Access Denied. You do not have permission to view this page.';
            header('Location: ' . getDashboardPath($currentRole));
            exit();
        }
        return false;
    }
    return true;
}

function getDashboardPath($roleId) {
    switch ((int)$roleId) {
        case ROLE_ADMIN: return app_url('admin/dashboard.php');
        case ROLE_INSTRUCTOR: return app_url('instructor/dashboard.php');
        case ROLE_COORDINATOR: return app_url('coordinator/dashboard.php');
        case ROLE_CHIEF_COORDINATOR: return app_url('chief_coordinator/dashboard.php');
        case ROLE_NON_ACADEMIC: return app_url('non_academic/dashboard.php');
        case ROLE_PROJECT_COORDINATOR: return app_url('project_coordinator/dashboard.php');
        case ROLE_DIRECTOR: return app_url('director/dashboard.php');
        default: return app_url('auth/login.php');
    }
}

function canPerformAction($action) {
    $role = getCurrentRoleId();
    $permissions = [
        'manage_users' => [ROLE_ADMIN],
        'manage_roles' => [ROLE_ADMIN],
        'view_activity_logs' => [ROLE_ADMIN],
        'assign_tasks' => [ROLE_COORDINATOR, ROLE_CHIEF_COORDINATOR],
        'handle_replacements' => [ROLE_COORDINATOR, ROLE_CHIEF_COORDINATOR],
        'view_smart_suggestions' => [ROLE_COORDINATOR, ROLE_CHIEF_COORDINATOR],
        'record_leave' => [ROLE_INSTRUCTOR],
        'request_replacement' => [ROLE_INSTRUCTOR],
        'create_presentation' => [ROLE_PROJECT_COORDINATOR],
        'assign_panel' => [ROLE_PROJECT_COORDINATOR],
        'view_only' => [ROLE_DIRECTOR],
    ];
    return isset($permissions[$action]) && in_array((int)$role, $permissions[$action], true);
}
?>