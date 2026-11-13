<?php
/**
 * System Settings
 * Smart Instructor Coordination and Workload Management System
 *
 * The catalog below defines every setting an administrator can manage:
 * its label, input type, default value and validation bounds. Values are
 * stored in the system_settings table; getSetting() falls back to the
 * catalog default when a row has not been saved yet, so new settings work
 * without a database migration.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

function sic_settings_catalog(): array
{
    return [
        'general' => [
            'title' => 'General',
            'fields' => [
                'site_name' => [
                    'label' => 'System Name',
                    'type' => 'text',
                    'default' => SITE_NAME,
                    'maxlength' => 100,
                    'required' => true,
                    'description' => 'Shown in browser tabs and page titles across the system.',
                ],
                'academic_year' => [
                    'label' => 'Academic Year',
                    'type' => 'text',
                    'default' => DEFAULT_ACADEMIC_YEAR,
                    'maxlength' => 20,
                    'required' => true,
                    'description' => 'Current academic year, used as the fallback label when no timetable records exist.',
                ],
                'default_presentation_duration' => [
                    'label' => 'Default Presentation Duration (hours)',
                    'type' => 'number',
                    'default' => 3,
                    'min' => 1,
                    'max' => 12,
                    'description' => 'Suggested session length when checking venue availability for presentations.',
                ],
            ],
        ],
        'workload' => [
            'title' => 'Workload',
            'fields' => [
                'max_weekly_workload_hours' => [
                    'label' => 'Max Weekly Workload Hours',
                    'type' => 'number',
                    'default' => DEFAULT_MAX_WEEKLY_HOURS,
                    'min' => 1,
                    'max' => 80,
                    'description' => 'Baseline maximum weekly hours used to calculate workload percentages.',
                ],
                'workload_warning_threshold' => [
                    'label' => 'Workload Warning Threshold (%)',
                    'type' => 'number',
                    'default' => 80,
                    'min' => 1,
                    'max' => 100,
                    'description' => 'Instructors at or above this percentage of their maximum weekly hours raise a workload alert.',
                ],
            ],
        ],
        'booking' => [
            'title' => 'Lecture Hall Booking',
            'fields' => [
                'booking_start_time' => [
                    'label' => 'Earliest Booking Start',
                    'type' => 'time',
                    'default' => '08:00',
                    'description' => 'Room bookings cannot start before this time.',
                ],
                'booking_end_time' => [
                    'label' => 'Latest Booking End',
                    'type' => 'time',
                    'default' => '18:00',
                    'description' => 'Room bookings must end by this time.',
                ],
                'max_booking_duration_hours' => [
                    'label' => 'Max Booking Duration (hours)',
                    'type' => 'number',
                    'default' => 4,
                    'min' => 1,
                    'max' => 12,
                    'description' => 'Maximum length of a single room booking.',
                ],
                'booking_advance_days' => [
                    'label' => 'Advance Booking Window (days)',
                    'type' => 'number',
                    'default' => 30,
                    'min' => 1,
                    'max' => 365,
                    'description' => 'How many days ahead rooms may be booked.',
                ],
            ],
        ],
        'leave' => [
            'title' => 'Leave',
            'fields' => [
                'max_leave_days_per_year' => [
                    'label' => 'Max Leave Days per Year',
                    'type' => 'number',
                    'default' => 21,
                    'min' => 0,
                    'max' => 365,
                    'description' => 'Maximum leave days an instructor may record per calendar year (pending + confirmed). 0 disables the limit.',
                ],
            ],
        ],
        'notifications' => [
            'title' => 'Notifications',
            'fields' => [
                'email_notifications_enabled' => [
                    'label' => 'Email Notifications',
                    'type' => 'toggle',
                    'default' => '1',
                    'description' => 'Send notification emails. Account recovery emails are always allowed.',
                ],
            ],
        ],
        'security' => [
            'title' => 'Security & Sessions',
            'fields' => [
                'session_timeout_minutes' => [
                    'label' => 'Session Timeout (minutes)',
                    'type' => 'number',
                    'default' => 60,
                    'min' => 5,
                    'max' => 480,
                    'description' => 'Minutes of inactivity before a user is signed out automatically.',
                ],
                'password_min_length' => [
                    'label' => 'Minimum Password Length',
                    'type' => 'number',
                    'default' => 12,
                    'min' => 6,
                    'max' => 64,
                    'description' => 'Minimum password length when creating accounts or changing passwords.',
                ],
                'maintenance_mode' => [
                    'label' => 'Maintenance Mode',
                    'type' => 'toggle',
                    'default' => '0',
                    'description' => 'While enabled, only administrators can sign in and use the system.',
                ],
            ],
        ],
    ];
}

/** Flat key => definition map, in catalog order. */
function sic_setting_definitions(): array
{
    static $flat = null;
    if ($flat === null) {
        $flat = [];
        foreach (sic_settings_catalog() as $group) {
            foreach ($group['fields'] as $key => $definition) {
                $flat[$key] = $definition;
            }
        }
    }
    return $flat;
}

/**
 * Read a setting value. Stored database values win; otherwise the catalog
 * default is used; otherwise the caller-supplied default. Always a string.
 */
function getSetting(string $key, $default = null): string
{
    static $values = null;
    if ($values === null) {
        $values = [];
        global $pdo;
        try {
            $rows = $pdo->query('SELECT setting_key, setting_value FROM system_settings');
            foreach ($rows as $row) {
                $values[$row['setting_key']] = (string) $row['setting_value'];
            }
        } catch (Throwable $e) {
            $values = [];
        }
    }

    if (isset($values[$key]) && $values[$key] !== '') {
        return $values[$key];
    }

    $definitions = sic_setting_definitions();
    if (isset($definitions[$key])) {
        return (string) $definitions[$key]['default'];
    }

    return (string) ($default ?? '');
}

function sic_site_name(): string
{
    return getSetting('site_name', SITE_NAME);
}

function sic_password_min_length(): int
{
    return max(6, (int) getSetting('password_min_length', 12));
}

function sic_maintenance_mode(): bool
{
    return getSetting('maintenance_mode', '0') === '1';
}