<?php
/**
 * Admin - System Settings
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/dashboard_ui.php'; // Included to define sic_user_avatar() for navbar.php

checkRole(ROLE_ADMIN);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedSettings = $_POST['settings'] ?? [];

    if (!is_array($postedSettings) || $postedSettings === []) {
        $error = 'No settings were submitted.';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'UPDATE system_settings SET setting_value = ? WHERE setting_key = ?'
            );

            foreach ($postedSettings as $key => $value) {
                if (!is_string($key)) {
                    continue;
                }

                $cleanKey = trim($key);
                $cleanValue = is_scalar($value) ? trim((string) $value) : '';

                if ($cleanKey === '') {
                    continue;
                }

                $stmt->execute([$cleanValue, $cleanKey]);
            }

            $pdo->commit();

            if (function_exists('logActivity')) {
                logActivity(
                    $_SESSION['user_id'] ?? null,
                    'Update Settings',
                    'Updated system settings'
                );
            }

            $success = 'Settings updated successfully.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = APP_DEBUG
                ? 'Unable to update settings: ' . $e->getMessage()
                : 'Unable to update settings.';
        }
    }
}

try {
    $settings = $pdo->query(
        'SELECT setting_key, setting_value, description FROM system_settings ORDER BY setting_key'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $settings = [];
    $error = APP_DEBUG
        ? 'Unable to load settings: ' . $e->getMessage()
        : 'Unable to load settings.';
}

$pageTitle = 'System Settings';
include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1>System Settings</h1>
                    <p>Update general configuration values used by the system.</p>
                </div>
            </div>

            <?php if ($success !== ''): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5>Configuration</h5>
                </div>
                <div class="card-body">
                    <?php if ($settings === []): ?>
                        <div class="alert alert-info mb-0">No system settings were found.</div>
                    <?php else: ?>
                        <form method="POST" action="<?= app_url('admin/settings.php') ?>">
                            <div class="row g-4">
                                <?php foreach ($settings as $setting): ?>
                                    <div class="col-md-6">
                                        <label class="form-label" for="setting-<?= htmlspecialchars($setting['setting_key']) ?>">
                                            <strong><?= htmlspecialchars(ucwords(str_replace('_', ' ', $setting['setting_key']))) ?></strong>
                                        </label>
                                        <input
                                            id="setting-<?= htmlspecialchars($setting['setting_key']) ?>"
                                            type="text"
                                            name="settings[<?= htmlspecialchars($setting['setting_key']) ?>]"
                                            class="form-control"
                                            value="<?= htmlspecialchars((string) $setting['setting_value']) ?>"
                                        >
                                        <div class="form-text"><?= htmlspecialchars((string) ($setting['description'] ?? '')) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <button type="submit" class="btn btn-primary mt-4">
                                <span class="ui-dot" aria-hidden="true"></span>Save Settings
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>