<?php

declare(strict_types=1);

namespace App\Leaderboard;

use App\Auth\AuthService;
use App\Core\Request;
use App\Core\Response;
use App\Matches\MatchRepository;

class LeaderboardController
{
    public function global(Request $request): Response
    {
        $vehicle = $request->query('vehicle') ?: null;
        $arena = $request->query('arena') ?: null;
        $search = $request->query('q') ?: null;

        $leaderboard = MatchRepository::getGlobalLeaderboard(50, $vehicle, $arena, $search);

        $user = AuthService::user();
        $personalRank = $user ? MatchRepository::getUserRank($user->id) : null;
        $pb = $user ? MatchRepository::getPersonalBest($user->id) : null;

        return view('leaderboard.global', [
            'tab' => 'global',
            'leaderboard' => $leaderboard,
            'personal_rank' => $personalRank,
            'pb' => $pb,
            'current_vehicle' => $vehicle,
            'current_arena' => $arena,
            'search' => $search,
            'vehicles' => config('game.vehicles', []),
            'arenas' => config('game.arenas', []),
            'title' => __('nav.leaderboard') . ' // ' . __('leaderboard.global_tab'),
        ]);
    }

    public function weekly(Request $request): Response
    {
        $vehicle = $request->query('vehicle') ?: null;
        $arena = $request->query('arena') ?: null;
        $search = $request->query('q') ?: null;

        $leaderboard = MatchRepository::getWeeklyLeaderboard(50, $vehicle, $arena, $search);

        $user = AuthService::user();
        $personalRank = $user ? MatchRepository::getUserRank($user->id) : null;

        return view('leaderboard.weekly', [
            'tab' => 'weekly',
            'leaderboard' => $leaderboard,
            'personal_rank' => $personalRank,
            'current_vehicle' => $vehicle,
            'current_arena' => $arena,
            'search' => $search,
            'vehicles' => config('game.vehicles', []),
            'arenas' => config('game.arenas', []),
            'title' => __('nav.leaderboard') . ' // ' . __('leaderboard.weekly_tab'),
        ]);
    }

    public function apiLeaderboard(Request $request): Response
    {
        $type = $request->query('type', 'global');
        $limit = min(100, max(5, (int) $request->query('limit', 20)));
        $vehicle = $request->query('vehicle') ?: null;
        $arena = $request->query('arena') ?: null;
        $search = $request->query('q') ?: null;

        if ($type === 'weekly') {
            $rows = MatchRepository::getWeeklyLeaderboard($limit, $vehicle, $arena, $search);
        } else {
            $rows = MatchRepository::getGlobalLeaderboard($limit, $vehicle, $arena, $search);
        }

        return Response::json([
            'type' => $type,
            'count' => count($rows),
            'rankings' => $rows,
        ]);
    }
}
