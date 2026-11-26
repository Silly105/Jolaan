<?php
declare(strict_types=1);

namespace App\Modules\Help;

use App\Core\Auth;
use App\Modules\Admin\AdminLayout;

class HelpController
{
    public static function handle(): void
    {
        $isAdmin = Auth::isAdmin();

        ob_start();
        ?>
        <style>
            .help-intro {
                margin-top: 0;
                margin-bottom: 0;
                color: #334155;
                font-size: 0.94rem;
            }
            details.help-acc {
                border: 1px solid #cbd5e1;
                border-radius: 7px;
                background: #ffffff;
                margin-bottom: 0.65rem;
                overflow: hidden;
            }
            details.help-acc summary {
                padding: 0.8rem 1.1rem;
                font-weight: 600;
                font-size: 0.96rem;
                color: #0f172a;
                background: #f8fafc;
                cursor: pointer;
                list-style: none;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
            }
            details.help-acc summary::-webkit-details-marker {
                display: none;
            }
            details.help-acc summary::after {
                content: "+";
                font-size: 1.2rem;
                font-weight: 700;
                color: var(--adm-accent);
                line-height: 1;
            }
            details.help-acc[open] summary {
                border-bottom: 1px solid #e2e8f0;
                background: #f1f5f9;
            }
            details.help-acc[open] summary::after {
                content: "−";
            }
            .help-acc-body {
                padding: 1rem 1.2rem;
                font-size: 0.92rem;
                color: #1e293b;
                line-height: 1.65;
            }
            .help-acc-body ol, .help-acc-body ul {
                margin: 0.35rem 0 0.25rem;
                padding-left: 1.35rem;
            }
            .help-acc-body li {
                margin-bottom: 0.35rem;
            }
        </style>

        <div class="card">
            <div class="card-header">
                <h2>Schnellhilfe für den Con-Alltag</h2>
            </div>
            <div class="card-body">
                <p class="help-intro">
                    Klicke einfach auf die Frage, die du gerade lösen möchtest – dort findest du die passende Schritt-für-Schritt-Anleitung ohne langes Suchen.
                </p>
            </div>
        </div>

        <!-- TEIL 1: TÄGLICHE ARBEIT FÜR SL & ORGA -->
        <div class="card">
            <div class="card-header">
                <h2>Tägliche Aufgaben (Anmeldungen, Zahlungen, Warteliste & Listen)</h2>
            </div>
            <div class="card-body">

                <details class="help-acc" open>
                    <summary>Wie wähle ich eine Veranstaltung aus und finde mich im Menü zurecht?</summary>
                    <div class="help-acc-body">
                        <ol>
                            <li>Klicke links im Menü unter <em>Veranstaltungen</em> auf <strong>Übersicht</strong>.</li>
                            <li>Klicke bei der gewünschten Con rechts auf <strong>Anmeldeverwaltung</strong> (oder <em>Listen</em> / <em>Verfügbare Plätze</em>).</li>
                            <li>Ab sofort steht der <strong>Name dieser Veranstaltung dauerhaft links im Menü</strong> – alle Unterpunkte darunter beziehen sich genau auf diese ausgewählte Con, bis du in der Übersicht eine andere wählst.</li>
                        </ol>
                    </div>
                </details>

                <details class="help-acc">
                    <summary>Wie verbuche ich Zahlungseingänge, Geldeingangsbestätigungen und Zahlungserinnerungen?</summary>
                    <div class="help-acc-body">
                        <p style="margin-top:0;"><strong>Mehrere Überweisungen schnell eintragen (z. B. beim Kontoauszug-Abgleich):</strong></p>
                        <ol>
                            <li>Öffne links unter der Veranstaltung die <strong>Anmeldeverwaltung</strong>.</li>
                            <li>Nutze oben das Feld <strong>Schnellsuche</strong> (tippe Nachname, Vorname oder E-Mail ein), um die Person sofort zu finden.</li>
                            <li>Trage in der Zeile den <strong>Bezahlten Betrag (€)</strong> ein und setze den Haken bei <strong>Voller Preis bezahlt</strong> (und ggf. <em>Orga bestätigt</em>).</li>
                            <li>Du kannst das für beliebig viele Personen hintereinander machen und am Ende einmal auf <strong>💾 Zahlungen & Haken speichern</strong> klicken.</li>
                        </ol>
                        <p style="margin-bottom:0.25rem;margin-top:0.75rem;"><strong>Bestätigungs-E-Mail oder Zahlungserinnerung an die Person schicken:</strong></p>
                        <ol>
                            <li>Klicke in der Zeile der Person rechts auf <strong>✏️ Öffnen & Mails</strong> (oder auf ihren Namen) – und speichere vorher ggf. den neuen Zahlungsbetrag.</li>
                            <li>Ganz oben findest du die fertigen Versand-Buttons:
                                <ul>
                                    <li><strong>✅ Anmeldebestätigung senden</strong> (bestätigt den Platz & setzt automatisch den Orga-Haken)</li>
                                    <li><strong>💶 Geldeingangsbestätigung senden</strong> (bestätigt den eingetragenen Betrag & setzt automatisch „Voller Preis bezahlt“)</li>
                                    <li><strong>⏰ Zahlungserinnerung senden</strong> (erinnert freundlich an den offenen Beitrag)</li>
                                </ul>
                            </li>
                            <li>Jede gesendete E-Mail wird automatisch mit Datum und Uhrzeit im <strong>Zeitlichen Verlauf</strong> des Teilnehmers protokolliert.</li>
                        </ol>
                    </div>
                </details>

                <details class="help-acc">
                    <summary>Wie funktioniert die Warteliste und wie lasse ich jemanden nachrücken?</summary>
                    <div class="help-acc-body">
                        <ul>
                            <li><strong>Wie landet jemand auf der Warteliste?</strong> Sobald eine Teilnehmergruppe voll ist, können sich Interessenten auf die Warteliste anmelden. Über den genauen Wartelisten-Platz (#1, #2, …) entscheidet fair der Zeitpunkt, zu dem der Teilnehmer den Bestätigungslink in seiner E-Mail anklickt.</li>
                            <li><strong>Kein Vordrängeln bei Absagen:</strong> Solange noch Personen auf der Warteliste stehen, bleibt die Gruppe auf der öffentlichen Webseite automatisch als <em>Ausgebucht / Warteliste</em> markiert – selbst wenn ein regulärer Platz durch eine Absage frei wird. In der Anmeldeverwaltung siehst du dann den orangenen Hinweis <em>„Platz frei für Warteliste“</em>.</li>
                            <li><strong>Wartelistenplatz bestätigen:</strong> Unten im orangenen Kasten der <strong>Anmeldeverwaltung</strong> kannst du mit <strong>✉️ Wartelistenplatz bestätigen</strong> eine Info-Mail mit dem aktuellen Wartelistenplatz senden.</li>
                            <li><strong>Nachrücken lassen (2 Möglichkeiten):</strong>
                                <ul>
                                    <li><strong>✅✉️ Freigeben & Bestätigungsmail senden:</strong> Holt die Person auf die reguläre Teilnehmerliste und schickt ihr sofort die offizielle Anmeldebestätigung (inkl. Zahlungsaufforderung).</li>
                                    <li><strong>✅ Nur freigeben:</strong> Verschiebt die Person ohne automatische E-Mail auf die reguläre Teilnehmerliste (z. B. wenn ihr vorher schon persönlich geschrieben habt).</li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </details>

                <details class="help-acc">
                    <summary>Wie verbuche ich eine Absage / Stornierung richtig?</summary>
                    <div class="help-acc-body">
                        <p style="margin-top:0;">Lösche abgesagte Anmeldungen am besten nicht sofort (falls schon Geld geflossen ist oder Rückfragen kommen), sondern verschiebe sie in die interne Gruppe <strong>Absagen</strong>:</p>
                        <ol>
                            <li>Klicke in der <strong>Anmeldeverwaltung</strong> auf den Namen der Person.</li>
                            <li>Stelle oben das Auswahlfeld <strong>Teilnehmergruppe</strong> auf <strong>Absagen (Interne Gruppe)</strong> um und klicke unten auf <strong>💾 Teilnehmerdaten speichern</strong>.</li>
                            <li><strong>Was passiert automatisch?</strong> Der reguläre Platz wird wieder frei, der Vorgang wird im Protokoll vermerkt, und die Person taucht automatisch <strong>nicht</strong> mehr in euren Küchen-, Sanitäter-, U18- oder Offene-Zahlungen-Listen auf!</li>
                        </ol>
                    </div>
                </details>

                <details class="help-acc">
                    <summary>Ein Teilnehmer sagt, er hat keine Bestätigungs-E-Mail erhalten – was tun?</summary>
                    <div class="help-acc-body">
                        <ol>
                            <li>Scrolle in der <strong>Anmeldeverwaltung</strong> nach unten zum gelben Kasten <strong>Noch nicht per E-Mail bestätigt</strong>.</li>
                            <li>Prüfe kurz, ob in der E-Mail-Adresse ein Tippfehler steckt (falls ja: über <strong>✏️ Bearbeiten</strong> im Stammsatz korrigieren und speichern).</li>
                            <li>Klicke auf <strong>✉️ E-Mail-Bestätigung erneut senden</strong> – oder klicke auf <strong>✔ Manuell freischalten</strong>, wenn dir die Person persönlich bestätigt hat, dass sie teilnehmen möchte.</li>
                        </ol>
                    </div>
                </details>

                <details class="help-acc">
                    <summary>Wie stelle ich verfügbare Plätze um oder trage jemanden selbst ein?</summary>
                    <div class="help-acc-body">
                        <ul>
                            <li><strong>Kontingente & Sperren anpassen:</strong> Unter <strong>Verfügbare Plätze</strong> können sowohl SL als auch Admins die maximale Platzzahl ändern, über <em>Manueller Override</em> eine feste Zahl freier Plätze für die Startseite vorgeben oder eine einzelne Gruppe per Haken <em>Gruppe voll / gesperrt</em> schließen.</li>
                            <li><strong>Interne Orga-Felder pflegen:</strong> Öffnest du einen Teilnehmer, findest du unten blau hinterlegt eure internen Felder (z. B. <em>Zimmerzuweisung, Aufbau-/Abbauhelfer, SL-Notizen</em>), die nur im Backend sichtbar sind.</li>
                            <li><strong>Teilnehmer manuell eintragen:</strong> Über den Button <strong>➕ Teilnehmer manuell eintragen</strong> oben rechts in der Anmeldeverwaltung kannst du Personen auch direkt selbst anlegen.</li>
                            <li><strong>Familien / Haushalte mit gleicher E-Mail:</strong> Im Online-Formular können mehrere verschiedene Personen (unterschiedlicher Name) problemlos dieselbe E-Mail-Adresse nutzen. Nach dem Absenden der ersten Anmeldung erscheint für Familien direkt der Button <em>„Weitere Person anmelden (Daten übernehmen)“</em>.</li>
                        </ul>
                    </div>
                </details>

                <details class="help-acc">
                    <summary>Wie drucke ich Küchen-, Sanitäter-, U18- oder Check-In-Listen (oder exportiere für Excel)?</summary>
                    <div class="help-acc-body">
                        <ol>
                            <li>Klicke links unter der Veranstaltung auf <strong>Listen</strong>.</li>
                            <li>Klicke oben einfach eine der fertigen Vorlagen an (z. B. <em>Minderjährige Teilnehmer (U18)</em>, <em>Offene Zahlungen</em>, <em>Küche & Verpflegung</em>, <em>Sanitäter & Gesundheit</em>, <em>SL-Charakterübersicht</em> oder <em>Check-In & Logistik</em>).</li>
                            <li>Oder stelle dir darunter unter <strong>Neue Auswertungsliste erstellen</strong> eigene Filter und Spalten zusammen und klicke auf <strong>🔍 Liste aktualisieren</strong>.</li>
                            <li>Mit <strong>🖨️ Druckansicht öffnen</strong> erhältst du eine saubere Schwarz-Weiß-Drucktabelle (im Browser auch direkt „Als PDF speichern“ wählbar); mit <strong>📥 Als CSV (Excel) exportieren</strong> lädst du die Tabelle für Excel herunter.</li>
                        </ol>
                    </div>
                </details>

                <details class="help-acc">
                    <summary>Wie ändere ich mein Passwort und schütze meinen Zugang mit 2FA?</summary>
                    <div class="help-acc-body">
                        <ol>
                            <li>Klicke ganz unten links in der Seitenleiste auf <strong>Passwort & 2FA</strong>.</li>
                            <li>Links änderst du dein Passwort; rechts kannst du die <strong>Zwei-Faktor-Authentifizierung (2FA)</strong> aktivieren.</li>
                            <li>Scanne dazu einfach den angezeigten QR-Code mit einer Authenticator-App (z. B. <em>Google Authenticator, Aegis, Bitwarden, 1Password, Apple Passwörter</em>) und gib den 6-stelligen Code ein.</li>
                            <li><strong>Wichtig:</strong> Speichere dir danach unbedingt die <strong>8 Einmal-Notfallcodes</strong> ab! Selbst ein Administrator kann dein 2FA im Web-Backend nicht ohne gültigen Code ausschalten.</li>
                        </ol>
                    </div>
                </details>

            </div>
        </div>

        <!-- TEIL 2: ADMINISTRATION & SYSTEM-EINRICHTUNG -->
        <div class="card">
            <div class="card-header">
                <h2>Einrichtung, Design & Datenschutz (nur für Administratoren)</h2>
                <?php if (!$isAdmin): ?>
                    <span class="badge badge-gray">Zur Information – nur mit Admin-Rechten sichtbar</span>
                <?php endif; ?>
            </div>
            <div class="card-body">

                <details class="help-acc">
                    <summary>Neue Veranstaltung anlegen, vom Vorjahr kopieren & Geheim-Link nutzen</summary>
                    <div class="help-acc-body">
                        <ul>
                            <li><strong>Con in 1 Minute anlegen:</strong> Klicke unter <em>Administration & Vorlagen</em> auf <strong>Neue Veranstaltung</strong>. Wähle ganz oben im Feld <em>Vorlage</em> eure letzte Veranstaltung aus – alle Teilnehmergruppen, Abfragefelder und gespeicherten Auswertungslisten werden komplett kopiert.</li>
                            <li><strong>Geheim-Link für Nachzügler:</strong> Nach dem Anlegen findest du ganz oben in den <strong>Con-Einstellungen</strong> den <em>Geheim-Link</em> (mit Kopier-Button). Wer diesen Link bekommt, kann sich auch bei geschlossener Anmeldung oder vollen Gruppen noch anmelden und überspringt automatisch die Warteliste.</li>
                        </ul>
                    </div>
                </details>

                <details class="help-acc">
                    <summary>Abfragefelder, Feldvorlagen, Verschlüsselung & Zweisprachigkeit (DE/EN)</summary>
                    <div class="help-acc-body">
                        <ul>
                            <li>Unter <strong>Abfragefelder</strong> bearbeitest du die Fragen der aktuell gewählten Veranstaltung; unter <strong>Feldvorlagen</strong> die globale Vorlage für zukünftige Veranstaltungen.</li>
                            <li><strong>Bedeutung der 5 Feld-Haken:</strong>
                                <ul>
                                    <li><strong>Pflichtfeld:</strong> Muss im Anmeldeformular ausgefüllt bzw. angehakt werden.</li>
                                    <li><strong>Verschlüsselt:</strong> Speichert den Wert verschlüsselt in der Datenbank (standardmäßig für E-Mail & Telefon aktiv; wird nach der Con <em>nicht</em> gelöscht).</li>
                                    <li><strong>Sensibel:</strong> Für Gesundheitsdaten wie Allergien/Krankheiten – wird verschlüsselt gespeichert <strong>und</strong> lässt sich nach der Con im DSGVO-Center mit einem Klick leeren.</li>
                                    <li><strong>Nur für Orga:</strong> Internes Feld, das Teilnehmer im Formular nicht sehen.</li>
                                    <li><strong>Übernahme:</strong> Wird vorausgefüllt, wenn jemand nach einer Anmeldung auf <em>„Weitere Person anmelden“</em> klickt (ideal für Nachname, Adresse, E-Mail, Telefon).</li>
                                </ul>
                            </li>
                            <li><strong>Zweisprachigkeit:</strong> Aktivierst du in den <em>Con-Einstellungen</em> den Haken <em>Zweisprachige Anmeldung</em>, erscheint links im Menü die <strong>Übersetzungstabelle</strong>, in der du alle englischen Bezeichnungen gesammelt eintragen (oder per Copy & Paste übersetzen) kannst.</li>
                        </ul>
                    </div>
                </details>

                <details class="help-acc">
                    <summary>Designs (Themes), Header-Modus & individuelles Veranstaltungs-Bild</summary>
                    <div class="help-acc-body">
                        <ul>
                            <li><strong>Globales Design vs. Con-Design:</strong> Unter <strong>Seiteneinstellungen</strong> wählst du das Standard-Theme für Startseite und Backend. Unter <strong>Con-Einstellungen</strong> kannst du jeder Veranstaltung ein eigenes Theme geben (z. B. <em>Keltensiegel, Feuervogel, Eisen & Banner, Reisechronik, Art Déco, Western, Orbital Terminal</em> oder <em>Arkham Grimoire</em>).</li>
                            <li><strong>Header-Modus & Bilder:</strong> Du kannst zwischen <em>Kompakter Kopfleiste</em>, <em>Zentriertem Wappen-/Banner-Header</em> und <em>Großem Hero-Header</em> wählen. Zusätzlich kannst du in den <em>Con-Einstellungen</em> ein <strong>eigenes Stimmungsbild in voller Formularbreite</strong> hochladen, das auf der Anmeldeseite direkt über dem Einführungstext erscheint.</li>
                        </ul>
                    </div>
                </details>

                <details class="help-acc">
                    <summary>E-Mail-Versand, Vorlagen & Test-E-Mail</summary>
                    <div class="help-acc-body">
                        <ul>
                            <li><strong>Versand ohne Technik-Aufwand:</strong> Standardmäßig verschickt das System E-Mails direkt über deinen eigenen Webserver (keine SMTP-Daten nötig, solange die Absenderadresse zu deiner Domain gehört). Optional kannst du unter <strong>Seiteneinstellungen</strong> einen externen SMTP-Server eintragen und jederzeit über <strong>Test-E-Mail senden</strong> prüfen.</li>
                            <li><strong>E-Mail-Vorlagen anpassen:</strong> Unter <strong>E-Mail-Vorlagen</strong> bearbeitest du die Texte aller automatischen Mails auf Deutsch und Englisch. Du kannst normalen Text mit Zeilenumbrüchen (Enter-Taste) schreiben; Platzhalter wie <code>{vorname}</code>, <code>{conname}</code> oder <code>{wartelistenplatz}</code> werden beim Senden automatisch ersetzt.</li>
                        </ul>
                    </div>
                </details>

                <details class="help-acc">
                    <summary>Nach der Con: Gesundheitsdaten per 1-Klick löschen & DSGVO-Auskunft</summary>
                    <div class="help-acc-body">
                        <ul>
                            <li><strong>Nach Veranstaltungsende aufräumen:</strong> Öffne das <strong>DSGVO-Center</strong> und klicke neben der vergangenen Con auf <strong>🧹 Sensible Daten jetzt leeren</strong>. Damit werden alle Gesundheitsangaben (Allergien, Krankheiten etc.) dieser Con unwiderruflich gelöscht, während Namen, Zahlungsdaten und Kontaktdaten erhalten bleiben.</li>
                            <li><strong>Auskunft oder Komplettlöschung auf Anfrage:</strong> Oben im <strong>DSGVO-Center</strong> kannst du nach Name oder E-Mail suchen, einen fertigen Auskunftsbericht über alle Veranstaltungen hinweg drucken oder alle Einträge einer Person endgültig löschen.</li>
                        </ul>
                    </div>
                </details>

                <details class="help-acc">
                    <summary>Wichtig für Backups & Notfall-Hilfe (Schlüsseldatei & 2FA-Reset per FTP)</summary>
                    <div class="help-acc-body">
                        <ul>
                            <li><strong>Schlüsseldatei <code>secret.key</code> sichern:</strong> Alle verschlüsselten Teilnehmerdaten können nur zusammen mit der Datei <code>secret.key</code> (Standardpfad: <code>storage/config/secret.key</code>, unter <em>Seiteneinstellungen</em> auch außerhalb des Webroots verschiebbar) entschlüsselt werden. Sichere diese Datei einmalig an einem sicheren Ort!</li>
                            <li><strong>Notfall-Reset bei verlorenem 2FA-Gerät + verlorenen Notfallcodes:</strong> Falls sich ein Benutzer komplett ausgesperrt hat, legt der Server-Inhaber per FTP/Dateimanager im Ordner <code>storage/config/</code> eine Textdatei namens <code>reset_2fa.txt</code> an, in der als einzige Zeile die Login-E-Mail-Adresse des betroffenen Accounts steht. Beim nächsten Login mit dem richtigen Passwort wird 2FA für diesen Account einmalig abgeschaltet und die Datei automatisch wieder gelöscht.</li>
                        </ul>
                    </div>
                </details>

            </div>
        </div>
        <?php
        AdminLayout::render('Hilfe & Handbuch', 'help', (string)ob_get_clean());
    }
}
