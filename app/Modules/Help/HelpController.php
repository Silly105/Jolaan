<?php
declare(strict_types=1);

namespace App\Modules\Help;

use App\Modules\Admin\AdminLayout;

class HelpController
{
    public static function handle(): void
    {
        ob_start();
        ?>
        <div class="card">
            <div class="card-header">
                <h2>❓ Willkommen im Handbuch für SL & Administration</h2>
            </div>
            <div class="card-body">
                <p style="margin-top:0;color:#334155;">
                    Diese Seite erklärt Schritt für Schritt die tägliche Arbeit mit dem Con-Management-System – von der Anlage einer neuen Veranstaltung über die Zahlungs- und Teilnehmerverwaltung bis hin zu Auswertungslisten und dem Datenschutz nach der Con.
                </p>
                <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
                    <a href="#sec-workflow" class="btn btn-secondary btn-sm">1. Neue Veranstaltung anlegen</a>
                    <a href="#sec-formbuilder" class="btn btn-secondary btn-sm">2. Formular-Baukasten & Verschlüsselung</a>
                    <a href="#sec-registrations" class="btn btn-secondary btn-sm">3. Teilnehmer & Zahlungen verwalten</a>
                    <a href="#sec-lists" class="btn btn-secondary btn-sm">4. Listen, Filter, U18 & Druck</a>
                    <a href="#sec-2fa" class="btn btn-secondary btn-sm">5. Zwei-Faktor-Schutz (2FA)</a>
                    <a href="#sec-gdpr" class="btn btn-secondary btn-sm">6. DSGVO & Datenbereinigung nach der Con</a>
                </div>
            </div>
        </div>

        <div class="card" id="sec-workflow">
            <div class="card-header"><h2>1. Ablauf: Eine neue Veranstaltung (Con) anlegen</h2></div>
            <div class="card-body">
                <ol style="margin:0;padding-left:1.35rem;line-height:1.7;">
                    <li><strong>Veranstaltung erstellen (nur Admin):</strong> Klicke links im Menü auf <em>„➕ Neue Veranstaltung“</em>.</li>
                    <li><strong>Einstellungen vom Vorjahr übernehmen:</strong> Wähle ganz oben unter <em>„Vorlage für Teilnehmergruppen, Formularfelder und Auswertungslisten“</em> entweder die globale Standard-Vorlage oder einfach eure letzte Veranstaltung aus. Dadurch werden alle Gruppen (SC, NSC, Team), Formularfelder und gespeicherten Listen automatisch übernommen!</li>
                    <li><strong>Basisdaten & Kartenlink eintragen:</strong> Trage Name, Beginn- und Enddatum (wichtig als Stichtag für die automatische U18-Minderjährigen-Prüfung!) sowie den Veranstaltungsort und optional einen Google-Maps-Link ein.</li>
                    <li><strong>Design & Header anpassen:</strong> Du kannst für jede Veranstaltung ein eigenes Header-Logo, einen Einleitungstext über dem Anmeldeformular und bei Bedarf ein eigenes Farb-Theme festlegen.</li>
                    <li><strong>Plätze & Kontingente festlegen:</strong> Wechsle nach dem Speichern in den Reiter <em>„🎟️ Teilnehmergruppen & Plätze“</em>. Dort legst du fest, wie viele SC- und NSC-Plätze verfügbar sind. Das System berechnet die freien Plätze auf der Startseite automatisch (du kannst die angezeigte Zahl aber jederzeit über das Feld <em>„Manueller Override“</em> überschreiben oder einzelne Gruppen als „Ausgebucht“ sperren).</li>
                    <li><strong>Geheim-Link für Nachzügler:</strong> Ist eine Veranstaltung oder eine Gruppe voll/gesperrt, findest du unter <em>„⚙️ Einstellungen“</em> den <strong>Geheim-Link</strong>. Wer diesen Link von euch bekommt, kann sich trotz Sperre noch anmelden.</li>
                </ol>
            </div>
        </div>

        <div class="card" id="sec-formbuilder">
            <div class="card-header"><h2>2. Formular-Baukasten, Interne Felder & Verschlüsselung</h2></div>
            <div class="card-body">
                <p style="margin-top:0;">Unter <em>„🧩 Formular-Baukasten“</em> legt der Administrator fest, welche Fragen im Anmeldeformular gestellt werden. Dabei gibt es vier wichtige Haken:</p>
                <ul style="margin:0;padding-left:1.35rem;line-height:1.7;">
                    <li><strong>⭐ Pflichtfeld:</strong> Der Teilnehmer muss dieses Feld ausfüllen (bzw. bei Dokumenten/AGBs die Checkbox anhaken), sonst lässt sich die Anmeldung nicht absenden.</li>
                    <li><strong>🔒 Verschlüsselt in DB speichern (AES-256):</strong> Der eingegebene Wert wird mit dem Serverschlüssel (<code>AES-256-GCM</code>) verschlüsselt in der Datenbank abgelegt. Standardmäßig aktiv für <strong>E-Mail-Adresse und Telefonnummer</strong> sowie alle Gesundheitsfelder. Selbst wenn jemand die Datenbank kopieren sollte, sind diese Kontaktdaten unlesbar. <em>Wichtig:</em> Rein verschlüsselte Felder (wie Telefon/E-Mail) werden bei der 1-Klick-Bereinigung nach der Con <strong>nicht</strong> gelöscht.</li>
                    <li><strong>🧹 Sensible Daten (Nach Con per 1-Klick löschen):</strong> Für besonders schützenswerte Angaben nach Art. 9 DSGVO (z. B. <em>Allergien, Krankheiten, Medikamente, Phobien, Schwangerschaft</em>). Diese Felder werden automatisch verschlüsselt <strong>und</strong> können nach Ende der Veranstaltung im DSGVO-Center mit einem einzigen Klick restlos geleert werden.</li>
                    <li><strong>👁️ Nur für Orga sichtbar:</strong> Interne Felder (z. B. <em>Zimmerzuweisung, Aufbau-/Abbauhelfer, SL-Notizen</em>). Diese Felder sieht der Teilnehmer bei der Anmeldung überhaupt nicht – sie können nur von SL und Admins im Backend ausgefüllt werden (deshalb wird „Pflichtfeld“ hier automatisch deaktiviert).</li>
                </ul>
                <p style="margin-bottom:0;margin-top:0.75rem;">
                    <strong>🌐 Zweisprachigkeit (DE / EN):</strong> Wenn in den Veranstaltungseinstellungen <em>„Zweisprachige Anmeldung“</em> aktiviert ist, findest du im Reiter <em>„🌐 Übersetzungstabelle (DE/EN)“</em> alle deutschen und englischen Begriffe übersichtlich nebeneinander (inkl. Kopier-Tabelle für automatische Übersetzer wie DeepL).
                </p>
            </div>
        </div>

        <div class="card" id="sec-registrations">
            <div class="card-header"><h2>3. Teilnehmerverwaltung, Zahlungen & System-E-Mails (für SL & Admin)</h2></div>
            <div class="card-body">
                <ul style="margin:0;padding-left:1.35rem;line-height:1.7;">
                    <li><strong>Double-Opt-In (E-Mail-Bestätigung):</strong> Meldet sich jemand online an, erhält er automatisch eine E-Mail mit Bestätigungslink. Erst nach Klick auf diesen Link wandert die Person in die reguläre Teilnehmerliste. Wer den Link noch nicht geklickt hat, steht ganz unten im gelben Kasten <em>„Noch nicht per E-Mail bestätigt“</em> (dort könnt ihr die Opt-In-Mail mit einem Klick erneut senden).</li>
                    <li><strong>Zahlungen erfassen:</strong> Direkt in der Teilnehmerübersicht könnt ihr für jede Person den <strong>Bezahlten Betrag (€)</strong> eintragen und den Haken <strong>„Voller Preis bezahlt“</strong> setzen, sobald nichts mehr offen ist. Ein Klick auf <em>„💾 Zahlungen & Haken speichern“</em> speichert alle Zeilen gleichzeitig.</li>
                    <li><strong>Teilnehmer-Details & E-Mails senden:</strong> Klickt auf den Namen eines Teilnehmers (oder auf <em>„✏️ Öffnen & Mails“</em>), um alle Antworten und internen SL-Felder (z. B. Zimmer oder SL-Notizen) zu bearbeiten. Ganz oben auf der Detailseite könnt ihr per 1-Klick die <strong>Anmeldebestätigung</strong>, <strong>Geldeingangsbestätigung</strong> oder eine <strong>Zahlungserinnerung</strong> per E-Mail verschicken.</li>
                    <li><strong>Manuelle Nachmeldung:</strong> Über den Button <em>„➕ Teilnehmer manuell eintragen“</em> könnt ihr Personen auch direkt ohne Umweg über das öffentliche Formular eintragen.</li>
                </ul>
            </div>
        </div>

        <div class="card" id="sec-lists">
            <div class="card-header"><h2>4. Listen, Filter, Minderjährigen-Prüfung (U18), Druck & Excel-Export</h2></div>
            <div class="card-body">
                <p style="margin-top:0;">Unter <em>„📋 Listen, Filter & Export“</em> stellt ihr euch mit wenigen Klicks jede benötigte Orga-, Küchen-, Sanitäter- oder SL-Liste zusammen:</p>
                <ul style="margin:0;padding-left:1.35rem;line-height:1.7;">
                    <li><strong>Fertige Schnell-Listen:</strong> Oben findet ihr bereits fertige Listen-Buttons (z. B. <em>Minderjährige Teilnehmer (U18)</em>, <em>Offene Zahlungen</em>, <em>Küche & Verpflegung</em>, <em>Sanitäter & Gesundheit</em>, <em>SL-Charakterübersicht</em>, <em>Check-In & Logistik</em>).</li>
                    <li><strong>🔞 Spezialfilter Minderjährige (U18):</strong> Berechnet anhand des Geburtsdatums und des eingetragenen Veranstaltungs-Beginndatums vollautomatisch, wer am ersten Tag der Veranstaltung noch unter 18 Jahre alt ist.</li>
                    <li><strong>Eigene Filter & Spalten wählen:</strong> Ihr könnt nach jeder Teilnehmergruppe, nach Zahlungsstatus und nach jedem beliebigen Formularfeld filtern (z. B. <em>„Allergien ist ausgefüllt“</em>) und per Haken exakt auswählen, welche Spalten angezeigt werden sollen.</li>
                    <li><strong>🖨️ Druckansicht & 📥 CSV-Export:</strong> Jede Liste lässt sich sofort als saubere Schwarz-Weiß-Drucktabelle öffnen oder als CSV-Datei für Excel herunterladen.</li>
                </ul>
            </div>
        </div>

        <div class="card" id="sec-2fa">
            <div class="card-header"><h2>5. Zwei-Faktor-Authentifizierung (2FA) für deinen Account</h2></div>
            <div class="card-body">
                <p style="margin-top:0;">
                    Jedes Teammitglied (SL und Admin) kann seinen Zugang unter <em>„Passwort ändern“</em> (unten links in der Seitenleiste) mit einer <strong>Zwei-Faktor-Authentifizierung (TOTP)</strong> schützen:
                </p>
                <ul style="margin:0;padding-left:1.35rem;line-height:1.7;">
                    <li>Du benötigst lediglich eine gängige Authenticator-App auf deinem Smartphone oder PC (z. B. <em>Google Authenticator, Aegis, Bitwarden, 1Password, Apple Passwörter</em>). Es werden <strong>keine externen Dienste</strong> kontaktiert.</li>
                    <li>Bei der Aktivierung erhältst du <strong>8 Einmal-Notfallcodes</strong>. Bewahre diese gut auf! Aus Sicherheitsgründen kann auch ein Administrator dein 2FA im Web-Backend nicht ohne gültigen 2FA-/Notfallcode ausschalten.</li>
                </ul>
            </div>
        </div>

        <div class="card" id="sec-gdpr">
            <div class="card-header"><h2>6. DSGVO-Center & 1-Klick-Bereinigung nach der Veranstaltung (nur Admin)</h2></div>
            <div class="card-body">
                <ul style="margin:0;padding-left:1.35rem;line-height:1.7;">
                    <li><strong>🧹 1-Klick-Bereinigung nach Veranstaltungsende:</strong> Sobald eine Con vorbei ist, klickt der Admin unter <em>„🛡️ DSGVO-Center & Bereinigung“</em> neben der Veranstaltung auf <em>„Sensible Daten jetzt leeren“</em>. Damit werden alle Gesundheitsdaten (Allergien, Krankheiten, Phobien, Schwangerschaft) dieser Con unwiderruflich gelöscht, während Name, Abrechnungsdaten sowie die verschlüsselte E-Mail/Telefonnummer erhalten bleiben.</li>
                    <li><strong>🔎 DSGVO-Datenauskunft (Art. 15 / Art. 17 DSGVO):</strong> Fragt ein Teilnehmer an, welche Daten über ihn gespeichert sind, gibt der Admin einfach die E-Mail-Adresse oder den Namen in die Suche ein und erhält auf Knopfdruck einen druckfertigen Auskunftsbericht über alle Veranstaltungen hinweg (oder kann alle Einträge der Person restlos löschen).</li>
                </ul>
            </div>
        </div>
        <?php
        AdminLayout::render('Hilfe & Handbuch', 'help', (string)ob_get_clean());
    }
}
