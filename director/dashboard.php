<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';

checkRole(ROLE_DIRECTOR);

$pageTitle = "Director / Department Head Dashboard";
include __DIR__ . '/../includes/header.php';

sic_render_dashboard(
    "Director / Department Head Dashboard",
    "Read-only monitoring of active tasks, leave records, replacements, room usage, reports, and allocation status.",
    sic_dashboard_cards('director'),
    "",
    ""
);

include __DIR__ . '/../includes/footer.php';
?>
