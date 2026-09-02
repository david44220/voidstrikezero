<?php

declare(strict_types=1);

return [
    'session' => [
        'name' => 'voidstrike_session',
        'lifetime' => (int) env('SESSION_LIFETIME', 7200),
        'path' => '/',
        'domain' => null,
        'secure' => (bool) env('SESSION_SECURE', false),
        'httponly' => true,
        'samesite' => env('SESSION_SAME_SITE', 'Strict'),
    ],
    'csrf' => [
        'token_name' => '_csrf',
        'header_name' => 'X-CSRF-Token',
        'lifetime' => 7200,
    ],
    'rate_limit' => [
        'enabled' => (bool) env('RATE_LIMIT_ENABLED', true),
        'max_attempts' => (int) env('RATE_LIMIT_MAX_ATTEMPTS', 60),
        'decay_seconds' => (int) env('RATE_LIMIT_DECAY_MINUTES', 1) * 60,
        'endpoints' => [
            'login' => ['max' => 5, 'decay' => 60],
            'register' => ['max' => 3, 'decay' => 120],
            'password_reset' => ['max' => 3, 'decay' => 300],
            'match_finish' => ['max' => 30, 'decay' => 60],
            'challenge_create' => ['max' => 10, 'decay' => 60],
        ],
    ],
    'headers' => [
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self'; media-src 'self' blob:; worker-src 'self' blob:;",
    ],
];
