<?php
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
?>
<aside class="sidebar collapse d-md-block" id="sidebarMenu">
  <div class="sidebar-brand">
    <img src="<?= app_url('assets/images/ucsc-logo.png') ?>" alt="UCSC Logo" class="ucsc-logo">
    <h2 class="sidebar-title">Smart Instructor System</h2>
    <p class="sidebar-subtitle">University of Colombo<br>School of Computing</p>
  </div>
  <ul class="nav flex-column">
    <li class="nav-item"><a class="nav-link <?= $currentPage=='dashboard.php'?'active':'' ?>" href="<?= getDashboardPath($currentRole) ?>"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
    <?php if ($currentRole == ROLE_ADMIN): ?>
      <li class="sidebar-section">Administration</li>
      <li class="nav-item"><a class="nav-link <?= nav_active('users.php') ?>" href="<?= app_url('admin/users.php') ?>"><i class="fas fa-users"></i><span>Users</span></a></li>
      <li class="nav-item"><a class="nav-link <?= nav_active('roles.php') ?>" href="<?= app_url('admin/roles.php') ?>"><i class="fas fa-shield-halved"></i><span>Roles</span></a></li>
      <li class="nav-item"><a class="nav-link <?= nav_active('activity_logs.php') ?>" href="<?= app_url('admin/activity_logs.php') ?>"><i class="fas fa-clock-rotate-left"></i><span>Activity Logs</span></a></li>
      <li class="nav-item"><a class="nav-link <?= nav_active('settings.php') ?>" href="<?= app_url('admin/settings.php') ?>"><i class="fas fa-gear"></i><span>Settings</span></a></li>
    <?php elseif ($currentRole == ROLE_INSTRUCTOR): ?>
      <li class="sidebar-section">Instructor</li>
      <li class="nav-item"><a class="nav-link <?= nav_active('timetable.php') ?>" href="<?= app_url('instructor/timetable.php') ?>"><i class="fas fa-calendar-days"></i><span>Timetable</span></a></li>
      <li class="nav-item"><a class="nav-link <?= nav_active('assigned_tasks.php') ?>" href="<?= app_url('instructor/assigned_tasks.php') ?>"><i class="fas fa-list-check"></i><span>Assigned Tasks</span></a></li>
      <li class="nav-item"><a class="nav-link <?= nav_active('workload.php') ?>" href="<?= app_url('instructor/workload.php') ?>"><i class="fas fa-chart-line"></i><span>Workload</span></a></li>
      <li class="nav-item"><a class="nav-link <?= nav_active('leave.php') ?>" href="<?= app_url('instructor/leave.php') ?>"><i class="fas fa-person-walking-arrow-right"></i><span>Leave Records</span></a></li>
      <li class="nav-item"><a class="nav-link <?= nav_active('replacement_requests.php') ?>" href="<?= app_url('instructor/replacement_requests.php') ?>"><i class="fas fa-right-left"></i><span>Replacement Requests</span></a></li>
    <?php elseif ($currentRole == ROLE_COORDINATOR): ?>
      <li class="sidebar-section">Coordination</li>
      <li class="nav-item"><a class="nav-link <?= nav_active('instructors.php') ?>" href="<?= app_url('coordinator/instructors.php') ?>"><i class="fas fa-person-chalkboard"></i><span>Instructors</span></a></li>
      <li class="nav-item"><a class="nav-link <?= nav_active('additional_tasks.php') ?>" href="<?= app_url('coordinator/additional_tasks.php') ?>"><i class="fas fa-square-plus"></i><span>Additional Tasks</span></a></li>
      <li class="nav-item"><a class="nav-link <?= nav_active('smart_suggestions.php') ?>" href="<?= app_url('coordinator/smart_suggestions.php') ?>"><i class="fas fa-wand-magic-sparkles"></i><span>Smart Suggestions</span></a></li>
      <li class="nav-item"><a class="nav-link <?= nav_active('replacements.php') ?>" href="<?= app_url('coordinator/replacements.php') ?>"><i class="fas fa-right-left"></i><span>Replacements</span></a></li>
      <li class="nav-item"><a class="nav-link <?= nav_active('urgency_replacements.php') ?>" href="<?= app_url('coordinator/urgency_replacements.php') ?>"><i class="fas fa-triangle-exclamation"></i><span>Urgency Alerts</span></a></li>
      <li class="nav-item"><a class="nav-link <?= nav_active('leave_records.php') ?>" href="<?= app_url('coordinator/leave_records.php') ?>"><i class="fas fa-file-medical"></i><span>Leave Records</span></a></li>
    <?php elseif ($currentRole == ROLE_CHIEF_COORDINATOR): ?>
      <li class="sidebar-section">Chief Coordinator</li>
      <li class="nav-item"><a class="nav-link <?= nav_active('allocations.php') ?>" href="<?= app_url('chief_coordinator/allocations.php') ?>"><i class="fas fa-diagram-project"></i><span>Allocations</span></a></li>
      <li class="nav-item"><a class="nav-link <?= nav_active('workload_monitoring.php') ?>" href="<?= app_url('chief_coordinator/workload_monitoring.php') ?>"><i class="fas fa-chart-pie"></i><span>Workload</span></a></li>
      <li class="nav-item"><a class="nav-link <?= nav_active('leave_records.php') ?>" href="<?= app_url('chief_coordinator/leave_records.php') ?>"><i class="fas fa-file-medical"></i><span>Leave Records</span></a></li>
      <li class="nav-item"><a class="nav-link <?= nav_active('reports.php') ?>" href="<?= app_url('chief_coordinator/reports.php') ?>"><i class="fas fa-file-lines"></i><span>Reports</span></a></li>
    <?php elseif ($currentRole == ROLE_NON_ACADEMIC): ?>
      <li class="sidebar-section">Operations</li>
      <li class="nav-item"><a class="nav-link <?= nav_active('timetable_records.php') ?>" href="<?= app_url('non_academic/timetable_records.php') ?>"><i class="fas fa-calendar-days"></i><span>Timetable</span></a></li>
      <li class="nav-item"><a class="nav-link <?= nav_active('room_schedules.php') ?>" href="<?= app_url('non_academic/room_schedules.php') ?>"><i class="fas fa-building"></i><span>Room Schedules</span></a></li>
      <li class="nav-item"><a class="nav-link <?= nav_active('leave_notifications.php') ?>" href="<?= app_url('non_academic/leave_notifications.php') ?>"><i class="fas fa-bell"></i><span>Leave Alerts</span></a></li>
    <?php elseif ($currentRole == ROLE_PROJECT_COORDINATOR): ?>
      <li class="sidebar-section">Presentations</li>
      <li class="nav-item"><a class="nav-link <?= nav_active('presentation_sessions.php') ?>" href="<?= app_url('project_coordinator/presentation_sessions.php') ?>"><i class="fas fa-display"></i><span>Sessions</span></a></li>
      <li class="nav-item"><a class="nav-link <?= nav_active('schedule_session.php') ?>" href="<?= app_url('project_coordinator/schedule_session.php') ?>"><i class="fas fa-calendar-plus"></i><span>Schedule Session</span></a></li>
      <li class="nav-item"><a class="nav-link <?= nav_active('presentation_panels.php') ?>" href="<?= app_url('project_coordinator/presentation_panels.php') ?>"><i class="fas fa-users-gear"></i><span>Panels</span></a></li>
    <?php elseif ($currentRole == ROLE_DIRECTOR): ?>
      <li class="sidebar-section">Monitoring</li>
      <li class="nav-item"><a class="nav-link <?= nav_active('workload_distribution.php') ?>" href="<?= app_url('director/workload_distribution.php') ?>"><i class="fas fa-chart-pie"></i><span>Workload</span></a></li>
      <li class="nav-item"><a class="nav-link <?= nav_active('leave_records.php') ?>" href="<?= app_url('director/leave_records.php') ?>"><i class="fas fa-file-medical"></i><span>Leave Records</span></a></li>
      <li class="nav-item"><a class="nav-link <?= nav_active('allocation_monitoring.php') ?>" href="<?= app_url('director/allocation_monitoring.php') ?>"><i class="fas fa-eye"></i><span>Allocations</span></a></li>
      <li class="nav-item"><a class="nav-link <?= nav_active('reports.php') ?>" href="<?= app_url('director/reports.php') ?>"><i class="fas fa-file-lines"></i><span>Reports</span></a></li>
    <?php endif; ?>
    <li class="sidebar-section">Common</li>
    <li class="nav-item"><a class="nav-link <?= nav_active('bookings.php') ?>" href="<?= app_url('rooms/bookings.php') ?>"><i class="fas fa-door-open"></i><span>Lecture Hall Booking</span></a></li>
    <li class="nav-item"><a class="nav-link <?= nav_active('notifications.php') ?>" href="<?= app_url('notifications.php') ?>"><i class="fas fa-bell"></i><span>Notifications</span><span class="badge rounded-pill">6</span></a></li>
    <li class="nav-item"><a class="nav-link <?= nav_active('profile.php') ?>" href="<?= app_url('profile.php') ?>"><i class="fas fa-user"></i><span>Profile</span></a></li>
  </ul>
  <div class="sidebar-bottom-card">
    <div class="d-flex align-items-center gap-2">
      <span class="sidebar-user-avatar"><?= $currentUser ? strtoupper(substr($currentUser['full_name'],0,1)) : 'U' ?></span>
      <div class="lh-sm"><strong><?= htmlspecialchars($currentUser['full_name'] ?? 'UCSC User') ?></strong><br><small class="text-white-50"><?= htmlspecialchars($currentUser['role_name'] ?? 'System User') ?></small></div>
    </div>
  </div>
</aside>
