<?php

declare(strict_types=1);

namespace App\Services;

class EnvService
{
    private string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? dirname(__DIR__, 2) . '/.env';
    }

    public function exists(): bool
    {
        return is_file($this->path);
    }

    public function ensureFromExample(): void
    {
        if (!$this->exists()) {
            $example = dirname(__DIR__, 2) . '/.env.example';
            if (!is_file($example)) {
                throw new \RuntimeException('.env.example not found.');
            }
            copy($example, $this->path);
        }
    }

    /** @param array<string, string|null> $values */
    public function setMany(array $values): void
    {
        $this->ensureFromExample();
        $lines = file($this->path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new \RuntimeException('Unable to read .env file.');
        }

        $keysWritten = [];
        foreach ($lines as $i => $line) {
            if ($line === '' || str_starts_with(trim($line), '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key] = explode('=', $line, 2);
            $key = trim($key);
            if (array_key_exists($key, $values)) {
                $lines[$i] = $this->formatLine($key, $values[$key]);
                $keysWritten[$key] = true;
            }
        }

        foreach ($values as $key => $value) {
            if (!isset($keysWritten[$key])) {
                $lines[] = $this->formatLine($key, $value);
            }
        }

        if (file_put_contents($this->path, implode(PHP_EOL, $lines) . PHP_EOL) === false) {
            throw new \RuntimeException('Unable to write .env file. Check folder permissions.');
        }

        foreach ($values as $key => $value) {
            $_ENV[$key] = $value ?? '';
            putenv($key . '=' . ($value ?? ''));
        }
    }

    private function formatLine(string $key, ?string $value): string
    {
        $value = $value ?? '';
        if ($value === '') {
            return $key . '=';
        }
        if (preg_match('/[\s#="\'\\\\]/', $value)) {
            return $key . '="' . addcslashes($value, '"\\') . '"';
        }
        return $key . '=' . $value;
    }
}
