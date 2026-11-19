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

    /**
     * Schritt 1 der Anmeldung: Prüft E-Mail & Passwort.
     * Gibt zurück:
     * - 'ok'   => Vollständig eingeloggt (kein 2FA aktiv oder per FTP-Notfalldatei zurückgesetzt)
     * - '2fa'  => Passwort korrekt, 2FA-Code erforderlich (pending_2fa_user_id in Session gesetzt)
     * - 'fail' => Ungültige Zugangsdaten
     */
    public static function attemptLogin(string $email, string $password): string
    {
        self::initSession();
        $table = Database::table('users');
        $user = Database::fetchOne(
            "SELECT * FROM {$table} WHERE email = :email LIMIT 1",
            ['email' => trim($email)]
        );

        if (!$user || !(bool)$user['is_active']) {
            return 'fail';
        }

        if (!password_verify($password, (string)$user['password_hash'])) {
            return 'fail';
        }

        // Prüfen, ob per FTP eine Notfall-Reset-Datei (storage/config/reset_2fa.txt) hinterlegt wurde
        if ((int)($user['totp_enabled'] ?? 0) === 1 && Totp::checkFtpEmergencyReset((string)$user['email'])) {
            Database::query(
                "UPDATE {$table} SET totp_enabled = 0, totp_secret = NULL, totp_backup_codes = NULL WHERE id = :id",
                ['id' => (int)$user['id']]
            );
            $user['totp_enabled'] = 0;
            $_SESSION['flash_success'] = '2FA wurde über die FTP-Notfalldatei (reset_2fa.txt) zurückgesetzt und die Datei automatisch gelöscht.';
        }

        if ((int)($user['totp_enabled'] ?? 0) === 1 && !empty($user['totp_secret'])) {
            $_SESSION['pending_2fa_user_id'] = (int)$user['id'];
            return '2fa';
        }

        self::completeLoginSession($user);
        return 'ok';
    }

    /**
     * Schritt 2 der Anmeldung: Prüft den 6-stelligen 2FA-App-Code oder einen 8-stelligen Einmal-Backup-Code.
     */
    public static function verifyPending2fa(string $code): bool
    {
        self::initSession();
        $pendingId = (int)($_SESSION['pending_2fa_user_id'] ?? 0);
        if ($pendingId <= 0) {
            return false;
        }

        $table = Database::table('users');
        $user = Database::fetchOne("SELECT * FROM {$table} WHERE id = :id AND is_active = 1 LIMIT 1", ['id' => $pendingId]);
        if (!$user || (int)($user['totp_enabled'] ?? 0) !== 1) {
            return false;
        }

        $secretEnc = (string)($user['totp_secret'] ?? '');
        $secret = Crypto::isEncrypted($secretEnc) ? Crypto::decrypt($secretEnc) : $secretEnc;

        // 1. Regulären 6-stelligen TOTP-App-Code prüfen
        if (Totp::verifyCode($secret, $code)) {
            unset($_SESSION['pending_2fa_user_id']);
            self::completeLoginSession($user);
            return true;
        }

        // 2. Einmal-Notfall-Backup-Code prüfen
        $updatedBackups = Totp::verifyAndConsumeBackupCode($code, $user['totp_backup_codes'] ?? null);
        if ($updatedBackups !== null) {
            Database::query("UPDATE {$table} SET totp_backup_codes = :bc WHERE id = :id", [
                'bc' => $updatedBackups,
                'id' => (int)$user['id'],
            ]);
            unset($_SESSION['pending_2fa_user_id']);
            self::completeLoginSession($user);
            $_SESSION['flash_success'] = 'Anmeldung über einen Einmal-Notfallcode erfolgreich. Dieser Notfallcode wurde nun verbraucht.';
            return true;
        }

        return false;
    }

    public static function hasPending2fa(): bool
    {
        self::initSession();
        return !empty($_SESSION['pending_2fa_user_id']);
    }

    private static function completeLoginSession(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']    = (int)$user['id'];
        $_SESSION['user_email'] = (string)$user['email'];
        $_SESSION['user_name']  = (string)$user['name'];
        $_SESSION['user_role']  = (string)$user['role']; // 'admin' oder 'orga' (Anzeigename: SL)
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

    public static function roleLabel(?string $role = null): string
    {
        $r = $role ?? ($_SESSION['user_role'] ?? 'orga');
        return $r === 'admin' ? 'ADMIN' : 'SL';
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
