<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Theme;
use App\Core\Version;
use App\Modules\Events\EventRepository;

class AdminLayout
{
    public static function renderLogin(string $error = '', bool $show2faStep = false): void
    {
        $siteTitle  = (string)Database::getSetting('site_title', 'Con-Management');
        $faviconUrl = (string)Database::getSetting('site_favicon', '');
        $palette    = Theme::getAdminPalette();
        ?>
        <!DOCTYPE html>
        <html lang="de">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Orga-Login – <?= e($siteTitle) ?></title>
            <?php if ($faviconUrl !== ''): ?>
                <link rel="icon" href="<?= e($faviconUrl) ?>">
            <?php endif; ?>
            <style>
                :root {
                    --adm-sidebar: <?= e($palette['sidebar_bg']) ?>;
                    --adm-sidebar-dark: <?= e($palette['sidebar_dark']) ?>;
                    --adm-accent: <?= e($palette['accent']) ?>;
                    --adm-accent-hover: <?= e($palette['accent_hover']) ?>;
                    --adm-accent-border: <?= e($palette['accent_border']) ?>;
                }
                *, *::before, *::after { box-sizing: border-box; }
                body {
                    margin: 0;
                    font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
                    background: var(--adm-sidebar);
                    color: #1e293b;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 1.5rem;
                }
                .login-card {
                    width: 100%;
                    max-width: 420px;
                    background: #ffffff;
                    border-radius: 10px;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
                    overflow: hidden;
                }
                .login-header {
                    background: var(--adm-sidebar-dark);
                    color: #ffffff;
                    padding: 1.5rem 1.75rem;
                    border-bottom: 4px solid var(--adm-accent-border);
                }
                .login-header h1 { margin: 0; font-size: 1.3rem; }
                .login-header p { margin: 0.25rem 0 0 0; opacity: 0.85; font-size: 0.88rem; }
                .login-body { padding: 1.75rem; }
                .form-group { margin-bottom: 1.1rem; }
                label { display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.35rem; }
                input {
                    width: 100%;
                    padding: 0.6rem 0.8rem;
                    border: 1px solid #cbd5e1;
                    border-radius: 6px;
                    font-size: 0.95rem;
                }
                input:focus { outline: none; border-color: var(--adm-accent); box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1); }
                .btn {
                    width: 100%;
                    background: var(--adm-accent);
                    color: #ffffff;
                    border: none;
                    padding: 0.7rem;
                    font-size: 1rem;
                    font-weight: 600;
                    border-radius: 6px;
                    cursor: pointer;
                }
                .btn:hover { background: var(--adm-accent-hover); }
                .err {
                    background: #fef2f2;
                    border: 1px solid #f87171;
                    color: #991b1b;
                    padding: 0.75rem 1rem;
                    border-radius: 6px;
                    margin-bottom: 1rem;
                    font-size: 0.9rem;
                }
                .back-link { display: block; text-align: center; margin-top: 1.25rem; font-size: 0.88rem; color: var(--adm-accent); text-decoration: none; }
            </style>
        </head>
        <body>
            <div class="login-card">
                <div class="login-header">
                    <h1><?= e($siteTitle) ?></h1>
                    <p>Orga- & Administrations-Login (v<?= e(Version::CURRENT) ?>)</p>
                </div>
                <div class="login-body">
                    <?php if ($error !== ''): ?>
                        <div class="err"><?= e($error) ?></div>
                    <?php endif; ?>

