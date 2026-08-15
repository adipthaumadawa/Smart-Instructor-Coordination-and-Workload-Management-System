<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';

checkRole(ROLE_NON_ACADEMIC);

$pageTitle = "Non-Academic Staff Dashboard";
include __DIR__ . '/../includes/header.php';

sic_render_dashboard(
    "Non-Academic Staff Dashboard",
    "Manage timetable records, attendance updates, lecture room bookings, and leave notifications.",
    sic_dashboard_cards('non_academic'),
    app_url('non_academic/timetable_records.php'),
    "Timetable Records"
);

include __DIR__ . '/../includes/footer.php';
?>