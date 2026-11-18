<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * DSGVO-konforme AES-256-GCM Verschlüsselung für als "Sensible Daten" markierte Formularfelder.
 */
class Crypto
{
    private const CIPHER = 'aes-256-gcm';
    private const PREFIX = 'ENCv1:';

    private static function getKey(): string
    {
        $keyPath = Config::keyPath();
        if (!is_file($keyPath)) {
            throw new RuntimeException('Verschlüsselungsschlüssel (secret.key) nicht gefunden.');
        }
        $raw = trim((string)file_get_contents($keyPath));
        $key = base64_decode($raw, true);
        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException('Ungültiger Verschlüsselungsschlüssel.');
        }
        return $key;
    }

    public static function generateKeyFile(): string
    {
        $key = random_bytes(32);
        $encoded = base64_encode($key);
        $dir = dirname(Config::keyPath());
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents(Config::keyPath(), $encoded, LOCK_EX);
        return $encoded;
    }

    public static function encrypt(string $plaintext): string
    {
        if ($plaintext === '') {
            return '';
        }
        $key = self::getKey();
        $ivLen = openssl_cipher_iv_length(self::CIPHER);
        $iv = random_bytes($ivLen);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        if ($ciphertext === false) {
            throw new RuntimeException('Verschlüsselung fehlgeschlagen.');
        }
        return self::PREFIX . base64_encode($iv . $tag . $ciphertext);
    }

    public static function decrypt(string $payload): string
    {
        if ($payload === '' || !str_starts_with($payload, self::PREFIX)) {
            return $payload;
        }
        $key = self::getKey();
        $decoded = base64_decode(substr($payload, strlen(self::PREFIX)), true);
        $ivLen = openssl_cipher_iv_length(self::CIPHER);
        if ($decoded === false || strlen($decoded) < ($ivLen + 16)) {
            return '[Entschlüsselungsfehler]';
        }
        $iv = substr($decoded, 0, $ivLen);
        $tag = substr($decoded, $ivLen, 16);
        $ciphertext = substr($decoded, $ivLen + 16);

        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        return $plaintext === false ? '[Entschlüsselungsfehler]' : $plaintext;
    }

    public static function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::PREFIX);
    }
}
