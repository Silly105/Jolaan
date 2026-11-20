<?php
declare(strict_types=1);

namespace App\Modules\Lists;

use App\Core\Auth;
use App\Core\Database;
use App\Modules\Admin\AdminLayout;
use App\Modules\Events\EventRepository;
use App\Modules\Registration\RegistrationRepository;

class ListGeneratorController
{
    public static function handle(): void
    {
        $allEvents = EventRepository::all();
        $eventId   = (int)($_REQUEST['event_id'] ?? ($_SESSION['active_event_id'] ?? 0));
        $event     = $eventId > 0 ? EventRepository::find($eventId) : null;

        if (!$event) {
            AdminLayout::render('Listen', 'lists', '<div class="card"><div class="card-body">Bitte wähle zuerst unter <a href="admin.php?module=dashboard">Übersicht</a> eine Veranstaltung aus.</div></div>');
            return;
        }

        $tViews = Database::table('saved_views');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::isAdmin()) {
            Auth::requireCsrf();
            $subAction = (string)($_POST['sub_action'] ?? '');

            if ($subAction === 'save_view') {
                $viewName = trim((string)($_POST['view_name'] ?? 'Neue Liste'));
                $saveGlobal = isset($_POST['save_as_global']);
                $cfg = [
                    'special_filter' => (string)($_POST['special_filter'] ?? ''),
                    'group_id'       => (string)($_POST['group_id'] ?? ''),
                    'payment_status' => (string)($_POST['payment_status'] ?? ''),
                    'field_filter'   => (string)($_POST['field_filter'] ?? ''),
                    'field_operator' => (string)($_POST['field_operator'] ?? 'not_empty'),
                    'field_value'    => trim((string)($_POST['field_value'] ?? '')),
                    'columns'        => array_values((array)($_POST['columns'] ?? [])),
                ];

                Database::query(
                    "INSERT INTO {$tViews} (event_id, name, config_json, sort_order) VALUES (:eid, :name, :cfg, 20)",
                    [
                        'eid'  => $saveGlobal ? null : $eventId,
                        'name' => $viewName,
                        'cfg'  => json_encode($cfg, JSON_UNESCAPED_UNICODE),
                    ]
                );
                $newViewId = Database::lastInsertId();
                $_SESSION['flash_success'] = "Liste „{$viewName}“ wurde gespeichert.";
                header("Location: admin.php?module=lists&event_id={$eventId}&view_id={$newViewId}");
                exit;
            } elseif ($subAction === 'delete_view') {
                $delId = (int)($_POST['delete_view_id'] ?? 0);
                Database::query("DELETE FROM {$tViews} WHERE id = :id", ['id' => $delId]);
                $_SESSION['flash_success'] = 'Gespeicherte Liste wurde gelöscht.';
                header("Location: admin.php?module=lists&event_id={$eventId}");
                exit;
            } elseif ($subAction === 'copy_views_from_event') {
                $sourceEid = (int)($_POST['source_event_id'] ?? 0);
                $srcViews  = EventRepository::getSavedViews($sourceEid > 0 ? $sourceEid : null);
                $copied = 0;
                foreach ($srcViews as $sv) {
                    Database::query(
                        "INSERT INTO {$tViews} (event_id, name, config_json, sort_order) VALUES (:eid, :name, :cfg, :sort)",
                        ['eid' => $eventId, 'name' => $sv['name'], 'cfg' => $sv['config_json'], 'sort' => (int)$sv['sort_order']]
                    );
                    $copied++;
                }
                $_SESSION['flash_success'] = "{$copied} Listen-Vorlagen wurden in diese Veranstaltung übernommen.";
                header("Location: admin.php?module=lists&event_id={$eventId}");
                exit;
            }
        }

        $savedViews  = EventRepository::getSavedViews($eventId);
        $globalViews = EventRepository::getSavedViews(null);
        $groups      = EventRepository::getGroupsWithSeats($eventId);
        $fields      = array_values(array_filter(EventRepository::getFields($eventId, true), fn($f) => $f['field_type'] !== 'heading'));

