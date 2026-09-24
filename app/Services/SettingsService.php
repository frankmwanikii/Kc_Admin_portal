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
        if ($uploaded) {
            $relative = '/' . ltrim($uploaded, '/');
            $full = dirname(__DIR__, 2) . '/public/' . ltrim($uploaded, '/');
            if (is_file($full)) {
                // Relative path works on any host; mtime busts browser cache after re-upload.
                return $relative . '?v=' . filemtime($full);
            }
        }

        $external = trim(self::get('church_logo_url', '') ?? '');
        if ($external !== '' && filter_var($external, FILTER_VALIDATE_URL)) {
            return $external;
        }

        return null;
    }

    /**
     * White monochrome logo for dark UI surfaces (sidebar dark mode, login brand pane).
     * Prefers uploads/branding/logo-white.png, then regenerates from the color logo,
     * then falls back to /images/kc-logo-white.png.
     */
    public static function logoUrlWhite(): ?string
    {
        $publicRoot = dirname(__DIR__, 2) . '/public';

        $uploadedWhite = $publicRoot . '/uploads/branding/logo-white.png';
        if (is_file($uploadedWhite)) {
            return '/uploads/branding/logo-white.png?v=' . filemtime($uploadedWhite);
        }

        $colorPath = self::get('church_logo_path');
        if ($colorPath) {
            $fullColor = $publicRoot . '/' . ltrim((string) $colorPath, '/');
            if (is_file($fullColor) && self::writeWhiteLogoVariant($fullColor, $uploadedWhite)) {
                return '/uploads/branding/logo-white.png?v=' . filemtime($uploadedWhite);
            }
        }

        $fallback = $publicRoot . '/images/kc-logo-white.png';
        if (is_file($fallback)) {
            return '/images/kc-logo-white.png?v=' . filemtime($fallback);
        }

        // Last resort: CSS-inverted color logo still works if nothing else exists.
        return self::logoUrl();
    }

    public static function hasLogo(): bool
    {
        return self::logoUrl() !== null;
    }

    /** Create a white-on-transparent PNG from a color logo file. */
    public static function writeWhiteLogoVariant(string $sourcePath, string $destPath): bool
    {
        if (!is_file($sourcePath) || !function_exists('imagecreatefromstring')) {
            return false;
        }

        $raw = @file_get_contents($sourcePath);
        if ($raw === false || $raw === '') {
            return false;
        }

        $src = @imagecreatefromstring($raw);
        if ($src === false) {
            return false;
        }

        imagesavealpha($src, true);
        $w = imagesx($src);
        $h = imagesy($src);
        $dst = imagecreatetruecolor($w, $h);
        if ($dst === false) {
            imagedestroy($src);

            return false;
        }

        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $w, $h, $transparent);

        // Detect solid backdrop from corners (black/white plate behind the mark).
        $corners = [
            imagecolorat($src, 0, 0),
            imagecolorat($src, max(0, $w - 1), 0),
            imagecolorat($src, 0, max(0, $h - 1)),
            imagecolorat($src, max(0, $w - 1), max(0, $h - 1)),
        ];
        $bgSamples = [];
        foreach ($corners as $rgba) {
            $a = ($rgba & 0x7F000000) >> 24;
            if ($a >= 120) {
                continue;
            }
            $bgSamples[] = [($rgba >> 16) & 0xFF, ($rgba >> 8) & 0xFF, $rgba & 0xFF];
        }

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgba = imagecolorat($src, $x, $y);
                $a = ($rgba & 0x7F000000) >> 24;
                if ($a >= 127) {
                    continue;
                }
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;

                $isBackdrop = false;
                foreach ($bgSamples as [$br, $bg, $bb]) {
                    if (abs($r - $br) <= 28 && abs($g - $bg) <= 28 && abs($b - $bb) <= 28) {
                        $isBackdrop = true;
                        break;
                    }
                }
                $luma = (0.2126 * $r) + (0.7152 * $g) + (0.0722 * $b);
                $chroma = max($r, $g, $b) - min($r, $g, $b);
                if ($chroma < 18 && ($luma <= 28 || $luma >= 235)) {
                    $isBackdrop = true;
                }
                if ($isBackdrop) {
                    continue;
                }

                $color = imagecolorallocatealpha($dst, 255, 255, 255, $a);
                imagesetpixel($dst, $x, $y, $color);
            }
        }

        $dir = dirname($destPath);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            imagedestroy($src);
            imagedestroy($dst);

            return false;
        }

        $ok = @imagepng($dst, $destPath, 6);
        imagedestroy($src);
        imagedestroy($dst);
        if ($ok) {
            @chmod($destPath, 0644);
        }

        return $ok;
    }
}
