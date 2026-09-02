<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;

class AuthMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!AuthService::check()) {
            if ($request->isJson()) {
                return Response::json(['error' => 'Unauthenticated', 'status' => 401], 401);
            }

            flash('error', __('auth.must_be_logged_in'));
            return Response::redirect('/login');
        }

        return $next($request);
    }
}
