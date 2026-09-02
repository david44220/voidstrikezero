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

        $waves = (int) ($matchData['waves'] ?? 0);
        $kills = (int) ($matchData['kills'] ?? 0);
        $duration = (int) ($matchData['duration'] ?? 0);
        $damageTaken = (int) ($matchData['damage_taken'] ?? 0);
        $comboMax = (float) ($matchData['combo_max'] ?? 1.0);
        $vehicle = (string) ($matchData['vehicle'] ?? 'striker');
        $specialsUsed = (int) ($matchData['specials_used'] ?? 0);

        // Server-side sanitization of client fields to prevent metric fabrication:
        // Titan kinetic barrier: only valid on Titan with active special activations
        $rawAbsorbed = (int) ($matchData['absorbed_damage'] ?? 0);
        $sanitizedAbsorbed = ($vehicle === 'titan' && $specialsUsed > 0)
            ? min($rawAbsorbed, $specialsUsed * 250)
            : 0;

        // Phantom phase shifts: only valid on Phantom with active special activations
        $rawPhases = (int) ($matchData['phase_shifts'] ?? 0);
        $sanitizedPhases = ($vehicle === 'phantom' && $specialsUsed > 0)
            ? min($rawPhases, $specialsUsed * 2)
            : 0;

        // 1. First Blood (requires at least 1 verified kill)
        if (!in_array('first_blood', $ownedCodes, true) && $kills >= 1) {
            $toCheck[] = 'first_blood';
        }

        // 2. Apex Survivor (waves >= 5)
        if (!in_array('apex_survivor', $ownedCodes, true) && $waves >= 5) {
            $toCheck[] = 'apex_survivor';
        }

        // 3. Untouchable (damage taken < 20, survived at least 3 waves, duration >= 45s)
        if (!in_array('untouchable', $ownedCodes, true) && $damageTaken < 20 && $waves >= 3 && $duration >= 45) {
            $toCheck[] = 'untouchable';
        }

        // 4. Combo Sovereign (combo >= 5.0)
        if (!in_array('combo_master', $ownedCodes, true) && $comboMax >= 5.0) {
            $toCheck[] = 'combo_master';
        }

        // 5. Titan Bastion (absorbed >= 600 with Titan special shield)
        if (!in_array('titan_wall', $ownedCodes, true) && $vehicle === 'titan' && $sanitizedAbsorbed >= 600) {
            $toCheck[] = 'titan_wall';
        }

        // 6. Speed Demon (Striker, kills >= 15, duration <= 60s and >= 25s)
        if (!in_array('speed_demon', $ownedCodes, true) && $vehicle === 'striker' && $kills >= 15 && $duration <= 60 && $duration >= 25) {
            $toCheck[] = 'speed_demon';
        }

        // 7. Subspace Phantasm (Phantom, executed >= 12 Phase Shifts verified by specials used)
        if (!in_array('phase_walker', $ownedCodes, true) && $vehicle === 'phantom' && $sanitizedPhases >= 12) {
            $toCheck[] = 'phase_walker';
        }

        // 8. Void Grandmaster (Clearance Level >= 10)
        if (!in_array('grandmaster', $ownedCodes, true) && $user->level >= 10) {
            $toCheck[] = 'grandmaster';
        }

        foreach ($toCheck as $code) {
            $awarded = self::awardDirect($user, $code);
            if ($awarded) {
                $unlocked[] = $awarded;
            }
        }

        return $unlocked;
    }

    public static function awardDirect(User $user, string $code): ?array
    {
        $existing = Database::selectOne(
            "SELECT a.id FROM achievements a 
             INNER JOIN user_achievements ua ON ua.achievement_id = a.id 
             WHERE ua.user_id = :uid AND a.code = :code",
            [':uid' => $user->id, ':code' => $code]
        );

        if ($existing) {
            return null; // Already unlocked
        }

        $ach = Database::selectOne("SELECT * FROM achievements WHERE code = :c", [':c' => $code]);
        if (!$ach) {
            return null;
        }

        Database::insert('user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $ach['id'],
            'unlocked_at' => gmdate('Y-m-d H:i:s'),
        ]);

        // Award achievement XP (capped to 2000 XP)
        $xpReward = min(2000, (int) $ach['xp_reward']);
        $user->addXp($xpReward);

        // Send bilingual notification
        NotificationService::create(
            $user->id,
            'achievement',
            "Achievement Unlocked: {$ach['name_en']}",
            "Succès Débloqué : {$ach['name_fr']}",
            "You unlocked the {$ach['name_en']} accolade! Earned +{$xpReward} XP.",
            "Vous avez débloqué la distinction {$ach['name_fr']} ! Gain de +{$xpReward} XP.",
            ['achievement_code' => $code, 'xp' => $xpReward]
        );

        return $ach;
    }
}
