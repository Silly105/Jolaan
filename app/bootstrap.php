<?php
declare(strict_types=1);

// Sicherstellen, dass UTF-8 und mitteleuropäische Zeitzone Standard sind
mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Berlin');

// Sicherheits-Header
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// PSR-4-artiger Autoloader für den Namespace App\
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

/**
 * HTML-Escaping Hilfsfunktion gegen XSS
 */
function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Formatiert ein ISO-Datum (YYYY-MM-DD) ins deutsche oder englische Format
 */
function format_date(?string $date, string $lang = 'de'): string
{
    if (!$date || $date === '0000-00-00') {
        return '';
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return (string)$date;
    }
    return $lang === 'en' ? date('Y-m-d', $ts) : date('d.m.Y', $ts);
}

/**
 * Berechnet das exakte Alter einer Person an einem Stichtag (z. B. Veranstaltungsbeginn für U18-Prüfung)
 */
function calculate_age_at(?string $birthDate, ?string $referenceDate): ?int
{
    if (!$birthDate || !$referenceDate) {
        return null;
    }
    try {
        $b = new DateTimeImmutable($birthDate);
        $r = new DateTimeImmutable($referenceDate);
        return $b->diff($r)->y;
    } catch (Throwable) {
        return null;
    }
}
