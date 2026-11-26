<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Config;
use App\Core\Crypto;
use App\Core\Database;
use App\Core\I18n;
use App\Core\Mailer;
use App\Core\Theme;
use App\Core\Updater;
use App\Modules\Events\EventRepository;
use App\Modules\Registration\RegistrationRepository;

if (!Config::isInstalled()) {
    header('Location: install.php');
    exit;
}

Updater::runPendingMigrations();
Auth::initSession();

$eventId = (int)($_REQUEST['event_id'] ?? $_REQUEST['conid'] ?? 0);
$action  = (string)($_REQUEST['action'] ?? '');
$event   = $eventId > 0 ? EventRepository::find($eventId) : null;

if (!$event) {
    I18n::init(true);
    Theme::render('Veranstaltung nicht gefunden', '<div class="alert alert-danger">Die angeforderte Veranstaltung existiert nicht oder wurde entfernt.</div><p><a href="index.php" class="btn btn-secondary">&larr; Zurück zur Startseite</a></p>');
    exit;
}

$allowEnglish    = (int)$event['is_bilingual'] === 1;
$waitlistEnabled = (int)($event['waitlist_enabled'] ?? 1) === 1;
I18n::init($allowEnglish);

$pageTitle = $event['title'] . (!empty($event['subtitle']) ? ' – ' . $event['subtitle'] : '') . ' (' . I18n::t('reg.title') . ')';
$orgaContactEmail = trim((string)$event['admin_email']) ?: (string)Database::getSetting('mail_from_address', '');

