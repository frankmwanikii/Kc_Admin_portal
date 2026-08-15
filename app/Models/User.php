<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class User extends Model
{
    protected static string $table = 'users';

    public int $id;
    public ?int $member_id;
    public ?string $username = null;
    public string $email;
    public ?string $phone = null;
    public ?string $display_name = null;
    public ?string $avatar_path = null;
    public string $password;
    public string $role;
    public ?string $magic_link_token;
    public ?string $magic_link_expires;
    public ?string $email_verified_at;
    public ?string $last_login_at;
    public ?string $created_at;

    private static bool $profileColumnsReady = false;

    public static function ensureProfileColumns(): void
    {
        if (self::$profileColumnsReady) {
            return;
        }

        $db = Database::connection();
        $existing = [];
        foreach ($db->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN) as $col) {
            $existing[(string) $col] = true;
        }

        if (!isset($existing['phone'])) {
            $db->exec('ALTER TABLE users ADD COLUMN phone VARCHAR(40) NULL AFTER email');
        }
        if (!isset($existing['display_name'])) {
            $db->exec('ALTER TABLE users ADD COLUMN display_name VARCHAR(150) NULL AFTER phone');
        }
        if (!isset($existing['avatar_path'])) {
            $db->exec('ALTER TABLE users ADD COLUMN avatar_path VARCHAR(255) NULL AFTER display_name');
        }

        self::$profileColumnsReady = true;
    }

    public static function findByEmail(string $email): ?self
    {
        self::ensureProfileColumns();
        $stmt = self::query('SELECT * FROM users WHERE email = ?', [$email]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function findByUsername(string $username): ?self
    {
        self::ensureProfileColumns();
        $username = trim($username);
        if ($username === '') {
            return null;
        }
        $stmt = self::query('SELECT * FROM users WHERE username = ?', [$username]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function findByMagicToken(string $token): ?self
    {
        self::ensureProfileColumns();
        $stmt = self::query(
            'SELECT * FROM users WHERE magic_link_token = ? AND magic_link_expires > ?',
            [$token, date('Y-m-d H:i:s')]
        );
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function find(int $id): ?static
    {
        self::ensureProfileColumns();
        return parent::find($id);
    }

    public function member(): ?Member
    {
        return $this->member_id ? Member::find($this->member_id) : null;
    }

    public function fullName(): string
    {
        if (!empty($this->display_name)) {
            return (string) $this->display_name;
        }
        $member = $this->member();
        if ($member) {
            return $member->fullName();
        }
        if (!empty($this->username)) {
            return (string) $this->username;
        }
        return $this->email;
    }

    public function initials(): string
    {
        $name = trim($this->fullName());
        if ($name === '') {
            return 'A';
        }
        $parts = preg_split('/\s+/', $name) ?: [];
        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }

    public function avatarUrl(): ?string
    {
        if (empty($this->avatar_path)) {
            return null;
        }
        $relative = ltrim((string) $this->avatar_path, '/');
        $full = dirname(__DIR__, 2) . '/public/' . $relative;
        if (!is_file($full)) {
            return null;
        }
        return '/' . $relative . '?v=' . filemtime($full);
    }

    /**
     * @param array{
     *   username?: string,
     *   email?: string,
     *   phone?: string|null,
     *   display_name?: string|null,
     *   password?: string|null,
     *   avatar_path?: string|null
     * } $data
     */
    public function updateProfile(array $data): void
    {
        self::ensureProfileColumns();
        $db = Database::connection();

        $username = trim((string) ($data['username'] ?? $this->username ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? $this->email)));
        $phone = trim((string) ($data['phone'] ?? ''));
        $displayName = trim((string) ($data['display_name'] ?? ''));
        $avatarPath = array_key_exists('avatar_path', $data)
            ? $data['avatar_path']
            : $this->avatar_path;

        if ($username === '') {
            throw new \InvalidArgumentException('Username is required.');
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Enter a valid email address.');
        }

        $dupUser = $db->prepare('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
        $dupUser->execute([$username, $this->id]);
        if ($dupUser->fetchColumn()) {
            throw new \InvalidArgumentException('That username is already taken.');
        }

        $dupEmail = $db->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
        $dupEmail->execute([$email, $this->id]);
        if ($dupEmail->fetchColumn()) {
            throw new \InvalidArgumentException('That email is already in use.');
        }

        $password = $data['password'] ?? null;
        if (is_string($password) && $password !== '') {
            if (strlen($password) < 8) {
                throw new \InvalidArgumentException('New password must be at least 8 characters.');
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare('
                UPDATE users
                SET username = ?, email = ?, phone = ?, display_name = ?, avatar_path = ?, password = ?
                WHERE id = ?
            ');
            $stmt->execute([
                $username,
                $email,
                $phone !== '' ? $phone : null,
                $displayName !== '' ? $displayName : null,
                $avatarPath,
                $hash,
                $this->id,
            ]);
            $this->password = $hash;
        } else {
            $stmt = $db->prepare('
                UPDATE users
                SET username = ?, email = ?, phone = ?, display_name = ?, avatar_path = ?
                WHERE id = ?
            ');
            $stmt->execute([
                $username,
                $email,
                $phone !== '' ? $phone : null,
                $displayName !== '' ? $displayName : null,
                $avatarPath,
                $this->id,
            ]);
        }

        $this->username = $username;
        $this->email = $email;
        $this->phone = $phone !== '' ? $phone : null;
        $this->display_name = $displayName !== '' ? $displayName : null;
        $this->avatar_path = $avatarPath;
    }
}
