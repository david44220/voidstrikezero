<?php

declare(strict_types=1);

namespace App\Admin;

use App\Core\Database;

class AuditLogger
{
    public static function log(
        ?int $adminId,
        string $action,
        string $targetType,
        string $targetId,
        array $details = [],
        string $ipAddress = '127.0.0.1'
    ): string {
        $id = 'audit_' . bin2hex(random_bytes(12));
        $now = gmdate('Y-m-d H:i:s');

        try {
            Database::insert('admin_audit_logs', [
                'id' => $id,
                'admin_id' => $adminId,
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'details' => json_encode($details),
                'ip_address' => $ipAddress,
                'created_at' => $now,
            ]);
        } catch (\Throwable) {
            // Failsafe: log to file if database write fails
        }

        // Also append to storage/logs/audit.log
        $logLine = sprintf(
            "[%s] [%s] AdminID:%s Action:%s Target:%s/%s IP:%s Details:%s\n",
            $now,
            $id,
            $adminId ?? 'SYSTEM',
            $action,
            $targetType,
            $targetId,
            $ipAddress,
            json_encode($details)
        );

        $logPath = dirname(__DIR__, 2) . '/storage/logs/audit.log';
        @file_put_contents($logPath, $logLine, FILE_APPEND | LOCK_EX);

        return $id;
    }

    public static function getRecent(int $limit = 50): array
    {
        return Database::select(
            "SELECT a.*, u.username as admin_username 
             FROM admin_audit_logs a 
             LEFT JOIN users u ON u.id = a.admin_id 
             ORDER BY a.created_at DESC LIMIT :lim",
            [':lim' => $limit]
        );
    }
}
