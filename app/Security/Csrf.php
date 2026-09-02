<?php

declare(strict_types=1);

namespace App\Security;

use App\Core\Session;

class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        $session = Session::getInstance();
        $token = $session->get(self::SESSION_KEY);

        if (!$token || !is_string($token)) {
            $token = bin2hex(random_bytes(32));
            $session->set(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public static function validate(?string $token): bool
    {
        if (empty($token)) {
            return false;
        }

        $sessionToken = Session::getInstance()->get(self::SESSION_KEY);
        if (!is_string($sessionToken) || empty($sessionToken)) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    public static function regenerate(): string
    {
        $session = Session::getInstance();
        $token = bin2hex(random_bytes(32));
        $session->set(self::SESSION_KEY, $token);
        return $token;
    }
}
