<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Models\User;

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            View::redirect(Auth::isAdmin() ? '/admin' : '/portal');
        }
        View::render('auth/login', $this->loginViewData(), 'layouts/guest');
    }

    public function login(): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = User::findByUsername($username);
        if (!$user || !password_verify($password, $user->password)) {
            View::render('auth/login', $this->loginViewData([
                'error' => 'Invalid username or password.',
                'username' => $username,
            ]), 'layouts/guest');
            return;
        }

        Database::connection()->prepare('UPDATE users SET last_login_at = ? WHERE id = ?')->execute([date('Y-m-d H:i:s'), $user->id]);
        Auth::login($user);

        $redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? null;
        if ($redirect && str_starts_with($redirect, '/') && !str_starts_with($redirect, '//')) {
            if (str_starts_with($redirect, '/admin') && !Auth::isAdmin()) {
                View::redirect('/portal');
            }
            View::redirect($redirect);
        }
        View::redirect(Auth::isAdmin() ? '/admin' : '/portal');
    }

    public function magicAccess(string $token): void
    {
        $user = User::findByMagicToken($token);
        if (!$user) {
            View::render('auth/login', $this->loginViewData([
                'error' => 'This link has expired or is invalid. Please sign in with your credentials.',
            ]), 'layouts/guest');
            return;
        }

        $now = date('Y-m-d H:i:s');
        Database::connection()->prepare('UPDATE users SET magic_link_token = NULL, magic_link_expires = NULL, email_verified_at = COALESCE(email_verified_at, ?), last_login_at = ? WHERE id = ?')->execute([$now, $now, $user->id]);
        Auth::login($user);
        View::redirect('/portal');
    }

    public function logout(): void
    {
        Auth::logout();
        View::redirect('/login');
    }

    /** @param array<string, mixed> $extra */
    private function loginViewData(array $extra = []): array
    {
        return array_merge([
            'title' => 'Sign In',
            'pageStyles' => ['/css/auth-login.css'],
        ], $extra);
    }
}
