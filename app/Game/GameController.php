<?php

declare(strict_types=1);

namespace App\Game;

use App\Auth\AuthService;
use App\Challenges\ChallengeRepository;
use App\Core\Request;
use App\Core\Response;
use Exception;

class GameController
{
    public function arena(Request $request): Response
    {
        $user = AuthService::user();
        $challengeId = $request->query('challenge');
        $challenge = null;
        $rivalGhost = null;

        $selectedVehicle = $request->query('vehicle', $user?->selected_vehicle ?? 'striker');
        $selectedArena = $request->query('arena', 'neon_core');
        $selectedDiff = $request->query('diff', 'normal');
        $mode = 'quick';

        if ($challengeId) {
            $challenge = ChallengeRepository::find((string) $challengeId);
            if ($challenge) {
                $selectedVehicle = $challenge['vehicle_class'];
                $selectedArena = $challenge['arena_id'];
                $selectedDiff = $challenge['difficulty'];
                $mode = 'challenge';
            }
        }

        $vehicles = config('game.vehicles', []);
        $arenas = config('game.arenas', []);
        $difficulties = config('game.difficulties', []);

        return view('game.arena', [
            'user' => $user,
            'challenge' => $challenge,
            'selected_vehicle' => $selectedVehicle,
            'selected_arena' => $selectedArena,
            'selected_diff' => $selectedDiff,
            'mode' => $mode,
            'vehicles' => $vehicles,
            'arenas' => $arenas,
            'difficulties' => $difficulties,
            'title' => 'VOIDSTRIKE ARENA // Combat Operations',
        ], 'layouts.game');
    }

    public function apiStartMatch(Request $request): Response
    {
        $user = AuthService::user();
        $params = $request->all();

        try {
            $handshake = MatchEngine::startMatch($user, $params);
            return Response::json([
                'success' => true,
                'handshake' => $handshake,
            ]);
        } catch (Exception $e) {
            return Response::json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function apiFinishMatch(Request $request): Response
    {
        $user = AuthService::user();
        $data = $request->all();
        $ip = $request->ip();

        try {
            $result = MatchEngine::finishMatch($user, $data, $ip);
            return Response::json([
                'success' => true,
                'result' => $result,
            ]);
        } catch (Exception $e) {
            return Response::json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
