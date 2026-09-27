<?php
/**
 * Admin - Manage Users
 * Smart Instructor Coordination and Workload Management System
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php';

checkRole(ROLE_ADMIN);

if (empty($_SESSION['admin_users_csrf'])) {
    $_SESSION['admin_users_csrf'] = bin2hex(random_bytes(32));
}

// All account changes use POST and a session CSRF token.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $targetId = (int)($_POST['user_id'] ?? 0);
    if (!hash_equals($_SESSION['admin_users_csrf'], (string)($_POST['csrf'] ?? ''))) {
        $_SESSION['error'] = 'The form expired. Reload this page and try again.';
    } elseif ($targetId <= 0 || $targetId === (int)($_SESSION['user_id'] ?? 0)) {
        $_SESSION['error'] = 'You cannot change your own account here.';
    } elseif (!in_array($action, ['deactivate', 'activate', 'delete'], true)) {
        $_SESSION['error'] = 'Invalid account action.';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT id, status FROM users WHERE id = ? FOR UPDATE');
            $stmt->execute([$targetId]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$target) {
                $_SESSION['error'] = 'User not found.';
            } elseif ($action === 'deactivate' && $target['status'] === 'active') {
                $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
                $stmt->execute([$targetId]);
                $stmt = $pdo->prepare("UPDATE instructors SET status = 'inactive' WHERE user_id = ?");
                $stmt->execute([$targetId]);
                $_SESSION['success'] = 'User deactivated successfully. The account remains in the list.';
            } elseif ($action === 'activate' && $target['status'] !== 'active') {
                $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                $stmt->execute([$targetId]);
                $stmt = $pdo->prepare("UPDATE instructors SET status = 'active' WHERE user_id = ? AND EXISTS (SELECT 1 FROM users WHERE id = ? AND role_id = ?)");
                $stmt->execute([$targetId, $targetId, ROLE_INSTRUCTOR]);
                $_SESSION['success'] = 'User activated successfully.';
            } elseif ($action === 'delete' && $target['status'] === 'inactive') {
                // Check every operational relationship before deleting a user or their instructor profile.
                // A user with history stays inactive so task, leave, timetable and audit records remain.
                $references = [
                    ['additional_task_requests', 'requested_by', $targetId],
                    ['lecture_hall_bookings', 'booked_by_user_id', $targetId],
                    ['presentation_sessions', 'project_coordinator_id', $targetId],
                    ['task_assignments', 'assigned_by', $targetId],
                    ['urgency_replacements', 'handled_by_coordinator_id', $targetId],
                    ['notifications', 'user_id', $targetId],
                    ['activity_logs', 'user_id', $targetId],
                    ['instructor_attendance', 'recorded_by', $targetId],
                    ['leave_records', 'approved_by', $targetId],
                    ['replacement_requests', 'responded_by', $targetId],
                    ['timetable_requirements', 'created_by', $targetId],
                    ['timetable_slots', 'assigned_by', $targetId],
                ];
                $instStmt = $pdo->prepare('SELECT id FROM instructors WHERE user_id = ?');
                $instStmt->execute([$targetId]);
                $instructorId = (int)$instStmt->fetchColumn();
                if ($instructorId > 0) {
                    foreach ([
                        ['instructor_attendance', 'instructor_id'],
                        ['leave_records', 'instructor_id'],
                        ['presentation_panel_members', 'instructor_id'],
                        ['replacement_requests', 'requested_by_instructor_id'],
                        ['replacement_requests', 'suggested_instructor_id'],
                        ['task_assignments', 'instructor_id'],
                        ['timetable_slots', 'instructor_id'],
                        ['urgency_replacements', 'new_instructor_id'],
                    ] as [$table, $column]) {
                        $references[] = [$table, $column, $instructorId];
                    }
                }
                $hasHistory = false;
                foreach ($references as [$table, $column, $value]) {
                    // Table and column names are fixed in this source file, never taken from a request.
                    $check = $pdo->prepare("SELECT 1 FROM `{$table}` WHERE `{$column}` = ? LIMIT 1");
                    $check->execute([$value]);
                    if ($check->fetchColumn()) {
                        $hasHistory = true;
                        break;
                    }
                }
                if ($hasHistory) {
                    $_SESSION['error'] = 'This account has linked records and cannot be permanently deleted. Keep it inactive or edit its email.';
                } else {
                    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
                    $stmt->execute([$targetId]);
                    $_SESSION['success'] = 'User permanently deleted. The email and username can now be reused.';
                }
            } else {
                $_SESSION['error'] = 'This action is not available for the selected account status.';
            }
            $pdo->commit();
            if (isset($_SESSION['success']) && function_exists('logActivity')) {
                try {
                    logActivity($_SESSION['user_id'] ?? null, ucfirst($action) . ' User', ucfirst($action) . " user ID: {$targetId}");
                } catch (Throwable $logError) {
                    error_log('Admin user action log: ' . $logError->getMessage());
                }
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Admin user action: ' . $e->getMessage());
            $_SESSION['error'] = 'Unable to change this account. Please try again.';
        }
    }
    header('Location: ' . app_url('admin/users.php'));
    exit;
}

$users =$pdo->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Manage Users';
include __DIR__ . '/../includes/header.php';
?>

<style>
.avatar-mini {
    width: 32px !important;
    height: 32px !important;
    min-width: 32px !important;
    min-height: 32px !important;
    flex: 0 0 32px !important;
    display: grid !important;
    place-items: center !important;
    border-radius: 50% !important;
    background: linear-gradient(145deg, var(--teal2, #0a9ba8), var(--teal, #087f8c)) !important;
    color: #ffffff !important;
    font-weight: 800 !important;
    font-size: 13px !important;
    overflow: hidden !important;
}
.avatar-mini img {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    border-radius: 50% !important;
}
.user-cell-flex {
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
    flex-wrap: nowrap !important;
}
.user-cell-flex strong {
    white-space: nowrap !important;
}
/* Action cell alignment fixes */
.action-cell {
    text-align: right !important;
    white-space: nowrap !important;
}
.action-btn-group {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: flex-end !important;
    gap: 8px !important;
}
.action-btn-group .btn {
    margin: 0 !important;
}
</style>

            <div class="page-toolbar">
                <div>
                    <h1>Manage Users</h1>
                    <p>Create, update, deactivate, and manage user accounts.</p>
                </div>
                <a href="<?= app_url('admin/add_user.php') ?>" class="btn btn-primary">
                    <span class="ui-dot" aria-hidden="true"></span>
                    Add New User
                </a>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5>System Users</h5>
                    <span class="text-muted small"><?= count($users) ?> accounts</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle admin-table" id="manageUsersTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $index =>$user): ?>
                                    <tr>
                                        <td data-label="#"><?= $index + 1 ?></td>
                                        <td data-label="User">
                                            <div class="user-cell-flex">
                                                <?php 
                                                    $cleanName = trim(preg_replace('/^(Dr\.|Prof\.|Mr\.|Mrs\.|Ms\.)\s+/i', '', $user['full_name'] ?? 'U'));$initial = mb_substr($cleanName, 0, 1);$avatarUrl = !empty($user['avatar_url']) ? app_url($user['avatar_url']) : null;
                                                ?>
                                                <?= sic_user_avatar($avatarUrl,$initial, 'avatar-mini') ?>
                                                <strong><?= htmlspecialchars($user['full_name']) ?></strong>
                                            </div>
                                        </td>
                                        <td data-label="Username"><?= htmlspecialchars($user['username']) ?></td>
                                        <td data-label="Email"><?= htmlspecialchars($user['email']) ?></td>
                                        <td data-label="Role"><span class="status-pill pill-blue"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $user['role_name']))) ?></span></td>
                                        <td data-label="Status"><?= getStatusBadge($user['status']) ?></td>
                                        <td data-label="Created"><?= !empty($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : 'N/A' ?></td>
                                        <td data-label="Actions" class="text-end action-cell">
                                            <div class="action-btn-group">
                                                <a href="<?= app_url('admin/edit_user.php?id=' . (int)$user['id']) ?>" class="btn btn-sm btn-outline-primary">
                                                    <span class="ui-dot" aria-hidden="true"></span>
                                                    Edit
                                                </a>
                                                <?php if ((int)$user['id'] !== (int)($_SESSION['user_id'] ?? 0)): ?>
                                                    <?php if ($user['status'] === 'active'): ?>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('Deactivate this account? The user will remain in the list.');">
                                                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_users_csrf']) ?>">
                                                            <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                                            <input type="hidden" name="action" value="deactivate">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">Deactivate</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="post" class="d-inline">
                                                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_users_csrf']) ?>">
                                                            <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                                            <input type="hidden" name="action" value="activate">
                                                            <button type="submit" class="btn btn-sm btn-outline-primary">Activate</button>
                                                        </form>
                                                        <?php if ($user['status'] === 'inactive'): ?>
                                                            <form method="post" class="d-inline" onsubmit="return confirm('Permanently delete this account? This cannot be undone. Accounts with linked work records will be protected.');">
                                                                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_users_csrf']) ?>">
                                                                <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                                                <input type="hidden" name="action" value="delete">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('globalSearch');
    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        const query = this.value.trim().toLowerCase();
        const rows = document.querySelectorAll('#manageUsersTable tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = (!query || text.includes(query)) ? '' : 'none';
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
