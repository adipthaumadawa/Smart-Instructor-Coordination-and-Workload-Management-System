<?php
require_once __DIR__ . '/../config/config.php';
if (!isset($pageTitle) || trim((string)$pageTitle) === '') { $pageTitle = SITE_NAME; }
if (!isset($currentUser) && function_exists('getCurrentUser')) { $currentUser = getCurrentUser(); }
$currentUser = is_array($currentUser ?? null) ? $currentUser : [];
$displayName = trim((string)($currentUser['full_name'] ?? 'UCSC User')) ?: 'UCSC User';
$roleName = trim((string)($currentUser['role_name'] ?? 'System User')) ?: 'System User';
$userInitial = strtoupper(substr($displayName, 0, 1));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#071a33">
  <meta name="description" content="Smart Instructor Coordination and Workload Management System – UCSC">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | UCSC SIS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
  <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
</head>
<body>
<?php if (empty($hideNavbar)): ?>
<div class="app-shell">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <div class="right-panel">
    <?php include __DIR__ . '/navbar.php'; ?>
    <main class="main-content" id="mainContent">
<?php endif; ?>
