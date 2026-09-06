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
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

/** @var Joomleague\Component\Joomleague\Site\View\Results\HtmlView $this */
$results = $this->results;
$config = $this->templateConfig;
$groupByRound = (bool) ($config['group_by_round'] ?? true);
$showDetail = (bool) ($config['show_match_detail_button'] ?? true);
$showPeriodScores = (bool) ($config['show_period_scores'] ?? false);
$showSetScores = (bool) ($config['show_set_scores'] ?? false);
$showRaceSplits = !$this->raceResults || (bool) ($config['show_splits'] ?? true);
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$formatValue = static function (?object $value, ?int $fallbackRank = null) use ($escape): string {
	if ($value !== null) {
		if ($value->numeric_value !== null) {
			return $escape(rtrim(rtrim((string) $value->numeric_value, '0'), '.'));
		}
		if ($value->text_value !== null && $value->text_value !== '') {
			return $escape($value->text_value);
		}
		if ($value->status_code !== null && $value->status_code !== '') {
			return $escape($value->status_code);
		}
		if ($value->result_rank !== null) {
			return '#' . (int) $value->result_rank;
		}
	}

	return $fallbackRank !== null ? '#' . $fallbackRank : '';
};
$formatSegments = static function (object $item) use ($escape, $formatValue, $showPeriodScores, $showSetScores, $showRaceSplits): string {
	$parts = [];
	foreach ($item->score_segments ?? [] as $segment) {
		$isSet = (string) $segment->level_code === 'set';
		$isRaceSplit = in_array((string) $segment->level_code, ['split', 'lap'], true);
		if ($isRaceSplit && !$showRaceSplits) {
			continue;
		}
		if (!$isRaceSplit && (($isSet && !$showSetScores) || (!$isSet && !$showPeriodScores))) {
			continue;
		}
		$values = [];
		foreach ($item->participants as $participant) {
			$values[] = $formatValue($segment->values[(int) $participant->id] ?? null);
		}
		$values = array_filter($values, static fn (string $value): bool => $value !== '');
		if ($values === []) {
			continue;
		}
		$key = 'COM_JOOMLEAGUE_SCORE_SEGMENT_' . strtoupper((string) $segment->level_code);
		$label = Text::_($key);
		if ($label === $key) {
			$label = (string) $segment->level_code;
		}
		$parts[] = '<span class="text-nowrap">' . $escape($label) . ' ' . (int) $segment->sequence_number . ': ' . implode(':', $values) . '</span>';
	}

	return implode('<span class="mx-1" aria-hidden="true">&middot;</span>', $parts);
};
?>
<div class="com-joomleague-results">
	<?php if (isset($results['error'])) : ?>
		<div class="alert alert-warning"><?php echo Text::_($results['error']); ?></div>
	<?php else : ?>
		<header class="mb-4">
			<h1><?php echo Text::_('COM_JOOMLEAGUE_RESULTS_VIEW_TITLE'); ?></h1>
			<p class="h4"><?php echo $escape($results['project']->name); ?></p>
			<p class="text-body-secondary mb-0"><?php echo Text::_('COM_JOOMLEAGUE_RESULTS_VIEW_INTRO'); ?></p>
		</header>

		<?php $previousStage = null; ?>
		<?php foreach ($results['rounds'] as $round) : ?>
			<?php if ($groupByRound && $previousStage !== $round['stage_name']) : ?>
				<h2 class="h4 mt-4"><?php echo $escape($round['stage_name']); ?></h2>
				<?php $previousStage = $round['stage_name']; ?>
			<?php endif; ?>
			<?php if ($groupByRound) : ?><h3 class="h5 mt-3 mb-2"><?php echo $escape($round['name']); ?></h3><?php endif; ?>
			<div class="table-responsive mb-4 d-none d-md-block">
				<table class="table table-hover align-middle mb-0">
					<thead><tr><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_RESULTS_DATE_LABEL'); ?></th><th scope="col" class="w-50"><?php echo Text::_('COM_JOOMLEAGUE_RESULTS_PROGRAMME_ITEM_LABEL'); ?></th><th scope="col" class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_RESULTS_RESULT_LABEL'); ?></th><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_RESULTS_VENUE_LABEL'); ?></th></tr></thead>
					<tbody>
					<?php foreach ($round['items'] as $item) :
						$date = null;
						if ($item->scheduled_start) {
							$date = Factory::getDate($item->scheduled_start, 'UTC');
							$date->setTimezone(new DateTimeZone((string) ($item->timezone ?: $results['project']->timezone ?: Factory::getApplication()->get('offset', 'UTC'))));
						}
						$values = [];
						foreach ($item->participants as $participant) {
							$values[] = $participant->resolved ? $formatValue($participant->result_value, $item->result_status === 'final' && $participant->result_rank !== null ? (int) $participant->result_rank : null) : '';
						}
						$participantNames = array_map(static fn (object $participant): string => $participant->resolved ? $escape($participant->name) : Text::_('COM_JOOMLEAGUE_RESULTS_PARTICIPANT_PENDING'), $item->participants);
					?>
						<tr>
							<td class="text-nowrap small"><?php if ($date) : ?><span class="fw-semibold"><?php echo $escape($date->format(Text::_('DATE_FORMAT_LC4'))); ?></span><br><span class="text-body-secondary"><?php echo $escape($date->format('H:i')); ?></span><?php else : ?><?php echo Text::_('COM_JOOMLEAGUE_RESULTS_DATE_PENDING'); ?><?php endif; ?></td>
							<td><?php if ($showDetail) : ?><a class="fw-semibold text-decoration-none" href="<?php echo Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $item->id); ?>"><?php else : ?><span class="fw-semibold"><?php endif; ?><?php if ($item->participants === []) : ?><?php echo Text::_('COM_JOOMLEAGUE_RESULTS_NO_PARTICIPANTS'); ?><?php elseif (count($item->participants) <= 2) : ?><?php echo implode(' <span class="text-body-secondary fw-normal">&ndash;</span> ', $participantNames); ?><?php else : ?><?php foreach ($participantNames as $participantName) : ?><span class="d-block"><?php echo $participantName; ?></span><?php endforeach; ?><?php endif; ?><?php echo $showDetail ? '</a>' : '</span>'; ?><?php if ($item->match_number) : ?><div class="small text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_RESULTS_NUMBER_LABEL'); ?>: <?php echo $escape($item->match_number); ?></div><?php endif; ?></td>
							<td class="text-center fw-bold"><?php if (count($item->participants) <= 2) : ?><?php echo implode(' <span class="text-body-secondary fw-normal">:</span> ', array_filter($values, static fn (string $value): bool => $value !== '')) ?: '&ndash;'; ?><?php else : ?><?php foreach ($values as $value) : ?><span class="d-block text-nowrap"><?php echo $value !== '' ? $value : '&ndash;'; ?></span><?php endforeach; ?><?php endif; ?><?php $segments = $formatSegments($item); if ($segments !== '') : ?><div class="small text-body-secondary fw-normal mt-1"><?php echo $segments; ?></div><?php endif; ?></td>
							<td class="small"><?php echo $item->venue_name ? $escape($item->venue_name) : '&ndash;'; ?><?php if ($item->attendance !== null) : ?><br><span class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_RESULTS_ATTENDANCE_LABEL'); ?>: <?php echo (int) $item->attendance; ?></span><?php endif; ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<div class="list-group list-group-flush mb-4 d-md-none">
				<?php foreach ($round['items'] as $item) :
					$date = null;
					if ($item->scheduled_start) {
						$date = Factory::getDate($item->scheduled_start, 'UTC');
						$date->setTimezone(new DateTimeZone((string) ($item->timezone ?: $results['project']->timezone ?: Factory::getApplication()->get('offset', 'UTC'))));
					}
					$values = [];
					foreach ($item->participants as $participant) {
						$values[] = $participant->resolved ? $formatValue($participant->result_value, $item->result_status === 'final' && $participant->result_rank !== null ? (int) $participant->result_rank : null) : '';
					}
					$participantNames = array_map(static fn (object $participant): string => $participant->resolved ? $escape($participant->name) : Text::_('COM_JOOMLEAGUE_RESULTS_PARTICIPANT_PENDING'), $item->participants);
				?>
					<?php if ($showDetail) : ?><a class="list-group-item list-group-item-action px-0 py-3" href="<?php echo Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $item->id); ?>"><?php else : ?><div class="list-group-item px-0 py-3"><?php endif; ?>
						<?php if ($item->participants === []) : ?><div class="fw-semibold"><?php echo Text::_('COM_JOOMLEAGUE_RESULTS_NO_PARTICIPANTS'); ?></div><?php elseif (count($item->participants) <= 2) : ?><div class="d-flex align-items-start justify-content-between gap-3"><span class="fw-semibold"><?php echo implode(' &ndash; ', $participantNames); ?></span><span class="fw-bold text-nowrap"><?php echo implode(' : ', array_filter($values, static fn (string $value): bool => $value !== '')) ?: '&ndash;'; ?></span></div><?php else : ?><div><?php foreach ($participantNames as $index => $participantName) : ?><div class="d-flex justify-content-between gap-3"><span class="fw-semibold"><?php echo $participantName; ?></span><span class="fw-bold text-nowrap"><?php echo ($values[$index] ?? '') !== '' ? $values[$index] : '&ndash;'; ?></span></div><?php endforeach; ?></div><?php endif; ?>
						<div class="small text-body-secondary mt-1"><?php echo $date ? $escape($date->format(Text::_('DATE_FORMAT_LC4')) . ' ' . $date->format('H:i')) : Text::_('COM_JOOMLEAGUE_RESULTS_DATE_PENDING'); ?><?php if ($item->venue_name) : ?> &middot; <?php echo $escape($item->venue_name); ?><?php endif; ?><?php if ($item->match_number) : ?> &middot; <?php echo Text::_('COM_JOOMLEAGUE_RESULTS_NUMBER_LABEL'); ?> <?php echo $escape($item->match_number); ?><?php endif; ?></div>
						<?php $segments = $formatSegments($item); if ($segments !== '') : ?><div class="small text-body-secondary mt-1"><?php echo $segments; ?></div><?php endif; ?>
					<?php echo $showDetail ? '</a>' : '</div>'; ?>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
