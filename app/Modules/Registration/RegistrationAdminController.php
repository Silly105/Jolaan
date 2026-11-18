<?php
declare(strict_types=1);

namespace App\Modules\Registration;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Mailer;
use App\Modules\Admin\AdminLayout;
use App\Modules\Events\EventRepository;

class RegistrationAdminController
{
    public static function handle(): void
    {
        $eventId = (int)($_REQUEST['event_id'] ?? 0);
        $event   = $eventId > 0 ? EventRepository::find($eventId) : null;
        if (!$event) {
            header('Location: admin.php');
            exit;
        }

        $action = (string)($_REQUEST['action'] ?? 'list');
        $tRegs  = Database::table('registrations');
        $tVals  = Database::table('registration_values');

        // POST-Aktionen verarbeiten
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::requireCsrf();
            $subAction = (string)($_POST['sub_action'] ?? '');

            if ($subAction === 'quick_payment') {
                // Schnellspeichern von "Gezahlter Betrag", "Voller Preis bezahlt" & "Orga bestätigt" direkt aus der Liste
                foreach ($_POST['pay'] ?? [] as $rid => $pData) {
                    $rid = (int)$rid;
                    $amountRaw = str_replace(',', '.', trim((string)($pData['amount_paid'] ?? '0')));
                    $amount    = round((float)$amountRaw, 2);
                    $fullPaid  = isset($pData['is_fully_paid']) ? 1 : 0;
                    $orgaConf  = isset($pData['is_orga_confirmed']) ? 1 : 0;

                    Database::query(
                        "UPDATE {$tRegs} SET amount_paid = :amt, is_fully_paid = :full, is_orga_confirmed = :oconf WHERE id = :id AND event_id = :eid",
                        ['amt' => $amount, 'full' => $fullPaid, 'oconf' => $orgaConf, 'id' => $rid, 'eid' => $eventId]
                    );
                }
                $_SESSION['flash_success'] = 'Zahlungsstände und Bestätigungen wurden gespeichert.';
                header("Location: admin.php?module=registrations&event_id={$eventId}");
                exit;
            } elseif ($subAction === 'send_mail') {
                $regId    = (int)($_POST['reg_id'] ?? 0);
                $mailType = (string)($_POST['mail_type'] ?? '');
                $reg      = RegistrationRepository::findWithValues($regId);

                if ($reg && (int)$reg['event_id'] === $eventId) {
                    $sent = self::sendSystemMailToParticipant($event, $reg, $mailType);
                    if ($sent) {
                        $_SESSION['flash_success'] = "E-Mail ({$mailType}) wurde an {$reg['email']} gesendet.";
                    } else {
                        $_SESSION['flash_error'] = "E-Mail an {$reg['email']} konnte nicht gesendet werden.";
                    }
                }
                $redirectBack = ($_POST['return_to'] ?? '') === 'edit'
                    ? "admin.php?module=registrations&event_id={$eventId}&action=edit&id={$regId}"
                    : "admin.php?module=registrations&event_id={$eventId}";
                header("Location: {$redirectBack}");
                exit;
            } elseif ($subAction === 'save_person') {
                // Teilnehmer anlegen (Orga-Direkteintrag) oder bearbeiten
                $regId      = (int)($_POST['reg_id'] ?? 0);
                $groupId    = (int)($_POST['group_id'] ?? 0);
                $isConf     = isset($_POST['is_confirmed']) ? 1 : 0;
                $isOrgaConf = isset($_POST['is_orga_confirmed']) ? 1 : 0;
                $amountPaid = round((float)str_replace(',', '.', (string)($_POST['amount_paid'] ?? '0')), 2);
                $isFullPaid = isset($_POST['is_fully_paid']) ? 1 : 0;
                $lang       = ($_POST['language'] ?? 'de') === 'en' ? 'en' : 'de';

                $fields = EventRepository::getFields($eventId, true);
                $submittedVals = $_POST['f'] ?? [];

                // Checkboxen, die nicht angehakt wurden, auf '0' setzen
                foreach ($fields as $f) {
                    if (in_array($f['field_type'], ['checkbox', 'document'], true)) {
                        $k = $f['field_key'];
                        $submittedVals[$k] = isset($submittedVals[$k]) ? '1' : '0';
                    }
                }

                $coreData = [
                    'first_name'        => trim((string)($_POST['core_first_name'] ?? '')),
                    'last_name'         => trim((string)($_POST['core_last_name'] ?? '')),
                    'email'             => trim((string)($_POST['core_email'] ?? '')),
                    'birth_date'        => trim((string)($_POST['core_birth_date'] ?? '')) ?: null,
                    'is_confirmed'      => $isConf,
                    'is_orga_confirmed' => $isOrgaConf,
                    'amount_paid'       => $amountPaid,
                    'is_fully_paid'     => $isFullPaid,
                    'language'          => $lang,
                ];

                // Falls System-Mapping-Felder ausgefüllt wurden, synchron halten
                foreach ($fields as $f) {
                    $k = $f['field_key'];
                    $v = trim((string)($submittedVals[$k] ?? ''));
                    if ($f['system_mapping'] === 'firstname' && $v !== '') {
                        $coreData['first_name'] = $v;
                    } elseif ($f['system_mapping'] === 'lastname' && $v !== '') {
                        $coreData['last_name'] = $v;
                    } elseif ($f['system_mapping'] === 'email' && $v !== '') {
                        $coreData['email'] = $v;
                    } elseif ($f['system_mapping'] === 'birthdate' && $v !== '') {
                        $coreData['birth_date'] = $v;
                    }
                }

                if ($regId > 0) {
                    Database::query(
                        "UPDATE {$tRegs} SET
                         group_id = :gid, email = :email, first_name = :fname, last_name = :lname, birth_date = :bdate,
                         is_confirmed = :conf, is_orga_confirmed = :oconf, amount_paid = :paid, is_fully_paid = :fullpaid, language = :lang
                         WHERE id = :id AND event_id = :eid",
                        [
                            'gid'      => $groupId,
                            'email'    => $coreData['email'],
                            'fname'    => $coreData['first_name'],
                            'lname'    => $coreData['last_name'],
                            'bdate'    => $coreData['birth_date'],
                            'conf'     => $isConf,
                            'oconf'    => $isOrgaConf,
                            'paid'     => $amountPaid,
                            'fullpaid' => $isFullPaid,
                            'lang'     => $lang,
                            'id'       => $regId,
                            'eid'      => $eventId,
                        ]
                    );
                    RegistrationRepository::saveFieldValues($regId, $fields, $submittedVals);
                    $_SESSION['flash_success'] = 'Teilnehmerdaten wurden aktualisiert.';
                } else {
                    $coreData['doi_token'] = bin2hex(random_bytes(20));
                    $regId = RegistrationRepository::create($eventId, $groupId, $coreData, $fields, $submittedVals);
                    $_SESSION['flash_success'] = 'Teilnehmer wurde manuell eingetragen.';
                }

                header("Location: admin.php?module=registrations&event_id={$eventId}&action=edit&id={$regId}");
                exit;
            } elseif ($subAction === 'delete_person') {
                $delId = (int)($_POST['delete_id'] ?? 0);
                Database::query("DELETE FROM {$tVals} WHERE registration_id = :id", ['id' => $delId]);
                Database::query("DELETE FROM {$tRegs} WHERE id = :id AND event_id = :eid", ['id' => $delId, 'eid' => $eventId]);
                $_SESSION['flash_success'] = 'Anmeldung wurde gelöscht.';
                header("Location: admin.php?module=registrations&event_id={$eventId}");
                exit;
            }
        }

