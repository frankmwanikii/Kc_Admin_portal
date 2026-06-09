<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function reset(): void
    {
        self::$instance = null;
    }

    public static function isMysql(): bool
    {
        return ($_ENV['DB_CONNECTION'] ?? 'mysql') === 'mysql';
    }

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            $driver = $_ENV['DB_CONNECTION'] ?? 'mysql';

            try {
                if ($driver === 'mysql') {
                    $dsn = sprintf(
                        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                        $_ENV['DB_HOST'] ?? '127.0.0.1',
                        $_ENV['DB_PORT'] ?? '3306',
                        $_ENV['DB_DATABASE_NAME'] ?? 'church_mis'
                    );
                    self::$instance = new PDO($dsn, $_ENV['DB_USERNAME'] ?? 'root', $_ENV['DB_PASSWORD'] ?? '', [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);
                } else {
                    $path = dirname(__DIR__, 2) . '/' . ($_ENV['DB_DATABASE'] ?? 'database/church.sqlite');
                    $dir = dirname($path);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    self::$instance = new PDO('sqlite:' . $path, null, null, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);
                }
            } catch (PDOException $e) {
                throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }
}
