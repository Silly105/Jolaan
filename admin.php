<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Config;
use App\Core\Updater;
use App\Modules\Admin\AdminLayout;
use App\Modules\Events\EventAdminController;
use App\Modules\FormBuilder\FormBuilderController;
use App\Modules\Gdpr\GdprController;
use App\Modules\Help\HelpController;
use App\Modules\Lists\ListGeneratorController;
use App\Modules\Registration\RegistrationAdminController;
use App\Modules\Settings\SettingsController;

if (!Config::isInstalled()) {
    header('Location: install.php');
    exit;
}

Auth::initSession();

// Automatische Datenbank-Migrationen bei Versionssprüngen ausführen
Updater::runPendingMigrations();

$action = (string)($_REQUEST['action'] ?? '');

// Logout
if ($action === 'logout') {
    Auth::logout();
    header('Location: admin.php');
    exit;
}

// Schritt 1: Login-Versuch (E-Mail & Passwort)
$loginError = '';
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireCsrf();
    $email = trim((string)($_POST['email'] ?? ''));
    $pass  = (string)($_POST['password'] ?? '');
    $res   = Auth::attemptLogin($email, $pass);

    if ($res === 'ok') {
        header('Location: admin.php');
        exit;
    } elseif ($res === '2fa') {
        AdminLayout::renderLogin('', true);
        exit;
    } elseif ($res === 'locked') {
        $loginError = 'Zu viele fehlgeschlagene Anmeldeversuche. Bitte warte 15 Minuten und versuche es dann erneut.';
    } else {
        $loginError = 'E-Mail-Adresse oder Passwort ist ungültig.';
    }
}

// Schritt 2: 2FA-Code oder Einmal-Notfallcode prüfen
if ($action === 'verify_2fa' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireCsrf();
    $code = trim((string)($_POST['totp_code'] ?? ''));
    $res2fa = Auth::verifyPending2faStatus($code);
    if ($res2fa === 'ok') {
        header('Location: admin.php');
        exit;
    } elseif ($res2fa === 'locked') {
        AdminLayout::renderLogin('Zu viele falsche 2FA-Codes eingegeben. Aus Sicherheitsgründen wurde der Vorgang für 15 Minuten gesperrt.', false);
        exit;
    }
    AdminLayout::renderLogin('Ungültiger 2FA- oder Notfallcode. Bitte versuche es erneut.', true);
    exit;
}

// Falls noch 2FA-Schritt offen oder nicht eingeloggt: Login-Maske anzeigen
if (!Auth::isOrga()) {
    AdminLayout::renderLogin($loginError, Auth::hasPending2fa());
    exit;
}

$module = (string)($_GET['module'] ?? 'dashboard');

switch ($module) {
    case 'events':
        EventAdminController::handleEvents();
        break;
    case 'groups':
        EventAdminController::handleGroups();
        break;
    case 'formbuilder':
        FormBuilderController::handleFormBuilder();
        break;
    case 'translations':
        FormBuilderController::handleTranslations();
        break;
    case 'registrations':
        RegistrationAdminController::handle();
        break;
    case 'lists':
        ListGeneratorController::handle();
        break;
    case 'gdpr':
        GdprController::handle();
        break;
    case 'settings':
        SettingsController::handleSettings();
        break;
    case 'mailtemplates':
        SettingsController::handleMailTemplates();
        break;
    case 'users':
        SettingsController::handleUsers();
        break;
    case 'profile':
        SettingsController::handleProfile();
        break;
    case 'updater':
        SettingsController::handleUpdater();
        break;
    case 'help':
        HelpController::handle();
        break;
    case 'dashboard':
    default:
        EventAdminController::handleDashboard();
        break;
}
