<?php

declare(strict_types=1);

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

/** @var Joomleague\Component\Joomleague\Site\View\Programitem\HtmlView $this */
$data = $this->programItem;

$formatNumber = static function ($value): string {
	return rtrim(rtrim(number_format((float) $value, 9, '.', ''), '0'), '.');
};

$formatValue = static function (object $value) use ($formatNumber): string {
	if ($value->numeric_value !== null) {
		return $formatNumber($value->numeric_value);
	}
	if ($value->text_value !== null && $value->text_value !== '') {
		return (string) $value->text_value;
	}
	if ($value->status_code !== null && $value->status_code !== '') {
		return (string) $value->status_code;
	}
	return $value->result_rank !== null ? '#' . (int) $value->result_rank : Text::_('JNONE');
};
?>
<div class="com-joomleague-programitem">
	<?php if (isset($data['error'])) : ?>
		<div class="alert alert-warning"><?php echo Text::_($data['error']); ?></div>
	<?php else : ?>
		<?php
		$item = $data['item'];
		$participants = $data['participants'];
		$timezone = (string) ($item->timezone ?: Factory::getApplication()->get('offset', 'UTC'));
		$date = null;
		if ($item->scheduled_start) {
			try {
				$date = Factory::getDate((string) $item->scheduled_start, 'UTC');
				$date->setTimezone(new DateTimeZone($timezone));
			} catch (Throwable) {
				$date = null;
			}
		}
		?>
		<header class="border-bottom pb-3 mb-4">
			<nav aria-label="<?php echo Text::_('COM_JOOMLEAGUE_PROGRAMITEM_CONTEXT'); ?>" class="mb-2">
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $item->project_id); ?>"><?php echo htmlspecialchars((string) $item->project_name, ENT_QUOTES, 'UTF-8'); ?></a>
				<span class="text-body-secondary mx-2" aria-hidden="true">/</span>
				<span><?php echo htmlspecialchars((string) $item->round_name, ENT_QUOTES, 'UTF-8'); ?></span>
			</nav>
			<h1 class="mb-2"><?php echo Text::_('COM_JOOMLEAGUE_PROGRAMITEM_VIEW_TITLE'); ?><?php if ($item->match_number) : ?> <span class="text-body-secondary">#<?php echo htmlspecialchars((string) $item->match_number, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?></h1>
			<div class="d-flex flex-wrap gap-2">
				<span class="badge text-bg-primary"><?php echo htmlspecialchars((string) $item->sport_name, ENT_QUOTES, 'UTF-8'); ?></span>
				<span class="badge text-bg-secondary"><?php echo htmlspecialchars((string) $item->competition_name, ENT_QUOTES, 'UTF-8'); ?></span>
				<span class="badge text-bg-light border"><?php echo htmlspecialchars((string) $item->season_name, ENT_QUOTES, 'UTF-8'); ?></span>
			</div>
		</header>

		<div class="row g-3 mb-4">
			<?php if ($date) : ?><div class="col-12 col-md-4"><div class="border rounded p-3 h-100"><div class="text-body-secondary small"><?php echo Text::_('COM_JOOMLEAGUE_PROGRAMITEM_DATE_TIME'); ?></div><strong><?php echo htmlspecialchars($date->format(Text::_('DATE_FORMAT_LC2')), ENT_QUOTES, 'UTF-8'); ?></strong><div class="small text-body-secondary"><?php echo htmlspecialchars($timezone, ENT_QUOTES, 'UTF-8'); ?></div></div></div><?php endif; ?>
			<?php if ($item->venue_name) : ?><div class="col-12 col-md-4"><div class="border rounded p-3 h-100"><div class="text-body-secondary small"><?php echo Text::_('COM_JOOMLEAGUE_PROGRAMITEM_VENUE'); ?></div><a href="<?php echo Route::_('index.php?option=com_joomleague&view=venue&venue_id=' . (int) $item->venue_id); ?>"><strong><?php echo htmlspecialchars((string) $item->venue_name, ENT_QUOTES, 'UTF-8'); ?></strong></a></div></div><?php endif; ?>
			<div class="col-12 col-md-4"><div class="border rounded p-3 h-100"><div class="text-body-secondary small"><?php echo Text::_('COM_JOOMLEAGUE_PROGRAMITEM_STATUS'); ?></div><strong><?php echo htmlspecialchars((string) $item->status_code, ENT_QUOTES, 'UTF-8'); ?></strong><?php if ($item->duration_minutes) : ?><div class="small text-body-secondary"><?php echo Text::sprintf('COM_JOOMLEAGUE_PROGRAMITEM_DURATION', (int) $item->duration_minutes); ?></div><?php endif; ?></div></div>
		</div>

		<section class="mb-4">
			<h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_PROGRAMITEM_PARTICIPANTS'); ?></h2>
			<?php if ($participants === []) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_PROGRAMITEM_NO_PARTICIPANTS'); ?></div><?php else : ?>
				<div class="row row-cols-1 row-cols-md-2 g-3">
					<?php foreach ($participants as $participant) : ?><div class="col"><div class="border rounded p-3 h-100 d-flex align-items-center justify-content-between gap-3"><div><span class="badge text-bg-light border me-2"><?php echo (int) $participant->slot_number; ?></span><?php if ($participant->team_id) : ?><a class="fw-semibold" href="<?php echo Route::_('index.php?option=com_joomleague&view=team&team_id=' . (int) $participant->team_id); ?>"><?php echo htmlspecialchars((string) $participant->name, ENT_QUOTES, 'UTF-8'); ?></a><?php elseif ($participant->person_id) : ?><a class="fw-semibold" href="<?php echo Route::_('index.php?option=com_joomleague&view=person&person_id=' . (int) $participant->person_id); ?>"><?php echo htmlspecialchars((string) $participant->name, ENT_QUOTES, 'UTF-8'); ?></a><?php else : ?><strong><?php echo htmlspecialchars((string) $participant->name, ENT_QUOTES, 'UTF-8'); ?></strong><?php endif; ?></div><?php if ($participant->result_rank) : ?><span class="badge text-bg-primary">#<?php echo (int) $participant->result_rank; ?></span><?php endif; ?></div></div><?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>

		<?php if ($data['segments'] !== []) : ?><section class="mb-4"><h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_PROGRAMITEM_RESULT'); ?></h2><div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_PROGRAMITEM_SEGMENT'); ?></th><?php foreach ($participants as $participant) : ?><th scope="col" class="text-end"><?php echo htmlspecialchars((string) $participant->name, ENT_QUOTES, 'UTF-8'); ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach ($data['segments'] as $segment) : ?><tr><th scope="row"><?php echo htmlspecialchars((string) $segment->level_code, ENT_QUOTES, 'UTF-8'); ?><?php if ($segment->parent_id !== null) : ?> <?php echo (int) $segment->sequence_number; ?><?php endif; ?></th><?php foreach ($participants as $participant) : $value = $data['valuesBySegment'][(int) $segment->id][(int) $participant->id] ?? null; ?><td class="text-end fw-semibold"><?php echo $value ? htmlspecialchars($formatValue($value), ENT_QUOTES, 'UTF-8') : Text::_('JNONE'); ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div><?php if ($item->result_notes) : ?><p class="text-body-secondary"><?php echo nl2br(htmlspecialchars((string) $item->result_notes, ENT_QUOTES, 'UTF-8')); ?></p><?php endif; ?></section><?php endif; ?>

		<?php if ($data['lineup'] !== []) : ?><section class="mb-4"><h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_PROGRAMITEM_LINEUP'); ?></h2><div class="row row-cols-1 row-cols-md-2 g-3"><?php foreach ($data['lineup'] as $member) : ?><div class="col"><div class="border rounded p-3 h-100"><strong><?php if ($member->shirt_number) : ?><span class="badge text-bg-light border me-2"><?php echo htmlspecialchars((string) $member->shirt_number, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?><?php echo htmlspecialchars((string) $member->name, ENT_QUOTES, 'UTF-8'); ?></strong><?php if ($member->role_code) : ?><div class="small text-body-secondary"><?php echo htmlspecialchars((string) $member->role_code, ENT_QUOTES, 'UTF-8'); ?><?php if ($member->is_captain) : ?> · <?php echo Text::_('COM_JOOMLEAGUE_PROGRAMITEM_CAPTAIN'); ?><?php endif; ?></div><?php endif; ?></div></div><?php endforeach; ?></div></section><?php endif; ?>

		<?php if ($data['events'] !== []) : ?><section class="mb-4"><h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_PROGRAMITEM_EVENTS'); ?></h2><div class="list-group"><?php foreach ($data['events'] as $event) : ?><div class="list-group-item d-flex justify-content-between gap-3"><div><strong><?php echo htmlspecialchars(Text::_((string) $event->event_name_key), ENT_QUOTES, 'UTF-8'); ?></strong><?php if ($event->primary_name_snapshot) : ?><div><?php echo htmlspecialchars((string) $event->primary_name_snapshot, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?></div><?php if ($event->clock_value !== null) : ?><span class="badge text-bg-light border"><?php echo htmlspecialchars($formatNumber($event->clock_value) . ' ' . (string) $event->clock_unit, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?></div><?php endforeach; ?></div></section><?php endif; ?>

		<?php if ($data['statistics'] !== []) : ?><section class="mb-4"><h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_PROGRAMITEM_STATISTICS'); ?></h2><div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_PROGRAMITEM_STATISTIC'); ?></th><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_PROGRAMITEM_TARGET'); ?></th><th scope="col" class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_PROGRAMITEM_VALUE'); ?></th></tr></thead><tbody><?php foreach ($data['statistics'] as $statistic) : ?><tr><th scope="row"><?php echo htmlspecialchars(Text::_((string) $statistic->statistic_name_key), ENT_QUOTES, 'UTF-8'); ?></th><td><?php echo htmlspecialchars((string) $statistic->target_name_snapshot, ENT_QUOTES, 'UTF-8'); ?></td><td class="text-end fw-semibold"><?php echo htmlspecialchars($statistic->numeric_value !== null ? $formatNumber($statistic->numeric_value) : (string) $statistic->text_value, ENT_QUOTES, 'UTF-8'); ?></td></tr><?php endforeach; ?></tbody></table></div></section><?php endif; ?>

		<?php if ($data['officials'] !== []) : ?><section class="mb-4"><h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_PROGRAMITEM_OFFICIALS'); ?></h2><dl class="row"><?php foreach ($data['officials'] as $official) : ?><dt class="col-sm-4"><?php echo htmlspecialchars((string) $official->role_code, ENT_QUOTES, 'UTF-8'); ?></dt><dd class="col-sm-8"><?php echo htmlspecialchars((string) $official->display_name_snapshot, ENT_QUOTES, 'UTF-8'); ?></dd><?php endforeach; ?></dl></section><?php endif; ?>

		<?php if ($item->description) : ?><section><h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_PROGRAMITEM_DESCRIPTION'); ?></h2><div><?php echo nl2br(htmlspecialchars((string) $item->description, ENT_QUOTES, 'UTF-8')); ?></div></section><?php endif; ?>
	<?php endif; ?>
</div>
