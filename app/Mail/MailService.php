<?php

declare(strict_types=1);

namespace App\Mail;

class MailService
{
    private static function getStorageDir(): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/mail';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    public static function sendPasswordReset(string $email, string $rawToken): bool
    {
        $appUrl = rtrim((string) config('app.url', 'http://127.0.0.1:8000'), '/');
        $resetUrl = "{$appUrl}/reset-password?token=" . urlencode($rawToken) . "&email=" . urlencode($email);
        $subject = "VOIDSTRIKE ARENA — Passcode Recovery Transmission";

        $body = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>{$subject}</title></head>
<body style="background:#060912; color:#cdd9e5; font-family:sans-serif; padding:20px;">
    <div style="max-width:600px; margin:0 auto; background:#0e1422; border:1px solid #00f0ff; border-radius:8px; padding:24px;">
        <h2 style="color:#00f0ff; margin-top:0;">VOIDSTRIKE DEFENSE GRID</h2>
        <p>A passcode reset request was dispatched for callsign: <strong>{$email}</strong>.</p>
        <p>Click the link below to commit a new security passcode. This transmission expires in 60 minutes.</p>
        <p style="margin:24px 0;">
            <a href="{$resetUrl}" style="background:#00f0ff; color:#060912; padding:12px 24px; text-decoration:none; font-weight:bold; border-radius:4px; display:inline-block;">RECOVER PASSCODE</a>
        </p>
        <p style="color:#6c7d93; font-size:12px;">If you did not initiate this directive, disregard this transmission.</p>
    </div>
</body>
</html>
HTML;

        return self::deliver($email, $subject, $body, 'reset');
    }

    public static function sendEmailVerification(string $email, string $rawToken): bool
    {
        $appUrl = rtrim((string) config('app.url', 'http://127.0.0.1:8000'), '/');
        $verifyUrl = "{$appUrl}/verify-email?token=" . urlencode($rawToken) . "&email=" . urlencode($email);
        $subject = "VOIDSTRIKE ARENA — Pilot Clearance Verification";

        $body = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>{$subject}</title></head>
<body style="background:#060912; color:#cdd9e5; font-family:sans-serif; padding:20px;">
    <div style="max-width:600px; margin:0 auto; background:#0e1422; border:1px solid #00f0ff; border-radius:8px; padding:24px;">
        <h2 style="color:#00f0ff; margin-top:0;">PILOT CLEARANCE INITIATIVE</h2>
        <p>Welcome to the Voidstrike Network, Pilot. Confirm your neural-link communications email: <strong>{$email}</strong>.</p>
        <p style="margin:24px 0;">
            <a href="{$verifyUrl}" style="background:#00f0ff; color:#060912; padding:12px 24px; text-decoration:none; font-weight:bold; border-radius:4px; display:inline-block;">VERIFY CLEARANCE</a>
        </p>
        <p style="color:#6c7d93; font-size:12px;">This single-use clearance key expires in 24 hours.</p>
    </div>
</body>
</html>
HTML;

        return self::deliver($email, $subject, $body, 'verify');
    }

    private static function deliver(string $email, string $subject, string $body, string $type): bool
    {
        $env = (string) config('app.env', 'production');

        // Safe development/testing mail delivery spool
        if ($env !== 'production') {
            $dir = self::getStorageDir();
            $filename = sprintf('%s/%s_%s_%s.html', $dir, date('Ymd_His'), substr(md5($email), 0, 8), $type);
            file_put_contents($filename, $body);
            return true;
        }

        // Production delivery via PHP mail()
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: VOIDSTRIKE ARENA <no-reply@voidstrike.io>',
            'X-Mailer: Voidstrike/1.0',
        ];

        return @mail($email, $subject, $body, implode("\r\n", $headers));
    }
}
