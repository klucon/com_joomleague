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
			<?php if ($previousStage !== $round['stage_name']) : ?>
				<h2 class="h4 mt-4"><?php echo $escape($round['stage_name']); ?></h2>
				<?php $previousStage = $round['stage_name']; ?>
			<?php endif; ?>
			<h3 class="h5 mt-3"><?php echo $escape($round['name']); ?></h3>
			<div class="list-group mb-4">
				<?php foreach ($round['items'] as $item) : ?>
					<a class="list-group-item list-group-item-action" href="<?php echo Route::_('index.php?option=com_joomleague&amp;view=programitem&amp;match_id=' . (int) $item->id); ?>">
						<div class="d-flex flex-wrap justify-content-between gap-3">
							<div class="flex-grow-1">
								<?php if ($item->participants === []) : ?>
									<span class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_RESULTS_NO_PARTICIPANTS'); ?></span>
								<?php else : ?>
									<?php foreach ($item->participants as $participant) : ?>
										<div class="d-flex justify-content-between gap-3 py-1">
											<span class="fw-semibold"><?php echo $escape($participant->name); ?></span>
											<?php $value = $formatValue($participant->result_value, $item->result_status === 'final' && $participant->result_rank !== null ? (int) $participant->result_rank : null); ?>
											<?php if ($value !== '') : ?><span class="badge text-bg-secondary"><?php echo $value; ?></span><?php endif; ?>
										</div>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
							<div class="text-end small text-body-secondary">
								<?php if ($item->scheduled_start) : ?>
									<?php $date = Factory::getDate($item->scheduled_start, 'UTC'); $date->setTimezone(new DateTimeZone((string) ($item->timezone ?: $results['project']->timezone ?: Factory::getApplication()->get('offset', 'UTC')))); ?>
									<div><?php echo $escape($date->format(Text::_('DATE_FORMAT_LC2'), true)); ?></div>
								<?php else : ?><div><?php echo Text::_('COM_JOOMLEAGUE_RESULTS_DATE_PENDING'); ?></div><?php endif; ?>
								<?php if ($item->match_number) : ?><div><?php echo Text::_('COM_JOOMLEAGUE_RESULTS_NUMBER_LABEL'); ?>: <?php echo $escape($item->match_number); ?></div><?php endif; ?>
							</div>
						</div>
						<?php $meta = array_filter([(string) $item->venue_name, $item->attendance !== null ? Text::_('COM_JOOMLEAGUE_RESULTS_ATTENDANCE_LABEL') . ': ' . (int) $item->attendance : '']); ?>
						<?php if ($meta !== []) : ?><div class="small text-body-secondary mt-2"><?php echo implode(' &middot; ', array_map($escape, $meta)); ?></div><?php endif; ?>
						<?php if ($results['show_events'] && $item->events !== []) : ?>
							<div class="small mt-2"><span class="fw-semibold"><?php echo Text::_('COM_JOOMLEAGUE_RESULTS_EVENTS_LABEL'); ?>:</span>
								<?php $eventParts = []; foreach ($item->events as $event) { $eventParts[] = $escape(Text::_($event->event_name_key) . ($event->primary_name_snapshot ? ' - ' . $event->primary_name_snapshot : '') . ($event->clock_value !== null ? ' (' . rtrim(rtrim((string) $event->clock_value, '0'), '.') . ' ' . $event->clock_unit . ')' : '')); } echo implode(', ', $eventParts); ?>
							</div>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
