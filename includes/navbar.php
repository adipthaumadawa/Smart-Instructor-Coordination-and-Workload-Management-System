<?php
$roleId = function_exists('getCurrentRoleId') ? (int)getCurrentRoleId() : 0;
$dashboardUrl = function_exists('getDashboardPath') ? getDashboardPath($roleId) : app_url('index.php');

// Point admins to admin settings, and all other users to the instructor settings page
$settingsUrl = $roleId === ROLE_ADMIN ? app_url('admin/settings.php') : app_url('instructor/setting.php');

$unreadCount = 0;
if (!empty($currentUser['id']) && isset($pdo)) {
    try {
        $q = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $q->execute([(int)$currentUser['id']]);
        $unreadCount = (int)$q->fetchColumn();
    } catch (Throwable $e) { 
        $unreadCount = 0; 
    }
}

// Avatar image URL resolution
$userAvatarUrl = !empty($_SESSION['avatar_url']) ? app_url($_SESSION['avatar_url']) : (!empty($currentUser['avatar_url']) ? app_url($currentUser['avatar_url']) : null);

// Strip academic/honorific titles (Dr., Prof., etc.) to extract a clean single-character initial fallback
$cleanName = trim(preg_replace('/^(Dr\.|Prof\.|Mr\.|Mrs\.|Ms\.)\s+/i', '', $displayName ?? 'User'));
$firstInitial = mb_substr($cleanName, 0, 1);
?>
<header class="topbar">
  <div class="topbar-inner">
    <div class="topbar-left">
      <div class="global-search" role="search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
        <input id="globalSearch" type="search" placeholder="Search..." aria-label="Search this page">
        <kbd>Ctrl K</kbd>
      </div>
    </div>

    <div class="top-actions">
      <?php if ($roleId === ROLE_ADMIN): ?>
      <div class="menu-wrap">
        <button class="primary-action" type="button" data-menu-button="quickMenu" aria-expanded="false">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
          <span>Add User</span>
        </button>
        <div class="dropdown-menu" id="quickMenu" hidden>
          <a href="<?= app_url('admin/add_user.php') ?>"><strong>Add new user</strong><small>Create a system account</small></a>
          <a href="<?= app_url('admin/users.php') ?>"><strong>Manage users</strong><small>Review all accounts</small></a>
        </div>
      </div>
      <?php endif; ?>

      <div class="menu-wrap">
        <button class="profile-button" type="button" data-menu-button="profileMenu" aria-expanded="false">
          <?= sic_user_avatar($userAvatarUrl, $firstInitial, 'avatar') ?>
          <span class="profile-copy">
            <strong><?= htmlspecialchars($displayName) ?></strong>
            <small><?= htmlspecialchars($roleName) ?></small>
          </span>
          <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m8 10 4 4 4-4"/></svg>
        </button>
        <div class="dropdown-menu" id="profileMenu" hidden>
          <div class="menu-identity">
            <?= sic_user_avatar($userAvatarUrl, $firstInitial, 'avatar') ?>
            <span>
              <strong><?= htmlspecialchars($displayName) ?></strong>
              <small><?= htmlspecialchars($roleName) ?></small>
            </span>
          </div>
          <a href="<?= htmlspecialchars($settingsUrl) ?>">
            <strong><?= $roleId === ROLE_ADMIN ? 'System settings' : 'Profile Settings' ?></strong>
            <small><?= $roleId === ROLE_ADMIN ? 'Configure the application' : 'Manage account details' ?></small>
          </a>
          <a class="danger-link" href="<?= app_url('auth/logout.php') ?>"><strong>Sign out</strong><small>End this session</small></a>
        </div>
      </div>
    </div>
  </div>
</header>