<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'VOIDSTRIKE ARENA'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://127.0.0.1:8000'),
    'secret' => env('APP_SECRET', 'voidstrike_default_secret_key_32_bytes!'),
    'locale' => env('DEFAULT_LOCALE', 'en'),
    'supported_locales' => explode(',', (string) env('SUPPORTED_LOCALES', 'en,fr')),
    'timezone' => 'UTC',
];
