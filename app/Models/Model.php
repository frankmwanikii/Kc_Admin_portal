<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOStatement;

abstract class Model
{
    protected static string $table;

    public static function find(int $id): ?static
    {
        $stmt = Database::connection()->prepare('SELECT * FROM ' . static::$table . ' WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? static::hydrate($row) : null;
    }

    /** @return static[] */
    public static function all(string $orderBy = 'id DESC'): array
    {
        $stmt = Database::connection()->query('SELECT * FROM ' . static::$table . ' ORDER BY ' . $orderBy);
        return array_map(fn($row) => static::hydrate($row), $stmt->fetchAll());
    }

    protected static function hydrate(array $row): static
    {
        $model = new static();
        foreach ($row as $key => $value) {
            $model->$key = $value;
        }
        return $model;
    }

    protected static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
