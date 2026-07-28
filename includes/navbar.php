<?php
/**
 * Top navigation bar
 * No Bootstrap / Font Awesome / external frameworks
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/role_check.php';
require_once __DIR__ . '/functions.php';

$currentUser = getCurrentUser();
$displayName = $currentUser ? trim($currentUser['full_name']) : 'Guest';
if ($displayName === '') {
    $displayName = 'System User';
}
$roleLabel = $currentUser['role_name'] ?? 'System User';
$unreadCount = 0;

if ($currentUser) {
    try {
        $unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $unreadStmt->execute([$currentUser['id']]);
        $unreadCount = (int) $unreadStmt->fetchColumn();
    } catch (Throwable $e) {
        $unreadCount = 0;
    }
}

$homeUrl = $currentUser ? getDashboardPath($currentUser['role_id']) : app_url('/');
$initial = $currentUser ? strtoupper(substr($currentUser['full_name'], 0, 1)) : 'U';
?>
<header class="topbar">
  <div class="topbar-inner">

    <!-- Brand -->
    <a class="topbar-brand" href="<?= htmlspecialchars($homeUrl) ?>">
      <span class="topbar-brand-mark">◆</span>
      <span class="topbar-brand-text">UCSC SIS</span>
    </a>

    <!-- Mobile menu toggle -->
    <button type="button" class="topbar-toggle" id="topbarToggle" aria-label="Open menu">
      ☰
    </button>

    <!-- Search + actions -->
    <div class="topbar-panel" id="topbarPanel">

      <div class="topbar-search">
        <span class="topbar-search-icon" aria-hidden="true">⌕</span>
        <input
          class="topbar-search-input"
          type="search"
          placeholder="Search instructors, courses, rooms, requests..."
          aria-label="Search"
        >
      </div>

      <?php if ($currentUser): ?>
      <nav class="topbar-actions" aria-label="User actions">

        <!-- Quick Actions -->
        <div class="menu">
          <button type="button" class="menu-toggle topbar-btn">
            ⚡ Quick Actions ▾
          </button>
          <ul class="menu-list" hidden>
            <li><a href="<?= app_url('coordinator/additional_tasks.php') ?>">＋ Additional Task</a></li>
            <li><a href="<?= app_url('rooms/bookings.php') ?>">⌂ Book Lecture Hall</a></li>
            <li><a href="<?= app_url('notifications.php') ?>">🔔 Notifications</a></li>
          </ul>
        </div>

        <!-- Notifications -->
        <div class="menu">
          <button type="button" class="menu-toggle topbar-icon-btn" aria-label="Notifications">
            🔔
            <?php if ($unreadCount > 0): ?>
              <span class="topbar-badge"><?= (int) $unreadCount ?></span>
            <?php endif; ?>
          </button>
          <ul class="menu-list menu-list-wide" hidden>
            <li class="menu-heading">Notifications</li>
            <?php
            $notifications = [];
            try {
                $st = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
                $st->execute([$currentUser['id']]);
                $notifications = $st->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {
                $notifications = [];
            }
            if (!$notifications): ?>
              <li class="menu-empty">No new notifications</li>
            <?php else: ?>
              <?php foreach ($notifications as $n): ?>
                <li>
                  <a href="<?= app_url('notifications.php') ?>">
                    <strong><?= htmlspecialchars($n['title']) ?></strong>
                    <small><?= htmlspecialchars(substr($n['message'], 0, 72)) ?>...</small>
                  </a>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
            <li class="menu-divider"></li>
            <li class="menu-footer">
              <a href="<?= app_url('notifications.php') ?>">View all</a>
            </li>
          </ul>
        </div>

        <!-- User menu -->
        <div class="menu">
          <button type="button" class="menu-toggle topbar-user" aria-label="User menu">
            <span class="topbar-avatar"><?= htmlspecialchars($initial) ?></span>
            <span class="topbar-user-text">
              <span class="topbar-user-name"><?= htmlspecialchars($displayName) ?></span>
              <small class="topbar-user-role"><?= htmlspecialchars($roleLabel) ?></small>
            </span>
            <span class="topbar-caret">▾</span>
          </button>
          <ul class="menu-list" hidden>
            <li><a href="<?= app_url('profile.php') ?>">👤 Profile</a></li>
            <li><a href="<?= app_url('notifications.php') ?>">🔔 Notifications</a></li>
            <li class="menu-divider"></li>
            <li><a class="menu-danger" href="<?= app_url('auth/logout.php') ?>">⎋ Logout</a></li>
          </ul>
        </div>

      </nav>
      <?php endif; ?>

    </div>
  </div>
</header>