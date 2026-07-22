<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $sessionDir = dirname(__DIR__, 2) . '/storage/sessions';
            if (!is_dir($sessionDir)) {
                mkdir($sessionDir, 0755, true);
            }
            if (is_writable($sessionDir)) {
                session_save_path($sessionDir);
            }

            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function login(User $user): void
    {
        self::start();
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_role'] = $user->role;
        $_SESSION['member_id'] = $user->member_id;
    }

    public static function logout(): void
    {
        self::start();
        session_destroy();
    }

    public static function user(): ?User
    {
        self::start();
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        return User::find((int) $_SESSION['user_id']);
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }
        return in_array($user->role, ['admin', 'pastor', 'staff'], true);
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            $return = $_SERVER['REQUEST_URI'] ?? '/';
            $login = '/login';
            if ($return !== '/' && $return !== '/login') {
                $login .= '?redirect=' . urlencode($return);
            }
            View::redirect($login);
        }
    }

    public static function requireAdmin(): void
    {
        self::requireAuth();
        if (!self::isAdmin()) {
            View::redirect('/portal');
        }
    }
}
