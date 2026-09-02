<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Session;
use App\Users\User;

class AuthService
{
    private const SESSION_USER_ID = 'auth_user_id';
    private static ?User $cachedUser = null;

    public static function hashPassword(string $password): string
    {
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($password, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 2,
            ]);
        }
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function login(User $user, bool $remember = false): void
    {
        $session = Session::getInstance();
        $session->regenerate(true);
        $session->set(self::SESSION_USER_ID, $user->id);
        self::$cachedUser = $user;
    }

    public static function logout(): void
    {
        self::$cachedUser = null;
        Session::getInstance()->destroy();
    }

    public static function user(): ?User
    {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        $userId = Session::getInstance()->get(self::SESSION_USER_ID);
        if (!$userId) {
            return null;
        }

        $user = User::find((int) $userId);
        if (!$user || !$user->isActive()) {
            self::logout();
            return null;
        }

        self::$cachedUser = $user;
        return $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        return self::user()?->id;
    }

    public static function setUserForTesting(?User $user): void
    {
        self::$cachedUser = $user;
    }
}
