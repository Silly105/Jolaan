<?php
declare(strict_types=1);

namespace App\Core;

class Version
{
    public const CURRENT = '0.9.008';

    /**
     * Fortlaufende Änderungshistorie & Migrations-Schritte.
     * Ermöglicht beliebige Versionssprünge (z. B. von 0.9.000 direkt auf 0.9.015).
     */
    public static function history(): array
    {
        return [
            '0.9.000' => [
                'date'    => '2026-11-18',
                'title'   => 'Initialer Neustart des modularen Con-Management-Systems',
                'changes' => [
                    '1-Klick Web-Installer mit Systemcheck und Standard-Vorlagen',
                    'Dynamischer Formular-Baukasten inkl. Dokumenten-Upload, Orga-Only-Feldern und AES-256-GCM Verschlüsselung',
                    'Zentrale Übersetzungstabelle (DE/EN), Listen-Generator mit U18-Filter und DSGVO-Center',
                ],
            ],
            '0.9.001' => [
                'date'    => '2026-11-19',
                'title'   => 'Update-Modul, Favicon, Maps-Verlinkung, Theme-Sync & Usability-Verbesserungen',
                'changes' => [
                    'Update- & Migrations-Modul mit Versionsanzeige (v0.9.001) und fortlaufender Änderungshistorie ergänzt',
                    'Globales Theme wird nun nach dem Speichern auch dynamisch auf das Orga-/Admin-Backend angewendet',
                    'Favicon-Upload in den globalen Einstellungen ergänzt (wird im Frontend und Backend eingebunden)',
                    'Sanity-Checks (Dateiformat, max. Dateigröße, max. Bildauflösung) und Format-Hinweise für Header-Logos & Favicon eingebaut',
                    'Neues Feld für Google-Maps-/Karten-Link bei Veranstaltungen (verlinkt im Frontend Veranstaltungsort & Orts-Nachsatz)',
                    '1-Klick-Kopieren-Button direkt in den Textfeldern für Öffentlichen Anmeldelink und Geheim-Link',
                    'Browser-Autofill-Konflikt beim Anlegen neuer Backend-Benutzer behoben',
                    'Navigation bereinigt (Datenschutz/Impressum nur im Footer, Orga-Login nur im Header) und Theme in „Western Forest (Waldgrün)“ umbenannt',
                ],
            ],
            '0.9.002' => [
                'date'    => '2026-11-19',
                'title'   => 'Lokale 2FA (TOTP), E-Mail-/Telefon-Verschlüsselung, Externer Schlüsselpfad, Hilfe-Handbuch & Bugfix',
                'changes' => [
                    'Bugfix (HTTP 500) im Formular-Baukasten beim Bearbeiten von Feldern behoben (doppelter PDO-Parameter :eid)',
                    'Lokale Zwei-Faktor-Authentifizierung (TOTP nach RFC 6238) ohne externe Dienste inkl. 8 Einmal-Notfall-Codes und FTP-Notfall-Reset eingebaut',
                    'Konfigurierbarer Speicherort für die Schlüsseldatei (secret.key) außerhalb des html-root inkl. 1-Klick-Verschiebe-Assistent',
                    'Getrennte Steuerung für „Verschlüsselt in DB speichern“ (standardmäßig aktiv u. a. für E-Mail & Telefon) und „Sensible Daten (nach Con per 1-Klick löschen)“',
                    'Stamm-E-Mail-Adresse in Anmeldungen wird nun ebenfalls AES-256-GCM verschlüsselt gespeichert (mit HMAC-SHA256 Such-Hash für Dublettenprüfung)',
                    'Neue Backend-Seite „Hilfe & Handbuch“ mit verständlichen Anleitungen für das gesamte Team',
                    'Auf der Anmeldeseite: Prominente Anzeige der Orga-Kontaktadresse, global konfigurierbarer Hinweis zum Datenspeicherstandort (DE/EN) und dezentes Schloss-Icon (ohne „DSGVO“-Text)',
                    'Zweisprachig anpassbare Linktexte für Hauptseite und Forum in den globalen Einstellungen; Buttons auf der Startseite verkürzt („Flyer“, „Forum“) und Orga-Button entfernt',
                    'Rolle „Orga / SL“ durchgehend in „SL“ umbenannt (inkl. Deaktivierung des Versions-Links für SL) und Dashboard-Badges bereinigt',
                ],
            ],
            '0.9.003' => [
                'date'    => '2026-11-20',
                'title'   => 'Wartelisten-System, Interne Teilnehmergruppen („Absagen“), Verlaufs-Protokoll & Menü-Überarbeitung',
                'changes' => [
                    'Komplettes Wartelisten-System: Pro Veranstaltung aktivierbar; Zeitpunkt der E-Mail-Bestätigung entscheidet über regulären Platz oder Wartelisten-Rang (Geheim-Link überspringt Warteliste)',
                    'Wartelisten-Verwaltung in der Anmeldeverwaltung inkl. Buttons „Wartelistenplatz bestätigen“ (sendet zweisprachige E-Mail mit aktuellem {wartelistenplatz}) und „Anmeldung freigeben“',
                    'Neue Option „In Anmeldung anzeigen“ bei Teilnehmergruppen für interne Gruppen (Standardgruppe „Absagen“ wird automatisch angelegt)',
                    'Seite „Verfügbare Plätze“ ist nun auch für die Rolle SL freigegeben',
                    'Automatisches, schreibgeschütztes Verlaufs- & Zeitprotokoll pro Teilnehmer (Anmeldung, E-Mail-Bestätigung, Warteliste, Nachrücken, versendete Bestätigungen & Zahlungserinnerungen)',
                    'Menüführung im Backend komplett überarbeitet: Dauerhafte Anzeige der gewählten Veranstaltung als eigene Menü-Kategorie, Entfernung der Emoji-Symbole und oberen Subnav-Buttons, klare Umbenennungen („Abfragefelder“, „Verfügbare Plätze“, „Seiteneinstellungen“ etc.) und durchgängige Bezeichnung „E-Mail-Bestätigung“',
                ],
            ],
            '0.9.004' => [
                'date'    => '2026-11-21',
                'title'   => 'Code-Review, Logik-Korrekturen für Interne Gruppen/Warteliste & Performance-Optimierung',
                'changes' => [
                    'Interne Teilnehmergruppen (z. B. „Absagen“) werden bei „Alle Gruppen“-Auswertungslisten sowie bei den Zählern für bestätigte Teilnehmer und offene Zahlungen nicht mehr fälschlich mitgezählt',
                    'Im Listen-Generator kann nun gezielt auch nach Wartelisten-Teilnehmern gefiltert werden („Nur reguläre Teilnehmer“, „Nur Warteliste“, „Regulär + Warteliste“)',
                    'Doppelte Stammsatz-Felder auf der Seite „Teilnehmer bearbeiten“ zusammengeführt, damit Änderungen an Name, E-Mail oder Geburtsdatum nicht durch untere Formularfelder überschrieben werden',
                    'Rein interne Teilnehmergruppen (ohne Anzeige in Anmeldung und Startseite) in der Übersetzungstabelle von der Übersetzungspflicht ausgenommen',
                    'Startseiten-Badge unterscheidet bei ausgebuchten Gruppen nun zwischen aktivierter Warteliste („Ausgebucht / Warteliste“) und deaktivierter Warteliste („Ausgebucht“)',
                    'Wartelisten-Prüfung bei E-Mail-Bestätigung berücksichtigt nun auch, ob die gesamte Veranstaltung inzwischen gesperrt wurde',
                    'Fehlerprüfung beim Dokumenten-Upload unter „Abfragefelder“, Spaltenbreite users.totp_secret in install.php auf VARCHAR(255) vereinheitlicht und Versions-Caching in config.php ergänzt',
                ],
            ],
            '0.9.005' => [
                'date'    => '2026-11-25',
                'title'   => 'Haushalts-Mehrfachanmeldung („Übernahme“), Nativer SMTP-Mailer + Testmail, 2FA-QR-Code & UI-Bereinigung',
                'changes' => [
                    'Haushalts-/Mehrfachanmeldungen: Dublettenprüfung auf E-Mail + Vorname + Nachname umgestellt; Button „Weitere Person anmelden (Daten übernehmen)“ nach erfolgreicher Anmeldung ergänzt',
                    'Neue Feld-Checkbox „Übernahme“ (keep_on_prefill) unter „Abfragefelder“ und „Feldvorlagen“, um gezielt festzulegen, welche Felder bei einer Folgeanmeldung übernommen werden (standardmäßig aktiv für Nachname, Straße, PLZ/Ort, E-Mail, Telefon)',
                    '100 % nativer SVG-QR-Code-Generator nach ISO/IEC 18004 direkt in PHP für die 2FA-Einrichtung – komplett ohne externe Bibliotheken oder API-Aufrufe',
                    '100 % nativer SMTP-Client (STARTTLS / SSL/TLS / AUTH LOGIN) ohne PHPMailer inkl. Testmail-Versand und Hinweistext zum Standard-Webserver-Versand in den Seiteneinstellungen',
                    'Optische Trennung der Menügruppen im Backend durch horizontale Linien oberhalb und unterhalb jeder Gruppenüberschrift',
                    'Emojis aus sämtlichen Seiten-/Bereichsüberschriften, Tabellen-Spaltenköpfen, Checkbox-Labels und Hilfetexten im Backend entfernt',
                ],
            ],
            '0.9.006' => [
                'date'    => '2026-11-25',
                'title'   => 'Großes Design- & Theme-Update: 9 neue Themes (Schlicht & Genre), 3 Header-Modi & Veranstaltungs-Bannerbild',
                'changes' => [
                    'Drei neue schlichte Farb-Themes inkl. Backend-Farbsynchronisation: „Fire & Ash“ (Rot/Schwarz), „Arctic Storm“ (Blau/Weiß) und „Mystic Twilight“ (Violett)',
                    'Sechs aufwändige Genre- & Erlebnis-Themes mit eigenen Hintergrundtexturen, SVG-Ornamentik und eigenen Gestaltungsparadigmen: „Eisen & Banner“ (Fantasy-Kampf), „Reisechronik & Pergament“ (Fantasy-Abenteuer), „Art Déco & Salon“ (1920er), „Frontier Gazette“ (Western), „Orbital Terminal“ (Sci-Fi Dark HUD) und „Arkham Grimoire“ (Mystery / Cthulhu)',
                    'Drei wählbare Header-Modi (global einstellbar und pro Veranstaltung überschreibbar): Kompakte Kopfleiste, Zentrierter Wappen-/Banner-Header und Großer Hero-Header',
                    'Neues optionales, individuelles Veranstaltungs-Bannerbild (Upload in den Con-Einstellungen), das in voller Formularbreite direkt oberhalb des Einführungstextes über der Anmeldung angezeigt wird',
                    'Übersichtliche Gruppierung der Theme-Auswahl in „Schlichte Farb-Themes“ und „Aufwändige Genre- & Erlebnis-Themes“',
                ],
            ],
            '0.9.007' => [
                'date'    => '2026-11-25',
                'title'   => 'Neue Genre-Themes „Keltensiegel“ & „Tanzball“, CSS-Cache-Busting & UI-Feinschliff',
                'changes' => [
                    'Neues Genre-Theme „Keltensiegel“ (moderne Neuinterpretation des klassischen Keltenknoten-Designs mit Wasserzeichen-Hintergrund, Moosgrün-Verläufen und geflochtenen keltischen SVG-Ornamenten)',
                    'Neues Genre-Theme „Tanzball“ (höfisches Fantasy-/Mittelalter-Ball-Design in Samtrot, Weiß und Blattgold mit Brokat-Hintergrund, Wappen-Ornamentik und goldener Kronen-Zierlinie)',
                    'Automatisches CSS-Cache-Busting (?v=...) für base.css und Theme-Stylesheets sowie abgesicherte Positionierung der Eck-Ornamente eingebaut',
                    'Kategorie-Überschrift in den Theme-Auswahlmenüs auf „Genre-Themes“ gekürzt und Emojis in der Info-Box über dem Anmeldeformular entfernt',
                ],
            ],
            '0.9.008' => [
                'date'    => '2026-11-26',
                'title'   => 'Aktivierung aller Genre-Hintergrundtexturen & Feinschliff „Keltensiegel“ / „Feuervogel“',
                'changes' => [
                    'Relativen CSS-Bildpfad für die Hintergrundtexturen (assets/bg.jpg) in allen 8 Genre-Themes korrigiert, sodass die Texturen nun überall sichtbar geladen werden',
                    'Theme „Keltensiegel“ in „Keltensiegel (Mittelalter / Fantasy – Feiern)“ umbenannt und obere Eckknoten sauber im hellgrünen Inhaltsbereich unterhalb der Kopfleiste ausgerichtet',
                    'Theme „Tanzball“ in „Feuervogel (Mittelalter / Fantasy – Tanzball)“ umbenannt: purpurroter Damast-Brokat im Header kombiniert mit hellem Perlweiß-/Seiden-Hintergrund hinter dem Anmeldeformular',
                ],
            ],
        ];
    }
}
