<?php

declare(strict_types=1);

namespace App\Game;

use App\Achievements\AchievementService;
use App\Admin\AuditLogger;
use App\Core\Database;
use App\Notifications\NotificationService;
use App\Users\User;
use Exception;

class MatchEngine
{
    public static function startMatch(?User $user, array $params): array
    {
        $vehicle = (string) ($params['vehicle'] ?? ($user?->selected_vehicle ?? 'striker'));
        $arena = (string) ($params['arena'] ?? 'neon_core');
        $difficulty = (string) ($params['difficulty'] ?? 'normal');
        $mode = (string) ($params['mode'] ?? 'quick');
        $challengeId = !empty($params['challenge_id']) ? (string) $params['challenge_id'] : null;

        // Validate vehicle and arena existence
        if (!config("game.vehicles.{$vehicle}")) {
            $vehicle = 'striker';
        }
        if (!config("game.arenas.{$arena}")) {
            $arena = 'neon_core';
        }
        if (!config("game.difficulties.{$difficulty}")) {
            $difficulty = 'normal';
        }

        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $seed = bin2hex(random_bytes(16));
        $startNonce = bin2hex(random_bytes(8));
        $expiresAt = gmdate('Y-m-d H:i:s', time() + (int) config('game.anti_cheat.token_lifetime_seconds', 600));

        Database::insert('run_tokens', [
            'token_hash' => $tokenHash,
            'user_id' => $user?->id,
            'vehicle_class' => $vehicle,
            'arena_id' => $arena,
            'difficulty' => $difficulty,
            'mode' => $mode,
            'challenge_id' => $challengeId,
            'seed' => $seed,
            'expires_at' => $expiresAt,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);

        return [
            'run_token' => $rawToken,
            'seed' => $seed,
            'start_nonce' => $startNonce,
            'vehicle' => $vehicle,
            'arena' => $arena,
            'difficulty' => $difficulty,
            'mode' => $mode,
            'timestamp' => time(),
        ];
    }

    public static function finishMatch(?User $user, array $data, string $ip): array
    {
        $rawToken = (string) ($data['run_token'] ?? '');
        if (empty($rawToken)) {
            throw new Exception("Missing run token");
        }

        $tokenHash = hash('sha256', $rawToken);
        $tokenRecord = Database::selectOne(
            "SELECT * FROM run_tokens WHERE token_hash = :hash",
            [':hash' => $tokenHash]
        );

        if (!$tokenRecord) {
            throw new Exception("Invalid run authorization token");
        }

        if ($tokenRecord['used_at'] !== null) {
            throw new Exception("Run authorization token already redeemed");
        }

        $now = time();
        if (strtotime($tokenRecord['expires_at']) < $now) {
            throw new Exception("Run authorization token has expired");
        }

        // Calculate server elapsed seconds
        $serverElapsed = max(0.1, (float) ($now - strtotime($tokenRecord['created_at'])));

        // Run anti-cheat heuristic analysis
        $validation = AntiCheatValidator::validate($tokenRecord, $data, $serverElapsed);

        $matchId = 'm_' . bin2hex(random_bytes(16));
        $score = (int) ($data['score'] ?? 0);
        $waves = (int) ($data['waves'] ?? 0);
        $kills = (int) ($data['kills'] ?? 0);
        $accuracy = (float) ($data['accuracy'] ?? 0.0);
        $comboMax = (int) ($data['combo_max'] ?? 1);
        $duration = (int) ($data['duration'] ?? 0);
        $vehicle = $tokenRecord['vehicle_class'];
        $arena = $tokenRecord['arena_id'];
        $diff = $tokenRecord['difficulty'];
        $mode = $tokenRecord['mode'];
        $challengeId = $tokenRecord['challenge_id'];

        $telemetrySummary = [
            'shots_fired' => (int) ($data['shots_fired'] ?? 0),
            'shots_hit' => (int) ($data['shots_hit'] ?? 0),
            'damage_dealt' => (int) ($data['damage_dealt'] ?? 0),
            'damage_taken' => (int) ($data['damage_taken'] ?? 0),
            'pickups_collected' => (int) ($data['pickups_collected'] ?? 0),
            'specials_used' => (int) ($data['specials_used'] ?? 0),
            'dashes_used' => (int) ($data['dashes_used'] ?? 0),
        ];

        $ghostData = isset($data['ghost']) && is_array($data['ghost']) ? $data['ghost'] : null;

        // Mark run token as used
        Database::update('run_tokens', ['used_at' => gmdate('Y-m-d H:i:s')], 'token_hash = :h', [':h' => $tokenHash]);

        // Insert match record
        Database::insert('matches', [
            'id' => $matchId,
            'user_id' => $user?->id,
            'vehicle_class' => $vehicle,
            'arena_id' => $arena,
            'difficulty' => $diff,
            'mode' => $mode,
            'score' => $score,
            'waves_cleared' => $waves,
            'kills' => $kills,
            'accuracy' => min(100.0, max(0.0, $accuracy)),
            'combo_max' => $comboMax,
            'duration_seconds' => $duration,
            'status' => $validation['status'],
            'anti_cheat_flags' => json_encode($validation),
            'run_token_hash' => $tokenHash,
            'start_nonce' => $data['start_nonce'] ?? null,
            'telemetry_summary' => json_encode($telemetrySummary),
            'ghost_data' => $ghostData ? json_encode($ghostData) : null,
            'created_at' => $tokenRecord['created_at'],
            'finished_at' => gmdate('Y-m-d H:i:s'),
        ]);

        $xpGained = 0;
        $leveledUp = false;
        $unlockedAchievements = [];

        if ($validation['valid']) {
            if ($user !== null) {
                // Compute XP: Base calculation from score, wave progression, and kills
                $xpGained = (int) (floor($score / 50) + ($waves * 60) + ($kills * 12));
                $diffMultiplier = (float) config("game.difficulties.{$diff}.score_multiplier", 1.0);
                $xpGained = (int) round($xpGained * ($diffMultiplier * 0.8));

                $leveledUp = $user->addXp($xpGained);

                // Check achievements
                $unlockedAchievements = AchievementService::checkAndAward($user, [
                    'waves' => $waves,
                    'damage_taken' => $telemetrySummary['damage_taken'],
                    'combo_max' => $comboMax,
                    'vehicle' => $vehicle,
                    'absorbed_damage' => $data['absorbed_damage'] ?? 0,
                    'kills' => $kills,
                    'duration' => $duration,
                    'phase_shifts' => $data['phase_shifts'] ?? 0,
                ]);

                // If this is a challenge attempt, record it
                if ($mode === 'challenge' && !empty($challengeId)) {
                    self::processChallengeAttempt($user, $challengeId, $matchId, $score);
                }
            }
        } else {
            // Log security event for audit
            AuditLogger::log(
                $user?->id,
                'cheat_attempt_flagged',
                'match',
                $matchId,
                [
                    'score' => $score,
                    'duration' => $duration,
                    'status' => $validation['status'],
                    'flags' => $validation['flags'],
                    'risk_score' => $validation['risk_score'],
                ],
                $ip
            );
        }

        return [
            'match_id' => $matchId,
            'valid' => $validation['valid'],
            'status' => $validation['status'],
            'score' => $score,
            'waves' => $waves,
            'kills' => $kills,
            'duration' => $duration,
            'xp_gained' => $xpGained,
            'new_level' => $user?->level,
            'leveled_up' => $leveledUp,
            'achievements' => $unlockedAchievements,
            'anti_cheat' => [
                'status' => $validation['status'],
                'risk_score' => $validation['risk_score'],
                'flags' => $validation['flags'],
            ],
        ];
    }

    private static function processChallengeAttempt(User $user, string $challengeId, string $matchId, int $score): void
    {
        $challenge = Database::selectOne("SELECT * FROM challenges WHERE id = :id", [':id' => $challengeId]);
        if (!$challenge || $challenge['status'] !== 'active') {
            return;
        }

        $targetScore = (int) $challenge['target_score'];
        $isBeaten = ($score > $targetScore);
        $attemptId = 'att_' . bin2hex(random_bytes(12));

        Database::insert('challenge_attempts', [
            'id' => $attemptId,
            'challenge_id' => $challengeId,
            'user_id' => $user->id,
            'match_id' => $matchId,
            'score' => $score,
            'is_beaten' => $isBeaten ? 1 : 0,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);

        Database::query(
            "UPDATE challenges SET challenger_count = challenger_count + 1 WHERE id = :id",
            [':id' => $challengeId]
        );

        if ($isBeaten) {
            // Update best attempt
            Database::update(
                'challenges',
                ['best_attempt_id' => $attemptId],
                'id = :id',
                [':id' => $challengeId]
            );

            // Notify challenge creator
            if ($challenge['creator_id'] !== $user->id) {
                NotificationService::create(
                    (int) $challenge['creator_id'],
                    'challenge_beaten',
                    "Challenge Beaten by {$user->display_name}!",
                    "Défi Battu par {$user->display_name} !",
                    "Pilot {$user->display_name} exceeded your target score ({$targetScore} pts) with {$score} pts!",
                    "Le pilote {$user->display_name} a dépassé votre score cible ({$targetScore} pts) avec {$score} pts !",
                    ['challenge_id' => $challengeId, 'score' => $score, 'challenger_id' => $user->id]
                );
            }
        }
    }
}
