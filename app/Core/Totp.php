<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Lokale Zwei-Faktor-Authentifizierung (TOTP nach RFC 6238) ohne externe Dienste
 * inkl. Einmal-Wiederherstellungscodes (Backup-Codes) und FTP-Notfall-Reset.
 */
class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Erzeugt ein zufälliges 160-Bit Base32-Geheimnis (32 Zeichen).
     */
    public static function generateSecret(int $length = 32): string
    {
        $bytes = random_bytes($length);
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::ALPHABET[ord($bytes[$i]) & 31];
        }
        return $secret;
    }

    /**
     * Prüft einen 6-stelligen TOTP-Code (erlaubt +/- 1 Zeitfenster à 30 Sek. für Uhrzeitabweichungen).
     */
    public static function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        $cleanCode = preg_replace('/\s+/', '', $code);
        if (!preg_match('/^\d{6}$/', (string)$cleanCode)) {
            return false;
        }
        $timeSlice = (int)floor(time() / 30);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::getCode($secret, $timeSlice + $i), $cleanCode)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Berechnet den 6-stelligen TOTP-Code für einen bestimmten 30-Sekunden-Zeitschlitz.
     */
    public static function getCode(string $secret, ?int $timeSlice = null): string
    {
        if ($timeSlice === null) {
            $timeSlice = (int)floor(time() / 30);
        }
        $secretKey = self::base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hm = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord(substr($hm, -1)) & 0x0F;
        $hashpart = substr($hm, $offset, 4);
        $value = unpack('N', $hashpart)[1] & 0x7FFFFFFF;
        return str_pad((string)($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Erzeugt die otpauth:// URI für Authenticator-Apps (Google Authenticator, Aegis, Bitwarden, Apple Passwörter etc.).
     */
    public static function getProvisioningUri(string $secret, string $email, string $issuer): string
    {
        $cleanIssuer = str_replace(':', '', trim($issuer)) ?: 'ConManagement';
        $label = rawurlencode($cleanIssuer . ':' . trim($email));
        return sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            $label,
            rawurlencode($secret),
            rawurlencode($cleanIssuer)
        );
    }

    /**
     * Erzeugt 8 zufällige Einmal-Notfall-Codes (Format: XXXX-XXXX).
     * Gibt ['plain' => [...], 'hashes_json' => '...'] zurück.
     */
    public static function generateBackupCodes(int $count = 8): array
    {
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $plain = [];
        $hashes = [];

        for ($i = 0; $i < $count; $i++) {
            $code = '';
            for ($j = 0; $j < 8; $j++) {
                if ($j === 4) {
                    $code .= '-';
                }
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $plain[] = $code;
            $normalized = strtoupper(str_replace('-', '', $code));
            $hashes[] = password_hash($normalized, PASSWORD_DEFAULT);
        }

        return [
            'plain'       => $plain,
            'hashes_json' => json_encode($hashes),
        ];
    }

    /**
     * Prüft, ob ein eingegebener Code ein gültiger Backup-Code ist.
     * Falls ja, wird dieser Backup-Code verbraucht und das aktualisierte JSON zurückgegeben.
     */
    public static function verifyAndConsumeBackupCode(string $inputCode, ?string $hashesJson): ?string
    {
        if (!$hashesJson) {
            return null;
        }
        $normalized = strtoupper((string)preg_replace('/[^a-zA-Z0-9]/', '', $inputCode));
        if (strlen($normalized) !== 8) {
            return null;
        }
        $hashes = json_decode($hashesJson, true);
        if (!is_array($hashes)) {
            return null;
        }

        foreach ($hashes as $idx => $hash) {
            if (password_verify($normalized, (string)$hash)) {
                unset($hashes[$idx]);
                return json_encode(array_values($hashes));
            }
        }
        return null;
    }

    /**
     * Prüft, ob der Server-Inhaber per FTP eine Datei storage/config/reset_2fa.txt mit der E-Mail-Adresse
     * hinterlegt hat, um 2FA im Notfall (Handy + Backup-Codes verloren) zurückzusetzen.
     */
    public static function checkFtpEmergencyReset(string $email): bool
    {
        $resetFile = dirname(__DIR__, 2) . '/storage/config/reset_2fa.txt';
        if (!is_file($resetFile)) {
            return false;
        }
        $content = strtolower(trim((string)file_get_contents($resetFile)));
        $target  = strtolower(trim($email));
        if ($target !== '' && ($content === $target || $content === 'all')) {
            @unlink($resetFile);
            return true;
        }
        return false;
    }

    private static function base32Decode(string $secret): string
    {
        $secret = strtoupper((string)preg_replace('/[^A-Z2-7]/', '', $secret));
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        for ($i = 0, $len = strlen($secret); $i < $len; $i++) {
            $val = strpos(self::ALPHABET, $secret[$i]);
            if ($val === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }
        return $output;
    }
}
