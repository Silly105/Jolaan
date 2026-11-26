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
     * Ersetzt Platzhalter wie {vorname}, {nachname}, {conname}, {betrag}, {link}, {wartelistenplatz} im Text
     * und wandelt normale Zeilenumbrüche automatisch in HTML-<br> um, falls kein Block-HTML verwendet wurde.
     */
    public static function parseTemplate(string $template, array $vars): string
    {
        $replacements = [];
        foreach ($vars as $k => $v) {
            $replacements['{' . $k . '}'] = (string)$v;
        }
        $rendered = strtr($template, $replacements);

        // Falls der Autor der E-Mail-Vorlage normalen Text ohne HTML-Absätze (<p>, <br>, <div>, <ul>, <table>) geschrieben hat:
        if (!preg_match('/<\s*(p|br|div|ul|ol|li|table|h[1-6])\b/i', $rendered)) {
            $rendered = nl2br(e($rendered));
            // Links ({link}) trotzdem klickbar machen, falls als reiner Text eingefügt
            $rendered = (string)preg_replace(
                '~(https?://[^\s<"]+)~i',
                '<a href="$1">$1</a>',
                $rendered
            );
        }

        return $rendered;
    }

    private static function sanitizeHeaderAddress(string $email): string
    {
        return (string)preg_replace('/[\r\n<>]+/', '', trim($email));
    }

    private static function sanitizeHeaderText(string $text): string
    {
        return trim((string)preg_replace('/[\r\n]+/', ' ', $text));
    }

    /**
     * Versendet eine HTML-E-Mail entweder über den lokalen Webserver (mail) oder über einen konfigurierten SMTP-Server.
     */
    public static function send(string $to, string $subject, string $htmlBody, ?string $fromEmail = null, ?string $fromName = null): bool
    {
        self::$lastError = '';

        $cleanTo = self::sanitizeHeaderAddress($to);
        if ($cleanTo === '' || !filter_var($cleanTo, FILTER_VALIDATE_EMAIL)) {
            self::$lastError = 'Ungültige Empfänger-E-Mail-Adresse.';
            return false;
        }

        $rawFrom   = $fromEmail ?: (string)Database::getSetting('mail_from_address', 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $cleanFrom = self::sanitizeHeaderAddress($rawFrom);
        if (!filter_var($cleanFrom, FILTER_VALIDATE_EMAIL)) {
            $cleanFrom = 'noreply@localhost';
        }

        $cleanFromName = self::sanitizeHeaderText($fromName ?: (string)Database::getSetting('mail_from_name', (string)Database::getSetting('site_title', 'Con-Orga')));
        $cleanSubject  = self::sanitizeHeaderText($subject);

        $transport = (string)Database::getSetting('mail_transport', 'mail');
        $smtpHost  = trim((string)Database::getSetting('smtp_host', ''));

        if ($transport === 'smtp' && $smtpHost !== '') {
            return self::sendViaNativeSmtp($cleanTo, $cleanSubject, $htmlBody, $cleanFrom, $cleanFromName, $smtpHost);
        }

        return self::sendViaLocalMail($cleanTo, $cleanSubject, $htmlBody, $cleanFrom, $cleanFromName);
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
        $smtpEnc  = (string)Database::getSetting('smtp_encryption', 'tls');
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

            $bodyBase64 = rtrim(chunk_split(base64_encode($htmlBody), 76, "\r\n"), "\r\n");
            $rawMessage = implode("\r\n", $headers) . "\r\n\r\n" . $bodyBase64;
            // RFC 5321 Abschnitt 4.5.2 Transparency (Dot-Stuffing)
            $dotStuffed = (string)preg_replace('/^\./m', '..', $rawMessage);
            $rawData    = $dotStuffed . "\r\n.";

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
            $cleanResp = trim((string)preg_replace('/\s+/', ' ', $response));
            throw new \RuntimeException("SMTP-Fehler ({$context}): Server antwortete mit [{$code}] {$cleanResp}");
        }
        return $response;
    }
}
