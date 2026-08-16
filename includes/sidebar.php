<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/role_check.php';
$currentRole = (int)getCurrentRoleId();
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$currentUser = getCurrentUser() ?: [];
if (!function_exists('sidebar_link')) {
    function sidebar_link(string $path, string $label, string $icon, string $currentPage): void {
        if (!project_file_exists($path)) { return; }
        $active = basename($path) === $currentPage ? ' active' : '';
        $iconUrl = app_url('assets/icons/' . $icon . '.svg');
        echo '<li><a class="sidebar-link' . $active . '" href="' . htmlspecialchars(app_url($path)) . '">';
        echo '<span class="sidebar-icon"><img src="' . htmlspecialchars($iconUrl) . '" alt=""></span>';
        echo '<span>' . htmlspecialchars($label) . '</span></a></li>';
    }
}
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-head">
    <span class="sidebar-emblem"><img src="<?= app_url('assets/images/ucsc-logo.png') ?>" alt="UCSC"></span>
    <div><strong>Smart Instructor</strong><small>Management System</small></div>
    <button class="sidebar-close" type="button" data-sidebar-toggle aria-label="Close navigation">×</button>
  </div>
  <nav aria-label="Main navigation">
    <p class="sidebar-label">Workspace</p>
    <ul class="sidebar-nav">
      <?php
      $dashMap = [
        ROLE_ADMIN=>'admin/dashboard.php', ROLE_INSTRUCTOR=>'instructor/dashboard.php',
        ROLE_COORDINATOR=>'coordinator/dashboard.php', ROLE_CHIEF_COORDINATOR=>'chief_coordinator/dashboard.php',
        ROLE_NON_ACADEMIC=>'non_academic/dashboard.php', ROLE_PROJECT_COORDINATOR=>'project_coordinator/coordinator_dashboard.php',
        ROLE_DIRECTOR=>'director/dashboard.php'
      ];
      sidebar_link($dashMap[$currentRole] ?? 'index.php', 'Dashboard', 'chart-column', $currentPage);

      if ($currentRole === ROLE_ADMIN) {
        echo '<p class="sidebar-label">Administration</p>';
        sidebar_link('admin/users.php','Users','users',$currentPage);
        sidebar_link('admin/roles.php','Roles & access','shield',$currentPage);
        sidebar_link('admin/activity_logs.php','Activity logs','history',$currentPage);
        sidebar_link('admin/settings.php','System settings','settings',$currentPage);
      }

      if ($currentRole === ROLE_COORDINATOR) {
        echo '<p class="sidebar-label">Instructors</p>';
        sidebar_link('coordinator/instructor.php','All Instructors','users',$currentPage);
        sidebar_link('coordinator/availability.php','Availability','calendar',$currentPage);
        echo '<p class="sidebar-label">Tasks &amp; Coordination</p>';
        sidebar_link('coordinator/additional_tasks.php','Additional Tasks','square-pen',$currentPage);
        sidebar_link('coordinator/smart_suggestions.php','Smart Suggestions','search',$currentPage);
        sidebar_link('coordinator/replacements.php','Replacement Requests','user-check',$currentPage);
        sidebar_link('coordinator/urgency_replacements.php','Urgency Replacements','bell',$currentPage);
        echo '<p class="sidebar-label">Records</p>';
        sidebar_link('coordinator/leave_records.php','Leave Records','history',$currentPage);
      }

      if ($currentRole === ROLE_INSTRUCTOR) {
        echo '<p class="sidebar-label">My Work</p>';
        sidebar_link('instructor/my_tasks.php','My Tasks','briefcase-business',$currentPage);
        sidebar_link('instructor/timetable.php','Timetable','calendar',$currentPage);
        sidebar_link('instructor/workload.php','Workload','chart-column',$currentPage);
        echo '<p class="sidebar-label">Coordination</p>';
        sidebar_link('instructor/leave_notification.php','Leave & Notifications','history',$currentPage);
        sidebar_link('instructor/replacement_request.php','Replacements','user-check',$currentPage);
        echo '<p class="sidebar-label">Account</p>';
        sidebar_link('instructor/setting.php','Settings','settings',$currentPage);
      }

if ($currentRole === ROLE_DIRECTOR) {
        echo '<p class="sidebar-label">Monitoring &amp; Oversight</p>';
        sidebar_link('director/active_tasks.php', 'Active Tasks', 'briefcase-business', $currentPage);
        sidebar_link('director/leave_records.php', 'Leave Records', 'history', $currentPage);
        sidebar_link('director/replacements.php', 'Replacements', 'user-check', $currentPage);
        sidebar_link('director/room_usage.php', 'Room Usage', 'calendar', $currentPage);
        sidebar_link('director/reports.php', 'Reports', 'chart-column', $currentPage);
        sidebar_link('director/allocations.php', 'Allocation Status', 'square-pen', $currentPage);
      }

      if ($currentRole === ROLE_PROJECT_COORDINATOR) {
        echo '<p class="sidebar-label">Presentation Coordination</p>';
        sidebar_link('project_coordinator/sessions.php','Sessions & Venues','calendar',$currentPage);
        echo '<p class="sidebar-label">Account</p>';
        sidebar_link('project_coordinator/setting.php','Settings','settings',$currentPage);
      }
      
      ?>
    </ul>
  </nav>
  <div class="sidebar-user">
    <span class="avatar"><?= htmlspecialchars(strtoupper(substr((string)($currentUser['full_name'] ?? 'U'),0,1))) ?></span>
    <span><strong><?= htmlspecialchars($currentUser['full_name'] ?? 'UCSC User') ?></strong><small><?= htmlspecialchars($currentUser['role_name'] ?? 'System User') ?></small></span>
  </div>
</aside>
<div class="sidebar-backdrop" data-sidebar-toggle></div>