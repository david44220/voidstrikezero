<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Auth\AuthService;
use App\Core\Request;
use App\Core\Response;

class NotificationController
{
    public function index(Request $request): Response
    {
        $user = AuthService::user();
        if (!$user) {
            return redirect('/login');
        }

        $notifications = NotificationService::listForUser($user->id, 50);

        return view('dashboard.notifications', [
            'user' => $user,
            'notifications' => $notifications,
            'title' => 'Pilot Notifications',
        ]);
    }

    public function markAllRead(Request $request): Response
    {
        $user = AuthService::user();
        if ($user) {
            NotificationService::markAllAsRead($user->id);
            flash('success', 'All notifications cleared.');
        }
        return redirect('/notifications');
    }
}
