<?php

$projectRoot = __DIR__;
$configDir = $projectRoot . DIRECTORY_SEPARATOR . 'config';
$schemaFile = $projectRoot . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'smart_instructor_system.sql';
$lockFile = $projectRoot . DIRECTORY_SEPARATOR . 'install.lock';

$defaults = [
    'db_host' => 'localhost',
    'db_name' => 'smart_instructor_system',
    'db_user' => 'root',
    'db_password' => '',
    'site_url' => 'http://localhost/' . basename(__DIR__),
];

$messages = [];
$errors = [];
$installed = file_exists($lockFile);

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function normalizeSiteUrl(string $value): string
{
    $value = trim($value);

    if ($value === '') {
        return 'http://localhost/' . basename(__DIR__);
    }

    if (!preg_match('#^https?://#i', $value)) {
        $value = 'http://' . $value;
    }

    return rtrim($value, '/');
}

function escapePhpSingleQuotedString(string $value): string
{
    return str_replace(["\\", "'"], ["\\\\", "\\'"], $value);
}

function buildDbConfig(array $data): string
{
    $host = escapePhpSingleQuotedString($data['db_host']);
    $dbname = escapePhpSingleQuotedString($data['db_name']);
    $username = escapePhpSingleQuotedString($data['db_user']);
    $password = escapePhpSingleQuotedString($data['db_password']);

    return <<<PHP
<?php
/**
 * Database Configuration
 * Smart Instructor Coordination and Workload Management System
 */








\$host = '{$host}';
\$dbname = '{$dbname}';
\$username = '{$username}';

// Default XAMPP MySQL setup uses an empty password for root.
\$password = '{$password}';

try {
    \$pdo = new PDO(
        "mysql:host=\$host;dbname=\$dbname;charset=utf8mb4",
        \$username,
        \$password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException \$e) {
    die("Database connection failed: " . \$e->getMessage());
}
?>
PHP;
}

function buildAppConfig(array $data): string
{
    $siteUrl = escapePhpSingleQuotedString($data['site_url']);

    return <<<PHP
<?php
/**
 * Application Configuration
 * Smart Instructor Coordination and Workload Management System
 */

define('SITE_NAME', 'Smart Instructor System - UCSC');
define('SITE_URL', '{$siteUrl}');
define('VERSION', '1.0.0');

// Default pagination
define('RECORDS_PER_PAGE', 10);

// Workload settings
define('DEFAULT_MAX_WEEKLY_HOURS', 40);

// Role ID constants
define('ROLE_ADMIN', 1);
define('ROLE_INSTRUCTOR', 2);
define('ROLE_COORDINATOR', 3);
define('ROLE_CHIEF_COORDINATOR', 4);
define('ROLE_NON_ACADEMIC', 5);
define('ROLE_PROJECT_COORDINATOR', 6);
define('ROLE_DIRECTOR', 7);

// Status constants
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');
define('STATUS_PENDING', 'Pending');
define('STATUS_APPROVED', 'Approved');
define('STATUS_REJECTED', 'Rejected');
define('STATUS_ASSIGNED', 'Assigned');
define('STATUS_COMPLETED', 'Completed');

// Date format
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
?>
PHP;
}

function createPdo(array $data, bool $withDatabase = true): PDO
{
    $dsn = 'mysql:host=' . $data['db_host'] . ';charset=utf8mb4';

    if ($withDatabase) {
        $dsn .= ';dbname=' . $data['db_name'];
    }

    return new PDO(
        $dsn,
        $data['db_user'],
        $data['db_password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

function stripSqlComments(string $sql): string
{
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    $lines = preg_split('/\r\n|\r|\n/', $sql);
    $cleanLines = [];

    foreach ($lines as $line) {
        $trimmed = ltrim($line);

        if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
            continue;
        }

        $cleanLines[] = $line;
    }

    return implode("\n", $cleanLines);
}

function splitSqlStatements(string $sql): array
{
    $statements = [];
    $statement = '';
    $length = strlen($sql);
    $inSingleQuote = false;
    $inDoubleQuote = false;

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($char === "'" && !$inDoubleQuote) {
            if ($inSingleQuote && $next === "'") {
                $statement .= $char . $next;
                $i++;
                continue;
            }

            $inSingleQuote = !$inSingleQuote;
            $statement .= $char;
            continue;
        }

        if ($char === '"' && !$inSingleQuote) {
            if ($inDoubleQuote && $next === '"') {
                $statement .= $char . $next;
                $i++;
                continue;
            }

            $inDoubleQuote = !$inDoubleQuote;
            $statement .= $char;
            continue;
        }

        if ($char === ';' && !$inSingleQuote && !$inDoubleQuote) {
            $trimmedStatement = trim($statement);
            if ($trimmedStatement !== '') {
                $statements[] = $trimmedStatement;
            }
            $statement = '';
            continue;
        }

        $statement .= $char;
    }

    $trimmedStatement = trim($statement);
    if ($trimmedStatement !== '') {
        $statements[] = $trimmedStatement;
    }

    return $statements;
}

function importSqlFile(PDO $pdo, string $schemaFile): void
{
    if (!file_exists($schemaFile)) {
        throw new RuntimeException('Database schema file not found.');
    }

    $sql = file_get_contents($schemaFile);
    if ($sql === false) {
        throw new RuntimeException('Unable to read the schema file.');
    }

    $sql = stripSqlComments($sql);
    $statements = splitSqlStatements($sql);

    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    $installData = [
        'db_host' => trim($_POST['db_host'] ?? $defaults['db_host']),
        'db_name' => trim($_POST['db_name'] ?? $defaults['db_name']),
        'db_user' => trim($_POST['db_user'] ?? $defaults['db_user']),
        'db_password' => (string)($_POST['db_password'] ?? $defaults['db_password']),
        'site_url' => normalizeSiteUrl((string)($_POST['site_url'] ?? $defaults['site_url'])),
    ];

    if ($installData['db_host'] === '' || $installData['db_name'] === '' || $installData['db_user'] === '') {
        $errors[] = 'Database host, name, and user are required.';
    } else {
        try {
            $serverPdo = createPdo($installData, false);
            $serverPdo->exec(
                sprintf(
                    "CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
                    str_replace('`', '``', $installData['db_name'])
                )
            );

            $pdo = createPdo($installData, true);
            importSqlFile($pdo, $schemaFile);

            if (!is_dir($configDir)) {
                throw new RuntimeException('The config directory is missing.');
            }

            if (file_put_contents($configDir . DIRECTORY_SEPARATOR . 'db.php', buildDbConfig($installData)) === false) {
                throw new RuntimeException('Failed to write config/db.php. Check file permissions.');
            }

            if (file_put_contents($configDir . DIRECTORY_SEPARATOR . 'config.php', buildAppConfig($installData)) === false) {
                throw new RuntimeException('Failed to write config/config.php. Check file permissions.');
            }

            $lockData = [
                'installed_at' => date(DATE_ATOM),
                'database' => $installData['db_name'],
                'site_url' => $installData['site_url'],
            ];

            if (file_put_contents($lockFile, json_encode($lockData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
                throw new RuntimeException('Failed to create install.lock.');
            }

            $installed = true;
            $messages[] = 'Installation completed successfully.';
            $messages[] = 'Admin login seeded from the SQL file: admin@example.com / password123';
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$appUrl = $installed ? 'Admin/' : '';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Setup</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f4f7fb;
            --card: #ffffff;
            --text: #172033;
            --muted: #667085;
            --border: #d9e2f2;
            --accent: #1f5eff;
            --accent-2: #0f8b8d;
            --danger: #b42318;
            --success: #067647;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(31, 94, 255, 0.14), transparent 32%),
                radial-gradient(circle at top right, rgba(15, 139, 141, 0.12), transparent 28%),
                var(--bg);
            color: var(--text);
        }

        .shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 32px 16px;
        }

        .card {
            width: min(920px, 100%);
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.12);
            overflow: hidden;
        }

        .hero {
            padding: 32px;
            background: linear-gradient(135deg, #0b1f44, #153f88 58%, #0f8b8d);
            color: #fff;
        }

        .hero h1 {
            margin: 0 0 10px;
            font-size: clamp(28px, 4vw, 44px);
        }

        .hero p {
            margin: 0;
            max-width: 62ch;
            color: rgba(255, 255, 255, 0.88);
        }

        .content {
            padding: 32px;
            display: grid;
            gap: 20px;
        }

        .grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .field {
            display: grid;
            gap: 8px;
        }

        label {
            font-size: 14px;
            font-weight: 700;
        }

        input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 14px;
            font-size: 15px;
            color: var(--text);
            background: #fff;
        }

        input:focus {
            outline: 3px solid rgba(31, 94, 255, 0.14);
            border-color: var(--accent);
        }

        .help {
            font-size: 13px;
            color: var(--muted);
        }

        .actions {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .button {
            border: 0;
            border-radius: 999px;
            padding: 14px 22px;
            background: linear-gradient(135deg, var(--accent), #1247cc);
            color: #fff;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
        }

        .notice {
            border-radius: 18px;
            padding: 16px 18px;
            border: 1px solid var(--border);
            background: #f8fbff;
        }

        .notice.error {
            border-color: rgba(180, 35, 24, 0.3);
            background: #fff5f5;
            color: var(--danger);
        }

        .notice.success {
            border-color: rgba(6, 118, 71, 0.3);
            background: #effcf5;
            color: var(--success);
        }

        .footer {
            padding: 0 32px 32px;
            color: var(--muted);
            font-size: 14px;
        }

        .mono {
            font-family: Consolas, Monaco, monospace;
        }

        @media (max-width: 720px) {
            .hero, .content, .footer {
                padding-left: 20px;
                padding-right: 20px;
            }

            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="shell">
    <div class="card">
        <div class="hero">
            <h1>System Setup</h1>
            <p>Run the one-click installer to create the database, import the schema, and write the local XAMPP configuration files.</p>
        </div>

        <div class="content">
            <?php if (!empty($errors)): ?>
                <div class="notice error">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo h($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($messages)): ?>
                <div class="notice success">
                    <?php foreach ($messages as $message): ?>
                        <div><?php echo h($message); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($installed && empty($errors) && !empty($messages)): ?>
                <div class="notice">
                    Installation lock created at <span class="mono"><?php echo h(basename($lockFile)); ?></span>.
                    Remove <span class="mono">setup.php</span> after installation for safety.
                </div>
            <?php endif; ?>

            <?php if (!$installed || !empty($errors)): ?>
                <form method="post">
                    <div class="grid">
                        <div class="field">
                            <label for="db_host">Database Host</label>
                            <input id="db_host" name="db_host" value="<?php echo h($_POST['db_host'] ?? $defaults['db_host']); ?>" required>
                        </div>
                        <div class="field">
                            <label for="db_name">Database Name</label>
                            <input id="db_name" name="db_name" value="<?php echo h($_POST['db_name'] ?? $defaults['db_name']); ?>" required>
                        </div>
                        <div class="field">
                            <label for="db_user">Database User</label>
                            <input id="db_user" name="db_user" value="<?php echo h($_POST['db_user'] ?? $defaults['db_user']); ?>" required>
                        </div>
                        <div class="field">
                            <label for="db_password">Database Password</label>
                            <input id="db_password" name="db_password" type="password" value="<?php echo h($_POST['db_password'] ?? $defaults['db_password']); ?>">
                        </div>
                        <div class="field" style="grid-column: 1 / -1;">
                            <label for="site_url">Site URL</label>
                            <input id="site_url" name="site_url" value="<?php echo h($_POST['site_url'] ?? $defaults['site_url']); ?>" required>
                            <div class="help">Use your local XAMPP URL, for example <span class="mono">http://localhost/smart_instructor_system</span>.</div>
                        </div>
                    </div>

                    <div class="actions" style="margin-top: 20px;">
                        <button class="button" type="submit" name="install" value="1">Install System</button>
                        <div class="help">The installer will import <span class="mono">database/smart_instructor_system.sql</span> and seed the sample admin account.</div>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <div class="footer">
            <?php if ($installed && empty($errors)): ?>
                Installation is complete.
                <a href="<?php echo h($appUrl); ?>">Open the application</a>.
                Delete or rename <span class="mono">setup.php</span> after confirming the system works.
            <?php else: ?>
                Make sure Apache and MySQL are running in XAMPP before starting the installation.
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>