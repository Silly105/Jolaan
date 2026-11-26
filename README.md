# Modulares LARP-Con-Management-System

Ein eigenständiges, datenschutzfreundliches und leicht zu bedienendes Online-Anmelde- und Verwaltungssystem für LARP-Veranstaltungen (Cons).  
Entwickelt für den direkten Einsatz auf jedem klassischen Shared-Webhosting (**PHP 8.0+** & **MySQL / MariaDB**) – **100 % ohne externe Bibliotheken, ohne Composer/Kommandozeile und ohne Datenabfluss an Drittanbieter**.

---

## Funktionsübersicht

### Veranstaltungsorganisation & Online-Anmeldung
- **1-Klick-Übernahme (Klonen):** Neue Veranstaltungen starten wahlweise aus der globalen Standard-Vorlage oder übernehmen mit einem Klick alle Teilnehmergruppen, Abfragefelder und Auswertungslisten einer früheren Con.
- **Teilnehmergruppen & automatische Platzberechnung:** Voreingestellt mit *SC*, *NSC*, *Team* und der internen Gruppe *Absagen* (beliebig erweiterbar). Automatische Berechnung freier Plätze auf der Startseite inkl. optionalem manuellem Override.
- **Interne Teilnehmergruppen („Absagen“):** Gruppen ohne Anzeige im öffentlichen Formular anlegen, um abgesagte Anmeldungen sauber auszusortieren (werden bei Zählern, Zahlungssummen und Standard-Listen automatisch ausgeschlossen).
- **Wartelisten-System mit Nachrück-Schutz:** Pro Veranstaltung aktivierbar. Der exakte Zeitpunkt der **E-Mail-Bestätigung** entscheidet fair über einen regulären Platz oder den Rang auf der Warteliste. Solange Personen auf der Warteliste stehen, bleibt die Gruppe nach außen für Neuanmeldungen gesperrt, bis die Orga Plätze freigibt (*„Nur freigeben“* oder *„Freigeben & Bestätigungsmail senden“*).
- **Geheim-Link für Nachzügler:** Erlaubt ausgewählten Personen die Anmeldung auch bei geschlossener Veranstaltung oder vollen Gruppen und überspringt automatisch die Warteliste.
- **Haushalts- & Familien-Anmeldungen („Übernahme“):** Mehrere verschiedene Personen können mit derselben E-Mail-Adresse angemeldet werden. Nach erfolgreicher Anmeldung bietet ein Button an, eine weitere Person anzumelden und alle als *„Übernahme“* markierten Kontaktdaten (z. B. Nachname, Adresse, E-Mail, Telefon) automatisch vorauszufüllen.
- **Startseiten-Übersicht & Flyer-Upload:** Automatische Trennung nach laufenden und kommenden Veranstaltungen, optionaler Live-Countdown, Flyer-Anbindung (wahlweise per externer URL **oder** direktem PDF-/Bild-Upload), Foren-Links und anklickbarer Karten-/Google-Maps-Link zum Veranstaltungsort.

### Formular-Baukasten & Zweisprachigkeit (DE / EN)
- **Frei gestaltbare Abfragefelder:** Einzeiliger Text, mehrzeiliger Text, E-Mail, Zahl, Datum, Auswahl-Dropdown, Checkbox, Abschnitts-Überschriften sowie **Dokumenten-Upload + Pflicht-Checkbox** (z. B. für AGB, Hausordnung oder Einverständniserklärungen).
- **Interne Orga-Felder:** Als *„Nur für Orga sichtbar“* markierte Felder (z. B. Zimmerzuweisung, Aufbauhelfer, SL-Notizen) erscheinen nur im Backend (inkl. automatischem Kollisionsschutz mit Pflichtfeldern).
- **Zweisprachigkeit & Übersetzungstabelle:** Optional pro Veranstaltung aktivierbar (DE/EN) – mit zentraler zweispaltiger Übersetzungstabelle, Vollständigkeitsprüfung und schnellem Tabellen-Copy-&-Paste (TSV) für Übersetzungen.

