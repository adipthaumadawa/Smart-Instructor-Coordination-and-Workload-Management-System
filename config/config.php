<?php

/**
 * Application Configuration
 * Smart Instructor Coordination and Workload Management System
 */

define('SITE_NAME', 'Smart Instructor System - UCSC');

define(
    'SITE_URL',
    'http://localhost/Smart-Instructor-Coordination-and-Workload-Management-System'
);

define('VERSION', '1.0.0');

// Default pagination
define('RECORDS_PER_PAGE', 10);

// Workload settings
define('DEFAULT_MAX_WEEKLY_HOURS', 40);

// Role ID constants
define('ROLE_ADMIN', 1);
define('ROLE_INSTRUCTOR', 2);
define('ROLE_COORDINATOR', 3);
define('ROLE_CHIEF_COORDINATOR', 4);
define('ROLE_NON_ACADEMIC', 5);
define('ROLE_PROJECT_COORDINATOR', 6);
define('ROLE_DIRECTOR', 7);

// Status constants
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');
define('STATUS_PENDING', 'Pending');
define('STATUS_APPROVED', 'Approved');
define('STATUS_REJECTED', 'Rejected');
define('STATUS_ASSIGNED', 'Assigned');
define('STATUS_COMPLETED', 'Completed');

// Date format
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');

/**
 * Build absolute URL from relative path
 * Example:
 * app_url('admin/dashboard.php')
 */
function app_url($path = '')
{
    $url = rtrim(SITE_URL, '/');

    if ($path) {
        $url .= '/' . ltrim($path, '/');
    }

    return $url;
}