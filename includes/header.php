<?php
require_once __DIR__ . '/../config/config.php';
if (!isset($pageTitle)) { $pageTitle = SITE_NAME; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | UCSC Smart Instructor System</title>
    <!-- Local CSS only (no Bootstrap, Font Awesome, Material Symbols, or Google Fonts) -->
    <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/stitch-theme.css') ?>">
</head>
<body>
<?php if (!isset($hideNavbar)) { include __DIR__ . '/navbar.php'; } ?>