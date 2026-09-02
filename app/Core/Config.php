<?php

declare(strict_types=1);

namespace App\Core;

class Config
{
    private static array $configs = [];
    private static string $basePath = '';

    public static function init(string $basePath): void
    {
        self::$basePath = rtrim($basePath, '/\\');
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $file = array_shift($segments);

        if (!isset(self::$configs[$file])) {
            $path = self::$basePath . DIRECTORY_SEPARATOR . $file . '.php';
            if (file_exists($path)) {
                self::$configs[$file] = require $path;
            } else {
                self::$configs[$file] = [];
            }
        }

        $current = self::$configs[$file];
        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $file = array_shift($segments);

        if (!isset(self::$configs[$file])) {
            self::$configs[$file] = [];
        }

        $current = &self::$configs[$file];
        foreach ($segments as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }
        $current = $value;
    }
}
