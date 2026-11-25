<?php
declare(strict_types=1);

namespace App\Core;

/**
 * 100 % nativer E-Mail- und SMTP-Client für PHP 8.x (ohne PHPMailer oder externe Libraries).
 * Unterstützt sowohl den direkten Versand über den eigenen Webserver (PHP mail())
 * als auch externen SMTP-Versand inkl. STARTTLS (Port 587), SSL/TLS (Port 465) und AUTH LOGIN.
 */
class Mailer
{
    private static string $lastError = '';

    public static function getLastError(): string
    {
        return self::$lastError;
    }

    /**
     * Ersetzt Platzhalter wie {vorname}, {nachname}, {conname}, {betrag}, {link}, {wartelistenplatz} im Text.
     */
    public static function parseTemplate(string $template, array $vars): string
    {
        $replacements = [];
        foreach ($vars as $k => $v) {
            $replacements['{' . $k . '}'] = (string)$v;
        }
        return strtr($template, $replacements);
    }

    /**
     * Versendet eine HTML-E-Mail entweder über den lokalen Webserver (mail) oder über einen konfigurierten SMTP-Server.
     */
    public static function send(string $to, string $subject, string $htmlBody, ?string $fromEmail = null, ?string $fromName = null): bool
    {
        self::$lastError = '';

        $fromEmail = $fromEmail ?: (string)Database::getSetting('mail_from_address', 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $fromName  = $fromName  ?: (string)Database::getSetting('mail_from_name', (string)Database::getSetting('site_title', 'Con-Orga'));
        $transport = (string)Database::getSetting('mail_transport', 'mail'); // 'mail' oder 'smtp'
        $smtpHost  = trim((string)Database::getSetting('smtp_host', ''));

        if ($transport === 'smtp' && $smtpHost !== '') {
            return self::sendViaNativeSmtp($to, $subject, $htmlBody, $fromEmail, $fromName, $smtpHost);
        }

        return self::sendViaLocalMail($to, $subject, $htmlBody, $fromEmail, $fromName);
    }

    private static function sendViaLocalMail(string $to, string $subject, string $htmlBody, string $fromEmail, string $fromName): bool
    {
        $encodedSubject  = mb_encode_mimeheader($subject, 'UTF-8', 'B');
        $encodedFromName = mb_encode_mimeheader($fromName, 'UTF-8', 'B');
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            "From: {$encodedFromName} <{$fromEmail}>",
            "Reply-To: {$fromEmail}",
            'Date: ' . date('r'),
            'X-Mailer: ConManagement-Native/2.0',
        ];

        $bodyBase64 = chunk_split(base64_encode($htmlBody), 76, "\r\n");
        $ok = @mail($to, $encodedSubject, $bodyBase64, implode("\r\n", $headers));
        if (!$ok) {
            $err = error_get_last();
            self::$lastError = 'Lokaler Webserver-Mailversand (PHP mail()) fehlgeschlagen' . (!empty($err['message']) ? ': ' . $err['message'] : '.');
        }
        return $ok;
    }

