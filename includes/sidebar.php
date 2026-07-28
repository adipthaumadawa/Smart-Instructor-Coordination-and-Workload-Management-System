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
<aside id="sidebarMenu">
  <div>
    <img src="<?= app_url('assets/images/ucsc-logo.png') ?>" alt="UCSC Logo">
    <h2>Smart Instructor System</h2>
    <p>University of Colombo<br>School of Computing</p>
  </div>
  <ul>
    <li><a class="<?= $currentPage=='dashboard.php'?'active':'' ?>" href="<?= getDashboardPath($currentRole) ?>"><i>home</i><span>Dashboard</span></a></li>
    <?php if ($currentRole == ROLE_ADMIN): ?>
      <li>Administration</li>
      <li><a class="<?= nav_active('users.php') ?>" href="<?= app_url('admin/users.php') ?>"><i>group</i><span>Users</span></a></li>
      <li><a class="<?= nav_active('roles.php') ?>" href="<?= app_url('admin/roles.php') ?>"><i>security</i><span>Roles</span></a></li>
      <li><a class="<?= nav_active('activity_logs.php') ?>" href="<?= app_url('admin/activity_logs.php') ?>"><i>history</i><span>Activity Logs</span></a></li>
      <li><a class="<?= nav_active('settings.php') ?>" href="<?= app_url('admin/settings.php') ?>"><i>settings</i><span>Settings</span></a></li>
    <?php elseif ($currentRole == ROLE_INSTRUCTOR): ?>
      <li>Instructor</li>
      <li><a class="<?= nav_active('timetable.php') ?>" href="<?= app_url('instructor/timetable.php') ?>"><i>calendar_month</i><span>Timetable</span></a></li>
      <li><a class="<?= nav_active('assigned_tasks.php') ?>" href="<?= app_url('instructor/assigned_tasks.php') ?>"><i>task</i><span>Assigned Tasks</span></a></li>
      <li><a class="<?= nav_active('workload.php') ?>" href="<?= app_url('instructor/workload.php') ?>"><i>trending_up</i><span>Workload</span></a></li>
      <li><a class="<?= nav_active('leave.php') ?>" href="<?= app_url('instructor/leave.php') ?>"><i>exit_to_app</i><span>Leave Records</span></a></li>
      <li><a class="<?= nav_active('replacement_requests.php') ?>" href="<?= app_url('instructor/replacement_requests.php') ?>"><i>swap_horiz</i><span>Replacement Requests</span></a></li>
    <?php elseif ($currentRole == ROLE_COORDINATOR): ?>
      <li>Coordination</li>
      <li><a class="<?= nav_active('instructors.php') ?>" href="<?= app_url('coordinator/instructors.php') ?>"><i>school</i><span>Instructors</span></a></li>
      <li><a class="<?= nav_active('additional_tasks.php') ?>" href="<?= app_url('coordinator/additional_tasks.php') ?>"><i>library_add</i><span>Additional Tasks</span></a></li>
      <li><a class="<?= nav_active('smart_suggestions.php') ?>" href="<?= app_url('coordinator/smart_suggestions.php') ?>"><i>auto_awesome</i><span>Smart Suggestions</span></a></li>
      <li><a class="<?= nav_active('replacements.php') ?>" href="<?= app_url('coordinator/replacements.php') ?>"><i>swap_horiz</i><span>Replacements</span></a></li>
      <li><a class="<?= nav_active('urgency_replacements.php') ?>" href="<?= app_url('coordinator/urgency_replacements.php') ?>"><i>warning</i><span>Urgency Alerts</span></a></li>
      <li><a class="<?= nav_active('leave_records.php') ?>" href="<?= app_url('coordinator/leave_records.php') ?>"><i>medical_services</i><span>Leave Records</span></a></li>
    <?php elseif ($currentRole == ROLE_CHIEF_COORDINATOR): ?>
      <li>Chief Coordinator</li>
      <li><a class="<?= nav_active('allocations.php') ?>" href="<?= app_url('chief_coordinator/allocations.php') ?>"><i>account_tree</i><span>Allocations</span></a></li>
      <li><a class="<?= nav_active('workload_monitoring.php') ?>" href="<?= app_url('chief_coordinator/workload_monitoring.php') ?>"><i>pie_chart</i><span>Workload</span></a></li>
      <li><a class="<?= nav_active('leave_records.php') ?>" href="<?= app_url('chief_coordinator/leave_records.php') ?>"><i>medical_services</i><span>Leave Records</span></a></li>
      <li><a class="<?= nav_active('reports.php') ?>" href="<?= app_url('chief_coordinator/reports.php') ?>"><i>description</i><span>Reports</span></a></li>
    <?php elseif ($currentRole == ROLE_NON_ACADEMIC): ?>
      <li>Operations</li>
      <li><a class="<?= nav_active('timetable_records.php') ?>" href="<?= app_url('non_academic/timetable_records.php') ?>"><i>calendar_month</i><span>Timetable</span></a></li>
      <li><a class="<?= nav_active('room_schedules.php') ?>" href="<?= app_url('non_academic/room_schedules.php') ?>"><i>apartment</i><span>Room Schedules</span></a></li>
      <li><a class="<?= nav_active('leave_notifications.php') ?>" href="<?= app_url('non_academic/leave_notifications.php') ?>"><i>notifications</i><span>Leave Alerts</span></a></li>
    <?php elseif ($currentRole == ROLE_PROJECT_COORDINATOR): ?>
      <li>Presentations</li>
      <li><a class="<?= nav_active('presentation_sessions.php') ?>" href="<?= app_url('project_coordinator/presentation_sessions.php') ?>"><i>display_settings</i><span>Sessions</span></a></li>
      <li><a class="<?= nav_active('schedule_session.php') ?>" href="<?= app_url('project_coordinator/schedule_session.php') ?>"><i>event_available</i><span>Schedule Session</span></a></li>
      <li><a class="<?= nav_active('presentation_panels.php') ?>" href="<?= app_url('project_coordinator/presentation_panels.php') ?>"><i>group_work</i><span>Panels</span></a></li>
    <?php elseif ($currentRole == ROLE_DIRECTOR): ?>
      <li>Monitoring</li>
      <li><a class="<?= nav_active('workload_distribution.php') ?>" href="<?= app_url('director/workload_distribution.php') ?>"><i>pie_chart</i><span>Workload</span></a></li>
      <li><a class="<?= nav_active('leave_records.php') ?>" href="<?= app_url('director/leave_records.php') ?>"><i>medical_services</i><span>Leave Records</span></a></li>
      <li><a class="<?= nav_active('allocation_monitoring.php') ?>" href="<?= app_url('director/allocation_monitoring.php') ?>"><i>visibility</i><span>Allocations</span></a></li>
      <li><a class="<?= nav_active('reports.php') ?>" href="<?= app_url('director/reports.php') ?>"><i>description</i><span>Reports</span></a></li>
    <?php endif; ?>
    <li>Common</li>
    <li><a class="<?= nav_active('bookings.php') ?>" href="<?= app_url('rooms/bookings.php') ?>"><i>meeting_room</i><span>Lecture Hall Booking</span></a></li>
    <li><a class="<?= nav_active('notifications.php') ?>" href="<?= app_url('notifications.php') ?>"><i>notifications</i><span>Notifications</span><span>6</span></a></li>
    <li><a class="<?= nav_active('profile.php') ?>" href="<?= app_url('profile.php') ?>"><i>person</i><span>Profile</span></a></li>
  </ul>
  <div>
    <div>
      <span><?= $currentUser ? strtoupper(substr($currentUser['full_name'],0,1)) : 'U' ?></span>
      <div><strong><?= htmlspecialchars($currentUser['full_name'] ?? 'UCSC User') ?></strong><br><small><?= htmlspecialchars($currentUser['role_name'] ?? 'System User') ?></small></div>
    </div>
  </div>
</aside>