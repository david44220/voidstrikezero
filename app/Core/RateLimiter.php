<?php

declare(strict_types=1);

namespace App\Core;

class RateLimiter
{
    private static string $storagePath = '';

    public static function init(?string $path = null): void
    {
        self::$storagePath = $path ?? dirname(__DIR__, 2) . '/storage/cache/rate_limits';
        if (!is_dir(self::$storagePath)) {
            mkdir(self::$storagePath, 0775, true);
        }
    }

    public static function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        return self::attempts($key) >= $maxAttempts;
    }

    public static function hit(string $key, int $decaySeconds = 60): int
    {
        self::init();
        $file = self::getFilePath($key);
        $now = time();

        $data = self::readData($file);
        if ($data === null || $data['expires_at'] <= $now) {
            $data = [
                'count' => 1,
                'expires_at' => $now + $decaySeconds,
            ];
        } else {
            $data['count']++;
        }

        self::writeData($file, $data);
        return $data['count'];
    }

    public static function attempts(string $key): int
    {
        self::init();
        $file = self::getFilePath($key);
        $data = self::readData($file);
        $now = time();

        if ($data === null || $data['expires_at'] <= $now) {
            return 0;
        }

        return (int) $data['count'];
    }

    public static function availableIn(string $key): int
    {
        self::init();
        $file = self::getFilePath($key);
        $data = self::readData($file);
        $now = time();

        if ($data === null || $data['expires_at'] <= $now) {
            return 0;
        }

        return max(0, (int) $data['expires_at'] - $now);
    }

    public static function clear(string $key): void
    {
        self::init();
        $file = self::getFilePath($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    private static function getFilePath(string $key): string
    {
        $hash = hash('sha256', $key);
        return self::$storagePath . DIRECTORY_SEPARATOR . "rate_{$hash}.json";
    }

    private static function readData(string $file): ?array
    {
        if (!file_exists($file)) {
            return null;
        }

        $fp = fopen($file, 'rb');
        if (!$fp) {
            return null;
        }

        flock($fp, LOCK_SH);
        $contents = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        if (!$contents) {
            return null;
        }

        $decoded = json_decode($contents, true);
        return is_array($decoded) ? $decoded : null;
    }

    private static function writeData(string $file, array $data): void
    {
        $fp = fopen($file, 'c+b');
        if (!$fp) {
            return;
        }

        flock($fp, LOCK_EX);
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}
