<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

/** @var Joomleague\Component\Joomleague\Site\View\Teamplan\HtmlView $this */
$plan = $this->plan;

$formatScore = static function ($own, $opponent): string {
    if ($own === null || $opponent === null) {
        return Text::_('JNONE');
    }
    $format = static fn ($value): string => rtrim(rtrim((string) $value, '0'), '.');
    return $format($own) . ':' . $format($opponent);
};

// win/draw/loss from the entry's own point of view — used purely to colour
// each played card's left border (Bootstrap border-color utilities), a
// sports-page convention readers scan for before reading a single number.
$outcome = static function (array $match): string {
    if ($match['own_score'] === null || $match['opponent_score'] === null) {
        return 'secondary';
    }
    if ((float) $match['own_score'] > (float) $match['opponent_score']) {
        return 'success';
    }
    return (float) $match['own_score'] < (float) $match['opponent_score'] ? 'danger' : 'secondary';
};

// One locale-aware "23. srpna 2026, 15:00"-style string per match (locale
// from the active site language) — a data-formatting concern, not a styling
// one; every visual choice below still comes entirely from Bootstrap.
$locale = str_replace('-', '_', Factory::getLanguage()->getTag());
$dateTimeFmt = new IntlDateFormatter($locale, IntlDateFormatter::LONG, IntlDateFormatter::SHORT);
$currentYear = (int) date('Y');
$formatDateTime = static function (?string $sql) use ($dateTimeFmt, $currentYear): ?string {
    if ($sql === null) {
        return null;
    }
    $ts = strtotime($sql);
    if ($ts === false) {
        return null;
    }
    $text = $dateTimeFmt->format($ts);
    return (int) date('Y', $ts) === $currentYear ? (string) preg_replace('/\s*' . $currentYear . '\s*/', ' ', $text) : $text;
};

$matches = $plan['matches'] ?? [];
$nextMatch = null;
if ($plan['highlight_next'] ?? false) {
    foreach ($matches as $match) {
        if ($match['match_id'] === $plan['next_match_id']) {
            $nextMatch = $match;
            break;
        }
    }
}

// Grouped, not just filtered — a fixtures page reads far better as two
// distinct blocks than one long interleaved wall. The hero card above
// already covers the next match on its own, so it's left out of the
// "Nadcházející" grid to avoid showing the same fixture twice.
$upcoming = [];
$played = [];
foreach ($matches as $match) {
    if ($nextMatch !== null && $match['match_id'] === $nextMatch['match_id']) {
        continue;
    }
    if ($match['played']) {
        $played[] = $match;
    } else {
        $upcoming[] = $match;
    }
}

$entryName = isset($plan['entry']) ? (string) $plan['entry']->display_name : '';

