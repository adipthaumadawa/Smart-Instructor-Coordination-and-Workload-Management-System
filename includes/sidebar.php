<?php
/**
 * Sidebar navigation
 * Framework-free: no Bootstrap, Font Awesome, or Material icons
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/role_check.php';

$currentRole = getCurrentRoleId();
$currentPage = basename($_SERVER['PHP_SELF']);
$currentUser = getCurrentUser();

if (!function_exists('nav_active')) {
    function nav_active($file) {
        return basename($_SERVER['PHP_SELF']) === basename($file) ? 'active' : '';
    }
}

$initial = $currentUser ? strtoupper(substr($currentUser['full_name'], 0, 1)) : 'U';
$displayName = $currentUser['full_name'] ?? 'UCSC User';
$roleName = $currentUser['role_name'] ?? 'System User';
?>
<aside class="sidebar" id="sidebarMenu">

  <div class="sidebar-brand">
    <img class="ucsc-logo" src="<?= app_url('assets/images/ucsc-logo.png') ?>" alt="UCSC Logo">
    <h2 class="sidebar-title">Smart Instructor System</h2>
    <p class="sidebar-subtitle">University of Colombo<br>School of Computing</p>
  </div>

  <ul class="nav">

    <li>
      <a class="nav-link <?= $currentPage === 'dashboard.php' || $currentPage === 'coordinator_dashboard.php' ? 'active' : '' ?>"
         href="<?= getDashboardPath($currentRole) ?>">
        <span class="nav-icon" aria-hidden="true">⌂</span>
        <span>Dashboard</span>
      </a>
    </li>

    <?php if ($currentRole == ROLE_ADMIN): ?>
      <li class="sidebar-section">Administration</li>
      <li>
        <a class="nav-link <?= nav_active('users.php') ?>" href="<?= app_url('admin/users.php') ?>">
          <span class="nav-icon" aria-hidden="true">👥</span><span>Users</span>
        </a>
      </li>
      <li>
        <a class="nav-link <?= nav_active('roles.php') ?>" href="<?= app_url('admin/roles.php') ?>">
          <span class="nav-icon" aria-hidden="true">🔐</span><span>Roles</span>
        </a>
      </li>
      <li>
        <a class="nav-link <?= nav_active('activity_logs.php') ?>" href="<?= app_url('admin/activity_logs.php') ?>">
          <span class="nav-icon" aria-hidden="true">📋</span><span>Activity Logs</span>
        </a>
      </li>
      <li>
        <a class="nav-link <?= nav_active('settings.php') ?>" href="<?= app_url('admin/settings.php') ?>">
          <span class="nav-icon" aria-hidden="true">⚙</span><span>Settings</span>
        </a>
      </li>

    <?php elseif ($currentRole == ROLE_INSTRUCTOR): ?>
      <li class="sidebar-section">Instructor</li>
      <li>
        <a class="nav-link <?= nav_active('timetable.php') ?>" href="<?= app_url('instructor/timetable.php') ?>">
          <span class="nav-icon" aria-hidden="true">📅</span><span>Timetable</span>
        </a>
      </li>
      <li>
        <a class="nav-link <?= nav_active('assigned_tasks.php') ?>" href="<?= app_url('instructor/assigned_tasks.php') ?>">
          <span class="nav-icon" aria-hidden="true">✓</span><span>Assigned Tasks</span>
        </a>
      </li>
      <li>
        <a class="nav-link <?= nav_active('workload.php') ?>" href="<?= app_url('instructor/workload.php') ?>">
          <span class="nav-icon" aria-hidden="true">📊</span><span>Workload</span>
        </a>
      </li>
      <li>
        <a class="nav-link <?= nav_active('leave.php') ?>" href="<?= app_url('instructor/leave.php') ?>">
          <span class="nav-icon" aria-hidden="true">🏖</span><span>Leave Records</span>
        </a>
      </li>
      <li>
        <a class="nav-link <?= nav_active('replacement_requests.php') ?>" href="<?= app_url('instructor/replacement_requests.php') ?>">
          <span class="nav-icon" aria-hidden="true">⇄</span><span>Replacement Requests</span>
        </a>
      </li>

    <?php elseif ($currentRole == ROLE_COORDINATOR): ?>
      <li class="sidebar-section">Coordination</li>
      <li>
        <a class="nav-link <?= nav_active('instructors.php') ?>" href="<?= app_url('coordinator/instructors.php') ?>">
          <span class="nav-icon" aria-hidden="true">🎓</span><span>Instructors</span>
        </a>
      </li>
      <li>
        <a class="nav-link <?= nav_active('additional_tasks.php') ?>" href="<?= app_url('coordinator/additional_tasks.php') ?>">
          <span class="nav-icon" aria-hidden="true">＋</span><span>Additional Tasks</span>
        </a>
      </li>
      <li>
        <a class="nav-link <?= nav_active('smart_suggestions.php') ?>" href="<?= app_url('coordinator/smart_suggestions.php') ?>">
          <span class="nav-icon" aria-hidden="true">✦</span><span>Smart Suggestions</span>
        </a>
      </li>
      <li>
        <a class="nav-link <?= nav_active('replacements.php') ?>" href="<?= app_url('coordinator/replacements.php') ?>">
          <span class="nav-icon" aria-hidden="true">⇄</span><span>Replacements</span>
        </a>
      </li>
      <li>
        <a class="nav-link <?= nav_active('urgency_replacements.php') ?>" href="<?= app_url('coordinator/urgency_replacements.php') ?>">
          <span class="nav-icon" aria-hidden="true">⚠</span><span>Urgency Alerts</span>
        </a>
      </li>
      <li>
        <a class="nav-link <?= nav_active('leave_records.php') ?>" href="<?= app_url('coordinator/leave_records.php') ?>">
          <span class="nav-icon" aria-hidden="true">🏖</span><span>Leave Records</span>
        </a>
      </li>

    <?php elseif ($currentRole == ROLE_CHIEF_COORDINATOR): ?>
      <li class="sidebar-section">Chief Coordinator</li>
      <li>
        <a class="nav-link <?= nav_active('allocations.php') ?>" href="<?= app_url('chief_coordinator/allocations.php') ?>">
          <span class="nav-icon" aria-hidden="true">🌳</span><span>Allocations</span>
        </a>
      </li>
      <li>
        <a class="nav-link <?= nav_active('workload_monitoring.php') ?>" href="<?= app_url('chief_coordinator/workload_monitoring.php') ?>">
          <span class="nav-icon" aria-hidden="true">📊</span><span>Workload</span>
        </a>
      </li>
      <li>
        <a class="nav-link <?= nav_active('leave_records.php') ?>" href="<?= app_url('chief_coordinator/leave_records.php') ?>">
          <span class="nav-icon" aria-hidden="true">🏖</span><span>Leave Records</span>
        </a>
      </li>
      <li>
        <a class="nav-link <?= nav_active('reports.php') ?>" href="<?= app_url('chief_coordinator/reports.php') ?>">
          <span class="nav-icon" aria-hidden="true">📄</span><span>Reports</span>
        </a>
      </li>

    <?php elseif ($currentRole == ROLE_NON_ACADEMIC): ?>
      <li class="sidebar-section">Operations</li>
      <li>
        <a class="nav-link <?= nav_active('timetable_records.php') ?>" href="<?= app_url('non_academic/timetable_records.php') ?>">
          <span class="nav-icon" aria-hidden="true">📅</span><span>Timetable</span>
        </a>
      </li>
      <li>
        <a class="nav-link <?= nav_active('room_schedules.php') ?>" href="<?= app_url('non_academic/room_schedules.php') ?>">
          <span class="nav-icon" aria-hidden="true">🏢</span><span>Room Schedules</span>
        </a>
      </li>
      <li>
        <a class="nav-link <?= nav_active('leave_notifications.php') ?>" href="<?= app_url('non_academic/leave_notifications.php') ?>">
          <span class="nav-icon" aria-hidden="true">🔔</span><span>Leave Alerts</span>
        </a>
      </li>

    <?php elseif ($currentRole == ROLE_PROJECT_COORDINATOR): ?>
      <li class="sidebar-section">Presentations</li>
      <li>
        <a class="nav-link <?= nav_active('presentation_sessions.php') ?>" href="<?= app_url('project_coordinator/presentation_sessions.php') ?>">
          <span class="nav-icon" aria-hidden="true">🖥</span><span>Sessions</span>
        </a>
      </li>
      <li>
        <a class="nav-link <?= nav_active('schedule_session.php') ?>" href="<?= app_url('project_coordinator/schedule_session.php') ?>">
          <span class="nav-icon" aria-hidden="true">📆</span><span>Schedule Session</span>
        </a>
      </li>
      <li>
        <a class="nav-link <?= nav_active('presentation_panels.php') ?>" href="<?= app_url('project_coordinator/presentation_panels.php') ?>">
          <span class="nav-icon" aria-hidden="true">👥</span><span>Panels</span>
        </a>
      </li>

    <?php elseif ($currentRole == ROLE_DIRECTOR): ?>
      <li class="sidebar-section">Monitoring</li>
      <li>
        <a class="nav-link <?= nav_active('workload_distribution.php') ?>" href="<?= app_url('director/workload_distribution.php') ?>">
          <span class="nav-icon" aria-hidden="true">📊</span><span>Workload</span>
        </a>
      </li>
      <li>
        <a class="nav-link <?= nav_active('leave_records.php') ?>" href="<?= app_url('director/leave_records.php') ?>">
          <span class="nav-icon" aria-hidden="true">🏖</span><span>Leave Records</span>
        </a>
      </li>
      <li>
        <a class="nav-link <?= nav_active('allocation_monitoring.php') ?>" href="<?= app_url('director/allocation_monitoring.php') ?>">
          <span class="nav-icon" aria-hidden="true">👁</span><span>Allocations</span>
        </a>
      </li>
      <li>
        <a class="nav-link <?= nav_active('reports.php') ?>" href="<?= app_url('director/reports.php') ?>">
          <span class="nav-icon" aria-hidden="true">📄</span><span>Reports</span>
        </a>
      </li>
    <?php endif; ?>

    <li class="sidebar-section">Common</li>
    <li>
      <a class="nav-link <?= nav_active('bookings.php') ?>" href="<?= app_url('rooms/bookings.php') ?>">
        <span class="nav-icon" aria-hidden="true">🏛</span><span>Lecture Hall Booking</span>
      </a>
    </li>
    <li>
      <a class="nav-link <?= nav_active('notifications.php') ?>" href="<?= app_url('notifications.php') ?>">
        <span class="nav-icon" aria-hidden="true">🔔</span><span>Notifications</span>
      </a>
    </li>
    <li>
      <a class="nav-link <?= nav_active('profile.php') ?>" href="<?= app_url('profile.php') ?>">
        <span class="nav-icon" aria-hidden="true">👤</span><span>Profile</span>
      </a>
    </li>

  </ul>

  <div class="sidebar-bottom-card">
    <div class="sidebar-user-avatar"><?= htmlspecialchars($initial) ?></div>
    <div>
      <strong><?= htmlspecialchars($displayName) ?></strong>
      <small><?= htmlspecialchars($roleName) ?></small>
    </div>
  </div>

</aside>