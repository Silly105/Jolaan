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
                <h2>Willkommen im Handbuch für SL & Administration</h2>
            </div>
            <div class="card-body">
                <p style="margin-top:0;color:#334155;">
                    Diese Seite erklärt Schritt für Schritt die tägliche Arbeit mit dem Con-Management-System – von der Anlage einer neuen Veranstaltung über die Anmeldeverwaltung, Warteliste und verfügbaren Plätze bis hin zu Auswertungslisten und dem Datenschutz nach der Con.
                </p>
                <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
                    <a href="#sec-workflow" class="btn btn-secondary btn-sm">Neue Veranstaltung & Verfügbare Plätze</a>
                    <a href="#sec-formbuilder" class="btn btn-secondary btn-sm">Abfragefelder & Verschlüsselung</a>
                    <a href="#sec-registrations" class="btn btn-secondary btn-sm">Anmeldeverwaltung, Warteliste & Zahlungen</a>
                    <a href="#sec-lists" class="btn btn-secondary btn-sm">Listen, Filter, U18 & Druck</a>
                    <a href="#sec-2fa" class="btn btn-secondary btn-sm">Zwei-Faktor-Schutz (2FA)</a>
                    <a href="#sec-gdpr" class="btn btn-secondary btn-sm">DSGVO-Center & Datenbereinigung</a>
                </div>
            </div>
        </div>

        <div class="card" id="sec-workflow">
            <div class="card-header"><h2>Ablauf: Eine neue Veranstaltung (Con) anlegen & Verfügbare Plätze steuern</h2></div>
            <div class="card-body">
                <ul style="margin:0;padding-left:1.35rem;line-height:1.7;">
                    <li><strong>Neue Veranstaltung erstellen (nur Admin):</strong> Klicke links im Menü unter <em>„Administration & Vorlagen“</em> auf <strong>„Neue Veranstaltung“</strong>.</li>
                    <li><strong>Einstellungen vom Vorjahr übernehmen:</strong> Wähle ganz oben unter <em>„Vorlage für Teilnehmergruppen, Abfragefelder und Auswertungslisten“</em> entweder die globale Standard-Vorlage oder eure letzte Veranstaltung aus. Dadurch werden alle Teilnehmergruppen (SC, NSC, Team, Absagen), Abfragefelder und gespeicherten Listen automatisch übernommen.</li>
                    <li><strong>Dauerhafte Auswahl im Menü:</strong> Sobald du unter <strong>„Übersicht“</strong> eine Veranstaltung anklickst, erscheint der Name dieser Veranstaltung links als eigene Menü-Überschrift mit allen zugehörigen Unterpunkten (<em>Anmeldeverwaltung, Listen, Con-Einstellungen, Verfügbare Plätze, Abfragefelder</em> und bei aktivierter Zweisprachigkeit auch <em>Übersetzungstabelle</em>).</li>
                    <li><strong>Verfügbare Plätze & Interne Gruppen (für SL & Admin):</strong> Unter <strong>„Verfügbare Plätze“</strong> legt ihr fest, wie viele Plätze pro Teilnehmergruppe verfügbar sind. Das System berechnet die freien Plätze auf der Startseite automatisch (optional per <em>„Manueller Override“</em> überschreibbar). Über den Haken <strong>„In Anmeldung anzeigen“</strong> könnt ihr rein interne Gruppen (wie <em>„Absagen“</em>) anlegen, die im öffentlichen Formular nicht wählbar sind, in die ihr aber Teilnehmer im Backend einsortieren könnt.</li>
                    <li><strong>Geheim-Link für Nachzügler:</strong> Unter <strong>„Con-Einstellungen“</strong> findet der Admin den <strong>Geheim-Link</strong>. Wer diesen Link erhält, kann sich auch bei geschlossener Anmeldung oder ausgebuchten Plätzen noch anmelden – und überspringt dabei automatisch die Warteliste.</li>
                </ul>
            </div>
        </div>

        <div class="card" id="sec-formbuilder">
            <div class="card-header"><h2>Abfragefelder, Feldvorlagen & Verschlüsselung</h2></div>
            <div class="card-body">
                <p style="margin-top:0;">Unter <strong>„Abfragefelder“</strong> (bzw. global für künftige Cons unter <strong>„Feldvorlagen“</strong>) legt der Administrator fest, welche Fragen im Anmeldeformular gestellt werden. Dabei gibt es vier wichtige Optionen:</p>
                <ul style="margin:0;padding-left:1.35rem;line-height:1.7;">
                    <li><strong>⭐ Pflichtfeld:</strong> Der Teilnehmer muss dieses Feld ausfüllen (bzw. bei Dokumenten/AGBs die Checkbox anhaken), sonst lässt sich die Anmeldung nicht absenden.</li>
                    <li><strong>🔒 Verschlüsselt in DB speichern (AES-256):</strong> Der eingegebene Wert wird mit dem Serverschlüssel (<code>AES-256-GCM</code>) verschlüsselt in der Datenbank abgelegt. Standardmäßig aktiv für <strong>E-Mail-Adresse und Telefonnummer</strong> sowie alle Gesundheitsfelder. Selbst wenn jemand die Datenbank kopieren sollte, sind diese Kontaktdaten unlesbar. <em>Wichtig:</em> Rein verschlüsselte Felder (wie Telefon/E-Mail) werden bei der 1-Klick-Bereinigung nach der Con <strong>nicht</strong> gelöscht.</li>
                    <li><strong>🧹 Sensible Daten (Nach Con per 1-Klick löschen):</strong> Für besonders schützenswerte Angaben nach Art. 9 DSGVO (z. B. <em>Allergien, Krankheiten, Medikamente, Phobien, Schwangerschaft</em>). Diese Felder werden automatisch verschlüsselt <strong>und</strong> können nach Ende der Veranstaltung im <strong>DSGVO-Center</strong> mit einem einzigen Klick restlos geleert werden.</li>
                    <li><strong>👁️ Nur für Orga sichtbar:</strong> Interne Felder (z. B. <em>Zimmerzuweisung, Aufbau-/Abbauhelfer, SL-Notizen</em>). Diese Felder sieht der Teilnehmer bei der Anmeldung überhaupt nicht – sie können nur von SL und Admins im Backend ausgefüllt werden (deshalb wird „Pflichtfeld“ hier automatisch deaktiviert).</li>
                </ul>
                <p style="margin-bottom:0;margin-top:0.75rem;">
                    <strong>Übersetzungstabelle (DE / EN):</strong> Sobald in den <em>Con-Einstellungen</em> der Haken <em>„Zweisprachige Anmeldung“</em> aktiv ist, erscheint links im Menü der Punkt <strong>„Übersetzungstabelle“</strong>. Dort lassen sich alle deutschen und englischen Feldnamen übersichtlich nebeneinander pflegen.
                </p>
            </div>
        </div>

        <div class="card" id="sec-registrations">
            <div class="card-header"><h2>Anmeldeverwaltung, E-Mail-Bestätigung, Warteliste & Verlaufs-Protokoll</h2></div>
            <div class="card-body">
                <ul style="margin:0;padding-left:1.35rem;line-height:1.7;">
                    <li><strong>E-Mail-Bestätigung:</strong> Meldet sich jemand online an, erhält er automatisch eine E-Mail mit Bestätigungslink. Erst nach Klick auf diesen Link wird die Anmeldung aktiv. Wer den Link noch nicht geklickt hat, steht unter <strong>„Anmeldeverwaltung“</strong> im gelben Kasten <em>„Noch nicht per E-Mail bestätigt“</em> (dort könnt ihr die E-Mail-Bestätigung erneut senden oder die Person manuell freischalten).</li>
                    <li><strong>Wartelisten-System:</strong> Ist eine Teilnehmergruppe zum Zeitpunkt der E-Mail-Bestätigung (oder der manuellen Freischaltung) bereits voll, landet der Teilnehmer automatisch auf der <strong>Warteliste</strong> – streng sortiert nach dem Zeitpunkt der E-Mail-Bestätigung (Platz #1, #2, ...).
                        <ul>
                            <li>Mit dem Button <strong>„Wartelistenplatz bestätigen“</strong> sendet ihr dem Teilnehmer eine E-Mail, die seinen Eingang und seinen aktuellen Wartelistenplatz bestätigt.</li>
                            <li>Mit dem Button <strong>„Anmeldung freigeben“</strong> holt ihr die Person von der Warteliste auf die reguläre Teilnehmerliste.</li>
                        </ul>
                    </li>
                    <li><strong>Zahlungen erfassen:</strong> Direkt in der Anmeldeverwaltung könnt ihr für jede Person den <strong>Bezahlten Betrag (€)</strong> eintragen und den Haken <strong>„Voller Preis bezahlt“</strong> setzen. Ein Klick auf <em>„💾 Zahlungen & Haken speichern“</em> speichert alle Zeilen gleichzeitig.</li>
                    <li><strong>Zeitlicher Verlauf & System-Protokoll:</strong> Öffnet ihr einen Teilnehmer, seht ihr über den Formularfeldern das schreibgeschützte <strong>System-Protokoll</strong>. Dort dokumentiert das System automatisch mit Datum und Uhrzeit, wann die Anmeldung einging, wann die E-Mail-Bestätigung erfolgte, wann der Teilnehmer auf die Warteliste kam oder nachgerückt ist und wann welche E-Mails (Anmeldebestätigung, Geldeingangsbestätigung, Zahlungserinnerungen) verschickt wurden.</li>
                </ul>
            </div>
        </div>

        <div class="card" id="sec-lists">
            <div class="card-header"><h2>Listen, Filter, Minderjährigen-Prüfung (U18), Druck & Excel-Export</h2></div>
            <div class="card-body">
                <p style="margin-top:0;">Unter <strong>„Listen“</strong> stellt ihr euch mit wenigen Klicks jede benötigte Orga-, Küchen-, Sanitäter- oder SL-Liste für die ausgewählte Veranstaltung zusammen:</p>
                <ul style="margin:0;padding-left:1.35rem;line-height:1.7;">
                    <li><strong>Gespeicherte Auswertungslisten:</strong> Oben stehen alle gespeicherten Listen für diese Veranstaltung untereinander (z. B. <em>Minderjährige Teilnehmer (U18)</em>, <em>Offene Zahlungen</em>, <em>Küche & Verpflegung</em>, <em>Sanitäter & Gesundheit</em>, <em>SL-Charakterübersicht</em>, <em>Check-In & Logistik</em>).</li>
                    <li><strong>🔞 Spezialfilter Minderjährige (U18):</strong> Berechnet anhand des Geburtsdatums und des eingetragenen Veranstaltungs-Beginndatums vollautomatisch, wer am ersten Tag der Veranstaltung noch unter 18 Jahre alt ist.</li>
                    <li><strong>Neue Auswertungsliste erstellen:</strong> Darunter könnt ihr nach Teilnehmergruppe, Zahlungsstatus und jedem beliebigen Abfragefeld filtern, die gewünschten Spalten anhaken und die Ansicht als neue Liste speichern, drucken oder als CSV für Excel exportieren.</li>
                </ul>
            </div>
        </div>

        <div class="card" id="sec-2fa">
            <div class="card-header"><h2>Zwei-Faktor-Authentifizierung (2FA) für deinen Account</h2></div>
            <div class="card-body">
                <p style="margin-top:0;">
                    Jedes Teammitglied (SL und Admin) kann seinen Zugang unten links über <strong>„Passwort & 2FA“</strong> mit einer Zwei-Faktor-Authentifizierung (TOTP) schützen:
                </p>
                <ul style="margin:0;padding-left:1.35rem;line-height:1.7;">
                    <li>Du benötigst lediglich eine gängige Authenticator-App auf deinem Smartphone oder PC (z. B. <em>Google Authenticator, Aegis, Bitwarden, 1Password, Apple Passwörter</em>). Es werden <strong>keine externen Dienste</strong> kontaktiert.</li>
                    <li>Bei der Aktivierung erhältst du <strong>8 Einmal-Notfallcodes</strong>. Bewahre diese gut auf! Aus Sicherheitsgründen kann auch ein Administrator dein 2FA im Web-Backend nicht ohne gültigen 2FA-/Notfallcode ausschalten.</li>
                </ul>
            </div>
        </div>

        <div class="card" id="sec-gdpr">
            <div class="card-header"><h2>DSGVO-Center & 1-Klick-Bereinigung nach der Veranstaltung (nur Admin)</h2></div>
            <div class="card-body">
                <ul style="margin:0;padding-left:1.35rem;line-height:1.7;">
                    <li><strong>🧹 1-Klick-Bereinigung nach Veranstaltungsende:</strong> Sobald eine Con vorbei ist, klickt der Admin unter <strong>„DSGVO-Center“</strong> neben der Veranstaltung auf <em>„Sensible Daten jetzt leeren“</em>. Damit werden alle als sensibel markierten Gesundheitsdaten (Allergien, Krankheiten, Phobien, Schwangerschaft) dieser Con unwiderruflich gelöscht, während Name, Abrechnungsdaten sowie die verschlüsselte E-Mail/Telefonnummer erhalten bleiben.</li>
                    <li><strong>🔎 DSGVO-Datenauskunft (Art. 15 / Art. 17 DSGVO):</strong> Fragt ein Teilnehmer an, welche Daten über ihn gespeichert sind, gibt der Admin einfach die E-Mail-Adresse oder den Namen in die Suche ein und erhält auf Knopfdruck einen druckfertigen Auskunftsbericht über alle Veranstaltungen hinweg (oder kann alle Einträge der Person restlos löschen).</li>
                </ul>
            </div>
        </div>
        <?php
        AdminLayout::render('Hilfe & Handbuch', 'help', (string)ob_get_clean());
    }
}
