<?php

declare(strict_types=1);

namespace App\Admin;

use App\Auth\AuthService;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;

class AdminMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        $user = AuthService::user();

        if (!$user) {
            if ($request->isJson()) {
                return Response::json(['error' => 'Unauthenticated', 'status' => 401], 401);
            }
            flash('error', __('auth.must_be_logged_in'));
            return Response::redirect('/login');
        }

        if (!$user->isAdmin()) {
            if ($request->isJson()) {
                return Response::json(['error' => 'Forbidden: Administrator privileges required', 'status' => 403], 403);
            }
            throw new HttpException(403, __('admin.forbidden'));
        }

        return $next($request);
    }
}
