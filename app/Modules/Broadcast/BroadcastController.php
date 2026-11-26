<?php
declare(strict_types=1);

namespace App\Modules\Broadcast;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Mailer;
use App\Modules\Admin\AdminLayout;
use App\Modules\Events\EventRepository;
use App\Modules\Registration\RegistrationRepository;

class BroadcastController
{
    public static function handle(): void
    {
        if (!Auth::isOrga()) {
            header('Location: admin.php');
            exit;
        }

        $eventId = (int)($_REQUEST['event_id'] ?? ($_SESSION['active_event_id'] ?? 0));
        $event   = $eventId > 0 ? EventRepository::find($eventId) : null;
        if (!$event) {
            $_SESSION['flash_error'] = 'Bitte wähle zuerst in der Übersicht eine Veranstaltung aus.';
            header('Location: admin.php');
            exit;
        }

        $eventId     = (int)$event['id'];
        $isBilingual = (int)($event['is_bilingual'] ?? 0) === 1;
        $draftKey    = 'broadcast_draft_' . $eventId;

        // AJAX-Endpunkt für den gedrosselten Paket-Versand (5 Empfänger pro Aufruf)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['sub_action'] ?? '') === 'send_batch_ajax') {
            self::handleAjaxBatch($event, $draftKey);
            return;
        }

        $groups  = EventRepository::getGroupsWithSeats($eventId);
        $allRegs = RegistrationRepository::allForEvent($eventId, $event['start_date']);

        $target = (string)($_REQUEST['target'] ?? ($_SESSION[$draftKey]['target'] ?? 'regular_all'));
        if (isset($_REQUEST['filter_submitted'])) {
            $dedup = isset($_REQUEST['dedup']) ? 1 : 0;
        } else {
            $dedup = (int)($_SESSION[$draftKey]['dedup'] ?? 1);
        }

        [$filteredRegs, $uniqueBccEmails, $dedupSkippedCount] = self::filterRecipients($allRegs, $target, $dedup === 1);

