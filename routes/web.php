<?php

declare(strict_types=1);

use App\Admin\AdminController;
use App\Admin\AdminMiddleware;
use App\Auth\AuthController;
use App\Auth\AuthMiddleware;
use App\Auth\GuestMiddleware;
use App\Achievements\AchievementController;
use App\Challenges\ChallengeController;
use App\Core\HomeController;
use App\Core\Router;
use App\Game\GameController;
use App\Leaderboard\LeaderboardController;
use App\Notifications\NotificationController;
use App\Security\RateLimitMiddleware;
use App\Users\UserController;

/** @var Router $router */

// Public Pages
$router->get('/', [HomeController::class, 'landing']);
$router->get('/play', [GameController::class, 'arena']);

// Leaderboards
$router->get('/leaderboard', [LeaderboardController::class, 'global']);
$router->get('/leaderboard/weekly', [LeaderboardController::class, 'weekly']);

// Challenges
$router->get('/challenges', [ChallengeController::class, 'index']);
$router->get('/challenges/create', [ChallengeController::class, 'create'], [AuthMiddleware::class]);
$router->post('/challenges', [ChallengeController::class, 'store'], [
    AuthMiddleware::class,
    new RateLimitMiddleware('challenge_create', 10, 60),
]);
$router->get('/challenge/{id}', [ChallengeController::class, 'show']);

// Achievements
$router->get('/achievements', [AchievementController::class, 'index']);

// Authentication Routes (Guest Only)
$router->get('/login', [AuthController::class, 'showLogin'], [GuestMiddleware::class]);
$router->post('/login', [AuthController::class, 'login'], [
    GuestMiddleware::class,
    new RateLimitMiddleware('login', 5, 60),
]);

$router->get('/register', [AuthController::class, 'showRegister'], [GuestMiddleware::class]);
$router->post('/register', [AuthController::class, 'register'], [
    GuestMiddleware::class,
    new RateLimitMiddleware('register', 3, 120),
]);

$router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);

$router->get('/forgot-password', [AuthController::class, 'showForgotPassword'], [GuestMiddleware::class]);
$router->post('/forgot-password', [AuthController::class, 'sendResetLink'], [
    GuestMiddleware::class,
    new RateLimitMiddleware('password_reset', 3, 300),
]);

$router->get('/reset-password', [AuthController::class, 'showResetPassword'], [GuestMiddleware::class]);
$router->post('/reset-password', [AuthController::class, 'resetPassword'], [GuestMiddleware::class]);

// Authenticated Pilot Dashboard & Settings
$router->get('/dashboard', [UserController::class, 'dashboard'], [AuthMiddleware::class]);
$router->get('/settings', [UserController::class, 'showSettings'], [AuthMiddleware::class]);
$router->post('/settings', [UserController::class, 'updateSettings'], [AuthMiddleware::class]);

// Notifications
$router->get('/notifications', [NotificationController::class, 'index'], [AuthMiddleware::class]);
$router->post('/notifications/read-all', [NotificationController::class, 'markAllRead'], [AuthMiddleware::class]);

// Admin Nexus
$router->group(['prefix' => '/admin', 'middleware' => [AdminMiddleware::class]], function (Router $r) {
    $r->get('/', [AdminController::class, 'dashboard']);
    $r->get('/users', [AdminController::class, 'users']);
    $r->get('/matches', [AdminController::class, 'matches']);
    $r->get('/challenges', [AdminController::class, 'challenges']);
    $r->get('/audit', [AdminController::class, 'audit']);

    $r->post('/users/ban', [AdminController::class, 'banUser']);
    $r->post('/users/unban', [AdminController::class, 'unbanUser']);
    $r->post('/matches/invalidate', [AdminController::class, 'invalidateMatch']);
});
