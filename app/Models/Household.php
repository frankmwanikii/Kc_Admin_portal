<?php

declare(strict_types=1);

namespace App\Models;

class Household extends Model
{
    protected static string $table = 'households';

    public int $id;
    public string $name;
    public ?string $address;
    public ?string $city;
    public ?string $phone;
    public ?int $head_member_id;
    public ?string $anniversary_date;
    public ?string $notes;
    public ?string $created_at;

    /** @return Member[] */
    public function members(): array
    {
        return Member::byHousehold($this->id);
    }

    public function head(): ?Member
    {
        return $this->head_member_id ? Member::find($this->head_member_id) : null;
    }

    public function memberCount(): int
    {
        $stmt = self::query('SELECT COUNT(*) FROM members WHERE household_id = ?', [$this->id]);
        return (int) $stmt->fetchColumn();
    }

    public static function withMemberCounts(): array
    {
        $stmt = self::query('
            SELECT h.*, COUNT(m.id) as member_count
            FROM households h
            LEFT JOIN members m ON m.household_id = h.id
            GROUP BY h.id
            ORDER BY h.name
        ');
        return $stmt->fetchAll();
    }
}
