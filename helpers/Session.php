<?php

/**
 * Session Helper - Surat Digital TVRI Jawa Timur
 */

if (!defined('CSRF_TOKEN_NAME')) {
    define('CSRF_TOKEN_NAME', '_csrf_token');
}
if (!defined('SESSION_REGENERATE_INTERVAL')) {
    define('SESSION_REGENERATE_INTERVAL', 1800);
}

class Session
{
    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['_last_regenerated']) || 
            (time() - $_SESSION['_last_regenerated']) > SESSION_REGENERATE_INTERVAL) {
            session_regenerate_id(true);
            $_SESSION['_last_regenerated'] = time();
        }
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    public static function setFlash(string $type, string $message): void
    {
        $_SESSION['_flash'][$type] = $message;
    }

    public static function getFlash(?string $type = null): mixed
    {
        if ($type !== null) {
            $msg = $_SESSION['_flash'][$type] ?? null;
            if ($msg !== null) {
                unset($_SESSION['_flash'][$type]);
            }
            return $msg;
        }

        $flash = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flash;
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public static function user(): ?array
    {
        if (!self::isLoggedIn()) {
            return null;
        }
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? '',
            'email' => $_SESSION['user_email'] ?? '',
            'username' => $_SESSION['user_username'] ?? '',
            'avatar' => $_SESSION['user_avatar'] ?? null,
            'role' => $_SESSION['user_role'] ?? 'admin',
        ];
    }

    public static function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION[CSRF_TOKEN_NAME] = $token;
        $_SESSION[CSRF_TOKEN_NAME . '_time'] = time();
        return $token;
    }

    public static function validateCsrfToken(string $token): bool
    {
        if (empty($token) || !isset($_SESSION[CSRF_TOKEN_NAME])) {
            return false;
        }
        $storedToken = $_SESSION[CSRF_TOKEN_NAME];
        if (hash_equals($storedToken, $token)) {
            return true;
        }
        return false;
    }

    public static function csrfToken(): string
    {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            return self::generateCsrfToken();
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . self::csrfToken() . '">';
    }
}
