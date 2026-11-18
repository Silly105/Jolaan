<?php
declare(strict_types=1);

namespace App\Modules\Gdpr;

use App\Core\Auth;
use App\Core\Crypto;
use App\Core\Database;
use App\Modules\Admin\AdminLayout;
use App\Modules\Events\EventRepository;

class GdprController
{
    public static function handle(): void
    {
        if (!Auth::isAdmin()) {
            header('Location: admin.php');
            exit;
        }

        $tRegs   = Database::table('registrations');
        $tVals   = Database::table('registration_values');
        $tFields = Database::table('form_fields');
        $tEvents = Database::table('events');
        $tGroups = Database::table('event_groups');

        $searchQuery = trim((string)($_REQUEST['q'] ?? ''));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::requireCsrf();
            $subAction = (string)($_POST['sub_action'] ?? '');

            if ($subAction === 'purge_sensitive_event') {
                // 1-Klick-Bereinigung aller als "Sensible Daten" markierten Felder einer Veranstaltung
                $eventId = (int)($_POST['event_id'] ?? 0);
                if ($eventId > 0) {
                    $stmt = Database::query(
                        "UPDATE {$tVals} rv
                         INNER JOIN {$tFields} f ON f.id = rv.field_id
                         INNER JOIN {$tRegs} r ON r.id = rv.registration_id
                         SET rv.field_value = '', rv.is_encrypted = 0
                         WHERE r.event_id = :eid AND f.is_sensitive = 1 AND rv.field_value != ''",
                        ['eid' => $eventId]
                    );
                    $affected = $stmt->rowCount();
                    $_SESSION['flash_success'] = "Sensible Daten bereinigt: Es wurden {$affected} sensible Feldeinträge für diese Veranstaltung unwiderruflich geleert.";
                }
                header('Location: admin.php?module=gdpr');
                exit;
            } elseif ($subAction === 'delete_person_all') {
                // Komplette Löschung (Recht auf Vergessenwerden Art. 17 DSGVO) für eine E-Mail-Adresse
                $targetEmail = trim((string)($_POST['target_email'] ?? ''));
                if ($targetEmail !== '' && trim((string)($_POST['confirm_word'] ?? '')) === 'LÖSCHEN') {
                    Database::query(
                        "DELETE rv FROM {$tVals} rv INNER JOIN {$tRegs} r ON r.id = rv.registration_id WHERE r.email = :email",
                        ['email' => $targetEmail]
                    );
                    $stmt = Database::query("DELETE FROM {$tRegs} WHERE email = :email", ['email' => $targetEmail]);
                    $count = $stmt->rowCount();
                    $_SESSION['flash_success'] = "DSGVO-Löschung durchgeführt: Alle {$count} Anmeldungen zu „{$targetEmail}“ wurden vollständig gelöscht.";
                } else {
                    $_SESSION['flash_error'] = 'Bitte gib zur Bestätigung das Wort „LÖSCHEN“ ein.';
                }
                header('Location: admin.php?module=gdpr');
                exit;
            }
        }

