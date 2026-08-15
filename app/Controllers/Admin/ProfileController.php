<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\View;
use App\Models\User;

class ProfileController
{
    public function index(): void
    {
        Auth::requireAdmin();
        User::ensureProfileColumns();
        $user = Auth::user();
        if (!$user) {
            View::redirect('/login');
        }

        View::render('admin/profile/index', [
            'title' => 'My profile',
            'user' => $user,
            'success' => isset($_GET['saved']) ? 'Profile updated.' : null,
            'error' => null,
        ], 'layouts/admin');
    }

    public function update(): void
    {
        Auth::requireAdmin();
        User::ensureProfileColumns();
        $user = Auth::user();
        if (!$user) {
            View::redirect('/login');
        }

        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = trim((string) ($_POST['new_password'] ?? ''));
        $confirmPassword = trim((string) ($_POST['confirm_password'] ?? ''));

        $payload = [
            'username' => trim((string) ($_POST['username'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'display_name' => trim((string) ($_POST['display_name'] ?? '')),
            'avatar_path' => $user->avatar_path,
        ];

        $needsPassword = $newPassword !== ''
            || strcasecmp($payload['email'], $user->email) !== 0
            || strcasecmp($payload['username'], (string) ($user->username ?? '')) !== 0;

        if ($needsPassword) {
            if ($currentPassword === '' || !password_verify($currentPassword, $user->password)) {
                View::render('admin/profile/index', [
                    'title' => 'My profile',
                    'user' => $this->previewUser($user, $payload),
                    'success' => null,
                    'error' => 'Enter your current password to change username, email, or password.',
                ], 'layouts/admin');
                return;
            }
        }

        if ($newPassword !== '') {
            if ($newPassword !== $confirmPassword) {
                View::render('admin/profile/index', [
                    'title' => 'My profile',
                    'user' => $this->previewUser($user, $payload),
                    'success' => null,
                    'error' => 'New password and confirmation do not match.',
                ], 'layouts/admin');
                return;
            }
            $payload['password'] = $newPassword;
        }

        if (!empty($_POST['remove_avatar'])) {
            $this->removeAvatarFile($user->avatar_path);
            $payload['avatar_path'] = null;
        }

        $uploadError = $this->avatarUploadError($_FILES['avatar'] ?? null);
        if ($uploadError !== null) {
            View::render('admin/profile/index', [
                'title' => 'My profile',
                'user' => $this->previewUser($user, $payload),
                'success' => null,
                'error' => $uploadError,
            ], 'layouts/admin');
            return;
        }

        try {
            if (!empty($_FILES['avatar']['name']) && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $payload['avatar_path'] = $this->storeAvatar($user->id, $_FILES['avatar'], $user->avatar_path);
            }
            $user->updateProfile($payload);
        } catch (\InvalidArgumentException $e) {
            View::render('admin/profile/index', [
                'title' => 'My profile',
                'user' => $this->previewUser($user, $payload),
                'success' => null,
                'error' => $e->getMessage(),
            ], 'layouts/admin');
            return;
        } catch (\Throwable $e) {
            View::render('admin/profile/index', [
                'title' => 'My profile',
                'user' => $this->previewUser($user, $payload),
                'success' => null,
                'error' => $e->getMessage() ?: 'Could not update profile.',
            ], 'layouts/admin');
            return;
        }

        View::redirect('/admin/profile?saved=1');
    }

    /** @param array<string, mixed> $payload */
    private function previewUser(User $user, array $payload): User
    {
        $user->username = (string) ($payload['username'] ?? $user->username);
        $user->email = (string) ($payload['email'] ?? $user->email);
        $user->phone = $payload['phone'] !== '' ? (string) $payload['phone'] : null;
        $user->display_name = ($payload['display_name'] ?? '') !== '' ? (string) $payload['display_name'] : null;
        return $user;
    }

    /** @param array<string, mixed>|null $file */
    private function avatarUploadError(?array $file): ?string
    {
        if ($file === null || empty($file['name'])) {
            return null;
        }

        return match ($file['error'] ?? UPLOAD_ERR_NO_FILE) {
            UPLOAD_ERR_OK => null,
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Image is too large. Maximum size is 2 MB.',
            UPLOAD_ERR_PARTIAL => 'Upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_FILE => null,
            default => 'Image upload failed. Please try again.',
        };
    }

    /** @param array<string, mixed> $file */
    private function storeAvatar(int $userId, array $file, ?string $previousPath): string
    {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : ($file['type'] ?? '');
        if ($finfo) {
            finfo_close($finfo);
        }

        if (!in_array($mime, $allowed, true)) {
            throw new \RuntimeException('Avatar must be JPG, PNG, WebP, or GIF.');
        }
        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            throw new \RuntimeException('Avatar must be under 2 MB.');
        }

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };

        $dir = dirname(__DIR__, 3) . '/public/uploads/avatars';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->removeAvatarFile($previousPath);

        $filename = 'user-' . $userId . '.' . $ext;
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('Failed to save profile image.');
        }

        return 'uploads/avatars/' . $filename;
    }

    private function removeAvatarFile(?string $path): void
    {
        if (!$path) {
            return;
        }
        $full = dirname(__DIR__, 3) . '/public/' . ltrim($path, '/');
        if (is_file($full)) {
            unlink($full);
        }
    }
}
