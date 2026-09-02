<?php

declare(strict_types=1);

namespace App\Security;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;

class CsrfMiddleware
{
    private array $exemptUris = [];

    public function __construct(array $exemptUris = [])
    {
        $this->exemptUris = $exemptUris;
    }

    public function handle(Request $request, callable $next): Response
    {
        $method = $request->method();

        // Safe methods do not mutate state
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        // Check if route is exempt
        foreach ($this->exemptUris as $exempt) {
            if ($request->uri() === $exempt) {
                return $next($request);
            }
        }

        $token = $request->input('_csrf') ?? $request->header('x-csrf-token');

        if (!Csrf::validate($token)) {
            throw new HttpException(419, 'CSRF token mismatch. Please reload the page and try again.');
        }

        return $next($request);
    }
}
