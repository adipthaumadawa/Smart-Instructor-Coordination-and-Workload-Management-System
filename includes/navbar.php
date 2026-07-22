<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/role_check.php';
require_once __DIR__ . '/functions.php';
$currentUser = getCurrentUser();
$displayName = $currentUser ? trim($currentUser['full_name']) : 'Guest';
if ($displayName === '') { $displayName = 'System User'; }
$roleLabel = $currentUser['role_name'] ?? 'System User';
$unreadCount = 0;
if ($currentUser) {
    try {
        $unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $unreadStmt->execute([$currentUser['id']]);
        $unreadCount = (int)$unreadStmt->fetchColumn();
    } catch (Throwable $e) { $unreadCount = 0; }
}
?>
<nav class="navbar navbar-expand-lg navbar-light sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $currentUser ? getDashboardPath($currentUser['role_id']) : app_url('/') ?>">
      <span class="brand-mark"><i class="fas fa-graduation-cap"></i></span>
      <span>UCSC SIS</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <div class="mx-lg-auto my-3 my-lg-0 sic-search-wrap">
        <i class="fas fa-search"></i>
        <input class="form-control sic-search" type="search" placeholder="Search instructors, courses, rooms, requests...">
      </div>
      <?php if ($currentUser): ?>
      <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
        <li class="nav-item dropdown">
          <a class="nav-link quick-action-btn" href="#" data-bs-toggle="dropdown"><i class="fas fa-bolt"></i> Quick Actions <i class="fas fa-chevron-down small"></i></a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= app_url('coordinator/additional_tasks.php') ?>"><i class="fas fa-plus-circle me-2"></i>Additional Task</a></li>
            <li><a class="dropdown-item" href="<?= app_url('rooms/bookings.php') ?>"><i class="fas fa-door-open me-2"></i>Book Lecture Hall</a></li>
            <li><a class="dropdown-item" href="<?= app_url('notifications.php') ?>"><i class="fas fa-bell me-2"></i>Notifications</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link nav-icon-btn" href="#" data-bs-toggle="dropdown"><i class="fas fa-bell"></i><?php if($unreadCount>0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $unreadCount ?></span><?php endif; ?></a>
          <ul class="dropdown-menu dropdown-menu-end" style="width:340px;max-height:420px;overflow-y:auto">
            <li class="dropdown-header fw-bold text-dark">Notifications</li>
            <?php
            $notifications=[];
            try { $st=$pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 5"); $st->execute([$currentUser['id']]); $notifications=$st->fetchAll(PDO::FETCH_ASSOC); } catch(Throwable $e) {}
            if(!$notifications): ?>
              <li><span class="dropdown-item-text text-muted px-3">No new notifications</span></li>
            <?php else: foreach($notifications as $n): ?>
              <li><a class="dropdown-item" href="<?= app_url('notifications.php') ?>"><strong><?= htmlspecialchars($n['title']) ?></strong><br><small class="text-muted"><?= htmlspecialchars(substr($n['message'],0,72)) ?>...</small></a></li>
            <?php endforeach; endif; ?>
            <li><hr class="dropdown-divider"></li><li><a class="dropdown-item text-center fw-bold" href="<?= app_url('notifications.php') ?>">View all</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle user-menu-link" href="#" role="button" data-bs-toggle="dropdown">
            <span class="user-avatar"><?= strtoupper(substr($currentUser['full_name'],0,1)) ?></span>
            <span class="user-menu-text"><span class="user-menu-name"><?= htmlspecialchars($displayName) ?></span><small><?= htmlspecialchars($roleLabel) ?></small></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= app_url('profile.php') ?>"><i class="fas fa-user me-2"></i>Profile</a></li>
            <li><a class="dropdown-item" href="<?= app_url('notifications.php') ?>"><i class="fas fa-bell me-2"></i>Notifications</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="<?= app_url('auth/logout.php') ?>"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
          </ul>
        </li>
      </ul>
      <?php endif; ?>
    </div>
  </div>
</nav>
