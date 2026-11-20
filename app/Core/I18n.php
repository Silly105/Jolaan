<?php
declare(strict_types=1);

namespace App\Core;

class I18n
{
    private static string $lang = 'de';

    private static array $strings = [
        'de' => [
            'nav.home'                => 'Startseite',
            'nav.main_site'           => 'Hauptseite',
            'nav.forum'               => 'Forum',
            'nav.privacy'             => 'Datenschutz',
            'nav.imprint'             => 'Impressum',
            'nav.orga_login'          => 'Orga-Login',
            'nav.admin_area'          => 'Orga-Backend',
            'nav.logout'              => 'Abmelden',
            'index.welcome_title'     => 'Veranstaltungsübersicht',
            'index.running_events'    => 'Aktuell laufende Veranstaltungen',
            'index.upcoming_events'   => 'Kommende Veranstaltungen',
            'index.no_events'         => 'Derzeit stehen keine neuen Veranstaltungstermine fest.',
            'index.free_seats'        => 'freie Plätze',
            'index.waitlist_full'     => 'Ausgebucht / Warteliste',
            'index.to_flyer'          => 'Flyer',
            'index.to_registration'   => 'Zur Online-Anmeldung',
            'index.to_forum'          => 'Forum',
            'index.reg_locked'        => 'Anmeldung derzeit geschlossen',
            'index.countdown_prefix'  => 'Noch',
            'index.days'              => 'Tage',
            'index.hours'             => 'Stunden',
            'index.minutes'           => 'Minuten',
            'index.seconds'           => 'Sekunden',
            'reg.title'               => 'Online-Anmeldung',
            'reg.contact_prefix'      => 'Bei Fragen zur Anmeldung und der Veranstaltung:',
            'reg.select_group'        => 'Anmelden als (Teilnehmergruppe)',
            'reg.please_select'       => '— Bitte wählen —',
            'reg.group_full'          => '(Ausgebucht)',
            'reg.group_waitlist'      => '(Ausgebucht – Anmeldung auf Warteliste möglich)',
            'reg.waitlist_warning'    => '⚠️ Hinweis zur Warteliste: Die gewählte Teilnehmergruppe ist aktuell ausgebucht. Deine Anmeldung erfolgt daher für die Warteliste. Maßgeblich für deinen Wartelistenplatz ist der Zeitpunkt deiner E-Mail-Bestätigung.',
            'reg.required_hint'       => 'Alle mit * markierten Felder sind Pflichtfelder.',
            'reg.encrypted_hint'      => 'Wird gemäß DSGVO verschlüsselt (AES-256-GCM) in der Datenbank gespeichert.',
            'reg.sensitive_hint'      => 'Sensible Daten: Werden gemäß DSGVO verschlüsselt gespeichert und nach der Veranstaltung gelöscht.',
            'reg.doc_open'            => 'Dokument öffnen / herunterladen',
            'reg.doc_accept'          => 'Ich habe das Dokument gelesen und akzeptiere es.',
            'reg.submit_btn'          => 'Verbindlich anmelden',
            'reg.error_heading'       => 'Bitte überprüfe folgende Eingaben:',
            'reg.error_required'      => 'Das Feld „%s“ ist ein Pflichtfeld.',
            'reg.error_email'         => 'Bitte gib eine gültige E-Mail-Adresse ein.',
            'reg.error_duplicate'     => 'Mit dieser E-Mail-Adresse liegt bereits eine Anmeldung für diese Veranstaltung vor.',
            'reg.error_group'         => 'Bitte wähle eine verfügbare Teilnehmergruppe aus.',
            'reg.error_bot'           => 'Spam-Schutz-Prüfung fehlgeschlagen. Bitte sende das Formular erneut ab.',
            'reg.locked_title'        => 'Anmeldung geschlossen',
            'reg.locked_text'         => 'Für diese Veranstaltung ist die Online-Anmeldung derzeit nicht geöffnet.',
            'reg.unlock_prompt'       => 'Hast du von der Orga ein Entsperrungspasswort erhalten?',
            'reg.unlock_btn'          => 'Freischalten',
            'reg.success_title'       => 'Fast geschafft – Bitte führe die E-Mail-Bestätigung durch!',
            'reg.success_text'        => 'Vielen Dank für deine Anmeldung! Wir haben dir soeben eine E-Mail mit einem Bestätigungslink geschickt. Bitte klicke auf den Link in der E-Mail (prüfe ggf. auch deinen Spam-Ordner), damit deine Anmeldung wirksam wird.',
            'reg.doi_ok_title'        => 'E-Mail-Bestätigung erfolgreich!',
            'reg.doi_ok_text'         => 'Hallo %s, deine E-Mail-Adresse wurde bestätigt und deine Anmeldung für „%s“ ist nun bei der Orga eingegangen.',
            'reg.doi_waitlist_text'   => 'Hallo %s, deine E-Mail-Adresse wurde bestätigt! Da die gewählte Teilnehmergruppe für „%s“ zum Zeitpunkt deiner E-Mail-Bestätigung bereits ausgebucht war, wurde deine Anmeldung auf die Warteliste (aktuell Platz #%d) gesetzt.',
            'reg.doi_already_title'   => 'Bereits bestätigt',
            'reg.doi_already_text'    => 'Deine E-Mail-Bestätigung wurde bereits zuvor abgeschlossen.',
            'reg.doi_invalid_title'   => 'Ungültiger Bestätigungslink',
            'reg.doi_invalid_text'    => 'Der Link zur E-Mail-Bestätigung ist ungültig oder unvollständig.',
        ],
        'en' => [
            'nav.home'                => 'Home',
            'nav.main_site'           => 'Main Website',
            'nav.forum'               => 'Forum',
            'nav.privacy'             => 'Privacy Policy',
            'nav.imprint'             => 'Legal Notice',
            'nav.orga_login'          => 'Staff Login',
            'nav.admin_area'          => 'Staff Backend',
            'nav.logout'              => 'Log out',
            'index.welcome_title'     => 'Upcoming Events',
            'index.running_events'    => 'Currently Running Events',
            'index.upcoming_events'   => 'Upcoming Events',
            'index.no_events'         => 'There are currently no upcoming events scheduled.',
            'index.free_seats'        => 'available spots',
            'index.waitlist_full'     => 'Fully booked / Waiting list',
            'index.to_flyer'          => 'Flyer',
            'index.to_registration'   => 'Online Registration',
            'index.to_forum'          => 'Forum',
            'index.reg_locked'        => 'Registration currently closed',
            'index.countdown_prefix'  => 'Starts in',
            'index.days'              => 'days',
            'index.hours'             => 'hours',
            'index.minutes'           => 'minutes',
            'index.seconds'           => 'seconds',
            'reg.title'               => 'Online Registration',
            'reg.contact_prefix'      => 'For questions regarding registration and the event:',
            'reg.select_group'        => 'Register as (Participant Group)',
            'reg.please_select'       => '— Please select —',
            'reg.group_full'          => '(Fully booked)',
            'reg.group_waitlist'      => '(Fully booked – Waiting list registration available)',
            'reg.waitlist_warning'    => '⚠️ Waiting List Notice: The selected participant group is currently fully booked. Your registration will therefore be placed on the waiting list. The exact time of your email confirmation determines your waiting list position.',
            'reg.required_hint'       => 'All fields marked with * are required.',
            'reg.encrypted_hint'      => 'Stored encrypted (AES-256-GCM) in the database in compliance with GDPR.',
            'reg.sensitive_hint'      => 'Sensitive data: Stored encrypted in compliance with GDPR and deleted after the event.',
            'reg.doc_open'            => 'Open / download document',
            'reg.doc_accept'          => 'I have read and accept the document.',
            'reg.submit_btn'          => 'Submit binding registration',
            'reg.error_heading'       => 'Please check the following inputs:',
            'reg.error_required'      => 'The field "%s" is required.',
            'reg.error_email'         => 'Please enter a valid email address.',
            'reg.error_duplicate'     => 'A registration with this email address already exists for this event.',
            'reg.error_group'         => 'Please select an available participant group.',
            'reg.error_bot'           => 'Anti-spam check failed. Please submit the form again.',
            'reg.locked_title'        => 'Registration Closed',
            'reg.locked_text'         => 'Online registration for this event is currently closed.',
            'reg.unlock_prompt'       => 'Did you receive an unlock password from the organizers?',
            'reg.unlock_btn'          => 'Unlock',
            'reg.success_title'       => 'Almost done – Please complete the email confirmation!',
            'reg.success_text'        => 'Thank you for registering! We have just sent you an email with a confirmation link. Please click the link in that email (check your spam folder if necessary) to complete your registration.',
            'reg.doi_ok_title'        => 'Email Confirmation Successful!',
            'reg.doi_ok_text'         => 'Hello %s, your email address has been verified and your registration for "%s" has been received by the organizers.',
            'reg.doi_waitlist_text'   => 'Hello %s, your email address has been verified! Since the selected group for "%s" was already fully booked at the time of your email confirmation, your registration has been placed on the waiting list (currently position #%d).',
            'reg.doi_already_title'   => 'Already Confirmed',
            'reg.doi_already_text'    => 'Your email confirmation has already been completed previously.',
            'reg.doi_invalid_title'   => 'Invalid Confirmation Link',
            'reg.doi_invalid_text'    => 'The email confirmation link is invalid or incomplete.',
        ],
    ];

    public static function init(bool $allowEnglish = true): void
    {
        Auth::initSession();
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['de', 'en'], true)) {
            $_SESSION['lang'] = $_GET['lang'];
        }
        $lang = $_SESSION['lang'] ?? 'de';
        if (!$allowEnglish) {
            $lang = 'de';
        }
        self::$lang = $lang;
    }

    public static function lang(): string
    {
        return self::$lang;
    }

    public static function isEnglish(): bool
    {
        return self::$lang === 'en';
    }

    public static function t(string $key, mixed ...$args): string
    {
        $text = self::$strings[self::$lang][$key] ?? self::$strings['de'][$key] ?? $key;
        return empty($args) ? $text : sprintf($text, ...$args);
    }

    public static function field(array $row, string $baseCol): string
    {
        if (self::$lang === 'en' && !empty($row[$baseCol . '_en'])) {
            return (string)$row[$baseCol . '_en'];
        }
        return (string)($row[$baseCol . '_de'] ?? $row[$baseCol] ?? '');
    }
}
