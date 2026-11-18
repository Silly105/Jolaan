<?php
declare(strict_types=1);

namespace App\Modules\Settings;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Theme;
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

            $keys = [
                'site_title', 'site_subtitle', 'default_theme',
                'index_intro_de', 'index_intro_en',
                'main_site_url', 'forum_url', 'privacy_url', 'imprint_url',
                'mail_from_address', 'mail_from_name', 'mail_transport',
                'smtp_host', 'smtp_port', 'smtp_user', 'smtp_encryption',
            ];
            foreach ($keys as $k) {
                if (isset($_POST[$k])) {
                    Database::setSetting($k, trim((string)$_POST[$k]));
                }
            }
            if (!empty($_POST['smtp_pass'])) {
                Database::setSetting('smtp_pass', (string)$_POST['smtp_pass']);
            }

            $uploadErrors = [];

            // Optionales globales Seitenlogo hochladen (mit Sanity-Check)
            if (!empty($_FILES['site_logo_file']['name'])) {
                $check = UploadValidator::validateAndStoreImage(
                    $_FILES['site_logo_file'],
                    'site_logo',
                    2097152, // max. 2 MB
                    2500,    // max. 2500 px Breite
                    800,     // max. 800 px Höhe
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
                    524288, // max. 512 KB
                    512,    // max. 512 px Breite
                    512,    // max. 512 px Höhe
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

        $themes      = Theme::availableThemes();
        $curTheme    = (string)Database::getSetting('default_theme', 'western-seal');
        $siteLogo    = (string)Database::getSetting('site_logo', '');
        $siteFavicon = (string)Database::getSetting('site_favicon', '');

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $currentDomainUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'deine-domain.de');

        ob_start();
        ?>
        <form method="post" action="admin.php?module=settings" enctype="multipart/form-data">
            <?= Auth::csrfField() ?>

            <div class="card">
                <div class="card-header"><h2>🎨 Globales Theme, Favicon & Seiten-Header</h2></div>
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

                    <div class="grid-3">
                        <div class="form-group">
                            <label for="default_theme">Globales Standard-Theme der Seite (pro Veranstaltung überschreibbar)</label>
                            <select id="default_theme" name="default_theme">
                                <?php foreach ($themes as $slug => $tMeta): ?>
                                    <option value="<?= e($slug) ?>" <?= $curTheme === $slug ? 'selected' : '' ?>>
                                        <?= e($tMeta['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="help-text">Gilt für Startseite, Orga-Backend und alle Veranstaltungen ohne eigenes Theme.</div>
                        </div>
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
                            <label>Link zur Hauptseite (optional)</label>
                            <input type="url" name="main_site_url" value="<?= e(Database::getSetting('main_site_url', '')) ?>" placeholder="<?= e($currentDomainUrl) ?>">
                        </div>
                        <div class="form-group">
                            <label>Link zum Forum (optional)</label>
                            <input type="url" name="forum_url" value="<?= e(Database::getSetting('forum_url', '')) ?>" placeholder="<?= e($currentDomainUrl) ?>/forum">
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
                <div class="card-header"><h2>📧 E-Mail-Versand & SMTP</h2></div>
                <div class="card-body">
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
                                <option value="mail" <?= Database::getSetting('mail_transport', 'mail') === 'mail' ? 'selected' : '' ?>>Standard PHP-Mail (Shared Hosting Standard)</option>
                                <option value="smtp" <?= Database::getSetting('mail_transport', 'mail') === 'smtp' ? 'selected' : '' ?>>Eigener SMTP-Server</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid-3">
                        <div class="form-group">
                            <label>SMTP-Host (falls SMTP)</label>
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
                            <label>SMTP-Passwort (leer lassen, um bestehendes beizubehalten)</label>
                            <input type="password" name="smtp_pass" placeholder="••••••••" autocomplete="new-password">
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="font-size:1rem;padding:0.65rem 1.5rem;">💾 Einstellungen speichern</button>
        </form>
        <?php
        AdminLayout::render('Globales Design & Systemeinstellungen', 'settings', (string)ob_get_clean());
    }

    public static function handleMailTemplates(): void
    {
        if (!Auth::isAdmin()) {
            header('Location: admin.php');
            exit;
        }

        $templates = [
            'doi'      => '1. Double-Opt-In Bestätigungslink (direkt nach Absenden des Formulars)',
            'confirm'  => '2. Anmeldebestätigung durch die Orga',
            'reminder' => '3. Zahlungserinnerung',
            'paid'     => '4. Geldeingangsbestätigung',
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
            💡 <strong>Verfügbare Platzhalter:</strong> <code>{vorname}</code>, <code>{nachname}</code>, <code>{conname}</code>, <code>{condatum}</code>, <code>{gruppe}</code>, <code>{betrag}</code>, <code>{link}</code> (HTML ist erlaubt).
        </div>

        <form method="post" action="admin.php?module=mailtemplates">
            <?= Auth::csrfField() ?>
            <?php foreach ($templates as $tKey => $tTitle): ?>
                <div class="card">
                    <div class="card-header"><h2>✉️ <?= e($tTitle) ?></h2></div>
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
                        $_SESSION['flash_success'] = "Benutzer „{$name}“ ({$role}) wurde angelegt.";
                    } catch (\Throwable) {
                        $_SESSION['flash_error'] = 'Diese E-Mail-Adresse ist bereits vergeben.';
                    }
                }
            } elseif ($subAction === 'update_user') {
                $uid   = (int)($_POST['user_id'] ?? 0);
                $role  = ($_POST['role'] ?? 'orga') === 'admin' ? 'admin' : 'orga';
                $act   = isset($_POST['is_active']) ? 1 : 0;
                $newPw = trim((string)($_POST['reset_user_password'] ?? ''));

                // Verhindern, dass man sich selbst aussperrt
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

        $users = Database::fetchAll("SELECT id, email, name, role, is_active, created_at FROM {$tUsers} ORDER BY role ASC, name ASC");
        $meId  = (int)(Auth::user()['id'] ?? 0);

        ob_start();
        ?>
        <div class="card">
            <div class="card-header"><h2>🔑 Orga- & Administrator-Konten</h2></div>
            <div class="card-body table-responsive">
                <p style="margin-top:0;font-size:0.88rem;color:#475569;">
                    <strong>Rollen-Konzept</strong> –
                    <code>Admin</code> hat Vollzugriff (Cons, Formular-Baukasten, DSGVO, Systemeinstellungen).
                    <code>Orga / SL</code> kann sich im Backend anmelden, Teilnehmerlisten/Filter/Druckansichten einsehen sowie Zahlungen und Anmeldungen bearbeiten.
                </p>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>E-Mail</th>
                            <th>Rolle</th>
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
                                            <option value="orga" <?= $u['role'] === 'orga' ? 'selected' : '' ?>>Orga / SL</option>
                                        </select>
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
            <div class="card-header"><h2>➕ Neuen Backend-Benutzer anlegen</h2></div>
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
                                <option value="orga">Orga / SL (Teilnehmer & Listen verwalten)</option>
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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::requireCsrf();
            $oldPw  = (string)($_POST['old_password'] ?? '');
            $newPw1 = (string)($_POST['new_password1'] ?? '');
            $newPw2 = (string)($_POST['new_password2'] ?? '');

            $tUsers = Database::table('users');
            $dbUser = Database::fetchOne("SELECT password_hash FROM {$tUsers} WHERE id = :id", ['id' => (int)$user['id']]);

            if (!$dbUser || !password_verify($oldPw, (string)$dbUser['password_hash'])) {
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
            header('Location: admin.php?module=profile');
            exit;
        }

        ob_start();
        ?>
        <div class="card" style="max-width:520px;">
            <div class="card-header"><h2>🔒 Eigenes Passwort ändern</h2></div>
            <div class="card-body">
                <form method="post" action="admin.php?module=profile">
                    <?= Auth::csrfField() ?>
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
        <?php
        AdminLayout::render('Mein Profil & Passwort', 'profile', (string)ob_get_clean());
    }

    /**
     * System-Update & Changelog Modul: Zeigt installierte DB-Version, Code-Version und die komplette Änderungshistorie
     */
    public static function handleUpdater(): void
    {
        if (!Auth::isAdmin()) {
            header('Location: admin.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::requireCsrf();
            $executed = Updater::runPendingMigrations();
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
                <h2>🔄 System-Status & Datenbank-Migration</h2>
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
            <div class="card-header"><h2>📜 Fortlaufende Änderungshistorie (Changelog)</h2></div>
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