        // POST-Aktion: Vorschau-E-Mail an eigene Adresse senden
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['sub_action'] ?? '') === 'send_preview_to_me') {
            Auth::requireCsrf();

            $subjectDe = trim((string)($_POST['subject_de'] ?? ''));
            $bodyDe    = trim((string)($_POST['body_de'] ?? ''));
            $subjectEn = trim((string)($_POST['subject_en'] ?? ''));
            $bodyEn    = trim((string)($_POST['body_en'] ?? ''));
            $previewTo = trim((string)($_POST['preview_to'] ?? ''));

            $_SESSION[$draftKey] = [
                'subject_de' => $subjectDe,
                'body_de'    => $bodyDe,
                'subject_en' => $subjectEn,
                'body_en'    => $bodyEn,
                'preview_to' => $previewTo,
                'target'     => $target,
                'dedup'      => $dedup,
            ];

            if ($previewTo === '' || !filter_var($previewTo, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['flash_error'] = 'Bitte gib für den Vorschau-Versand eine gültige Empfänger-E-Mail-Adresse an.';
            } elseif ($subjectDe === '' || $bodyDe === '') {
                $_SESSION['flash_error'] = 'Bitte fülle zuerst Betreff und Nachrichtentext (Deutsch) aus.';
            } else {
                $sampleReg = $filteredRegs[0] ?? [
                    'first_name'    => 'Max',
                    'last_name'     => 'Mustermann',
                    'group_name_de' => 'SC',
                    'group_name_en' => 'PC',
                    'amount_paid'   => 0.0,
                    'language'      => 'de',
                ];

                $varsDe     = self::buildPlaceholderVars($event, $sampleReg, false);
                $subjRender = '[VORSCHAU] ' . self::replacePlainPlaceholders($subjectDe, $varsDe);
                $htmlBody   = Mailer::parseTemplate($bodyDe, $varsDe);

                if ($isBilingual && $bodyEn !== '') {
                    $varsEn   = self::buildPlaceholderVars($event, $sampleReg, true);
                    $subjEnR  = self::replacePlainPlaceholders($subjectEn !== '' ? $subjectEn : $subjectDe, $varsEn);
                    $htmlBody .= '<hr style="margin:1.5rem 0;border:none;border-top:1px solid #cbd5e1;">'
                              . '<p style="font-size:12px;color:#64748b;"><strong>Englische Sprachversion (' . e($subjEnR) . '):</strong></p>'
                              . Mailer::parseTemplate($bodyEn, $varsEn);
                }

                if (Mailer::send($previewTo, $subjRender, $htmlBody)) {
                    $_SESSION['flash_success'] = "Vorschau-E-Mail wurde erfolgreich an {$previewTo} gesendet (mit Beispieldaten für Platzhalter).";
                } else {
                    $_SESSION['flash_error'] = 'Vorschau-E-Mail konnte nicht gesendet werden: ' . Mailer::getLastError();
                }
            }

            $redir = 'admin.php?module=broadcast&event_id=' . $eventId
                   . '&target=' . rawurlencode($target)
                   . '&filter_submitted=1'
                   . ($dedup === 1 ? '&dedup=1' : '');
            header('Location: ' . $redir);
            exit;
        }

        $draft     = $_SESSION[$draftKey] ?? [];
        $userEmail = trim((string)(Auth::user()['email'] ?? ''));
        $subjectDe = (string)($draft['subject_de'] ?? ('[' . $event['title'] . '] Wichtige Informationen zur Veranstaltung'));
        $bodyDe    = (string)($draft['body_de'] ?? "Hallo {vorname},\n\nhier sind die aktuellen Informationen zu {conname} ({condatum}):\n\n\nViele Grüße\nDeine Orga");
        $subjectEn = (string)($draft['subject_en'] ?? ('[' . ($event['title_en'] ?: $event['title']) . '] Important Event Information'));
        $bodyEn    = (string)($draft['body_en'] ?? '');
        $previewTo = (string)($draft['preview_to'] ?? $userEmail);

        // Eigene An-Adresse für mailto:-Links (damit Teilnehmer nur die Orga im An-Feld sehen und alle anderen sicher in BCC stehen)
        $orgaToEmail = trim((string)($event['admin_email'] ?? ''));
        if ($orgaToEmail === '' || !filter_var($orgaToEmail, FILTER_VALIDATE_EMAIL)) {
            $orgaToEmail = trim((string)Database::getSetting('mail_from_address', ''));
        }
        if ($orgaToEmail === '' || !filter_var($orgaToEmail, FILTER_VALIDATE_EMAIL)) {
            $orgaToEmail = $userEmail;
        }

        $recipientIds = array_map(static fn(array $r): int => (int)$r['id'], $filteredRegs);
        $bccChunks    = array_chunk($uniqueBccEmails, 50);
        $transport    = (string)Database::getSetting('mail_transport', 'mail');
        $fromAddress  = (string)Database::getSetting('mail_from_address', '');

        ob_start();
        ?>
        <style>
            .broadcast-guide {
                background: #f0f9ff;
                border: 1px solid #bae6fd;
                border-left: 4px solid #0284c7;
                border-radius: 8px;
                padding: 1rem 1.2rem;
                margin-bottom: 1.25rem;
                color: #0f172a;
                font-size: 0.9rem;
                line-height: 1.55;
            }
            .broadcast-guide strong.guide-title {
                display: block;
                font-size: 0.98rem;
                margin-bottom: 0.4rem;
                color: #0369a1;
            }
            .broadcast-guide ul {
                margin: 0.4rem 0 0;
                padding-left: 1.25rem;
            }
            .broadcast-guide li {
                margin-bottom: 0.25rem;
            }
            .progress-box {
                display: none;
                margin-top: 1rem;
                padding: 1rem 1.15rem;
                border-radius: 8px;
                background: #f8fafc;
                border: 1px solid #cbd5e1;
            }
            .progress-track {
                width: 100%;
                height: 18px;
                background: #e2e8f0;
                border-radius: 999px;
                overflow: hidden;
                margin: 0.5rem 0;
            }
            .progress-fill {
                height: 100%;
                width: 0%;
                background: var(--adm-accent);
                transition: width 0.3s ease;
            }
            .bcc-bundle-card {
                border: 1px solid #cbd5e1;
                border-radius: 7px;
                padding: 0.9rem 1rem;
                background: #f8fafc;
                margin-bottom: 0.85rem;
            }
            .bcc-copy-row {
                display: grid;
                grid-template-columns: 1fr auto;
                gap: 0.45rem;
                align-items: center;
                margin-top: 0.45rem;
            }
        </style>

        <!-- Einsteigerfreundliche Info-Box ganz oben -->
        <div class="broadcast-guide">
            <strong class="guide-title">Welcher Versandweg ist der richtige? (Kurzer Leitfaden für den Alltag)</strong>
            Wenn viele Personen gleichzeitig angeschrieben werden (z. B. mit der Anreise-Info kurz vor der Con), gibt es zwei typische Stolperfallen:
            <strong>Datenschutz</strong> (niemand darf die E-Mail-Adressen aller anderen Teilnehmer im „An“- oder „CC“-Feld sehen) und <strong>Spamfilter</strong> (wenn 100 Mails in derselben Sekunde verschickt werden, blockieren manche Anbieter die Zustellung).
            <ul>
                <li><strong>Empfohlener Standardweg (Abschnitt 2 direkt hier drunter):</strong> Nutze den integrierten System-Versand. Er schickt jedem Teilnehmer automatisch eine <strong>eigene einzelne E-Mail</strong> (100 % datenschutzkonform ohne sichtbare fremde Adressen) und legt zwischen kleinen 5er-Paketen kurze Pausen ein, damit Spamfilter nicht anschlagen. Schicke dir vorher einfach mit einem Klick eine Vorschau-Mail zu.</li>
                <li><strong>Alternative fürs eigene E-Mail-Programm (Abschnitt 3 weiter unten):</strong> Wenn du mehrere große PDF-Dateien anhängen möchtest oder lieber über dein privates Postfach (Thunderbird, Outlook, Apple Mail, Webmail) schreibst, findest du unten fertige <strong>50er-Pakete</strong>, die alle Adressen automatisch ins unsichtbare <strong>BCC-Feld (Blindkopie)</strong> setzen.</li>
            </ul>
        </div>

        <!-- 1. Empfängerkreis auswählen -->
        <div class="card">
            <div class="card-header">
                <h2>1. Empfängerkreis auswählen</h2>
                <span class="badge badge-blue" style="font-size:0.88rem;padding:0.3rem 0.65rem;">
                    <?= count($filteredRegs) ?> Empfänger ausgewählt
                </span>
            </div>
            <div class="card-body">
                <form method="get" action="admin.php" id="recipientFilterForm">
                    <input type="hidden" name="module" value="broadcast">
                    <input type="hidden" name="event_id" value="<?= $eventId ?>">
                    <input type="hidden" name="filter_submitted" value="1">

                    <div class="grid-2" style="align-items:end;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Zielgruppe dieser Rundmail</label>
                            <select name="target" onchange="document.getElementById('recipientFilterForm').submit();">
                                <option value="regular_all" <?= $target === 'regular_all' ? 'selected' : '' ?>>
                                    Alle bestätigten regulären Teilnehmer (ohne Warteliste &amp; ohne Absagen)
                                </option>
                                <option value="unpaid" <?= $target === 'unpaid' ? 'selected' : '' ?>>
                                    Nur Teilnehmer mit noch offener Zahlung (ohne Warteliste &amp; ohne Absagen)
                                </option>
                                <option value="waitlist" <?= $target === 'waitlist' ? 'selected' : '' ?>>
                                    Nur Personen auf der Warteliste (ohne Absagen)
                                </option>
                                <optgroup label="Einzelne Teilnehmergruppe (ohne Warteliste)">
                                    <?php foreach ($groups as $grp):
                                        $gVal = 'group_' . (int)$grp['id'];
                                        $isInternal = (int)($grp['show_in_registration'] ?? 1) === 0;
                                    ?>
                                        <option value="<?= e($gVal) ?>" <?= $target === $gVal ? 'selected' : '' ?>>
                                            Nur Gruppe: <?= e($grp['name_de']) ?><?= $isInternal ? ' (Interne Gruppe)' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom:0;">
                            <label style="display:flex;align-items:center;gap:0.5rem;font-weight:500;cursor:pointer;">
                                <input type="checkbox" name="dedup" value="1" <?= $dedup === 1 ? 'checked' : '' ?> onchange="document.getElementById('recipientFilterForm').submit();" style="width:auto;">
                                Gleiche E-Mail-Adressen zusammenfassen (Haushalte / Familien erhalten die Mail nur einmal)
                            </label>
                            <small class="hint">
                                <?php if ($dedup === 1 && $dedupSkippedCount > 0): ?>
                                    Aktuell wurden <strong><?= $dedupSkippedCount ?></strong> mehrfach vorkommende Familien-Adressen zusammengefasst. Entferne den Haken, wenn jedes Familienmitglied eine eigene personalisierte Mail mit seinem Vornamen erhalten soll.
                                <?php else: ?>
                                    Nur bestätigte E-Mail-Adressen werden angeschrieben. Noch unbestätigte Neuanmeldungen werden automatisch übersprungen.
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>

                    <div style="margin-top:0.85rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;">
                        <div class="hint" style="margin:0;">
                            Aktiver System-Absender: <strong><?= e($fromAddress !== '' ? $fromAddress : 'Standard-Webserver') ?></strong>
                            (Versandart: <?= $transport === 'smtp' ? 'Externer SMTP-Server' : 'Eigener Webserver / PHP mail()' ?>)
                        </div>
                        <button type="submit" class="btn btn-secondary btn-sm">🔍 Empfängerkreis aktualisieren</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. Weg B (Oben / Primär): Gedrosselter System-Versand -->
        <div class="card">
            <div class="card-header">
                <h2>2. Empfohlen: Rundmail direkt über das System versenden</h2>
            </div>
            <div class="card-body">
                <p class="hint" style="margin-top:0;margin-bottom:0.9rem;">
                    Du kannst normalen Text mit Zeilenumbrüchen (Enter-Taste) schreiben; Internet-Links (<code>https://...</code>) werden automatisch anklickbar gemacht.
                    Verfügbare Platzhalter:
                    <code>{vorname}</code>, <code>{nachname}</code>, <code>{conname}</code>, <code>{condatum}</code>, <code>{gruppe}</code>, <code>{betrag}</code>.
                </p>

                <form method="post" action="admin.php?module=broadcast&event_id=<?= $eventId ?>" id="broadcastSystemForm">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="event_id" value="<?= $eventId ?>">
                    <input type="hidden" name="target" value="<?= e($target) ?>">
                    <?php if ($dedup === 1): ?>
                        <input type="hidden" name="dedup" value="1">
                    <?php endif; ?>
                    <input type="hidden" name="sub_action" value="send_preview_to_me">

                    <?php if ($isBilingual): ?>
                        <div class="grid-2">
                            <div>
                                <div class="form-group">
                                    <label>Betreff (Deutsch) *</label>
                                    <input type="text" name="subject_de" id="bc_subject_de" value="<?= e($subjectDe) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Nachrichtentext (Deutsch) *</label>
                                    <textarea name="body_de" id="bc_body_de" rows="10" required><?= e($bodyDe) ?></textarea>
                                </div>
                            </div>
                            <div>
                                <div class="form-group">
                                    <label>Betreff (Englisch – optional für englischsprachige Teilnehmer)</label>
                                    <input type="text" name="subject_en" id="bc_subject_en" value="<?= e($subjectEn) ?>">
                                </div>
                                <div class="form-group">
                                    <label>Nachrichtentext (Englisch – falls leer, wird der deutsche Text gesendet)</label>
                                    <textarea name="body_en" id="bc_body_en" rows="10"><?= e($bodyEn) ?></textarea>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="form-group">
                            <label>Betreff *</label>
                            <input type="text" name="subject_de" id="bc_subject_de" value="<?= e($subjectDe) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Nachrichtentext *</label>
                            <textarea name="body_de" id="bc_body_de" rows="10" required><?= e($bodyDe) ?></textarea>
                        </div>
                        <input type="hidden" name="subject_en" id="bc_subject_en" value="">
                        <input type="hidden" name="body_en" id="bc_body_en" value="">
                    <?php endif; ?>

                    <!-- Vorab-Testversand an eigene Adresse -->
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:7px;padding:0.85rem 1rem;margin-bottom:1rem;display:flex;flex-wrap:wrap;align-items:end;gap:0.75rem;">
                        <div style="flex:1;min-width:240px;">
                            <label style="display:block;font-weight:600;font-size:0.84rem;margin-bottom:0.25rem;">Schritt A: Zuerst unverbindliche Vorschau-Mail testen</label>
                            <input type="email" name="preview_to" value="<?= e($previewTo) ?>" placeholder="deine-adresse@beispiel.de">
                        </div>
                        <div>
                            <button type="submit" class="btn btn-secondary">✉️ Vorschau an mich senden</button>
                        </div>
                    </div>

                    <!-- Haupt-Versandbutton mit Live-Fortschritt -->
                    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:0.75rem;">
                        <div class="hint" style="margin:0;">
                            <strong>Schritt B:</strong> Versendet die Nachricht in schonenden 5er-Paketen (mit kurzer Pause) und vermerkt den Versand im Protokoll jedes Teilnehmers.
                        </div>
                        <button type="button"
                                id="btnStartBroadcast"
                                class="btn btn-primary"
                                <?= empty($recipientIds) ? 'disabled' : '' ?>
                                onclick="startThrottledBroadcast();">
                            📨 Rundmail jetzt schonend an <?= count($recipientIds) ?> Empfänger senden
                        </button>
                    </div>
                </form>

                <!-- Fortschrittsanzeige während des Paket-Versands -->
                <div id="broadcastProgressBox" class="progress-box">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:0.5rem;font-weight:600;font-size:0.92rem;">
                        <span id="bcStatusLabel">Versand läuft – bitte dieses Browserfenster geöffnet lassen …</span>
                        <span id="bcCounterLabel">0 / <?= count($recipientIds) ?></span>
                    </div>
                    <div class="progress-track">
                        <div id="bcProgressFill" class="progress-fill"></div>
                    </div>
                    <div id="bcDetailInfo" class="hint" style="margin-top:0.35rem;">Bereite erstes Paket vor …</div>
                    <ul id="bcErrorList" style="display:none;margin:0.65rem 0 0;padding-left:1.2rem;color:#991b1b;font-size:0.84rem;"></ul>
                </div>
            </div>
        </div>

        <!-- 3. Weg A (Unten / Fallback): 50er-BCC-Pakete für lokales Mailprogramm -->
        <div class="card">
            <div class="card-header">
                <h2>3. Alternative: Im eigenen E-Mail-Programm öffnen (50er-BCC-Pakete)</h2>
                <span class="badge badge-gray"><?= count($uniqueBccEmails) ?> eindeutige Adressen in <?= count($bccChunks) ?> <?= count($bccChunks) === 1 ? 'Paket' : 'Paketen' ?></span>
            </div>
            <div class="card-body">
                <p class="hint" style="margin-top:0;margin-bottom:0.9rem;">
                    Falls du große Dateianhänge mitschicken möchtest oder dein eigenes Mailprogramm bevorzugst, sind alle eindeutigen E-Mail-Adressen deiner obigen Auswahl hier fertig in <strong>50er-Bündel</strong> aufgeteilt
                    (viele Anbieter wie Gmail, GMX oder Web.de erlauben maximal 50–100 Empfänger pro einzelner Mail).
                    Ein Klick auf den Button öffnet dein Standard-Mailprogramm und trägt die Adressen direkt ins unsichtbare <strong>BCC-Feld (Blindkopie)</strong> ein.
                    Falls du Webmail im Browser nutzt, kannst du darunter die fertige Adressliste mit einem Klick kopieren und selbst ins <strong>BCC-Feld</strong> einfügen.
                </p>

                <?php if (empty($bccChunks)): ?>
                    <p style="margin:0;color:#64748b;">Für den gewählten Filter sind aktuell keine bestätigten E-Mail-Adressen vorhanden.</p>
                <?php else: ?>
                    <?php foreach ($bccChunks as $idx => $chunk):
                        $pkgNum     = $idx + 1;
                        $fromNr     = $idx * 50 + 1;
                        $toNr       = $idx * 50 + count($chunk);
                        $commaStr   = implode(', ', $chunk);
                        $semiStr    = implode('; ', $chunk);
                        $mailtoHref = 'mailto:' . rawurlencode($orgaToEmail)
                                    . '?bcc=' . rawurlencode(implode(',', $chunk))
                                    . '&subject=' . rawurlencode('[' . $event['title'] . '] ');
                        $commaId    = 'bcc_comma_' . $pkgNum;
                        $semiId     = 'bcc_semi_' . $pkgNum;
                    ?>
                        <div class="bcc-bundle-card">
                            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.6rem;margin-bottom:0.55rem;">
                                <div>
                                    <strong>Paket <?= $pkgNum ?> von <?= count($bccChunks) ?></strong>
                                    <span class="hint" style="margin-left:0.4rem;">(Adressen <?= $fromNr ?>–<?= $toNr ?>, <?= count($chunk) ?> Empfänger)</span>
                                </div>
                                <a href="<?= e($mailtoHref) ?>" class="btn btn-secondary btn-sm">
                                    ✉️ Paket <?= $pkgNum ?> im E-Mail-Programm öffnen (automatisch in BCC)
                                </a>
                            </div>

                            <div class="bcc-copy-row">
                                <input type="text" id="<?= $commaId ?>" value="<?= e($commaStr) ?>" readonly style="font-size:0.8rem;background:#fff;color:#334155;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="copyInputToClipboard('<?= $commaId ?>', this)">📋 Komma-Liste (Thunderbird / Apple / Gmail)</button>
                            </div>
                            <div class="bcc-copy-row">
                                <input type="text" id="<?= $semiId ?>" value="<?= e($semiStr) ?>" readonly style="font-size:0.8rem;background:#fff;color:#334155;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="copyInputToClipboard('<?= $semiId ?>', this)">📋 Semikolon-Liste (Klassisches Outlook)</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <script>
        (function () {
            const recipientIds = <?= json_encode($recipientIds, JSON_THROW_ON_ERROR) ?>;
            const csrfToken    = <?= json_encode(Auth::csrfToken(), JSON_THROW_ON_ERROR) ?>;
            const eventId      = <?= (int)$eventId ?>;
            const batchSize    = 5;
            const pauseMs      = 1800;
            let isSending      = false;

            window.addEventListener('beforeunload', function (ev) {
                if (isSending) {
                    ev.preventDefault();
                    ev.returnValue = '';
                }
            });

            window.startThrottledBroadcast = async function () {
                if (isSending || recipientIds.length === 0) {
                    return;
                }

                const subjectDe = document.getElementById('bc_subject_de').value.trim();
                const bodyDe    = document.getElementById('bc_body_de').value.trim();
                const subjectEn = document.getElementById('bc_subject_en').value.trim();
                const bodyEn    = document.getElementById('bc_body_en').value.trim();

                if (!subjectDe || !bodyDe) {
                    alert('Bitte fülle zuerst Betreff und Nachrichtentext aus.');
                    return;
                }

                const confirmMsg = 'Möchtest du diese Rundmail jetzt verbindlich an ' + recipientIds.length + ' Empfänger versenden?\n\n'
                                 + 'Betreff: ' + subjectDe + '\n\n'
                                 + 'Hinweis: Bitte lasse dieses Browserfenster geöffnet, bis der Fortschrittsbalken 100 % erreicht hat.';
                if (!window.confirm(confirmMsg)) {
                    return;
                }

                isSending = true;
                const btn        = document.getElementById('btnStartBroadcast');
                const box        = document.getElementById('broadcastProgressBox');
                const fill       = document.getElementById('bcProgressFill');
                const statusLbl  = document.getElementById('bcStatusLabel');
                const counterLbl = document.getElementById('bcCounterLabel');
                const detailLbl  = document.getElementById('bcDetailInfo');
                const errList    = document.getElementById('bcErrorList');

                btn.disabled = true;
                box.style.display = 'block';
                errList.style.display = 'none';
                errList.innerHTML = '';
                fill.style.background = 'var(--adm-accent)';

                let processed = 0;
                let totalSent = 0;
                let totalFail = 0;
                const total   = recipientIds.length;
                const totalBatches = Math.ceil(total / batchSize);

                for (let b = 0; b < totalBatches; b++) {
                    const chunk = recipientIds.slice(b * batchSize, (b + 1) * batchSize);
                    detailLbl.textContent = 'Sende Paket ' + (b + 1) + ' von ' + totalBatches + ' (' + chunk.length + ' E-Mails) …';

                    const formData = new URLSearchParams();
                    formData.set('csrf_token', csrfToken);
                    formData.set('sub_action', 'send_batch_ajax');
                    formData.set('event_id', String(eventId));
                    formData.set('reg_ids', chunk.join(','));
                    formData.set('subject_de', subjectDe);
                    formData.set('body_de', bodyDe);
                    formData.set('subject_en', subjectEn);
                    formData.set('body_en', bodyEn);

                    try {
                        const resp = await fetch('admin.php?module=broadcast&event_id=' + eventId, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                            body: formData.toString()
                        });
                        const data = await resp.json();

                        if (!data || !data.ok) {
                            const errMsg = (data && data.error) ? data.error : 'Unbekannter Serverfehler beim Paketversand.';
                            totalFail += chunk.length;
                            appendErrorLine(errList, 'Paket ' + (b + 1) + ': ' + errMsg);
                        } else {
                            totalSent += Number(data.sent || 0);
                            totalFail += Number(data.failed || 0);
                            if (Array.isArray(data.errors)) {
                                data.errors.forEach(function (eText) {
                                    appendErrorLine(errList, eText);
                                });
                            }
                        }
                    } catch (netErr) {
                        totalFail += chunk.length;
                        appendErrorLine(errList, 'Netzwerkfehler bei Paket ' + (b + 1) + ': ' + String(netErr));
                    }

                    processed += chunk.length;
                    const pct = Math.min(100, Math.round((processed / total) * 100));
                    fill.style.width = pct + '%';
                    counterLbl.textContent = processed + ' / ' + total + ' (' + pct + ' %)';

                    if (b < totalBatches - 1) {
                        detailLbl.textContent = 'Kurze Spam-Schutz-Pause vor Paket ' + (b + 2) + ' …';
                        await new Promise(function (res) { setTimeout(res, pauseMs); });
                    }
                }

                isSending = false;
                btn.disabled = false;

                if (totalFail === 0) {
                    fill.style.background = '#16a34a';
                    statusLbl.textContent = 'Versand vollständig abgeschlossen!';
                    detailLbl.textContent = 'Alle ' + totalSent + ' E-Mails wurden erfolgreich übergeben und im Teilnehmer-Protokoll vermerkt.';
                } else {
                    fill.style.background = '#d97706';
                    statusLbl.textContent = 'Versand abgeschlossen (mit Hinweisen)';
                    detailLbl.textContent = totalSent + ' erfolgreich gesendet, ' + totalFail + ' fehlgeschlagen (siehe Hinweise unten).';
                }
            };

            function appendErrorLine(listEl, text) {
                listEl.style.display = 'block';
                const li = document.createElement('li');
                li.textContent = text;
                listEl.appendChild(li);
            }
        })();
        </script>
        <?php
        AdminLayout::render('Rundmail – ' . $event['title'], 'broadcast', (string)ob_get_clean(), $eventId);
    }

    /**
     * Verarbeitet ein einzelnes 5er-Paket per AJAX und gibt JSON zurück.
     */
    private static function handleAjaxBatch(array $event, string $draftKey): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        $token = (string)($_POST['csrf_token'] ?? '');
        if ($token === '' || !hash_equals(Auth::csrfToken(), $token)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Ungültiges Sicherheits-Token (CSRF). Bitte lade die Seite neu.']);
            return;
        }

        $eventId     = (int)$event['id'];
        $isBilingual = (int)($event['is_bilingual'] ?? 0) === 1;
        $subjectDe   = trim((string)($_POST['subject_de'] ?? ''));
        $bodyDe      = trim((string)($_POST['body_de'] ?? ''));
        $subjectEn   = trim((string)($_POST['subject_en'] ?? ''));
        $bodyEn      = trim((string)($_POST['body_en'] ?? ''));

        if ($subjectDe === '' || $bodyDe === '') {
            echo json_encode(['ok' => false, 'error' => 'Betreff und Nachrichtentext dürfen nicht leer sein.']);
            return;
        }

        $_SESSION[$draftKey] = array_merge($_SESSION[$draftKey] ?? [], [
            'subject_de' => $subjectDe,
            'body_de'    => $bodyDe,
            'subject_en' => $subjectEn,
            'body_en'    => $bodyEn,
        ]);

        // Session-Lock freigeben, damit der Browser während der SMTP-Verbindungen flüssig bleibt
        session_write_close();

        $rawIds = array_filter(array_map('intval', explode(',', (string)($_POST['reg_ids'] ?? ''))));
        $rawIds = array_slice(array_values(array_unique($rawIds)), 0, 10);

        $sent   = 0;
        $failed = 0;
        $errors = [];

        foreach ($rawIds as $idx => $rid) {
            if ($idx > 0) {
                usleep(150000); // 0,15 Sek. Abstand innerhalb des 5er-Pakets
            }

            $reg = RegistrationRepository::findWithValues($rid);
            if (!$reg || (int)$reg['event_id'] !== $eventId || (int)$reg['is_confirmed'] !== 1) {
                $failed++;
                $errors[] = "Anmeldung #{$rid} übersprungen (nicht gefunden oder unbestätigt).";
                continue;
            }

            $email = trim((string)($reg['email'] ?? ''));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $failed++;
                $errors[] = "{$reg['first_name']} {$reg['last_name']}: Ungültige E-Mail-Adresse.";
                continue;
            }

            $useEn    = ($isBilingual && ($reg['language'] ?? 'de') === 'en' && $bodyEn !== '');
            $vars     = self::buildPlaceholderVars($event, $reg, $useEn);
            $subjTpl  = ($useEn && $subjectEn !== '') ? $subjectEn : $subjectDe;
            $bodyTpl  = $useEn ? $bodyEn : $bodyDe;

            $subjFinal = self::replacePlainPlaceholders($subjTpl, $vars);
            $htmlBody  = Mailer::parseTemplate($bodyTpl, $vars);

            if (Mailer::send($email, $subjFinal, $htmlBody)) {
                $sent++;
                RegistrationRepository::appendHistoryLog($rid, 'Rundmail gesendet: „' . $subjFinal . '“', true);
            } else {
                $failed++;
                $errors[] = "{$reg['first_name']} {$reg['last_name']} ({$email}): " . Mailer::getLastError();
            }
        }

        echo json_encode([
            'ok'     => true,
            'sent'   => $sent,
            'failed' => $failed,
            'errors' => $errors,
        ]);
    }

    /**
     * Filtert die bestätigten Anmeldungen passend zum gewählten Zielgruppen-Filter.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, string>, 2: int}
     */
    private static function filterRecipients(array $allRegs, string $target, bool $deduplicateSystemList): array
    {
        $specificGroupId = null;
        if (preg_match('/^group_(\d+)$/', $target, $m)) {
            $specificGroupId = (int)$m[1];
        }

        $matched = [];
        foreach ($allRegs as $r) {
            if ((int)($r['is_confirmed'] ?? 0) !== 1) {
                continue;
            }
            $email = trim((string)($r['email'] ?? ''));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $isWaitlist  = (int)($r['is_waitlist'] ?? 0) === 1;
            $isPublicGrp = (int)($r['group_show_in_reg'] ?? 1) === 1;
            $isFullyPaid = (int)($r['is_fully_paid'] ?? 0) === 1;

            if ($specificGroupId !== null) {
                if ((int)$r['group_id'] !== $specificGroupId || $isWaitlist) {
                    continue;
                }
            } elseif ($target === 'unpaid') {
                if ($isWaitlist || !$isPublicGrp || $isFullyPaid) {
                    continue;
                }
            } elseif ($target === 'waitlist') {
                if (!$isWaitlist || !$isPublicGrp) {
                    continue;
                }
            } else {
                // Default: regular_all
                if ($isWaitlist || !$isPublicGrp) {
                    continue;
                }
            }

            $matched[] = $r;
        }

        $seenLower       = [];
        $uniqueBccEmails = [];
        $finalSystemRegs = [];
        $dedupSkipped    = 0;

        foreach ($matched as $r) {
            $cleanEmail = trim((string)$r['email']);
            $lower      = mb_strtolower($cleanEmail, 'UTF-8');

            if (!isset($seenLower[$lower])) {
                $seenLower[$lower] = true;
                $uniqueBccEmails[] = $cleanEmail;
                $finalSystemRegs[] = $r;
            } else {
                if ($deduplicateSystemList) {
                    $dedupSkipped++;
                } else {
                    $finalSystemRegs[] = $r;
                }
            }
        }

        return [$finalSystemRegs, $uniqueBccEmails, $dedupSkipped];
    }

    /**
     * Baut die Platzhalter-Variablen für einen Teilnehmer auf.
     *
     * @return array<string, string>
     */
    private static function buildPlaceholderVars(array $event, array $reg, bool $isEn): array
    {
        $lang      = $isEn ? 'en' : 'de';
        $conName   = ($isEn && trim((string)($event['title_en'] ?? '')) !== '') ? (string)$event['title_en'] : (string)$event['title'];
        $startFmt  = format_date($event['start_date'] ?? null, $lang);
        $endFmt    = format_date($event['end_date'] ?? null, $lang);
        $conDate   = ($endFmt !== '' && $endFmt !== $startFmt) ? ($startFmt . ' – ' . $endFmt) : $startFmt;
        $groupName = ($isEn && trim((string)($reg['group_name_en'] ?? '')) !== '')
            ? (string)$reg['group_name_en']
            : (string)($reg['group_name_de'] ?? '');
        $decSep    = $isEn ? '.' : ',';
        $amountFmt = number_format((float)($reg['amount_paid'] ?? 0), 2, $decSep, '') . ' €';

        return [
            'vorname'  => (string)($reg['first_name'] ?? ''),
            'nachname' => (string)($reg['last_name'] ?? ''),
            'conname'  => $conName,
            'condatum' => $conDate,
            'gruppe'   => $groupName,
            'betrag'   => $amountFmt,
        ];
    }

    /**
     * Ersetzt Platzhalter in einzeiligen Texten (z. B. E-Mail-Betreff) ohne HTML-Konvertierung.
     *
     * @param array<string, string> $vars
     */
    private static function replacePlainPlaceholders(string $text, array $vars): string
    {
        $map = [];
        foreach ($vars as $k => $v) {
            $map['{' . $k . '}'] = $v;
        }
        return strtr($text, $map);
    }
}
