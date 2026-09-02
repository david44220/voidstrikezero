<?php

declare(strict_types=1);

namespace App\Security;

use App\Core\Request;
use App\Core\Response;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $headers = (array) config('security.headers', []);
        foreach ($headers as $name => $value) {
            $response->header($name, $value);
        }

        return $response;
    }
}