                    <?php if ($show2faStep): ?>
                        <form method="post" action="admin.php?action=verify_2fa">
                            <?= Auth::csrfField() ?>
                            <div class="form-group">
                                <label for="totp_code">Zwei-Faktor-Code (6 Ziffern) oder Einmal-Notfallcode</label>
                                <input type="text" id="totp_code" name="totp_code" placeholder="123456 oder XXXX-XXXX" autocomplete="one-time-code" required autofocus style="font-size:1.15rem;letter-spacing:0.12em;text-align:center;">
                            </div>
                            <button type="submit" class="btn">Code bestätigen & Anmelden</button>
                        </form>
                        <a href="admin.php?action=logout" class="back-link">&larr; Anmeldung abbrechen</a>
                    <?php else: ?>
                        <form method="post" action="admin.php?action=login">
                            <?= Auth::csrfField() ?>
                            <div class="form-group">
                                <label for="email">E-Mail-Adresse</label>
                                <input type="email" id="email" name="email" autocomplete="username" required autofocus>
                            </div>
                            <div class="form-group">
                                <label for="password">Passwort</label>
                                <input type="password" id="password" name="password" autocomplete="current-password" required>
                            </div>
                            <button type="submit" class="btn">Anmelden</button>
                        </form>
                        <a href="index.php" class="back-link">&larr; Zurück zur öffentlichen Startseite</a>
                    <?php endif; ?>
                </div>
            </div>
        </body>
        </html>
        <?php
    }

    public static function render(string $title, string $activeModule, string $contentHtml, ?int $activeEventId = null): void
    {
        $user       = Auth::user();
        $isAdmin    = Auth::isAdmin();
        $siteTitle  = (string)Database::getSetting('site_title', 'Con-Management');
        $faviconUrl = (string)Database::getSetting('site_favicon', '');
        $palette    = Theme::getAdminPalette();

        // Dauerhaft ausgewählte Veranstaltung aus Parameter oder Session ermitteln
        if ($activeEventId !== null && $activeEventId > 0) {
            $_SESSION['active_event_id'] = $activeEventId;
        }
        $sessionEventId = (int)($_SESSION['active_event_id'] ?? 0);
        $activeEvent    = $sessionEventId > 0 ? EventRepository::find($sessionEventId) : null;
        if (!$activeEvent) {
            unset($_SESSION['active_event_id']);
            $sessionEventId = 0;
        }

        // Unterscheiden, ob formbuilder für eine Veranstaltung oder global (Feldvorlagen, event_id=0) geöffnet ist
        $isGlobalFormTemplates = ($activeModule === 'formbuilder' && isset($_GET['event_id']) && (int)$_GET['event_id'] === 0);
        $isNewEventPage        = ($activeModule === 'events' && ($_GET['action'] ?? '') === 'new');

        $flashSuccess = $_SESSION['flash_success'] ?? '';
        $flashError   = $_SESSION['flash_error'] ?? '';
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        ?>
        <!DOCTYPE html>
        <html lang="de">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= e($title) ?> – Orga-Backend</title>
            <?php if ($faviconUrl !== ''): ?>
                <link rel="icon" href="<?= e($faviconUrl) ?>">
            <?php endif; ?>
            <style>
                :root {
                    --adm-sidebar: <?= e($palette['sidebar_bg']) ?>;
                    --adm-sidebar-dark: <?= e($palette['sidebar_dark']) ?>;
                    --adm-accent: <?= e($palette['accent']) ?>;
                    --adm-accent-hover: <?= e($palette['accent_hover']) ?>;
                    --adm-accent-border: <?= e($palette['accent_border']) ?>;
                    --adm-accent-light: <?= e($palette['accent_light']) ?>;
                    --adm-body-bg: <?= e($palette['body_bg']) ?>;
                }
                *, *::before, *::after { box-sizing: border-box; }
                body {
                    margin: 0;
                    font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
                    font-size: 15px;
                    line-height: 1.5;
                    background: var(--adm-body-bg);
                    color: #0f172a;
                    display: flex;
                    min-height: 100vh;
                }
                /* Sidebar */
                .sidebar {
                    width: 260px;
                    background: var(--adm-sidebar);
                    color: #e2e8f0;
                    display: flex;
                    flex-direction: column;
                    flex-shrink: 0;
                }
                .sidebar-brand {
                    padding: 1.25rem 1.35rem;
                    background: var(--adm-sidebar-dark);
                    border-bottom: 3px solid var(--adm-accent-border);
                }
                .sidebar-brand a.brand-title { color: #ffffff; text-decoration: none; font-weight: 700; font-size: 1.15rem; display: block; }
                .sidebar-brand span.brand-sub { font-size: 0.78rem; color: #cbd5e1; display: block; margin-top: 0.15rem; }
                .sidebar-brand .version-pill {
                    display: inline-block;
                    margin-top: 0.45rem;
                    padding: 0.12rem 0.55rem;
                    font-size: 0.72rem;
                    font-weight: 700;
                    letter-spacing: 0.03em;
                    background: rgba(255, 255, 255, 0.12);
                    color: var(--adm-accent-light);
                    border-radius: 999px;
                    text-decoration: none;
                }
                .sidebar-brand a.version-pill:hover {
                    background: rgba(255, 255, 255, 0.22);
                    color: #ffffff;
                }
                .nav-section {
                    padding: 0.45rem 1.35rem;
                    margin: 0.55rem 0 0.25rem;
                    font-size: 0.74rem;
                    text-transform: uppercase;
                    letter-spacing: 0.06em;
                    color: #94a3b8;
                    font-weight: 700;
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                    border-top: 1px solid rgba(255, 255, 255, 0.14);
                    border-bottom: 1px solid rgba(255, 255, 255, 0.14);
                    background: rgba(0, 0, 0, 0.12);
                }
                .nav-section.active-event-heading {
                    color: var(--adm-accent-light);
                    border-top-color: rgba(255, 255, 255, 0.2);
                    border-bottom-color: rgba(255, 255, 255, 0.2);
                    background: rgba(0, 0, 0, 0.2);
                }
                .side-nav { list-style: none; margin: 0; padding: 0; }
                .side-nav li a {
                    display: flex;
                    align-items: center;
                    padding: 0.58rem 1.35rem;
                    color: #cbd5e1;
                    text-decoration: none;
                    font-weight: 500;
                    border-left: 3px solid transparent;
                }
                .side-nav li a:hover { background: rgba(255,255,255,0.06); color: #ffffff; }
                .side-nav li a.active {
                    background: rgba(255, 255, 255, 0.14);
                    color: #ffffff;
                    border-left-color: var(--adm-accent-light);
                    font-weight: 600;
                }
                .sidebar-footer {
                    margin-top: auto;
                    padding: 1rem 1.35rem;
                    background: var(--adm-sidebar-dark);
                    font-size: 0.84rem;
                    border-top: 1px solid rgba(255,255,255,0.08);
                }
                .sidebar-footer a { color: #fca5a5; text-decoration: none; font-weight: 600; }
                /* Main Area */
                .main-area {
                    flex: 1;
                    display: flex;
                    flex-direction: column;
                    min-width: 0;
                }
                .topbar {
                    background: #ffffff;
                    border-bottom: 1px solid #cbd5e1;
                    padding: 0.9rem 1.75rem;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 1rem;
                }
                .topbar h1 { margin: 0; font-size: 1.3rem; color: #1e293b; }
                .content-wrap { padding: 1.75rem; max-width: 1400px; width: 100%; }
                /* Cards & Tables */
                .card {
                    background: #ffffff;
                    border: 1px solid #cbd5e1;
                    border-radius: 8px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
                    margin-bottom: 1.5rem;
                    overflow: hidden;
                }
                .card-header {
                    padding: 0.95rem 1.35rem;
                    background: #f8fafc;
                    border-bottom: 1px solid #e2e8f0;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 0.75rem;
                }
                .card-header h2 { margin: 0; font-size: 1.08rem; color: #0f172a; }
                .card-body { padding: 1.35rem; }
                .table-responsive { overflow-x: auto; }
                table.data-table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 0.9rem;
                }
                table.data-table th, table.data-table td {
                    padding: 0.65rem 0.85rem;
                    border-bottom: 1px solid #e2e8f0;
                    text-align: left;
                    vertical-align: middle;
                }
                table.data-table th {
                    background: #f8fafc;
                    font-weight: 600;
                    color: #334155;
                    white-space: nowrap;
                }
                table.data-table tr:hover td { background: #f8fafc; }
                /* Forms & Buttons */
                .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
                .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; }
                .form-group { margin-bottom: 1rem; }
                label { display: block; font-weight: 600; font-size: 0.88rem; margin-bottom: 0.3rem; color: #1e293b; }
                .help-text { font-size: 0.8rem; color: #64748b; margin-top: 0.2rem; }
                input[type="text"], input[type="email"], input[type="number"], input[type="date"], input[type="datetime-local"], input[type="password"], input[type="url"], select, textarea {
                    width: 100%;
                    padding: 0.5rem 0.7rem;
                    border: 1px solid #cbd5e1;
                    border-radius: 6px;
                    font-size: 0.92rem;
                    font-family: inherit;
                    background: #fff;
                }
                input:focus, select:focus, textarea:focus {
                    outline: none;
                    border-color: var(--adm-accent);
                    box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.08);
                }
                .sort-input {
                    min-width: 95px;
                    width: 95px !important;
                }
                /* Copy Input Wrapper (1-Klick Kopieren Symbol im Textfeld) */
                .copy-input-wrap {
                    position: relative;
                    display: flex;
                    align-items: center;
                }
                .copy-input-wrap input {
                    padding-right: 2.5rem;
                }
                .copy-icon-btn {
                    position: absolute;
                    right: 0.35rem;
                    background: #f1f5f9;
                    border: 1px solid #cbd5e1;
                    border-radius: 4px;
                    padding: 0.25rem 0.45rem;
                    cursor: pointer;
                    font-size: 0.88rem;
                    line-height: 1;
                    color: #334155;
                    transition: background 0.15s;
                }
                .copy-icon-btn:hover {
                    background: #e2e8f0;
                    color: #0f172a;
                }
                .btn {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.35rem;
                    padding: 0.48rem 0.95rem;
                    font-size: 0.88rem;
                    font-weight: 600;
                    border-radius: 6px;
                    border: 1px solid transparent;
                    cursor: pointer;
                    text-decoration: none;
                }
                .btn-sm { padding: 0.3rem 0.65rem; font-size: 0.8rem; }
                .btn-primary { background: var(--adm-accent); color: #fff; }
                .btn-primary:hover { background: var(--adm-accent-hover); }
                .btn-secondary { background: #fff; color: #334155; border-color: #cbd5e1; }
                .btn-secondary:hover { background: #f1f5f9; }
                .btn-danger { background: #dc2626; color: #fff; }
                .btn-danger:hover { background: #b91c1c; }
                .btn-warning { background: #d97706; color: #fff; }
                .badge {
                    display: inline-block;
                    padding: 0.15rem 0.55rem;
                    border-radius: 999px;
                    font-size: 0.76rem;
                    font-weight: 600;
                }
                .badge-green { background: #dcfce7; color: #166534; }
                .badge-red { background: #fee2e2; color: #991b1b; }
                .badge-amber { background: #fef3c7; color: #92400e; }
                .badge-orange { background: #ffedd5; color: #c2410c; border: 1px solid #fdba74; }
                .badge-blue { background: #dbeafe; color: #1e40af; }
                .badge-gray { background: #f1f5f9; color: #475569; }
                .alert {
                    padding: 0.85rem 1.15rem;
                    border-radius: 6px;
                    margin-bottom: 1.25rem;
                    font-weight: 500;
                }
                .alert-success { background: #dcfce7; border: 1px solid #86efac; color: #166534; }
                .alert-danger { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
                @media (max-width: 900px) {
                    body { flex-direction: column; }
                    .sidebar { width: 100%; }
                    .grid-2, .grid-3 { grid-template-columns: 1fr; }
                }
            </style>
        </head>
        <body>
            <aside class="sidebar">
                <div class="sidebar-brand">
                    <a href="admin.php" class="brand-title"><?= e($siteTitle) ?></a>
                    <span class="brand-sub">Orga- & Admin-Backend</span>
                    <?php if ($isAdmin): ?>
                        <a href="admin.php?module=updater" class="version-pill" title="System-Version & Änderungshistorie anzeigen">
                            Version <?= e(Version::CURRENT) ?>
                        </a>
                    <?php else: ?>
                        <span class="version-pill">Version <?= e(Version::CURRENT) ?></span>
                    <?php endif; ?>
                </div>

                <!-- 1. Hilfe & Handbuch ganz oben vor der ersten Überschrift -->
                <ul class="side-nav" style="margin-top:0.5rem;">
                    <li><a href="admin.php?module=help" class="<?= $activeModule === 'help' ? 'active' : '' ?>">Hilfe & Handbuch</a></li>
                </ul>

                <!-- 2. Veranstaltungen -->
                <div class="nav-section">Veranstaltungen</div>
                <ul class="side-nav">
                    <li><a href="admin.php?module=dashboard" class="<?= $activeModule === 'dashboard' ? 'active' : '' ?>">Übersicht</a></li>
                </ul>

                <!-- 3. Aktuell ausgewählte Veranstaltung (dauerhaft sichtbar, sobald ausgewählt) -->
                <?php if ($activeEvent):
                    $aeId = (int)$activeEvent['id'];
                    $aeBilingual = (int)$activeEvent['is_bilingual'] === 1;
                ?>
                    <div class="nav-section active-event-heading"><?= e($activeEvent['title']) ?></div>
                    <ul class="side-nav">
                        <li><a href="admin.php?module=registrations&event_id=<?= $aeId ?>" class="<?= $activeModule === 'registrations' ? 'active' : '' ?>">Anmeldeverwaltung</a></li>
                        <li><a href="admin.php?module=lists&event_id=<?= $aeId ?>" class="<?= $activeModule === 'lists' ? 'active' : '' ?>">Listen</a></li>
                        <?php if ($isAdmin): ?>
                            <li><a href="admin.php?module=events&action=edit&id=<?= $aeId ?>" class="<?= ($activeModule === 'events' && !$isNewEventPage) ? 'active' : '' ?>">Con-Einstellungen</a></li>
                        <?php endif; ?>
                        <li><a href="admin.php?module=groups&event_id=<?= $aeId ?>" class="<?= $activeModule === 'groups' ? 'active' : '' ?>">Verfügbare Plätze</a></li>
                        <?php if ($isAdmin): ?>
                            <li><a href="admin.php?module=formbuilder&event_id=<?= $aeId ?>" class="<?= ($activeModule === 'formbuilder' && !$isGlobalFormTemplates) ? 'active' : '' ?>">Abfragefelder</a></li>
                            <?php if ($aeBilingual): ?>
                                <li><a href="admin.php?module=translations&event_id=<?= $aeId ?>" class="<?= $activeModule === 'translations' ? 'active' : '' ?>">Übersetzungstabelle</a></li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>

                <!-- 4. Administration & Vorlagen -->
                <?php if ($isAdmin): ?>
                    <div class="nav-section">Administration & Vorlagen</div>
                    <ul class="side-nav">
                        <li><a href="admin.php?module=events&action=new" class="<?= $isNewEventPage ? 'active' : '' ?>">Neue Veranstaltung</a></li>
                        <li><a href="admin.php?module=formbuilder&event_id=0" class="<?= $isGlobalFormTemplates ? 'active' : '' ?>">Feldvorlagen</a></li>
                        <li><a href="admin.php?module=gdpr" class="<?= $activeModule === 'gdpr' ? 'active' : '' ?>">DSGVO-Center</a></li>
                        <li><a href="admin.php?module=mailtemplates" class="<?= $activeModule === 'mailtemplates' ? 'active' : '' ?>">E-Mail-Vorlagen</a></li>
                        <li><a href="admin.php?module=users" class="<?= $activeModule === 'users' ? 'active' : '' ?>">Benutzer & Rollen</a></li>
                        <li><a href="admin.php?module=settings" class="<?= $activeModule === 'settings' ? 'active' : '' ?>">Seiteneinstellungen</a></li>
                        <li><a href="admin.php?module=updater" class="<?= $activeModule === 'updater' ? 'active' : '' ?>">Update & Changelog</a></li>
                    </ul>
                <?php endif; ?>

                <!-- 5. Frontend -->
                <div class="nav-section">Frontend</div>
                <ul class="side-nav">
                    <li><a href="index.php" target="_blank">Öffentliche Startseite &nearr;</a></li>
                </ul>

                <div class="sidebar-footer">
                    <div>Angemeldet als <strong><?= e($user['name'] ?? '') ?></strong> (<?= e(Auth::roleLabel($user['role'] ?? 'orga')) ?>)</div>
                    <div style="margin-top:0.35rem;">
                        <a href="admin.php?module=profile" style="color:#93c5fd;margin-right:0.75rem;">Passwort & 2FA</a>
                        <a href="admin.php?action=logout">Abmelden</a>
                    </div>
                </div>
            </aside>

            <div class="main-area">
                <header class="topbar">
                    <h1><?= e($title) ?></h1>
                </header>
                <main class="content-wrap">
                    <?php if ($flashSuccess !== ''): ?>
                        <div class="alert alert-success"><?= e($flashSuccess) ?></div>
                    <?php endif; ?>
                    <?php if ($flashError !== ''): ?>
                        <div class="alert alert-danger"><?= e($flashError) ?></div>
                    <?php endif; ?>
                    <?= $contentHtml ?>
                </main>
            </div>
            <script>
            function copyInputToClipboard(inputId, btnEl) {
                const input = document.getElementById(inputId);
                if (!input) return;
                input.select();
                input.setSelectionRange(0, 99999);
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(input.value);
                } else {
                    document.execCommand('copy');
                }
                const orig = btnEl.textContent;
                btnEl.textContent = '✔';
                setTimeout(() => { btnEl.textContent = orig; }, 1400);
            }
            </script>
        </body>
        </html>
        <?php
    }

    /**
     * Obere Subnav-Buttons auf den veranstaltungsbezogenen Seiten wurden auf Wunsch entfernt
     * (Navigation erfolgt übersichtlich links unter dem Veranstaltungsnamen).
     */
    public static function renderEventSubnav(array $event, string $activeModule): string
    {
        return '';
    }
}
