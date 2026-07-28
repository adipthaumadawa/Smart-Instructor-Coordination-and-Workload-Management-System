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
<nav>
  <div>
    <a href="<?= $currentUser ? getDashboardPath($currentUser['role_id']) : app_url('/') ?>">
      <span><i></i></span>
      <span>UCSC SIS</span>
    </a>
    <button type="button"><span></span></button>
    <div>
      <div>
        <i></i>
        <input type="search" placeholder="Search instructors, courses, rooms, requests...">
      </div>
      <?php if ($currentUser): ?>
      <ul>
        <li>
          <a href="#"><i></i> Quick Actions <i></i></a>
          <ul>
            <li><a href="<?= app_url('coordinator/additional_tasks.php') ?>"><i></i>Additional Task</a></li>
            <li><a href="<?= app_url('rooms/bookings.php') ?>"><i></i>Book Lecture Hall</a></li>
            <li><a href="<?= app_url('notifications.php') ?>"><i></i>Notifications</a></li>
          </ul>
        </li>
        <li>
          <a href="#"><i></i><?php if($unreadCount>0): ?><span><?= $unreadCount ?></span><?php endif; ?></a>
          <ul style="width:340px;max-height:420px;overflow-y:auto">
            <li>Notifications</li>
            <?php
            $notifications=[];
            try { $st=$pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 5"); $st->execute([$currentUser['id']]); $notifications=$st->fetchAll(PDO::FETCH_ASSOC); } catch(Throwable $e) {}
            if(!$notifications): ?>
              <li><span>No new notifications</span></li>
            <?php else: foreach($notifications as $n): ?>
              <li><a href="<?= app_url('notifications.php') ?>"><strong><?= htmlspecialchars($n['title']) ?></strong><br><small><?= htmlspecialchars(substr($n['message'],0,72)) ?>...</small></a></li>
            <?php endforeach; endif; ?>
            <li><hr></li><li><a href="<?= app_url('notifications.php') ?>">View all</a></li>
          </ul>
        </li>
        <li>
          <a href="#" role="button">
            <span><?= strtoupper(substr($currentUser['full_name'],0,1)) ?></span>
            <span><span><?= htmlspecialchars($displayName) ?></span><small><?= htmlspecialchars($roleLabel) ?></small></span>
          </a>
          <ul>
            <li><a href="<?= app_url('profile.php') ?>"><i></i>Profile</a></li>
            <li><a href="<?= app_url('notifications.php') ?>"><i></i>Notifications</a></li>
            <li><hr></li>
            <li><a href="<?= app_url('auth/logout.php') ?>"><i></i>Logout</a></li>
          </ul>
        </li>
      </ul>
      <?php endif; ?>
    </div>
  </div>
</nav>