        $selectedViewId = (int)($_GET['view_id'] ?? 0);
        $activeViewName = 'Individuelle Auswertung';
        $config = [
            'special_filter' => (string)($_GET['special_filter'] ?? ''),
            'group_id'       => (string)($_GET['group_id'] ?? ''),
            'payment_status' => (string)($_GET['payment_status'] ?? ''),
            'field_filter'   => (string)($_GET['field_filter'] ?? ''),
            'field_operator' => (string)($_GET['field_operator'] ?? 'not_empty'),
            'field_value'    => trim((string)($_GET['field_value'] ?? '')),
            'columns'        => (array)($_GET['columns'] ?? ['group', 'email', 'amount_paid', 'is_fully_paid']),
        ];

        if ($selectedViewId > 0) {
            foreach (array_merge($savedViews, $globalViews) as $sv) {
                if ((int)$sv['id'] === $selectedViewId) {
                    $decoded = json_decode((string)$sv['config_json'], true);
                    if (is_array($decoded)) {
                        $config = array_merge($config, $decoded);
                        $activeViewName = $sv['name'];
                    }
                    break;
                }
            }
        }

        $availableColumns = [
            'group'             => 'Teilnehmergruppe',
            'email'             => 'E-Mail-Adresse',
            'birth_date'        => 'Geburtsdatum',
            'age_at_event'      => 'Alter (zum Con-Start)',
            'amount_paid'       => 'Bezahlter Betrag (€)',
            'is_fully_paid'     => 'Voll bezahlt (Ja/Nein)',
            'is_orga_confirmed' => 'Orga bestätigt',
            'created_at'        => 'Anmeldedatum',
        ];
        foreach ($fields as $f) {
            $k = $f['field_key'];
            if (!in_array($f['system_mapping'], ['firstname', 'lastname'], true)) {
                $availableColumns[$k] = $f['label_de'] . ((int)$f['is_orga_only'] === 1 ? ' (Orga)' : '');
            }
        }

        // Alle bestätigten regulären Anmeldungen (oder bei Bedarf auch Warteliste) laden
        $allRegs = array_filter(
            RegistrationRepository::allForEvent($eventId, $event['start_date']),
            fn($r) => (int)$r['is_confirmed'] === 1 && (int)($r['is_waitlist'] ?? 0) === 0
        );

        $filteredRegs = [];
        foreach ($allRegs as $r) {
            if ($config['special_filter'] === 'under18' && !$r['is_under18']) {
                continue;
            }
            if ($config['group_id'] !== '' && (int)$r['group_id'] !== (int)$config['group_id']) {
                continue;
            }
            if ($config['payment_status'] === 'paid' && (int)$r['is_fully_paid'] !== 1) {
                continue;
            }
            if ($config['payment_status'] === 'unpaid' && (int)$r['is_fully_paid'] === 1) {
                continue;
            }
            if ($config['field_filter'] !== '') {
                $fKey = $config['field_filter'];
                $fVal = trim((string)($r['values'][$fKey] ?? ''));
                $op   = $config['field_operator'];
                $cmp  = $config['field_value'];

                if ($op === 'not_empty' && ($fVal === '' || $fVal === '0' || strtolower($fVal) === 'keine' || strtolower($fVal) === 'nein')) {
                    continue;
                } elseif ($op === 'equals' && mb_strtolower($fVal) !== mb_strtolower($cmp)) {
                    continue;
                } elseif ($op === 'contains' && ($cmp === '' || mb_stripos($fVal, $cmp) === false)) {
                    continue;
                }
            }

            $filteredRegs[] = $r;
        }

        $getCellValue = function (array $r, string $colKey): string {
            return match ($colKey) {
                'group'             => (string)($r['group_name_de'] ?? ''),
                'email'             => (string)$r['email'],
                'birth_date'        => format_date($r['birth_date'] ?? null),
                'age_at_event'      => $r['age_at_event'] !== null ? ($r['age_at_event'] . ' J.' . ($r['is_under18'] ? ' (U18)' : '')) : '—',
                'amount_paid'       => number_format((float)$r['amount_paid'], 2, ',', '.') . ' €',
                'is_fully_paid'     => (int)$r['is_fully_paid'] === 1 ? 'Ja' : 'Offen',
                'is_orga_confirmed' => (int)$r['is_orga_confirmed'] === 1 ? 'Ja' : 'Nein',
                'created_at'        => (string)$r['created_at'],
                default             => (string)($r['values'][$colKey] ?? ''),
            };
        };

        $selectedCols = array_values(array_filter($config['columns'], fn($c) => isset($availableColumns[$c])));

