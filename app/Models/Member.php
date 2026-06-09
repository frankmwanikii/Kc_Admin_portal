<?php

declare(strict_types=1);

namespace App\Models;

class Member extends Model
{
    protected static string $table = 'members';

    public int $id;
    public ?int $household_id;
    public string $first_name;
    public string $last_name;
    public ?string $email;
    public ?string $phone;
    public ?string $gender;
    public ?string $date_of_birth;
    public ?string $marital_status;
    public ?string $occupation;
    public ?string $photo_url;
    public int $is_head_of_household;
    public string $membership_status;
    public ?string $joined_date;
    public ?string $onboarding_token;
    public int $onboarding_completed;
    public ?string $created_at;

    public function fullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function household(): ?Household
    {
        return $this->household_id ? Household::find($this->household_id) : null;
    }

    public static function findByToken(string $token): ?self
    {
        $stmt = self::query('SELECT * FROM members WHERE onboarding_token = ?', [$token]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    /** @return self[] */
    public static function search(string $term): array
    {
        $like = '%' . $term . '%';
        $stmt = self::query(
            'SELECT * FROM members WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY last_name',
            [$like, $like, $like, $like]
        );
        return array_map(fn($row) => self::hydrate($row), $stmt->fetchAll());
    }

    /** @return self[] */
    public static function byHousehold(int $householdId): array
    {
        $stmt = self::query('SELECT * FROM members WHERE household_id = ? ORDER BY is_head_of_household DESC, first_name', [$householdId]);
        return array_map(fn($row) => self::hydrate($row), $stmt->fetchAll());
    }

    public static function missedAttendance(int $weeks = 3): array
    {
        $cutoff = date('Y-m-d', strtotime('-' . ($weeks * 7) . ' days'));
        $stmt = self::query("
            SELECT m.*, MAX(s.session_date) as last_attended
            FROM members m
            LEFT JOIN attendance_records ar ON ar.member_id = m.id
            LEFT JOIN attendance_sessions s ON s.id = ar.session_id AND ar.status = 'present'
            WHERE m.membership_status = 'active'
            GROUP BY m.id
            HAVING last_attended IS NULL OR last_attended < ?
            ORDER BY last_attended ASC
        ", [$cutoff]);
        return array_map(fn($row) => self::hydrate($row), $stmt->fetchAll());
    }
}
