<?php
/**
 * Admin - Manage Roles
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php'; // Required for sic_user_avatar() in navbar.php
require_once __DIR__ . '/../config/db.php';

checkRole(ROLE_ADMIN);

$pageTitle = "Manage Roles";
include __DIR__ . '/../includes/header.php';

// Get all roles
$roles = $pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll();
?>

            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><span class="ui-dot" aria-hidden="true"></span>Manage Roles</h1>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Role Name</th>
                                    <th>Description</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roles as $index => $role): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($role['role_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($role['description']) ?></td>
                                    <td><?= date('d M Y', strtotime($role['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <strong>Note:</strong> Roles are predefined in the system. You can modify descriptions if needed by editing the database directly or extending this page.
                    </div>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>