<?php
declare(strict_types=1);

namespace App\Core;

class Updater
{
    /**
     * Prüft, ob die Datenbank-Version hinter Version::CURRENT liegt, und führt alle fehlenden
     * Migrationsschritte der Reihe nach automatisch aus (unterstützt beliebige Versionssprünge).
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

        $installedVersion = (string)Database::getSetting('db_version', '0.9.000');
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

            if (!self::columnExists($tUsers, 'totp_secret')) {
                $pdo->exec("ALTER TABLE {$tUsers}
                    ADD COLUMN `totp_secret` VARCHAR(255) NULL DEFAULT NULL AFTER `is_active`,
                    ADD COLUMN `totp_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `totp_secret`,
                    ADD COLUMN `totp_backup_codes` TEXT NULL DEFAULT NULL AFTER `totp_enabled`");
            }

            if (!self::columnExists($tFields, 'is_encrypted')) {
                $pdo->exec("ALTER TABLE {$tFields} ADD COLUMN `is_encrypted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_required`");
                $pdo->exec("UPDATE {$tFields} SET `is_encrypted` = 1 WHERE `is_sensitive` = 1 OR `field_type` = 'email' OR `system_mapping` = 'email' OR `field_key` IN ('email', 'telefon', 'handy', 'phone')");
            }

            if (!self::columnExists($tRegs, 'email_hash')) {
                try {
                    $pdo->exec("ALTER TABLE {$tRegs} DROP INDEX `idx_reg_email`");
                } catch (\Throwable) {
                }
                $pdo->exec("ALTER TABLE {$tRegs} MODIFY COLUMN `email` TEXT NOT NULL");
                $pdo->exec("ALTER TABLE {$tRegs} ADD COLUMN `email_hash` VARCHAR(64) NOT NULL DEFAULT '' AFTER `email`, ADD INDEX `idx_reg_email_hash` (`email_hash`)");
            }

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

            if (Database::getSetting('main_site_label_de') === null) {
                Database::setSetting('main_site_label_de', 'Hauptseite');
                Database::setSetting('main_site_label_en', 'Main Website');
                Database::setSetting('forum_label_de', 'Forum');
                Database::setSetting('forum_label_en', 'Forum');
                Database::setSetting('storage_notice_de', 'Deine Anmeldedaten werden auf Servern in Deutschland / der EU gespeichert. Kontakt- und Gesundheitsdaten (mit 🔒 markiert) werden zusätzlich mit AES-256-GCM verschlüsselt in der Datenbank abgelegt.');
                Database::setSetting('storage_notice_en', 'Your registration data is stored on servers in Germany / the EU. Contact and health data (marked with 🔒) are additionally stored encrypted using AES-256-GCM.');
            }
        }

        if ($version === '0.9.003') {
            $tEvents = Database::table('events');
            $tGroups = Database::table('event_groups');
            $tRegs   = Database::table('registrations');

            if (!self::columnExists($tEvents, 'waitlist_enabled')) {
                $pdo->exec("ALTER TABLE {$tEvents} ADD COLUMN `waitlist_enabled` TINYINT(1) NOT NULL DEFAULT 1 AFTER `status`");
            }

            if (!self::columnExists($tGroups, 'show_in_registration')) {
                $pdo->exec("ALTER TABLE {$tGroups} ADD COLUMN `show_in_registration` TINYINT(1) NOT NULL DEFAULT 1 AFTER `show_on_index`");
            }

            $globalAbsagen = Database::fetchOne("SELECT id FROM {$tGroups} WHERE event_id IS NULL AND name_de = 'Absagen' LIMIT 1");
            if (!$globalAbsagen) {
                Database::query(
                    "INSERT INTO {$tGroups} (event_id, name_de, name_en, max_seats, is_locked, show_on_index, show_in_registration, sort_order)
                     VALUES (NULL, 'Absagen', 'Cancellations', 0, 0, 0, 0, 90)"
                );
            }
            $allEvents = Database::fetchAll("SELECT id FROM {$tEvents}");
            foreach ($allEvents as $evRow) {
                $eid = (int)$evRow['id'];
                $evAbs = Database::fetchOne("SELECT id FROM {$tGroups} WHERE event_id = :eid AND name_de = 'Absagen' LIMIT 1", ['eid' => $eid]);
                if (!$evAbs) {
                    Database::query(
                        "INSERT INTO {$tGroups} (event_id, name_de, name_en, max_seats, is_locked, show_on_index, show_in_registration, sort_order)
                         VALUES (:eid, 'Absagen', 'Cancellations', 0, 0, 0, 0, 90)",
                        ['eid' => $eid]
                    );
                }
            }

            if (!self::columnExists($tRegs, 'is_waitlist')) {
                $pdo->exec("ALTER TABLE {$tRegs}
                    ADD COLUMN `is_waitlist` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_confirmed`,
                    ADD COLUMN `waitlist_confirmed` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_waitlist`,
                    ADD COLUMN `is_secret_unlock` TINYINT(1) NOT NULL DEFAULT 0 AFTER `waitlist_confirmed`,
                    ADD COLUMN `confirmed_at` DATETIME NULL DEFAULT NULL AFTER `created_at`,
                    ADD COLUMN `history_log` TEXT NULL DEFAULT NULL AFTER `confirmed_at`");

                $pdo->exec("UPDATE {$tRegs} SET `confirmed_at` = `created_at` WHERE `is_confirmed` = 1 AND `confirmed_at` IS NULL");
                $pdo->exec("UPDATE {$tRegs} SET `history_log` = CONCAT(DATE_FORMAT(`created_at`, '%d.%m.%Y %H:%i:%s'), ' – Anmeldung im System eingegangen\n', IF(`is_confirmed` = 1, CONCAT(DATE_FORMAT(`created_at`, '%d.%m.%Y %H:%i:%s'), ' – E-Mail-Bestätigung erfolgt (Reguläre Teilnehmerliste)'), '')) WHERE `history_log` IS NULL");
            }

            if (Database::getSetting('mail_tpl_waitlist_subject_de') === null) {
                Database::setSetting('mail_tpl_waitlist_subject_de', 'Dein Wartelistenplatz für {conname}');
                Database::setSetting(
                    'mail_tpl_waitlist_body_de',
                    "<p>Hallo {vorname} {nachname},</p><p>wir bestätigen den Eingang deiner Anmeldung für <b>{conname}</b> ({condatum}) in der Gruppe <b>{gruppe}</b>.</p><p>Da diese Teilnehmergruppe zum Zeitpunkt deiner E-Mail-Bestätigung bereits ausgebucht war, stehst du aktuell auf der <b>Warteliste auf Platz {wartelistenplatz}</b>.</p><p>Bitte überweise noch keinen Teilnahmebeitrag. Sobald ein Platz für dich frei wird, melden wir uns umgehend bei dir!</p><p>Viele Grüße<br>Deine Orga</p>"
                );
                Database::setSetting('mail_tpl_waitlist_subject_en', 'Your Waiting List Spot for {conname}');
                Database::setSetting(
                    'mail_tpl_waitlist_body_en',
                    "<p>Hello {vorname} {nachname},</p><p>we confirm receipt of your registration for <b>{conname}</b> ({condatum}) in the group <b>{gruppe}</b>.</p><p>Since this participant group was already fully booked at the time of your email confirmation, you are currently on the <b>waiting list at position #{wartelistenplatz}</b>.</p><p>Please do not transfer any participation fee yet. As soon as a spot becomes available for you, we will contact you!</p><p>Best regards<br>The Organizers</p>"
                );
            }
        }

        if ($version === '0.9.004') {
            $tUsers = Database::table('users');
            if (self::columnExists($tUsers, 'totp_secret')) {
                $pdo->exec("ALTER TABLE {$tUsers} MODIFY COLUMN `totp_secret` VARCHAR(255) NULL DEFAULT NULL");
            }
        }

        if ($version === '0.9.005') {
            $tFields = Database::table('form_fields');
            if (!self::columnExists($tFields, 'keep_on_prefill')) {
                $pdo->exec("ALTER TABLE {$tFields} ADD COLUMN `keep_on_prefill` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_orga_only`");
                $pdo->exec("UPDATE {$tFields} SET `keep_on_prefill` = 1 WHERE `system_mapping` IN ('lastname', 'last_name', 'email') OR `field_key` IN ('nachname', 'strasse', 'plz', 'ort', 'plz_ort', 'email', 'telefon', 'handy', 'phone')");
            }
        }

        if ($version === '0.9.006') {
            $tEvents = Database::table('events');
            if (!self::columnExists($tEvents, 'header_mode')) {
                $pdo->exec("ALTER TABLE {$tEvents} ADD COLUMN `header_mode` VARCHAR(30) NULL DEFAULT NULL AFTER `theme`");
            }
            if (!self::columnExists($tEvents, 'banner_image_path')) {
                $pdo->exec("ALTER TABLE {$tEvents} ADD COLUMN `banner_image_path` VARCHAR(255) NOT NULL DEFAULT '' AFTER `header_logo_path`");
            }
            if (Database::getSetting('default_header_mode') === null) {
                Database::setSetting('default_header_mode', 'compact');
            }
        }
    }
}