        if ($action === 'edit' || $action === 'new') {
            self::renderEditPerson($event, (int)($_GET['id'] ?? 0));
            return;
        }

        self::renderList($event);
    }

    private static function sendSystemMailToParticipant(array $event, array $reg, string $mailType): bool
    {
        $langSuffix = ($reg['language'] ?? 'de') === 'en' ? 'en' : 'de';
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir    = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

        $token = (string)$reg['doi_token'];
        if ($token === '') {
            $token = bin2hex(random_bytes(20));
            $tRegs = Database::table('registrations');
            Database::query("UPDATE {$tRegs} SET doi_token = :tok WHERE id = :id", ['tok' => $token, 'id' => (int)$reg['id']]);
        }

        $confirmLink = "{$scheme}://{$host}{$dir}/register.php?event_id={$event['id']}&action=confirm&id={$reg['id']}&token={$token}&lang={$langSuffix}";
        $startF = format_date($event['start_date'], $langSuffix);
        $endF   = format_date($event['end_date'], $langSuffix);

        $vars = [
            'vorname'  => $reg['first_name'],
            'nachname' => $reg['last_name'],
            'conname'  => $event['title'],
            'condatum' => ($startF === $endF) ? $startF : "{$startF} – {$endF}",
            'gruppe'   => ($langSuffix === 'en' && !empty($reg['group_name_en'])) ? $reg['group_name_en'] : ($reg['group_name_de'] ?? ''),
            'betrag'   => number_format((float)$reg['amount_paid'], 2, ',', '.'),
            'link'     => $confirmLink,
        ];

        $map = [
            'doi'      => ['mail_tpl_doi_subject_', 'mail_tpl_doi_body_'],
            'confirm'  => ['mail_tpl_confirm_subject_', 'mail_tpl_confirm_body_'],
            'reminder' => ['mail_tpl_reminder_subject_', 'mail_tpl_reminder_body_'],
            'paid'     => ['mail_tpl_paid_subject_', 'mail_tpl_paid_body_'],
        ];

        if (!isset($map[$mailType])) {
            return false;
        }

        $subjTpl = (string)Database::getSetting($map[$mailType][0] . $langSuffix, '{conname}');
        $bodyTpl = (string)Database::getSetting($map[$mailType][1] . $langSuffix, '');

        $tRegs = Database::table('registrations');
        if ($mailType === 'confirm') {
            Database::query("UPDATE {$tRegs} SET is_orga_confirmed = 1 WHERE id = :id", ['id' => (int)$reg['id']]);
        } elseif ($mailType === 'paid') {
            Database::query("UPDATE {$tRegs} SET is_fully_paid = 1 WHERE id = :id", ['id' => (int)$reg['id']]);
        }

        return Mailer::send(
            (string)$reg['email'],
            Mailer::parseTemplate($subjTpl, $vars),
            Mailer::parseTemplate($bodyTpl, $vars),
            trim((string)$event['admin_email']) ?: null
        );
    }

    private static function renderList(array $event): void
    {
        $eventId = (int)$event['id'];
        $groups  = EventRepository::getGroupsWithSeats($eventId);
        $regs    = RegistrationRepository::allForEvent($eventId, $event['start_date']);

        $confirmedByGroup = [];
        $unconfirmedDoi   = [];
        $totalMoney       = 0.0;
        $openPaymentCount = 0;

        foreach ($regs as $r) {
            $totalMoney += (float)$r['amount_paid'];
            if ((int)$r['is_confirmed'] === 0) {
                $unconfirmedDoi[] = $r;
            } else {
                $confirmedByGroup[(int)$r['group_id']][] = $r;
                if ((int)$r['is_fully_paid'] === 0) {
                    $openPaymentCount++;
                }
            }
        }

        ob_start();
        echo AdminLayout::renderEventSubnav($event, 'registrations');
        ?>
        <div class="card">
            <div class="card-header">
                <h2>📊 Zusammenfassung: <?= e($event['title']) ?></h2>
                <div>
                    <a href="admin.php?module=registrations&event_id=<?= $eventId ?>&action=new" class="btn btn-primary btn-sm">➕ Teilnehmer manuell eintragen (Orga-Anmeldung)</a>
                    <a href="admin.php?module=lists&event_id=<?= $eventId ?>" class="btn btn-secondary btn-sm">📋 Zu den Auswertungs- & Drucklisten</a>
                </div>
            </div>
            <div class="card-body">
                <div class="grid-3">
                    <div style="background:#f8fafc;padding:0.9rem 1.1rem;border-radius:6px;border:1px solid #e2e8f0;">
                        <div style="font-size:0.82rem;color:#64748b;">Anmeldungen (E-Mail bestätigt)</div>
                        <div style="font-size:1.45rem;font-weight:700;color:#0f172a;">
                            <?= count($regs) - count($unconfirmedDoi) ?>
                            <span style="font-size:0.85rem;font-weight:400;color:#64748b;">(+ <?= count($unconfirmedDoi) ?> unbestätigt)</span>
                        </div>
                    </div>
                    <div style="background:#f8fafc;padding:0.9rem 1.1rem;border-radius:6px;border:1px solid #e2e8f0;">
                        <div style="font-size:0.82rem;color:#64748b;">Verbuchte Zahlungseingänge (Summe)</div>
                        <div style="font-size:1.45rem;font-weight:700;color:#255E00;">
                            <?= number_format($totalMoney, 2, ',', '.') ?> €
                        </div>
                    </div>
                    <div style="background:#f8fafc;padding:0.9rem 1.1rem;border-radius:6px;border:1px solid #e2e8f0;">
                        <div style="font-size:0.82rem;color:#64748b;">Zahlungsstatus</div>
                        <div style="font-size:1.25rem;font-weight:700;color:<?= $openPaymentCount > 0 ? '#b45309' : '#166534' ?>;">
                            <?= $openPaymentCount ?> Zahlung(en) noch offen
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form method="post" action="admin.php?module=registrations&event_id=<?= $eventId ?>">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="sub_action" value="quick_payment">

            <?php foreach ($groups as $g):
                $gid = (int)$g['id'];
                $groupRegs = $confirmedByGroup[$gid] ?? [];
            ?>
                <div class="card">
                    <div class="card-header">
                        <h2>
                            <?= e($g['name_de']) ?> (<?= count($groupRegs) ?>)
                            <?php if ($g['calculated_free'] !== null): ?>
                                <span class="badge badge-green" style="margin-left:0.5rem;"><?= (int)$g['calculated_free'] ?> Plätze frei</span>
                            <?php endif; ?>
                        </h2>
                        <button type="submit" class="btn btn-primary btn-sm">💾 Zahlungen & Haken speichern</button>
                    </div>
                    <div class="card-body table-responsive">
                        <?php if (empty($groupRegs)): ?>
                            <p style="margin:0;color:#64748b;font-size:0.9rem;">Noch keine bestätigten Anmeldungen in dieser Gruppe.</p>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>E-Mail</th>
                                        <th>Alter (zum Con)</th>
                                        <th style="text-align:center;">Orga bestätigt</th>
                                        <th style="width:140px;">Bezahlter Betrag (€)</th>
                                        <th style="text-align:center;">Voller Preis bezahlt</th>
                                        <th>Status</th>
                                        <th>Details / Mails</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($groupRegs as $r):
                                        $rid = (int)$r['id'];
                                        $fullPaid = (int)$r['is_fully_paid'] === 1;
                                    ?>
                                        <tr>
                                            <td>
                                                <a href="admin.php?module=registrations&event_id=<?= $eventId ?>&action=edit&id=<?= $rid ?>" style="font-weight:600;color:#255E00;">
                                                    <?= e($r['last_name'] . ', ' . $r['first_name']) ?>
                                                </a>
                                            </td>
                                            <td><?= e($r['email']) ?></td>
                                            <td>
                                                <?php if ($r['age_at_event'] !== null): ?>
                                                    <?= (int)$r['age_at_event'] ?> J.
                                                    <?php if ($r['is_under18']): ?>
                                                        <span class="badge badge-red">U18</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    —
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align:center;">
                                                <input type="checkbox" name="pay[<?= $rid ?>][is_orga_confirmed]" value="1" <?= (int)$r['is_orga_confirmed'] === 1 ? 'checked' : '' ?>>
                                            </td>
                                            <td>
                                                <input type="text" name="pay[<?= $rid ?>][amount_paid]" value="<?= number_format((float)$r['amount_paid'], 2, ',', '') ?>" style="text-align:right;">
                                            </td>
                                            <td style="text-align:center;">
                                                <input type="checkbox" name="pay[<?= $rid ?>][is_fully_paid]" value="1" <?= $fullPaid ? 'checked' : '' ?>>
                                            </td>
                                            <td>
                                                <?php if ($fullPaid): ?>
                                                    <span class="badge badge-green">✔ Voll bezahlt</span>
                                                <?php elseif ((float)$r['amount_paid'] > 0): ?>
                                                    <span class="badge badge-amber">Teilzahlung (offen)</span>
                                                <?php else: ?>
                                                    <span class="badge badge-red">Offen</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="white-space:nowrap;">
                                                <a href="admin.php?module=registrations&event_id=<?= $eventId ?>&action=edit&id=<?= $rid ?>" class="btn btn-secondary btn-sm">✏️ Öffnen & Mails</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </form>

        <?php if (!empty($unconfirmedDoi)): ?>
            <div class="card" style="border-color:#fcd34d;">
                <div class="card-header" style="background:#fef3c7;">
                    <h2 style="color:#92400e;">⏳ Noch nicht per E-Mail bestätigt (Double-Opt-In ausstehend: <?= count($unconfirmedDoi) ?>)</h2>
                </div>
                <div class="card-body table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>E-Mail</th>
                                <th>Gruppe</th>
                                <th>Eingegangen am</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unconfirmedDoi as $r):
                                $rid = (int)$r['id'];
                            ?>
                                <tr>
                                    <td>
                                        <a href="admin.php?module=registrations&event_id=<?= $eventId ?>&action=edit&id=<?= $rid ?>" style="font-weight:600;">
                                            <?= e($r['last_name'] . ', ' . $r['first_name']) ?>
                                        </a>
                                    </td>
                                    <td><?= e($r['email']) ?></td>
                                    <td><?= e($r['group_name_de'] ?? '') ?></td>
                                    <td><?= e($r['created_at']) ?></td>
                                    <td style="display:flex;gap:0.4rem;">
                                        <form method="post" action="admin.php?module=registrations&event_id=<?= $eventId ?>" style="margin:0;">
                                            <?= Auth::csrfField() ?>
                                            <input type="hidden" name="sub_action" value="send_mail">
                                            <input type="hidden" name="reg_id" value="<?= $rid ?>">
                                            <input type="hidden" name="mail_type" value="doi">
                                            <button type="submit" class="btn btn-secondary btn-sm">✉️ Opt-In-Mail erneut senden</button>
                                        </form>
                                        <a href="admin.php?module=registrations&event_id=<?= $eventId ?>&action=edit&id=<?= $rid ?>" class="btn btn-secondary btn-sm">✏️ Bearbeiten / Manuell freischalten</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        <?php
        AdminLayout::render('Teilnehmerverwaltung: ' . $event['title'], 'registrations', (string)ob_get_clean(), $eventId);
    }

    private static function renderEditPerson(array $event, int $regId): void
    {
        $eventId = (int)$event['id'];
        $groups  = EventRepository::getGroupsWithSeats($eventId);
        $fields  = EventRepository::getFields($eventId, true);
        $reg     = $regId > 0 ? RegistrationRepository::findWithValues($regId) : null;
        $isNew   = ($reg === null);

        $r = $reg ?? [
            'id'                => 0,
            'group_id'          => (int)($groups[0]['id'] ?? 0),
            'first_name'        => '',
            'last_name'         => '',
            'email'             => '',
            'birth_date'        => '',
            'is_confirmed'      => 1,
            'is_orga_confirmed' => 1,
            'amount_paid'       => 0.00,
            'is_fully_paid'     => 0,
            'language'          => 'de',
            'values'            => [],
        ];

        $ageAtEvent = calculate_age_at($r['birth_date'] ?: null, $event['start_date']);

        ob_start();
        echo AdminLayout::renderEventSubnav($event, 'registrations');
        ?>

        <?php if (!$isNew): ?>
            <div class="card">
                <div class="card-header">
                    <h2>✉️ System-E-Mails an <?= e($r['first_name'] . ' ' . $r['last_name']) ?> (<?= e($r['email']) ?>) senden</h2>
                </div>
                <div class="card-body" style="display:flex;flex-wrap:wrap;gap:0.75rem;">
                    <?php
                    $mailButtons = [
                        'confirm'  => '✅ Anmeldebestätigung senden (& Orga-Haken setzen)',
                        'paid'     => '💶 Geldeingangsbestätigung senden (& Voller Preis bezahlt setzen)',
                        'reminder' => '⏰ Zahlungserinnerung senden',
                        'doi'      => '🔗 Double-Opt-In Bestätigungslink erneut senden',
                    ];
                    foreach ($mailButtons as $mType => $mLabel):
                    ?>
                        <form method="post" action="admin.php?module=registrations&event_id=<?= $eventId ?>" style="margin:0;">
                            <?= Auth::csrfField() ?>
                            <input type="hidden" name="sub_action" value="send_mail">
                            <input type="hidden" name="return_to" value="edit">
                            <input type="hidden" name="reg_id" value="<?= (int)$r['id'] ?>">
                            <input type="hidden" name="mail_type" value="<?= e($mType) ?>">
                            <button type="submit" class="btn btn-secondary btn-sm"><?= e($mLabel) ?></button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" action="admin.php?module=registrations&event_id=<?= $eventId ?>">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="sub_action" value="save_person">
            <input type="hidden" name="reg_id" value="<?= (int)$r['id'] ?>">

            <div class="card">
                <div class="card-header">
                    <h2><?= $isNew ? '➕ Teilnehmer manuell eintragen (Orga-Anmeldung)' : '⚙️ Status, Gruppe & Zahlung: ' . e($r['first_name'] . ' ' . $r['last_name']) ?></h2>
                    <a href="admin.php?module=registrations&event_id=<?= $eventId ?>" class="btn btn-secondary btn-sm">&larr; Zurück zur Liste</a>
                </div>
                <div class="card-body">
                    <div class="grid-3">
                        <div class="form-group">
                            <label for="group_id">Teilnehmergruppe*</label>
                            <select id="group_id" name="group_id" required>
                                <?php foreach ($groups as $g): ?>
                                    <option value="<?= (int)$g['id'] ?>" <?= (int)$r['group_id'] === (int)$g['id'] ? 'selected' : '' ?>>
                                        <?= e($g['name_de']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="amount_paid">Bezahlter Betrag (€)</label>
                            <input type="text" id="amount_paid" name="amount_paid" value="<?= number_format((float)$r['amount_paid'], 2, ',', '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="language">Sprache der Anmeldung</label>
                            <select id="language" name="language">
                                <option value="de" <?= ($r['language'] ?? 'de') === 'de' ? 'selected' : '' ?>>Deutsch (DE)</option>
                                <option value="en" <?= ($r['language'] ?? 'de') === 'en' ? 'selected' : '' ?>>Englisch (EN)</option>
                            </select>
                        </div>
                    </div>

                    <div style="display:flex;flex-wrap:wrap;gap:1.75rem;background:#f8fafc;padding:0.9rem 1.1rem;border-radius:6px;border:1px solid #e2e8f0;margin-bottom:1rem;">
                        <label style="margin:0;cursor:pointer;">
                            <input type="checkbox" name="is_confirmed" value="1" <?= (int)$r['is_confirmed'] === 1 ? 'checked' : '' ?>>
                            📧 <strong>E-Mail-Adresse bestätigt (Double-Opt-In)</strong>
                        </label>
                        <label style="margin:0;cursor:pointer;">
                            <input type="checkbox" name="is_orga_confirmed" value="1" <?= (int)$r['is_orga_confirmed'] === 1 ? 'checked' : '' ?>>
                            ✅ <strong>Anmeldung durch Orga bestätigt</strong>
                        </label>
                        <label style="margin:0;cursor:pointer;">
                            <input type="checkbox" name="is_fully_paid" value="1" <?= (int)$r['is_fully_paid'] === 1 ? 'checked' : '' ?>>
                            💶 <strong>Voller Preis bezahlt (Nichts mehr offen)</strong>
                        </label>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Vorname (Stammsatz)</label>
                            <input type="text" name="core_first_name" value="<?= e($r['first_name']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Nachname (Stammsatz)</label>
                            <input type="text" name="core_last_name" value="<?= e($r['last_name']) ?>">
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>E-Mail-Adresse (Stammsatz)</label>
                            <input type="email" name="core_email" value="<?= e($r['email']) ?>">
                        </div>
                        <div class="form-group">
                            <label>
                                Geburtsdatum (Stammsatz)
                                <?php if ($ageAtEvent !== null): ?>
                                    <span class="badge <?= $ageAtEvent < 18 ? 'badge-red' : 'badge-green' ?>">
                                        Alter zum Con-Beginn: <?= $ageAtEvent ?> Jahre <?= $ageAtEvent < 18 ? '(MINDERJÄHRIG)' : '' ?>
                                    </span>
                                <?php endif; ?>
                            </label>
                            <input type="date" name="core_birth_date" value="<?= e($r['birth_date']) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>📋 Formularfelder & Interne Orga-Felder</h2>
                </div>
                <div class="card-body">
                    <?php foreach ($fields as $f):
                        $type = $f['field_type'];
                        $key  = $f['field_key'];
                        $val  = (string)($r['values'][$key] ?? '');
                        $isOrgaOnly = (int)$f['is_orga_only'] === 1;
                        $isSens     = (int)$f['is_sensitive'] === 1;

                        if ($type === 'heading'):
                    ?>
                        <h3 style="margin:1.5rem 0 0.75rem;padding-bottom:0.35rem;border-bottom:2px solid #cbd5e1;color:#255E00;">
                            <?= e($f['label_de']) ?>
                        </h3>
                        <?php continue; endif; ?>

                        <div class="form-group" style="<?= $isOrgaOnly ? 'background:#eff6ff;padding:0.75rem 1rem;border-radius:6px;border:1px solid #bfdbfe;' : '' ?>">
                            <label for="f_<?= e($key) ?>">
                                <?= e($f['label_de']) ?>
                                <?php if ($isOrgaOnly): ?>
                                    <span class="badge badge-blue">👁️ Nur für Orga sichtbar</span>
                                <?php endif; ?>
                                <?php if ($isSens): ?>
                                    <span class="badge badge-amber">🔒 Sensible Daten (AES-256 verschlüsselt)</span>
                                <?php endif; ?>
                            </label>

                            <?php if ($type === 'textarea'): ?>
                                <textarea id="f_<?= e($key) ?>" name="f[<?= e($key) ?>]" rows="3"><?= e($val) ?></textarea>
                            <?php elseif ($type === 'select'):
                                $opts = json_decode((string)$f['options_json'], true)['de'] ?? [];
                            ?>
                                <select id="f_<?= e($key) ?>" name="f[<?= e($key) ?>]">
                                    <option value="">— Bitte wählen —</option>
                                    <?php foreach ($opts as $opt): ?>
                                        <option value="<?= e($opt) ?>" <?= $val === (string)$opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif ($type === 'checkbox' || $type === 'document'): ?>
                                <label style="font-weight:400;">
                                    <input type="checkbox" id="f_<?= e($key) ?>" name="f[<?= e($key) ?>]" value="1" <?= $val === '1' ? 'checked' : '' ?>>
                                    Ja / Akzeptiert
                                </label>
                            <?php else: ?>
                                <input type="<?= e(in_array($type, ['date', 'number', 'email'], true) ? $type : 'text') ?>" id="f_<?= e($key) ?>" name="f[<?= e($key) ?>]" value="<?= e($val) ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <div style="margin-top:1.5rem;">
                        <button type="submit" class="btn btn-primary" style="font-size:1rem;padding:0.65rem 1.5rem;">💾 Teilnehmerdaten speichern</button>
                    </div>
                </div>
            </div>
        </form>

        <?php if (!$isNew): ?>
            <div class="card" style="border-color:#fca5a5;">
                <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;">
                    <span>Diese Anmeldung unwiderruflich aus der Datenbank entfernen:</span>
                    <form method="post" action="admin.php?module=registrations&event_id=<?= $eventId ?>" onsubmit="return confirm('Anmeldung wirklich löschen?');" style="margin:0;">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="sub_action" value="delete_person">
                        <input type="hidden" name="delete_id" value="<?= (int)$r['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">🗑️ Anmeldung löschen</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        <?php
        AdminLayout::render($isNew ? 'Orga-Anmeldung' : 'Teilnehmer bearbeiten: ' . $r['first_name'] . ' ' . $r['last_name'], 'registrations', (string)ob_get_clean(), $eventId);
    }
}
