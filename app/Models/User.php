<?php

declare(strict_types=1);

namespace App\Models;

class User extends Model
{
    protected static string $table = 'users';

    public int $id;
    public ?int $member_id;
    public string $email;
    public string $password;
    public string $role;
    public ?string $magic_link_token;
    public ?string $magic_link_expires;
    public ?string $email_verified_at;
    public ?string $last_login_at;
    public ?string $created_at;

    public static function findByEmail(string $email): ?self
    {
        $stmt = self::query('SELECT * FROM users WHERE email = ?', [$email]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function findByMagicToken(string $token): ?self
    {
        $stmt = self::query(
            'SELECT * FROM users WHERE magic_link_token = ? AND magic_link_expires > ?',
            [$token, date('Y-m-d H:i:s')]
        );
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public function member(): ?Member
    {
        return $this->member_id ? Member::find($this->member_id) : null;
    }

    public function fullName(): string
    {
        $member = $this->member();
        return $member ? $member->fullName() : $this->email;
    }
}
