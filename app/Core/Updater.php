<?php
declare(strict_types=1);

namespace App\Core;

class Updater
{
    /**
     * Prüft, ob die Datenbank-Version hinter Version::CURRENT liegt, und führt alle fehlenden
     * Migrationsschritte ab v1.0.0 der Reihe nach automatisch aus.
     * Nutzt einen schnellen Config-Cache, damit im laufenden Betrieb keine SQL-Query pro Seitenaufruf nötig ist.
     */
    public static function runPendingMigrations(bool $forceDbCheck = false): array
    {
        if (!Config::isInstalled()) {
            return [];
        }

        $cachedVersion = (string)Config::get('db_version_cache', '');
        if (!$forceDbCheck && $cachedVersion !== '' && version_compare($cachedVersion, Version::CURRENT, '>=')) {
            return [];
        }

        $installedVersion = (string)Database::getSetting('db_version', '1.0.0');
        if (version_compare($installedVersion, Version::CURRENT, '>=')) {
            Config::save(['db_version_cache' => Version::CURRENT]);
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

        Database::setSetting('db_version', Version::CURRENT);
        Config::save(['db_version_cache' => Version::CURRENT]);
        return $executed;
    }

    /**
     * Prüft direkt über PDO::query, ob eine Spalte in einer Tabelle existiert.
     */
    private static function columnExists(string $tableSql, string $columnName): bool
    {
        $pdo = Database::connect();
        $cleanCol = preg_replace('/[^a-zA-Z0-9_]/', '', $columnName);
        $stmt = $pdo->query("SHOW COLUMNS FROM {$tableSql} LIKE '{$cleanCol}'");
        return $stmt !== false && $stmt->fetch() !== false;
    }

    /**
     * Führt die spezifischen Datenbank-/System-Änderungen zukünftiger Versionen (ab 1.0.1+) aus.
     */
    private static function migrateTo(string $version): void
    {
        // Ausgangsbasis Version 1.0.0 – zukünftige Schema-Migrationen werden hier ergänzt.
    }
}
