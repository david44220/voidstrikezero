<?php

declare(strict_types=1);

// VOIDSTRIKE ARENA — Front Controller
// Pure PHP 8.5 Frameworkless Architecture

define('VOIDSTRIKE_START', microtime(true));

$basePath = dirname(__DIR__);

require_once $basePath . '/vendor/autoload.php';

use App\Core\Config;
use App\Core\Env;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Localization\LocaleMiddleware;
use App\Localization\Translator;
use App\Security\CsrfMiddleware;
use App\Security\SecurityHeadersMiddleware;

// 1. Bootstrap Core Services
Env::load($basePath . '/.env');
Config::init($basePath . '/config');
Translator::init($basePath . '/resources/lang');
View::init($basePath . '/resources/views');

// 2. Initialize Session
Session::getInstance()->start();

// 3. Initialize Router & Middlewares
$router = new Router();

// Global Middlewares (applied in pipeline order)
$router->use(SecurityHeadersMiddleware::class);
$router->use(LocaleMiddleware::class);

// CSRF Protection (Exempt API routes which use run authorization tokens)
$router->use(new CsrfMiddleware([
    '/api/match/start',
    '/api/match/finish',
]));

// 4. Register Routes
require_once $basePath . '/routes/web.php';
require_once $basePath . '/routes/api.php';

// 5. Capture Request and Dispatch
$request = Request::capture();
$response = $router->dispatch($request);

// 6. Send Response to Client
$response->send();
