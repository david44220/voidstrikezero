<?php

declare(strict_types=1);

namespace App\Challenges;

use App\Auth\AuthService;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

class ChallengeController
{
    public function index(Request $request): Response
    {
        $user = AuthService::user();
        $publicChallenges = ChallengeRepository::listActive(30);
        $myChallenges = $user ? ChallengeRepository::listForUser($user->id) : [];

        return view('challenges.index', [
            'public_challenges' => $publicChallenges,
            'my_challenges' => $myChallenges,
            'user' => $user,
            'title' => __('nav.challenges'),
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        $challenge = ChallengeRepository::find($id);
        if (!$challenge) {
            flash('error', 'Challenge not found or has expired.');
            return redirect('/challenges');
        }

        $attempts = ChallengeRepository::getAttempts($id, 25);

        return view('challenges.view', [
            'challenge' => $challenge,
            'attempts' => $attempts,
            'title' => 'Duel Challenge // ' . e($challenge['id']),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = AuthService::user();
        if (!$user) {
            return redirect('/login');
        }

        return view('challenges.create', [
            'user' => $user,
            'vehicles' => config('game.vehicles', []),
            'arenas' => config('game.arenas', []),
            'difficulties' => config('game.difficulties', []),
            'title' => __('challenges.create_title'),
        ]);
    }

    public function store(Request $request): Response
    {
        $user = AuthService::user();
        if (!$user) {
            return redirect('/login');
        }

        $validator = Validator::make($request->all(), [
            'target_score' => 'required|numeric|min:5000|max:500000',
            'vehicle_class' => 'required|in:striker,titan,phantom',
            'arena_id' => 'required|in:neon_core,orbital_station,magma_foundry',
            'difficulty' => 'required|in:easy,normal,hard',
        ]);

        if ($validator->fails()) {
            flash('error', $validator->firstError());
            session()->flashInput($request->all());
            return redirect('/challenges/create');
        }

        $score = (int) $request->input('target_score');
        $vehicle = (string) $request->input('vehicle_class');
        $arena = (string) $request->input('arena_id');
        $diff = (string) $request->input('difficulty');

        $challengeId = ChallengeRepository::create($user->id, $score, $vehicle, $arena, $diff, 7);

        flash('success', 'Challenge broadcast successfully to the galactic network!');
        return redirect('/challenge/' . $challengeId);
    }
}
