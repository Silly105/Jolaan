<?php
declare(strict_types=1);

namespace App\Core;

class Version
{
    public const CURRENT   = '1.0.0';
    public const COPYRIGHT = '(C) 2026 J. Gerhard';
    public const LICENSE   = 'GPL-3.0';

    /**
     * Öffentliche Änderungshistorie & Migrations-Schritte ab Release 1.0.0.
     * Zukünftige Versionen (z. B. 1.0.1, 1.1.0) werden hier fortlaufend ergänzt.
     */
    public static function history(): array
    {
        return [
            '1.0.0' => [
                'date'    => '2026-11-26',
                'title'   => 'Erstes öffentliches Release (Version 1.0.0)',
                'changes' => [
                    '1-Klick Web-Installer ohne externe Abhängigkeiten für klassisches PHP-8- / MySQL-Webhosting',
                    'Veranstaltungsverwaltung mit Vorjahres-Klonfunktion, Kontingenten, Wartelisten-System, Geheim-Link und Haushalts-Mehrfachanmeldung',
                    'Dynamischer Formular-Baukasten inkl. Dokumenten-Upload, internen Orga-Feldern und zweisprachiger Übersetzungstabelle (DE/EN)',
                    'Anmeldeverwaltung mit Zahlungsabgleich, Protokollverlauf, Listen-Generator (inkl. U18-Stichtagsprüfung, Druck & CSV) sowie Rundmail-Modul (Systemversand & 50er-BCC-Pakete)',
                    '14 enthaltene Themes (6 schlichte Farb-Themes & 8 Genre-Themes) mit 3 Header-Modi, Logo-, Banner- und Flyer-Upload',
                    'Zweistufige AES-256-GCM-Verschlüsselung, DSGVO-Center, lokale TOTP-Zwei-Faktor-Authentifizierung mit nativem QR-Code und Brute-Force-Schutz',
                ],
            ],
        ];
    }
}
