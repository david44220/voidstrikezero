<?php

declare(strict_types=1);

use App\Core\Router;
use App\Game\GameController;
use App\Leaderboard\LeaderboardController;
use App\Security\RateLimitMiddleware;

/** @var Router $router */

$router->post('/api/match/start', [GameController::class, 'apiStartMatch'], [
    new RateLimitMiddleware('match_start', 15, 60),
]);

$router->post('/api/match/finish', [GameController::class, 'apiFinishMatch'], [
    new RateLimitMiddleware('match_finish', 30, 60),
]);

$router->get('/api/leaderboard', [LeaderboardController::class, 'apiLeaderboard']);