    /**
     * Eigenständiger RFC 5321 / RFC 4954 SMTP-Client über PHP stream_socket_client().
     */
    private static function sendViaNativeSmtp(string $to, string $subject, string $htmlBody, string $fromEmail, string $fromName, string $smtpHost): bool
    {
        $smtpPort = (int)Database::getSetting('smtp_port', 587);
        if ($smtpPort <= 0) {
            $smtpPort = 587;
        }
        $smtpEnc  = (string)Database::getSetting('smtp_encryption', 'tls'); // 'tls' (STARTTLS), 'ssl' (SMTPS), 'none'
        $smtpUser = trim((string)Database::getSetting('smtp_user', ''));
        $rawPass  = (string)Database::getSetting('smtp_pass', '');
        $smtpPass = ($rawPass !== '' && Crypto::isEncrypted($rawPass)) ? Crypto::decrypt($rawPass) : $rawPass;

        $remote = ($smtpEnc === 'ssl' ? 'ssl://' : 'tcp://') . $smtpHost . ':' . $smtpPort;
        $errno  = 0;
        $errstr = '';

        $fp = @stream_socket_client($remote, $errno, $errstr, 12, STREAM_CLIENT_CONNECT);
        if (!is_resource($fp)) {
            self::$lastError = "SMTP-Verbindung zu {$smtpHost}:{$smtpPort} fehlgeschlagen ({$errno}: {$errstr})";
            return false;
        }

        stream_set_timeout($fp, 12);

        try {
            self::expectSmtpCode($fp, [220], 'Server-Greeting');

            $ehloHost = preg_replace('/[^a-zA-Z0-9.\-]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost')) ?: 'localhost';
            self::sendSmtpCmd($fp, "EHLO {$ehloHost}", [250]);

            if ($smtpEnc === 'tls') {
                self::sendSmtpCmd($fp, 'STARTTLS', [220]);
                $cryptoOk = @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if ($cryptoOk !== true) {
                    throw new \RuntimeException('STARTTLS-Verschlüsselung konnte nicht ausgehandelt werden.');
                }
                self::sendSmtpCmd($fp, "EHLO {$ehloHost}", [250]);
            }

            if ($smtpUser !== '') {
                self::sendSmtpCmd($fp, 'AUTH LOGIN', [334]);
                self::sendSmtpCmd($fp, base64_encode($smtpUser), [334], 'SMTP-Benutzername');
                self::sendSmtpCmd($fp, base64_encode($smtpPass), [235], 'SMTP-Passwort');
            }

            self::sendSmtpCmd($fp, "MAIL FROM:<{$fromEmail}>", [250]);
            self::sendSmtpCmd($fp, "RCPT TO:<{$to}>", [250, 251]);
            self::sendSmtpCmd($fp, 'DATA', [354]);

            $encodedSubject  = mb_encode_mimeheader($subject, 'UTF-8', 'B');
            $encodedFromName = mb_encode_mimeheader($fromName, 'UTF-8', 'B');
            $msgId           = bin2hex(random_bytes(12)) . '@' . $ehloHost;

            $headers = [
                'Date: ' . date('r'),
                "Message-ID: <{$msgId}>",
                "From: {$encodedFromName} <{$fromEmail}>",
                "Reply-To: <{$fromEmail}>",
                "To: <{$to}>",
                "Subject: {$encodedSubject}",
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: base64',
                'X-Mailer: ConManagement-Native/2.0',
            ];

            $bodyBase64 = chunk_split(base64_encode($htmlBody), 76, "\r\n");
            $rawData    = implode("\r\n", $headers) . "\r\n\r\n" . $bodyBase64 . "\r\n.";

            self::sendSmtpCmd($fp, $rawData, [250]);
            @fwrite($fp, "QUIT\r\n");
            @fclose($fp);
            return true;
        } catch (\Throwable $e) {
            self::$lastError = $e->getMessage();
            @fclose($fp);
            return false;
        }
    }

    private static function sendSmtpCmd($fp, string $cmd, array $expectedCodes, string $stepLabel = ''): string
    {
        if (@fwrite($fp, $cmd . "\r\n") === false) {
            throw new \RuntimeException('Senden an SMTP-Server fehlgeschlagen' . ($stepLabel ? " ({$stepLabel})" : '') . '.');
        }
        return self::expectSmtpCode($fp, $expectedCodes, $stepLabel ?: $cmd);
    }

    private static function expectSmtpCode($fp, array $expectedCodes, string $context): string
    {
        $response = '';
        while (($line = @fgets($fp, 515)) !== false) {
            $response .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }
        $code = (int)substr(trim($response), 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            $cleanResp = trim(preg_replace('/\s+/', ' ', $response));
            throw new \RuntimeException("SMTP-Fehler ({$context}): Server antwortete mit [{$code}] {$cleanResp}");
        }
        return $response;
    }
}
