<?php

declare(strict_types=1);

namespace App\Achievements;

use App\Core\Database;
use App\Users\User;
use App\Notifications\NotificationService;

class AchievementService
{
    public static function checkAndAward(User $user, array $matchData): array
    {
        $unlocked = [];

        // Fetch already unlocked codes
        $existing = Database::select(
            "SELECT a.code FROM achievements a 
             INNER JOIN user_achievements ua ON ua.achievement_id = a.id 
             WHERE ua.user_id = :uid",
            [':uid' => $user->id]
        );
        $ownedCodes = array_column($existing, 'code');

        $toCheck = [];

        // 1. First Blood
        if (!in_array('first_blood', $ownedCodes, true)) {
            $toCheck[] = 'first_blood';
        }

        // 2. Apex Survivor (waves >= 5)
        if (!in_array('apex_survivor', $ownedCodes, true) && ($matchData['waves'] ?? 0) >= 5) {
            $toCheck[] = 'apex_survivor';
        }

        // 3. Untouchable (damage taken < 20)
        $damageTaken = (int) ($matchData['damage_taken'] ?? 0);
        if (!in_array('untouchable', $ownedCodes, true) && $damageTaken < 20 && ($matchData['waves'] ?? 0) >= 3) {
            $toCheck[] = 'untouchable';
        }

        // 4. Combo Sovereign (combo >= 5)
        if (!in_array('combo_master', $ownedCodes, true) && ($matchData['combo_max'] ?? 0) >= 5) {
            $toCheck[] = 'combo_master';
        }

        // 5. Titan Bastion
        $vehicle = $matchData['vehicle'] ?? 'striker';
        $absorbed = (int) ($matchData['absorbed_damage'] ?? 0);
        if (!in_array('titan_wall', $ownedCodes, true) && $vehicle === 'titan' && $absorbed >= 600) {
            $toCheck[] = 'titan_wall';
        }

        // 6. Speed Demon
        $kills = (int) ($matchData['kills'] ?? 0);
        $duration = (int) ($matchData['duration'] ?? 0);
        if (!in_array('speed_demon', $ownedCodes, true) && $vehicle === 'striker' && $kills >= 15 && $duration <= 60) {
            $toCheck[] = 'speed_demon';
        }

        // 7. Phase Walker
        $phases = (int) ($matchData['phase_shifts'] ?? 0);
        if (!in_array('phase_walker', $ownedCodes, true) && $vehicle === 'phantom' && $phases >= 12) {
            $toCheck[] = 'phase_walker';
        }

        // 8. Grandmaster
        if (!in_array('grandmaster', $ownedCodes, true) && $user->level >= 10) {
            $toCheck[] = 'grandmaster';
        }

        foreach ($toCheck as $code) {
            $ach = Database::selectOne("SELECT * FROM achievements WHERE code = :c", [':c' => $code]);
            if ($ach) {
                Database::insert('user_achievements', [
                    'user_id' => $user->id,
                    'achievement_id' => $ach['id'],
                    'unlocked_at' => gmdate('Y-m-d H:i:s'),
                ]);

                // Award achievement XP
                $xpReward = (int) $ach['xp_reward'];
                $user->addXp($xpReward);

                $unlocked[] = $ach;

                // Send notification
                NotificationService::create(
                    $user->id,
                    'achievement',
                    "Achievement Unlocked: {$ach['name_en']}",
                    "Succès Débloqué : {$ach['name_fr']}",
                    "You unlocked the {$ach['name_en']} accolade! Earned +{$xpReward} XP.",
                    "Vous avez débloqué la distinction {$ach['name_fr']} ! Gain de +{$xpReward} XP.",
                    ['achievement_code' => $code, 'xp' => $xpReward]
                );
            }
        }

        return $unlocked;
    }
}
