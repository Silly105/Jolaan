<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\I18n;

// Sprachumschalter-Links bauen
$currentParams = $_GET;
$currentParams['lang'] = 'de';
$langUrlDe = '?' . http_build_query($currentParams);
$currentParams['lang'] = 'en';
$langUrlEn = '?' . http_build_query($currentParams);
?>
<!DOCTYPE html>
<html lang="<?= e(I18n::lang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> – <?= e($siteTitle) ?></title>
    <?php if (!empty($faviconUrl)): ?>
        <link rel="icon" href="<?= e($faviconUrl) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= e($themeCssUrl) ?>">
</head>
<body class="theme-<?= e($activeTheme) ?>">
    <header class="site-header">
        <div class="container header-inner">
            <div class="brand">
                <a href="index.php" class="brand-link">
                    <?php if ($headerLogo !== ''): ?>
                        <img src="<?= e($headerLogo) ?>" alt="<?= e($siteTitle) ?>" class="header-logo">
                    <?php else: ?>
                        <span class="brand-title"><?= e($siteTitle) ?></span>
                        <?php if ($siteSubtitle !== ''): ?>
                            <span class="brand-subtitle"><?= e($siteSubtitle) ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </a>
            </div>
            <div class="header-controls">
                <?php if ($allowEnglish): ?>
                    <div class="lang-switch" aria-label="Language">
                        <a href="<?= e($langUrlDe) ?>" class="lang-pill <?= I18n::lang() === 'de' ? 'active' : '' ?>">DE</a>
                        <a href="<?= e($langUrlEn) ?>" class="lang-pill <?= I18n::lang() === 'en' ? 'active' : '' ?>">EN</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <nav class="top-nav">
            <div class="container nav-inner">
                <ul class="nav-links">
                    <li><a href="index.php"><?= e(I18n::t('nav.home')) ?></a></li>
                    <?php if ($mainSiteUrl !== ''): ?>
                        <li><a href="<?= e($mainSiteUrl) ?>"><?= e(I18n::t('nav.main_site')) ?></a></li>
                    <?php endif; ?>
                    <?php if ($forumUrl !== ''): ?>
                        <li><a href="<?= e($forumUrl) ?>"><?= e(I18n::t('nav.forum')) ?></a></li>
                    <?php endif; ?>
                </ul>
                <div class="nav-orga">
                    <?php if (Auth::isOrga()): ?>
                        <a href="admin.php" class="nav-badge"><?= e(I18n::t('nav.admin_area')) ?></a>
                    <?php else: ?>
                        <a href="admin.php" class="nav-login"><?= e(I18n::t('nav.orga_login')) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

    <main class="container main-layout">
        <?php if ($headerText !== ''): ?>
            <section class="event-header-banner">
                <?= nl2br(e($headerText)) ?>
            </section>
        <?php endif; ?>

        <article class="content-card">
            <header class="card-header">
                <h1><?= e($title) ?></h1>
            </header>
            <div class="card-body">
                <?= $contentHtml ?>
            </div>
        </article>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <span>&copy; <?= date('Y') ?> <?= e($siteTitle) ?></span>
            <span class="footer-links">
                <?php if ($privacyUrl !== ''): ?>
                    <a href="<?= e($privacyUrl) ?>"><?= e(I18n::t('nav.privacy')) ?></a>
                <?php endif; ?>
                <?php if ($imprintUrl !== ''): ?>
                    <a href="<?= e($imprintUrl) ?>"><?= e(I18n::t('nav.imprint')) ?></a>
                <?php endif; ?>
            </span>
        </div>
    </footer>
</body>
</html>
