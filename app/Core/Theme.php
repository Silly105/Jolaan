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
     * Verfügbare Header-Modi (global einstellbar und pro Veranstaltung überschreibbar).
     */
    public static function headerModes(): array
    {
        return [
            'compact'  => 'Kompakte Kopfleiste (Logo/Titel links, schmale Navigation)',
            'centered' => 'Zentrierter Wappen- / Banner-Header (Großes zentriertes Logo & Navigation)',
            'hero'     => 'Großer Hero-Header (Hoher Bühnen-Kopfbereich mit Tiefenwirkung)',
        ];
    }

    /**
     * Ermittelt den aktiven Header-Modus (Con-spezifisch oder globaler Standard).
     */
    public static function resolveHeaderMode(?string $eventHeaderMode = null): string
    {
        $modes = self::headerModes();
        if ($eventHeaderMode !== null && $eventHeaderMode !== '' && isset($modes[$eventHeaderMode])) {
            return $eventHeaderMode;
        }
        $globalMode = (string)Database::getSetting('default_header_mode', 'compact');
        if (isset($modes[$globalMode])) {
            return $globalMode;
        }
        return 'compact';
    }

    /**
     * Liefert alle installierten Themes aus dem Ordner themes/, sortiert nach sort_order.
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
                        'category'    => $meta['category'] ?? 'clean',
                        'sort_order'  => (int)($meta['sort_order'] ?? 999),
                        'preview_bg'  => $meta['preview_bg'] ?? '#eaf7cb',
                        'preview_acc' => $meta['preview_accent'] ?? '#255E00',
                    ];
                }
            }
        }

        uasort($themes, static function (array $a, array $b): int {
            if ($a['sort_order'] === $b['sort_order']) {
                return strcmp((string)$a['name'], (string)$b['name']);
            }
            return $a['sort_order'] <=> $b['sort_order'];
        });

        return $themes;
    }

    /**
     * Liefert die Themes gruppiert nach Kategorie ('clean' vs. 'genre') für übersichtliche Auswahlmenüs.
     */
    public static function availableThemesGrouped(): array
    {
        $grouped = [
            'clean' => [
                'label'  => 'Schlichte Farb-Themes',
                'themes' => [],
            ],
            'genre' => [
                'label'  => 'Aufwändige Genre- & Erlebnis-Themes (mit Texturen & Ornamentik)',
                'themes' => [],
            ],
        ];

        foreach (self::availableThemes() as $slug => $meta) {
            $cat = ($meta['category'] ?? 'clean') === 'genre' ? 'genre' : 'clean';
            $grouped[$cat]['themes'][$slug] = $meta;
        }

        return $grouped;
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
     * Liefert die Farbpalette eines Themes für das Admin-Backend, damit das globale Theme auch im Backend sofort wirkt.
     */
    public static function getAdminPalette(?string $themeSlug = null): array
    {
        $slug = self::resolveTheme($themeSlug);
        return match ($slug) {
            'parchment', 'fantasy-adventure' => [
                'sidebar_bg'    => '#2b180d',
                'sidebar_dark'  => '#1f1008',
                'accent'        => '#7c2d12',
                'accent_hover'  => '#581c08',
                'accent_border' => '#b45309',
                'accent_light'  => '#f59e0b',
                'body_bg'       => '#f7f2e7',
            ],
            'modern-clean' => [
                'sidebar_bg'    => '#0f172a',
                'sidebar_dark'  => '#020617',
                'accent'        => '#0f766e',
                'accent_hover'  => '#115e59',
                'accent_border' => '#0d9488',
                'accent_light'  => '#2dd4bf',
                'body_bg'       => '#f1f5f9',
            ],
            'fire-and-ash', 'fantasy-battle' => [
                'sidebar_bg'    => '#1a1415',
                'sidebar_dark'  => '#110c0d',
                'accent'        => '#991b1b',
                'accent_hover'  => '#7f1d1d',
                'accent_border' => '#dc2626',
                'accent_light'  => '#f87171',
                'body_bg'       => '#f4f1f1',
            ],
            'arctic-storm' => [
                'sidebar_bg'    => '#0f2238',
                'sidebar_dark'  => '#091626',
                'accent'        => '#0369a1',
                'accent_hover'  => '#075985',
                'accent_border' => '#0284c7',
                'accent_light'  => '#38bdf8',
                'body_bg'       => '#f0f7fc',
            ],
            'mystic-twilight' => [
                'sidebar_bg'    => '#231538',
                'sidebar_dark'  => '#170d26',
                'accent'        => '#6d28d9',
                'accent_hover'  => '#5b21b6',
                'accent_border' => '#8b5cf6',
                'accent_light'  => '#c4b5fd',
                'body_bg'       => '#f5f2fa',
            ],
            'roaring-1920s' => [
                'sidebar_bg'    => '#0d211d',
                'sidebar_dark'  => '#071411',
                'accent'        => '#85641e',
                'accent_hover'  => '#694e14',
                'accent_border' => '#c5a059',
                'accent_light'  => '#e5c171',
                'body_bg'       => '#f8f5ec',
            ],
            'western-frontier' => [
                'sidebar_bg'    => '#291a12',
                'sidebar_dark'  => '#1c110b',
                'accent'        => '#8c3b1a',
                'accent_hover'  => '#6e2c11',
                'accent_border' => '#b86b35',
                'accent_light'  => '#eab308',
                'body_bg'       => '#f4ebd9',
            ],
            'scifi-hud' => [
                'sidebar_bg'    => '#091324',
                'sidebar_dark'  => '#050b16',
                'accent'        => '#0284c7',
                'accent_hover'  => '#0369a1',
                'accent_border' => '#06b6d4',
                'accent_light'  => '#22d3ee',
                'body_bg'       => '#eef4f8',
            ],
            'cthulhu-arkham' => [
                'sidebar_bg'    => '#0e1f1d',
                'sidebar_dark'  => '#081413',
                'accent'        => '#274e47',
                'accent_hover'  => '#1b3833',
                'accent_border' => '#8c734b',
                'accent_light'  => '#c2a675',
                'body_bg'       => '#eff1ec',
            ],
            default => [ // western-seal ("Western Forest (Waldgrün)")
                'sidebar_bg'    => '#1e2e22',
                'sidebar_dark'  => '#162219',
                'accent'        => '#255E00',
                'accent_hover'  => '#1b4600',
                'accent_border' => '#739757',
                'accent_light'  => '#84cc16',
                'body_bg'       => '#f2f7ec',
            ],
        };
    }

    /**
     * Rendert eine öffentliche Seite im gewählten Theme inkl. Header-Modus, Veranstaltungs-Bannerbild und Header-Textblock.
     */
    public static function render(string $title, string $contentHtml, array $context = []): void
    {
        $event = $context['event'] ?? null;
        $eventTheme = is_array($event) ? ($event['theme'] ?? null) : null;
        $eventHeaderMode = is_array($event) ? ($event['header_mode'] ?? null) : null;
        $activeTheme = self::resolveTheme($eventTheme);
        $headerMode  = self::resolveHeaderMode($eventHeaderMode);
        $allowEnglish = is_array($event) ? (bool)($event['is_bilingual'] ?? false) : true;

        $siteTitle = (string)Database::getSetting('site_title', 'Con-Management');
        $siteSubtitle = (string)Database::getSetting('site_subtitle', 'Online-Anmeldesystem');
        $mainSiteUrl = (string)Database::getSetting('main_site_url', '');
        $forumUrl = (string)Database::getSetting('forum_url', '');
        $privacyUrl = (string)Database::getSetting('privacy_url', '');
        $imprintUrl = (string)Database::getSetting('imprint_url', '');
        $globalLogo = (string)Database::getSetting('site_logo', '');
        $faviconUrl = (string)Database::getSetting('site_favicon', '');

        $isEn = I18n::isEnglish();
        $mainSiteLabel = $isEn
            ? (string)Database::getSetting('main_site_label_en', I18n::t('nav.main_site'))
            : (string)Database::getSetting('main_site_label_de', I18n::t('nav.main_site'));
        if (trim($mainSiteLabel) === '') {
            $mainSiteLabel = I18n::t('nav.main_site');
        }
        $forumLabel = $isEn
            ? (string)Database::getSetting('forum_label_en', I18n::t('nav.forum'))
            : (string)Database::getSetting('forum_label_de', I18n::t('nav.forum'));
        if (trim($forumLabel) === '') {
            $forumLabel = I18n::t('nav.forum');
        }

        // Pro Veranstaltung konfigurierbarer Header (Logo, Bannerbild in Formularbreite & Textblock über der Anmeldung)
        $headerLogo  = '';
        $bannerImage = '';
        $headerText  = '';
        if (is_array($event)) {
            if (!empty($event['header_logo_path'])) {
                $headerLogo = (string)$event['header_logo_path'];
            }
            if (!empty($event['banner_image_path'])) {
                $bannerImage = (string)$event['banner_image_path'];
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

        echo "<!DOCTYPE html><html lang='de'><head><meta charset='UTF-8'><title>" . e($title) . "</title></head><body>{$contentHtml}</body></html>";
    }
}
