<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Config;
use App\Core\Crypto;
use App\Core\Database;
use App\Core\Theme;

Auth::initSession();

if (Config::isInstalled()) {
    http_response_code(403);
    die('<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>Bereits installiert</title><style>body{font-family:system-ui,sans-serif;background:#f1f5f9;padding:3rem;text-align:center}.box{max-width:520px;margin:0 auto;background:#fff;padding:2rem;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,.08)}</style></head><body><div class="box"><h2>System ist bereits installiert</h2><p>Aus Sicherheitsgründen ist der Installer gesperrt, solange <code>storage/config/config.php</code> existiert.</p><p><a href="index.php">Zur Startseite</a> &middot; <a href="admin.php">Zum Orga-Backend</a></p></div></body></html>');
}

// Systemvoraussetzungen prüfen
$checks = [
    'php_version' => [
        'label' => 'PHP-Version >= 8.0 (aktuell: ' . PHP_VERSION . ')',
        'ok'    => version_compare(PHP_VERSION, '8.0.0', '>='),
    ],
    'pdo_mysql' => [
        'label' => 'PDO MySQL-Erweiterung (pdo_mysql)',
        'ok'    => extension_loaded('pdo_mysql'),
    ],
    'openssl' => [
        'label' => 'OpenSSL-Erweiterung (für AES-256-GCM Verschlüsselung sensibler Daten)',
        'ok'    => extension_loaded('openssl') && in_array('aes-256-gcm', openssl_get_cipher_methods(), true),
    ],
    'mbstring' => [
        'label' => 'Multibyte-String-Erweiterung (mbstring)',
        'ok'    => extension_loaded('mbstring'),
    ],
    'storage_writable' => [
        'label' => 'Schreibrechte in storage/config und storage/uploads',
        'ok'    => is_writable(__DIR__ . '/storage/config') && is_writable(__DIR__ . '/storage/uploads'),
    ],
];

$allChecksOk = !in_array(false, array_column($checks, 'ok'), true);
$errors = [];
$success = false;

