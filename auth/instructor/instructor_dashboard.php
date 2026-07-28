<?php
/**
 * [ROLE] Dashboard
 * Replace [ROLE] with actual role name
 */

// 1. Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';

// 2. Ensure user is logged in
requireLogin();

// 3. Ensure user has correct role
checkRole(ROLE_INSTRUCTOR);  // Change to appropriate role

// 4. Get user data
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard | Smart Instructor System</title>
    <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
</head>
<body>
    <!-- 5. Display dashboard content -->
    <div style="padding: 20px; max-width: 1200px; margin: 0 auto;">
        <h1>Welcome, <?= htmlspecialchars($currentUser['full_name']) ?></h1>
        
        <!-- Dashboard content here -->
        
        <a href="<?= app_url('auth/logout.php') ?>">Logout</a>
    </div>
</body>
</html>