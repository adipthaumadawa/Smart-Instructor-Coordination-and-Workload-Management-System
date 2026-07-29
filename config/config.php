<?php
/** Portable application configuration for XAMPP and shared development. */
declare(strict_types=1);

define('APP_DEBUG', true);
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

date_default_timezone_set('Asia/Colombo');
define('SITE_NAME', 'Smart Instructor System - UCSC');
define('VERSION', '1.1.0');

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443);
$scheme = $https ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$projectRoot = realpath(dirname(__DIR__));
$basePath = '';
if ($documentRoot && $projectRoot) {
    $doc = rtrim(str_replace('\\', '/', $documentRoot), '/');
    $project = str_replace('\\', '/', $projectRoot);
    if (str_starts_with(strtolower($project), strtolower($doc))) {
        $basePath = substr($project, strlen($doc));
    }
}
$basePath = '/' . trim(str_replace('\\', '/', $basePath), '/');
if ($basePath === '/') { $basePath = ''; }
define('APP_ROOT_URL', $basePath);
define('SITE_URL', rtrim($scheme . $host . $basePath, '/'));
define('APP_URL', SITE_URL);

if (!function_exists('app_url')) {
    function app_url(string $path = ''): string {
        $path = ltrim($path, '/');
        return $path === '' ? SITE_URL : SITE_URL . '/' . $path;
    }
}
if (!function_exists('project_file_exists')) {
    function project_file_exists(string $path): bool {
        return is_file(dirname(__DIR__) . '/' . ltrim($path, '/'));
    }
}

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'smart_instructor_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('RECORDS_PER_PAGE', 10);
define('DEFAULT_MAX_WEEKLY_HOURS', 40);
define('ROLE_ADMIN', 1);
define('ROLE_INSTRUCTOR', 2);
define('ROLE_COORDINATOR', 3);
define('ROLE_CHIEF_COORDINATOR', 4);
define('ROLE_NON_ACADEMIC', 5);
define('ROLE_PROJECT_COORDINATOR', 6);
define('ROLE_DIRECTOR', 7);
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');
define('STATUS_PENDING', 'Pending');
define('STATUS_APPROVED', 'Approved');
define('STATUS_REJECTED', 'Rejected');
define('STATUS_ASSIGNED', 'Assigned');
define('STATUS_COMPLETED', 'Completed');
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