$teamsMarkup = static function (array $match) use ($entryName): string {
    $own = '<span class="fw-bold">' . htmlspecialchars($entryName, ENT_QUOTES, 'UTF-8') . '</span>';
    $opp = htmlspecialchars($match['opponent'], ENT_QUOTES, 'UTF-8');
    return $match['is_home'] ? ($own . ' &ndash; ' . $opp) : ($opp . ' &ndash; ' . $own);
};
?>
<div class="com-joomleague-teamplan">
	<?php if (isset($plan['error'])) : ?>
		<div class="alert alert-warning"><?php echo Text::_($plan['error']); ?></div>
	<?php else : ?>
		<h1 class="com-joomleague-teamplan__title mb-3"><?php echo htmlspecialchars($entryName, ENT_QUOTES, 'UTF-8'); ?></h1>

		<?php if ($matches === []) : ?>
			<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_TEAMPLAN_VIEW_EMPTY'); ?></div>
		<?php else : ?>

			<?php if ($nextMatch !== null) : ?>
				<div class="card text-bg-primary bg-gradient shadow-sm mb-4">
					<div class="card-body">
						<div class="d-flex justify-content-between align-items-center mb-2">
							<span class="badge text-bg-light"><i class="fa-solid fa-star me-1"></i><?php echo Text::_('COM_JOOMLEAGUE_TEAMPLAN_NEXT_MATCH_BADGE'); ?></span>
							<?php if ($plan['show_round']) : ?>
								<span class="badge rounded-pill text-bg-light"><?php echo htmlspecialchars($nextMatch['round_name'], ENT_QUOTES, 'UTF-8'); ?></span>
							<?php endif; ?>
						</div>
						<h2 class="h4 mb-3"><?php echo $teamsMarkup($nextMatch); ?></h2>
						<div class="d-flex flex-wrap gap-3 align-items-center">
							<span class="fs-5 fw-semibold">
								<i class="fa-regular fa-calendar-days me-2"></i>
								<?php echo htmlspecialchars((string) $formatDateTime($nextMatch['scheduled_start']), ENT_QUOTES, 'UTF-8'); ?>
							</span>
							<?php if ($plan['show_venue'] && $nextMatch['venue'] !== null && $nextMatch['venue'] !== '') : ?>
								<span class="fs-6"><i class="fa-solid fa-location-dot me-2"></i><?php echo htmlspecialchars((string) $nextMatch['venue'], ENT_QUOTES, 'UTF-8'); ?></span>
							<?php endif; ?>
							<span class="badge text-bg-light"><?php echo Text::_($nextMatch['is_home'] ? 'COM_JOOMLEAGUE_TEAMPLAN_HOME_LABEL' : 'COM_JOOMLEAGUE_TEAMPLAN_AWAY_LABEL'); ?></span>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<?php
			$renderCards = function (array $rows, string $sectionTitle, string $sectionIcon) use ($teamsMarkup, $formatScore, $formatDateTime, $outcome, $plan) {
				if ($rows === []) {
					return;
				}
				?>
				<h2 class="h6 text-uppercase text-muted mt-4 mb-2"><i class="<?php echo $sectionIcon; ?> me-2"></i><?php echo Text::_($sectionTitle); ?></h2>
				<div class="row row-cols-1 row-cols-md-2 g-3 mb-3">
					<?php foreach ($rows as $match) :
						$borderColor = $match['played'] ? $outcome($match) : 'secondary-subtle';
						?>
						<div class="col">
							<div class="card h-100 shadow-sm border-start border-4 border-<?php echo $borderColor; ?>">
								<div class="card-body">
									<div class="d-flex justify-content-between align-items-start mb-2">
										<?php if ($plan['show_round']) : ?>
											<span class="badge rounded-pill text-bg-light border"><?php echo htmlspecialchars($match['round_name'], ENT_QUOTES, 'UTF-8'); ?></span>
										<?php else : ?>
											<span></span>
										<?php endif; ?>
										<span class="badge rounded-pill text-bg-secondary"><?php echo Text::_($match['is_home'] ? 'COM_JOOMLEAGUE_TEAMPLAN_HOME_SHORT' : 'COM_JOOMLEAGUE_TEAMPLAN_AWAY_SHORT'); ?></span>
									</div>
									<p class="card-text mb-2"><?php echo $teamsMarkup($match); ?></p>
									<div class="d-flex justify-content-between align-items-end">
										<div class="text-muted small">
											<div><i class="fa-regular fa-calendar-days me-1"></i> <?php echo htmlspecialchars((string) ($formatDateTime($match['scheduled_start']) ?? Text::_('COM_JOOMLEAGUE_TEAMPLAN_UPCOMING_LABEL')), ENT_QUOTES, 'UTF-8'); ?></div>
											<?php if ($plan['show_venue'] && $match['venue'] !== null && $match['venue'] !== '') : ?>
												<div><i class="fa-solid fa-location-dot me-1"></i> <?php echo htmlspecialchars((string) $match['venue'], ENT_QUOTES, 'UTF-8'); ?></div>
											<?php endif; ?>
										</div>
										<?php if ($match['played']) : ?>
											<span class="fs-4 fw-bold">
												<?php echo $match['is_home']
													? htmlspecialchars($formatScore($match['own_score'], $match['opponent_score']), ENT_QUOTES, 'UTF-8')
													: htmlspecialchars($formatScore($match['opponent_score'], $match['own_score']), ENT_QUOTES, 'UTF-8'); ?>
											</span>
										<?php endif; ?>
									</div>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<?php
			};

			$renderCards($upcoming, 'COM_JOOMLEAGUE_TEAMPLAN_SECTION_UPCOMING', 'fa-regular fa-calendar-check');
			$renderCards($played, 'COM_JOOMLEAGUE_TEAMPLAN_SECTION_PLAYED', 'fa-solid fa-clock-rotate-left');
			?>
		<?php endif; ?>
	<?php endif; ?>
</div>
