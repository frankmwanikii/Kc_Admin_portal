<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;
use Throwable;

class SqlFile
{
    public static function run(PDO $db, string $path): void
    {
        $sql = is_file($path) ? file_get_contents($path) : false;
        if ($sql === false || trim($sql) === '') {
            return;
        }

        foreach (self::split($sql) as $statement) {
            try {
                self::execute($db, $statement);
            } catch (Throwable $e) {
                if (!self::isBenign($e)) {
                    throw $e;
                }
            }
        }
    }

    public static function runQuietly(PDO $db, string $path): void
    {
        try {
            self::run($db, $path);
        } catch (Throwable) {
            // Schema files may contain host-specific statements; callers create required tables themselves.
        }
    }

    public static function execute(PDO $db, string $sql): void
    {
        $stmt = $db->query($sql);
        if ($stmt instanceof PDOStatement) {
            self::drain($stmt);
        }
    }

    public static function drain(?PDOStatement $stmt): void
    {
        if ($stmt === null) {
            return;
        }

        try {
            $stmt->fetchAll();
        } catch (Throwable) {
        }

        try {
            while ($stmt->nextRowset()) {
                try {
                    $stmt->fetchAll();
                } catch (Throwable) {
                }
            }
        } catch (Throwable) {
        }

        try {
            $stmt->closeCursor();
        } catch (Throwable) {
        }
    }

    /** @return string[] */
    public static function split(string $sql): array
    {
        $statements = [];
        $buffer = '';
        foreach (explode("\n", $sql) as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                continue;
            }
            $buffer .= $line . "\n";
            if (str_ends_with(rtrim($line), ';')) {
                $statements[] = trim($buffer);
                $buffer = '';
            }
        }

        return array_values(array_filter($statements));
    }

    public static function isBenign(Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'already exists')
            || str_contains($message, 'duplicate column')
            || str_contains($message, 'duplicate key')
            || str_contains($message, 'multiple primary key')
            || str_contains($message, 'check that column/key exists')
            || str_contains($message, 'unbuffered queries are active');
    }
}