// 1. E-Mail-Bestätigungslink verarbeiten (Zeitpunkt der Bestätigung entscheidet über Warteliste!)
if ($action === 'confirm') {
    // Vorab-Prüfungen durch automatische Mail-Scanner (HEAD-Requests) dürfen den Link nicht auslösen
    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'HEAD') {
        http_response_code(200);
        exit;
    }

    $regId = (int)($_GET['id'] ?? 0);
    $token = trim((string)($_GET['token'] ?? ''));
    $tRegs = Database::table('registrations');

    $reg = Database::fetchOne(
        "SELECT * FROM {$tRegs} WHERE id = :id AND event_id = :eid LIMIT 1",
        ['id' => $regId, 'eid' => $eventId]
    );

    if (!$reg || $token === '' || !hash_equals((string)$reg['doi_token'], $token)) {
        Theme::render($pageTitle, '<div class="alert alert-danger"><h3>' . e(I18n::t('reg.doi_invalid_title')) . '</h3><p>' . e(I18n::t('reg.doi_invalid_text')) . '</p></div>', ['event' => $event]);
        exit;
    }

    if ((int)$reg['is_confirmed'] === 1) {
        Theme::render($pageTitle, '<div class="alert alert-info"><h3>' . e(I18n::t('reg.doi_already_title')) . '</h3><p>' . e(I18n::t('reg.doi_already_text')) . '</p></div>', ['event' => $event]);
        exit;
    }

    // Zum jetzigen Zeitpunkt prüfen, ob in der Teilnehmergruppe noch ein regulärer Platz frei ist
    $isSecretUnlock = (int)($reg['is_secret_unlock'] ?? 0) === 1;
    $mustGoToWaitlist = RegistrationRepository::determineWaitlistStatusOnConfirm($eventId, (int)$reg['group_id'], $isSecretUnlock);
    $now = date('Y-m-d H:i:s');

    Database::query(
        "UPDATE {$tRegs} SET is_confirmed = 1, is_waitlist = :wait, confirmed_at = :cat WHERE id = :id",
        ['wait' => $mustGoToWaitlist ? 1 : 0, 'cat' => $now, 'id' => $regId]
    );

    $waitPos = 0;
    if ($mustGoToWaitlist) {
        $waitPos = RegistrationRepository::getWaitlistPosition($eventId, (int)$reg['group_id'], $regId);
        RegistrationRepository::appendHistoryLog($regId, "E-Mail-Bestätigung durch Teilnehmer erfolgt – Gruppe ausgebucht, daher auf Warteliste gesetzt (Startplatz #{$waitPos})");
    } else {
        RegistrationRepository::appendHistoryLog($regId, 'E-Mail-Bestätigung durch Teilnehmer erfolgt (Reguläre Teilnehmerliste' . ($isSecretUnlock ? ' via Geheim-Link' : '') . ')');
    }

    $decryptedEmail = Crypto::decrypt((string)$reg['email']);

    // Benachrichtigung an die Orga senden
    if ($orgaContactEmail !== '' && filter_var($orgaContactEmail, FILTER_VALIDATE_EMAIL)) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir    = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $adminLink = "{$scheme}://{$host}{$dir}/admin.php?module=registrations&event_id={$eventId}&action=edit&id={$regId}";
        $statusHint = $mustGoToWaitlist ? "<p style=\"color:#b45309;font-weight:bold;\">⚠️ Hinweis: Gruppe ausgebucht – Teilnehmer steht auf der Warteliste (Platz #{$waitPos}).</p>" : '';
        $orgaBody = "<p>Neue bestätigte Online-Anmeldung für <b>" . e($event['title']) . "</b>:</p>"
                  . $statusHint
                  . "<ul><li><b>Name:</b> " . e($reg['first_name'] . ' ' . $reg['last_name']) . "</li>"
                  . "<li><b>E-Mail:</b> " . e($decryptedEmail) . "</li></ul>"
                  . "<p><a href=\"" . e($adminLink) . "\">Anmeldung im Orga-Backend ansehen &rarr;</a></p>";
        $subjPrefix = $mustGoToWaitlist ? 'Neue Wartelisten-Anmeldung: ' : 'Neue Anmeldung: ';
        Mailer::send($orgaContactEmail, $subjPrefix . $event['title'] . ' (' . $reg['first_name'] . ' ' . $reg['last_name'] . ')', $orgaBody);
    }

    if ($mustGoToWaitlist) {
        $msg = I18n::t('reg.doi_waitlist_text', $reg['first_name'] . ' ' . $reg['last_name'], $event['title'], $waitPos);
    } else {
        $msg = I18n::t('reg.doi_ok_text', $reg['first_name'] . ' ' . $reg['last_name'], $event['title']);
    }
    Theme::render($pageTitle, '<div class="alert alert-success"><h3>' . e(I18n::t('reg.doi_ok_title')) . '</h3><p>' . e($msg) . '</p></div><p><a href="index.php" class="btn btn-secondary">&larr; ' . e(I18n::t('nav.home')) . '</a></p>', ['event' => $event]);
    exit;
}

// 2. Geheimlink / Entsperrungspasswort prüfen
$unlockReq = trim((string)($_REQUEST['unlock'] ?? $_REQUEST['entsperrungspasswort'] ?? ''));
if ($unlockReq !== '') {
    $_SESSION['unlocked_event_' . $eventId] = $unlockReq;
}
$savedUnlock = (string)($_SESSION['unlocked_event_' . $eventId] ?? '');
$isUnlockedBySecret = ($event['unlock_password'] !== '' && hash_equals((string)$event['unlock_password'], $savedUnlock));

if ($event['status'] === 'locked' && !$isUnlockedBySecret) {
    ob_start();
    ?>
    <div class="alert alert-info">
        <h3 style="margin-top:0;"><?= e(I18n::t('reg.locked_title')) ?></h3>
        <p><?= e(I18n::t('reg.locked_text')) ?></p>
    </div>
    <?php if ($orgaContactEmail !== ''): ?>
        <p><strong><?= e(I18n::t('reg.contact_prefix')) ?></strong> <a href="mailto:<?= e($orgaContactEmail) ?>"><?= e($orgaContactEmail) ?></a></p>
    <?php endif; ?>
    <?php if ($event['unlock_password'] !== ''): ?>
        <form method="get" action="register.php" style="margin-top:1.25rem;max-width:420px;">
            <input type="hidden" name="event_id" value="<?= (int)$eventId ?>">
            <div class="form-group">
                <label class="form-label" for="unlock"><?= e(I18n::t('reg.unlock_prompt')) ?></label>
                <div style="display:flex;gap:0.5rem;">
                    <input type="password" class="form-control" id="unlock" name="unlock" required>
                    <button type="submit" class="btn btn-primary"><?= e(I18n::t('reg.unlock_btn')) ?></button>
                </div>
            </div>
        </form>
    <?php endif; ?>
    <?php
    Theme::render($pageTitle, (string)ob_get_clean(), ['event' => $event]);
    exit;
}

