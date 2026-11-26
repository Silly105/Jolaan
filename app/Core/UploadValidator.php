<?php
declare(strict_types=1);

namespace App\Core;

class UploadValidator
{
    /**
     * Prüft einen Bild-Upload (Header-Logo, Bannerbild oder Favicon) auf Fehler, Dateigröße,
     * zulässiges Format, maximale Pixel-Auflösung sowie SVG-Skriptfreiheit.
     */
    public static function validateAndStoreImage(
        array $file,
        string $prefix,
        int $maxBytes = 2097152,
        int $maxWidth = 2500,
        int $maxHeight = 800,
        array $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg']
    ): array {
        if (empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => 'Keine Datei ausgewählt.'];
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Fehler beim Datei-Upload (Code ' . (int)$file['error'] . ').'];
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            $maxMb = round($maxBytes / 1048576, 1);
            return ['ok' => false, 'error' => "Die Datei ist zu groß (maximal {$maxMb} MB erlaubt)."];
        }

        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions, true)) {
            return ['ok' => false, 'error' => 'Ungültiges Dateiformat. Erlaubt sind: ' . strtoupper(implode(', ', $allowedExtensions)) . '.'];
        }

        $tmpPath = (string)$file['tmp_name'];

        if ($ext === 'svg') {
            $svgContent = (string)@file_get_contents($tmpPath);
            if ($svgContent === '' || stripos($svgContent, '<svg') === false) {
                return ['ok' => false, 'error' => 'Die hochgeladene Datei ist keine gültige SVG-Grafik.'];
            }
            if (preg_match('/<\s*script\b|on[a-z]+\s*=|javascript\s*:|<\s*foreignObject\b/i', $svgContent)) {
                return ['ok' => false, 'error' => 'SVG-Datei abgelehnt: Aktive Skripte oder Event-Handler sind aus Sicherheitsgründen nicht erlaubt.'];
            }
        } elseif ($ext !== 'ico') {
            $imgInfo = @getimagesize($tmpPath);
            if ($imgInfo === false) {
                return ['ok' => false, 'error' => 'Die hochgeladene Datei ist kein gültiges Bild.'];
            }
            [$width, $height] = $imgInfo;
            if ($width > $maxWidth || $height > $maxHeight) {
                return [
                    'ok'    => false,
                    'error' => "Die Bildauflösung ({$width}×{$height} px) überschreitet das Maximum von {$maxWidth}×{$maxHeight} Pixeln.",
                ];
            }
        }

        $fileName = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destDir  = dirname(__DIR__, 2) . '/storage/uploads';
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        if (!move_uploaded_file($tmpPath, $destDir . '/' . $fileName)) {
            return ['ok' => false, 'error' => 'Datei konnte nicht im Upload-Verzeichnis gespeichert werden.'];
        }

        return [
            'ok'   => true,
            'path' => 'storage/uploads/' . $fileName,
        ];
    }

    /**
     * Löscht eine nicht mehr referenzierte Datei sicher aus storage/uploads/.
     */
    public static function deleteStoredFileIfUnused(?string $relativePath): void
    {
        $path = trim((string)$relativePath);
        if ($path === '' || !str_starts_with($path, 'storage/uploads/')) {
            return;
        }
        $safeBase = basename($path);
        if ($safeBase === '' || $safeBase === '.htaccess' || $safeBase === '.gitkeep' || $safeBase === 'index.html') {
            return;
        }

        $normalized = 'storage/uploads/' . $safeBase;

        // Prüfen, ob noch eine andere Veranstaltung oder ein geklontes Formularfeld auf dieselbe Datei verweist
        try {
            $tEvents   = Database::table('events');
            $tFields   = Database::table('form_fields');
            $tSettings = Database::table('settings');

            $inEvents = (int)Database::fetchScalar(
                "SELECT COUNT(*) FROM {$tEvents} WHERE header_logo_path = :p1 OR banner_image_path = :p2",
                ['p1' => $normalized, 'p2' => $normalized]
            );
            $inSettings = (int)Database::fetchScalar(
                "SELECT COUNT(*) FROM {$tSettings} WHERE setting_value = :p",
                ['p' => $normalized]
            );
            $inFields = (int)Database::fetchScalar(
                "SELECT COUNT(*) FROM {$tFields} WHERE options_json LIKE :like",
                ['like' => '%' . $safeBase . '%']
            );

            if ($inEvents > 0 || $inSettings > 0 || $inFields > 0) {
                return;
            }
        } catch (\Throwable) {
        }

        $absPath = dirname(__DIR__, 2) . '/storage/uploads/' . $safeBase;
        if (is_file($absPath)) {
            @unlink($absPath);
        }
    }
}
