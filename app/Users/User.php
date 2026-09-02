<?php

declare(strict_types=1);

namespace App\Users;

use App\Core\Database;

class User
{
    public ?int $id = null;
    public string $username = '';
    public string $email = '';
    public string $password_hash = '';
    public string $display_name = '';
    public ?string $avatar_url = null;
    public string $role = 'player';
    public string $status = 'active';
    public ?string $email_verified_at = null;
    public int $xp = 0;
    public int $level = 1;
    public string $selected_vehicle = 'striker';
    public string $preferred_locale = 'en';
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key)) {
                if ($key === 'xp' || $key === 'level') {
                    $this->{$key} = (int) $value;
                } elseif ($key === 'id' && $value !== null) {
                    $this->{$key} = (int) $value;
                } else {
                    $this->{$key} = $value;
                }
            }
        }
    }

    public static function find(int $id): ?self
    {
        $row = Database::selectOne("SELECT * FROM users WHERE id = :id", [':id' => $id]);
        return $row ? new self($row) : null;
    }

    public static function findByEmail(string $email): ?self
    {
        $row = Database::selectOne("SELECT * FROM users WHERE LOWER(email) = LOWER(:email)", [':email' => trim($email)]);
        return $row ? new self($row) : null;
    }

    public static function findByUsername(string $username): ?self
    {
        $row = Database::selectOne("SELECT * FROM users WHERE LOWER(username) = LOWER(:username)", [':username' => trim($username)]);
        return $row ? new self($row) : null;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->password_hash);
    }

    public function save(): bool
    {
        $now = gmdate('Y-m-d H:i:s');
        $this->updated_at = $now;

        if ($this->id === null) {
            $this->created_at = $now;
            $data = [
                'username' => $this->username,
                'email' => $this->email,
                'password_hash' => $this->password_hash,
                'display_name' => $this->display_name ?: $this->username,
                'avatar_url' => $this->avatar_url,
                'role' => $this->role,
                'status' => $this->status,
                'email_verified_at' => $this->email_verified_at,
                'xp' => $this->xp,
                'level' => $this->level,
                'selected_vehicle' => $this->selected_vehicle,
                'preferred_locale' => $this->preferred_locale,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ];
            $id = Database::insert('users', $data, 'id');
            $this->id = (int) $id;
            return true;
        }

        $data = [
            'username' => $this->username,
            'email' => $this->email,
            'password_hash' => $this->password_hash,
            'display_name' => $this->display_name,
            'avatar_url' => $this->avatar_url,
            'role' => $this->role,
            'status' => $this->status,
            'email_verified_at' => $this->email_verified_at,
            'xp' => $this->xp,
            'level' => $this->level,
            'selected_vehicle' => $this->selected_vehicle,
            'preferred_locale' => $this->preferred_locale,
            'updated_at' => $this->updated_at,
        ];

        Database::update('users', $data, 'id = :user_id', [':user_id' => $this->id]);
        return true;
    }

    public function addXp(int $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $this->xp += $amount;
        $newLevel = self::calculateLevelForXp($this->xp);
        $leveledUp = ($newLevel > $this->level);
        $this->level = $newLevel;
        $this->save();

        return $leveledUp;
    }

    public static function calculateLevelForXp(int $xp): int
    {
        // Smooth progression curve: Level = 1 + floor(sqrt(xp / 100))
        return (int) (1 + floor(sqrt(max(0, $xp) / 100)));
    }

    public function calculateLevel(int $xp): int
    {
        return self::calculateLevelForXp($xp);
    }

    public static function xpRequiredForLevel(int $level): int
    {
        if ($level <= 1) {
            return 0;
        }
        return ($level - 1) * ($level - 1) * 100;
    }

    public function getNextLevelXp(): int
    {
        return self::xpRequiredForLevel($this->level + 1);
    }

    public function getCurrentLevelBaseXp(): int
    {
        return self::xpRequiredForLevel($this->level);
    }

    public function getLevelProgressPercentage(): float
    {
        $base = $this->getCurrentLevelBaseXp();
        $next = $this->getNextLevelXp();
        $diff = $next - $base;
        if ($diff <= 0) {
            return 100.0;
        }
        $currentInLevel = $this->xp - $base;
        return min(100.0, max(0.0, round(($currentInLevel / $diff) * 100, 1)));
    }

    public function getAvatarUrl(): string
    {
        if ($this->avatar_url && file_exists(dirname(__DIR__, 2) . '/public' . $this->avatar_url)) {
            return url($this->avatar_url);
        }

        // SVG generative procedural avatar based on username hash
        return "data:image/svg+xml;utf8," . rawurlencode($this->generateDefaultAvatarSvg());
    }

    private function generateDefaultAvatarSvg(): string
    {
        $hash = md5($this->username);
        $hue1 = hexdec(substr($hash, 0, 2)) % 360;
        $hue2 = ($hue1 + 60) % 360;
        $initial = strtoupper(substr($this->display_name ?: $this->username, 0, 1));

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100">
  <defs>
    <linearGradient id="g_{$hash}" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="hsl({$hue1}, 80%, 45%)" />
      <stop offset="100%" stop-color="hsl({$hue2}, 90%, 65%)" />
    </linearGradient>
  </defs>
  <rect width="100" height="100" rx="20" fill="url(#g_{$hash})" />
  <text x="50" y="62" font-family="'Inter', sans-serif" font-weight="bold" font-size="42" fill="#ffffff" text-anchor="middle">{$initial}</text>
</svg>
SVG;
    }
}
