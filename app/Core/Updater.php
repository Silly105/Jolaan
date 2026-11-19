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

        Database::setSetting('db_version', Version::CURRENT);
        return $executed;
    }

    /**
     * Prüft direkt über PDO::query (ohne Prepared-Statement-Einschränkung von MySQL für SHOW COLUMNS),
     * ob eine Spalte in einer Tabelle existiert.
     */
    private static function columnExists(string $tableSql, string $columnName): bool
    {
        $pdo = Database::connect();
        $cleanCol = preg_replace('/[^a-zA-Z0-9_]/', '', $columnName);
        $stmt = $pdo->query("SHOW COLUMNS FROM {$tableSql} LIKE '{$cleanCol}'");
        return $stmt !== false && $stmt->fetch() !== false;
    }

    /**
     * Führt die spezifischen Datenbank-/System-Änderungen einer einzelnen Version aus.
     */
    private static function migrateTo(string $version): void
    {
        $pdo = Database::connect();

        if ($version === '0.9.001') {
            $tEvents = Database::table('events');
            if (!self::columnExists($tEvents, 'maps_url')) {
                $pdo->exec("ALTER TABLE {$tEvents} ADD COLUMN `maps_url` VARCHAR(500) NOT NULL DEFAULT '' AFTER `location_suffix`");
            }

            $curTitle = (string)Database::getSetting('site_title', '');
            if ($curTitle === 'Westliches Siegel – Con-System') {
                Database::setSetting('site_title', 'Con-Management-System');
            }
        }

        if ($version === '0.9.002') {
            $tUsers  = Database::table('users');
            $tFields = Database::table('form_fields');
            $tRegs   = Database::table('registrations');
            $tVals   = Database::table('registration_values');

            // 1. 2FA-Spalten in users ergänzen
            if (!self::columnExists($tUsers, 'totp_secret')) {
                $pdo->exec("ALTER TABLE {$tUsers}
                    ADD COLUMN `totp_secret` VARCHAR(255) NULL DEFAULT NULL AFTER `is_active`,
                    ADD COLUMN `totp_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `totp_secret`,
                    ADD COLUMN `totp_backup_codes` TEXT NULL DEFAULT NULL AFTER `totp_enabled`");
            }

            // 2. Spalte is_encrypted in form_fields ergänzen und für E-Mail, Telefon & sensible Felder aktivieren
            if (!self::columnExists($tFields, 'is_encrypted')) {
                $pdo->exec("ALTER TABLE {$tFields} ADD COLUMN `is_encrypted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_required`");
                $pdo->exec("UPDATE {$tFields} SET `is_encrypted` = 1 WHERE `is_sensitive` = 1 OR `field_type` = 'email' OR `system_mapping` = 'email' OR `field_key` IN ('email', 'telefon', 'handy', 'phone')");
            }

            // 3. Spalte email (Text für Chiffretext) & email_hash in registrations ergänzen
            if (!self::columnExists($tRegs, 'email_hash')) {
                // Alten Index idx_reg_email entfernen, falls vorhanden (da TEXT keinen vollen Index ohne Länge erlaubt)
                try {
                    $pdo->exec("ALTER TABLE {$tRegs} DROP INDEX `idx_reg_email`");
                } catch (\Throwable) {
                    // Index existierte nicht
                }
                $pdo->exec("ALTER TABLE {$tRegs} MODIFY COLUMN `email` TEXT NOT NULL");
                $pdo->exec("ALTER TABLE {$tRegs} ADD COLUMN `email_hash` VARCHAR(64) NOT NULL DEFAULT '' AFTER `email`, ADD INDEX `idx_reg_email_hash` (`email_hash`)");
            }

            // 4. Bestehende Klartext-E-Mails in registrations und Klartext-Werte verschlüsselter Felder nachverschlüsseln
            $allRegs = Database::fetchAll("SELECT id, email FROM {$tRegs}");
            foreach ($allRegs as $r) {
                $rawEmail = (string)$r['email'];
                $plainEmail = Crypto::isEncrypted($rawEmail) ? Crypto::decrypt($rawEmail) : $rawEmail;
                $encEmail = Crypto::encrypt($plainEmail);
                $hash = Crypto::blindIndex($plainEmail);
                Database::query("UPDATE {$tRegs} SET email = :e, email_hash = :h WHERE id = :id", [
                    'e'  => $encEmail,
                    'h'  => $hash,
                    'id' => (int)$r['id'],
                ]);
            }

            $unencVals = Database::fetchAll(
                "SELECT rv.id, rv.field_value
                 FROM {$tVals} rv
                 INNER JOIN {$tFields} f ON f.id = rv.field_id
                 WHERE (f.is_encrypted = 1 OR f.is_sensitive = 1) AND rv.is_encrypted = 0 AND rv.field_value != ''"
            );
            foreach ($unencVals as $uv) {
                $val = (string)$uv['field_value'];
                if (!Crypto::isEncrypted($val)) {
                    $val = Crypto::encrypt($val);
                }
                Database::query("UPDATE {$tVals} SET field_value = :v, is_encrypted = 1 WHERE id = :id", [
                    'v'  => $val,
                    'id' => (int)$uv['id'],
                ]);
            }

            // 5. Neue Standard-Settings für v0.9.002 setzen (falls noch nicht vorhanden)
            if (Database::getSetting('main_site_label_de') === null) {
                Database::setSetting('main_site_label_de', 'Hauptseite');
                Database::setSetting('main_site_label_en', 'Main Website');
                Database::setSetting('forum_label_de', 'Forum');
                Database::setSetting('forum_label_en', 'Forum');
                Database::setSetting('storage_notice_de', 'Deine Anmeldedaten werden auf Servern in Deutschland / der EU gespeichert. Kontakt- und Gesundheitsdaten (mit 🔒 markiert) werden zusätzlich mit AES-256-GCM verschlüsselt in der Datenbank abgelegt.');
                Database::setSetting('storage_notice_en', 'Your registration data is stored on servers in Germany / the EU. Contact and health data (marked with 🔒) are additionally stored encrypted using AES-256-GCM.');
            }
        }
    }
}
