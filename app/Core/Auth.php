<?php
declare(strict_types=1);

namespace App\Core;

class Auth
{
    public static function initSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function csrfToken(): string
    {
        self::initSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION['csrf_token'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . e(self::csrfToken()) . '">';
    }

    public static function verifyCsrf(?string $token = null): bool
    {
        self::initSession();
        $submitted = $token ?? ($_POST['csrf_token'] ?? '');
        $stored = $_SESSION['csrf_token'] ?? '';
        return is_string($submitted) && $stored !== '' && hash_equals($stored, $submitted);
    }

    public static function requireCsrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !self::verifyCsrf()) {
            http_response_code(403);
            die('Ungültiges Sicherheits-Token (CSRF). Bitte lade die Seite neu und versuche es erneut.');
        }
    }

    public static function login(string $email, string $password): bool
    {
        self::initSession();
        $table = Database::table('users');
        $user = Database::fetchOne(
            "SELECT id, email, password_hash, name, role, is_active FROM {$table} WHERE email = :email LIMIT 1",
            ['email' => trim($email)]
        );

        if (!$user || !(bool)$user['is_active']) {
            return false;
        }

        if (!password_verify($password, (string)$user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_email'] = (string)$user['email'];
        $_SESSION['user_name'] = (string)$user['name'];
        $_SESSION['user_role'] = (string)$user['role']; // 'admin' oder 'orga'
        return true;
    }

    public static function logout(): void
    {
        self::initSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        self::initSession();
        return !empty($_SESSION['user_id']);
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return [
            'id'    => (int)$_SESSION['user_id'],
            'email' => (string)($_SESSION['user_email'] ?? ''),
            'name'  => (string)($_SESSION['user_name'] ?? ''),
            'role'  => (string)($_SESSION['user_role'] ?? 'orga'),
        ];
    }

    public static function isAdmin(): bool
    {
        return self::check() && ($_SESSION['user_role'] ?? '') === 'admin';
    }

    public static function isOrga(): bool
    {
        return self::check() && in_array($_SESSION['user_role'] ?? '', ['admin', 'orga'], true);
    }
}