// 3. Teilnehmergruppen (nur solche mit show_in_registration = 1!) & Formularfelder laden
$allGroups = EventRepository::getGroupsWithSeats($eventId);
$groups = array_values(array_filter($allGroups, fn($g) => (int)($g['show_in_registration'] ?? 1) === 1));
$fields = EventRepository::getFields($eventId, false);

$errors = [];
$invalidKeys = [];
$submittedGroup = (int)($_POST['group_id'] ?? 0);
$submittedVals  = $_POST['f'] ?? [];
$registrationSuccess = false;
$isPrefilledFromPrevious = false;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['form_render_ts_' . $eventId] = time();
    if (isset($_GET['prefill']) && $_GET['prefill'] === '1' && !empty($_SESSION['prefill_event_' . $eventId])) {
        $prefillData = $_SESSION['prefill_event_' . $eventId];
        $submittedGroup = (int)($prefillData['group_id'] ?? 0);
        $submittedVals  = is_array($prefillData['values'] ?? null) ? $prefillData['values'] : [];
        $isPrefilledFromPrevious = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireCsrf();

    $honeypot = trim((string)($_POST['website_url_hp'] ?? ''));
    $renderTs = (int)($_SESSION['form_render_ts_' . $eventId] ?? 0);
    if ($honeypot !== '' || ($renderTs > 0 && (time() - $renderTs) < 2)) {
        $errors[] = I18n::t('reg.error_bot');
    }

    // Teilnehmergruppe validieren (wenn voll, nur erlaubt falls Warteliste aktiv oder Geheim-Link)
    $selectedGroupObj = null;
    foreach ($groups as $g) {
        if ((int)$g['id'] === $submittedGroup) {
            $selectedGroupObj = $g;
            break;
        }
    }
    if (!$selectedGroupObj || ($selectedGroupObj['is_full'] && !$waitlistEnabled && !$isUnlockedBySecret)) {
        $errors[] = I18n::t('reg.error_group');
        $invalidKeys['group_id'] = true;
    }

    $coreData = [
        'first_name'       => '',
        'last_name'        => '',
        'email'            => '',
        'birth_date'       => null,
        'language'         => I18n::lang(),
        'is_secret_unlock' => $isUnlockedBySecret ? 1 : 0,
    ];

    foreach ($fields as $field) {
        if ($field['field_type'] === 'heading') {
            continue;
        }
        $key   = $field['field_key'];
        $label = I18n::field($field, 'label');
        $val   = isset($submittedVals[$key]) ? trim((string)$submittedVals[$key]) : '';
        $submittedVals[$key] = $val;

        if ((int)$field['is_required'] === 1) {
            if ($field['field_type'] === 'checkbox' || $field['field_type'] === 'document') {
                if ($val !== '1') {
                    $errors[] = I18n::t('reg.error_required', $label);
                    $invalidKeys[$key] = true;
                }
            } elseif ($val === '') {
                $errors[] = I18n::t('reg.error_required', $label);
                $invalidKeys[$key] = true;
            }
        }

        if ($field['field_type'] === 'email' && $val !== '' && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
            $errors[] = I18n::t('reg.error_email') . " ({$label})";
            $invalidKeys[$key] = true;
        }

        switch ($field['system_mapping']) {
            case 'firstname':
                $coreData['first_name'] = $val;
                break;
            case 'lastname':
                $coreData['last_name'] = $val;
                break;
            case 'email':
                $coreData['email'] = $val;
                break;
            case 'birthdate':
                $coreData['birth_date'] = $val !== '' ? $val : null;
                break;
        }
    }

    if ($coreData['email'] !== '') {
        $tRegs = Database::table('registrations');
        $emailHash = Crypto::blindIndex($coreData['email']);
        $existing = Database::fetchOne(
            "SELECT id FROM {$tRegs}
             WHERE event_id = :eid AND email_hash = :ehash
               AND LOWER(TRIM(first_name)) = LOWER(:fname)
               AND LOWER(TRIM(last_name)) = LOWER(:lname)
             LIMIT 1",
            [
                'eid'   => $eventId,
                'ehash' => $emailHash,
                'fname' => trim($coreData['first_name']),
                'lname' => trim($coreData['last_name']),
            ]
        );
        if ($existing) {
            $errors[] = I18n::t('reg.error_duplicate');
        }
    }

    if (empty($errors)) {
        $doiToken = bin2hex(random_bytes(24));
        $coreData['doi_token']    = $doiToken;
        $coreData['is_confirmed'] = 0;

        $regId = RegistrationRepository::create($eventId, $submittedGroup, $coreData, $fields, $submittedVals);

        // Für "Weitere Person anmelden" alle als keep_on_prefill = 1 markierten Felder in der Session merken
        $prefillValues = [];
        foreach ($fields as $pf) {
            if ((int)($pf['keep_on_prefill'] ?? 0) === 1 && !in_array($pf['field_type'], ['heading', 'document'], true)) {
                $pKey = $pf['field_key'];
                if (isset($submittedVals[$pKey])) {
                    $prefillValues[$pKey] = $submittedVals[$pKey];
                }
            }
        }
        $_SESSION['prefill_event_' . $eventId] = [
            'group_id' => $submittedGroup,
            'values'   => $prefillValues,
        ];

        // E-Mail-Bestätigung an Teilnehmer senden
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir    = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $confirmLink = "{$scheme}://{$host}{$dir}/register.php?event_id={$eventId}&action=confirm&id={$regId}&token={$doiToken}&lang=" . I18n::lang();

        $langSuffix = I18n::isEnglish() ? 'en' : 'de';
        $tplSubj = (string)Database::getSetting("mail_tpl_doi_subject_{$langSuffix}", 'Bitte bestätige deine Anmeldung für {conname}');
        $tplBody = (string)Database::getSetting("mail_tpl_doi_body_{$langSuffix}", '<p>Hallo {vorname}, bitte bestätige deine Anmeldung: <a href="{link}">{link}</a></p>');

        $startF = format_date($event['start_date'], I18n::lang());
        $endF   = format_date($event['end_date'], I18n::lang());
        $vars = [
            'vorname'  => $coreData['first_name'],
            'nachname' => $coreData['last_name'],
            'conname'  => $event['title'],
            'condatum' => ($startF === $endF) ? $startF : "{$startF} – {$endF}",
            'gruppe'   => I18n::field($selectedGroupObj, 'name'),
            'link'     => $confirmLink,
            'betrag'   => '0,00',
        ];

        if ($coreData['email'] !== '') {
            Mailer::send(
                $coreData['email'],
                Mailer::parseTemplate($tplSubj, $vars),
                Mailer::parseTemplate($tplBody, $vars),
                $orgaContactEmail ?: null
            );
            RegistrationRepository::appendHistoryLog($regId, 'E-Mail mit Bestätigungslink an Teilnehmer gesendet');
        }

        $registrationSuccess = true;
    }
}

