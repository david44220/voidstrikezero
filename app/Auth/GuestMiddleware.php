<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Request;
use App\Core\Response;

class GuestMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (AuthService::check()) {
            if ($request->isJson()) {
                return Response::json(['message' => 'Already authenticated'], 200);
            }
            return Response::redirect('/dashboard');
        }

        return $next($request);
    }
}
