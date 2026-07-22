<?php

declare(strict_types=1);

/**
 * Temporary cPanel diagnostic page — delete after fixing deployment.
 */
header('Content-Type: text/html; charset=utf-8');

$root = dirname(__DIR__);
$checks = [];

function check(string $label, bool $ok, string $detail = ''): void
{
    global $checks;
    $checks[] = ['label' => $label, 'ok' => $ok, 'detail' => $detail];
}

check(
    'PHP version >= 8.1',
    PHP_VERSION_ID >= 80100,
    'Running PHP ' . PHP_VERSION
);

check(
    'vendor/autoload.php exists',
    is_file($root . '/vendor/autoload.php'),
    $root . '/vendor/autoload.php'
);

check(
    '.env file exists',
    is_file($root . '/.env'),
    $root . '/.env'
);

$envLoaded = false;
if (is_file($root . '/vendor/autoload.php')) {
    require_once $root . '/vendor/autoload.php';
    if (is_file($root . '/.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable($root);
        $dotenv->safeLoad();
        $envLoaded = true;
    }
}

check('Environment loaded', $envLoaded);

$sessionDir = $root . '/storage/sessions';
if (!is_dir($sessionDir)) {
    @mkdir($sessionDir, 0755, true);
}
check(
    'Session directory writable',
    is_dir($sessionDir) && is_writable($sessionDir),
    $sessionDir
);

if ($envLoaded) {
    check(
        'APP_INSTALLED',
        ($_ENV['APP_INSTALLED'] ?? 'false') === 'true',
        'Value: ' . ($_ENV['APP_INSTALLED'] ?? '(not set)')
    );

    try {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST'] ?? 'localhost',
            $_ENV['DB_PORT'] ?? '3306',
            $_ENV['DB_DATABASE_NAME'] ?? ''
        );
        $pdo = new PDO(
            $dsn,
            $_ENV['DB_USERNAME'] ?? '',
            $_ENV['DB_PASSWORD'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        check('Main database connection', true, $_ENV['DB_DATABASE_NAME'] ?? '');

        $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        check('users table readable', true, $userCount . ' user(s) found');

        $admin = $pdo->query("SELECT email, role FROM users WHERE role = 'admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        check(
            'Admin user exists',
            is_array($admin),
            is_array($admin) ? ($admin['email'] ?? 'unknown') : 'No admin row'
        );
    } catch (Throwable $e) {
        check('Main database connection', false, $e->getMessage());
    }

    $formsDb = trim($_ENV['FORMS_DB_DATABASE_NAME'] ?? '');
    if ($formsDb !== '') {
        try {
            $formsDsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $_ENV['FORMS_DB_HOST'] ?? $_ENV['DB_HOST'] ?? 'localhost',
                $_ENV['FORMS_DB_PORT'] ?? $_ENV['DB_PORT'] ?? '3306',
                $formsDb
            );
            new PDO(
                $formsDsn,
                $_ENV['FORMS_DB_USERNAME'] ?? $_ENV['DB_USERNAME'] ?? '',
                $_ENV['FORMS_DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            check('Forms database connection', true, $formsDb);
        } catch (Throwable $e) {
            check('Forms database connection', false, $e->getMessage());
        }
    }

    try {
        require_once $root . '/app/Core/Database.php';
        App\Core\Database::connection();
        App\Services\SettingsService::churchName();
        check('SettingsService (login page dependency)', true);
    } catch (Throwable $e) {
        check('SettingsService (login page dependency)', false, $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Portal Diagnostics</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 760px; margin: 2rem auto; padding: 0 1rem; }
        h1 { font-size: 1.35rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { text-align: left; padding: 0.65rem 0.75rem; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .ok { color: #047857; font-weight: 600; }
        .fail { color: #b91c1c; font-weight: 600; }
        .note { margin-top: 1.25rem; padding: 1rem; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; }
        code { background: #f3f4f6; padding: 0.1rem 0.35rem; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Kingdomcity Portal — Diagnostics</h1>
    <p>Delete <code>public/diagnose.php</code> after fixing any failed checks.</p>
    <table>
        <thead>
            <tr><th>Check</th><th>Status</th><th>Details</th></tr>
        </thead>
        <tbody>
            <?php foreach ($checks as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['label']) ?></td>
                <td class="<?= $row['ok'] ? 'ok' : 'fail' ?>"><?= $row['ok'] ? 'PASS' : 'FAIL' ?></td>
                <td><?= htmlspecialchars($row['detail']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="note">
        <strong>If database connection fails:</strong> quote passwords in <code>.env</code>, e.g.
        <code>DB_PASSWORD="Vicfirth2026!!"</code><br><br>
        <strong>Login after import:</strong><br>
        Email: <code>admin@kingdomcitychurchnanyuki.org</code><br>
        Password: <code>password123</code>
    </div>
</body>
</html>
