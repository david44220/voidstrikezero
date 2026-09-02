<?php

declare(strict_types=1);

namespace App\Game;

use App\Admin\AuditLogger;
use App\Achievements\AchievementService;
use App\Core\Database;
use App\Notifications\NotificationService;
use App\Users\User;
use Exception;

class MatchEngine
{
    public static function startMatch(?User $user, array $params): array
    {
        // 1. Opportunistic cleanup of expired run tokens older than 1 hour
        try {
            Database::query("DELETE FROM run_tokens WHERE expires_at < (CURRENT_TIMESTAMP - INTERVAL '1 hour')");
            Database::query("UPDATE challenges SET status = 'expired' WHERE status = 'active' AND expires_at <= CURRENT_TIMESTAMP");
        } catch (\Throwable) {
            // Non-blocking cleanup
        }

        $mode = (string) ($params['mode'] ?? 'quick');
        if (!in_array($mode, ['quick', 'challenge', 'rival'], true)) {
            $mode = 'quick';
        }

        $vehicle = (string) ($params['vehicle'] ?? ($user?->selected_vehicle ?? 'striker'));
        $arena = (string) ($params['arena'] ?? 'neon_core');
        $difficulty = (string) ($params['difficulty'] ?? 'normal');
        $challengeId = !empty($params['challenge_id']) ? (string) $params['challenge_id'] : null;

        // Challenge integrity: Server enforces challenge configuration
        if ($mode === 'challenge') {
            if (empty($challengeId)) {
                throw new Exception("Challenge ID required for challenge engagement");
            }

            $challenge = Database::selectOne(
                "SELECT * FROM challenges WHERE id = :id AND status = 'active' AND expires_at > CURRENT_TIMESTAMP",
                [':id' => $challengeId]
            );

            if (!$challenge) {
                throw new Exception("Challenge not found, expired, or inactive");
            }

            // Strictly enforce server-authoritative challenge parameters
            $vehicle = $challenge['vehicle_class'];
            $arena = $challenge['arena_id'];
            $difficulty = $challenge['difficulty'];
        }

        // Validate vehicle, arena, difficulty existence
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
        $startNonce = bin2hex(random_bytes(16));
        $sessionId = session_id() ?: 'cli_' . bin2hex(random_bytes(8));
        $sessionHash = hash('sha256', $sessionId);
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
            'start_nonce' => $startNonce,
            'session_hash' => $sessionHash,
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
            'challenge_id' => $challengeId,
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

        // Execute redemption inside an atomic database transaction with row locking
        return Database::transaction(function (\PDO $pdo) use ($user, $data, $tokenHash, $ip) {
            // Row lock the token to strictly prevent concurrent / replay redemption
            $stmt = $pdo->prepare("SELECT * FROM run_tokens WHERE token_hash = :hash FOR UPDATE");
            $stmt->execute([':hash' => $tokenHash]);
            $tokenRecord = $stmt->fetch(\PDO::FETCH_ASSOC);

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

            // Atomic conditional update to mark as used
            $updateStmt = $pdo->prepare("UPDATE run_tokens SET used_at = CURRENT_TIMESTAMP WHERE id = :id AND used_at IS NULL");
            $updateStmt->execute([':id' => $tokenRecord['id']]);
            if ($updateStmt->rowCount() !== 1) {
                throw new Exception("Run authorization token already redeemed");
            }

            // Validate start nonce
            $clientNonce = (string) ($data['start_nonce'] ?? '');
            if (empty($tokenRecord['start_nonce']) || !hash_equals((string) $tokenRecord['start_nonce'], $clientNonce)) {
                throw new Exception("Security violation: invalid match start nonce");
            }

            // User / Guest boundary enforcement:
            // If token was issued to a guest (user_id is NULL), it MUST remain a guest run!
            if ($tokenRecord['user_id'] === null) {
                $user = null; // Strictly deny awarding XP or linking to authenticated user
            } else {
                // Token was issued to an authenticated user: Must be redeemed by that exact user
                if ($user === null || (int) $user->id !== (int) $tokenRecord['user_id']) {
                    throw new Exception("Security violation: run token user binding mismatch");
                }
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

            // Validate and bound ghost data
            $ghostData = null;
            if (isset($data['ghost']) && is_array($data['ghost'])) {
                // Bound ghost representation to max 300 checkpoints
                $bounded = array_slice($data['ghost'], 0, 300);
                $validGhost = [];
                foreach ($bounded as $cp) {
                    if (is_array($cp) && count($cp) >= 4) {
                        $validGhost[] = [
                            round((float) ($cp[0] ?? 0), 2),
                            round((float) ($cp[1] ?? 0), 2),
                            round((float) ($cp[2] ?? 0), 2),
                            round((float) ($cp[3] ?? 0), 2),
                            !empty($cp[4]) ? 1 : 0,
                        ];
                    }
                }
                if (!empty($validGhost)) {
                    $ghostData = $validGhost;
                }
            }

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
                'start_nonce' => $clientNonce,
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
                    $rawXp = (int) (floor($score / 50) + ($waves * 60) + ($kills * 12));
                    $diffMultiplier = (float) config("game.difficulties.{$diff}.score_multiplier", 1.0);
                    $calculatedXp = (int) round($rawXp * ($diffMultiplier * 0.8));
                    // Cap maximum XP awardable per match to 5,000 pts
                    $xpGained = min(5000, max(10, $calculatedXp));

                    $leveledUp = $user->addXp($xpGained);

                    // Check achievements with verified combat telemetry metrics
                    $unlockedAchievements = AchievementService::checkAndAward($user, [
                        'waves' => $waves,
                        'damage_taken' => $telemetrySummary['damage_taken'],
                        'combo_max' => $comboMax,
                        'vehicle' => $vehicle,
                        'absorbed_damage' => (int) ($data['absorbed_damage'] ?? 0),
                        'kills' => $kills,
                        'duration' => $duration,
                        'phase_shifts' => (int) ($data['phase_shifts'] ?? 0),
                        'specials_used' => $telemetrySummary['specials_used'],
                    ]);

                    // If this is a challenge attempt, record it
                    if ($mode === 'challenge' && !empty($challengeId)) {
                        self::processChallengeAttempt($user, $challengeId, $matchId, $score);
                    }
                }
            } else {
                // Log security incident for audit trail
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
        });
    }

    private static function processChallengeAttempt(User $user, string $challengeId, string $matchId, int $score): void
    {
        $challenge = Database::selectOne(
            "SELECT * FROM challenges WHERE id = :id AND status = 'active' AND expires_at > CURRENT_TIMESTAMP",
            [':id' => $challengeId]
        );
        if (!$challenge) {
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
            // Check existing best attempt: only replace if new score is strictly better
            $shouldUpdateBest = false;
            if (empty($challenge['best_attempt_id'])) {
                $shouldUpdateBest = true;
            } else {
                $currentBest = Database::selectOne(
                    "SELECT score FROM challenge_attempts WHERE id = :aid",
                    [':aid' => $challenge['best_attempt_id']]
                );
                if (!$currentBest || $score > (int) $currentBest['score']) {
                    $shouldUpdateBest = true;
                }
            }

            if ($shouldUpdateBest) {
                Database::update(
                    'challenges',
                    ['best_attempt_id' => $attemptId],
                    'id = :id',
                    [':id' => $challengeId]
                );
            }

            // Award 'challenger' achievement for beating a challenge
            AchievementService::awardDirect($user, 'challenger');

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
