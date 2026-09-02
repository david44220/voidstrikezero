<?php

declare(strict_types=1);

namespace App\Achievements;

use App\Auth\AuthService;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class AchievementController
{
    public function index(Request $request): Response
    {
        $user = AuthService::user();
        $achievements = Database::select("SELECT * FROM achievements ORDER BY id ASC");

        $unlockedMap = [];
        if ($user) {
            $userAchs = Database::select(
                "SELECT achievement_id, unlocked_at FROM user_achievements WHERE user_id = :uid",
                [':uid' => $user->id]
            );
            foreach ($userAchs as $ua) {
                $unlockedMap[$ua['achievement_id']] = $ua['unlocked_at'];
            }
        }

        return view('achievements.index', [
            'achievements' => $achievements,
            'unlocked_map' => $unlockedMap,
            'user' => $user,
            'title' => __('nav.achievements'),
        ]);
    }
}
