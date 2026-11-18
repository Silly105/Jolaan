<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Config;
use App\Modules\Admin\AdminLayout;
use App\Modules\Events\EventAdminController;
use App\Modules\FormBuilder\FormBuilderController;
use App\Modules\Gdpr\GdprController;
use App\Modules\Lists\ListGeneratorController;
use App\Modules\Registration\RegistrationAdminController;
use App\Modules\Settings\SettingsController;

if (!Config::isInstalled()) {
    header('Location: install.php');
    exit;
}

Auth::initSession();

$action = (string)($_REQUEST['action'] ?? '');

// Logout
if ($action === 'logout') {
    Auth::logout();
    header('Location: admin.php');
    exit;
}

// Login-Versuch verarbeiten
$loginError = '';
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireCsrf();
    $email = trim((string)($_POST['email'] ?? ''));
    $pass  = (string)($_POST['password'] ?? '');
    if (Auth::login($email, $pass)) {
        header('Location: admin.php');
        exit;
    }
    $loginError = 'E-Mail-Adresse oder Passwort ist ungültig.';
}

// Falls nicht eingeloggt: Login-Maske anzeigen
if (!Auth::isOrga()) {
    AdminLayout::renderLogin($loginError);
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
    case 'dashboard':
    default:
        EventAdminController::handleDashboard();
        break;
}
