<?php
/**
 * Admin - System Settings
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/dashboard_ui.php'; // Included to define sic_user_avatar() for navbar.php

checkRole(ROLE_ADMIN);

$catalog = sic_settings_catalog();
$definitions = sic_setting_definitions();

$success = isset($_GET['saved']) ? 'Settings updated successfully.' : '';
$error = '';
$fieldErrors = [];

// Effective values: stored setting, or catalog default.
$values = [];
foreach ($definitions as $key => $definition) {
    $values[$key] = getSetting($key);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $posted = $_POST['settings'] ?? [];
    if (!is_array($posted)) {
        $posted = [];
    }

    $clean = [];
    foreach ($definitions as $key => $definition) {
        $raw = $posted[$key] ?? '';
        $raw = is_scalar($raw) ? trim((string) $raw) : '';
        $values[$key] = $raw; // keep what the admin typed when re-rendering

        switch ($definition['type']) {
            case 'toggle':
                $clean[$key] = $raw === '1' ? '1' : '0';
                break;

            case 'number':
                $min = (int) $definition['min'];
                $max = (int) $definition['max'];
                if (!preg_match('/^\d+$/', $raw) || (int) $raw < $min || (int) $raw > $max) {
                    $fieldErrors[$key] = "Enter a whole number between {$min} and {$max}.";
                } else {
                    $clean[$key] = (string) (int) $raw;
                }
                break;

            case 'time':
                if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $raw)) {
                    $fieldErrors[$key] = 'Enter a valid time (HH:MM).';
                } else {
                    $clean[$key] = $raw;
                }
                break;

            default: // text
                $maxlength = (int) ($definition['maxlength'] ?? 255);
                if (!empty($definition['required']) && $raw === '') {
                    $fieldErrors[$key] = 'This field is required.';
                } elseif (mb_strlen($raw) > $maxlength) {
                    $fieldErrors[$key] = "Must be {$maxlength} characters or fewer.";
                } else {
                    $clean[$key] = $raw;
                }
        }
    }

    // Cross-field rule: the booking window must run forwards.
    if (!isset($fieldErrors['booking_start_time'], $fieldErrors['booking_end_time'])
        && $clean['booking_start_time'] >= $clean['booking_end_time']) {
        $fieldErrors['booking_end_time'] = 'The latest booking end must be after the earliest booking start.';
    }

    if ($fieldErrors !== []) {
        $error = 'Please correct the highlighted settings. Nothing was saved.';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'INSERT INTO system_settings (setting_key, setting_value, description)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                     setting_value = VALUES(setting_value),
                     description = VALUES(description)'
            );
            foreach ($clean as $key => $value) {
                $stmt->execute([$key, $value, $definitions[$key]['description']]);
            }
            $pdo->commit();

            logActivity($_SESSION['user_id'] ?? null, 'Update Settings', 'Updated system settings');

            header('Location: ' . app_url('admin/settings.php?saved=1'));
            exit;
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

$pageTitle = 'System Settings';
include __DIR__ . '/../includes/header.php';
?>

            <div class="page-toolbar">
                <div>
                    <h1>System Settings</h1>
                    <p>Configure how the system behaves for every role. Changes apply immediately.</p>
                </div>
            </div>

            <?php if ($success !== ''): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= app_url('admin/settings.php') ?>">
                <?= csrf_field() ?>

                <?php foreach ($catalog as $group): ?>
                    <div class="card mb-20px">
                        <div class="card-header">
                            <h5><?= htmlspecialchars($group['title']) ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <?php foreach ($group['fields'] as $key => $definition): ?>
                                    <?php
                                    $fieldId = 'setting-' . $key;
                                    $value = (string) ($values[$key] ?? $definition['default']);
                                    $invalid = isset($fieldErrors[$key]) ? ' is-invalid' : '';
                                    ?>
                                    <div class="col-md-6">
                                        <label class="form-label" for="<?= htmlspecialchars($fieldId) ?>">
                                            <strong><?= htmlspecialchars($definition['label']) ?></strong>
                                        </label>

                                        <?php if ($definition['type'] === 'toggle'): ?>
                                            <select id="<?= htmlspecialchars($fieldId) ?>" name="settings[<?= htmlspecialchars($key) ?>]" class="form-select<?= $invalid ?>">
                                                <option value="1" <?= $value === '1' ? 'selected' : '' ?>>Enabled</option>
                                                <option value="0" <?= $value !== '1' ? 'selected' : '' ?>>Disabled</option>
                                            </select>
                                        <?php elseif ($definition['type'] === 'number'): ?>
                                            <input
                                                id="<?= htmlspecialchars($fieldId) ?>"
                                                type="number"
                                                name="settings[<?= htmlspecialchars($key) ?>]"
                                                class="form-control<?= $invalid ?>"
                                                value="<?= htmlspecialchars($value) ?>"
                                                min="<?= (int) $definition['min'] ?>"
                                                max="<?= (int) $definition['max'] ?>"
                                                step="1"
                                                required
                                            >
                                        <?php elseif ($definition['type'] === 'time'): ?>
                                            <input
                                                id="<?= htmlspecialchars($fieldId) ?>"
                                                type="time"
                                                name="settings[<?= htmlspecialchars($key) ?>]"
                                                class="form-control<?= $invalid ?>"
                                                value="<?= htmlspecialchars($value) ?>"
                                                required
                                            >
                                        <?php else: ?>
                                            <input
                                                id="<?= htmlspecialchars($fieldId) ?>"
                                                type="text"
                                                name="settings[<?= htmlspecialchars($key) ?>]"
                                                class="form-control<?= $invalid ?>"
                                                value="<?= htmlspecialchars($value) ?>"
                                                maxlength="<?= (int) ($definition['maxlength'] ?? 255) ?>"
                                                <?= !empty($definition['required']) ? 'required' : '' ?>
                                            >
                                        <?php endif; ?>

                                        <?php if (isset($fieldErrors[$key])): ?>
                                            <div class="form-text text-danger"><?= htmlspecialchars($fieldErrors[$key]) ?></div>
                                        <?php endif; ?>
                                        <div class="form-text"><?= htmlspecialchars($definition['description']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-primary">
                    <span class="ui-dot" aria-hidden="true"></span>Save Settings
                </button>
            </form>

<?php include __DIR__ . '/../includes/footer.php'; ?>