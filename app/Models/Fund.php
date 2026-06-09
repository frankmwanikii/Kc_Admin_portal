<?php

declare(strict_types=1);

namespace App\Models;

class Fund extends Model
{
    protected static string $table = 'funds';

    public int $id;
    public string $name;
    public string $code;
    public ?string $description;
    public int $is_active;
    public ?string $created_at;

    public static function active(): array
    {
        $stmt = self::query('SELECT * FROM funds WHERE is_active = 1 ORDER BY name');
        return array_map(fn($row) => self::hydrate($row), $stmt->fetchAll());
    }
}