        // CSV-Export
        $exportFormat = (string)($_GET['export'] ?? '');
        if ($exportFormat === 'csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="liste_' . $eventId . '_' . date('Ymd_His') . '.csv"');
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            $headerRow = ['Nachname', 'Vorname'];
            foreach ($selectedCols as $cKey) {
                $headerRow[] = $availableColumns[$cKey];
            }
            fputcsv($out, $headerRow, ';');

            foreach ($filteredRegs as $r) {
                $row = [$r['last_name'], $r['first_name']];
                foreach ($selectedCols as $cKey) {
                    $row[] = $getCellValue($r, $cKey);
                }
                fputcsv($out, $row, ';');
            }
            fclose($out);
            exit;
        }

        // Druckansicht
        if ($exportFormat === 'print') {
            ?>
            <!DOCTYPE html>
            <html lang="de">
            <head>
                <meta charset="UTF-8">
                <title>Druckansicht: <?= e($activeViewName) ?> – <?= e($event['title']) ?></title>
                <style>
                    body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #000; margin: 1.5cm; }
                    h1 { font-size: 18px; margin: 0 0 4px 0; }
                    .meta { font-size: 12px; color: #444; margin-bottom: 14px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #333; padding: 5px 7px; text-align: left; vertical-align: top; }
                    th { background: #eaeaea; font-weight: bold; }
                    .no-print { margin-bottom: 15px; }
                    @media print { .no-print { display: none; } body { margin: 0.5cm; } }
                </style>
            </head>
            <body>
                <div class="no-print">
                    <button onclick="window.print()" style="padding:6px 14px;font-weight:bold;cursor:pointer;">🖨️ Jetzt drucken</button>
                    <button onclick="window.close()" style="padding:6px 14px;cursor:pointer;margin-left:8px;">Schließen</button>
                </div>
                <h1><?= e($event['title']) ?> – <?= e($activeViewName) ?></h1>
                <div class="meta">
                    Veranstaltungsdatum: <?= e(format_date($event['start_date'])) ?> bis <?= e(format_date($event['end_date'])) ?>
                    &middot; Stand: <?= date('d.m.Y H:i') ?> Uhr &middot; Anzahl Einträge: <strong><?= count($filteredRegs) ?></strong>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th style="width:32px;">#</th>
                            <th>Nachname, Vorname</th>
                            <?php foreach ($selectedCols as $cKey): ?>
                                <th><?= e($availableColumns[$cKey]) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $idx = 1; foreach ($filteredRegs as $r): ?>
                            <tr>
                                <td><?= $idx++ ?></td>
                                <td><strong><?= e($r['last_name'] . ', ' . $r['first_name']) ?></strong></td>
                                <?php foreach ($selectedCols as $cKey): ?>
                                    <td><?= nl2br(e($getCellValue($r, $cKey))) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </body>
            </html>
            <?php
            exit;
        }

        $currentQuery = $_GET;
        $currentQuery['module'] = 'lists';
        $currentQuery['event_id'] = $eventId;
        $currentQuery['columns'] = $selectedCols;
        $printUrl = 'admin.php?' . http_build_query(array_merge($currentQuery, ['export' => 'print']));
        $csvUrl   = 'admin.php?' . http_build_query(array_merge($currentQuery, ['export' => 'csv']));

        ob_start();
        ?>

        <!-- Gespeicherte Listen-Vorlagen (untereinander, eine pro Zeile, ohne Veranstaltungs-Dropdown & ohne 📌/📥) -->
        <div class="card">
            <div class="card-header">
                <h2>📋 Gespeicherte Auswertungslisten für „<?= e($event['title']) ?>“</h2>
            </div>
            <div class="card-body">
                <div style="display:flex;flex-direction:column;gap:0.45rem;max-width:640px;">
                    <?php foreach ($savedViews as $sv):
                        $vid = (int)$sv['id'];
                        $isActive = ($selectedViewId === $vid);
                    ?>
                        <div style="display:flex;align-items:center;justify-content:space-between;background:<?= $isActive ? 'var(--adm-accent)' : '#f8fafc' ?>;border-radius:6px;border:1px solid #cbd5e1;overflow:hidden;">
                            <a href="admin.php?module=lists&event_id=<?= $eventId ?>&view_id=<?= $vid ?>" style="flex:1;padding:0.48rem 0.9rem;text-decoration:none;font-weight:600;font-size:0.88rem;color:<?= $isActive ? '#fff' : '#1e293b' ?>;">
                                <?= e($sv['name']) ?>
                            </a>
                            <?php if (Auth::isAdmin()): ?>
                                <form method="post" action="admin.php?module=lists&event_id=<?= $eventId ?>" onsubmit="return confirm('Gespeicherte Liste löschen?');" style="margin:0;">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="sub_action" value="delete_view">
                                    <input type="hidden" name="delete_view_id" value="<?= $vid ?>">
                                    <button type="submit" style="background:transparent;border:none;color:<?= $isActive ? '#fca5a5' : '#94a3b8' ?>;cursor:pointer;padding:0.45rem 0.7rem;font-weight:bold;" title="Liste löschen">✕</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (Auth::isAdmin()): ?>
                    <form method="post" action="admin.php?module=lists&event_id=<?= $eventId ?>" style="display:flex;flex-wrap:wrap;gap:0.5rem;align-items:center;margin-top:1rem;padding-top:0.85rem;border-top:1px solid #e2e8f0;">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="sub_action" value="copy_views_from_event">
                        <span style="font-size:0.84rem;color:#64748b;">Listen aus anderer Veranstaltung übernehmen</span>
                        <select name="source_event_id" style="width:auto;padding:0.3rem 0.6rem;font-size:0.84rem;">
                            <option value="0">Globale Standard-Listen</option>
                            <?php foreach ($allEvents as $otherEv): if ((int)$otherEv['id'] === $eventId) continue; ?>
                                <option value="<?= (int)$otherEv['id'] ?>"><?= e($otherEv['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-secondary btn-sm">Listen hierher kopieren</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Neue Auswertungsliste erstellen -->
        <div class="card">
            <div class="card-header">
                <h2>🔍 Neue Auswertungsliste erstellen</h2>
            </div>
            <div class="card-body">
                <form method="get" action="admin.php" id="filterForm">
                    <input type="hidden" name="module" value="lists">
                    <input type="hidden" name="event_id" value="<?= $eventId ?>">

                    <div class="grid-3">
                        <div class="form-group">
                            <label>Spezialfilter (Altersprüfung)</label>
                            <select name="special_filter">
                                <option value="">— Alle Altersgruppen —</option>
                                <option value="under18" <?= $config['special_filter'] === 'under18' ? 'selected' : '' ?>>
                                    🔞 Nur Minderjährige (unter 18 zum Con-Start am <?= e(format_date($event['start_date'])) ?>)
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Teilnehmergruppe</label>
                            <select name="group_id">
                                <option value="">— Alle Gruppen —</option>
                                <?php foreach ($groups as $g): ?>
                                    <option value="<?= (int)$g['id'] ?>" <?= (string)$config['group_id'] === (string)$g['id'] ? 'selected' : '' ?>>
                                        <?= e($g['name_de']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Zahlungsstatus</label>
                            <select name="payment_status">
                                <option value="">— Alle —</option>
                                <option value="unpaid" <?= $config['payment_status'] === 'unpaid' ? 'selected' : '' ?>>🔴 Noch offen (Voller Preis nicht bezahlt)</option>
                                <option value="paid" <?= $config['payment_status'] === 'paid' ? 'selected' : '' ?>>🟢 Vollständig bezahlt</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid-3">
                        <div class="form-group">
                            <label>Nach Abfragefeld filtern</label>
                            <select name="field_filter">
                                <option value="">— Kein Feldfilter —</option>
                                <?php foreach ($fields as $f): ?>
                                    <option value="<?= e($f['field_key']) ?>" <?= $config['field_filter'] === $f['field_key'] ? 'selected' : '' ?>>
                                        <?= e($f['label_de']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Bedingung</label>
                            <select name="field_operator">
                                <option value="not_empty" <?= $config['field_operator'] === 'not_empty' ? 'selected' : '' ?>>Ist ausgefüllt / Ja (nicht leer/Nein/Keine)</option>
                                <option value="contains" <?= $config['field_operator'] === 'contains' ? 'selected' : '' ?>>Enthält Suchbegriff</option>
                                <option value="equals" <?= $config['field_operator'] === 'equals' ? 'selected' : '' ?>>Ist exakt gleich</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Vergleichswert (für „Enthält“ / „Exakt“)</label>
                            <input type="text" name="field_value" value="<?= e($config['field_value']) ?>" placeholder="z. B. Vegetarisch">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Angezeigte Spalten in der Tabelle / Druckansicht / CSV</label>
                        <div style="display:flex;flex-wrap:wrap;gap:0.65rem;background:#f8fafc;padding:0.85rem;border-radius:6px;border:1px solid #e2e8f0;">
                            <?php foreach ($availableColumns as $colKey => $colLabel): ?>
                                <label style="font-weight:400;font-size:0.86rem;margin:0;display:inline-flex;align-items:center;gap:0.3rem;cursor:pointer;">
                                    <input type="checkbox" name="columns[]" value="<?= e($colKey) ?>" <?= in_array($colKey, $selectedCols, true) ? 'checked' : '' ?>>
                                    <?= e($colLabel) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center;justify-content:space-between;">
                        <div>
                            <button type="submit" class="btn btn-primary">🔍 Liste aktualisieren</button>
                            <a href="<?= e($printUrl) ?>" target="_blank" class="btn btn-secondary">🖨️ Druckansicht öffnen</a>
                            <a href="<?= e($csvUrl) ?>" class="btn btn-secondary">📥 Als CSV (Excel) exportieren</a>
                        </div>
                    </div>
                </form>

                <?php if (Auth::isAdmin()): ?>
                    <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid #e2e8f0;display:flex;flex-wrap:wrap;gap:0.6rem;align-items:center;">
                        <input type="text" id="save_view_name_input" placeholder="Name für neue Listen-Vorlage (z. B. Küchenliste)..." style="max-width:320px;">
                        <label style="font-weight:400;font-size:0.85rem;margin:0;">
                            <input type="checkbox" id="save_view_global_chk"> Auch als globale Vorlage speichern
                        </label>
                        <button type="button" onclick="submitSaveCurrentView()" class="btn btn-secondary btn-sm">Aktuelle Filter & Spalten als Liste speichern</button>
                    </div>
                    <script>
                    function submitSaveCurrentView() {
                        const name = document.getElementById('save_view_name_input').value.trim();
                        if (!name) { alert('Bitte gib einen Namen für die Liste ein.'); return; }
                        const form = document.getElementById('filterForm');
                        form.method = 'post';
                        form.action = 'admin.php?module=lists&event_id=<?= $eventId ?>';
                        const addHidden = (k, v) => {
                            const i = document.createElement('input');
                            i.type = 'hidden'; i.name = k; i.value = v;
                            form.appendChild(i);
                        };
                        addHidden('csrf_token', <?= json_encode(Auth::csrfToken()) ?>);
                        addHidden('sub_action', 'save_view');
                        addHidden('view_name', name);
                        if (document.getElementById('save_view_global_chk').checked) {
                            addHidden('save_as_global', '1');
                        }
                        form.submit();
                    }
                    </script>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ergebnistabelle -->
        <div class="card">
            <div class="card-header">
                <h2>📄 Ergebnis: <?= e($activeViewName) ?> (<?= count($filteredRegs) ?> Teilnehmer)</h2>
                <div>
                    <a href="<?= e($printUrl) ?>" target="_blank" class="btn btn-secondary btn-sm">🖨️ Druckansicht</a>
                    <a href="<?= e($csvUrl) ?>" class="btn btn-secondary btn-sm">📥 CSV-Export</a>
                </div>
            </div>
            <div class="card-body table-responsive">
                <?php if (empty($filteredRegs)): ?>
                    <p style="margin:0;color:#64748b;">Keine Teilnehmer entsprechen den gewählten Filterkriterien.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>Nachname, Vorname</th>
                                <?php foreach ($selectedCols as $cKey): ?>
                                    <th><?= e($availableColumns[$cKey]) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($filteredRegs as $r): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td>
                                        <a href="admin.php?module=registrations&event_id=<?= $eventId ?>&action=edit&id=<?= (int)$r['id'] ?>" style="font-weight:600;color:var(--adm-accent);">
                                            <?= e($r['last_name'] . ', ' . $r['first_name']) ?>
                                        </a>
                                    </td>
                                    <?php foreach ($selectedCols as $cKey): ?>
                                        <td><?= nl2br(e($getCellValue($r, $cKey))) ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
        AdminLayout::render('Listen – ' . $event['title'], 'lists', (string)ob_get_clean(), $eventId);
    }
}
