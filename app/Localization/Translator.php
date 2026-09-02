<?php

declare(strict_types=1);

namespace App\Localization;

use App\Core\Config;
use App\Core\Session;

class Translator
{
    private static string $locale = 'en';
    private static array $lines = [];
    private static string $langPath = '';

    public static function init(string $langPath): void
    {
        self::$langPath = rtrim($langPath, '/\\');
        self::$locale = (string) Config::get('app.locale', 'en');
        self::$lines = [];
    }

    public static function setLocale(string $locale): void
    {
        $supported = Config::get('app.supported_locales', ['en', 'fr']);
        if (in_array($locale, $supported, true)) {
            self::$locale = $locale;
        }
    }

    public static function getLocale(): string
    {
        return self::$locale;
    }

    public static function trans(string $key, array $replace = []): string
    {
        self::loadLocale(self::$locale);

        $line = self::getDot(self::$lines[self::$locale] ?? [], $key);

        // Fallback to English if translation is missing
        if ($line === null && self::$locale !== 'en') {
            self::loadLocale('en');
            $line = self::getDot(self::$lines['en'] ?? [], $key);
        }

        if ($line === null) {
            $line = $key;
        }

        foreach ($replace as $placeholder => $value) {
            $line = str_replace(
                [':' . $placeholder, '{' . $placeholder . '}'],
                (string) $value,
                (string) $line
            );
        }

        return (string) $line;
    }

    private static function getDot(array $array, string $key): mixed
    {
        if (isset($array[$key])) {
            return $array[$key];
        }

        $segments = explode('.', $key);
        $current = $array;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    private static function loadLocale(string $locale): void
    {
        if (isset(self::$lines[$locale]) && !empty(self::$lines[$locale])) {
            return;
        }

        $file = self::$langPath . DIRECTORY_SEPARATOR . $locale . '.php';
        if (file_exists($file)) {
            self::$lines[$locale] = require $file;
        } else {
            self::$lines[$locale] = [];
        }
    }
}
