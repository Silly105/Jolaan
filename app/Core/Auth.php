<?php
declare(strict_types=1);

namespace App\Core;

class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    private static ?array $verifiedUserCache = null;
    private static bool $userCheckedThisRequest = false;

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

    private static function clientIpHash(): string
    {
        $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
        return Crypto::blindIndex('ip:' . $ip);
    }

    /**
     * Prüft, ob für die aktuelle IP-Adresse oder Kennung bereits >= 5 Fehlversuche in den letzten 15 Minuten vorliegen.
     */
    public static function isRateLimited(string $identifier = ''): bool
    {
        try {
            $tAttempts = Database::table('login_attempts');
            $cutoff = date('Y-m-d H:i:s', time() - (self::LOCKOUT_MINUTES * 60));
            Database::query("DELETE FROM {$tAttempts} WHERE attempted_at < :cut", ['cut' => $cutoff]);

            $ipHash = self::clientIpHash();
            $ipCount = (int)Database::fetchScalar(
                "SELECT COUNT(*) FROM {$tAttempts} WHERE ip_hash = :iph AND attempted_at >= :cut",
                ['iph' => $ipHash, 'cut' => $cutoff]
            );
            if ($ipCount >= self::MAX_ATTEMPTS) {
                return true;
            }

            $normIdent = mb_strtolower(trim($identifier), 'UTF-8');
            if ($normIdent !== '') {
                $idCount = (int)Database::fetchScalar(
                    "SELECT COUNT(*) FROM {$tAttempts} WHERE identifier = :ident AND attempted_at >= :cut",
                    ['ident' => $normIdent, 'cut' => $cutoff]
                );
                if ($idCount >= self::MAX_ATTEMPTS) {
                    return true;
                }
            }
        } catch (\Throwable) {
            // Falls Tabelle während eines laufenden Updates noch angelegt wird, nicht blockieren
        }
        return false;
    }

    private static function recordFailedAttempt(string $identifier = ''): void
    {
        try {
            $tAttempts = Database::table('login_attempts');
            Database::query(
                "INSERT INTO {$tAttempts} (ip_hash, identifier, attempted_at) VALUES (:iph, :ident, :now)",
                [
                    'iph'   => self::clientIpHash(),
                    'ident' => mb_strtolower(trim($identifier), 'UTF-8'),
                    'now'   => date('Y-m-d H:i:s'),
                ]
            );
        } catch (\Throwable) {
        }
    }

    private static function clearFailedAttempts(string $identifier = ''): void
    {
        try {
            $tAttempts = Database::table('login_attempts');
            $ipHash = self::clientIpHash();
            Database::query("DELETE FROM {$tAttempts} WHERE ip_hash = :iph", ['iph' => $ipHash]);
            $normIdent = mb_strtolower(trim($identifier), 'UTF-8');
            if ($normIdent !== '') {
                Database::query("DELETE FROM {$tAttempts} WHERE identifier = :ident", ['ident' => $normIdent]);
            }
        } catch (\Throwable) {
        }
    }

    /**
     * Schritt 1 der Anmeldung: Prüft E-Mail & Passwort.
     * Gibt zurück: 'ok', '2fa', 'locked' oder 'fail'.
     */
    public static function attemptLogin(string $email, string $password): string
    {
        self::initSession();
        $cleanEmail = trim($email);

        if (self::isRateLimited($cleanEmail)) {
            return 'locked';
        }

        $table = Database::table('users');
        $user = Database::fetchOne(
            "SELECT * FROM {$table} WHERE email = :email LIMIT 1",
            ['email' => $cleanEmail]
        );

        if (!$user || !(bool)$user['is_active'] || !password_verify($password, (string)$user['password_hash'])) {
            self::recordFailedAttempt($cleanEmail);
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
            $_SESSION['pending_2fa_fails'] = 0;
            return '2fa';
        }

        self::clearFailedAttempts($cleanEmail);
        self::completeLoginSession($user);
        return 'ok';
    }

    /**
     * Schritt 2 der Anmeldung: Prüft den 6-stelligen 2FA-App-Code oder einen 8-stelligen Einmal-Backup-Code.
     * Gibt 'ok', 'locked' oder 'fail' zurück.
     */
    public static function verifyPending2faStatus(string $code): string
    {
        self::initSession();
        $pendingId = (int)($_SESSION['pending_2fa_user_id'] ?? 0);
        if ($pendingId <= 0) {
            return 'fail';
        }

        $ident = '2fa_user_' . $pendingId;
        if (self::isRateLimited($ident)) {
            unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_fails']);
            return 'locked';
        }

        $table = Database::table('users');
        $user = Database::fetchOne("SELECT * FROM {$table} WHERE id = :id AND is_active = 1 LIMIT 1", ['id' => $pendingId]);
        if (!$user || (int)($user['totp_enabled'] ?? 0) !== 1) {
            unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_fails']);
            return 'fail';
        }

        $secretEnc = (string)($user['totp_secret'] ?? '');
        $secret = Crypto::isEncrypted($secretEnc) ? Crypto::decrypt($secretEnc) : $secretEnc;

        // 1. Regulären 6-stelligen TOTP-App-Code prüfen
        if (Totp::verifyCode($secret, $code)) {
            unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_fails']);
            self::clearFailedAttempts($ident);
            self::clearFailedAttempts((string)$user['email']);
            self::completeLoginSession($user);
            return 'ok';
        }

        // 2. Einmal-Notfall-Backup-Code prüfen
        $updatedBackups = Totp::verifyAndConsumeBackupCode($code, $user['totp_backup_codes'] ?? null);
        if ($updatedBackups !== null) {
            Database::query("UPDATE {$table} SET totp_backup_codes = :bc WHERE id = :id", [
                'bc' => $updatedBackups,
                'id' => (int)$user['id'],
            ]);
            unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_fails']);
            self::clearFailedAttempts($ident);
            self::clearFailedAttempts((string)$user['email']);
            self::completeLoginSession($user);
            $_SESSION['flash_success'] = 'Anmeldung über einen Einmal-Notfallcode erfolgreich. Dieser Notfallcode wurde nun verbraucht.';
            return 'ok';
        }

        self::recordFailedAttempt($ident);
        $_SESSION['pending_2fa_fails'] = (int)($_SESSION['pending_2fa_fails'] ?? 0) + 1;
        if ($_SESSION['pending_2fa_fails'] >= self::MAX_ATTEMPTS) {
            unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_fails']);
            return 'locked';
        }

        return 'fail';
    }

    public static function verifyPending2fa(string $code): bool
    {
        return self::verifyPending2faStatus($code) === 'ok';
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
        $_SESSION['user_role']  = (string)$user['role'];
        self::$userCheckedThisRequest = false;
        self::$verifiedUserCache = null;
    }

    public static function logout(): void
    {
        self::initSession();
        self::$verifiedUserCache = null;
        self::$userCheckedThisRequest = true;
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    /**
     * Prüft einmal pro Request live gegen die Datenbank, ob der eingeloggte Benutzer noch aktiv ist
     * und synchronisiert Rolle, Name und E-Mail in Echtzeit.
     */
    private static function loadLiveUser(): ?array
    {
        self::initSession();
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        if (self::$userCheckedThisRequest) {
            return self::$verifiedUserCache;
        }

        self::$userCheckedThisRequest = true;
        $uid = (int)$_SESSION['user_id'];

        try {
            $tUsers = Database::table('users');
            $dbUser = Database::fetchOne(
                "SELECT id, email, name, role, is_active FROM {$tUsers} WHERE id = :id LIMIT 1",
                ['id' => $uid]
            );
            if (!$dbUser || (int)$dbUser['is_active'] !== 1) {
                self::logout();
                return null;
            }
            $_SESSION['user_email'] = (string)$dbUser['email'];
            $_SESSION['user_name']  = (string)$dbUser['name'];
            $_SESSION['user_role']  = (string)$dbUser['role'];

            self::$verifiedUserCache = [
                'id'    => (int)$dbUser['id'],
                'email' => (string)$dbUser['email'],
                'name'  => (string)$dbUser['name'],
                'role'  => (string)$dbUser['role'],
            ];
            return self::$verifiedUserCache;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function check(): bool
    {
        return self::loadLiveUser() !== null;
    }

    public static function user(): ?array
    {
        return self::loadLiveUser();
    }

    public static function roleLabel(?string $role = null): string
    {
        $u = self::loadLiveUser();
        $r = $role ?? ($u['role'] ?? 'orga');
        return $r === 'admin' ? 'ADMIN' : 'SL';
    }

    public static function isAdmin(): bool
    {
        $u = self::loadLiveUser();
        return $u !== null && $u['role'] === 'admin';
    }

    public static function isOrga(): bool
    {
        $u = self::loadLiveUser();
        return $u !== null && in_array($u['role'], ['admin', 'orga'], true);
    }
}
