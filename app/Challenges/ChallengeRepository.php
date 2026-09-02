<?php

declare(strict_types=1);

namespace App\Challenges;

use App\Core\Database;

class ChallengeRepository
{
    public static function find(string $id): ?array
    {
        return Database::selectOne(
            "SELECT c.*, u.username as creator_username, u.display_name as creator_name, u.avatar_url as creator_avatar,
                    ba.score as best_score, bu.username as best_username, bu.display_name as best_challenger_name
             FROM challenges c
             INNER JOIN users u ON u.id = c.creator_id
             LEFT JOIN challenge_attempts ba ON ba.id = c.best_attempt_id
             LEFT JOIN users bu ON bu.id = ba.user_id
             WHERE c.id = :id",
            [':id' => $id]
        );
    }

    public static function listActive(int $limit = 20): array
    {
        try {
            Database::query("UPDATE challenges SET status = 'expired' WHERE status = 'active' AND expires_at <= CURRENT_TIMESTAMP");
        } catch (\Throwable) {
        }

        return Database::select(
            "SELECT c.*, u.username as creator_username, u.display_name as creator_name, u.avatar_url as creator_avatar,
                    ba.score as best_score, bu.username as best_username, bu.display_name as best_challenger_name
             FROM challenges c
             INNER JOIN users u ON u.id = c.creator_id
             LEFT JOIN challenge_attempts ba ON ba.id = c.best_attempt_id
             LEFT JOIN users bu ON bu.id = ba.user_id
             WHERE c.status = 'active' AND c.expires_at > CURRENT_TIMESTAMP
             ORDER BY c.created_at DESC LIMIT :lim",
            [':lim' => $limit]
        );
    }

    public static function listForUser(int $userId): array
    {
        return Database::select(
            "SELECT c.*, ba.score as best_score, bu.username as best_username, bu.display_name as best_challenger_name
             FROM challenges c
             LEFT JOIN challenge_attempts ba ON ba.id = c.best_attempt_id
             LEFT JOIN users bu ON bu.id = ba.user_id
             WHERE c.creator_id = :uid
             ORDER BY c.created_at DESC",
            [':uid' => $userId]
        );
    }

    public static function create(
        int $creatorId,
        int $targetScore,
        string $vehicle,
        string $arena,
        string $diff,
        int $durationDays = 7
    ): string {
        $id = 'c_' . bin2hex(random_bytes(10));
        $expiresAt = gmdate('Y-m-d H:i:s', time() + ($durationDays * 86400));

        Database::insert('challenges', [
            'id' => $id,
            'creator_id' => $creatorId,
            'target_score' => $targetScore,
            'vehicle_class' => $vehicle,
            'arena_id' => $arena,
            'difficulty' => $diff,
            'expires_at' => $expiresAt,
            'status' => 'active',
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);

        return $id;
    }

    public static function getAttempts(string $challengeId, int $limit = 20): array
    {
        return Database::select(
            "SELECT ca.*, u.username, u.display_name, u.avatar_url 
             FROM challenge_attempts ca 
             INNER JOIN users u ON u.id = ca.user_id 
             WHERE ca.challenge_id = :cid 
             ORDER BY ca.score DESC, ca.created_at ASC LIMIT :lim",
            [':cid' => $challengeId, ':lim' => $limit]
        );
    }

    public static function recordAttempt(string $challengeId, ?int $userId, string $matchId, int $score): ?string
    {
        $challenge = Database::selectOne("SELECT * FROM challenges WHERE id = :id", [':id' => $challengeId]);
        if (!$challenge) {
            return null;
        }

        if ($userId === null) {
            return null;
        }

        $attemptId = 'att_' . bin2hex(random_bytes(10));
        $isBeaten = $score >= (int) $challenge['target_score'];

        Database::insert('challenge_attempts', [
            'id' => $attemptId,
            'challenge_id' => $challengeId,
            'user_id' => $userId,
            'match_id' => $matchId,
            'score' => $score,
            'is_beaten' => $isBeaten,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);

        Database::query(
            "UPDATE challenges SET challenger_count = challenger_count + 1 WHERE id = :id",
            [':id' => $challengeId]
        );

        $priorBestScore = 0;
        if (!empty($challenge['best_attempt_id'])) {
            $currentBest = Database::selectOne(
                "SELECT score FROM challenge_attempts WHERE id = :id",
                [':id' => $challenge['best_attempt_id']]
            );
            if ($currentBest) {
                $priorBestScore = (int) $currentBest['score'];
            }
        }

        if ($score > $priorBestScore) {
            Database::update('challenges', [
                'best_attempt_id' => $attemptId,
            ], 'id = :id', [':id' => $challengeId]);
        }

        return $attemptId;
    }
}
