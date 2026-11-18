<?php
declare(strict_types=1);

namespace App\Core;

class Theme
{
    public static function themesDir(): string
    {
        return dirname(__DIR__, 2) . '/themes';
    }

    /**
     * Liefert alle installierten Themes aus dem Ordner themes/
     */
    public static function availableThemes(): array
    {
        $dir = self::themesDir();
        if (!is_dir($dir)) {
            return [];
        }
        $themes = [];
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $manifestPath = $dir . '/' . $entry . '/theme.json';
            if (is_file($manifestPath)) {
                $meta = json_decode((string)file_get_contents($manifestPath), true);
                if (is_array($meta)) {
                    $themes[$entry] = [
                        'slug'        => $entry,
                        'name'        => $meta['name'] ?? $entry,
                        'description' => $meta['description'] ?? '',
                        'preview_bg'  => $meta['preview_bg'] ?? '#eaf7cb',
                        'preview_acc' => $meta['preview_accent'] ?? '#255E00',
                    ];
                }
            }
        }
        return $themes;
    }

    /**
     * Ermittelt das aktive Theme: Entweder das Con-spezifische Theme oder das globale Standard-Theme.
     */
    public static function resolveTheme(?string $eventTheme = null): string
    {
        $available = self::availableThemes();
        if ($eventTheme !== null && $eventTheme !== '' && isset($available[$eventTheme])) {
            return $eventTheme;
        }
        $globalTheme = (string)Database::getSetting('default_theme', 'western-seal');
        if (isset($available[$globalTheme])) {
            return $globalTheme;
        }
        return 'western-seal';
    }

    /**
     * Rendert eine öffentliche Seite im gewählten Theme inkl. optionalem Veranstaltungs-Header (Logo + Textblock).
     */
    public static function render(string $title, string $contentHtml, array $context = []): void
    {
        $event = $context['event'] ?? null;
        $eventTheme = is_array($event) ? ($event['theme'] ?? null) : null;
        $activeTheme = self::resolveTheme($eventTheme);
        $allowEnglish = is_array($event) ? (bool)($event['is_bilingual'] ?? false) : true;

        $siteTitle = (string)Database::getSetting('site_title', 'Con-Management');
        $siteSubtitle = (string)Database::getSetting('site_subtitle', 'Online-Anmeldesystem');
        $mainSiteUrl = (string)Database::getSetting('main_site_url', '');
        $forumUrl = (string)Database::getSetting('forum_url', '');
        $privacyUrl = (string)Database::getSetting('privacy_url', '');
        $imprintUrl = (string)Database::getSetting('imprint_url', '');
        $globalLogo = (string)Database::getSetting('site_logo', '');

        // Pro Veranstaltung konfigurierbarer Header (Logo & Textblock über der Anmeldung)
        $headerLogo = '';
        $headerText = '';
        if (is_array($event)) {
            if (!empty($event['header_logo_path'])) {
                $headerLogo = (string)$event['header_logo_path'];
            }
            $headerText = I18n::field($event, 'header_text');
        }
        if ($headerLogo === '' && $globalLogo !== '') {
            $headerLogo = $globalLogo;
        }

        $themeCssUrl = 'themes/' . rawurlencode($activeTheme) . '/style.css';
        $layoutFile = self::themesDir() . '/' . $activeTheme . '/layout.php';

        if (is_file($layoutFile)) {
            require $layoutFile;
            return;
        }

        // Fallback falls layout.php fehlt
        echo "<!DOCTYPE html><html lang='de'><head><meta charset='UTF-8'><title>" . e($title) . "</title></head><body>{$contentHtml}</body></html>";
    }
}
