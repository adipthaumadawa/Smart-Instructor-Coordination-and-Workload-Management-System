<?php
/**
 * Application Configuration
 * Smart Instructor Coordination and Workload Management System
 */

// =====================================================
// ENVIRONMENT & ERROR REPORTING
// =====================================================
// Set to 1 for development; set to 0 in production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set default system timezone
date_default_timezone_set('Asia/Colombo');



// =====================================================
// APPLICATION & URL SETTINGS
// =====================================================
define('SITE_NAME', 'Smart Instructor System - UCSC'); //[cite: 2]
define('VERSION', '1.0.0'); //[cite: 2]

// Dynamic URL detection (works on local server or production without manual path changes)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$defaultUrl = $protocol . $host . '/Smart-Instructor-Coordination-and-Workload-Management-System'; //[cite: 2]

define('SITE_URL', $defaultUrl); //[cite: 2]
define('APP_URL', SITE_URL);     // Alias so both APP_URL and SITE_URL work seamlessly

// =====================================================
// DATABASE CREDENTIALS (for config/db.php)
// =====================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'smart_instructor_db');

// =====================================================
// SYSTEM DEFAULTS & PAGINATION
// =====================================================
define('RECORDS_PER_PAGE', 10);        //[cite: 2]
define('DEFAULT_MAX_WEEKLY_HOURS', 40); //[cite: 2]

// =====================================================
// USER ROLES
// =====================================================
define('ROLE_ADMIN', 1);               //[cite: 2]
define('ROLE_INSTRUCTOR', 2);          //[cite: 2]
define('ROLE_COORDINATOR', 3);         //[cite: 2]
define('ROLE_CHIEF_COORDINATOR', 4);   //[cite: 2]
define('ROLE_NON_ACADEMIC', 5);        //[cite: 2]
define('ROLE_PROJECT_COORDINATOR', 6); //[cite: 2]
define('ROLE_DIRECTOR', 7);            //[cite: 2]

// =====================================================
// STATUS CONSTANTS
// =====================================================
define('STATUS_ACTIVE', 'active');       //[cite: 2]
define('STATUS_INACTIVE', 'inactive');   //[cite: 2]
define('STATUS_PENDING', 'Pending');     //[cite: 2]
define('STATUS_APPROVED', 'Approved');   //[cite: 2]
define('STATUS_REJECTED', 'Rejected');   //[cite: 2]
define('STATUS_ASSIGNED', 'Assigned');   //[cite: 2]
define('STATUS_COMPLETED', 'Completed'); //[cite: 2]

// =====================================================
// DATE & TIME FORMATS
// =====================================================
define('DATE_FORMAT', 'Y-m-d');         //[cite: 2]
define('DATETIME_FORMAT', 'Y-m-d H:i:s');//[cite: 2]