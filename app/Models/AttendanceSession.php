<?php

declare(strict_types=1);

namespace App\Models;

class AttendanceSession extends Model
{
    protected static string $table = 'attendance_sessions';

    public int $id;
    public string $title;
    public string $type;
    public string $session_date;
    public ?string $start_time;
    public ?string $location;
    public ?string $notes;
    public ?int $created_by;
    public ?string $created_at;

    public function attendanceCount(): int
    {
        $stmt = self::query("SELECT COUNT(*) FROM attendance_records WHERE session_id = ? AND status = 'present'", [$this->id]);
        return (int) $stmt->fetchColumn();
    }

    public static function recent(int $limit = 10): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = self::query("SELECT * FROM attendance_sessions ORDER BY session_date DESC LIMIT {$limit}");
        return array_map(fn($row) => self::hydrate($row), $stmt->fetchAll());
    }
}
