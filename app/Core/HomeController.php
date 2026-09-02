<?php

declare(strict_types=1);

namespace App\Core;

use App\Challenges\ChallengeRepository;
use App\Matches\MatchRepository;

class HomeController
{
    public function landing(Request $request): Response
    {
        $topPlayers = MatchRepository::getGlobalLeaderboard(5);
        $recentChallenges = ChallengeRepository::listActive(4);

        $stats = [
            'total_matches' => (int) Database::selectValue("SELECT COUNT(*) FROM matches"),
            'total_pilots' => (int) Database::selectValue("SELECT COUNT(*) FROM users WHERE status = 'active'"),
            'high_score' => (int) Database::selectValue("SELECT COALESCE(MAX(score), 0) FROM matches WHERE status = 'completed'"),
            'tick_rate' => '60 Hz',
        ];

        return view('home.landing', [
            'top_players' => $topPlayers,
            'recent_challenges' => $recentChallenges,
            'stats' => $stats,
            'vehicles' => config('game.vehicles', []),
            'arenas' => config('game.arenas', []),
            'title' => config('app.name') . ' // ' . __('app.tagline'),
        ]);
    }
}
