<?php

declare(strict_types=1);

namespace App\Core;

class Session
{
    private static ?self $instance = null;
    private bool $started = false;

    private function __construct()
    {
        $this->start();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return;
        }

        if (headers_sent() || php_sapi_name() === 'cli') {
            if (!isset($_SESSION)) {
                $_SESSION = [];
            }
            $this->started = true;
            $this->ageFlashData();
            return;
        }

        $lifetime = (int) config('security.session.lifetime', 7200);
        $secure = (bool) config('security.session.secure', false);
        $sameSite = (string) config('security.session.samesite', 'Strict');
        $sessionName = (string) config('security.session.name', 'voidstrike_session');

        session_name($sessionName);

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $sameSite,
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        session_start();
        $this->started = true;

        $this->ageFlashData();
    }

    public function regenerate(bool $deleteOldSession = true): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return session_regenerate_id($deleteOldSession);
        }
        return false;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash']['new'][$key] = $value;
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        return $_SESSION['_flash']['old'][$key] ?? $_SESSION['_flash']['new'][$key] ?? $default;
    }

    public function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash']['old'][$key]) || isset($_SESSION['_flash']['new'][$key]);
    }

    public function flashInput(array $data): void
    {
        // Don't flash sensitive fields like password
        unset($data['password'], $data['password_confirmation'], $data['_csrf']);
        $this->flash('_old_input', $data);
    }

    public function getOldInput(string $key, mixed $default = null): mixed
    {
        $old = $this->getFlash('_old_input', []);
        return $old[$key] ?? $default;
    }

    private function ageFlashData(): void
    {
        // Move new flash items to old, discard old
        $old = $_SESSION['_flash']['new'] ?? [];
        $_SESSION['_flash']['old'] = $old;
        $_SESSION['_flash']['new'] = [];
    }

    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            session_destroy();
            $this->started = false;
        }
    }
}
