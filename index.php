<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;
use App\Core\I18n;
use App\Core\Theme;
use App\Core\Updater;
use App\Modules\Events\EventRepository;

if (!Config::isInstalled()) {
    header('Location: install.php');
    exit;
}

Updater::runPendingMigrations();
I18n::init(true);

$events = EventRepository::upcomingForIndex();
$today = date('Y-m-d');

$runningEvents  = [];
$upcomingEvents = [];

foreach ($events as $ev) {
    $ev['groups'] = EventRepository::getGroupsWithSeats((int)$ev['id']);
    if ($ev['start_date'] <= $today && $ev['end_date'] >= $today) {
        $runningEvents[] = $ev;
    } else {
        $upcomingEvents[] = $ev;
    }
}

$introDe = (string)Database::getSetting('index_intro_de', 'Willkommen beim Online-Anmeldesystem! Hier findest du alle aktuellen Veranstaltungen und die Übersicht der freien Plätze.');
$introEn = (string)Database::getSetting('index_intro_en', 'Welcome to our online registration system! Below you will find all upcoming events and available spots.');
$introText = (I18n::isEnglish() && $introEn !== '') ? $introEn : $introDe;

ob_start();
?>

<?php if ($introText !== ''): ?>
    <p style="margin-top:0;margin-bottom:1.5rem;"><?= nl2br(e($introText)) ?></p>
<?php endif; ?>

<?php
$renderEventCard = function (array $ev): void {
    $lang = I18n::lang();
    $startFormatted = format_date($ev['start_date'], $lang);
    $endFormatted   = format_date($ev['end_date'], $lang);
    $dateRange      = ($startFormatted === $endFormatted) ? $startFormatted : "{$startFormatted} – {$endFormatted}";

    $locPre  = trim((string)($ev['location_prefix'] ?? ''));
    $locMain = trim((string)($ev['location'] ?? ''));
    $locSuf  = trim((string)($ev['location_suffix'] ?? ''));
    $mapsUrl = trim((string)($ev['maps_url'] ?? ''));
    $locTargetText = trim($locMain . ($locSuf !== '' ? ' ' . $locSuf : ''));
    ?>
    <div class="event-item">
        <h2 class="event-title">
            <?= e($ev['title']) ?>
            <?php if (!empty($ev['subtitle'])): ?>
                <span style="font-weight:400;opacity:0.85;"> – <?= e($ev['subtitle']) ?></span>
            <?php endif; ?>
        </h2>
        <div class="event-meta">
            📅 <?= e($dateRange) ?>
            <?php if ($locPre !== '' || $locTargetText !== ''): ?>
                &nbsp;&middot;&nbsp; 📍
                <?php if ($locPre !== ''): ?><?= e($locPre) ?> <?php endif; ?>
                <?php if ($mapsUrl !== '' && $locTargetText !== ''): ?>
                    <a href="<?= e($mapsUrl) ?>" target="_blank" rel="noopener" title="Auf Karte anzeigen"><?= e($locTargetText) ?> &nearr;</a>
                <?php else: ?>
                    <?= e($locTargetText) ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php
        $visibleGroups = array_filter($ev['groups'], fn($g) => (int)$g['show_on_index'] === 1);
        if (!empty($visibleGroups)):
        ?>
            <div class="seats-grid">
                <?php foreach ($visibleGroups as $g):
                    $gName = I18n::field($g, 'name');
                    $free  = $g['calculated_free'];
                    $full  = (bool)$g['is_full'];
                ?>
                    <?php if ($full):
                        $fullLabel = ((int)($ev['waitlist_enabled'] ?? 1) === 1)
                            ? I18n::t('index.waitlist_full')
                            : I18n::t('index.only_full');
                    ?>
                        <span class="seat-badge full">
                            <?= e($gName) ?>: <?= e($fullLabel) ?>
                        </span>
                    <?php elseif ($free !== null): ?>
                        <span class="seat-badge">
                            <?= e($gName) ?>: <strong><?= (int)$free ?></strong> <?= e(I18n::t('index.free_seats')) ?>
                        </span>
                    <?php else: ?>
                        <span class="seat-badge">
                            <?= e($gName) ?>
                        </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ((int)$ev['show_countdown'] === 1 && !empty($ev['countdown_target'])): ?>
            <div class="countdown-box" data-countdown="<?= e(date('c', strtotime((string)$ev['countdown_target']))) ?>">
                ⏳ <?= e(I18n::t('index.countdown_prefix')) ?>: <span class="cd-timer">…</span>
            </div>
        <?php endif; ?>

        <div class="event-actions">
            <?php if ($ev['status'] === 'open'): ?>
                <a href="register.php?event_id=<?= (int)$ev['id'] ?>" class="btn btn-primary">
                    ✍️ <?= e(I18n::t('index.to_registration')) ?>
                </a>
            <?php else: ?>
                <span class="btn btn-secondary" style="opacity:0.75;cursor:default;">
                    🔒 <?= e(I18n::t('index.reg_locked')) ?>
                </span>
            <?php endif; ?>

            <?php
            $effectiveFlyer = !empty($ev['flyer_file_path']) ? (string)$ev['flyer_file_path'] : (string)($ev['flyer_url'] ?? '');
            if ($effectiveFlyer !== ''):
            ?>
                <a href="<?= e($effectiveFlyer) ?>" target="_blank" rel="noopener" class="btn btn-secondary">
                    📄 <?= e(I18n::t('index.to_flyer')) ?>
                </a>
            <?php endif; ?>

            <?php if (!empty($ev['forum_url'])): ?>
                <a href="<?= e($ev['forum_url']) ?>" target="_blank" rel="noopener" class="btn btn-secondary">
                    💬 <?= e(I18n::t('index.to_forum')) ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
};
?>

