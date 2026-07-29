<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
requireLogin();
$pageTitle = 'Module Not Available';
include __DIR__ . '/../includes/header.php';
?>
  <div class="page-toolbar"><div><h1>Module not available</h1><p>This feature has not been included in the current project files.</p></div></div>
  <section class="card"><div class="card-body">
    <div class="alert alert-info">This navigation destination was removed to prevent a 404 error. Add the module file before enabling its menu link.</div>
    <a class="btn btn-primary" href="<?= htmlspecialchars(getDashboardPath(getCurrentRoleId())) ?>">Return to dashboard</a>
  </div></section>
<?php include __DIR__ . '/../includes/footer.php'; ?>