        // DSGVO-Datenauskunft (Art. 15) suchen & ggf. als JSON oder Druckbericht exportieren
        $foundRecords = [];
        if ($searchQuery !== '') {
            $regs = Database::fetchAll(
                "SELECT r.*, e.title AS event_title, e.start_date, e.end_date, g.name_de AS group_name
                 FROM {$tRegs} r
                 LEFT JOIN {$tEvents} e ON e.id = r.event_id
                 LEFT JOIN {$tGroups} g ON g.id = r.group_id
                 WHERE r.email LIKE :q1 OR CONCAT(r.first_name, ' ', r.last_name) LIKE :q2
                 ORDER BY r.created_at DESC",
                ['q1' => '%' . $searchQuery . '%', 'q2' => '%' . $searchQuery . '%']
            );

            foreach ($regs as $r) {
                $rid = (int)$r['id'];
                $vals = Database::fetchAll(
                    "SELECT f.label_de, f.field_key, f.is_sensitive, f.is_orga_only, rv.field_value, rv.is_encrypted
                     FROM {$tVals} rv
                     INNER JOIN {$tFields} f ON f.id = rv.field_id
                     WHERE rv.registration_id = :rid
                     ORDER BY f.sort_order ASC",
                    ['rid' => $rid]
                );

                $decodedFields = [];
                foreach ($vals as $v) {
                    $raw = (string)$v['field_value'];
                    if ((int)$v['is_encrypted'] === 1 || Crypto::isEncrypted($raw)) {
                        $raw = Crypto::decrypt($raw);
                    }
                    $decodedFields[] = [
                        'label'        => $v['label_de'],
                        'key'          => $v['field_key'],
                        'value'        => $raw,
                        'is_sensitive' => (int)$v['is_sensitive'] === 1,
                        'is_orga_only' => (int)$v['is_orga_only'] === 1,
                    ];
                }
                $r['field_details'] = $decodedFields;
                $foundRecords[] = $r;
            }

            if (($_GET['export'] ?? '') === 'json') {
                header('Content-Type: application/json; charset=UTF-8');
                header('Content-Disposition: attachment; filename="dsgvo_auskunft_' . date('Ymd_His') . '.json"');
                echo json_encode([
                    'generated_at' => date('c'),
                    'query'        => $searchQuery,
                    'records'      => $foundRecords,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
            }

            if (($_GET['export'] ?? '') === 'print') {
                ?>
                <!DOCTYPE html>
                <html lang="de">
                <head>
                    <meta charset="UTF-8">
                    <title>DSGVO-Datenauskunft (Art. 15 DSGVO)</title>
                    <style>
                        body { font-family: Arial, sans-serif; font-size: 13px; color: #111; margin: 1.5cm; line-height: 1.45; }
                        h1 { font-size: 20px; border-bottom: 2px solid #222; padding-bottom: 6px; }
                        .record { border: 1px solid #888; border-radius: 6px; padding: 14px; margin-bottom: 18px; page-break-inside: avoid; }
                        .record h2 { margin: 0 0 8px 0; font-size: 15px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
                        th, td { border: 1px solid #ccc; padding: 5px 8px; text-align: left; }
                        th { width: 35%; background: #f3f3f3; }
                        @media print { .no-print { display: none; } }
                    </style>
                </head>
                <body>
                    <div class="no-print" style="margin-bottom:15px;">
                        <button onclick="window.print()" style="padding:6px 14px;font-weight:bold;cursor:pointer;">🖨️ Auskunft drucken / als PDF speichern</button>
                    </div>
                    <h1>Auskunft nach Art. 15 DSGVO – Gespeicherte personenbezogene Daten</h1>
                    <p>Suchkriterium: <strong><?= e($searchQuery) ?></strong> &middot; Erstellt am: <?= date('d.m.Y H:i') ?> Uhr &middot; Gefundene Datensätze: <?= count($foundRecords) ?></p>
                    <?php foreach ($foundRecords as $rec): ?>
                        <div class="record">
                            <h2>Veranstaltung: <?= e($rec['event_title'] ?? 'Unbekannt') ?> (<?= e(format_date($rec['start_date'] ?? '')) ?> – <?= e(format_date($rec['end_date'] ?? '')) ?>)</h2>
                            <table>
                                <tr><th>Name</th><td><?= e($rec['first_name'] . ' ' . $rec['last_name']) ?></td></tr>
                                <tr><th>E-Mail-Adresse</th><td><?= e($rec['email']) ?></td></tr>
                                <tr><th>Geburtsdatum</th><td><?= e(format_date($rec['birth_date'])) ?></td></tr>
                                <tr><th>Teilnehmergruppe</th><td><?= e($rec['group_name'] ?? '') ?></td></tr>
                                <tr><th>Anmeldedatum</th><td><?= e($rec['created_at']) ?></td></tr>
                                <tr><th>Verbuchter Zahlungsbetrag</th><td><?= number_format((float)$rec['amount_paid'], 2, ',', '.') ?> € (Voll bezahlt: <?= (int)$rec['is_fully_paid'] === 1 ? 'Ja' : 'Nein' ?>)</td></tr>
                                <?php foreach ($rec['field_details'] as $fd): ?>
                                    <tr>
                                        <th><?= e($fd['label']) ?> <?= $fd['is_sensitive'] ? '(Sensibel / Verschlüsselt)' : '' ?></th>
                                        <td><?= nl2br(e($fd['value'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    <?php endforeach; ?>
                </body>
                </html>
                <?php
                exit;
            }
        }

        // Übersicht der Veranstaltungen inkl. Anzahl aktuell vorhandener sensibler Feldeinträge
        $events = EventRepository::all();
        foreach ($events as &$ev) {
            $eid = (int)$ev['id'];
            $ev['sensitive_values_count'] = (int)Database::fetchScalar(
                "SELECT COUNT(*) FROM {$tVals} rv
                 INNER JOIN {$tFields} f ON f.id = rv.field_id
                 INNER JOIN {$tRegs} r ON r.id = rv.registration_id
                 WHERE r.event_id = :eid AND f.is_sensitive = 1 AND rv.field_value != ''",
                ['eid' => $eid]
            );
            $ev['sensitive_field_names'] = Database::fetchAll(
                "SELECT label_de FROM {$tFields} WHERE event_id = :eid AND is_sensitive = 1 ORDER BY sort_order ASC",
                ['eid' => $eid]
            );
        }
        unset($ev);

        ob_start();
        ?>
        <!-- 1. DSGVO-Auskunft & Datenexport (Art. 15 & Art. 17) -->
        <div class="card">
            <div class="card-header">
                <h2>🔎 DSGVO-Datenauskunft & Export (Art. 15 / Art. 17 DSGVO)</h2>
            </div>
            <div class="card-body">
                <p style="margin-top:0;color:#475569;font-size:0.9rem;">
                    Gib eine E-Mail-Adresse oder den Namen einer Person ein, um alle über sämtliche Veranstaltungen hinweg gespeicherten Daten einzusehen, als druckfertigen Bericht / JSON zu exportieren oder komplett zu löschen.
                </p>
                <form method="get" action="admin.php" style="display:flex;gap:0.75rem;max-width:600px;">
                    <input type="hidden" name="module" value="gdpr">
                    <input type="text" name="q" value="<?= e($searchQuery) ?>" placeholder="E-Mail-Adresse oder Vorname Nachname..." required>
                    <button type="submit" class="btn btn-primary">🔍 Suchen</button>
                </form>

                <?php if ($searchQuery !== ''): ?>
                    <div style="margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid #e2e8f0;">
                        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;margin-bottom:1rem;">
                            <h3 style="margin:0;font-size:1.05rem;">Gefundene Anmeldungen für „<?= e($searchQuery) ?>“: <?= count($foundRecords) ?></h3>
                            <?php if (!empty($foundRecords)): ?>
                                <div style="display:flex;gap:0.5rem;">
                                    <a href="admin.php?module=gdpr&q=<?= urlencode($searchQuery) ?>&export=print" target="_blank" class="btn btn-secondary btn-sm">🖨️ Druckbericht / PDF-Ansicht</a>
                                    <a href="admin.php?module=gdpr&q=<?= urlencode($searchQuery) ?>&export=json" class="btn btn-secondary btn-sm">📥 JSON-Datenexport</a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php foreach ($foundRecords as $rec): ?>
                            <div style="background:#f8fafc;border:1px solid #cbd5e1;border-radius:6px;padding:1rem;margin-bottom:1rem;">
                                <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;">
                                    <strong><?= e($rec['event_title'] ?? '') ?> – <?= e($rec['first_name'] . ' ' . $rec['last_name']) ?> (<?= e($rec['email']) ?>)</strong>
                                    <span class="badge badge-blue"><?= e($rec['group_name'] ?? '') ?></span>
                                </div>
                                <div style="font-size:0.85rem;display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:0.4rem;">
                                    <?php foreach ($rec['field_details'] as $fd): if ($fd['value'] === '') continue; ?>
                                        <div>
                                            <span style="color:#64748b;"><?= e($fd['label']) ?>:</span>
                                            <strong><?= e($fd['value']) ?></strong>
                                            <?php if ($fd['is_sensitive']): ?><span title="Verschlüsselt gespeichert">🔒</span><?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (!empty($foundRecords) && filter_var($searchQuery, FILTER_VALIDATE_EMAIL)): ?>
                            <form method="post" action="admin.php?module=gdpr" onsubmit="return confirm('Alle Anmeldungen dieser E-Mail-Adresse unwiderruflich löschen?');" style="background:#fef2f2;border:1px solid #fca5a5;padding:1rem;border-radius:6px;margin-top:1rem;">
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="sub_action" value="delete_person_all">
                                <input type="hidden" name="target_email" value="<?= e($searchQuery) ?>">
                                <strong style="color:#991b1b;">🗑️ Recht auf Löschung (Art. 17 DSGVO):</strong>
                                <p style="margin:0.35rem 0;font-size:0.88rem;">Um alle gespeicherten Anmeldungen zu <code><?= e($searchQuery) ?></code> restlos zu löschen, tippe <strong>LÖSCHEN</strong> ein:</p>
                                <div style="display:flex;gap:0.5rem;max-width:360px;">
                                    <input type="text" name="confirm_word" placeholder="LÖSCHEN" required>
                                    <button type="submit" class="btn btn-danger btn-sm">Endgültig löschen</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Sensible Daten nach einer Veranstaltung leeren (Nachfolger von felderleeren.php) -->
        <div class="card">
            <div class="card-header">
                <h2>🧹 1-Klick-Bereinigung sensibler Teilnehmerdaten nach Veranstaltungsende</h2>
            </div>
            <div class="card-body table-responsive">
                <p style="margin-top:0;color:#475569;font-size:0.9rem;">
                    Alle im Formular-Baukasten als <strong>„🔒 Sensible Daten (DSGVO)“</strong> gekennzeichneten Felder (z. B. Allergien, Krankheiten, Phobien, Schwangerschaft) werden mit <code>AES-256-GCM</code> verschlüsselt gespeichert und können hier nach Abschluss der Veranstaltung mit einem Klick geleert werden, während die restliche Abrechnungs-/Teilnehmerliste erhalten bleibt.
                </p>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Veranstaltung</th>
                            <th>Zeitraum</th>
                            <th>Sensible Felder dieser Veranstaltung</th>
                            <th>Aktuell gespeicherte sensible Einträge</th>
                            <th>Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $ev):
                            $eid = (int)$ev['id'];
                            $fNames = array_column($ev['sensitive_field_names'], 'label_de');
                            $cnt = (int)$ev['sensitive_values_count'];
                        ?>
                            <tr>
                                <td><strong><?= e($ev['title']) ?></strong></td>
                                <td><?= e(format_date($ev['start_date'])) ?> – <?= e(format_date($ev['end_date'])) ?></td>
                                <td>
                                    <?php if (!empty($fNames)): ?>
                                        <?php foreach ($fNames as $fn): ?>
                                            <span class="badge badge-amber" style="margin-right:0.25rem;">🔒 <?= e($fn) ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span style="color:#94a3b8;">Keine sensiblen Felder definiert</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($cnt > 0): ?>
                                        <span class="badge badge-red"><?= $cnt ?> verschlüsselte Einträge vorhanden</span>
                                    <?php else: ?>
                                        <span class="badge badge-green">0 (Bereinigt / Leer)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($cnt > 0): ?>
                                        <form method="post" action="admin.php?module=gdpr" onsubmit="return confirm('Alle sensiblen Daten (Allergien, Krankheiten etc.) für „<?= e($ev['title']) ?>“ jetzt unwiderruflich leeren?');" style="margin:0;">
                                            <?= Auth::csrfField() ?>
                                            <input type="hidden" name="sub_action" value="purge_sensitive_event">
                                            <input type="hidden" name="event_id" value="<?= $eid ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">🧹 Sensible Daten jetzt leeren</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color:#94a3b8;font-size:0.85rem;">Keine Aktion nötig</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        AdminLayout::render('DSGVO-Center & Datenbereinigung', 'gdpr', (string)ob_get_clean());
    }
}
