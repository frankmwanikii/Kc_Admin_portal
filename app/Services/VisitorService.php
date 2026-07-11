<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class VisitorService
{
    private static bool $tableChecked = false;

    public static function ensureTable(): void
    {
        if (self::$tableChecked) {
            return;
        }
        if (($_ENV['APP_INSTALLED'] ?? 'false') !== 'true') {
            return;
        }

        $db = Database::connection();
        $driver = $_ENV['DB_CONNECTION'] ?? 'mysql';

        if ($driver === 'mysql') {
            $db->exec("
                CREATE TABLE IF NOT EXISTS visitor_feedback (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    first_name VARCHAR(100) NOT NULL,
                    last_name VARCHAR(100) NOT NULL,
                    spouse_name VARCHAR(150) NULL,
                    children_names TEXT NULL,
                    phone VARCHAR(30) NOT NULL,
                    email VARCHAR(150) NOT NULL,
                    review TEXT NULL,
                    how_heard_about_us VARCHAR(100) NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } else {
            $db->exec("
                CREATE TABLE IF NOT EXISTS visitor_feedback (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    first_name VARCHAR(100) NOT NULL,
                    last_name VARCHAR(100) NOT NULL,
                    spouse_name VARCHAR(150),
                    children_names TEXT,
                    phone VARCHAR(30) NOT NULL,
                    email VARCHAR(150) NOT NULL,
                    review TEXT,
                    how_heard_about_us VARCHAR(100),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
        }

        self::$tableChecked = true;
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        self::ensureTable();

        $children = $data['children'] ?? [];
        $childrenJson = json_encode(
            array_values(array_filter(array_map('trim', is_array($children) ? $children : []))),
            JSON_THROW_ON_ERROR
        );

        $stmt = Database::connection()->prepare('
            INSERT INTO visitor_feedback
                (first_name, last_name, spouse_name, children_names, phone, email, review, how_heard_about_us)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['spouse_name'] ?: null,
            $childrenJson !== '[]' ? $childrenJson : null,
            $data['phone'],
            $data['email'],
            $data['review'] ?: null,
            $data['how_heard_about_us'] ?: null,
        ]);

        return (int) Database::connection()->lastInsertId();
    }
}
