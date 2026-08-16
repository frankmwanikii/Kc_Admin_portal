<?php

declare(strict_types=1);

namespace App\Core;

class PublicFile
{
    /** @var array<string, string> */
    private const MIMES = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'css' => 'text/css',
        'js' => 'text/javascript',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ico' => 'image/x-icon',
    ];

    public static function tryServe(string $uri): bool
    {
        $relative = ltrim($uri, '/');
        if ($relative === '' || str_contains($relative, '..') || !str_contains($relative, '.')) {
            return false;
        }

        $ext = strtolower((string) pathinfo($relative, PATHINFO_EXTENSION));
        if (!isset(self::MIMES[$ext])) {
            return false;
        }

        $publicRoot = dirname(__DIR__, 2) . '/public';
        $full = $publicRoot . '/' . $relative;
        $realPublic = realpath($publicRoot);
        $realFile = realpath($full);
        if ($realPublic === false || $realFile === false || !is_file($realFile)) {
            return false;
        }
        if (!str_starts_with($realFile, $realPublic . DIRECTORY_SEPARATOR)) {
            return false;
        }

        $mime = self::MIMES[$ext];
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($realFile));
        header('Cache-Control: public, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
            readfile($realFile);
        }
        return true;
    }
}
