<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use PDOException;
use App\Models\User;

class Installer
{
    public static function isInstalled(): bool
    {
        return ($_ENV['APP_INSTALLED'] ?? 'false') === 'true';
    }

    public static function testConnection(string $host, string $port, string $database, string $username, string $password): void
    {
        self::ensureDatabase($host, $port, $database, $username, $password);
    }

    public static function createDatabase(string $host, string $port, string $database, string $username, string $password): void
    {
        self::ensureDatabase($host, $port, $database, $username, $password);
    }

    /**
     * Use the existing database (cPanel) when CREATE DATABASE is not allowed.
     */
    private static function ensureDatabase(string $host, string $port, string $database, string $username, string $password): void
    {
        try {
            $pdo = self::connectServer($host, $port, $username, $password);
            $safeName = str_replace('`', '``', $database);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (\Throwable $e) {
            // cPanel MySQL users usually cannot CREATE DATABASE. Connecting is enough.
        }

        self::connectDatabase($host, $port, $database, $username, $password);
    }

    /** @param array<string, string> $config */
    public static function install(array $config): void
    {
        $host = $config['db_host'];
        $port = $config['db_port'];
        $database = $config['db_name'];
        $username = $config['db_username'];
        $password = $config['db_password'];

        self::ensureDatabase($host, $port, $database, $username, $password);

        $formsDb = trim($config['forms_db_name'] ?? '');
        if ($formsDb !== '') {
            $formsUser = trim($config['forms_db_username'] ?? $username);
            $formsPass = $config['forms_db_password'] ?? $password;
            self::ensureDatabase(
                trim($config['forms_db_host'] ?? $host),
                trim($config['forms_db_port'] ?? $port),
                $formsDb,
                $formsUser,
                $formsPass
            );
        }

        $appUrl = rtrim($config['app_url'], '/');

        $env = new EnvService();
        $envValues = [
            'APP_NAME' => $config['church_name'] . ' MIS',
            'APP_URL' => $appUrl,
            'APP_INSTALLED' => 'true',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $host,
            'DB_PORT' => $port,
            'DB_USERNAME' => $username,
            'DB_PASSWORD' => $password,
            'DB_DATABASE_NAME' => $database,
            'CHURCH_NAME' => $config['church_name'],
            'MAIL_FROM_NAME' => $config['church_name'],
        ];

        $formsDb = trim($config['forms_db_name'] ?? '');
        if ($formsDb !== '') {
            $envValues['FORMS_DB_HOST'] = trim($config['forms_db_host'] ?? $host);
            $envValues['FORMS_DB_PORT'] = trim($config['forms_db_port'] ?? $port);
            $envValues['FORMS_DB_DATABASE_NAME'] = $formsDb;
            $envValues['FORMS_DB_USERNAME'] = trim($config['forms_db_username'] ?? $username);
            $envValues['FORMS_DB_PASSWORD'] = $config['forms_db_password'] ?? $password;
        }

        $env->setMany($envValues);

        Database::reset();
        self::migrate();
        User::ensureProfileColumns();

        if ($formsDb !== '') {
            $formsSql = file_get_contents(dirname(__DIR__, 2) . '/database/shared-form-submissions.sql');
            if ($formsSql !== false) {
                Database::formsConnection()->exec($formsSql);
            }
        }

        self::seed($config['admin_email'], $config['admin_password']);
    }

    private static function migrate(): void
    {
        $files = [
            'schema.mysql.sql',
            'shared-form-submissions.sql',
            'finance-reconciliation.sql',
            'finance-budget.sql',
            'finance-expense-catalog.sql',
        ];

        foreach ($files as $file) {
            self::runSqlFile($file);
        }
    }

    private static function runSqlFile(string $file): void
    {
        $path = dirname(__DIR__, 2) . '/database/' . $file;
        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new \RuntimeException('SQL file not found: ' . $file);
        }

        $db = Database::connection();
        foreach (self::splitSql($sql) as $statement) {
            $db->exec($statement);
        }
    }

    private static function seed(string $adminEmail, string $adminPassword): void
    {
        $db = Database::connection();
        $seed = file_get_contents(dirname(__DIR__, 2) . '/database/seed.mysql.sql');
        if ($seed === false) {
            throw new \RuntimeException('MySQL seed file not found.');
        }
        foreach (self::splitSql($seed) as $statement) {
            $db->exec($statement);
        }

        $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE users SET email = ?, password = ?, username = COALESCE(NULLIF(username, \'\'), ?) WHERE role = ? LIMIT 1');
        $stmt->execute([$adminEmail, $hash, 'Admin', 'admin']);
    }

    /** @return string[] */
    private static function splitSql(string $sql): array
    {
        $statements = [];
        $buffer = '';
        foreach (explode("\n", $sql) as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                continue;
            }
            $buffer .= $line . "\n";
            if (str_ends_with(rtrim($line), ';')) {
                $statements[] = trim($buffer);
                $buffer = '';
            }
        }
        return array_filter($statements);
    }

    private static function connectServer(string $host, string $port, string $username, string $password): PDO
    {
        try {
            return new PDO(
                sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port),
                $username,
                $password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            throw new \RuntimeException('Could not connect to MySQL server: ' . $e->getMessage());
        }
    }

    private static function connectDatabase(string $host, string $port, string $database, string $username, string $password): PDO
    {
        try {
            return new PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database),
                $username,
                $password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            throw new \RuntimeException('Could not connect to database "' . $database . '": ' . $e->getMessage());
        }
    }
}
