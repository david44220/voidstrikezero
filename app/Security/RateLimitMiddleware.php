<?php

declare(strict_types=1);

namespace App\Security;

use App\Core\HttpException;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;

class RateLimitMiddleware
{
    private string $action;
    private int $maxAttempts;
    private int $decaySeconds;

    public function __construct(string $action = 'general', int $maxAttempts = 60, int $decaySeconds = 60)
    {
        $this->action = $action;

        // Check if configured in security.php
        $configured = config("security.rate_limit.endpoints.{$action}");
        if (is_array($configured)) {
            $this->maxAttempts = (int) ($configured['max'] ?? $maxAttempts);
            $this->decaySeconds = (int) ($configured['decay'] ?? $decaySeconds);
        } else {
            $this->maxAttempts = $maxAttempts;
            $this->decaySeconds = $decaySeconds;
        }
    }

    public function handle(Request $request, callable $next): Response
    {
        if (!config('security.rate_limit.enabled', true)) {
            return $next($request);
        }

        $key = "rl:{$this->action}:" . $request->ip();

        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);
            if ($request->isJson()) {
                $resp = Response::json([
                    'error' => 'Too many requests. Please slow down.',
                    'retry_after' => $retryAfter,
                ], 429);
                $resp->header('Retry-After', (string) $retryAfter);
                return $resp;
            }

            throw new HttpException(429, "Too many requests. Please retry in {$retryAfter} seconds.");
        }

        RateLimiter::hit($key, $this->decaySeconds);

        /** @var Response $response */
        $response = $next($request);
        $remaining = max(0, $this->maxAttempts - RateLimiter::attempts($key));
        $response->header('X-RateLimit-Limit', (string) $this->maxAttempts);
        $response->header('X-RateLimit-Remaining', (string) $remaining);

        return $response;
    }
}
