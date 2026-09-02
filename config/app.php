<?php

declare(strict_types=1);

$env = (string) env('APP_ENV', 'development');
$secret = env('APP_SECRET');

if ($env === 'production') {
    if (empty($secret) || $secret === 'voidstrike_default_secret_key_32_bytes!' || strlen((string) $secret) < 32) {
        throw new \RuntimeException(
            "CRITICAL SECURITY CONFIGURATION ERROR: APP_SECRET must be explicitly configured with a cryptographically secure key (minimum 32 characters) in production."
        );
    }
} else {
    $secret = $secret ?: 'voidstrike_dev_insecure_secret_key_32_bytes!';
}

return [
    'name' => env('APP_NAME', 'VOIDSTRIKE ARENA'),
    'env' => $env,
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://127.0.0.1:8000'),
    'secret' => $secret,
    'locale' => env('DEFAULT_LOCALE', 'en'),
    'supported_locales' => explode(',', (string) env('SUPPORTED_LOCALES', 'en,fr')),
    'timezone' => 'UTC',
];
