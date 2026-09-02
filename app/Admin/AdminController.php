<?php

declare(strict_types=1);

namespace App\Admin;

use App\Auth\AuthService;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Matches\MatchRepository;
use App\Users\User;

class AdminController
{
    public function dashboard(Request $request): Response
    {
        $stats = [
            'users_count' => (int) Database::selectValue("SELECT COUNT(*) FROM users"),
            'matches_count' => (int) Database::selectValue("SELECT COUNT(*) FROM matches"),
            'flagged_count' => (int) Database::selectValue("SELECT COUNT(*) FROM matches WHERE status IN ('flagged', 'invalidated')"),
            'challenges_count' => (int) Database::selectValue("SELECT COUNT(*) FROM challenges WHERE status = 'active'"),
        ];

        $flagged = MatchRepository::getFlaggedMatches(8);
        $recentAudit = AuditLogger::getRecent(10);

        return view('admin.dashboard', [
            'stats' => $stats,
            'flagged' => $flagged,
            'audit_logs' => $recentAudit,
            'title' => __('admin.title'),
        ], 'layouts.admin');
    }

    public function users(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $sql = "SELECT * FROM users";
        $params = [];

        if ($search !== '') {
            $sql .= " WHERE LOWER(username) LIKE :s OR LOWER(email) LIKE :s OR LOWER(display_name) LIKE :s";
            $params[':s'] = '%' . strtolower($search) . '%';
        }

        $sql .= " ORDER BY created_at DESC LIMIT 50";
        $users = Database::select($sql, $params);

        return view('admin.users', [
            'users' => $users,
            'search' => $search,
            'title' => __('admin.manage_users'),
        ], 'layouts.admin');
    }

    public function matches(Request $request): Response
    {
        $status = $request->query('status', 'flagged');
        $sql = "SELECT m.*, u.username, u.display_name 
                FROM matches m 
                LEFT JOIN users u ON u.id = m.user_id";
        $params = [];

        if ($status === 'flagged') {
            $sql .= " WHERE m.status IN ('flagged', 'invalidated')";
        } elseif ($status === 'completed') {
            $sql .= " WHERE m.status = 'completed'";
        }

        $sql .= " ORDER BY m.finished_at DESC LIMIT 50";
        $matches = Database::select($sql, $params);

        return view('admin.matches', [
            'matches' => $matches,
            'status' => $status,
            'title' => __('admin.manage_matches'),
        ], 'layouts.admin');
    }

    public function challenges(Request $request): Response
    {
        $challenges = Database::select(
            "SELECT c.*, u.username as creator_username, u.display_name as creator_name 
             FROM challenges c 
             INNER JOIN users u ON u.id = c.creator_id 
             ORDER BY c.created_at DESC LIMIT 50"
        );

        return view('admin.challenges', [
            'challenges' => $challenges,
            'title' => 'Admin // Challenges Moderation',
        ], 'layouts.admin');
    }

    public function audit(Request $request): Response
    {
        $logs = AuditLogger::getRecent(100);
        return view('admin.audit', [
            'logs' => $logs,
            'title' => __('admin.audit_logs'),
        ], 'layouts.admin');
    }

    public function banUser(Request $request): Response
    {
        $userId = (int) $request->input('user_id');
        $user = User::find($userId);

        if ($user && !$user->isAdmin()) {
            $user->status = 'banned';
            $user->save();

            AuditLogger::log(
                AuthService::id(),
                'ban_user',
                'user',
                (string) $userId,
                ['username' => $user->username],
                $request->ip()
            );

            flash('success', "Callsign {$user->username} has been decommissioned (banned).");
        } else {
            flash('error', "Cannot ban administrator or user not found.");
        }

        return redirect('/admin/users');
    }

    public function unbanUser(Request $request): Response
    {
        $userId = (int) $request->input('user_id');
        $user = User::find($userId);

        if ($user) {
            $user->status = 'active';
            $user->save();

            AuditLogger::log(
                AuthService::id(),
                'unban_user',
                'user',
                (string) $userId,
                ['username' => $user->username],
                $request->ip()
            );

            flash('success', "Callsign {$user->username} has been reactivated.");
        }

        return redirect('/admin/users');
    }

    public function invalidateMatch(Request $request): Response
    {
        $matchId = (string) $request->input('match_id');
        $reason = trim((string) $request->input('reason', 'Administrative invalidation via control nexus'));

        $success = MatchRepository::invalidateMatch($matchId, (int) AuthService::id(), $reason, $request->ip());

        if ($success) {
            flash('success', "Match {$matchId} was invalidated and purged from rankings.");
        } else {
            flash('error', "Match not found or already purged.");
        }

        return redirect('/admin/matches');
    }

    public function settings(Request $request): Response
    {
        $settingsRows = Database::select("SELECT * FROM system_settings");
        $settings = [];
        foreach ($settingsRows as $row) {
            $settings[$row['key']] = $row['value'];
        }

        return view('admin.settings', [
            'settings' => $settings,
            'title' => 'Admin // Platform Settings',
        ], 'layouts.admin');
    }

    public function updateSettings(Request $request): Response
    {
        $seasonTitle = trim((string) $request->input('season_title', 'Season 1 // Void Genesis'));
        $seasonEndDate = trim((string) $request->input('season_end_date', '2026-12-31'));
        $maintenance = (string) $request->input('maintenance_mode', '0');
        $maxScoreRate = (string) $request->input('max_score_per_sec', '280');
        $clockDrift = (string) $request->input('clock_drift_tol', '15');

        $updates = [
            'season_title' => $seasonTitle,
            'season_end_date' => $seasonEndDate,
            'maintenance_mode' => $maintenance,
            'max_score_per_sec' => $maxScoreRate,
            'clock_drift_tol' => $clockDrift,
        ];

        foreach ($updates as $key => $val) {
            Database::query(
                "INSERT INTO system_settings (key, value, updated_at) 
                 VALUES (:k, :v, CURRENT_TIMESTAMP) 
                 ON CONFLICT (key) DO UPDATE SET value = :v, updated_at = CURRENT_TIMESTAMP",
                [':k' => $key, ':v' => $val]
            );
        }

        AuditLogger::log(
            AuthService::id(),
            'update_system_settings',
            'settings',
            'global',
            $updates,
            $request->ip()
        );

        flash('success', 'Platform settings and season parameters committed.');
        return redirect('/admin/settings');
    }
}
