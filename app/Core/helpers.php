<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Env;
use App\Core\Session;
use App\Core\Response;
use App\Core\View;
use App\Localization\Translator;
use App\Security\Csrf;
use App\Auth\AuthService;
use App\Users\User;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim((string) config('app.url', ''), '/');
        $path = '/' . ltrim($path, '/');
        return $base ? $base . $path : $path;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('/assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('__')) {
    function __(string $key, array $replace = []): string
    {
        return Translator::trans($key, $replace);
    }
}

if (!function_exists('session')) {
    function session(): Session
    {
        return Session::getInstance();
    }
}

if (!function_exists('auth')) {
    function auth(): ?User
    {
        return AuthService::user();
    }
}

if (!function_exists('flash')) {
    function flash(string $type, string $message): void
    {
        session()->flash($type, $message);
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = null): mixed
    {
        return session()->getOldInput($key, $default);
    }
}

if (!function_exists('view')) {
    function view(string $view, array $data = [], ?string $layout = 'layouts/main'): Response
    {
        $content = View::render($view, $data, $layout);
        return new Response($content, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}

if (!function_exists('json')) {
    function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $to, int $status = 302): Response
    {
        return Response::redirect($to, $status);
    }
}

if (!function_exists('abort')) {
    function abort(int $code, string $message = ''): void
    {
        throw new \App\Core\HttpException($code, $message);
    }
}
