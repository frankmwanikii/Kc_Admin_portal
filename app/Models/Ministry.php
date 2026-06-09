<?php

declare(strict_types=1);

namespace App\Models;

class Ministry extends Model
{
    protected static string $table = 'ministries';

    public int $id;
    public string $name;
    public ?string $description;
    public ?int $leader_id;
    public ?string $meeting_day;
    public int $is_active;
    public ?string $created_at;

    public static function withDetails(): array
    {
        $stmt = self::query("
            SELECT m.*, mem.first_name as leader_first, mem.last_name as leader_last,
                   (SELECT COUNT(*) FROM ministry_members mm WHERE mm.ministry_id = m.id) as member_count
            FROM ministries m
            LEFT JOIN members mem ON mem.id = m.leader_id
            WHERE m.is_active = 1
            ORDER BY m.name
        ");
        return $stmt->fetchAll();
    }
}
