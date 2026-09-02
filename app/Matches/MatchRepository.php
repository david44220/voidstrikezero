<?php

declare(strict_types=1);

namespace App\Matches;

use App\Admin\AuditLogger;
use App\Core\Database;

class MatchRepository
{
    public static function find(string $id): ?array
    {
        return Database::selectOne(
            "SELECT m.*, u.username, u.display_name, u.avatar_url, u.level as user_level 
             FROM matches m 
             LEFT JOIN users u ON u.id = m.user_id 
             WHERE m.id = :id",
            [':id' => $id]
        );
    }

    public static function getGlobalLeaderboard(
        int $limit = 50,
        ?string $vehicle = null,
        ?string $arena = null,
        ?string $search = null
    ): array {
        $sql = "SELECT m.*, u.username, u.display_name, u.avatar_url, u.level as user_level 
                FROM matches m 
                INNER JOIN users u ON u.id = m.user_id 
                WHERE m.status = 'completed'";
        $params = [];

        if ($vehicle) {
            $sql .= " AND m.vehicle_class = :veh";
            $params[':veh'] = $vehicle;
        }
        if ($arena) {
            $sql .= " AND m.arena_id = :arena";
            $params[':arena'] = $arena;
        }
        if ($search) {
            $sql .= " AND (LOWER(u.username) LIKE :s OR LOWER(u.display_name) LIKE :s)";
            $params[':s'] = '%' . strtolower($search) . '%';
        }

        $sql .= " ORDER BY m.score DESC, m.finished_at ASC LIMIT :lim";
        $params[':lim'] = $limit;

        return Database::select($sql, $params);
    }

    public static function getWeeklyLeaderboard(
        int $limit = 50,
        ?string $vehicle = null,
        ?string $arena = null,
        ?string $search = null
    ): array {
        $sql = "SELECT m.*, u.username, u.display_name, u.avatar_url, u.level as user_level 
                FROM matches m 
                INNER JOIN users u ON u.id = m.user_id 
                WHERE m.status = 'completed' 
                  AND m.finished_at >= (CURRENT_TIMESTAMP - INTERVAL '7 days')";
        $params = [];

        if ($vehicle) {
            $sql .= " AND m.vehicle_class = :veh";
            $params[':veh'] = $vehicle;
        }
        if ($arena) {
            $sql .= " AND m.arena_id = :arena";
            $params[':arena'] = $arena;
        }
        if ($search) {
            $sql .= " AND (LOWER(u.username) LIKE :s OR LOWER(u.display_name) LIKE :s)";
            $params[':s'] = '%' . strtolower($search) . '%';
        }

        $sql .= " ORDER BY m.score DESC, m.finished_at ASC LIMIT :lim";
        $params[':lim'] = $limit;

        return Database::select($sql, $params);
    }

    public static function getUserRank(int $userId): ?int
    {
        // Calculate pilot's highest score rank
        $pb = self::getPersonalBest($userId);
        if (!$pb) {
            return null;
        }

        $rank = Database::selectValue(
            "SELECT COUNT(*) + 1 
             FROM matches 
             WHERE status = 'completed' AND score > :pb_score",
            [':pb_score' => $pb['score']]
        );

        return (int) $rank;
    }

    public static function getPersonalBest(int $userId): ?array
    {
        return Database::selectOne(
            "SELECT * FROM matches 
             WHERE user_id = :uid AND status = 'completed' 
             ORDER BY score DESC, finished_at ASC LIMIT 1",
            [':uid' => $userId]
        );
    }

    public static function getUserMatchHistory(int $userId, int $limit = 20): array
    {
        return Database::select(
            "SELECT * FROM matches 
             WHERE user_id = :uid 
             ORDER BY finished_at DESC LIMIT :lim",
            [':uid' => $userId, ':lim' => $limit]
        );
    }

    public static function getUserStats(int $userId): array
    {
        $stats = Database::selectOne(
            "SELECT 
                COUNT(*) as total_matches,
                COUNT(CASE WHEN waves_cleared >= 3 THEN 1 END) as wins,
                COUNT(CASE WHEN waves_cleared < 3 THEN 1 END) as losses,
                COALESCE(MAX(score), 0) as high_score,
                COALESCE(SUM(kills), 0) as total_kills,
                COALESCE(AVG(accuracy), 0.0) as avg_accuracy,
                COALESCE(MAX(combo_max), 0) as max_combo,
                COALESCE(SUM(duration_seconds), 0) as total_combat_seconds
             FROM matches 
             WHERE user_id = :uid AND status = 'completed'",
            [':uid' => $userId]
        );

        $totalMatches = (int) ($stats['total_matches'] ?? 0);
        $wins = (int) ($stats['wins'] ?? 0);
        $winRate = $totalMatches > 0 ? round(($wins / $totalMatches) * 100, 1) : 0.0;

        return [
            'total_matches' => $totalMatches,
            'wins' => $wins,
            'losses' => (int) ($stats['losses'] ?? 0),
            'win_rate' => $winRate,
            'high_score' => (int) ($stats['high_score'] ?? 0),
            'total_kills' => (int) ($stats['total_kills'] ?? 0),
            'avg_accuracy' => round((float) ($stats['avg_accuracy'] ?? 0.0), 1),
            'max_combo' => (int) ($stats['max_combo'] ?? 0),
            'total_combat_seconds' => (int) ($stats['total_combat_seconds'] ?? 0),
        ];
    }

    public static function getFlaggedMatches(int $limit = 50): array
    {
        return Database::select(
            "SELECT m.*, u.username, u.display_name, u.email 
             FROM matches m 
             LEFT JOIN users u ON u.id = m.user_id 
             WHERE m.status IN ('flagged', 'invalidated') 
             ORDER BY m.finished_at DESC LIMIT :lim",
            [':lim' => $limit]
        );
    }

    public static function invalidateMatch(string $id, int $adminId, string $reason, string $ip): bool
    {
        $match = self::find($id);
        if (!$match) {
            return false;
        }

        Database::update(
            'matches',
            ['status' => 'invalidated'],
            'id = :id',
            [':id' => $id]
        );

        AuditLogger::log(
            $adminId,
            'invalidate_match',
            'match',
            $id,
            ['reason' => $reason, 'original_score' => $match['score'], 'pilot' => $match['username']],
            $ip
        );

        return true;
    }
}
