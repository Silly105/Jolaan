# Modulares LARP-Con-Management-System

Ein leichtgewichtiges, modulares und DSGVO-konformes Online-Anmelde- und Verwaltungssystem für LARP-Veranstaltungen (Cons), entwickelt für den direkten Betrieb auf jedem klassischen Shared-Webhosting (PHP 8.0+ & MySQL/MariaDB).

## Hauptfunktionen

- **1-Klick Web-Installer (`install.php`):** Prüft Server-Voraussetzungen, richtet alle Tabellen und Vorlagen ein, generiert den kryptografischen Verschlüsselungs-Key (`secret.key`) und sperrt sich nach Abschluss selbst.
- **Dynamischer Formular-Baukasten:**
  - Frei konfigurierbare Felder pro Veranstaltung (Text, Textbereich, Zahl, Datum, E-Mail, Dropdown, Checkbox, Überschrift).
  - **Dokumenten-Akzeptanz-Feld:** Dateien (z. B. AGB, Datenschutz, Hausordnung) direkt zum Feld hochladen und als akzeptiert abfragen.
  - **Interne Orga-Felder:** Felder als *„Nur für Orga sichtbar“* markieren (inkl. automatischem Kollisionsschutz mit der Pflichtfeld-Einstellung).
  - **Vorlagen & Klonen:** Übernahme aller Einstellungen, Teilnehmergruppen, Felder und Listen einer vorherigen Veranstaltung mit einem Klick.
- **Zweisprachigkeit (DE / EN) & Übersetzungstabelle:**
  - Pro Veranstaltung aktivierbar.
  - Zentrale zweispaltige Übersetzungstabelle im Backend mit Pflichtfeld-Kontrolle sowie TSV-Kopier-/Import-Funktion für schnelle Übersetzungen.
- **Theme-System & Veranstaltungs-Header:**
  - Globales Standard-Theme (`western-seal`, `parchment`, `modern-clean`) + optional abweichendes Theme pro Veranstaltung.
  - Konfigurierbarer Header pro Veranstaltung (eigenes Veranstaltungs-Logo + optionaler Textblock über dem Anmeldeformular).
- **Teilnehmergruppen & Automatische Platzberechnung:**
  - Voreingestellt mit *SC*, *NSC* und *Team* (beliebig erweiterbar).
  - Automatische Berechnung der freien Plätze auf der Startseite inkl. manuellem Override und Geheimlink für Nachzügler.
- **Vereinfachte Zahlungsverwaltung & Dynamische Listen:**
  - Speicherung des gezahlten Betrags + Checkbox *„Voller Preis bezahlt“*.
  - Universeller Listen- & Filter-Generator mit Druckansicht, CSV-Export und Spezialfilter für **Minderjährige (U18) zum Veranstaltungsbeginn**.
- **DSGVO-Schutz & Datenauskunft:**
  - Automatische `AES-256-GCM`-Verschlüsselung aller als *„Sensible Daten“* markierten Felder (Allergien, Krankheiten etc.).
  - 1-Klick-Bereinigung sensibler Daten nach Veranstaltungsende.
  - DSGVO-Auskunfts- & Datenexport-Funktion (Art. 15 / Art. 17 DSGVO) über alle Veranstaltungen hinweg.

## Installation

1. Dateien auf den Webspace hochladen.
2. Im Browser `https://deine-domain.de/install.php` (oder einfach die Startseite) aufrufen.
3. Den Schritten des Setup-Assistenten folgen.