$storageNoticeDe = (string)Database::getSetting('storage_notice_de', 'Deine Anmeldedaten werden auf Servern in Deutschland / der EU gespeichert. Kontakt- und Gesundheitsdaten (mit 🔒 markiert) werden zusätzlich mit AES-256-GCM verschlüsselt in der Datenbank abgelegt.');
$storageNoticeEn = (string)Database::getSetting('storage_notice_en', 'Your registration data is stored on servers in Germany / the EU. Contact and health data (marked with 🔒) are additionally stored encrypted using AES-256-GCM.');
$storageNotice   = (I18n::isEnglish() && trim($storageNoticeEn) !== '') ? trim($storageNoticeEn) : trim($storageNoticeDe);

ob_start();
?>

<?php if ($registrationSuccess): ?>
    <div class="alert alert-success">
        <h3 style="margin-top:0;"><?= e(I18n::t('reg.success_title')) ?></h3>
        <p><?= e(I18n::t('reg.success_text')) ?></p>
    </div>
    <?php if ($orgaContactEmail !== ''): ?>
        <p><strong><?= e(I18n::t('reg.contact_prefix')) ?></strong> <a href="mailto:<?= e($orgaContactEmail) ?>"><?= e($orgaContactEmail) ?></a></p>
    <?php endif; ?>
    <div style="display:flex;flex-wrap:wrap;gap:0.75rem;margin-top:1.25rem;">
        <a href="register.php?event_id=<?= (int)$eventId ?>&prefill=1<?= $isUnlockedBySecret ? '&unlock=' . urlencode($savedUnlock) : '' ?>" class="btn btn-primary">
            <?= e(I18n::t('reg.add_another_btn')) ?>
        </a>
        <a href="index.php" class="btn btn-secondary">&larr; <?= e(I18n::t('nav.home')) ?></a>
    </div>
