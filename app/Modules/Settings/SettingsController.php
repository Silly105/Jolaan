<?php
declare(strict_types=1);

namespace App\Modules\Settings;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Crypto;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\QrCode;
use App\Core\Theme;
use App\Core\Totp;
use App\Core\Updater;
use App\Core\UploadValidator;
use App\Core\Version;
use App\Modules\Admin\AdminLayout;

class SettingsController
{
    public static function handleSettings(): void
    {
        if (!Auth::isAdmin()) {
            header('Location: admin.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::requireCsrf();
            $subAction = (string)($_POST['sub_action'] ?? 'save_general');

            if ($subAction === 'send_test_mail') {
                $testTo = trim((string)($_POST['test_mail_to'] ?? ''));
                if (!filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
                    $_SESSION['flash_error'] = 'Bitte gib eine gültige Empfänger-E-Mail-Adresse für die Test-E-Mail an.';
                } else {
                    $siteTitle = (string)Database::getSetting('site_title', 'Con-Management');
                    $transport = (string)Database::getSetting('mail_transport', 'mail');
                    $modeLabel = ($transport === 'smtp') ? 'Externer SMTP-Server (' . Database::getSetting('smtp_host', '') . ')' : 'Eigener Webserver (PHP mail())';
                    $subj = "Test-E-Mail von {$siteTitle}";
                    $body = "<p>Hallo,</p><p>dies ist eine automatische Test-E-Mail aus den Seiteneinstellungen von <b>" . e($siteTitle) . "</b>.</p>"
                          . "<ul><li><b>Versandart:</b> " . e($modeLabel) . "</li>"
                          . "<li><b>Zeitpunkt:</b> " . date('d.m.Y H:i:s') . "</li></ul>"
                          . "<p>Der E-Mail-Versand funktioniert einwandfrei!</p>";
                    if (Mailer::send($testTo, $subj, $body)) {
                        $_SESSION['flash_success'] = "Test-E-Mail wurde erfolgreich über „{$modeLabel}“ an {$testTo} versendet!";
                    } else {
                        $_SESSION['flash_error'] = 'Test-E-Mail konnte nicht gesendet werden: ' . Mailer::getLastError();
                    }
                }
                header('Location: admin.php?module=settings');
                exit;
            }

            if ($subAction === 'move_secret_key') {
                // Schlüsseldatei an einen benutzerdefinierten Pfad (z. B. außerhalb des html-root) verschieben
                $targetPath = trim((string)($_POST['custom_key_path'] ?? ''));
                $currentPath = Config::keyPath();

                if ($targetPath === '') {
                    $_SESSION['flash_error'] = 'Bitte gib einen vollständigen Dateipfad für die Schlüsseldatei an.';
                } elseif (realpath($targetPath) === realpath($currentPath)) {
                    $_SESSION['flash_success'] = 'Die Schlüsseldatei befindet sich bereits an diesem Speicherort.';
                } else {
                    try {
                        $targetDir = dirname($targetPath);
                        if (!is_dir($targetDir)) {
                            @mkdir($targetDir, 0750, true);
                        }
                        $keyContent = @file_get_contents($currentPath);
                        if ($keyContent === false || strlen(trim($keyContent)) < 40) {
                            throw new \RuntimeException('Aktuelle Schlüsseldatei konnte nicht gelesen werden.');
                        }
                        if (@file_put_contents($targetPath, $keyContent, LOCK_EX) === false) {
                            throw new \RuntimeException('Auf den Zielpfad konnte nicht geschrieben werden (prüfe Schreibrechte oder open_basedir-Einschränkungen des Hosters).');
                        }
                        $verifyRead = @file_get_contents($targetPath);
                        if ($verifyRead !== $keyContent) {
                            throw new \RuntimeException('Leseprüfung am neuen Speicherort fehlgeschlagen.');
                        }

                        Config::save(['custom_key_path' => $targetPath]);
                        if (realpath($targetPath) !== realpath($currentPath) && is_file($currentPath)) {
                            @unlink($currentPath);
                        }
                        $_SESSION['flash_success'] = "Schlüsseldatei wurde erfolgreich nach „{$targetPath}“ verschoben und das alte Original entfernt!";
                    } catch (\Throwable $e) {
                        $_SESSION['flash_error'] = 'Schlüsseldatei konnte nicht verschoben werden: ' . $e->getMessage();
                    }
                }
                header('Location: admin.php?module=settings');
                exit;
            }

            $keys = [
                'site_title', 'site_subtitle', 'default_theme', 'default_header_mode',
                'index_intro_de', 'index_intro_en',
                'storage_notice_de', 'storage_notice_en',
                'main_site_url', 'main_site_label_de', 'main_site_label_en',
                'forum_url', 'forum_label_de', 'forum_label_en',
                'privacy_url', 'imprint_url',
                'mail_from_address', 'mail_from_name', 'mail_transport',
                'smtp_host', 'smtp_port', 'smtp_user', 'smtp_encryption',
            ];
            foreach ($keys as $k) {
                if (isset($_POST[$k])) {
                    Database::setSetting($k, trim((string)$_POST[$k]));
                }
            }
            if (!empty($_POST['smtp_pass'])) {
                Database::setSetting('smtp_pass', Crypto::encrypt((string)$_POST['smtp_pass']));
            }

            $uploadErrors = [];

            // Optionales globales Seitenlogo hochladen (mit Sanity-Check)
            if (!empty($_FILES['site_logo_file']['name'])) {
                $check = UploadValidator::validateAndStoreImage(
                    $_FILES['site_logo_file'],
                    'site_logo',
                    2097152,
                    2500,
                    800,
                    ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg']
                );
                if ($check['ok']) {
                    Database::setSetting('site_logo', $check['path']);
                } else {
                    $uploadErrors[] = 'Header-Logo: ' . $check['error'];
                }
            } elseif (isset($_POST['remove_site_logo'])) {
                Database::setSetting('site_logo', '');
            }

            // Optionales globales Favicon hochladen (mit Sanity-Check)
            if (!empty($_FILES['site_favicon_file']['name'])) {
                $checkFav = UploadValidator::validateAndStoreImage(
                    $_FILES['site_favicon_file'],
                    'favicon',
                    524288,
                    512,
                    512,
                    ['ico', 'png', 'svg', 'gif', 'webp']
                );
                if ($checkFav['ok']) {
                    Database::setSetting('site_favicon', $checkFav['path']);
                } else {
                    $uploadErrors[] = 'Favicon: ' . $checkFav['error'];
                }
            } elseif (isset($_POST['remove_site_favicon'])) {
                Database::setSetting('site_favicon', '');
            }

            if (!empty($uploadErrors)) {
                $_SESSION['flash_error'] = implode(' | ', $uploadErrors);
            } else {
                $_SESSION['flash_success'] = 'Globale Design- und Systemeinstellungen wurden gespeichert und angewendet.';
            }

            header('Location: admin.php?module=settings');
            exit;
        }

        $themesGrouped = Theme::availableThemesGrouped();
        $headerModes   = Theme::headerModes();
        $curTheme      = (string)Database::getSetting('default_theme', 'western-seal');
        $curHeaderMode = (string)Database::getSetting('default_header_mode', 'compact');
        $siteLogo      = (string)Database::getSetting('site_logo', '');
        $siteFavicon   = (string)Database::getSetting('site_favicon', '');

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $currentDomainUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'deine-domain.de');

        $currentKeyPath = Config::keyPath();
        $docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__, 3)), '/\\');
        $suggestedOutsidePath = dirname($docRoot) . '/con_secret.key';

        ob_start();
        ?>
        <form method="post" action="admin.php?module=settings" enctype="multipart/form-data">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="sub_action" value="save_general">

            <div class="card">
                <div class="card-header"><h2>Globales Theme, Favicon & Seiten-Header</h2></div>
                <div class="card-body">
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="site_title">Name der Webseite / Orga</label>
                            <input type="text" id="site_title" name="site_title" value="<?= e(Database::getSetting('site_title', '')) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="site_subtitle">Untertitel</label>
                            <input type="text" id="site_subtitle" name="site_subtitle" value="<?= e(Database::getSetting('site_subtitle', '')) ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="default_theme">Globales Standard-Theme der Seite (pro Veranstaltung überschreibbar)</label>
                            <select id="default_theme" name="default_theme">
                                <?php foreach ($themesGrouped as $grp): if (empty($grp['themes'])) continue; ?>
                                    <optgroup label="<?= e($grp['label']) ?>">
                                        <?php foreach ($grp['themes'] as $slug => $tMeta): ?>
                                            <option value="<?= e($slug) ?>" <?= $curTheme === $slug ? 'selected' : '' ?>>
                                                <?= e($tMeta['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                            <div class="help-text">Gilt für Startseite, Orga-Backend und alle Veranstaltungen ohne eigenes Theme.</div>
                        </div>
                        <div class="form-group">
                            <label for="default_header_mode">Globaler Standard Header-Modus (pro Veranstaltung überschreibbar)</label>
                            <select id="default_header_mode" name="default_header_mode">
                                <?php foreach ($headerModes as $mKey => $mLabel): ?>
                                    <option value="<?= e($mKey) ?>" <?= $curHeaderMode === $mKey ? 'selected' : '' ?>>
                                        <?= e($mLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="help-text">Steuert die Darstellung des oberen Seitenkopfes auf der Startseite und bei Veranstaltungen.</div>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="site_logo_file">Globales Header-Logo (falls Veranstaltung kein eigenes Logo hat)</label>
                            <input type="file" id="site_logo_file" name="site_logo_file" accept=".png,.jpg,.jpeg,.gif,.webp,.svg">
                            <div class="help-text">Erlaubt: PNG, JPG, WEBP, GIF, SVG &middot; Max. 2 MB &middot; Max. 2500×800 px (Empfohlen: Querformat ca. 800×150 px bis 1600×300 px).</div>
                            <?php if ($siteLogo !== ''): ?>
                                <div style="margin-top:0.5rem;display:flex;align-items:center;gap:1rem;">
                                    <img src="<?= e($siteLogo) ?>" alt="Logo" style="max-height:45px;border-radius:4px;border:1px solid #cbd5e1;">
                                    <label style="font-weight:400;margin:0;"><input type="checkbox" name="remove_site_logo" value="1"> Entfernen</label>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="site_favicon_file">Favicon der Webseite (Browser-Tab-Icon)</label>
                            <input type="file" id="site_favicon_file" name="site_favicon_file" accept=".ico,.png,.svg,.gif,.webp">
                            <div class="help-text">Erlaubt: ICO, PNG, SVG, WEBP, GIF &middot; Max. 512 KB &middot; Max. 512×512 px (Empfohlen: Quadratisch 32×32 px oder 64×64 px).</div>
                            <?php if ($siteFavicon !== ''): ?>
                                <div style="margin-top:0.5rem;display:flex;align-items:center;gap:1rem;">
                                    <img src="<?= e($siteFavicon) ?>" alt="Favicon" style="width:28px;height:28px;object-fit:contain;border-radius:4px;border:1px solid #cbd5e1;">
                                    <label style="font-weight:400;margin:0;"><input type="checkbox" name="remove_site_favicon" value="1"> Entfernen</label>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Begrüßungstext auf der Startseite (Deutsch)</label>
                            <textarea name="index_intro_de" rows="3"><?= e(Database::getSetting('index_intro_de', '')) ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Begrüßungstext auf der Startseite (Englisch)</label>
                            <textarea name="index_intro_en" rows="3"><?= e(Database::getSetting('index_intro_en', '')) ?></textarea>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Hinweis zum Datenspeicherstandort & Verschlüsselung über der Anmeldung (Deutsch)</label>
                            <textarea name="storage_notice_de" rows="3"><?= e(Database::getSetting('storage_notice_de', 'Deine Anmeldedaten werden auf Servern in Deutschland / der EU gespeichert. Kontakt- und Gesundheitsdaten (mit 🔒 markiert) werden zusätzlich mit AES-256-GCM verschlüsselt in der Datenbank abgelegt.')) ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Hinweis zum Datenspeicherstandort & Verschlüsselung über der Anmeldung (Englisch)</label>
                            <textarea name="storage_notice_en" rows="3"><?= e(Database::getSetting('storage_notice_en', 'Your registration data is stored on servers in Germany / the EU. Contact and health data (marked with 🔒) are additionally stored encrypted using AES-256-GCM.')) ?></textarea>
                        </div>
                    </div>

                    <div class="grid-3">
                        <div class="form-group">
                            <label>Link zur Hauptseite (URL – optional)</label>
                            <input type="url" name="main_site_url" value="<?= e(Database::getSetting('main_site_url', '')) ?>" placeholder="<?= e($currentDomainUrl) ?>">
                        </div>
                        <div class="form-group">
                            <label>Linktext für Hauptseite (Deutsch)</label>
                            <input type="text" name="main_site_label_de" value="<?= e(Database::getSetting('main_site_label_de', 'Hauptseite')) ?>">
                        </div>
                        <div class="form-group">
                            <label>Linktext für Hauptseite (Englisch)</label>
                            <input type="text" name="main_site_label_en" value="<?= e(Database::getSetting('main_site_label_en', 'Main Website')) ?>">
                        </div>
                    </div>
                    <div class="grid-3">
                        <div class="form-group">
                            <label>Link zum Forum (URL – optional)</label>
                            <input type="url" name="forum_url" value="<?= e(Database::getSetting('forum_url', '')) ?>" placeholder="<?= e($currentDomainUrl) ?>/forum">
                        </div>
                        <div class="form-group">
                            <label>Linktext für Forum (Deutsch)</label>
                            <input type="text" name="forum_label_de" value="<?= e(Database::getSetting('forum_label_de', 'Forum')) ?>">
                        </div>
                        <div class="form-group">
                            <label>Linktext für Forum (Englisch)</label>
                            <input type="text" name="forum_label_en" value="<?= e(Database::getSetting('forum_label_en', 'Forum')) ?>">
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Link zur Datenschutzerklärung</label>
                            <input type="url" name="privacy_url" value="<?= e(Database::getSetting('privacy_url', '')) ?>" placeholder="<?= e($currentDomainUrl) ?>/datenschutz">
                        </div>
                        <div class="form-group">
                            <label>Link zum Impressum</label>
                            <input type="url" name="imprint_url" value="<?= e(Database::getSetting('imprint_url', '')) ?>" placeholder="<?= e($currentDomainUrl) ?>/impressum">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2>E-Mail-Versand & SMTP</h2></div>
                <div class="card-body">
                    <div class="alert" style="background:#f8fafc;border:1px solid #cbd5e1;color:#334155;font-size:0.88rem;margin-bottom:1.1rem;">
                        <strong>Hinweis zum E-Mail-Versand:</strong> Ohne Angabe eines externen SMTP-Servers („Standard PHP-Mail“) werden alle System-E-Mails automatisch direkt über den lokalen Maildienst deines eigenen Webservers verschickt. Das funktioniert sofort, solange die Absender-E-Mail-Adresse zu deiner gehosteten Domain gehört. Einen externen SMTP-Server benötigst du nur, wenn du über ein externes Postfach (z. B. mit eigener DKIM-Signatur oder fremder Domain) senden möchtest.
                    </div>
                    <div class="grid-3">
                        <div class="form-group">
                            <label>Absender-E-Mail-Adresse</label>
                            <input type="email" name="mail_from_address" value="<?= e(Database::getSetting('mail_from_address', '')) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Absender-Name</label>
                            <input type="text" name="mail_from_name" value="<?= e(Database::getSetting('mail_from_name', '')) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Versandart</label>
                            <select name="mail_transport">
                                <option value="mail" <?= Database::getSetting('mail_transport', 'mail') === 'mail' ? 'selected' : '' ?>>Eigener Webserver (Standard PHP-Mail)</option>
                                <option value="smtp" <?= Database::getSetting('mail_transport', 'mail') === 'smtp' ? 'selected' : '' ?>>Externer SMTP-Server</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid-3">
                        <div class="form-group">
                            <label>SMTP-Host (nur bei externem SMTP-Server)</label>
                            <input type="text" name="smtp_host" value="<?= e(Database::getSetting('smtp_host', '')) ?>">
                        </div>
                        <div class="form-group">
                            <label>SMTP-Port</label>
                            <input type="number" name="smtp_port" value="<?= e(Database::getSetting('smtp_port', '587')) ?>">
                        </div>
                        <div class="form-group">
                            <label>SMTP-Verschlüsselung</label>
                            <select name="smtp_encryption">
                                <option value="tls" <?= Database::getSetting('smtp_encryption', 'tls') === 'tls' ? 'selected' : '' ?>>STARTTLS (Port 587)</option>
                                <option value="ssl" <?= Database::getSetting('smtp_encryption', 'tls') === 'ssl' ? 'selected' : '' ?>>SSL/TLS (Port 465)</option>
                                <option value="none" <?= Database::getSetting('smtp_encryption', 'tls') === 'none' ? 'selected' : '' ?>>Keine</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>SMTP-Benutzername</label>
                            <input type="text" name="smtp_user" value="<?= e(Database::getSetting('smtp_user', '')) ?>" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label>SMTP-Passwort (wird AES-256-GCM verschlüsselt gespeichert; leer lassen = unverändert)</label>
                            <input type="password" name="smtp_pass" placeholder="••••••••" autocomplete="new-password">
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-bottom:1.75rem;">
                <button type="submit" class="btn btn-primary" style="font-size:1rem;padding:0.65rem 1.5rem;">💾 Einstellungen speichern</button>
            </div>
        </form>

        <!-- Test-E-Mail senden -->
        <div class="card">
            <div class="card-header">
                <h2>Test-E-Mail senden</h2>
            </div>
            <div class="card-body">
                <p style="margin-top:0;font-size:0.9rem;color:#475569;">
                    Hier kannst du prüfen, ob der E-Mail-Versand mit den aktuell gespeicherten Einstellungen (eigener Webserver oder externer SMTP-Server) funktioniert.
                </p>
                <form method="post" action="admin.php?module=settings">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="sub_action" value="send_test_mail">
                    <div class="form-group" style="max-width:540px;margin-bottom:0;">
                        <label for="test_mail_to">Empfänger-E-Mail-Adresse für die Testmail</label>
                        <div style="display:flex;gap:0.6rem;">
                            <input type="email" id="test_mail_to" name="test_mail_to" value="<?= e(Auth::user()['email'] ?? '') ?>" placeholder="deine-adresse@beispiel.de" required>
                            <button type="submit" class="btn btn-secondary" style="white-space:nowrap;">✉️ Test-E-Mail jetzt senden</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Speicherort der AES-256 Schlüsseldatei (secret.key) anpassen -->
        <div class="card">
            <div class="card-header">
                <h2>Speicherort der Verschlüsselungs-Schlüsseldatei (<code>secret.key</code>)</h2>
            </div>
            <div class="card-body">
                <p style="margin-top:0;font-size:0.9rem;color:#475569;">
                    Die Schlüsseldatei wird benötigt, um verschlüsselte Teilnehmerdaten (E-Mail, Telefon, Gesundheitsdaten) zu ver- und entschlüsseln.
                    Standardmäßig liegt sie durch <code>.htaccess</code> geschützt unter <code>storage/config/secret.key</code>.
                    Für maximale Sicherheit kannst du sie mit einem Klick in ein Verzeichnis <strong>oberhalb des Webroots (außerhalb von <code>public_html</code>)</strong> verschieben.
                </p>
                <div style="background:#f8fafc;padding:0.85rem 1.1rem;border-radius:6px;border:1px solid #cbd5e1;margin-bottom:1rem;font-size:0.88rem;">
                    <div><strong>Aktueller Speicherort:</strong> <code><?= e($currentKeyPath) ?></code></div>
                    <div style="margin-top:0.25rem;color:#64748b;"><strong>Vorschlag außerhalb des Webroots:</strong> <code><?= e($suggestedOutsidePath) ?></code></div>
                </div>
                <form method="post" action="admin.php?module=settings" onsubmit="return confirm('Schlüsseldatei jetzt an den neuen Serverpfad verschieben?');">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="sub_action" value="move_secret_key">
                    <div class="form-group" style="max-width:680px;">
                        <label for="custom_key_path">Neuer absoluter Serverpfad für die Schlüsseldatei</label>
                        <div style="display:flex;gap:0.6rem;">
                            <input type="text" id="custom_key_path" name="custom_key_path" value="<?= e($currentKeyPath) ?>" placeholder="<?= e($suggestedOutsidePath) ?>" required>
                            <button type="submit" class="btn btn-secondary" style="white-space:nowrap;">📦 Schlüsseldatei verschieben</button>
                        </div>
                        <div class="help-text">Das System prüft vor dem Umstellen automatisch, ob der neue Pfad beschreibbar und lesbar ist.</div>
                    </div>
                </form>
            </div>
        </div>
        <?php
        AdminLayout::render('Seiteneinstellungen', 'settings', (string)ob_get_clean());
    }

    public static function handleMailTemplates(): void
    {
        if (!Auth::isAdmin()) {
            header('Location: admin.php');
            exit;
        }

        $templates = [
            'doi'      => 'E-Mail-Bestätigung (Bestätigungslink direkt nach Absenden des Formulars)',
            'waitlist' => 'Wartelistenplatz bestätigen (mit aktuellem {wartelistenplatz})',
            'confirm'  => 'Anmeldebestätigung durch die Orga',
            'reminder' => 'Zahlungserinnerung',
            'paid'     => 'Geldeingangsbestätigung',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::requireCsrf();
            foreach (array_keys($templates) as $tKey) {
                foreach (['de', 'en'] as $lang) {
                    $sKey = "mail_tpl_{$tKey}_subject_{$lang}";
                    $bKey = "mail_tpl_{$tKey}_body_{$lang}";
                    if (isset($_POST[$sKey])) {
                        Database::setSetting($sKey, trim((string)$_POST[$sKey]));
                    }
                    if (isset($_POST[$bKey])) {
                        Database::setSetting($bKey, trim((string)$_POST[$bKey]));
                    }
                }
            }
            $_SESSION['flash_success'] = 'E-Mail-Vorlagen wurden gespeichert.';
            header('Location: admin.php?module=mailtemplates');
            exit;
        }

        ob_start();
        ?>
        <div class="alert" style="background:#eff6ff;border:1px solid #93c5fd;color:#1e3a8a;">
            <strong>Verfügbare Platzhalter:</strong> <code>{vorname}</code>, <code>{nachname}</code>, <code>{conname}</code>, <code>{condatum}</code>, <code>{gruppe}</code>, <code>{betrag}</code>, <code>{link}</code>, <code>{wartelistenplatz}</code> (HTML ist erlaubt).
        </div>

        <form method="post" action="admin.php?module=mailtemplates">
            <?= Auth::csrfField() ?>
            <?php foreach ($templates as $tKey => $tTitle): ?>
                <div class="card">
                    <div class="card-header"><h2><?= e($tTitle) ?></h2></div>
                    <div class="card-body">
                        <div class="grid-2">
                            <div>
                                <div class="form-group">
                                    <label>Betreff (Deutsch)</label>
                                    <input type="text" name="mail_tpl_<?= $tKey ?>_subject_de" value="<?= e(Database::getSetting("mail_tpl_{$tKey}_subject_de", '')) ?>">
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label>Nachrichtentext HTML (Deutsch)</label>
                                    <textarea name="mail_tpl_<?= $tKey ?>_body_de" rows="6"><?= e(Database::getSetting("mail_tpl_{$tKey}_body_de", '')) ?></textarea>
                                </div>
                            </div>
                            <div>
                                <div class="form-group">
                                    <label>Betreff (Englisch)</label>
                                    <input type="text" name="mail_tpl_<?= $tKey ?>_subject_en" value="<?= e(Database::getSetting("mail_tpl_{$tKey}_subject_en", '')) ?>">
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label>Nachrichtentext HTML (Englisch)</label>
                                    <textarea name="mail_tpl_<?= $tKey ?>_body_en" rows="6"><?= e(Database::getSetting("mail_tpl_{$tKey}_body_en", '')) ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary" style="font-size:1rem;padding:0.65rem 1.5rem;">💾 Alle E-Mail-Vorlagen speichern</button>
        </form>
        <?php
        AdminLayout::render('E-Mail-Vorlagen (DE & EN)', 'mailtemplates', (string)ob_get_clean());
    }

    public static function handleUsers(): void
    {
        if (!Auth::isAdmin()) {
            header('Location: admin.php');
            exit;
        }

        $tUsers = Database::table('users');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::requireCsrf();
            $subAction = (string)($_POST['sub_action'] ?? '');

            if ($subAction === 'create_user') {
                $email = trim((string)($_POST['new_user_email'] ?? ''));
                $name  = trim((string)($_POST['new_user_name'] ?? ''));
                $role  = ($_POST['new_user_role'] ?? 'orga') === 'admin' ? 'admin' : 'orga';
                $pass  = (string)($_POST['new_user_initial_password'] ?? '');

                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8) {
                    $_SESSION['flash_error'] = 'Bitte gib eine gültige E-Mail-Adresse und ein Passwort mit mindestens 8 Zeichen an.';
                } else {
                    try {
                        Database::query(
                            "INSERT INTO {$tUsers} (email, password_hash, name, role, is_active) VALUES (:email, :hash, :name, :role, 1)",
                            ['email' => $email, 'hash' => password_hash($pass, PASSWORD_DEFAULT), 'name' => $name, 'role' => $role]
                        );
                        $_SESSION['flash_success'] = "Benutzer „{$name}“ (" . Auth::roleLabel($role) . ") wurde angelegt.";
                    } catch (\Throwable) {
                        $_SESSION['flash_error'] = 'Diese E-Mail-Adresse ist bereits vergeben.';
                    }
                }
            } elseif ($subAction === 'update_user') {
                $uid   = (int)($_POST['user_id'] ?? 0);
                $role  = ($_POST['role'] ?? 'orga') === 'admin' ? 'admin' : 'orga';
                $act   = isset($_POST['is_active']) ? 1 : 0;
                $newPw = trim((string)($_POST['reset_user_password'] ?? ''));

                $currentUser = Auth::user();
                if ($currentUser && $uid === (int)$currentUser['id']) {
                    $role = 'admin';
                    $act  = 1;
                }

                Database::query("UPDATE {$tUsers} SET role = :role, is_active = :act WHERE id = :id", ['role' => $role, 'act' => $act, 'id' => $uid]);
                if ($newPw !== '' && strlen($newPw) >= 8) {
                    Database::query("UPDATE {$tUsers} SET password_hash = :hash WHERE id = :id", ['hash' => password_hash($newPw, PASSWORD_DEFAULT), 'id' => $uid]);
                }
                $_SESSION['flash_success'] = 'Benutzer wurde aktualisiert.';
            }

            header('Location: admin.php?module=users');
            exit;
        }

        $users = Database::fetchAll("SELECT id, email, name, role, is_active, totp_enabled, created_at FROM {$tUsers} ORDER BY role ASC, name ASC");
        $meId  = (int)(Auth::user()['id'] ?? 0);

        ob_start();
        ?>
        <div class="card">
            <div class="card-header"><h2>SL- & Administrator-Konten</h2></div>
            <div class="card-body table-responsive">
                <p style="margin-top:0;font-size:0.88rem;color:#475569;">
                    <strong>Rollen-Konzept</strong> –
                    <code>Admin</code> hat Vollzugriff (Cons, Abfragefelder, DSGVO-Center, Seiteneinstellungen).
                    <code>SL</code> kann sich im Backend anmelden, Anmeldeverwaltung, Verfügbare Plätze sowie Listen/Filter/Druckansichten einsehen und bearbeiten.
                </p>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>E-Mail</th>
                            <th>Rolle</th>
                            <th>2FA-Schutz</th>
                            <th>Aktiv</th>
                            <th>Neues Passwort setzen (optional)</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u):
                            $uid = (int)$u['id'];
                        ?>
                            <tr>
                                <form method="post" action="admin.php?module=users" autocomplete="off">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="sub_action" value="update_user">
                                    <input type="hidden" name="user_id" value="<?= $uid ?>">
                                    <td><strong><?= e($u['name']) ?></strong> <?= $uid === $meId ? '<span class="badge badge-blue">Du</span>' : '' ?></td>
                                    <td><?= e($u['email']) ?></td>
                                    <td>
                                        <select name="role" <?= $uid === $meId ? 'disabled' : '' ?>>
                                            <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                                            <option value="orga" <?= $u['role'] === 'orga' ? 'selected' : '' ?>>SL</option>
                                        </select>
                                    </td>
                                    <td>
                                        <?php if ((int)($u['totp_enabled'] ?? 0) === 1): ?>
                                            <span class="badge badge-green" title="Geschützt durch TOTP + persönliche Notfallcodes">🔐 2FA aktiv</span>
                                        <?php else: ?>
                                            <span class="badge badge-gray">Aus</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <input type="checkbox" name="is_active" value="1" <?= (int)$u['is_active'] === 1 ? 'checked' : '' ?> <?= $uid === $meId ? 'disabled' : '' ?>>
                                    </td>
                                    <td>
                                        <input type="password" name="reset_user_password" placeholder="Mind. 8 Zeichen (leer = unverändert)" autocomplete="new-password">
                                    </td>
                                    <td style="white-space:nowrap;">
                                        <button type="submit" class="btn btn-primary btn-sm">💾 Speichern</button>
                                    </td>
                                </form>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Neuen Backend-Benutzer anlegen</h2></div>
            <div class="card-body">
                <form method="post" action="admin.php?module=users" autocomplete="off">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="sub_action" value="create_user">
                    <!-- Versteckte Dummy-Felder verhindern unerwünschtes Browser-Autofill des eigenen Admin-Logins -->
                    <input type="text" name="prevent_autofill_user" style="display:none;" tabindex="-1" autocomplete="username">
                    <input type="password" name="prevent_autofill_pass" style="display:none;" tabindex="-1" autocomplete="current-password">

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="new_user_name">Name*</label>
                            <input type="text" id="new_user_name" name="new_user_name" autocomplete="off" required>
                        </div>
                        <div class="form-group">
                            <label for="new_user_email">E-Mail-Adresse*</label>
                            <input type="email" id="new_user_email" name="new_user_email" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="new_user_role">Rolle*</label>
                            <select id="new_user_role" name="new_user_role">
                                <option value="orga">SL (Teilnehmer & Listen verwalten)</option>
                                <option value="admin">Administrator (Vollzugriff)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="new_user_initial_password">Initiales Passwort (mindestens 8 Zeichen)*</label>
                            <input type="password" id="new_user_initial_password" name="new_user_initial_password" minlength="8" autocomplete="new-password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-secondary">➕ Benutzer anlegen</button>
                </form>
            </div>
        </div>
        <?php
        AdminLayout::render('Benutzer & Rollen verwalten', 'users', (string)ob_get_clean());
    }

    public static function handleProfile(): void
    {
        $user = Auth::user();
        if (!$user) {
            header('Location: admin.php');
            exit;
        }

        $tUsers = Database::table('users');
        $dbUser = Database::fetchOne("SELECT * FROM {$tUsers} WHERE id = :id", ['id' => (int)$user['id']]);
        if (!$dbUser) {
            header('Location: admin.php');
            exit;
        }

        $freshBackupCodes = $_SESSION['fresh_2fa_backup_codes'] ?? null;
        unset($_SESSION['fresh_2fa_backup_codes']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::requireCsrf();
            $subAction = (string)($_POST['sub_action'] ?? 'change_password');

            if ($subAction === 'change_password') {
                $oldPw  = (string)($_POST['old_password'] ?? '');
                $newPw1 = (string)($_POST['new_password1'] ?? '');
                $newPw2 = (string)($_POST['new_password2'] ?? '');

                if (!password_verify($oldPw, (string)$dbUser['password_hash'])) {
                    $_SESSION['flash_error'] = 'Das aktuelle Passwort ist nicht korrekt.';
                } elseif (strlen($newPw1) < 8 || $newPw1 !== $newPw2) {
                    $_SESSION['flash_error'] = 'Das neue Passwort muss mindestens 8 Zeichen lang sein und mit der Wiederholung übereinstimmen.';
                } else {
                    Database::query(
                        "UPDATE {$tUsers} SET password_hash = :hash WHERE id = :id",
                        ['hash' => password_hash($newPw1, PASSWORD_DEFAULT), 'id' => (int)$user['id']]
                    );
                    $_SESSION['flash_success'] = 'Dein Passwort wurde erfolgreich geändert.';
                }
            } elseif ($subAction === 'enable_2fa') {
                $setupSecret = (string)($_SESSION['setup_2fa_secret'] ?? '');
                $verifyCode  = trim((string)($_POST['totp_verify_code'] ?? ''));

                if ($setupSecret === '' || !Totp::verifyCode($setupSecret, $verifyCode)) {
                    $_SESSION['flash_error'] = 'Der eingegebene 6-stellige Code stimmt nicht überein. Bitte prüfe die Uhrzeit deines Geräts und versuche es erneut.';
                } else {
                    $backups = Totp::generateBackupCodes(8);
                    Database::query(
                        "UPDATE {$tUsers} SET totp_secret = :sec, totp_enabled = 1, totp_backup_codes = :bc WHERE id = :id",
                        [
                            'sec' => Crypto::encrypt($setupSecret),
                            'bc'  => $backups['hashes_json'],
                            'id'  => (int)$user['id'],
                        ]
                    );
                    unset($_SESSION['setup_2fa_secret']);
                    $_SESSION['fresh_2fa_backup_codes'] = $backups['plain'];
                    $_SESSION['flash_success'] = 'Zwei-Faktor-Authentifizierung (2FA) wurde erfolgreich aktiviert! Bitte sichere jetzt deine 8 Einmal-Notfallcodes.';
                }
            } elseif ($subAction === 'disable_2fa') {
                // Deaktivieren erfordert zwingend einen gültigen aktuellen 2FA-Code oder einen gültigen Backup-Code
                $confirmCode = trim((string)($_POST['disable_2fa_code'] ?? ''));
                $secretEnc   = (string)($dbUser['totp_secret'] ?? '');
                $secret      = Crypto::isEncrypted($secretEnc) ? Crypto::decrypt($secretEnc) : $secretEnc;

                $codeValid = Totp::verifyCode($secret, $confirmCode)
                    || (Totp::verifyAndConsumeBackupCode($confirmCode, $dbUser['totp_backup_codes'] ?? null) !== null);

                if (!$codeValid) {
                    $_SESSION['flash_error'] = 'Ungültiger 2FA- oder Notfallcode. Ohne gültigen zweiten Faktor kann 2FA nicht deaktiviert werden.';
                } else {
                    Database::query(
                        "UPDATE {$tUsers} SET totp_enabled = 0, totp_secret = NULL, totp_backup_codes = NULL WHERE id = :id",
                        ['id' => (int)$user['id']]
                    );
                    $_SESSION['flash_success'] = 'Die Zwei-Faktor-Authentifizierung wurde für deinen Account deaktiviert.';
                }
            }

            header('Location: admin.php?module=profile');
            exit;
        }

        $is2faEnabled = (int)($dbUser['totp_enabled'] ?? 0) === 1;
        $setupSecret = '';
        $otpUri      = '';
        if (!$is2faEnabled) {
            if (empty($_SESSION['setup_2fa_secret'])) {
                $_SESSION['setup_2fa_secret'] = Totp::generateSecret(32);
            }
            $setupSecret = (string)$_SESSION['setup_2fa_secret'];
            $siteTitle   = (string)Database::getSetting('site_title', 'ConManagement');
            $otpUri      = Totp::getProvisioningUri($setupSecret, (string)$user['email'], $siteTitle);
        }

        $remainingBackups = 0;
        if ($is2faEnabled && !empty($dbUser['totp_backup_codes'])) {
            $arr = json_decode((string)$dbUser['totp_backup_codes'], true);
            $remainingBackups = is_array($arr) ? count($arr) : 0;
        }

        ob_start();
        ?>
        <?php if (is_array($freshBackupCodes) && !empty($freshBackupCodes)): ?>
            <div class="card" style="border:2px solid #16a34a;background:#f0fdf4;">
                <div class="card-header" style="background:#dcfce7;">
                    <h2 style="color:#166534;">WICHTIG – Deine 8 persönlichen Einmal-Notfallcodes (Backup-Codes)</h2>
                </div>
                <div class="card-body">
                    <p style="margin-top:0;color:#166534;">
                        Diese Codes werden <strong>nur ein einziges Mal jetzt angezeigt</strong> und sind in der Datenbank nur verschlüsselt als Hash hinterlegt.
                        Drucke sie aus oder speichere sie in deinem Passwort-Manager! Falls du dein Smartphone verlierst, kannst du dich mit jedem dieser Codes anmelden oder 2FA zurücksetzen.
                    </p>
                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.65rem;background:#ffffff;padding:1rem;border-radius:6px;border:1px solid #86efac;font-family:monospace;font-size:1.1rem;font-weight:700;text-align:center;">
                        <?php foreach ($freshBackupCodes as $bc): ?>
                            <div style="padding:0.35rem;background:#f8fafc;border-radius:4px;border:1px solid #e2e8f0;"><?= e($bc) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid-2">
            <!-- Passwort ändern -->
            <div class="card">
                <div class="card-header"><h2>Eigenes Passwort ändern</h2></div>
                <div class="card-body">
                    <form method="post" action="admin.php?module=profile">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="sub_action" value="change_password">
                        <div class="form-group">
                            <label>Aktuelles Passwort</label>
                            <input type="password" name="old_password" autocomplete="current-password" required>
                        </div>
                        <div class="form-group">
                            <label>Neues Passwort (mindestens 8 Zeichen)</label>
                            <input type="password" name="new_password1" minlength="8" autocomplete="new-password" required>
                        </div>
                        <div class="form-group">
                            <label>Neues Passwort wiederholen</label>
                            <input type="password" name="new_password2" minlength="8" autocomplete="new-password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">💾 Passwort ändern</button>
                    </form>
                </div>
            </div>

            <!-- Zwei-Faktor-Authentifizierung (TOTP) -->
            <div class="card">
                <div class="card-header">
                    <h2>Zwei-Faktor-Authentifizierung (2FA / TOTP)</h2>
                    <?php if ($is2faEnabled): ?>
                        <span class="badge badge-green">Aktiviert</span>
                    <?php else: ?>
                        <span class="badge badge-gray">Inaktiv</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($is2faEnabled): ?>
                        <p style="margin-top:0;color:#166534;font-weight:600;">
                            Dein Account ist durch Zwei-Faktor-Authentifizierung geschützt.
                        </p>
                        <p style="font-size:0.88rem;color:#475569;">
                            Verbleibende Einmal-Notfallcodes: <strong><?= $remainingBackups ?> von 8</strong>.<br>
                            Aus Sicherheitsgründen kann 2FA im Web-Backend nur nach Eingabe eines gültigen 6-stelligen App-Codes oder eines Einmal-Notfallcodes deaktiviert werden.
                        </p>
                        <form method="post" action="admin.php?module=profile" onsubmit="return confirm('2FA wirklich deaktivieren?');" style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid #e2e8f0;">
                            <?= Auth::csrfField() ?>
                            <input type="hidden" name="sub_action" value="disable_2fa">
                            <div class="form-group">
                                <label for="disable_2fa_code">Aktueller 6-stelliger 2FA-Code (oder Einmal-Notfallcode) zur Bestätigung</label>
                                <input type="text" id="disable_2fa_code" name="disable_2fa_code" placeholder="123456 oder XXXX-XXXX" autocomplete="one-time-code" required>
                            </div>
                            <button type="submit" class="btn btn-danger btn-sm">🔓 2FA deaktivieren</button>
                        </form>
                    <?php else: ?>
                        <p style="margin-top:0;font-size:0.88rem;color:#475569;">
                            Schütze deinen Account mit einer Authenticator-App (z. B. <em>Google Authenticator, Aegis, Bitwarden, 1Password, Apple Passwörter</em>). Der QR-Code wird lokal auf deinem Server erzeugt – es werden <strong>keine externen Dienste</strong> eingebunden.
                        </p>
                        <div style="display:flex;gap:1.25rem;align-items:center;flex-wrap:wrap;margin-bottom:1rem;background:#f8fafc;padding:0.9rem;border-radius:8px;border:1px solid #e2e8f0;">
                            <div>
                                <?= QrCode::renderSvg($otpUri, 168, 2) ?>
                            </div>
                            <div style="flex:1;min-width:220px;">
                                <div style="font-weight:600;font-size:0.88rem;margin-bottom:0.35rem;color:#0f172a;">1. QR-Code mit Authenticator-App scannen</div>
                                <div style="font-size:0.82rem;color:#475569;margin-bottom:0.65rem;">Oder den Einrichtungsschlüssel manuell in der App eintragen:</div>
                                <div class="copy-input-wrap">
                                    <input type="text" id="totp_secret_input" readonly value="<?= e($setupSecret) ?>" onclick="this.select()" style="font-family:monospace;font-weight:700;font-size:0.86rem;">
                                    <button type="button" class="copy-icon-btn" onclick="copyInputToClipboard('totp_secret_input', this)" title="Schlüssel kopieren">📋</button>
                                </div>
                                <div class="help-text">Typ: Zeitbasiert (TOTP, 6 Ziffern, 30 Sek.) &middot; <a href="<?= e($otpUri) ?>">Direktlink (otpauth://)</a></div>
                            </div>
                        </div>

                        <form method="post" action="admin.php?module=profile">
                            <?= Auth::csrfField() ?>
                            <input type="hidden" name="sub_action" value="enable_2fa">
                            <div class="form-group">
                                <label for="totp_verify_code">2. Angezeigten 6-stelligen Code aus der App zur Bestätigung eingeben</label>
                                <input type="text" id="totp_verify_code" name="totp_verify_code" placeholder="123456" pattern="\d{6}" maxlength="6" autocomplete="one-time-code" required>
                            </div>
                            <button type="submit" class="btn btn-primary">🔐 2FA jetzt aktivieren & Notfallcodes erzeugen</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        AdminLayout::render('Mein Profil, Passwort & 2FA', 'profile', (string)ob_get_clean());
    }

    /**
     * System-Update & Changelog Modul
     */
    public static function handleUpdater(): void
    {
        if (!Auth::isAdmin()) {
            header('Location: admin.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::requireCsrf();
            $executed = Updater::runPendingMigrations(true);
            if (!empty($executed)) {
                $_SESSION['flash_success'] = 'Datenbank-Update erfolgreich durchgeführt für Version(en): ' . implode(', ', $executed);
            } else {
                $_SESSION['flash_success'] = 'Datenbank und System sind bereits auf dem aktuellsten Stand (v' . Version::CURRENT . ').';
            }
            header('Location: admin.php?module=updater');
            exit;
        }

        $dbVersion   = (string)Database::getSetting('db_version', '0.9.000');
        $codeVersion = Version::CURRENT;
        $isUpToDate  = version_compare($dbVersion, $codeVersion, '>=');
        $history     = array_reverse(Version::history(), true);

        ob_start();
        ?>
        <div class="card">
            <div class="card-header">
                <h2>System-Status & Datenbank-Migration</h2>
                <form method="post" action="admin.php?module=updater" style="margin:0;">
                    <?= Auth::csrfField() ?>
                    <button type="submit" class="btn btn-primary btn-sm">🔄 Migrations-Prüfung jetzt ausführen</button>
                </form>
            </div>
            <div class="card-body">
                <div class="grid-3">
                    <div style="background:#f8fafc;padding:1rem;border-radius:6px;border:1px solid #e2e8f0;">
                        <div style="font-size:0.82rem;color:#64748b;">Programm-Version (Dateien)</div>
                        <div style="font-size:1.4rem;font-weight:700;color:#0f172a;">v<?= e($codeVersion) ?></div>
                    </div>
                    <div style="background:#f8fafc;padding:1rem;border-radius:6px;border:1px solid #e2e8f0;">
                        <div style="font-size:0.82rem;color:#64748b;">Datenbank-Schema-Version</div>
                        <div style="font-size:1.4rem;font-weight:700;color:#0f172a;">v<?= e($dbVersion) ?></div>
                    </div>
                    <div style="background:#f8fafc;padding:1rem;border-radius:6px;border:1px solid #e2e8f0;">
                        <div style="font-size:0.82rem;color:#64748b;">Update-Status</div>
                        <div style="font-size:1.15rem;font-weight:700;margin-top:0.2rem;">
                            <?php if ($isUpToDate): ?>
                                <span class="badge badge-green" style="font-size:0.9rem;">✔ Aktuell & Synchron</span>
                            <?php else: ?>
                                <span class="badge badge-amber" style="font-size:0.9rem;">⚠️ Datenbank-Update ausstehend</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <p style="margin:1rem 0 0 0;font-size:0.88rem;color:#475569;">
                    Das Update-Modul erkennt Versionssprünge automatisch und führt alle dazwischenliegenden Datenbank-Migrationen chronologisch aus, ohne dass bestehende Daten verloren gehen.
                </p>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Fortlaufende Änderungshistorie (Changelog)</h2></div>
            <div class="card-body">
                <?php foreach ($history as $ver => $meta): ?>
                    <div style="border-left:4px solid var(--adm-accent);padding-left:1rem;margin-bottom:1.5rem;">
                        <div style="display:flex;align-items:center;gap:0.65rem;margin-bottom:0.35rem;">
                            <span class="badge badge-blue" style="font-size:0.85rem;">v<?= e($ver) ?></span>
                            <strong><?= e($meta['title'] ?? '') ?></strong>
                            <span style="font-size:0.82rem;color:#64748b;">(<?= e($meta['date'] ?? '') ?>)</span>
                        </div>
                        <ul style="margin:0.35rem 0 0 1.2rem;padding:0;font-size:0.9rem;color:#334155;">
                            <?php foreach ($meta['changes'] ?? [] as $chg): ?>
                                <li style="margin-bottom:0.2rem;"><?= e($chg) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        AdminLayout::render('System-Update & Änderungshistorie (v' . Version::CURRENT . ')', 'updater', (string)ob_get_clean());
    }
}
