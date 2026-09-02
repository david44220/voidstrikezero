<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Core\Database;

class NotificationService
{
    public static function create(
        int $userId,
        string $type,
        string $titleEn,
        string $titleFr,
        string $messageEn,
        string $messageFr,
        array $data = []
    ): string {
        $id = 'notif_' . bin2hex(random_bytes(12));
        Database::insert('notifications', [
            'id' => $id,
            'user_id' => $userId,
            'type' => $type,
            'title_en' => $titleEn,
            'title_fr' => $titleFr,
            'message_en' => $messageEn,
            'message_fr' => $messageFr,
            'data' => json_encode($data),
            'is_read' => 0,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);

        return $id;
    }

    public static function unreadCount(int $userId): int
    {
        return (int) Database::selectValue(
            "SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = FALSE",
            [':uid' => $userId]
        );
    }

    public static function listForUser(int $userId, int $limit = 20): array
    {
        return Database::select(
            "SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT :lim",
            [':uid' => $userId, ':lim' => $limit]
        );
    }

    public static function markAllAsRead(int $userId): int
    {
        return Database::update(
            'notifications',
            ['is_read' => 1],
            'user_id = :uid AND is_read = FALSE',
            [':uid' => $userId]
        );
    }

    public static function markAsRead(string $id, int $userId): bool
    {
        return Database::update(
            'notifications',
            ['is_read' => 1],
            'id = :id AND user_id = :uid',
            [':id' => $id, ':uid' => $userId]
        ) > 0;
    }
}