### Anmeldeverwaltung, Listen & E-Mail-System
- **Übersichtliche Anmeldeverwaltung:** Schnellerfassung von Zahlungsbeträgen, Live-Schnellsuche nach Name/E-Mail, 1-Klick-Versand zweisprachiger Systemmails (E-Mail-Bestätigung, Wartelistenplatz-Bestätigung mit aktuellem `{wartelistenplatz}`, Anmeldebestätigung, Zahlungserinnerung, Geldeingangsbestätigung) und automatisches, schreibgeschütztes **Verlaufs- & Zeitprotokoll** pro Teilnehmer.
- **Dynamischer Listen-Generator:** Vorgefertigte und frei speicherbare Listen (inkl. automatischer **U18-Minderjährigen-Berechnung zum Con-Beginndatum**, Wartelisten-Filter, Küchen-, Sanitäter-, Zahlungs- und SL-Listen) mit sauberer Druck-/PDF-Ansicht und Excel-kompatiblem CSV-Export.
- **Rundmail-Modul für Orga & SL (Spam- & Datenschutz-sicher):** Eigene Veranstaltungsseite *Rundmail* (z. B. für Anreise-Infos an alle bestätigten Teilnehmer, einzelne Gruppen, offene Zahlungen oder die Warteliste – inkl. Haushalts-Deduplizierung):
  - *Empfohlener System-Versand:* Versendet personalisierte Einzel-Mails (`{vorname}`, `{condatum}`, `{gruppe}` …) schonend gedrosselt in 5er-Paketen mit Live-Fortschrittsbalken, Vorab-Vorschaumail an die eigene Adresse und automatischem Vermerk im Teilnehmer-Protokoll.
  - *Fallback fürs eigene E-Mail-Programm:* Automatische Aufteilung in 50er-Bündel mit direkten `mailto:?bcc=...`-Buttons (setzt alle Adressen sofort ins unsichtbare `BCC`-Feld) sowie 1-Klick-Kopierfeldern (Komma- und Semikolon-getrennt).
- **100 % nativer E-Mail- & SMTP-Versand:** Funktioniert sofort über den lokalen Webserver (`PHP mail()`) oder optional über einen eigenen externen SMTP-Server (`STARTTLS` / `SSL/TLS`) – inkl. **Test-E-Mail-Funktion** im Backend.

### Designs (14 Themes) & Header-Optionen
- **6 schlichte Farb-Themes:** *Western Forest (Waldgrün)*, *Warm Parchment (Sand & Braun)*, *Modern Clean (Schiefer & Petrol)*, *Fire & Ash (Glutrot & Schwarz)*, *Arctic Storm (Eisblau & Weiß)* und *Mystic Twilight (Violett & Amethyst)*.
- **8 aufwändige Genre-Themes mit eigenen Texturen & SVG-Ornamentik:**
  - *Keltensiegel (Mittelalter / Fantasy – Feiern)*
  - *Eisen & Banner (Mittelalter / Fantasy – Kampf)*
  - *Reisechronik & Pergament (Mittelalter / Fantasy – Abenteuer)*
  - *Feuervogel (Mittelalter / Fantasy – Tanzball)*
  - *Art Déco & Salon (1920er Jahre)*
  - *Frontier Gazette & Saloon (Western)*
  - *Orbital Terminal (Generisches Sci-Fi / Dark HUD)*
  - *Arkham Grimoire (Mystery / Cthulhu)*
- **Flexible Header-Gestaltung:** Drei Header-Modi (*Kompakte Kopfleiste*, *Zentrierter Wappen-/Banner-Header*, *Großer Hero-Header*), eigenes Favicon, globales/veranstaltungsbezogenes Header-Logo sowie ein optionales **individuelles Veranstaltungs-Bild in voller Formularbreite** über dem Einführungstext. Das globale Theme färbt automatisch auch das Orga-Backend passend ein.

### Datenschutz (DSGVO) & Sicherheit
- **Zweistufige `AES-256-GCM`-Verschlüsselung:**
  - *Verschlüsselt in DB speichern* (standardmäßig u. a. für E-Mail-Adresse mit `HMAC-SHA256`-Suchindex sowie Telefonnummer – selbst bei einem Datenbank-Abfluss unlesbar).
  - *Sensible Daten* (z. B. Allergien, Krankheiten, Medikamente) werden verschlüsselt gespeichert **und** lassen sich nach der Con im **DSGVO-Center per 1-Klick restlos leeren**, während Abrechnungs- und Kontaktdaten erhalten bleiben.
- **DSGVO-Auskunft & Löschung (Art. 15 / Art. 17):** Veranstaltungsübergreifende Suche nach Name oder E-Mail mit druckfertigem Auskunftsbericht, JSON-Export und Komplettlöschung.
- **1-Klick-Schlüsselverschiebung:** Die Schlüsseldatei (`secret.key`) kann über die Seiteneinstellungen mit einem Klick in ein Verzeichnis außerhalb des öffentlichen Webroots (`public_html` / `httpdocs`) verschoben werden.
- **Rollen, lokale 2FA & Brute-Force-Schutz:** Zwei Rollen (`Admin` und `SL`), lokal berechnete Zwei-Faktor-Authentifizierung (TOTP) mit **nativ erzeugtem SVG-QR-Code** (ohne externe API-Aufrufe), 8 Einmal-Notfallcodes, Echtzeit-Rechteabgleich und automatischer 15-Minuten-Sperre nach 5 Fehlversuchen.

---