<?php if (empty($runningEvents) && empty($upcomingEvents)): ?>
    <div class="alert alert-info">
        <?= e(I18n::t('index.no_events')) ?>
    </div>
<?php endif; ?>

<?php if (!empty($runningEvents)): ?>
    <h3 class="form-section-heading" style="margin-top:0;"><?= e(I18n::t('index.running_events')) ?></h3>
    <?php foreach ($runningEvents as $ev) { $renderEventCard($ev); } ?>
<?php endif; ?>

<?php if (!empty($upcomingEvents)): ?>
    <h3 class="form-section-heading" style="margin-top:0;"><?= e(I18n::t('index.upcoming_events')) ?></h3>
    <?php foreach ($upcomingEvents as $ev) { $renderEventCard($ev); } ?>
<?php endif; ?>

<script>
(function () {
    const boxes = document.querySelectorAll('[data-countdown]');
    if (!boxes.length) return;
    const lblD = <?= json_encode(I18n::t('index.days')) ?>;
    const lblH = <?= json_encode(I18n::t('index.hours')) ?>;
    const lblM = <?= json_encode(I18n::t('index.minutes')) ?>;
    const lblS = <?= json_encode(I18n::t('index.seconds')) ?>;

    function updateCountdowns() {
        const now = Date.now();
        boxes.forEach(function (box) {
            const target = Date.parse(box.getAttribute('data-countdown'));
            const out = box.querySelector('.cd-timer');
            if (!target || !out) return;
            let diff = Math.floor((target - now) / 1000);
            if (diff <= 0) {
                box.style.display = 'none';
                return;
            }
            const d = Math.floor(diff / 86400); diff %= 86400;
            const h = Math.floor(diff / 3600);  diff %= 3600;
            const m = Math.floor(diff / 60);
            const s = diff % 60;
            out.textContent = d + ' ' + lblD + ', ' +
                String(h).padStart(2, '0') + ' ' + lblH + ', ' +
                String(m).padStart(2, '0') + ' ' + lblM + ', ' +
                String(s).padStart(2, '0') + ' ' + lblS;
        });
    }
    updateCountdowns();
    setInterval(updateCountdowns, 1000);
})();
</script>

<?php
$html = (string)ob_get_clean();
Theme::render(I18n::t('index.welcome_title'), $html);