$form = [
    'db_host'      => $_POST['db_host'] ?? 'localhost',
    'db_port'      => $_POST['db_port'] ?? '3306',
    'db_name'      => $_POST['db_name'] ?? '',
    'db_user'      => $_POST['db_user'] ?? '',
    'db_pass'      => $_POST['db_pass'] ?? '',
    'db_prefix'    => $_POST['db_prefix'] ?? 'con_',
    'site_title'   => $_POST['site_title'] ?? 'Con-Management-System',
    'site_subtitle'=> $_POST['site_subtitle'] ?? 'Online-Anmeldung & Veranstaltungsverwaltung',
    'default_theme'=> $_POST['default_theme'] ?? 'western-seal',
    'mail_from'    => $_POST['mail_from'] ?? ('con@' . ($_SERVER['HTTP_HOST'] ?? 'example.org')),
    'admin_name'   => $_POST['admin_name'] ?? 'Administrator',
    'admin_email'  => $_POST['admin_email'] ?? '',
    'admin_pass'   => $_POST['admin_pass'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $allChecksOk) {
    Auth::requireCsrf();

    $prefix = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$form['db_prefix']);
    if ($prefix === '') {
        $prefix = 'con_';
    }
    $form['db_prefix'] = $prefix;

    if (trim($form['db_name']) === '' || trim($form['db_user']) === '') {
        $errors[] = 'Bitte gib Datenbankname und Datenbank-Benutzer an.';
    }
    if (!filter_var($form['admin_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Bitte gib eine gültige E-Mail-Adresse für den Administrator-Account an.';
    }
    if (strlen((string)$form['admin_pass']) < 8) {
        $errors[] = 'Das Administrator-Passwort muss mindestens 8 Zeichen lang sein.';
    }

    if (empty($errors)) {
        try {
            $pdo = Database::connect([
                'db_host'   => trim($form['db_host']),
                'db_port'   => (int)$form['db_port'],
                'db_name'   => trim($form['db_name']),
                'db_user'   => trim($form['db_user']),
                'db_pass'   => $form['db_pass'],
                'db_prefix' => $prefix,
            ]);

            // Tabellen anlegen
            $queries = [
                "CREATE TABLE IF NOT EXISTS `{$prefix}settings` (
                    `setting_key` VARCHAR(100) NOT NULL PRIMARY KEY,
                    `setting_value` MEDIUMTEXT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                "CREATE TABLE IF NOT EXISTS `{$prefix}users` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `email` VARCHAR(190) NOT NULL UNIQUE,
                    `password_hash` VARCHAR(255) NOT NULL,
                    `name` VARCHAR(150) NOT NULL,
                    `role` ENUM('admin', 'orga') NOT NULL DEFAULT 'orga',
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                "CREATE TABLE IF NOT EXISTS `{$prefix}events` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `title` VARCHAR(200) NOT NULL,
                    `subtitle` VARCHAR(255) NOT NULL DEFAULT '',
                    `start_date` DATE NOT NULL,
                    `end_date` DATE NOT NULL,
                    `countdown_target` DATETIME NULL,
                    `show_on_index` TINYINT(1) NOT NULL DEFAULT 1,
                    `show_countdown` TINYINT(1) NOT NULL DEFAULT 0,
                    `location_prefix` VARCHAR(100) NOT NULL DEFAULT '',
                    `location` VARCHAR(200) NOT NULL DEFAULT '',
                    `location_suffix` VARCHAR(150) NOT NULL DEFAULT '',
                    `maps_url` VARCHAR(500) NOT NULL DEFAULT '',
                    `status` ENUM('open', 'locked') NOT NULL DEFAULT 'open',
                    `unlock_password` VARCHAR(100) NOT NULL DEFAULT '',
                    `is_bilingual` TINYINT(1) NOT NULL DEFAULT 0,
                    `theme` VARCHAR(80) NULL DEFAULT NULL,
                    `header_logo_path` VARCHAR(255) NOT NULL DEFAULT '',
                    `header_text_de` TEXT NULL,
                    `header_text_en` TEXT NULL,
                    `flyer_url` VARCHAR(500) NOT NULL DEFAULT '',
                    `forum_url` VARCHAR(500) NOT NULL DEFAULT '',
                    `admin_email` VARCHAR(190) NOT NULL DEFAULT '',
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                "CREATE TABLE IF NOT EXISTS `{$prefix}event_groups` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `event_id` INT UNSIGNED NULL DEFAULT NULL,
                    `name_de` VARCHAR(120) NOT NULL,
                    `name_en` VARCHAR(120) NOT NULL DEFAULT '',
                    `max_seats` INT UNSIGNED NOT NULL DEFAULT 0,
                    `manual_free_override` INT NULL DEFAULT NULL,
                    `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
                    `show_on_index` TINYINT(1) NOT NULL DEFAULT 1,
                    `sort_order` INT NOT NULL DEFAULT 10,
                    INDEX `idx_event_groups_event` (`event_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                "CREATE TABLE IF NOT EXISTS `{$prefix}form_fields` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `event_id` INT UNSIGNED NULL DEFAULT NULL,
                    `field_key` VARCHAR(80) NOT NULL,
                    `field_type` VARCHAR(40) NOT NULL DEFAULT 'text',
                    `label_de` VARCHAR(255) NOT NULL,
                    `label_en` VARCHAR(255) NOT NULL DEFAULT '',
                    `help_de` TEXT NULL,
                    `help_en` TEXT NULL,
                    `options_json` TEXT NULL,
                    `is_required` TINYINT(1) NOT NULL DEFAULT 0,
                    `is_sensitive` TINYINT(1) NOT NULL DEFAULT 0,
                    `is_orga_only` TINYINT(1) NOT NULL DEFAULT 0,
                    `system_mapping` VARCHAR(40) NOT NULL DEFAULT 'none',
                    `sort_order` INT NOT NULL DEFAULT 10,
                    INDEX `idx_form_fields_event` (`event_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                "CREATE TABLE IF NOT EXISTS `{$prefix}registrations` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `event_id` INT UNSIGNED NOT NULL,
                    `group_id` INT UNSIGNED NOT NULL,
                    `email` VARCHAR(190) NOT NULL,
                    `first_name` VARCHAR(120) NOT NULL DEFAULT '',
                    `last_name` VARCHAR(120) NOT NULL DEFAULT '',
                    `birth_date` DATE NULL DEFAULT NULL,
                    `doi_token` VARCHAR(80) NOT NULL DEFAULT '',
                    `is_confirmed` TINYINT(1) NOT NULL DEFAULT 0,
                    `is_orga_confirmed` TINYINT(1) NOT NULL DEFAULT 0,
                    `amount_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `is_fully_paid` TINYINT(1) NOT NULL DEFAULT 0,
                    `language` VARCHAR(5) NOT NULL DEFAULT 'de',
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_reg_event` (`event_id`),
                    INDEX `idx_reg_email` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                "CREATE TABLE IF NOT EXISTS `{$prefix}registration_values` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `registration_id` INT UNSIGNED NOT NULL,
                    `field_id` INT UNSIGNED NOT NULL,
                    `field_value` MEDIUMTEXT NULL,
                    `is_encrypted` TINYINT(1) NOT NULL DEFAULT 0,
                    UNIQUE KEY `uq_reg_field` (`registration_id`, `field_id`),
                    INDEX `idx_regval_field` (`field_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                "CREATE TABLE IF NOT EXISTS `{$prefix}saved_views` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `event_id` INT UNSIGNED NULL DEFAULT NULL,
                    `name` VARCHAR(150) NOT NULL,
                    `config_json` TEXT NOT NULL,
                    `sort_order` INT NOT NULL DEFAULT 10,
                    INDEX `idx_views_event` (`event_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
            ];

            foreach ($queries as $q) {
                $pdo->exec($q);
            }

            // Config & Verschlüsselungs-Key speichern
            Crypto::generateKeyFile();
            Config::save([
                'db_host'      => trim($form['db_host']),
                'db_port'      => (int)$form['db_port'],
                'db_name'      => trim($form['db_name']),
                'db_user'      => trim($form['db_user']),
                'db_pass'      => $form['db_pass'],
                'db_prefix'    => $prefix,
                'installed_at' => date('c'),
            ]);

            // Standard-Settings eintragen
            $defaultSettings = [
                'db_version'        => \App\Core\Version::CURRENT,
                'site_title'        => trim($form['site_title']),
                'site_subtitle'     => trim($form['site_subtitle']),
                'default_theme'     => $form['default_theme'],
                'mail_from_address' => trim($form['mail_from']),
                'mail_from_name'    => trim($form['site_title']),
                'mail_transport'    => 'mail',
                'main_site_url'     => '',
                'forum_url'         => '',
                'privacy_url'       => '',
                'imprint_url'       => '',
                // Standard E-Mail-Vorlagen (DE & EN)
                'mail_tpl_doi_subject_de' => 'Bitte bestätige deine Anmeldung für {conname}',
                'mail_tpl_doi_body_de'    => "<p>Hallo {vorname} {nachname},</p><p>vielen Dank für deine Anmeldung zur Veranstaltung <b>{conname}</b> ({condatum}) als <b>{gruppe}</b>.</p><p>Damit deine Anmeldung gültig wird, klicke bitte auf folgenden Bestätigungslink:</p><p><a href=\"{link}\">{link}</a></p><p>Viele Grüße<br>Deine Orga</p>",
                'mail_tpl_doi_subject_en' => 'Please confirm your registration for {conname}',
                'mail_tpl_doi_body_en'    => "<p>Hello {vorname} {nachname},</p><p>thank you for registering for <b>{conname}</b> ({condatum}) as <b>{gruppe}</b>.</p><p>To complete your registration, please click the confirmation link below:</p><p><a href=\"{link}\">{link}</a></p><p>Best regards<br>The Organizers</p>",

                'mail_tpl_confirm_subject_de' => 'Anmeldebestätigung für {conname}',
                'mail_tpl_confirm_body_de'    => "<p>Hallo {vorname} {nachname},</p><p>deine Anmeldung für <b>{conname}</b> ({condatum}) als <b>{gruppe}</b> wurde von der Orga geprüft und bestätigt.</p><p>Bitte überweise deinen Teilnahmebeitrag unter Angabe von „{conname} – {vorname} {nachname}“.</p><p>Viele Grüße<br>Deine Orga</p>",
                'mail_tpl_confirm_subject_en' => 'Registration Confirmed for {conname}',
                'mail_tpl_confirm_body_en'    => "<p>Hello {vorname} {nachname},</p><p>your registration for <b>{conname}</b> ({condatum}) as <b>{gruppe}</b> has been confirmed by the organizers.</p><p>Best regards<br>The Organizers</p>",

                'mail_tpl_reminder_subject_de' => 'Zahlungserinnerung für {conname}',
                'mail_tpl_reminder_body_de'    => "<p>Hallo {vorname} {nachname},</p><p>wir möchten dich freundlich daran erinnern, dass für deine Anmeldung zu <b>{conname}</b> ({condatum}) der Teilnahmebeitrag noch offen ist (bisher verbucht: {betrag} €).</p><p>Falls sich deine Überweisung mit dieser E-Mail überschnitten hat, betrachte diese Nachricht bitte als gegenstandslos.</p><p>Viele Grüße<br>Deine Orga</p>",
                'mail_tpl_reminder_subject_en' => 'Payment Reminder for {conname}',
                'mail_tpl_reminder_body_en'    => "<p>Hello {vorname} {nachname},</p><p>this is a friendly reminder regarding your open participation fee for <b>{conname}</b> ({condatum}) (amount recorded so far: {betrag} €).</p><p>Best regards<br>The Organizers</p>",

                'mail_tpl_paid_subject_de' => 'Geldeingangsbestätigung für {conname}',
                'mail_tpl_paid_body_de'    => "<p>Hallo {vorname} {nachname},</p><p>wir haben deine Zahlung ({betrag} €) für <b>{conname}</b> ({condatum}) erhalten. Dein Platz als <b>{gruppe}</b> ist damit fest gebucht!</p><p>Wir freuen uns auf dich!<br>Deine Orga</p>",
                'mail_tpl_paid_subject_en' => 'Payment Received for {conname}',
                'mail_tpl_paid_body_en'    => "<p>Hello {vorname} {nachname},</p><p>we have received your payment ({betrag} €) for <b>{conname}</b> ({condatum}). Your spot as <b>{gruppe}</b> is now fully booked!</p><p>Best regards<br>The Organizers</p>",
            ];

            foreach ($defaultSettings as $k => $v) {
                Database::setSetting($k, (string)$v);
            }

            // Ersten Admin-Benutzer anlegen
            $stmtUser = $pdo->prepare("INSERT INTO `{$prefix}users` (email, password_hash, name, role, is_active) VALUES (:email, :hash, :name, 'admin', 1)");
            $stmtUser->execute([
                'email' => trim($form['admin_email']),
                'hash'  => password_hash((string)$form['admin_pass'], PASSWORD_DEFAULT),
                'name'  => trim($form['admin_name']),
            ]);

            // Globale Standard-Teilnehmergruppen (event_id = NULL) anlegen: SC, NSC, Team
            $defaultGroups = [
                ['SC (Spielercharakter)', 'PC (Player Character)', 60, 1, 10],
                ['NSC (Nichtspielercharakter)', 'NPC (Non-Player Character)', 30, 1, 20],
                ['Team / Orga / Helfer', 'Staff / Crew', 0, 0, 30],
            ];
            $stmtGroup = $pdo->prepare("INSERT INTO `{$prefix}event_groups` (event_id, name_de, name_en, max_seats, show_on_index, sort_order) VALUES (NULL, ?, ?, ?, ?, ?)");
            foreach ($defaultGroups as $g) {
                $stmtGroup->execute($g);
            }

            // Globale Standard-Formularfelder (event_id = NULL) anlegen
            $defaultFields = [
                // [field_key, field_type, label_de, label_en, help_de, help_en, options_json, is_required, is_sensitive, is_orga_only, system_mapping, sort_order]
                ['h_personal', 'heading', 'Persönliche Daten', 'Personal Information', '', '', null, 0, 0, 0, 'none', 10],
                ['vorname', 'text', 'Vorname', 'First Name', '', '', null, 1, 0, 0, 'firstname', 20],
                ['nachname', 'text', 'Nachname', 'Last Name', '', '', null, 1, 0, 0, 'lastname', 30],
                ['email', 'email', 'E-Mail-Adresse', 'Email Address', 'Für die Bestätigungsmail und Rückfragen der Orga.', 'For the confirmation link and updates from the organizers.', null, 1, 0, 0, 'email', 40],
                ['strasse', 'text', 'Straße & Hausnummer', 'Street & Number', '', '', null, 1, 0, 0, 'none', 50],
                ['plz', 'text', 'Postleitzahl', 'Postal Code', '', '', null, 1, 0, 0, 'none', 60],
                ['ort', 'text', 'Wohnort', 'City', '', '', null, 1, 0, 0, 'none', 70],
                ['geburtsdatum', 'date', 'Geburtsdatum', 'Date of Birth', 'Wird für die Altersprüfung zum Veranstaltungsbeginn benötigt.', 'Required for age verification at the start of the event.', null, 1, 0, 0, 'birthdate', 80],
                ['geschlecht', 'select', 'Geschlecht', 'Gender', '', '', json_encode(['de' => ['Weiblich', 'Männlich', 'Divers', 'Keine Angabe'], 'en' => ['Female', 'Male', 'Non-binary / Diverse', 'Prefer not to say']], JSON_UNESCAPED_UNICODE), 0, 0, 0, 'none', 90],
                ['telefon', 'text', 'Telefon / Mobilnummer', 'Phone / Mobile', '', '', null, 0, 0, 0, 'none', 100],

                ['h_health', 'heading', 'Verpflegung, Gesundheit & Notfallinfos', 'Dietary, Health & Medical Information', '', '', null, 0, 0, 0, 'none', 110],
                ['verpflegung', 'select', 'Essgewohnheiten', 'Dietary Preferences', '', '', json_encode(['de' => ['Allesesser', 'Vegetarisch', 'Vegan'], 'en' => ['Omnivore', 'Vegetarian', 'Vegan']], JSON_UNESCAPED_UNICODE), 0, 0, 0, 'none', 120],
                ['medausbildung', 'select', 'Medizinische Ausbildung', 'Medical Training', '', '', json_encode(['de' => ['Keine', 'Ersthelfer', 'Sanitäter', 'Rettungsassistent / Notfallsanitäter', 'Mediziner / Arzt'], 'en' => ['None', 'First Aider', 'Paramedic / EMT', 'Physician / Doctor']], JSON_UNESCAPED_UNICODE), 0, 0, 0, 'none', 130],
                ['allergien', 'textarea', 'Allergien & Unverträglichkeiten', 'Allergies & Intolerances', '', '', null, 0, 1, 0, 'none', 140],
                ['krankheiten', 'textarea', 'Wichtige Krankheiten / Medikamente / Phobien', 'Medical Conditions / Medication / Phobias', 'Wird verschlüsselt gespeichert und nach der Veranstaltung gelöscht.', 'Stored encrypted and deleted after the event.', null, 0, 1, 0, 'none', 150],
                ['schwanger', 'checkbox', 'Ich bin schwanger', 'I am pregnant', '', '', null, 0, 1, 0, 'none', 160],

                ['h_char', 'heading', 'Charakter- & Spielinformationen', 'Character & Game Information', '', '', null, 0, 0, 0, 'none', 170],
                ['charaktername', 'text', 'Charaktername (für SC) bzw. NSC-Wunschrolle', 'Character Name (for PC) or preferred NPC role', '', '', null, 0, 0, 0, 'none', 180],
                ['klasse', 'text', 'Charakterklasse / Konzept', 'Character Class / Concept', '', '', null, 0, 0, 0, 'none', 190],
                ['spielergruppe', 'text', 'Spielergruppe / Lager / Siegel', 'Player Group / Faction', '', '', null, 0, 0, 0, 'none', 200],
                ['contage', 'number', 'Bisherige Contage des Charakters', 'Character Experience (Con Days)', '', '', null, 0, 0, 0, 'none', 210],
                ['magiekundig', 'checkbox', 'Charakter ist magiekundig / wirkt Rituale', 'Character uses magic / rituals', '', '', null, 0, 0, 0, 'none', 220],
                ['alchemist', 'checkbox', 'Charakter ist Alchemist / Giftkundig', 'Character is an alchemist / herbalist', '', '', null, 0, 0, 0, 'none', 230],
                ['dieben', 'checkbox', 'Charakter betreibt Diebstahl / Unterwelt-Spiel', 'Character engages in thievery / underworld play', '', '', null, 0, 0, 0, 'none', 240],
                ['artefakte', 'textarea', 'Artefakte, Besonderheiten &NSC-Ausrüstung', 'Artifacts, Special Items & NPC Gear', '', '', null, 0, 0, 0, 'none', 250],

                ['h_orga', 'heading', 'Anreise & Sonstiges', 'Travel & Miscellaneous', '', '', null, 0, 0, 0, 'none', 260],
                ['kennzeichen', 'text', 'KFZ-Kennzeichen (falls Anreise mit PKW)', 'License Plate (if arriving by car)', '', '', null, 0, 0, 0, 'none', 270],
                ['bahnhof_abholung', 'text', 'Bahnhof-Abholung / Ankunftszeit Zug', 'Train Station Pickup / Arrival Time', '', '', null, 0, 0, 0, 'none', 280],
                ['bemerkungen', 'textarea', 'Bemerkungen / Mitteilungen an die Orga', 'Remarks / Notes for the Organizers', '', '', null, 0, 0, 0, 'none', 290],
                ['agb_akzeptiert', 'document', 'Teilnahmebedingungen (AGB) & Datenschutzerklärung', 'Terms & Conditions and Privacy Policy', 'Bitte bestätige unsere Teilnahmebedingungen und Datenschutzhinweise.', 'Please accept our terms of participation and privacy policy.', json_encode(['file_path' => '', 'link_label_de' => 'Teilnahmebedingungen & Datenschutz', 'link_label_en' => 'Terms & Privacy Policy'], JSON_UNESCAPED_UNICODE), 1, 0, 0, 'none', 300],

                // Standard-Orga-Interne Felder (is_orga_only = 1, is_required = 0)
                ['orga_zimmer', 'text', 'Zimmer- / Unterkunftszuweisung', 'Room Assignment', 'Internes Orga-Feld', '', null, 0, 0, 1, 'none', 310],
                ['orga_helfer', 'select', 'Aufbau- / Abbauhelfer', 'Setup / Teardown Helper', 'Internes Orga-Feld', '', json_encode(['de' => ['Nein', 'Aufbauhelfer', 'Abbauhelfer', 'Auf- und Abbauhelfer'], 'en' => ['No', 'Setup', 'Teardown', 'Both']], JSON_UNESCAPED_UNICODE), 0, 0, 1, 'none', 320],
                ['orga_notizen', 'textarea', 'Interne Orga- & SL-Notizen', 'Internal Staff & GM Notes', 'Nur für Orga/SL im Backend sichtbar', '', null, 0, 0, 1, 'none', 330],
            ];

            $stmtField = $pdo->prepare("INSERT INTO `{$prefix}form_fields`
                (event_id, field_key, field_type, label_de, label_en, help_de, help_en, options_json, is_required, is_sensitive, is_orga_only, system_mapping, sort_order)
                VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($defaultFields as $f) {
                $stmtField->execute($f);
            }

            // Globale Standard-Listen-Vorlagen (event_id = NULL) anlegen
            $defaultViews = [
                [
                    'Minderjährige Teilnehmer (U18 zum Con-Start)',
                    json_encode([
                        'special_filter' => 'under18',
                        'group_id'       => '',
                        'payment_status' => '',
                        'field_filter'   => '',
                        'field_operator' => 'not_empty',
                        'field_value'    => '',
                        'columns'        => ['group', 'birth_date', 'age_at_event', 'telefon', 'bemerkungen'],
                    ], JSON_UNESCAPED_UNICODE),
                    10,
                ],
                [
                    'Offene Zahlungen (Noch nicht vollständig bezahlt)',
                    json_encode([
                        'special_filter' => '',
                        'group_id'       => '',
                        'payment_status' => 'unpaid',
                        'field_filter'   => '',
                        'field_operator' => 'not_empty',
                        'field_value'    => '',
                        'columns'        => ['group', 'email', 'amount_paid', 'is_fully_paid', 'created_at'],
                    ], JSON_UNESCAPED_UNICODE),
                    20,
                ],
                [
                    'Küche & Verpflegung (Essgewohnheiten & Allergien)',
                    json_encode([
                        'special_filter' => '',
                        'group_id'       => '',
                        'payment_status' => '',
                        'field_filter'   => '',
                        'field_operator' => 'not_empty',
                        'field_value'    => '',
                        'columns'        => ['group', 'verpflegung', 'allergien'],
                    ], JSON_UNESCAPED_UNICODE),
                    30,
                ],
                [
                    'Sanitäter & Gesundheit (Med. Ausbildung, Krankheiten, Allergien)',
                    json_encode([
                        'special_filter' => '',
                        'group_id'       => '',
                        'payment_status' => '',
                        'field_filter'   => '',
                        'field_operator' => 'not_empty',
                        'field_value'    => '',
                        'columns'        => ['group', 'medausbildung', 'allergien', 'krankheiten', 'schwanger'],
                    ], JSON_UNESCAPED_UNICODE),
                    40,
                ],
                [
                    'SL-Charakterübersicht (Charaktere, Fähigkeiten & Artefakte)',
                    json_encode([
                        'special_filter' => '',
                        'group_id'       => '',
                        'payment_status' => '',
                        'field_filter'   => '',
                        'field_operator' => 'not_empty',
                        'field_value'    => '',
                        'columns'        => ['group', 'charaktername', 'klasse', 'spielergruppe', 'contage', 'magiekundig', 'alchemist', 'dieben', 'artefakte', 'orga_notizen'],
                    ], JSON_UNESCAPED_UNICODE),
                    50,
                ],
                [
                    'Check-In, Zimmer & Logistik (KFZ, Bahnhof, Helfer)',
                    json_encode([
                        'special_filter' => '',
                        'group_id'       => '',
                        'payment_status' => '',
                        'field_filter'   => '',
                        'field_operator' => 'not_empty',
                        'field_value'    => '',
                        'columns'        => ['group', 'amount_paid', 'is_fully_paid', 'orga_zimmer', 'orga_helfer', 'kennzeichen', 'bahnhof_abholung'],
                    ], JSON_UNESCAPED_UNICODE),
                    60,
                ],
            ];

            $stmtView = $pdo->prepare("INSERT INTO `{$prefix}saved_views` (event_id, name, config_json, sort_order) VALUES (NULL, ?, ?, ?)");
            foreach ($defaultViews as $v) {
                $stmtView->execute($v);
            }

            $success = true;
        } catch (Throwable $e) {
            $errors[] = 'Datenbankfehler: ' . $e->getMessage();
        }
    }
}

$themes = Theme::availableThemes();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation – Con-Management-System</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: #eaf7cb;
            color: #1e2920;
            padding: 2rem 1rem;
            line-height: 1.5;
        }
        .wizard {
            max-width: 760px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #adc793;
            border-radius: 10px;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        .wizard-header {
            background: #243b29;
            color: #ffffff;
            padding: 1.5rem 2rem;
            border-bottom: 4px solid #739757;
        }
        .wizard-header h1 { margin: 0 0 0.25rem 0; font-size: 1.5rem; }
        .wizard-header p { margin: 0; opacity: 0.85; font-size: 0.95rem; }
        .wizard-body { padding: 2rem; }
        h2 {
            font-size: 1.15rem;
            color: #255E00;
            border-bottom: 2px solid #e7f4c8;
            padding-bottom: 0.4rem;
            margin: 1.75rem 0 1rem 0;
        }
        h2:first-child { margin-top: 0; }
        .check-list { list-style: none; padding: 0; margin: 0 0 1.5rem 0; }
        .check-list li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.55rem 0.85rem;
            border-radius: 6px;
            margin-bottom: 0.4rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: 0.93rem;
        }
        .badge-ok { background: #dcfce7; color: #166534; padding: 0.15rem 0.6rem; border-radius: 99px; font-weight: 600; font-size: 0.8rem; }
        .badge-err { background: #fee2e2; color: #991b1b; padding: 0.15rem 0.6rem; border-radius: 99px; font-weight: 600; font-size: 0.8rem; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.3rem; }
        input, select {
            width: 100%;
            padding: 0.55rem 0.75rem;
            border: 1px solid #94a3b8;
            border-radius: 6px;
            font-size: 0.95rem;
        }
        input:focus, select:focus { outline: none; border-color: #255E00; box-shadow: 0 0 0 3px rgba(37,94,0,.15); }
        .btn {
            display: inline-block;
            background: #255E00;
            color: #fff;
            font-weight: 600;
            font-size: 1rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn:hover { background: #194000; }
        .alert-err { background: #fef2f2; border: 1px solid #f87171; color: #991b1b; padding: 1rem; border-radius: 6px; margin-bottom: 1.25rem; }
        .alert-ok { background: #f0fdf4; border: 1px solid #4ade80; color: #166534; padding: 1.5rem; border-radius: 8px; }
        @media (max-width: 600px) { .grid-2 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="wizard">
    <div class="wizard-header">
        <h1>Con-Management-System – Setup-Assistent</h1>
        <p>Einrichtung der Datenbank, DSGVO-Verschlüsselung & Standard-Vorlagen</p>
    </div>
    <div class="wizard-body">
        <?php if ($success): ?>
            <div class="alert-ok">
                <h2 style="margin-top:0;border:none;">🎉 Installation erfolgreich abgeschlossen!</h2>
                <p>Alle Datenbanktabellen, der <code>AES-256-GCM</code> Verschlüsselungsschlüssel für sensible Teilnehmerdaten sowie die Standard-Vorlagen (Felder, Teilnehmergruppen SC/NSC/Team und Auswertungslisten) wurden eingerichtet.</p>
                <p>Der Installer ist ab sofort automatisch gesperrt.</p>
                <p style="margin-top:1.5rem;">
                    <a href="admin.php" class="btn">Jetzt im Orga-Backend anmelden &rarr;</a>
                    <a href="index.php" style="margin-left:1rem;color:#255E00;font-weight:600;">Zur öffentlichen Startseite</a>
                </p>
            </div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="alert-err">
                    <strong>Bitte korrigiere folgende Fehler:</strong>
                    <ul style="margin:0.5rem 0 0 1.2rem;padding:0;">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <h2>1. Systemvoraussetzungen</h2>
            <ul class="check-list">
                <?php foreach ($checks as $c): ?>
                    <li>
                        <span><?= e($c['label']) ?></span>
                        <?php if ($c['ok']): ?>
                            <span class="badge-ok">OK</span>
                        <?php else: ?>
                            <span class="badge-err">Fehlt</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($allChecksOk): ?>
                <form method="post" action="install.php">
                    <?= Auth::csrfField() ?>

                    <h2>2. MySQL / MariaDB Datenbank</h2>
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="db_host">Datenbank-Host</label>
                            <input type="text" id="db_host" name="db_host" value="<?= e($form['db_host']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="db_port">Port</label>
                            <input type="number" id="db_port" name="db_port" value="<?= e($form['db_port']) ?>" required>
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="db_name">Datenbank-Name</label>
                            <input type="text" id="db_name" name="db_name" value="<?= e($form['db_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="db_prefix">Tabellen-Präfix</label>
                            <input type="text" id="db_prefix" name="db_prefix" value="<?= e($form['db_prefix']) ?>" required>
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="db_user">Datenbank-Benutzer</label>
                            <input type="text" id="db_user" name="db_user" value="<?= e($form['db_user']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="db_pass">Datenbank-Passwort</label>
                            <input type="password" id="db_pass" name="db_pass" value="<?= e($form['db_pass']) ?>">
                        </div>
                    </div>

                    <h2>3. Grundeinstellungen & Design</h2>
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="site_title">Name der Webseite / Orga</label>
                            <input type="text" id="site_title" name="site_title" value="<?= e($form['site_title']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="site_subtitle">Untertitel</label>
                            <input type="text" id="site_subtitle" name="site_subtitle" value="<?= e($form['site_subtitle']) ?>">
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="default_theme">Globales Standard-Theme (pro Veranstaltung überschreibbar)</label>
                            <select id="default_theme" name="default_theme">
                                <?php foreach ($themes as $slug => $tMeta): ?>
                                    <option value="<?= e($slug) ?>" <?= $form['default_theme'] === $slug ? 'selected' : '' ?>>
                                        <?= e($tMeta['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="mail_from">Absender-E-Mail für Systemmails</label>
                            <input type="email" id="mail_from" name="mail_from" value="<?= e($form['mail_from']) ?>" required>
                        </div>
                    </div>

                    <h2>4. Erster Administrator-Zugang</h2>
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="admin_name">Name des Administrators</label>
                            <input type="text" id="admin_name" name="admin_name" value="<?= e($form['admin_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="admin_email">Login-E-Mail-Adresse</label>
                            <input type="email" id="admin_email" name="admin_email" value="<?= e($form['admin_email']) ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="admin_pass">Passwort (mindestens 8 Zeichen)</label>
                        <input type="password" id="admin_pass" name="admin_pass" minlength="8" required>
                    </div>

                    <div style="margin-top:1.75rem;">
                        <button type="submit" class="btn">System jetzt installieren & einrichten</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert-err">
                    Bitte behebe zuerst die fehlenden Systemvoraussetzungen und lade diese Seite neu.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
