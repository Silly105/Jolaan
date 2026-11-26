<?php
declare(strict_types=1);

namespace App\Modules\Events;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Theme;
use App\Core\UploadValidator;
use App\Modules\Admin\AdminLayout;

class EventAdminController
{
    public static function handleDashboard(): void
    {
        $events = EventRepository::all();
        $tRegs   = Database::table('registrations');
        $tGroups = Database::table('event_groups');

        foreach ($events as &$ev) {
            $eid = (int)$ev['id'];
            $ev['groups'] = EventRepository::getGroupsWithSeats($eid);
            $ev['total_regs'] = (int)Database::fetchScalar(
                "SELECT COUNT(*) FROM {$tRegs} r LEFT JOIN {$tGroups} g ON g.id = r.group_id WHERE r.event_id = :eid AND COALESCE(g.show_in_registration, 1) = 1",
                ['eid' => $eid]
            );
            $ev['confirmed_regs'] = (int)Database::fetchScalar(
                "SELECT COUNT(*) FROM {$tRegs} r LEFT JOIN {$tGroups} g ON g.id = r.group_id WHERE r.event_id = :eid AND r.is_confirmed = 1 AND r.is_waitlist = 0 AND COALESCE(g.show_in_registration, 1) = 1",
                ['eid' => $eid]
            );
            $ev['waitlist_regs'] = (int)Database::fetchScalar(
                "SELECT COUNT(*) FROM {$tRegs} r LEFT JOIN {$tGroups} g ON g.id = r.group_id WHERE r.event_id = :eid AND r.is_confirmed = 1 AND r.is_waitlist = 1 AND COALESCE(g.show_in_registration, 1) = 1",
                ['eid' => $eid]
            );
            $ev['unpaid_regs'] = (int)Database::fetchScalar(
                "SELECT COUNT(*) FROM {$tRegs} r LEFT JOIN {$tGroups} g ON g.id = r.group_id WHERE r.event_id = :eid AND r.is_confirmed = 1 AND r.is_waitlist = 0 AND r.is_fully_paid = 0 AND COALESCE(g.show_in_registration, 1) = 1",
                ['eid' => $eid]
            );
        }
        unset($ev);

        ob_start();
        ?>
        <div class="card">
            <div class="card-header">
                <h2>Veranstaltungen (Cons)</h2>
                <?php if (Auth::isAdmin()): ?>
                    <a href="admin.php?module=events&action=new" class="btn btn-primary">➕ Neue Veranstaltung anlegen</a>
                <?php endif; ?>
            </div>
            <div class="card-body table-responsive">
                <?php if (empty($events)): ?>
                    <p style="margin:0;color:#64748b;">Noch keine Veranstaltungen angelegt.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Veranstaltung</th>
                                <th style="width:110px;">Datum</th>
                                <th>Status</th>
                                <th>Anmeldungen</th>
                                <th>Verfügbare Plätze</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $ev):
                                $eid = (int)$ev['id'];
                                $sFormatted = format_date($ev['start_date']);
                                $eFormatted = format_date($ev['end_date']);
                            ?>
                                <tr>
                                    <td>
                                        <strong><?= e($ev['title']) ?></strong>
                                        <?php if ($ev['subtitle'] !== ''): ?>
                                            <div style="font-size:0.82rem;color:#64748b;"><?= e($ev['subtitle']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="width:110px;min-width:102px;max-width:115px;line-height:1.35;">
                                        <?php if ($sFormatted === $eFormatted): ?>
                                            <?= e($sFormatted) ?>
                                        <?php else: ?>
                                            <span style="white-space:nowrap;"><?= e($sFormatted) ?> –</span><br>
                                            <span style="white-space:nowrap;"><?= e($eFormatted) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display:flex;flex-wrap:wrap;gap:0.25rem;">
                                            <?php if ($ev['status'] === 'open'): ?>
                                                <span class="badge badge-green">Anmeldung offen</span>
                                            <?php else: ?>
                                                <span class="badge badge-red">Gesperrt</span>
                                            <?php endif; ?>
                                            <?php if ((int)$ev['show_on_index'] === 1): ?>
                                                <span class="badge badge-blue">Auf Startseite</span>
                                            <?php endif; ?>
                                            <?php if ($ev['waitlist_regs'] > 0): ?>
                                                <span class="badge badge-orange">Warteliste (<?= $ev['waitlist_regs'] ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?= $ev['confirmed_regs'] ?></strong> bestätigt
                                        <?php if ($ev['waitlist_regs'] > 0): ?>
                                            <span style="color:#c2410c;font-weight:600;">(+ <?= $ev['waitlist_regs'] ?> WL)</span>
                                        <?php endif; ?>
                                        <span style="color:#64748b;">(ges. <?= $ev['total_regs'] ?>)</span>
                                        <?php if ($ev['unpaid_regs'] > 0): ?>
                                            <div style="font-size:0.8rem;color:#b45309;"><?= $ev['unpaid_regs'] ?> Zahlung(en) offen</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php foreach ($ev['groups'] as $g):
                                            if ((int)($g['show_in_registration'] ?? 1) === 0 && (int)$g['total_count'] === 0) continue;
                                        ?>
                                            <div style="font-size:0.82rem;">
                                                <?= e($g['name_de']) ?>:
                                                <?php if ((int)($g['show_in_registration'] ?? 1) === 0): ?>
                                                    <em><?= (int)$g['total_count'] ?> (intern)</em>
                                                <?php elseif ($g['calculated_free'] !== null): ?>
                                                    <strong><?= (int)$g['calculated_free'] ?></strong> frei
                                                    <?= $g['is_overridden'] ? '(manuell)' : '' ?>
                                                <?php else: ?>
                                                    <em>unbegrenzt</em>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </td>
                                    <td>
                                        <div style="display:flex;flex-wrap:wrap;gap:0.35rem;">
                                            <a href="admin.php?module=registrations&event_id=<?= $eid ?>" class="btn btn-primary btn-sm">Anmeldeverwaltung</a>
                                            <a href="admin.php?module=lists&event_id=<?= $eid ?>" class="btn btn-secondary btn-sm">Listen</a>
                                            <a href="admin.php?module=broadcast&event_id=<?= $eid ?>" class="btn btn-secondary btn-sm">Rundmail</a>
                                            <a href="admin.php?module=groups&event_id=<?= $eid ?>" class="btn btn-secondary btn-sm">Verfügbare Plätze</a>
                                            <?php if (Auth::isAdmin()): ?>
                                                <a href="admin.php?module=events&action=edit&id=<?= $eid ?>" class="btn btn-secondary btn-sm">Con-Einstellungen</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
        AdminLayout::render('Veranstaltungsübersicht (Dashboard)', 'dashboard', (string)ob_get_clean());
    }

    public static function handleEvents(): void
    {
        if (!Auth::isAdmin()) {
            $_SESSION['flash_error'] = 'Nur Administratoren dürfen Veranstaltungen anlegen oder ändern.';
            header('Location: admin.php');
            exit;
        }

        $action = (string)($_REQUEST['action'] ?? 'new');
        $id     = (int)($_REQUEST['id'] ?? 0);
        $tEvents = Database::table('events');

        // Veranstaltung löschen
        if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
            Auth::requireCsrf();
            if (trim((string)($_POST['confirm_text'] ?? '')) === 'LÖSCHEN') {
                $tRegs   = Database::table('registrations');
                $tVals   = Database::table('registration_values');
                $tGroups = Database::table('event_groups');
                $tFields = Database::table('form_fields');
                $tViews  = Database::table('saved_views');

                $evToDelete = EventRepository::find($id);
                $docFields  = Database::fetchAll("SELECT options_json FROM {$tFields} WHERE event_id = :eid AND field_type = 'document'", ['eid' => $id]);

                Database::query("DELETE rv FROM {$tVals} rv INNER JOIN {$tRegs} r ON r.id = rv.registration_id WHERE r.event_id = :eid", ['eid' => $id]);
                Database::query("DELETE FROM {$tRegs} WHERE event_id = :eid", ['eid' => $id]);
                Database::query("DELETE FROM {$tGroups} WHERE event_id = :eid", ['eid' => $id]);
                Database::query("DELETE FROM {$tFields} WHERE event_id = :eid", ['eid' => $id]);
                Database::query("DELETE FROM {$tViews} WHERE event_id = :eid", ['eid' => $id]);
                Database::query("DELETE FROM {$tEvents} WHERE id = :eid", ['eid' => $id]);

                if ($evToDelete) {
                    UploadValidator::deleteStoredFileIfUnused($evToDelete['header_logo_path'] ?? '');
                    UploadValidator::deleteStoredFileIfUnused($evToDelete['banner_image_path'] ?? '');
                    UploadValidator::deleteStoredFileIfUnused($evToDelete['flyer_file_path'] ?? '');
                }
                foreach ($docFields as $df) {
                    $opt = json_decode((string)($df['options_json'] ?? ''), true);
                    if (!empty($opt['file_path'])) {
                        UploadValidator::deleteStoredFileIfUnused((string)$opt['file_path']);
                    }
                }

                if ((int)($_SESSION['active_event_id'] ?? 0) === $id) {
                    unset($_SESSION['active_event_id']);
                }

                $_SESSION['flash_success'] = 'Die Veranstaltung und alle zugehörigen Daten wurden gelöscht.';
                header('Location: admin.php');
                exit;
            } else {
                $_SESSION['flash_error'] = 'Bestätigungstext „LÖSCHEN“ wurde nicht korrekt eingegeben.';
                header("Location: admin.php?module=events&action=edit&id={$id}");
                exit;
            }
        }

        $event = $id > 0 ? EventRepository::find($id) : null;
        $isNew = ($event === null);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['new', 'edit'], true)) {
            Auth::requireCsrf();

            $title           = trim((string)($_POST['title'] ?? ''));
            $subtitle        = trim((string)($_POST['subtitle'] ?? ''));
            $startDate       = trim((string)($_POST['start_date'] ?? date('Y-m-d')));
            $endDate         = trim((string)($_POST['end_date'] ?? $startDate));
            $countdownTarget = trim((string)($_POST['countdown_target'] ?? ''));
            $showOnIndex     = isset($_POST['show_on_index']) ? 1 : 0;
            $showCountdown   = isset($_POST['show_countdown']) ? 1 : 0;
            $locPrefix       = trim((string)($_POST['location_prefix'] ?? ''));
            $location        = trim((string)($_POST['location'] ?? ''));
            $locSuffix       = trim((string)($_POST['location_suffix'] ?? ''));
            $mapsUrl         = trim((string)($_POST['maps_url'] ?? ''));
            $status          = ($_POST['status'] ?? 'open') === 'locked' ? 'locked' : 'open';
            $waitlistEnabled = isset($_POST['waitlist_enabled']) ? 1 : 0;
            $unlockPassword  = trim((string)($_POST['unlock_password'] ?? ''));
            $isBilingual     = isset($_POST['is_bilingual']) ? 1 : 0;
            $theme           = trim((string)($_POST['theme'] ?? ''));
            $headerMode      = trim((string)($_POST['header_mode'] ?? ''));
            $headerTextDe    = trim((string)($_POST['header_text_de'] ?? ''));
            $headerTextEn    = trim((string)($_POST['header_text_en'] ?? ''));
            $flyerUrl        = trim((string)($_POST['flyer_url'] ?? ''));
            $forumUrl        = trim((string)($_POST['forum_url'] ?? ''));
            $adminEmail      = trim((string)($_POST['admin_email'] ?? ''));

            // Optionaler Upload eines Veranstaltungslogos für den Header (mit Sanity-Check)
            $oldHeaderLogo  = (string)($event['header_logo_path'] ?? '');
            $headerLogoPath = $oldHeaderLogo;
            if (!empty($_FILES['header_logo']['name'])) {
                $check = UploadValidator::validateAndStoreImage(
                    $_FILES['header_logo'],
                    'event_logo',
                    2097152,
                    2500,
                    800,
                    ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg']
                );
                if ($check['ok']) {
                    $headerLogoPath = $check['path'];
                } else {
                    $_SESSION['flash_error'] = 'Header-Logo Upload abgelehnt: ' . $check['error'];
                    header('Location: admin.php?module=events&action=' . ($isNew ? 'new' : 'edit&id=' . $id));
                    exit;
                }
            } elseif (isset($_POST['remove_header_logo'])) {
                $headerLogoPath = '';
            }

            // Optionaler Upload eines individuellen Banner-/Stimmungsbildes über der Anmeldung (volle Formularbreite)
            $oldBannerImage  = (string)($event['banner_image_path'] ?? '');
            $bannerImagePath = $oldBannerImage;
            if (!empty($_FILES['banner_image']['name'])) {
                $checkBanner = UploadValidator::validateAndStoreImage(
                    $_FILES['banner_image'],
                    'event_banner',
                    4194304,
                    3000,
                    1400,
                    ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg']
                );
                if ($checkBanner['ok']) {
                    $bannerImagePath = $checkBanner['path'];
                } else {
                    $_SESSION['flash_error'] = 'Bannerbild-Upload abgelehnt: ' . $checkBanner['error'];
                    header('Location: admin.php?module=events&action=' . ($isNew ? 'new' : 'edit&id=' . $id));
                    exit;
                }
            } elseif (isset($_POST['remove_banner_image'])) {
                $bannerImagePath = '';
            }

            // Optionaler Upload eines Flyers (PDF oder Bilddatei, alternativ zur Flyer-URL)
            $oldFlyerFile  = (string)($event['flyer_file_path'] ?? '');
            $flyerFilePath = $oldFlyerFile;
            if (!empty($_FILES['flyer_file']['name'])) {
                $checkFlyer = UploadValidator::validateAndStoreFlyer($_FILES['flyer_file'], 15728640);
                if ($checkFlyer['ok']) {
                    $flyerFilePath = $checkFlyer['path'];
                } else {
                    $_SESSION['flash_error'] = 'Flyer-Upload abgelehnt: ' . $checkFlyer['error'];
                    header('Location: admin.php?module=events&action=' . ($isNew ? 'new' : 'edit&id=' . $id));
                    exit;
                }
            } elseif (isset($_POST['remove_flyer_file'])) {
                $flyerFilePath = '';
            }

            $params = [
                'title'     => $title !== '' ? $title : 'Unbenannte Veranstaltung',
                'subtitle'  => $subtitle,
                'sdate'     => $startDate,
                'edate'     => $endDate,
                'cdtarget'  => $countdownTarget !== '' ? str_replace('T', ' ', $countdownTarget) : null,
                'sindex'    => $showOnIndex,
                'scd'       => $showCountdown,
                'lpre'      => $locPrefix,
                'loc'       => $location,
                'lsuf'      => $locSuffix,
                'maps'      => $mapsUrl,
                'status'    => $status,
                'waitlist'  => $waitlistEnabled,
                'unlock'    => $unlockPassword,
                'biling'    => $isBilingual,
                'theme'     => $theme !== '' ? $theme : null,
                'hmode'     => $headerMode !== '' ? $headerMode : null,
                'hlogo'     => $headerLogoPath,
                'bimg'      => $bannerImagePath,
                'htext_de'  => $headerTextDe,
                'htext_en'  => $headerTextEn,
                'flyer'     => $flyerUrl,
                'flyerfile' => $flyerFilePath,
                'forum'     => $forumUrl,
                'aemail'    => $adminEmail,
            ];

            if ($isNew) {
                Database::query(
                    "INSERT INTO {$tEvents}
                     (title, subtitle, start_date, end_date, countdown_target, show_on_index, show_countdown, location_prefix, location, location_suffix, maps_url, status, waitlist_enabled, unlock_password, is_bilingual, theme, header_mode, header_logo_path, banner_image_path, header_text_de, header_text_en, flyer_url, flyer_file_path, forum_url, admin_email)
                     VALUES (:title, :subtitle, :sdate, :edate, :cdtarget, :sindex, :scd, :lpre, :loc, :lsuf, :maps, :status, :waitlist, :unlock, :biling, :theme, :hmode, :hlogo, :bimg, :htext_de, :htext_en, :flyer, :flyerfile, :forum, :aemail)",
                    $params
                );
                $newId = Database::lastInsertId();

                $cloneFrom = (int)($_POST['clone_from_event_id'] ?? 0);
                EventRepository::cloneStructure($cloneFrom > 0 ? $cloneFrom : null, $newId);

                $_SESSION['flash_success'] = 'Veranstaltung wurde angelegt und Felder/Gruppen/Listen wurden übernommen.';
                header("Location: admin.php?module=events&action=edit&id={$newId}");
                exit;
            } else {
                $params['id'] = $id;
                Database::query(
                    "UPDATE {$tEvents} SET
                     title = :title, subtitle = :subtitle, start_date = :sdate, end_date = :edate,
                     countdown_target = :cdtarget, show_on_index = :sindex, show_countdown = :scd,
                     location_prefix = :lpre, location = :loc, location_suffix = :lsuf, maps_url = :maps,
                     status = :status, waitlist_enabled = :waitlist, unlock_password = :unlock, is_bilingual = :biling,
                     theme = :theme, header_mode = :hmode, header_logo_path = :hlogo, banner_image_path = :bimg,
                     header_text_de = :htext_de, header_text_en = :htext_en,
                     flyer_url = :flyer, flyer_file_path = :flyerfile, forum_url = :forum, admin_email = :aemail
                     WHERE id = :id",
                    $params
                );
                if ($oldHeaderLogo !== '' && $oldHeaderLogo !== $headerLogoPath) {
                    UploadValidator::deleteStoredFileIfUnused($oldHeaderLogo);
                }
                if ($oldBannerImage !== '' && $oldBannerImage !== $bannerImagePath) {
                    UploadValidator::deleteStoredFileIfUnused($oldBannerImage);
                }
                if ($oldFlyerFile !== '' && $oldFlyerFile !== $flyerFilePath) {
                    UploadValidator::deleteStoredFileIfUnused($oldFlyerFile);
                }
                $_SESSION['flash_success'] = 'Veranstaltungseinstellungen wurden gespeichert.';
                header("Location: admin.php?module=events&action=edit&id={$id}");
                exit;
            }
        }

        $allEvents     = EventRepository::all();
        $themesGrouped = Theme::availableThemesGrouped();
        $headerModes   = Theme::headerModes();

        $ev = $event ?? [
            'id'                => 0,
            'title'             => '',
            'subtitle'          => '',
            'start_date'        => date('Y-m-d', strtotime('+30 days')),
            'end_date'          => date('Y-m-d', strtotime('+32 days')),
            'countdown_target'  => '',
            'show_on_index'     => 1,
            'show_countdown'    => 0,
            'location_prefix'   => '',
            'location'          => '',
            'location_suffix'   => '',
            'maps_url'          => '',
            'status'            => 'open',
            'waitlist_enabled'  => 1,
            'unlock_password'   => bin2hex(random_bytes(4)),
            'is_bilingual'      => 0,
            'theme'             => '',
            'header_mode'       => '',
            'header_logo_path'  => '',
            'banner_image_path' => '',
            'header_text_de'    => '',
            'header_text_en'    => '',
            'flyer_url'         => '',
            'flyer_file_path'   => '',
            'forum_url'         => '',
            'admin_email'       => (string)Database::getSetting('mail_from_address', ''),
        ];

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir    = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $regUrl = "{$scheme}://{$host}{$dir}/register.php?event_id={$id}";
        $secretUrl = $regUrl . '&unlock=' . urlencode((string)$ev['unlock_password']);

        ob_start();
        ?>
        <form method="post" action="admin.php?module=events&action=<?= $isNew ? 'new' : 'edit&id=' . $id ?>" enctype="multipart/form-data">
            <?= Auth::csrfField() ?>

            <?php if ($isNew): ?>
                <div class="card">
                    <div class="card-header"><h2>Einstellungen, Felder & Listen übernehmen</h2></div>
                    <div class="card-body">
                        <div class="form-group" style="margin-bottom:0;">
                            <label for="clone_from_event_id">Vorlage für Teilnehmergruppen, Abfragefelder und Auswertungslisten</label>
                            <select id="clone_from_event_id" name="clone_from_event_id">
                                <option value="0">Aus globaler Standard-Vorlage starten (SC, NSC, Team, Absagen + Standardfelder)</option>
                                <?php foreach ($allEvents as $other): ?>
                                    <option value="<?= (int)$other['id'] ?>">
                                        Komplett übernehmen von <?= e($other['title']) ?> (<?= e(format_date($other['start_date'])) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="help-text">Alle Abfragefelder, Teilnehmergruppen und gespeicherten Listen der gewählten Veranstaltung werden automatisch in die neue Veranstaltung kopiert.</div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header"><h2>Direktlinks zur Online-Anmeldung</h2></div>
                    <div class="card-body">
                        <div class="grid-2">
                            <div>
                                <label for="input_public_reg_url">Öffentlicher Anmeldelink</label>
                                <div class="copy-input-wrap">
                                    <input type="text" id="input_public_reg_url" readonly value="<?= e($regUrl) ?>" onclick="this.select()">
                                    <button type="button" class="copy-icon-btn" onclick="copyInputToClipboard('input_public_reg_url', this)" title="In die Zwischenablage kopieren">📋</button>
                                </div>
                            </div>
                            <div>
                                <label for="input_secret_reg_url">Geheim-Link (Anmeldung trotz Sperre / ausgebuchter Plätze)</label>
                                <div class="copy-input-wrap">
                                    <input type="text" id="input_secret_reg_url" readonly value="<?= e($secretUrl) ?>" onclick="this.select()">
                                    <button type="button" class="copy-icon-btn" onclick="copyInputToClipboard('input_secret_reg_url', this)" title="In die Zwischenablage kopieren">📋</button>
                                </div>
                                <div class="help-text">Hinweis: Eine Anmeldung über den Geheim-Link überspringt auch die Warteliste und bucht direkt regulär ein.</div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header"><h2>Basisdaten & Zeitraum</h2></div>
                <div class="card-body">
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="title">Veranstaltungsname (z. B. „Roots VII“)*</label>
                            <input type="text" id="title" name="title" value="<?= e($ev['title']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="subtitle">Untertitel (z. B. „Das Blut des Lebens“)</label>
                            <input type="text" id="subtitle" name="subtitle" value="<?= e($ev['subtitle']) ?>">
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="start_date">Beginn-Datum*</label>
                            <input type="date" id="start_date" name="start_date" value="<?= e($ev['start_date']) ?>" required>
                            <div class="help-text">Stichtag für die automatische U18-Minderjährigen-Berechnung.</div>
                        </div>
                        <div class="form-group">
                            <label for="end_date">End-Datum*</label>
                            <input type="date" id="end_date" name="end_date" value="<?= e($ev['end_date']) ?>" required>
                        </div>
                    </div>
                    <div class="grid-3">
                        <div class="form-group">
                            <label for="location_prefix">Orts-Vorsatz (z. B. „in der“)</label>
                            <input type="text" id="location_prefix" name="location_prefix" value="<?= e($ev['location_prefix']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="location">Veranstaltungsort (z. B. „Burg Bilstein“)</label>
                            <input type="text" id="location" name="location" value="<?= e($ev['location']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="location_suffix">Orts-Nachsatz (z. B. „im Sauerland“)</label>
                            <input type="text" id="location_suffix" name="location_suffix" value="<?= e($ev['location_suffix']) ?>">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="maps_url">Karten- / Google-Maps-Link zum Veranstaltungsort (optional)</label>
                        <input type="url" id="maps_url" name="maps_url" value="<?= e($ev['maps_url'] ?? '') ?>" placeholder="https://maps.google.com/...">
                        <div class="help-text">Wird auf der Startseite als anklickbarer Link hinter Veranstaltungsort und Orts-Nachsatz gelegt.</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2>Freischaltung, Warteliste, Startseite & Zweisprachigkeit</h2></div>
                <div class="card-body">
                    <div class="grid-3">
                        <div class="form-group">
                            <label for="status">Anmeldestatus</label>
                            <select id="status" name="status">
                                <option value="open" <?= $ev['status'] === 'open' ? 'selected' : '' ?>>🟢 Anmeldung geöffnet</option>
                                <option value="locked" <?= $ev['status'] === 'locked' ? 'selected' : '' ?>>🔴 Anmeldung gesperrt (nur mit Geheimlink)</option>
                            </select>
                            <div class="help-text">Einzelne Gruppen (z. B. SC voll) kannst du unter „Verfügbare Plätze“ sperren.</div>
                        </div>
                        <div class="form-group">
                            <label for="unlock_password">Geheimlink-Entsperrungspasswort</label>
                            <input type="text" id="unlock_password" name="unlock_password" value="<?= e($ev['unlock_password']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="admin_email">Orga-Kontakt- & Benachrichtigungs-E-Mail</label>
                            <input type="email" id="admin_email" name="admin_email" value="<?= e($ev['admin_email']) ?>">
                        </div>
                    </div>

                    <div class="grid-2" style="margin-top:0.5rem;">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="show_on_index" value="1" <?= (int)$ev['show_on_index'] === 1 ? 'checked' : '' ?>>
                                Auf der öffentlichen Startseite anzeigen
                            </label>
                            <label style="margin-top:0.65rem;">
                                <input type="checkbox" name="waitlist_enabled" value="1" <?= (int)($ev['waitlist_enabled'] ?? 1) === 1 ? 'checked' : '' ?>>
                                Wartelisten-Anmeldung bei ausgebuchten Teilnehmergruppen ermöglichen
                            </label>
                            <div class="help-text">Wenn aktiv, können sich Teilnehmer für ausgebuchte Gruppen auf die Warteliste anmelden (Reihenfolge nach E-Mail-Bestätigung).</div>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_bilingual" value="1" <?= (int)$ev['is_bilingual'] === 1 ? 'checked' : '' ?>>
                                Zweisprachige Anmeldung (Deutsch & Englisch)
                            </label>
                            <div class="help-text">Aktiviert den Sprachumschalter und blendet links im Menü die „Übersetzungstabelle“ ein.</div>
                            <label style="margin-top:0.65rem;">
                                <input type="checkbox" name="show_countdown" value="1" <?= (int)$ev['show_countdown'] === 1 ? 'checked' : '' ?>>
                                Countdown auf Startseite anzeigen
                            </label>
                            <input type="datetime-local" name="countdown_target" value="<?= !empty($ev['countdown_target']) ? e(date('Y-m-d\TH:i', strtotime((string)$ev['countdown_target']))) : '' ?>" style="margin-top:0.35rem;">
                        </div>
                    </div>

                    <div class="grid-3">
                        <div class="form-group">
                            <label for="flyer_file">Flyer / Ausschreibung hochladen (PDF oder Bild – optional)</label>
                            <input type="file" id="flyer_file" name="flyer_file" accept=".pdf,.jpg,.jpeg,.png,.webp">
                            <div class="help-text">Erlaubt: PDF, JPG, PNG, WEBP &middot; Max. 15 MB. Hat Vorrang vor einer externen Flyer-URL.</div>
                            <?php if (!empty($ev['flyer_file_path'])): ?>
                                <div style="margin-top:0.45rem;display:flex;align-items:center;gap:0.85rem;flex-wrap:wrap;font-size:0.84rem;">
                                    <a href="<?= e($ev['flyer_file_path']) ?>" target="_blank" rel="noopener">📄 <?= e(basename((string)$ev['flyer_file_path'])) ?></a>
                                    <label style="font-weight:400;margin:0;">
                                        <input type="checkbox" name="remove_flyer_file" value="1"> Datei entfernen
                                    </label>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="flyer_url">Alternativ: Externe URL zum Flyer (optional)</label>
                            <input type="url" id="flyer_url" name="flyer_url" value="<?= e($ev['flyer_url']) ?>" placeholder="https://...">
                            <div class="help-text">Wird genutzt, falls links keine eigene Datei hochgeladen wurde.</div>
                        </div>
                        <div class="form-group">
                            <label for="forum_url">Link zum Veranstaltungs-Forum (optional)</label>
                            <input type="url" id="forum_url" name="forum_url" value="<?= e($ev['forum_url']) ?>" placeholder="https://...">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2>Design, Theme & Veranstaltungs-Header über der Anmeldung</h2></div>
                <div class="card-body">
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="theme">Theme für diese Veranstaltung</label>
                            <select id="theme" name="theme">
                                <option value="">Globales Standard-Theme der Webseite verwenden</option>
                                <?php foreach ($themesGrouped as $grp): if (empty($grp['themes'])) continue; ?>
                                    <optgroup label="<?= e($grp['label']) ?>">
                                        <?php foreach ($grp['themes'] as $slug => $tMeta): ?>
                                            <option value="<?= e($slug) ?>" <?= ($ev['theme'] ?? '') === $slug ? 'selected' : '' ?>>
                                                <?= e($tMeta['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                            <div class="help-text">Überschreibt das globale Seitentheme beim Aufruf dieses Anmeldeformulars.</div>
                        </div>
                        <div class="form-group">
                            <label for="header_mode">Header-Modus für diese Veranstaltung</label>
                            <select id="header_mode" name="header_mode">
                                <option value="">Globalen Standard Header-Modus verwenden</option>
                                <?php foreach ($headerModes as $mKey => $mLabel): ?>
                                    <option value="<?= e($mKey) ?>" <?= ($ev['header_mode'] ?? '') === $mKey ? 'selected' : '' ?>>
                                        <?= e($mLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="help-text">Legt fest, ob die Kopfleiste kompakt, als zentriertes Wappen/Banner oder als großer Hero-Header dargestellt wird.</div>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="header_logo">Optionales Veranstaltungs-Logo im oberen Seitenkopf</label>
                            <input type="file" id="header_logo" name="header_logo" accept=".png,.jpg,.jpeg,.gif,.webp,.svg">
                            <div class="help-text">Erlaubt: PNG, JPG, WEBP, GIF, SVG &middot; Max. 2 MB &middot; Max. 2500×800 px (Empfohlen: Querformat oder Wappen mit transparentem Hintergrund).</div>
                            <?php if (!empty($ev['header_logo_path'])): ?>
                                <div style="margin-top:0.5rem;display:flex;align-items:center;gap:1rem;">
                                    <img src="<?= e($ev['header_logo_path']) ?>" alt="Logo" style="max-height:48px;border-radius:4px;border:1px solid #cbd5e1;">
                                    <label style="font-weight:400;margin:0;">
                                        <input type="checkbox" name="remove_header_logo" value="1"> Logo entfernen
                                    </label>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="banner_image">Optionales Bild über dem Einführungstext (in voller Formularbreite)</label>
                            <input type="file" id="banner_image" name="banner_image" accept=".png,.jpg,.jpeg,.gif,.webp,.svg">
                            <div class="help-text">Wird auf der Anmeldeseite direkt oberhalb des Einführungstextes in exakt derselben Breite wie das Anmeldeformular (980 px) angezeigt. Max. 4 MB &middot; Max. 3000×1400 px (Empfohlen: ca. 980×280 px bis 1960×560 px).</div>
                            <?php if (!empty($ev['banner_image_path'])): ?>
                                <div style="margin-top:0.5rem;display:flex;align-items:center;gap:1rem;">
                                    <img src="<?= e($ev['banner_image_path']) ?>" alt="Veranstaltungsbild" style="max-height:56px;max-width:180px;object-fit:cover;border-radius:4px;border:1px solid #cbd5e1;">
                                    <label style="font-weight:400;margin:0;">
                                        <input type="checkbox" name="remove_banner_image" value="1"> Bild entfernen
                                    </label>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="header_text_de">Optionaler Textblock im Header über der Anmeldung (Deutsch)</label>
                            <textarea id="header_text_de" name="header_text_de" rows="3" placeholder="Wichtige Hinweise, Einleitungstext oder IT-Stimmungstext über dem Formular..."><?= e($ev['header_text_de']) ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="header_text_en">Optionaler Textblock im Header über der Anmeldung (Englisch)</label>
                            <textarea id="header_text_en" name="header_text_en" rows="3" placeholder="Optional header text in English..."><?= e($ev['header_text_en']) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-bottom:2rem;">
                <button type="submit" class="btn btn-primary" style="font-size:1rem;padding:0.7rem 1.5rem;">
                    💾 <?= $isNew ? 'Veranstaltung jetzt anlegen' : 'Änderungen speichern' ?>
                </button>
                <a href="admin.php" class="btn btn-secondary" style="margin-left:0.5rem;">Zurück zur Übersicht</a>
            </div>
        </form>

        <?php if (!$isNew): ?>
            <div class="card" style="border-color:#fca5a5;">
                <div class="card-header" style="background:#fef2f2;">
                    <h2 style="color:#991b1b;">Gefahrenzone – Veranstaltung komplett löschen</h2>
                </div>
                <div class="card-body">
                    <form method="post" action="admin.php?module=events&action=delete&id=<?= $id ?>" onsubmit="return confirm('Veranstaltung samt aller Anmeldungen wirklich unwiderruflich löschen?');">
                        <?= Auth::csrfField() ?>
                        <p style="margin-top:0;">Um diese Veranstaltung inklusive aller eingegangenen Anmeldungen unwiderruflich zu löschen, tippe <strong>LÖSCHEN</strong> in das Feld</p>
                        <div style="display:flex;gap:0.75rem;max-width:420px;">
                            <input type="text" name="confirm_text" placeholder="LÖSCHEN" required>
                            <button type="submit" class="btn btn-danger">Endgültig löschen</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        <?php
        AdminLayout::render($isNew ? 'Neue Veranstaltung anlegen' : 'Con-Einstellungen – ' . $ev['title'], 'events', (string)ob_get_clean(), $isNew ? null : $id);
    }

    /**
     * Seite "Verfügbare Plätze" (Teilnehmergruppen & Kontingente) – freigegeben für Admin UND SL!
     */
    public static function handleGroups(): void
    {
        if (!Auth::isOrga()) {
            header('Location: admin.php');
            exit;
        }

        $eventId = (int)($_REQUEST['event_id'] ?? ($_SESSION['active_event_id'] ?? 0));
        $event   = $eventId > 0 ? EventRepository::find($eventId) : null;
        if (!$event) {
            header('Location: admin.php');
            exit;
        }

        $tGroups = Database::table('event_groups');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::requireCsrf();
            $subAction = (string)($_POST['sub_action'] ?? '');

            if ($subAction === 'save_all') {
                foreach ($_POST['groups'] ?? [] as $gid => $gData) {
                    $gid = (int)$gid;
                    $overrideRaw = trim((string)($gData['manual_free_override'] ?? ''));
                    $overrideVal = ($overrideRaw === '') ? null : max(0, (int)$overrideRaw);

                    Database::query(
                        "UPDATE {$tGroups} SET
                         name_de = :nde, name_en = :nen, max_seats = :max,
                         manual_free_override = :ovr, is_locked = :lck, show_on_index = :sidx, show_in_registration = :sreg, sort_order = :sort
                         WHERE id = :id AND event_id = :eid",
                        [
                            'nde'  => trim((string)($gData['name_de'] ?? 'Gruppe')),
                            'nen'  => trim((string)($gData['name_en'] ?? '')),
                            'max'  => max(0, (int)($gData['max_seats'] ?? 0)),
                            'ovr'  => $overrideVal,
                            'lck'  => isset($gData['is_locked']) ? 1 : 0,
                            'sidx' => isset($gData['show_on_index']) ? 1 : 0,
                            'sreg' => isset($gData['show_in_registration']) ? 1 : 0,
                            'sort' => (int)($gData['sort_order'] ?? 10),
                            'id'   => $gid,
                            'eid'  => $eventId,
                        ]
                    );
                }
                $_SESSION['flash_success'] = 'Teilnehmergruppen und verfügbare Plätze wurden aktualisiert.';
            } elseif ($subAction === 'add_group') {
                $nameDe = trim((string)($_POST['new_name_de'] ?? ''));
                if ($nameDe !== '') {
                    Database::query(
                        "INSERT INTO {$tGroups} (event_id, name_de, name_en, max_seats, show_on_index, show_in_registration, sort_order)
                         VALUES (:eid, :nde, :nen, :max, :sidx, :sreg, :sort)",
                        [
                            'eid'  => $eventId,
                            'nde'  => $nameDe,
                            'nen'  => trim((string)($_POST['new_name_en'] ?? '')),
                            'max'  => max(0, (int)($_POST['new_max_seats'] ?? 0)),
                            'sidx' => isset($_POST['new_show_on_index']) ? 1 : 0,
                            'sreg' => isset($_POST['new_show_in_registration']) ? 1 : 0,
                            'sort' => (int)($_POST['new_sort_order'] ?? 40),
                        ]
                    );
                    $_SESSION['flash_success'] = "Teilnehmergruppe „{$nameDe}“ wurde hinzugefügt.";
                }
            } elseif ($subAction === 'delete_group') {
                $delId = (int)($_POST['delete_id'] ?? 0);
                Database::query("DELETE FROM {$tGroups} WHERE id = :id AND event_id = :eid", ['id' => $delId, 'eid' => $eventId]);
                $_SESSION['flash_success'] = 'Teilnehmergruppe wurde entfernt.';
            }

            header("Location: admin.php?module=groups&event_id={$eventId}");
            exit;
        }

        $groups = EventRepository::getGroupsWithSeats($eventId);

        ob_start();
        ?>
        <div class="card">
            <div class="card-header">
                <h2>Verfügbare Plätze & Teilnehmergruppen</h2>
            </div>
            <div class="card-body">
                <p style="margin-top:0;color:#475569;font-size:0.9rem;">
                    Die freien Plätze auf der Startseite und im Anmeldeformular werden automatisch aus <strong>Max. Plätze minus bestätigte reguläre Anmeldungen</strong> berechnet (bei <code>0</code> = unbegrenzt).
                    Über die Checkbox <strong>„In Anmeldung anzeigen“</strong> legst du fest, ob Teilnehmer diese Gruppe im Formular wählen können (deaktiviert für rein interne Gruppen wie <em>„Absagen“</em>).
                </p>

                <form method="post" action="admin.php?module=groups&event_id=<?= $eventId ?>">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="sub_action" value="save_all">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width:105px;">Sort.</th>
                                    <th>Bezeichnung (DE)*</th>
                                    <th>Bezeichnung (EN)</th>
                                    <th>Max. Plätze (0 = unbegrenzt)</th>
                                    <th>Bestätigte Anm.</th>
                                    <th>Manueller Override</th>
                                    <th>Aktuell angezeigt</th>
                                    <th style="text-align:center;">In Anmeldung anzeigen</th>
                                    <th style="text-align:center;">Auf Startseite</th>
                                    <th style="text-align:center;">Gruppe voll / gesperrt</th>
                                    <th>Löschen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($groups as $g):
                                    $gid = (int)$g['id'];
                                    $inReg = (int)($g['show_in_registration'] ?? 1) === 1;
                                ?>
                                    <tr>
                                        <td>
                                            <input type="number" class="sort-input" name="groups[<?= $gid ?>][sort_order]" value="<?= (int)$g['sort_order'] ?>">
                                        </td>
                                        <td>
                                            <input type="text" name="groups[<?= $gid ?>][name_de]" value="<?= e($g['name_de']) ?>" required>
                                        </td>
                                        <td>
                                            <input type="text" name="groups[<?= $gid ?>][name_en]" value="<?= e($g['name_en']) ?>">
                                        </td>
                                        <td style="width:120px;">
                                            <input type="number" min="0" name="groups[<?= $gid ?>][max_seats]" value="<?= (int)$g['max_seats'] ?>">
                                        </td>
                                        <td>
                                            <strong><?= (int)$g['confirmed_count'] ?></strong>
                                            <?php if ((int)($g['waitlist_count'] ?? 0) > 0): ?>
                                                <span class="badge badge-orange">+<?= (int)$g['waitlist_count'] ?> WL</span>
                                            <?php endif; ?>
                                            <span style="color:#64748b;">(ges. <?= (int)$g['total_count'] ?>)</span>
                                        </td>
                                        <td style="width:130px;">
                                            <input type="number" min="0" name="groups[<?= $gid ?>][manual_free_override]" value="<?= $g['manual_free_override'] !== null ? (int)$g['manual_free_override'] : '' ?>" placeholder="Automatik">
                                        </td>
                                        <td>
                                            <?php if (!$inReg): ?>
                                                <span class="badge badge-gray">Intern (nicht im Formular)</span>
                                            <?php elseif (!empty($g['has_waiting_queue']) && ($g['raw_free'] ?? 0) > 0): ?>
                                                <span class="badge badge-orange" title="Nach außen hin weiterhin als ausgebucht angezeigt, bis die Warteliste abgearbeitet ist"><?= (int)$g['raw_free'] ?> frei für WL (Außen: Voll)</span>
                                            <?php elseif ($g['is_full']): ?>
                                                <span class="badge badge-red">Ausgebucht</span>
                                            <?php elseif ($g['calculated_free'] !== null): ?>
                                                <span class="badge badge-green"><?= (int)$g['calculated_free'] ?> frei <?= $g['is_overridden'] ? '(Override)' : '(Auto)' ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-gray">Unbegrenzt</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:center;">
                                            <input type="checkbox" name="groups[<?= $gid ?>][show_in_registration]" value="1" <?= $inReg ? 'checked' : '' ?>>
                                        </td>
                                        <td style="text-align:center;">
                                            <input type="checkbox" name="groups[<?= $gid ?>][show_on_index]" value="1" <?= (int)$g['show_on_index'] === 1 ? 'checked' : '' ?>>
                                        </td>
                                        <td style="text-align:center;">
                                            <input type="checkbox" name="groups[<?= $gid ?>][is_locked]" value="1" <?= (int)$g['is_locked'] === 1 ? 'checked' : '' ?>>
                                        </td>
                                        <td>
                                            <?php if ((int)$g['total_count'] === 0): ?>
                                                <button type="submit" name="sub_action" value="delete_group" onclick="document.getElementById('del_gid').value='<?= $gid ?>';return confirm('Gruppe löschen?');" class="btn btn-danger btn-sm">✕</button>
                                            <?php else: ?>
                                                <span style="font-size:0.78rem;color:#94a3b8;" title="Bereits Anmeldungen vorhanden">Belegt</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <input type="hidden" id="del_gid" name="delete_id" value="0">
                    <div style="margin-top:1rem;">
                        <button type="submit" class="btn btn-primary">💾 Alle Gruppen speichern</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Weitere Teilnehmergruppe hinzufügen</h2></div>
            <div class="card-body">
                <form method="post" action="admin.php?module=groups&event_id=<?= $eventId ?>">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="sub_action" value="add_group">
                    <div class="grid-3">
                        <div class="form-group">
                            <label>Bezeichnung (Deutsch)*</label>
                            <input type="text" name="new_name_de" placeholder="z. B. Tagesgast / Absagen / Küche" required>
                        </div>
                        <div class="form-group">
                            <label>Bezeichnung (Englisch)</label>
                            <input type="text" name="new_name_en" placeholder="e.g. Day Guest / Cancellations">
                        </div>
                        <div class="form-group">
                            <label>Max. Plätze (0 = unbegrenzt)</label>
                            <input type="number" min="0" name="new_max_seats" value="0">
                        </div>
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:1.5rem;margin-bottom:1rem;">
                        <label style="font-weight:400;margin:0;">
                            <input type="checkbox" name="new_show_in_registration" value="1" checked> Gruppe in Anmeldung anzeigen (deaktivieren für rein interne Gruppen wie „Absagen“)
                        </label>
                        <label style="font-weight:400;margin:0;">
                            <input type="checkbox" name="new_show_on_index" value="1" checked> Freie Plätze dieser Gruppe auf der Startseite anzeigen
                        </label>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-secondary">➕ Gruppe anlegen</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
        AdminLayout::render('Verfügbare Plätze – ' . $event['title'], 'groups', (string)ob_get_clean(), $eventId);
    }
}
