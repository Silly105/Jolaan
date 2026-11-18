<?php
declare(strict_types=1);

namespace App\Core;

class Mailer
{
    /**
     * Ersetzt Platzhalter wie {vorname}, {nachname}, {conname}, {betrag}, {link} im Text.
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
     * Versendet eine HTML- + Text-E-Mail (nutzt entweder den gebündelten PHPMailer via SMTP/Mail oder mail()).
     */
    public static function send(string $to, string $subject, string $htmlBody, ?string $fromEmail = null, ?string $fromName = null): bool
    {
        $fromEmail = $fromEmail ?: (string)Database::getSetting('mail_from_address', 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $fromName  = $fromName  ?: (string)Database::getSetting('mail_from_name', (string)Database::getSetting('site_title', 'Con-Orga'));
        $transport = (string)Database::getSetting('mail_transport', 'mail'); // 'mail' oder 'smtp'

        // Prüfen, ob PHPMailer im Projekt oder _alt vorhanden ist
        $phpMailerDir = dirname(__DIR__, 2) . '/_alt/i/PHPMailer/src';
        if (is_file($phpMailerDir . '/PHPMailer.php')) {
            require_once $phpMailerDir . '/Exception.php';
            require_once $phpMailerDir . '/PHPMailer.php';
            require_once $phpMailerDir . '/SMTP.php';

            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->CharSet = 'UTF-8';
                if ($transport === 'smtp') {
                    $mail->isSMTP();
                    $mail->Host       = (string)Database::getSetting('smtp_host', '');
                    $mail->Port       = (int)Database::getSetting('smtp_port', 587);
                    $smtpUser         = (string)Database::getSetting('smtp_user', '');
                    $smtpPass         = (string)Database::getSetting('smtp_pass', '');
                    $smtpEnc          = (string)Database::getSetting('smtp_encryption', 'tls');
                    $mail->SMTPAuth   = ($smtpUser !== '');
                    $mail->Username   = $smtpUser;
                    $mail->Password   = $smtpPass;
                    if ($smtpEnc === 'ssl') {
                        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    } elseif ($smtpEnc === 'tls') {
                        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    }
                }
                $mail->setFrom($fromEmail, $fromName);
                $mail->addAddress($to);
                $mail->Subject = $subject;
                $mail->isHTML(true);
                $mail->Body    = $htmlBody;
                $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody));
                return $mail->send();
            } catch (\Throwable) {
                // Fallback auf native PHP mail()-Funktion
            }
        }

        $encodedSubject = mb_encode_mimeheader($subject, 'UTF-8', 'B');
        $encodedFromName = mb_encode_mimeheader($fromName, 'UTF-8', 'B');
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            "From: {$encodedFromName} <{$fromEmail}>",
            "Reply-To: {$fromEmail}",
            'X-Mailer: ConManagement/2.0',
        ];
        return @mail($to, $encodedSubject, $htmlBody, implode("\r\n", $headers));
    }
}
