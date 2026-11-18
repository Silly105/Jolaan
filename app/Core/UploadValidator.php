<?php
declare(strict_types=1);

namespace App\Core;

class UploadValidator
{
    /**
     * Prüft einen Bild-Upload (Header-Logo oder Favicon) auf Fehler, Dateigröße, MIME-Type und maximale Pixel-Auflösung.
     * Gibt bei Erfolg ['ok' => true, 'path' => 'storage/uploads/...'] oder bei Fehler ['ok' => false, 'error' => '...'] zurück.
     */
    public static function validateAndStoreImage(
        array $file,
        string $prefix,
        int $maxBytes = 2097152, // 2 MB
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

        // Bei Rasterbildern (nicht SVG/ICO) echte Bildabmessungen prüfen
        if (!in_array($ext, ['svg', 'ico'], true)) {
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
            mkdir($destDir, 0755, true);
        }

        if (!move_uploaded_file($tmpPath, $destDir . '/' . $fileName)) {
            return ['ok' => false, 'error' => 'Datei konnte nicht im Upload-Verzeichnis gespeichert werden.'];
        }

        return [
            'ok'   => true,
            'path' => 'storage/uploads/' . $fileName,
        ];
    }
}
