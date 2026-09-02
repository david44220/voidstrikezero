<?php

declare(strict_types=1);

namespace App\Core;

class Env
{
    private static array $variables = [];
    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);

            // Strip surrounding quotes
            if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                $val = substr($val, 1, -1);
            }

            self::$variables[$key] = self::cast($val);
            putenv("{$key}={$val}");
            $_ENV[$key] = self::$variables[$key];
            $_SERVER[$key] = self::$variables[$key];
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$variables)) {
            return self::$variables[$key];
        }

        $envVal = getenv($key);
        if ($envVal !== false) {
            return self::cast($envVal);
        }

        return $default;
    }

    private static function cast(string $val): mixed
    {
        $lower = strtolower($val);
        if ($lower === 'true' || $lower === '(true)') {
            return true;
        }
        if ($lower === 'false' || $lower === '(false)') {
            return false;
        }
        if ($lower === 'null' || $lower === '(null)') {
            return null;
        }
        if (is_numeric($val)) {
            return str_contains($val, '.') ? (float) $val : (int) $val;
        }
        return $val;
    }
}
