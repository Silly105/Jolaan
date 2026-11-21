<?php
declare(strict_types=1);

namespace App\Modules\FormBuilder;

use App\Core\Auth;
use App\Core\Database;
use App\Modules\Admin\AdminLayout;
use App\Modules\Events\EventRepository;

class FormBuilderController
{
    public static function handleFormBuilder(): void
    {
        if (!Auth::isAdmin()) {
            header('Location: admin.php');
            exit;
        }

        $rawEventId = (int)($_REQUEST['event_id'] ?? 0);
        $eventId    = $rawEventId > 0 ? $rawEventId : null;
        $event      = $eventId !== null ? EventRepository::find($eventId) : null;
        $tFields    = Database::table('form_fields');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::requireCsrf();
            $subAction = (string)($_POST['sub_action'] ?? '');

            if ($subAction === 'save_field') {
                $fieldId    = (int)($_POST['field_id'] ?? 0);
                $labelDe    = trim((string)($_POST['label_de'] ?? 'Feld'));
                $labelEn    = trim((string)($_POST['label_en'] ?? ''));
                $fieldType  = (string)($_POST['field_type'] ?? 'text');
                $helpDe     = trim((string)($_POST['help_de'] ?? ''));
                $helpEn     = trim((string)($_POST['help_en'] ?? ''));
                $sortOrder  = (int)($_POST['sort_order'] ?? 10);
                $sysMap     = (string)($_POST['system_mapping'] ?? 'none');
                $isSens     = isset($_POST['is_sensitive']) ? 1 : 0;
                $isEnc      = ($isSens === 1 || isset($_POST['is_encrypted'])) ? 1 : 0;
                $isOrgaOnly = isset($_POST['is_orga_only']) ? 1 : 0;

                // Kollisionskontrolle: Ein "Nur für Orga sichtbar"-Feld oder eine Überschrift darf niemals Pflichtfeld im öffentlichen Formular sein
                $isRequired = ($isOrgaOnly === 1 || $fieldType === 'heading') ? 0 : (isset($_POST['is_required']) ? 1 : 0);

                // Technischen Schlüssel erzeugen falls neu
                $fieldKey = trim((string)($_POST['field_key'] ?? ''));
                if ($fieldKey === '') {
                    $fieldKey = strtolower((string)preg_replace('/[^a-zA-Z0-9_]/', '_', strtr($labelDe, ['ä'=>'ae','ö'=>'oe','ü'=>'ue','ß'=>'ss','Ä'=>'Ae','Ö'=>'Oe','Ü'=>'Ue'])));
                    $fieldKey = trim((string)preg_replace('/_+/', '_', $fieldKey), '_') ?: ('field_' . time());
                }

                // Optionen (für select) oder Dokument-Upload (für document) verarbeiten
                $optionsJson = null;
                if ($fieldType === 'select') {
                    $optsDe = array_values(array_filter(array_map('trim', explode("\n", (string)($_POST['options_de'] ?? ''))), fn($v) => $v !== ''));
                    $optsEn = array_values(array_filter(array_map('trim', explode("\n", (string)($_POST['options_en'] ?? ''))), fn($v) => $v !== ''));
                    $optionsJson = json_encode(['de' => $optsDe, 'en' => $optsEn], JSON_UNESCAPED_UNICODE);
                } elseif ($fieldType === 'document') {
                    $existingFile = trim((string)($_POST['existing_doc_path'] ?? ''));
                    if (!empty($_FILES['doc_file']['name'])) {
                        if (($_FILES['doc_file']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                            $_SESSION['flash_error'] = 'Dokument-Upload fehlgeschlagen (Fehlercode ' . (int)$_FILES['doc_file']['error'] . ').';
                            header('Location: admin.php?module=formbuilder&event_id=' . ($eventId ?? 0) . ($fieldId > 0 ? '&edit_id=' . $fieldId : ''));
                            exit;
                        }
                        $ext = strtolower(pathinfo((string)$_FILES['doc_file']['name'], PATHINFO_EXTENSION));
                        if (!in_array($ext, ['pdf', 'txt', 'doc', 'docx', 'odt', 'png', 'jpg'], true)) {
                            $_SESSION['flash_error'] = 'Ungültiges Dokumentformat. Erlaubt sind: PDF, TXT, DOC, DOCX, ODT, PNG, JPG.';
                            header('Location: admin.php?module=formbuilder&event_id=' . ($eventId ?? 0) . ($fieldId > 0 ? '&edit_id=' . $fieldId : ''));
                            exit;
                        }
                        $fileName = 'doc_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        $destDir  = dirname(__DIR__, 3) . '/storage/uploads';
                        if (move_uploaded_file($_FILES['doc_file']['tmp_name'], $destDir . '/' . $fileName)) {
                            $existingFile = 'storage/uploads/' . $fileName;
                        }
                    }
                    $optionsJson = json_encode([
                        'file_path'     => $existingFile,
                        'link_label_de' => trim((string)($_POST['doc_label_de'] ?? 'Dokument öffnen')),
                        'link_label_en' => trim((string)($_POST['doc_label_en'] ?? 'Open document')),
                    ], JSON_UNESCAPED_UNICODE);
                }

                $params = [
                    'fkey'  => $fieldKey,
                    'ftype' => $fieldType,
                    'lde'   => $labelDe,
                    'len'   => $labelEn,
                    'hde'   => $helpDe,
                    'hen'   => $helpEn,
                    'opt'   => $optionsJson,
                    'req'   => $isRequired,
                    'enc'   => $isEnc,
                    'sens'  => $isSens,
                    'orga'  => $isOrgaOnly,
                    'map'   => $sysMap,
                    'sort'  => $sortOrder,
                ];

                if ($fieldId > 0) {
                    $params['id'] = $fieldId;
                    if ($eventId === null) {
                        Database::query(
                            "UPDATE {$tFields} SET
                             field_key = :fkey, field_type = :ftype, label_de = :lde, label_en = :len,
                             help_de = :hde, help_en = :hen, options_json = :opt,
                             is_required = :req, is_encrypted = :enc, is_sensitive = :sens, is_orga_only = :orga,
                             system_mapping = :map, sort_order = :sort
                             WHERE id = :id AND event_id IS NULL",
                            $params
                        );
                    } else {
                        $params['eid'] = $eventId;
                        Database::query(
                            "UPDATE {$tFields} SET
                             field_key = :fkey, field_type = :ftype, label_de = :lde, label_en = :len,
                             help_de = :hde, help_en = :hen, options_json = :opt,
                             is_required = :req, is_encrypted = :enc, is_sensitive = :sens, is_orga_only = :orga,
                             system_mapping = :map, sort_order = :sort
                             WHERE id = :id AND event_id = :eid",
                            $params
                        );
                    }
                    $_SESSION['flash_success'] = "Feld „{$labelDe}“ wurde aktualisiert.";
                } else {
                    $params['eid'] = $eventId;
                    Database::query(
                        "INSERT INTO {$tFields}
                         (event_id, field_key, field_type, label_de, label_en, help_de, help_en, options_json, is_required, is_encrypted, is_sensitive, is_orga_only, system_mapping, sort_order)
                         VALUES (:eid, :fkey, :ftype, :lde, :len, :hde, :hen, :opt, :req, :enc, :sens, :orga, :map, :sort)",
                        $params
                    );
                    $_SESSION['flash_success'] = "Neues Feld „{$labelDe}“ wurde hinzugefügt.";
                }

                if ($isOrgaOnly === 1 && isset($_POST['is_required'])) {
                    $_SESSION['flash_success'] .= ' (Hinweis: Da das Feld „Nur für Orga sichtbar“ ist, wurde „Pflichtfeld“ automatisch deaktiviert.)';
                }
            } elseif ($subAction === 'quick_update') {
                // Schnellspeichern der Tabelle (Sortierung & Flags)
                foreach ($_POST['q'] ?? [] as $fid => $qData) {
                    $fid = (int)$fid;
                    $isOrgaOnly = isset($qData['is_orga_only']) ? 1 : 0;
                    $isRequired = $isOrgaOnly === 1 ? 0 : (isset($qData['is_required']) ? 1 : 0);
                    $isSens     = isset($qData['is_sensitive']) ? 1 : 0;
                    $isEnc      = ($isSens === 1 || isset($qData['is_encrypted'])) ? 1 : 0;
                    $sort       = (int)($qData['sort_order'] ?? 10);

                    Database::query(
                        "UPDATE {$tFields} SET sort_order = :sort, is_required = :req, is_encrypted = :enc, is_sensitive = :sens, is_orga_only = :orga
                         WHERE id = :id",
                        ['sort' => $sort, 'req' => $isRequired, 'enc' => $isEnc, 'sens' => $isSens, 'orga' => $isOrgaOnly, 'id' => $fid]
                    );
                }
                $_SESSION['flash_success'] = 'Reihenfolge und Feld-Optionen wurden gespeichert.';
            } elseif ($subAction === 'delete_field') {
                $delId = (int)($_POST['delete_id'] ?? 0);
                Database::query("DELETE FROM {$tFields} WHERE id = :id", ['id' => $delId]);
                Database::query("DELETE FROM " . Database::table('registration_values') . " WHERE field_id = :id", ['id' => $delId]);
                $_SESSION['flash_success'] = 'Feld wurde gelöscht.';
            }

            header('Location: admin.php?module=formbuilder&event_id=' . ($eventId ?? 0));
            exit;
        }

        $fields = EventRepository::getFields($eventId, true);
        $editFieldId = (int)($_GET['edit_id'] ?? 0);
        $editField = null;
        foreach ($fields as $f) {
            if ((int)$f['id'] === $editFieldId) {
                $editField = $f;
                break;
            }
        }

        $fieldTypes = [
            'text'     => 'Einzeiliges Textfeld (Text)',
            'textarea' => 'Mehrzeiliges Textfeld (Textarea)',
            'email'    => 'E-Mail-Adresse',
            'number'   => 'Zahl',
            'date'     => 'Datum (z. B. Geburtsdatum)',
            'select'   => 'Auswahl-Dropdown (Select)',
            'checkbox' => 'Ja/Nein-Checkbox',
            'document' => 'Dokument-Upload + Akzeptieren-Checkbox (AGB, Datenschutz, Hausordnung etc.)',
            'heading'  => 'Abschnitts-Überschrift / Trenner',
        ];

        ob_start();
        if (!$event) {
            echo '<div class="alert alert-success" style="background:#eff6ff;border-color:#93c5fd;color:#1e3a8a;">📐 Du bearbeitest hier die <strong>globale Standard-Feldvorlage</strong>. Diese Felder werden automatisch vorgeschlagen, wenn eine neue Veranstaltung ohne Kopier-Vorlage erstellt wird.</div>';
        }

        $ef = $editField ?? [
            'id'             => 0,
            'field_key'      => '',
            'field_type'     => 'text',
            'label_de'       => '',
            'label_en'       => '',
            'help_de'        => '',
            'help_en'        => '',
            'options_json'   => '',
            'is_required'    => 0,
            'is_encrypted'   => 0,
            'is_sensitive'   => 0,
            'is_orga_only'   => 0,
            'system_mapping' => 'none',
            'sort_order'     => (count($fields) + 1) * 10,
        ];
        $optsDecoded = json_decode((string)$ef['options_json'], true) ?: [];
        $optsDeText  = isset($optsDecoded['de']) && is_array($optsDecoded['de']) ? implode("\n", $optsDecoded['de']) : '';
        $optsEnText  = isset($optsDecoded['en']) && is_array($optsDecoded['en']) ? implode("\n", $optsDecoded['en']) : '';
        $docPath     = (string)($optsDecoded['file_path'] ?? '');
        $docLblDe    = (string)($optsDecoded['link_label_de'] ?? 'Dokument öffnen / herunterladen');
        $docLblEn    = (string)($optsDecoded['link_label_en'] ?? 'Open / download document');
        ?>

        <div class="card">
            <div class="card-header">
                <h2><?= $ef['id'] > 0 ? '✏️ Feld bearbeiten – ' . e($ef['label_de']) : '➕ Neues Formularfeld hinzufügen' ?></h2>
                <?php if ($ef['id'] > 0): ?>
                    <a href="admin.php?module=formbuilder&event_id=<?= $eventId ?? 0 ?>" class="btn btn-secondary btn-sm">Abbrechen / Neues Feld</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="post" action="admin.php?module=formbuilder&event_id=<?= $eventId ?? 0 ?>" enctype="multipart/form-data">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="sub_action" value="save_field">
                    <input type="hidden" name="field_id" value="<?= (int)$ef['id'] ?>">

                    <div class="grid-3">
                        <div class="form-group">
                            <label for="field_type">Feldtyp*</label>
                            <select id="field_type" name="field_type" onchange="toggleFieldTypeOptions()">
                                <?php foreach ($fieldTypes as $tKey => $tName): ?>
                                    <option value="<?= e($tKey) ?>" <?= $ef['field_type'] === $tKey ? 'selected' : '' ?>><?= e($tName) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="label_de">Feldbezeichnung (Deutsch)*</label>
                            <input type="text" id="label_de" name="label_de" value="<?= e($ef['label_de']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="label_en">Feldbezeichnung (Englisch)</label>
                            <input type="text" id="label_en" name="label_en" value="<?= e($ef['label_en']) ?>">
                        </div>
                    </div>

                    <div class="grid-3">
                        <div class="form-group">
                            <label for="sort_order">Sortier-Reihenfolge (Zahl)</label>
                            <input type="number" id="sort_order" name="sort_order" value="<?= (int)$ef['sort_order'] ?>">
                        </div>
                        <div class="form-group">
                            <label for="system_mapping">System-Zuordnung (für Suche, Mail & U18-Alter)</label>
                            <select id="system_mapping" name="system_mapping">
                                <option value="none" <?= $ef['system_mapping'] === 'none' ? 'selected' : '' ?>>— Normales freies Feld —</option>
                                <option value="firstname" <?= $ef['system_mapping'] === 'firstname' ? 'selected' : '' ?>>Vorname des Teilnehmers</option>
                                <option value="lastname" <?= $ef['system_mapping'] === 'lastname' ? 'selected' : '' ?>>Nachname des Teilnehmers</option>
                                <option value="email" <?= $ef['system_mapping'] === 'email' ? 'selected' : '' ?>>E-Mail-Adresse (für E-Mail-Bestätigung & DSGVO)</option>
                                <option value="birthdate" <?= $ef['system_mapping'] === 'birthdate' ? 'selected' : '' ?>>Geburtsdatum (für U18-Minderjährigen-Liste)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="field_key">Interner Schlüssel (automatisch, falls leer)</label>
                            <input type="text" id="field_key" name="field_key" value="<?= e($ef['field_key']) ?>" placeholder="z. B. allergien">
                        </div>
                    </div>

                    <!-- Zusatzbox für Dropdown-Auswahloptionen -->
                    <div id="box_select_options" class="grid-2" style="display:none;background:#f8fafc;padding:1rem;border-radius:6px;border:1px solid #cbd5e1;margin-bottom:1rem;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Dropdown-Auswahlmöglichkeiten (Deutsch – eine Option pro Zeile)</label>
                            <textarea name="options_de" rows="4" placeholder="Allesesser&#10;Vegetarisch&#10;Vegan"><?= e($optsDeText) ?></textarea>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Dropdown-Auswahlmöglichkeiten (Englisch – eine Option pro Zeile)</label>
                            <textarea name="options_en" rows="4" placeholder="Omnivore&#10;Vegetarian&#10;Vegan"><?= e($optsEnText) ?></textarea>
                        </div>
                    </div>

                    <!-- Zusatzbox für Dokument-Upload & Akzeptanz-Checkbox -->
                    <div id="box_document_options" style="display:none;background:#f8fafc;padding:1rem;border-radius:6px;border:1px solid #cbd5e1;margin-bottom:1rem;">
                        <div class="grid-3">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Dokument hochladen (PDF, Bild, Text – z. B. AGB / Hausordnung)</label>
                                <input type="file" name="doc_file" accept=".pdf,.txt,.doc,.docx,.odt,.png,.jpg">
                                <input type="hidden" name="existing_doc_path" value="<?= e($docPath) ?>">
                                <?php if ($docPath !== ''): ?>
                                    <div class="help-text">Aktuelle Datei: <a href="<?= e($docPath) ?>" target="_blank"><?= e(basename($docPath)) ?></a></div>
                                <?php endif; ?>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Download-Button-Text (DE)</label>
                                <input type="text" name="doc_label_de" value="<?= e($docLblDe) ?>">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Download-Button-Text (EN)</label>
                                <input type="text" name="doc_label_en" value="<?= e($docLblEn) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="help_de">Hilfetext / Beschreibungstext (Deutsch)</label>
                            <input type="text" id="help_de" name="help_de" value="<?= e($ef['help_de']) ?>" placeholder="Bei Dokument-Feldern: Text neben der Akzeptieren-Checkbox">
                        </div>
                        <div class="form-group">
                            <label for="help_en">Hilfetext / Beschreibungstext (Englisch)</label>
                            <input type="text" id="help_en" name="help_en" value="<?= e($ef['help_en']) ?>">
                        </div>
                    </div>

                    <div style="display:flex;flex-wrap:wrap;gap:1.5rem;background:#f8fafc;padding:0.9rem 1.1rem;border-radius:6px;border:1px solid #e2e8f0;margin-bottom:1rem;">
                        <label style="margin:0;cursor:pointer;">
                            <input type="checkbox" id="chk_required" name="is_required" value="1" <?= (int)$ef['is_required'] === 1 ? 'checked' : '' ?>>
                            ⭐ <strong>Pflichtfeld</strong>
                        </label>
                        <label style="margin:0;cursor:pointer;">
                            <input type="checkbox" id="chk_encrypted" name="is_encrypted" value="1" <?= ((int)($ef['is_encrypted'] ?? 0) === 1 || (int)$ef['is_sensitive'] === 1) ? 'checked' : '' ?>>
                            🔒 <strong>Verschlüsselt in DB speichern (AES-256)</strong> (z. B. E-Mail, Telefon; bleibt bei 1-Klick-Bereinigung erhalten)
                        </label>
                        <label style="margin:0;cursor:pointer;">
                            <input type="checkbox" id="chk_sensitive" name="is_sensitive" value="1" <?= (int)$ef['is_sensitive'] === 1 ? 'checked' : '' ?> onchange="if(this.checked){document.getElementById('chk_encrypted').checked=true;}">
                            🧹 <strong>Sensible Daten (Nach Con per 1-Klick löschen)</strong> (z. B. Allergien, Krankheiten; aktiviert automatisch Verschlüsselung)
                        </label>
                        <label style="margin:0;cursor:pointer;">
                            <input type="checkbox" id="chk_orga_only" name="is_orga_only" value="1" <?= (int)$ef['is_orga_only'] === 1 ? 'checked' : '' ?> onchange="enforceOrgaRequiredCollision()">
                            👁️ <strong>Nur für Orga sichtbar</strong> (deaktiviert automatisch „Pflichtfeld“)
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        💾 <?= $ef['id'] > 0 ? 'Feld-Änderungen speichern' : 'Feld zum Formular hinzufügen' ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Übersicht aller vorhandenen Felder -->
        <div class="card">
            <div class="card-header">
                <h2>📋 Vorhandene Felder (<?= count($fields) ?>)</h2>
            </div>
            <div class="card-body">
                <form method="post" action="admin.php?module=formbuilder&event_id=<?= $eventId ?? 0 ?>">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="sub_action" value="quick_update">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width:105px;">Sort.</th>
                                    <th>Bezeichnung (DE / EN)</th>
                                    <th>Feldtyp</th>
                                    <th style="text-align:center;">Pflichtfeld</th>
                                    <th style="text-align:center;" title="Wird mit AES-256-GCM verschlüsselt gespeichert">🔒 Verschlüsselt</th>
                                    <th style="text-align:center;" title="Wird bei der 1-Klick-Bereinigung nach der Con gelöscht">🧹 Sensibel (Löschen nach Con)</th>
                                    <th style="text-align:center;">Nur für Orga</th>
                                    <th>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fields as $f):
                                    $fid = (int)$f['id'];
                                    $isHeading = $f['field_type'] === 'heading';
                                    $isSens = (int)$f['is_sensitive'] === 1;
                                    $isEnc  = $isSens || ((int)($f['is_encrypted'] ?? 0) === 1);
                                ?>
                                    <tr style="<?= $isHeading ? 'background:#eef7dc;font-weight:700;' : '' ?>">
                                        <td>
                                            <input type="number" class="sort-input" name="q[<?= $fid ?>][sort_order]" value="<?= (int)$f['sort_order'] ?>">
                                        </td>
                                        <td>
                                            <div><?= e($f['label_de']) ?></div>
                                            <?php if ($f['label_en'] !== ''): ?>
                                                <div style="font-size:0.78rem;color:#64748b;">EN: <?= e($f['label_en']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-gray"><?= e($f['field_type']) ?></span>
                                            <?php if ($f['system_mapping'] !== 'none'): ?>
                                                <span class="badge badge-blue"><?= e($f['system_mapping']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:center;">
                                            <?php if (!$isHeading): ?>
                                                <input type="checkbox" class="row-req" data-fid="<?= $fid ?>" name="q[<?= $fid ?>][is_required]" value="1" <?= (int)$f['is_required'] === 1 ? 'checked' : '' ?> <?= (int)$f['is_orga_only'] === 1 ? 'disabled' : '' ?>>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:center;">
                                            <?php if (!$isHeading): ?>
                                                <input type="checkbox" class="row-enc" data-fid="<?= $fid ?>" name="q[<?= $fid ?>][is_encrypted]" value="1" <?= $isEnc ? 'checked' : '' ?>>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:center;">
                                            <?php if (!$isHeading): ?>
                                                <input type="checkbox" name="q[<?= $fid ?>][is_sensitive]" value="1" <?= $isSens ? 'checked' : '' ?> onchange="if(this.checked){const e=document.querySelector('.row-enc[data-fid=\'<?= $fid ?>\']');if(e)e.checked=true;}">
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:center;">
                                            <?php if (!$isHeading): ?>
                                                <input type="checkbox" class="row-orga" data-fid="<?= $fid ?>" name="q[<?= $fid ?>][is_orga_only]" value="1" <?= (int)$f['is_orga_only'] === 1 ? 'checked' : '' ?> onchange="toggleRowCollision(<?= $fid ?>, this.checked)">
                                            <?php endif; ?>
                                        </td>
                                        <td style="white-space:nowrap;">
                                            <a href="admin.php?module=formbuilder&event_id=<?= $eventId ?? 0 ?>&edit_id=<?= $fid ?>" class="btn btn-secondary btn-sm">✏️ Details</a>
                                            <button type="submit" name="sub_action" value="delete_field" onclick="document.getElementById('del_fid').value='<?= $fid ?>';return confirm('Feld wirklich löschen?');" class="btn btn-danger btn-sm">✕</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <input type="hidden" id="del_fid" name="delete_id" value="0">
                    <div style="margin-top:1rem;">
                        <button type="submit" class="btn btn-primary">💾 Sortierung & Haken speichern</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function toggleFieldTypeOptions() {
            const type = document.getElementById('field_type').value;
            document.getElementById('box_select_options').style.display = (type === 'select') ? 'grid' : 'none';
            document.getElementById('box_document_options').style.display = (type === 'document') ? 'block' : 'none';
        }
        function enforceOrgaRequiredCollision() {
            const orgaChk = document.getElementById('chk_orga_only');
            const reqChk  = document.getElementById('chk_required');
            if (orgaChk.checked) {
                reqChk.checked = false;
                reqChk.disabled = true;
            } else {
                reqChk.disabled = false;
            }
        }
        function toggleRowCollision(fid, isOrga) {
            const req = document.querySelector('.row-req[data-fid="' + fid + '"]');
            if (!req) return;
            if (isOrga) {
                req.checked = false;
                req.disabled = true;
            } else {
                req.disabled = false;
            }
        }
        toggleFieldTypeOptions();
        enforceOrgaRequiredCollision();
        </script>
        <?php
        $title = $event ? ('Abfragefelder – ' . $event['title']) : 'Feldvorlagen für neue Veranstaltungen';
        AdminLayout::render($title, 'formbuilder', (string)ob_get_clean(), $eventId);
    }

    /**
     * Zentrale Übersetzungstabelle (DE <-> EN) mit Pflichtfeld-Kontrolle und TSV/Tabellen-Schnellimport
     */
    public static function handleTranslations(): void
    {
        if (!Auth::isAdmin()) {
            header('Location: admin.php');
            exit;
        }

        $eventId = (int)($_REQUEST['event_id'] ?? 0);
        $event   = $eventId > 0 ? EventRepository::find($eventId) : null;
        if (!$event) {
            header('Location: admin.php');
            exit;
        }

        $tEvents = Database::table('events');
        $tGroups = Database::table('event_groups');
        $tFields = Database::table('form_fields');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::requireCsrf();
            $subAction = (string)($_POST['sub_action'] ?? 'save_table');

            if ($subAction === 'save_table') {
                $headerEn = trim((string)($_POST['event_header_text_en'] ?? ''));
                Database::query("UPDATE {$tEvents} SET header_text_en = :hen WHERE id = :eid", ['hen' => $headerEn, 'eid' => $eventId]);

                foreach ($_POST['groups_en'] ?? [] as $gid => $gNameEn) {
                    Database::query(
                        "UPDATE {$tGroups} SET name_en = :nen WHERE id = :id AND event_id = :eid",
                        ['nen' => trim((string)$gNameEn), 'id' => (int)$gid, 'eid' => $eventId]
                    );
                }

                foreach ($_POST['fields'] ?? [] as $fid => $fData) {
                    $fid = (int)$fid;
                    $labelEn = trim((string)($fData['label_en'] ?? ''));
                    $helpEn  = trim((string)($fData['help_en'] ?? ''));

                    $existing = Database::fetchOne("SELECT field_type, options_json FROM {$tFields} WHERE id = :id AND event_id = :eid", ['id' => $fid, 'eid' => $eventId]);
                    $optJson = $existing['options_json'] ?? null;
                    if ($existing && $existing['field_type'] === 'select' && isset($fData['options_en'])) {
                        $decoded = json_decode((string)$optJson, true) ?: [];
                        $decoded['en'] = array_values(array_filter(array_map('trim', explode('|', (string)$fData['options_en'])), fn($v) => $v !== ''));
                        $optJson = json_encode($decoded, JSON_UNESCAPED_UNICODE);
                    } elseif ($existing && $existing['field_type'] === 'document' && isset($fData['doc_label_en'])) {
                        $decoded = json_decode((string)$optJson, true) ?: [];
                        $decoded['link_label_en'] = trim((string)$fData['doc_label_en']);
                        $optJson = json_encode($decoded, JSON_UNESCAPED_UNICODE);
                    }

                    Database::query(
                        "UPDATE {$tFields} SET label_en = :len, help_en = :hen, options_json = :opt WHERE id = :id AND event_id = :eid",
                        ['len' => $labelEn, 'hen' => $helpEn, 'opt' => $optJson, 'id' => $fid, 'eid' => $eventId]
                    );
                }

                $_SESSION['flash_success'] = 'Übersetzungstabelle wurde gespeichert.';
            } elseif ($subAction === 'import_tsv') {
                $tsv = trim((string)($_POST['tsv_input'] ?? ''));
                $lines = preg_split('/\r\n|\r|\n/', $tsv) ?: [];
                $imported = 0;

                foreach ($lines as $line) {
                    $parts = explode("\t", $line);
                    if (count($parts) < 3) {
                        continue;
                    }
                    $rowKey = trim($parts[0]);
                    $enText = trim($parts[2]);
                    if ($enText === '') {
                        continue;
                    }

                    if ($rowKey === 'event.header_text') {
                        Database::query("UPDATE {$tEvents} SET header_text_en = :hen WHERE id = :eid", ['hen' => $enText, 'eid' => $eventId]);
                        $imported++;
                    } elseif (preg_match('/^group\.(\d+)\.name$/', $rowKey, $m)) {
                        Database::query("UPDATE {$tGroups} SET name_en = :nen WHERE id = :id AND event_id = :eid", ['nen' => $enText, 'id' => (int)$m[1], 'eid' => $eventId]);
                        $imported++;
                    } elseif (preg_match('/^field\.(\d+)\.(label|help)$/', $rowKey, $m)) {
                        $col = $m[2] === 'label' ? 'label_en' : 'help_en';
                        Database::query("UPDATE {$tFields} SET {$col} = :val WHERE id = :id AND event_id = :eid", ['val' => $enText, 'id' => (int)$m[1], 'eid' => $eventId]);
                        $imported++;
                    }
                }
                $_SESSION['flash_success'] = "{$imported} englische Übersetzungen wurden aus der TSV-Tabelle importiert.";
            }

            header("Location: admin.php?module=translations&event_id={$eventId}");
            exit;
        }

        $event  = EventRepository::find($eventId);
        // Nur öffentlich sichtbare Teilnehmergruppen (die in der Anmeldung oder auf der Startseite erscheinen) müssen übersetzt werden
        $allGroups = EventRepository::getGroupsWithSeats($eventId);
        $groups = array_values(array_filter(
            $allGroups,
            fn($g) => (int)($g['show_in_registration'] ?? 1) === 1 || (int)($g['show_on_index'] ?? 1) === 1
        ));
        $fields = EventRepository::getFields($eventId, false);
        $isBilingual = (int)$event['is_bilingual'] === 1;

        $missingCount = 0;
        $tsvRows = [];

        if (trim((string)$event['header_text_de']) !== '') {
            if (trim((string)$event['header_text_en']) === '') {
                $missingCount++;
            }
            $tsvRows[] = "event.header_text\t" . str_replace(["\r", "\n", "\t"], ' ', (string)$event['header_text_de']) . "\t" . str_replace(["\r", "\n", "\t"], ' ', (string)$event['header_text_en']);
        }

        foreach ($groups as $g) {
            if (trim((string)$g['name_en']) === '') {
                $missingCount++;
            }
            $tsvRows[] = "group.{$g['id']}.name\t{$g['name_de']}\t{$g['name_en']}";
        }

        foreach ($fields as $f) {
            if (trim((string)$f['label_en']) === '') {
                $missingCount++;
            }
            $tsvRows[] = "field.{$f['id']}.label\t{$f['label_de']}\t{$f['label_en']}";
            if (trim((string)$f['help_de']) !== '') {
                if (trim((string)$f['help_en']) === '') {
                    $missingCount++;
                }
                $tsvRows[] = "field.{$f['id']}.help\t" . str_replace(["\r", "\n", "\t"], ' ', (string)$f['help_de']) . "\t" . str_replace(["\r", "\n", "\t"], ' ', (string)$f['help_en']);
            }
        }

        ob_start();
        ?>

        <?php if ($isBilingual && $missingCount > 0): ?>
            <div class="alert alert-danger">
                ⚠️ Für diese Veranstaltung ist <strong>Zweisprachigkeit (DE/EN)</strong> aktiviert. Es fehlen aktuell noch <strong><?= $missingCount ?></strong> englische Übersetzungen (rot markiert). Bitte fülle alle Pflichtfelder in der rechten Spalte aus.
            </div>
        <?php elseif ($isBilingual): ?>
            <div class="alert alert-success">
                ✅ Alle öffentlichen Felder und Gruppen dieser Veranstaltung sind vollständig ins Englische übersetzt!
            </div>
        <?php else: ?>
            <div class="alert" style="background:#eff6ff;border:1px solid #93c5fd;color:#1e3a8a;">
                ℹ️ Zweisprachigkeit ist für diese Veranstaltung derzeit deaktiviert (kann unter „Con-Einstellungen“ aktiviert werden). Du kannst die Übersetzungen hier trotzdem jederzeit vorbereiten.
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>🌐 Zentrale Übersetzungstabelle (Deutsch &leftrightarrow; Englisch)</h2>
            </div>
            <div class="card-body">
                <form method="post" action="admin.php?module=translations&event_id=<?= $eventId ?>">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="sub_action" value="save_table">

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width:18%;">Bereich / Element</th>
                                    <th style="width:41%;">Deutsch (Original)</th>
                                    <th style="width:41%;">Englisch (Übersetzung <?= $isBilingual ? '*' : '' ?>)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (trim((string)$event['header_text_de']) !== ''):
                                    $miss = $isBilingual && trim((string)$event['header_text_en']) === '';
                                ?>
                                    <tr>
                                        <td><span class="badge badge-blue">Header-Textblock</span></td>
                                        <td><?= nl2br(e($event['header_text_de'])) ?></td>
                                        <td>
                                            <textarea name="event_header_text_en" rows="2" style="<?= $miss ? 'border-color:#dc2626;background:#fef2f2;' : '' ?>" <?= $isBilingual ? 'required' : '' ?>><?= e($event['header_text_en']) ?></textarea>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($groups as $g):
                                    $gid = (int)$g['id'];
                                    $miss = $isBilingual && trim((string)$g['name_en']) === '';
                                ?>
                                    <tr>
                                        <td><span class="badge badge-amber">Teilnehmergruppe</span></td>
                                        <td><strong><?= e($g['name_de']) ?></strong></td>
                                        <td>
                                            <input type="text" name="groups_en[<?= $gid ?>]" value="<?= e($g['name_en']) ?>" style="<?= $miss ? 'border-color:#dc2626;background:#fef2f2;' : '' ?>" <?= $isBilingual ? 'required' : '' ?>>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php foreach ($fields as $f):
                                    $fid = (int)$f['id'];
                                    $missLbl = $isBilingual && trim((string)$f['label_en']) === '';
                                    $hasHelp = trim((string)$f['help_de']) !== '';
                                    $missHlp = $isBilingual && $hasHelp && trim((string)$f['help_en']) === '';
                                    $opts = json_decode((string)$f['options_json'], true) ?: [];
                                ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-gray">Feld (<?= e($f['field_type']) ?>)</span>
                                            <div style="font-size:0.75rem;color:#64748b;"><?= e($f['field_key']) ?></div>
                                        </td>
                                        <td>
                                            <div><strong><?= e($f['label_de']) ?></strong></div>
                                            <?php if ($hasHelp): ?>
                                                <div style="font-size:0.82rem;color:#475569;margin-top:0.35rem;"><em>Hilfetext:</em> <?= e($f['help_de']) ?></div>
                                            <?php endif; ?>
                                            <?php if ($f['field_type'] === 'select' && !empty($opts['de'])): ?>
                                                <div style="font-size:0.8rem;color:#255E00;margin-top:0.35rem;"><em>Optionen:</em> <?= e(implode(' | ', $opts['de'])) ?></div>
                                            <?php elseif ($f['field_type'] === 'document'): ?>
                                                <div style="font-size:0.8rem;color:#255E00;margin-top:0.35rem;"><em>Button:</em> <?= e($opts['link_label_de'] ?? '') ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <input type="text" name="fields[<?= $fid ?>][label_en]" value="<?= e($f['label_en']) ?>" placeholder="Englische Bezeichnung" style="<?= $missLbl ? 'border-color:#dc2626;background:#fef2f2;' : '' ?>" <?= $isBilingual ? 'required' : '' ?>>
                                            <?php if ($hasHelp): ?>
                                                <input type="text" name="fields[<?= $fid ?>][help_en]" value="<?= e($f['help_en']) ?>" placeholder="Englischer Hilfetext" style="margin-top:0.35rem;<?= $missHlp ? 'border-color:#dc2626;background:#fef2f2;' : '' ?>" <?= $isBilingual ? 'required' : '' ?>>
                                            <?php endif; ?>
                                            <?php if ($f['field_type'] === 'select' && !empty($opts['de'])): ?>
                                                <input type="text" name="fields[<?= $fid ?>][options_en]" value="<?= e(implode(' | ', $opts['en'] ?? [])) ?>" placeholder="Optionen getrennt mit | (z.B. Omnivore | Vegetarian | Vegan)" style="margin-top:0.35rem;">
                                            <?php elseif ($f['field_type'] === 'document'): ?>
                                                <input type="text" name="fields[<?= $fid ?>][doc_label_en]" value="<?= e($opts['link_label_en'] ?? '') ?>" placeholder="Englischer Download-Button-Text" style="margin-top:0.35rem;">
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top:1.25rem;">
                        <button type="submit" class="btn btn-primary">💾 Alle Übersetzungen speichern</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>⚡ Schnell-Übersetzung (Tabellen Copy & Paste für DeepL / KI)</h2></div>
            <div class="card-body">
                <p style="margin-top:0;font-size:0.88rem;color:#475569;">
                    Du kannst diese tabulatorgetrennte Tabelle (Format: <code>Schlüssel [TAB] Deutsch [TAB] Englisch</code>) kopieren, in einem Rutsch übersetzen lassen und unten wieder einfügen:
                </p>
                <div class="grid-2">
                    <div>
                        <label>Aktuelle Tabelle zum Kopieren</label>
                        <textarea rows="7" readonly onclick="this.select()" style="font-family:monospace;font-size:0.82rem;"><?= e(implode("\n", $tsvRows)) ?></textarea>
                    </div>
                    <div>
                        <form method="post" action="admin.php?module=translations&event_id=<?= $eventId ?>">
                            <?= Auth::csrfField() ?>
                            <input type="hidden" name="sub_action" value="import_tsv">
                            <label>Übersetzte Tabelle hier einfügen & importieren</label>
                            <textarea name="tsv_input" rows="5" placeholder="field.1.label&#9;Vorname&#9;First Name" style="font-family:monospace;font-size:0.82rem;" required></textarea>
                            <button type="submit" class="btn btn-secondary btn-sm" style="margin-top:0.5rem;">📥 TSV-Übersetzungen importieren</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
        AdminLayout::render('Übersetzungstabelle (DE/EN) – ' . $event['title'], 'translations', (string)ob_get_clean(), $eventId);
    }
}
