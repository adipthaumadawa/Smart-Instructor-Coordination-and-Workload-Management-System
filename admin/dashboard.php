<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';

checkRole(ROLE_ADMIN);

$pageTitle = "Admin Dashboard";
include __DIR__ . '/../includes/header.php';

sic_render_dashboard(
    "Admin Dashboard",
    "System administration: users, roles, permissions, settings, and activity logs.",
    sic_dashboard_cards('admin'),
    app_url("admin/add_user.php"),
    "Add User"
);

include __DIR__ . '/../includes/footer.php';
?>
