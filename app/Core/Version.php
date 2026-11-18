<?php
declare(strict_types=1);

namespace App\Core;

class Version
{
    public const CURRENT = '0.9.001';

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
        ];
    }
}
