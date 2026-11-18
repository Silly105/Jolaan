<?php
declare(strict_types=1);

namespace App\Core;

class Updater
{
    /**
     * Prüft, ob die Datenbank-Version hinter Version::CURRENT liegt, und führt alle fehlenden
     * Migrationsschritte der Reihe nach automatisch aus (unterstützt beliebige Versionssprünge).
     */
    public static function runPendingMigrations(): array
    {
        if (!Config::isInstalled()) {
            return [];
        }

        $installedVersion = (string)Database::getSetting('db_version', '0.9.000');
        if (version_compare($installedVersion, Version::CURRENT, '>=')) {
            return [];
        }

        $executed = [];
        $history = Version::history();
        uksort($history, 'version_compare');

        foreach ($history as $ver => $meta) {
            if (version_compare($ver, $installedVersion, '>') && version_compare($ver, Version::CURRENT, '<=')) {
                self::migrateTo($ver);
                Database::setSetting('db_version', $ver);
                $installedVersion = $ver;
                $executed[] = $ver;
            }
        }

        // Sicherheitshalber aktuelle Version setzen
        Database::setSetting('db_version', Version::CURRENT);
        return $executed;
    }

    /**
     * Führt die spezifischen Datenbank-/System-Änderungen einer einzelnen Version aus.
     */
    private static function migrateTo(string $version): void
    {
        $pdo = Database::connect();

        if ($version === '0.9.001') {
            $tEvents = Database::table('events');
            // Prüfen, ob Spalte maps_url in events bereits existiert
            $cols = Database::fetchAll("SHOW COLUMNS FROM {$tEvents} LIKE 'maps_url'");
            if (empty($cols)) {
                $pdo->exec("ALTER TABLE {$tEvents} ADD COLUMN `maps_url` VARCHAR(500) NOT NULL DEFAULT '' AFTER `location_suffix`");
            }

            // Alte Demo-Referenzen im Seitentitel bereinigen, falls noch unverändert vom ersten Test
            $curTitle = (string)Database::getSetting('site_title', '');
            if ($curTitle === 'Westliches Siegel – Con-System') {
                Database::setSetting('site_title', 'Con-Management-System');
            }
        }
    }
}