## Schritt-für-Schritt-Installation (ohne Technik-Vorkenntnisse)

Du benötigst lediglich ein normales Webhosting-Paket (z. B. bei Dogado, Plesk, IONOS, Strato, All-Inkl oder Netcup).

### Schritt 1: Im Kundenmenü deines Hosters vorbereiten (einmalig 2 Minuten)
1. **PHP aktivieren:** Prüfe in deinem Hosting-Menü (z. B. unter *Webseiten & Domains -> PHP-Einstellungen*), dass **PHP** aktiviert ist und auf **Version 8.1, 8.2 oder 8.3** (mindestens 8.0) steht.
2. **Leere Datenbank anlegen:** Klicke in deinem Hosting-Menü auf **Datenbanken -> Datenbank erstellen**. Notiere dir diese drei Angaben, die dir dein Hoster dabei anzeigt:
   - **Datenbank-Name**
   - **Datenbank-Benutzername**
   - **Datenbank-Passwort**  
   *(Als Datenbank-Host gilt fast immer `localhost`, als Port `3306`.)*

### Schritt 2: Dateien auf den Webspace hochladen
1. Lade alle Projektdateien und Ordner (`app/`, `storage/`, `themes/`, `index.php`, `register.php`, `admin.php`, `install.php` usw.) mit einem FTP-Programm (z. B. *FileZilla* oder *WinSCP*) oder über den Dateimanager deines Hosters in das gewünschte Verzeichnis deiner Webseite hoch (z. B. direkt ins Hauptverzeichnis `httpdocs` oder in einen Unterordner wie `httpdocs/anmeldung`).
2. Du musst keine Konfigurationsdateien von Hand bearbeiten – falls versteckte `.htaccess`-Schutzdateien beim Kopieren fehlen sollten, legt der Installer sie automatisch selbst an.

### Schritt 3: Setup-Assistenten im Browser ausführen
1. Öffne deine Webseite im Browser (z. B. `https://deine-domain.de/` oder `https://deine-domain.de/anmeldung/`). Du wirst automatisch zum **Setup-Assistenten (`install.php`)** weitergeleitet.
2. Trage die Datenbank-Zugangsdaten aus Schritt 1, den Namen deiner Orga, eine Absender-E-Mail-Adresse deiner Domain (z. B. `orga@deine-domain.de`) sowie deine gewünschte Admin-Login-E-Mail und ein Passwort ein.
3. Klicke auf **„System jetzt installieren“** – fertig! Der Installer sperrt sich danach automatisch selbst. Im Backend findest du ganz oben links unter **„Hilfe & Handbuch“** alle Abläufe für dein Team erklärt.

---

## Spätere Updates einspielen (ohne Datenverlust)

Wenn es eine neue Programmversion gibt:
1. Lade einfach die neuen Programmdateien (`app/`, `themes/`, `index.php`, `register.php`, `admin.php` usw.) per FTP über die bestehenden Dateien auf deinem Server.
2. **Wichtig:** Lösche oder überschreibe auf dem Server **niemals** den Ordner `storage/config/` (dort liegen deine `config.php` und deine Schlüsseldatei `secret.key`) und den Ordner `storage/uploads/` (dort liegen deine hochgeladenen Logos und Dokumente).
3. Rufe danach einfach deine Seite oder das Backend im Browser auf – das integrierte **Auto-Update-Modul** erkennt Versionssprünge vollautomatisch und aktualisiert die Datenbank im Hintergrund, ohne dass Daten verloren gehen.

---

## Backup & Notfall-Hilfe

- **Vollständiges Backup:** Zu einer vollständigen Datensicherung gehören immer **zwei Dinge**:
  1. Ein Backup deiner **MySQL-Datenbank** (über dein Hosting-Menü / phpMyAdmin),
  2. Eine Sicherheitskopie deiner Schlüsseldatei **`secret.key`** (standardmäßig unter `storage/config/secret.key`, oder an dem Pfad, den du unter *Seiteneinstellungen* gewählt hast).  
  *Ohne die zugehörige `secret.key` können die in der Datenbank verschlüsselten E-Mail-Adressen, Telefonnummern und Gesundheitsdaten nicht wieder entschlüsselt werden!*
- **Notfall-Reset bei verlorenem 2FA-Smartphone + verlorenen Notfallcodes:**
  Falls sich ein Teammitglied komplett ausgesperrt hat, lege per FTP im Ordner `storage/config/` eine einfache Textdatei mit dem Namen **`reset_2fa.txt`** an und schreibe als einzige Zeile die Login-E-Mail-Adresse der betroffenen Person hinein (oder `all` für alle Konten). Beim nächsten Login mit dem regulären Passwort wird 2FA für diesen Account zurückgesetzt und die Datei `reset_2fa.txt` sofort automatisch wieder gelöscht.
