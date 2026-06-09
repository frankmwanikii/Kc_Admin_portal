<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class SettingsService
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
                CREATE TABLE IF NOT EXISTS settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(100) NOT NULL UNIQUE,
                    setting_value TEXT,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } else {
            $db->exec("
                CREATE TABLE IF NOT EXISTS settings (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    setting_key VARCHAR(100) NOT NULL UNIQUE,
                    setting_value TEXT,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
        }

        self::$tableChecked = true;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        self::ensureTable();
        $stmt = Database::connection()->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value !== false ? (string) $value : $default;
    }

    public static function set(string $key, ?string $value): void
    {
        self::ensureTable();
        $db = Database::connection();
        $driver = $_ENV['DB_CONNECTION'] ?? 'mysql';

        if ($driver === 'mysql') {
            $stmt = $db->prepare('
                INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ');
        } else {
            $stmt = $db->prepare('
                INSERT OR REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)
            ');
        }
        $stmt->execute([$key, $value]);
    }

    public static function churchName(): string
    {
        return self::get('church_name', $_ENV['CHURCH_NAME'] ?? 'Grace Community Church') ?? 'Grace Community Church';
    }

    public static function churchAddress(): string
    {
        return self::get('church_address', $_ENV['CHURCH_ADDRESS'] ?? '') ?? '';
    }

    public static function churchPhone(): string
    {
        return self::get('church_phone', $_ENV['CHURCH_PHONE'] ?? '') ?? '';
    }

  /** Public URL for <img src>, or null for default icon */
    public static function logoUrl(): ?string
    {
        $uploaded = self::get('church_logo_path');
        if ($uploaded && is_file(dirname(__DIR__, 2) . '/public/' . $uploaded)) {
            return rtrim($_ENV['APP_URL'] ?? '', '/') . '/' . ltrim($uploaded, '/');
        }

        $external = trim(self::get('church_logo_url', '') ?? '');
        if ($external !== '' && filter_var($external, FILTER_VALIDATE_URL)) {
            return $external;
        }

        return null;
    }

    public static function hasLogo(): bool
    {
        return self::logoUrl() !== null;
    }
}