<?php else: ?>
    <?php if ($isPrefilledFromPrevious): ?>
        <div class="alert alert-info" style="margin-bottom:1.1rem;">
            <?= e(I18n::t('reg.prefill_notice')) ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong><?= e(I18n::t('reg.error_heading')) ?></strong>
            <ul style="margin:0.5rem 0 0 1.25rem;padding:0;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Prominenter Hinweis auf Orga-Kontaktadresse & Datenspeicherstandort -->
    <div class="doc-box" style="margin-bottom:1.25rem;">
        <?php if ($orgaContactEmail !== ''): ?>
            <div style="margin-bottom:<?= $storageNotice !== '' ? '0.5rem' : '0' ?>;">
                <strong><?= e(I18n::t('reg.contact_prefix')) ?></strong>
                <a href="mailto:<?= e($orgaContactEmail) ?>" style="font-weight:700;"><?= e($orgaContactEmail) ?></a>
            </div>
        <?php endif; ?>
        <?php if ($storageNotice !== ''): ?>
            <div style="font-size:0.86rem;color:var(--text-muted);">
                <?= nl2br(e($storageNotice)) ?>
            </div>
        <?php endif; ?>
    </div>

    <p style="font-size:0.9rem;color:var(--text-muted);margin-top:0;"><?= e(I18n::t('reg.required_hint')) ?></p>

    <form method="post" action="register.php?event_id=<?= (int)$eventId ?>" novalidate>
        <?= Auth::csrfField() ?>
        <?php if ($isUnlockedBySecret): ?>
            <input type="hidden" name="unlock" value="<?= e($savedUnlock) ?>">
        <?php endif; ?>

        <!-- Unsichtbares Honeypot-Feld gegen Spam-Bots -->
        <div style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true">
            <label for="website_url_hp">Website</label>
            <input type="text" id="website_url_hp" name="website_url_hp" tabindex="-1" autocomplete="off">
        </div>

        <!-- Auswahl der Teilnehmergruppe (SC / NSC / Team ...) inkl. Wartelisten-Kennzeichnung -->
        <div class="form-group">
            <label class="form-label" for="group_id">
                <?= e(I18n::t('reg.select_group')) ?><span class="req-star">*</span>
            </label>
            <select id="group_id" name="group_id" class="form-control <?= isset($invalidKeys['group_id']) ? 'is-invalid' : '' ?>" onchange="checkWaitlistNotice()" required>
                <option value="0" data-waitlist="0"><?= e(I18n::t('reg.please_select')) ?></option>
                <?php foreach ($groups as $g):
                    $gid = (int)$g['id'];
                    $gLabel = I18n::field($g, 'name');
                    $isFull = (bool)$g['is_full'];
                    $isWaitlistOption = ($isFull && $waitlistEnabled && !$isUnlockedBySecret);
                    $disabled = ($isFull && !$waitlistEnabled && !$isUnlockedBySecret);

                    if ($isWaitlistOption) {
                        $gLabel .= ' ' . I18n::t('reg.group_waitlist');
                    } elseif ($disabled) {
                        $gLabel .= ' ' . I18n::t('reg.group_full');
                    } elseif ($g['calculated_free'] !== null && (int)$g['show_on_index'] === 1) {
                        $gLabel .= ' (' . (int)$g['calculated_free'] . ' ' . I18n::t('index.free_seats') . ')';
                    }
                ?>
                    <option value="<?= $gid ?>" data-waitlist="<?= $isWaitlistOption ? '1' : '0' ?>" <?= $submittedGroup === $gid ? 'selected' : '' ?> <?= $disabled ? 'disabled' : '' ?>>
                        <?= e($gLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Deutlicher orangener Hinweis-Kasten, sobald eine ausgebuchte Gruppe mit Warteliste ausgewählt wird -->
            <div id="waitlist_notice_box" class="waitlist-notice-box" style="display:none;">
                <?= e(I18n::t('reg.waitlist_warning')) ?>
            </div>
        </div>

        <script>
        function checkWaitlistNotice() {
            const sel = document.getElementById('group_id');
            const box = document.getElementById('waitlist_notice_box');
            if (!sel || !box) return;
            const opt = sel.options[sel.selectedIndex];
            box.style.display = (opt && opt.getAttribute('data-waitlist') === '1') ? 'block' : 'none';
        }
        document.addEventListener('DOMContentLoaded', checkWaitlistNotice);
        </script>

        <!-- Dynamische Felder aus dem Formular-Baukasten -->
        <?php foreach ($fields as $field):
            $type = $field['field_type'];
            $key  = $field['field_key'];
            $lbl  = I18n::field($field, 'label');
            $help = I18n::field($field, 'help');
            $req  = (int)$field['is_required'] === 1;
            $sens = (int)($field['is_sensitive'] ?? 0) === 1;
            $enc  = $sens || ((int)($field['is_encrypted'] ?? 0) === 1);
            $lockTooltip = $sens ? I18n::t('reg.sensitive_hint') : I18n::t('reg.encrypted_hint');
            $val  = (string)($submittedVals[$key] ?? '');
            $invClass = isset($invalidKeys[$key]) ? 'is-invalid' : '';

            if ($type === 'heading'):
        ?>
            <h3 class="form-section-heading"><?= e($lbl) ?></h3>
            <?php if ($help !== ''): ?>
                <p class="form-help" style="margin-top:-0.4rem;margin-bottom:1rem;"><?= nl2br(e($help)) ?></p>
            <?php endif; ?>
            <?php continue; endif; ?>

            <div class="form-group">
                <?php if ($type !== 'checkbox' && $type !== 'document'): ?>
                    <label class="form-label" for="f_<?= e($key) ?>">
                        <?= e($lbl) ?>
                        <?php if ($req): ?><span class="req-star">*</span><?php endif; ?>
                        <?php if ($enc): ?><span style="cursor:help;font-size:0.9rem;margin-left:0.25rem;opacity:0.8;" title="<?= e($lockTooltip) ?>">🔒</span><?php endif; ?>
                    </label>
                <?php endif; ?>

                <?php if ($type === 'text' || $type === 'email' || $type === 'number' || $type === 'date'): ?>
                    <input
                        type="<?= e($type) ?>"
                        id="f_<?= e($key) ?>"
                        name="f[<?= e($key) ?>]"
                        value="<?= e($val) ?>"
                        class="form-control <?= $invClass ?>"
                        <?= $req ? 'required' : '' ?>
                    >

                <?php elseif ($type === 'textarea'): ?>
                    <textarea
                        id="f_<?= e($key) ?>"
                        name="f[<?= e($key) ?>]"
                        rows="3"
                        class="form-control <?= $invClass ?>"
                        <?= $req ? 'required' : '' ?>
                    ><?= e($val) ?></textarea>

                <?php elseif ($type === 'select'):
                    $optsData = json_decode((string)$field['options_json'], true);
                    $langKey  = I18n::isEnglish() && !empty($optsData['en']) ? 'en' : 'de';
                    $options  = $optsData[$langKey] ?? $optsData['de'] ?? [];
                ?>
                    <select id="f_<?= e($key) ?>" name="f[<?= e($key) ?>]" class="form-control <?= $invClass ?>" <?= $req ? 'required' : '' ?>>
                        <option value=""><?= e(I18n::t('reg.please_select')) ?></option>
                        <?php foreach ($options as $opt): ?>
                            <option value="<?= e($opt) ?>" <?= $val === (string)$opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                        <?php endforeach; ?>
                    </select>

                <?php elseif ($type === 'checkbox'): ?>
                    <div class="checkbox-row">
                        <input
                            type="checkbox"
                            id="f_<?= e($key) ?>"
                            name="f[<?= e($key) ?>]"
                            value="1"
                            <?= $val === '1' ? 'checked' : '' ?>
                        >
                        <label for="f_<?= e($key) ?>" style="font-weight:600;cursor:pointer;">
                            <?= e($lbl) ?>
                            <?php if ($req): ?><span class="req-star">*</span><?php endif; ?>
                            <?php if ($enc): ?><span style="cursor:help;font-size:0.9rem;margin-left:0.25rem;opacity:0.8;" title="<?= e($lockTooltip) ?>">🔒</span><?php endif; ?>
                        </label>
                    </div>

                <?php elseif ($type === 'document'):
                    $docMeta = json_decode((string)$field['options_json'], true) ?: [];
                    $docFile = (string)($docMeta['file_path'] ?? '');
                    $docLbl  = I18n::isEnglish() && !empty($docMeta['link_label_en'])
                        ? (string)$docMeta['link_label_en']
                        : (string)($docMeta['link_label_de'] ?? I18n::t('reg.doc_open'));
                ?>
                    <div class="doc-box <?= $invClass ?>">
                        <div style="font-weight:600;margin-bottom:0.4rem;">
                            <?= e($lbl) ?>
                            <?php if ($req): ?><span class="req-star">*</span><?php endif; ?>
                        </div>
                        <?php if ($docFile !== ''): ?>
                            <div style="margin-bottom:0.65rem;">
                                <a href="<?= e($docFile) ?>" target="_blank" rel="noopener" class="btn btn-secondary" style="padding:0.35rem 0.8rem;font-size:0.86rem;">
                                    📎 <?= e($docLbl) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <div class="checkbox-row">
                            <input
                                type="checkbox"
                                id="f_<?= e($key) ?>"
                                name="f[<?= e($key) ?>]"
                                value="1"
                                <?= $val === '1' ? 'checked' : '' ?>
                            >
                            <label for="f_<?= e($key) ?>" style="cursor:pointer;">
                                <?= e($help !== '' ? $help : I18n::t('reg.doc_accept')) ?>
                            </label>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($help !== '' && $type !== 'document'): ?>
                    <div class="form-help"><?= nl2br(e($help)) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div style="margin-top:2rem;padding-top:1.25rem;border-top:2px solid var(--card-border);">
            <button type="submit" class="btn btn-primary" style="font-size:1.05rem;padding:0.75rem 1.75rem;">
                ✅ <?= e(I18n::t('reg.submit_btn')) ?>
            </button>
            <a href="index.php" class="btn btn-secondary" style="margin-left:0.75rem;">
                <?= e(I18n::t('nav.home')) ?>
            </a>
        </div>
    </form>
<?php endif; ?>

<?php
$html = (string)ob_get_clean();
Theme::render($pageTitle, $html, ['event' => $event]);
