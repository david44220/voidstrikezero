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
}
