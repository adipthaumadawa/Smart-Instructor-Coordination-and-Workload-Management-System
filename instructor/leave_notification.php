<?php
/**
 * Deprecated: split into instructor/leave.php and instructor/notifications.php.
 * Kept only so old bookmarks/links don't 404.
 */
require_once __DIR__ . '/../config/config.php';
header('Location: ' . app_url('instructor/leave.php'));
exit;