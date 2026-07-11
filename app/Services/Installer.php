<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use PDOException;

class Installer
{
    public static function isInstalled(): bool
    {
        return ($_ENV['APP_INSTALLED'] ?? 'false') === 'true';
    }

    public static function testConnection(string $host, string $port, string $database, string $username, string $password): void
    {
        self::createDatabase($host, $port, $database, $username, $password);
        self::connectDatabase($host, $port, $database, $username, $password);
    }

    public static function createDatabase(string $host, string $port, string $database, string $username, string $password): void
    {
        $pdo = self::connectServer($host, $port, $username, $password);
        $safeName = str_replace('`', '``', $database);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    /** @param array<string, string> $config */
    public static function install(array $config): void
    {
        $host = $config['db_host'];
        $port = $config['db_port'];
        $database = $config['db_name'];
        $username = $config['db_username'];
        $password = $config['db_password'];

        self::createDatabase($host, $port, $database, $username, $password);

        $formsDb = trim($config['forms_db_name'] ?? '');
        if ($formsDb !== '') {
            $formsUser = trim($config['forms_db_username'] ?? $username);
            $formsPass = $config['forms_db_password'] ?? $password;
            self::createDatabase(
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
        $db = Database::connection();
        $schema = file_get_contents(dirname(__DIR__, 2) . '/database/schema.mysql.sql');
        if ($schema === false) {
            throw new \RuntimeException('MySQL schema file not found.');
        }
        foreach (self::splitSql($schema) as $statement) {
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
        $stmt = $db->prepare('UPDATE users SET email = ?, password = ? WHERE role = ? LIMIT 1');
        $stmt->execute([$adminEmail, $hash, 'admin']);
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
