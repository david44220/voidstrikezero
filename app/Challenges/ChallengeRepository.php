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
        return Database::select(
            "SELECT c.*, u.username as creator_username, u.display_name as creator_name, u.avatar_url as creator_avatar,
                    ba.score as best_score, bu.username as best_username, bu.display_name as best_challenger_name
             FROM challenges c
             INNER JOIN users u ON u.id = c.creator_id
             LEFT JOIN challenge_attempts ba ON ba.id = c.best_attempt_id
             LEFT JOIN users bu ON bu.id = ba.user_id
             WHERE c.status = 'active'
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
}
