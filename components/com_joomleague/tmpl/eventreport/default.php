<?php

declare(strict_types=1);

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

/** @var Joomleague\Component\Joomleague\Site\View\Eventreport\HtmlView $this */
$data = $this->programItem;
$config = $this->templateConfig;

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

$statusLabel = static function (?string $status): string {
	$key = match ($status) {
		'scheduled' => 'COM_JOOMLEAGUE_EVENTREPORT_STATUS_SCHEDULED',
		'in_progress' => 'COM_JOOMLEAGUE_EVENTREPORT_STATUS_IN_PROGRESS',
		'finished', 'final' => 'COM_JOOMLEAGUE_EVENTREPORT_STATUS_FINISHED',
		'postponed' => 'COM_JOOMLEAGUE_EVENTREPORT_STATUS_POSTPONED',
		'cancelled' => 'COM_JOOMLEAGUE_EVENTREPORT_STATUS_CANCELLED',
		'abandoned' => 'COM_JOOMLEAGUE_EVENTREPORT_STATUS_ABANDONED',
		default => 'COM_JOOMLEAGUE_EVENTREPORT_STATUS_UNKNOWN',
	};

	return Text::_($key);
};

$segmentLabel = static function (object $segment): string {
	$key = (string) ($segment->name_key ?? '');
	if ($key !== '' && Factory::getApplication()->getLanguage()->hasKey($key)) {
		return Text::_($key);
	}

	return ucwords(str_replace('_', ' ', (string) $segment->level_code));
};
$sectionOrders = [
	'standard' => ['lineup' => 1, 'substitutions' => 2, 'events' => 3, 'statistics' => 4, 'officials' => 5],
	'activity_first' => ['events' => 1, 'substitutions' => 2, 'lineup' => 3, 'statistics' => 4, 'officials' => 5],
	'statistics_first' => ['statistics' => 1, 'events' => 2, 'lineup' => 3, 'substitutions' => 4, 'officials' => 5],
];
$sectionOrderCode = (string) ($config['section_order'] ?? 'standard');
$sectionOrder = $sectionOrders[$sectionOrderCode] ?? $sectionOrders['standard'];
?>
<div class="com-joomleague-eventreport">
	<?php if (isset($data['error'])) : ?>
		<div class="alert alert-warning"><?php echo Text::_($data['error']); ?></div>
	<?php else : ?>
		<?php
		$item = $data['item'];
		$participants = $data['participants'];
		$participantNames = array_map(static fn (object $participant): string => (string) $participant->name, $participants);
		$reportTitle = count($participantNames) >= 2 && count($participantNames) <= 4
			? implode(' – ', $participantNames)
			: (string) ($item->round_name ?: $item->project_name);
		$rootSegment = null;
		foreach ($data['segments'] as $segment) {
			if ($segment->parent_id === null) {
				$rootSegment = $segment;
				break;
			}
		}
		$lineupByParticipant = [];
		foreach ($data['lineup'] as $member) {
			if (($member->member_person_type === 'staff' && !($config['show_staff'] ?? true))
				|| ($member->member_person_type !== 'staff' && !($config['show_lineups'] ?? true))) {
				continue;
			}
			$lineupByParticipant[(int) $member->match_participant_id][] = $member;
		}
		if ($lineupByParticipant === []) {
			$data['lineup'] = [];
		}
		$changesByParticipant = [];
		foreach ($data['substitutions'] as $change) {
			$changesByParticipant[(int) $change->match_participant_id][] = $change;
		}
		$comparisonStatistics = [];
		$otherStatistics = [];
		$comparisonParticipantIds = count($participants) === 2
			? array_map(static fn (object $participant): int => (int) $participant->id, $participants)
			: [];
		foreach ($data['statistics'] as $statistic) {
			$participantId = (int) $statistic->match_participant_id;
			$isParticipantTotal = $comparisonParticipantIds !== []
				&& (string) $statistic->target_kind === 'participant'
				&& $statistic->person_id === null
				&& $statistic->lineup_member_id === null
				&& (int) $statistic->segment_key === 0
				&& in_array($participantId, $comparisonParticipantIds, true);

			if (!$isParticipantTotal) {
				$otherStatistics[] = $statistic;
				continue;
			}

			$code = (string) $statistic->statistic_code;
			$comparisonStatistics[$code] ??= [
				'name_key' => (string) $statistic->statistic_name_key,
				'values' => [],
			];
			$comparisonStatistics[$code]['values'][$participantId] = $statistic;
		}
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
			<nav aria-label="<?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_CONTEXT'); ?>" class="mb-2">
				<a href="<?php echo Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $item->project_id); ?>"><?php echo htmlspecialchars((string) $item->project_name, ENT_QUOTES, 'UTF-8'); ?></a>
				<span class="text-body-secondary mx-2" aria-hidden="true">/</span>
				<span><?php echo htmlspecialchars((string) $item->round_name, ENT_QUOTES, 'UTF-8'); ?></span>
			</nav>
			<h1 class="mb-2"><?php echo htmlspecialchars($reportTitle, ENT_QUOTES, 'UTF-8'); ?><?php if ($item->match_number) : ?> <span class="text-body-secondary">#<?php echo htmlspecialchars((string) $item->match_number, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?></h1>
			<div class="d-flex flex-wrap gap-2">
				<span class="badge text-bg-primary"><?php echo htmlspecialchars((string) $item->sport_name, ENT_QUOTES, 'UTF-8'); ?></span>
				<span class="badge text-bg-secondary"><?php echo htmlspecialchars((string) $item->competition_name, ENT_QUOTES, 'UTF-8'); ?></span>
				<span class="badge text-bg-light border"><?php echo htmlspecialchars((string) $item->season_name, ENT_QUOTES, 'UTF-8'); ?></span>
			</div>
		</header>
		<?php echo LayoutHelper::render('joomleague.fields', ['context' => 'com_joomleague.match', 'item' => $item], JPATH_ROOT . '/components/com_joomleague/layouts'); ?>

		<div class="row g-3 mb-4">
			<?php if ($date) : ?><div class="col-12 col-md-4"><div class="border rounded p-3 h-100"><div class="text-body-secondary small"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_DATE_TIME'); ?></div><strong><?php echo htmlspecialchars($date->format(Text::_('DATE_FORMAT_LC2')), ENT_QUOTES, 'UTF-8'); ?></strong><div class="small text-body-secondary"><?php echo htmlspecialchars($timezone, ENT_QUOTES, 'UTF-8'); ?></div></div></div><?php endif; ?>
			<?php if ($item->venue_name) : ?><div class="col-12 col-md-4"><div class="border rounded p-3 h-100"><div class="text-body-secondary small"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_VENUE'); ?></div><a href="<?php echo Route::_('index.php?option=com_joomleague&view=venue&venue_id=' . (int) $item->venue_id); ?>"><strong><?php echo htmlspecialchars((string) $item->venue_name, ENT_QUOTES, 'UTF-8'); ?></strong></a></div></div><?php endif; ?>
			<div class="col-12 col-md-4"><div class="border rounded p-3 h-100"><div class="text-body-secondary small"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_STATUS'); ?></div><strong><?php echo htmlspecialchars($statusLabel((string) $item->status_code), ENT_QUOTES, 'UTF-8'); ?></strong><?php if ($item->duration_minutes) : ?><div class="small text-body-secondary"><?php echo Text::sprintf('COM_JOOMLEAGUE_EVENTREPORT_DURATION', (int) $item->duration_minutes); ?></div><?php endif; ?><?php if ($item->attendance !== null) : ?><div class="small text-body-secondary"><?php echo Text::sprintf('COM_JOOMLEAGUE_EVENTREPORT_ATTENDANCE', (int) $item->attendance); ?></div><?php endif; ?></div></div>
		</div>

		<section class="mb-4">
			<h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_PARTICIPANTS'); ?></h2>
			<?php if ($participants === []) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_NO_PARTICIPANTS'); ?></div><?php else : ?>
				<div class="row row-cols-1 row-cols-md-2 g-3">
					<?php foreach ($participants as $participant) :
						$resultValue = $rootSegment ? ($data['valuesBySegment'][(int) $rootSegment->id][(int) $participant->id] ?? null) : null;
					?>
						<div class="col"><div class="card h-100"><div class="card-body d-flex align-items-center justify-content-between gap-3">
							<div><span class="badge text-bg-light border me-2"><?php echo (int) $participant->slot_number; ?></span><a class="fw-semibold" href="<?php echo Route::_('index.php?option=com_joomleague&view=participant&project_id=' . (int) $item->project_id . '&entry_id=' . (int) $participant->entry_id); ?>"><?php echo htmlspecialchars((string) $participant->name, ENT_QUOTES, 'UTF-8'); ?></a><?php if ($participant->result_status) : ?><div class="small text-body-secondary mt-1"><?php echo htmlspecialchars((string) $participant->result_status, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?></div>
							<div class="text-end"><?php if ($resultValue) : ?><div class="fs-3 fw-bold"><?php echo htmlspecialchars($formatValue($resultValue), ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?><?php if ($participant->result_rank) : ?><span class="badge text-bg-primary">#<?php echo (int) $participant->result_rank; ?></span><?php endif; ?></div>
						</div></div></div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>

		<?php if ($data['segments'] !== []) : ?><section class="mb-4"><h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_RESULT'); ?></h2><div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_SEGMENT'); ?></th><?php foreach ($participants as $participant) : ?><th scope="col" class="text-end"><?php echo htmlspecialchars((string) $participant->name, ENT_QUOTES, 'UTF-8'); ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach ($data['segments'] as $segment) : ?><tr><th scope="row"><?php echo htmlspecialchars($segmentLabel($segment), ENT_QUOTES, 'UTF-8'); ?><?php if ($segment->parent_id !== null) : ?> <?php echo (int) $segment->sequence_number; ?><?php endif; ?></th><?php foreach ($participants as $participant) : $value = $data['valuesBySegment'][(int) $segment->id][(int) $participant->id] ?? null; ?><td class="text-end fw-semibold"><?php echo $value ? htmlspecialchars($formatValue($value), ENT_QUOTES, 'UTF-8') : Text::_('JNONE'); ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div><?php if ($item->result_notes) : ?><p class="text-body-secondary"><?php echo nl2br(htmlspecialchars((string) $item->result_notes, ENT_QUOTES, 'UTF-8')); ?></p><?php endif; ?></section><?php endif; ?>

		<div class="d-flex flex-column">
		<?php if ($data['lineup'] !== []) : ?>
		<section class="mb-4 order-<?php echo (int) $sectionOrder['lineup']; ?>"><h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_LINEUP'); ?></h2><div class="row row-cols-1 row-cols-lg-2 g-3"><?php foreach ($participants as $participant) : if (($lineupByParticipant[(int) $participant->id] ?? []) === []) continue; ?><div class="col"><div class="card h-100"><div class="card-header fw-semibold"><?php echo htmlspecialchars((string) $participant->name, ENT_QUOTES, 'UTF-8'); ?></div><ul class="list-group list-group-flush"><?php foreach ($lineupByParticipant[(int) $participant->id] as $member) : ?><li class="list-group-item d-flex justify-content-between gap-3"><span><?php if ($member->shirt_number) : ?><span class="badge text-bg-light border me-2"><?php echo htmlspecialchars((string) $member->shirt_number, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=person&person_id=' . (int) $member->person_id); ?>"><?php echo htmlspecialchars((string) $member->name, ENT_QUOTES, 'UTF-8'); ?></a><?php if ($member->is_captain) : ?> <span class="badge text-bg-secondary"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_CAPTAIN'); ?></span><?php endif; ?></span><small class="text-body-secondary"><?php echo htmlspecialchars((string) ($member->role_code ?: $member->lineup_status), ENT_QUOTES, 'UTF-8'); ?></small></li><?php endforeach; ?></ul></div></div><?php endforeach; ?></div></section><?php endif; ?>

		<?php if ($data['substitutions'] !== []) : ?><section class="mb-4 order-<?php echo (int) $sectionOrder['substitutions']; ?>"><h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_CHANGES'); ?></h2><div class="list-group"><?php foreach ($participants as $participant) : foreach ($changesByParticipant[(int) $participant->id] ?? [] as $change) : ?><div class="list-group-item"><div class="d-flex flex-wrap justify-content-between gap-2"><strong><?php echo htmlspecialchars((string) $participant->name, ENT_QUOTES, 'UTF-8'); ?></strong><?php if ($change->clock_value !== null) : ?><span class="badge text-bg-light border"><?php echo htmlspecialchars($formatNumber($change->clock_value) . ' ' . (string) $change->clock_unit, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?></div><div class="mt-1"><span class="text-danger"><span class="icon-arrow-down" aria-hidden="true"></span> <?php echo htmlspecialchars((string) $change->outgoing_name, ENT_QUOTES, 'UTF-8'); ?></span><span class="mx-2" aria-hidden="true">/</span><span class="text-success"><span class="icon-arrow-up" aria-hidden="true"></span> <?php echo htmlspecialchars((string) $change->incoming_name, ENT_QUOTES, 'UTF-8'); ?></span></div><?php if ($change->notes) : ?><small class="text-body-secondary"><?php echo htmlspecialchars((string) $change->notes, ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?></div><?php endforeach; endforeach; ?></div></section><?php endif; ?>

		<?php if ($data['events'] !== [] && ($config['show_timeline'] ?? true)) : ?><section class="mb-4 order-<?php echo (int) $sectionOrder['events']; ?>"><h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_EVENTS'); ?></h2><div class="list-group"><?php foreach ($data['events'] as $event) : ?><div class="list-group-item d-flex justify-content-between gap-3"><div><strong><?php echo htmlspecialchars(Text::_((string) $event->event_name_key), ENT_QUOTES, 'UTF-8'); ?></strong><?php if ($event->primary_name_snapshot) : ?><div><?php echo htmlspecialchars((string) $event->primary_name_snapshot, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?></div><?php if ($event->clock_value !== null) : ?><span class="badge text-bg-light border"><?php echo htmlspecialchars($formatNumber($event->clock_value) . ' ' . (string) $event->clock_unit, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?></div><?php endforeach; ?></div></section><?php endif; ?>

		<?php if ($data['statistics'] !== []) : ?>
			<section class="mb-4 order-<?php echo (int) $sectionOrder['statistics']; ?>">
				<h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_STATISTICS'); ?></h2>
				<?php if ($comparisonStatistics !== []) : ?>
					<div class="table-responsive mb-3">
						<table class="table table-striped align-middle text-center">
							<thead><tr><th scope="col" class="w-25"><?php echo htmlspecialchars((string) $participants[0]->name, ENT_QUOTES, 'UTF-8'); ?></th><th scope="col" class="w-50"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_STATISTIC'); ?></th><th scope="col" class="w-25"><?php echo htmlspecialchars((string) $participants[1]->name, ENT_QUOTES, 'UTF-8'); ?></th></tr></thead>
							<tbody><?php foreach ($comparisonStatistics as $row) : ?><tr><?php foreach ([$comparisonParticipantIds[0], $comparisonParticipantIds[1]] as $column => $participantId) : $statistic = $row['values'][$participantId] ?? null; if ($column === 1) : ?><th scope="row" class="fw-semibold"><?php echo htmlspecialchars(Text::_($row['name_key']), ENT_QUOTES, 'UTF-8'); ?></th><?php endif; ?><td class="fs-5 fw-bold"><?php echo $statistic ? htmlspecialchars($statistic->numeric_value !== null ? $formatNumber($statistic->numeric_value) : (string) $statistic->text_value, ENT_QUOTES, 'UTF-8') : '&ndash;'; ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody>
						</table>
					</div>
				<?php endif; ?>
				<?php if ($otherStatistics !== []) : ?>
					<div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_STATISTIC'); ?></th><th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_TARGET'); ?></th><th scope="col" class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_VALUE'); ?></th></tr></thead><tbody><?php foreach ($otherStatistics as $statistic) : ?><tr><th scope="row"><?php echo htmlspecialchars(Text::_((string) $statistic->statistic_name_key), ENT_QUOTES, 'UTF-8'); ?></th><td><?php echo htmlspecialchars((string) $statistic->target_name_snapshot, ENT_QUOTES, 'UTF-8'); ?></td><td class="text-end fw-semibold"><?php echo htmlspecialchars($statistic->numeric_value !== null ? $formatNumber($statistic->numeric_value) : (string) $statistic->text_value, ENT_QUOTES, 'UTF-8'); ?></td></tr><?php endforeach; ?></tbody></table></div>
				<?php endif; ?>
			</section>
		<?php endif; ?>

		<?php if ($data['officials'] !== [] && ($config['show_officials'] ?? true)) : ?><section class="mb-4 order-<?php echo (int) $sectionOrder['officials']; ?>"><h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_OFFICIALS'); ?></h2><dl class="row"><?php foreach ($data['officials'] as $official) : ?><dt class="col-sm-4"><?php echo htmlspecialchars((string) $official->role_code, ENT_QUOTES, 'UTF-8'); ?></dt><dd class="col-sm-8"><?php echo htmlspecialchars((string) $official->display_name_snapshot, ENT_QUOTES, 'UTF-8'); ?></dd><?php endforeach; ?></dl></section><?php endif; ?>

		</div>

		<?php if ($item->description) : ?><section><h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_DESCRIPTION'); ?></h2><div><?php echo nl2br(htmlspecialchars((string) $item->description, ENT_QUOTES, 'UTF-8')); ?></div></section><?php endif; ?>

		<?php
		$calendarPath = 'index.php?option=com_joomleague&task=ical.download&project_id=' . (int) $item->project_id . '&scope=event&event_id=' . (int) $item->id;
		$calendarHttpUrl = rtrim(Uri::root(), '/') . '/' . ltrim(Route::_($calendarPath, false), '/');
		$calendarWebcalUrl = preg_replace('#^https?://#i', 'webcal://', $calendarHttpUrl);
		$calendarName = $reportTitle . ($item->match_number ? ' #' . (string) $item->match_number : '');
		$googleUrl = 'https://calendar.google.com/calendar/r?cid=' . rawurlencode($calendarWebcalUrl);
		$outlookParams = 'name=' . rawurlencode($calendarName) . '&url=' . rawurlencode($calendarWebcalUrl);
		?>
		<section class="card mt-4">
			<div class="card-body">
				<h2 class="h5 card-title"><span class="icon-calendar" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_CALENDAR_TITLE'); ?></h2>
				<p class="card-text text-body-secondary small"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_CALENDAR_DESC'); ?></p>
				<div class="d-flex flex-wrap gap-2">
					<a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars($calendarHttpUrl, ENT_QUOTES, 'UTF-8'); ?>"><span class="icon-download" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_TEAMPLAN_CALENDAR_ICS'); ?></a>
					<a class="btn btn-outline-primary" href="<?php echo htmlspecialchars($googleUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_JOOMLEAGUE_TEAMPLAN_CALENDAR_GOOGLE'); ?></a>
					<a class="btn btn-outline-primary" href="<?php echo htmlspecialchars($calendarWebcalUrl, ENT_QUOTES, 'UTF-8'); ?>"><?php echo Text::_('COM_JOOMLEAGUE_TEAMPLAN_CALENDAR_APPLE'); ?></a>
					<a class="btn btn-outline-primary" href="https://outlook.live.com/calendar/addcalendar?<?php echo htmlspecialchars($outlookParams, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_JOOMLEAGUE_TEAMPLAN_CALENDAR_OUTLOOK'); ?></a>
					<a class="btn btn-outline-primary" href="https://outlook.office.com/calendar/addcalendar?<?php echo htmlspecialchars($outlookParams, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_JOOMLEAGUE_TEAMPLAN_CALENDAR_OFFICE'); ?></a>
				</div>
			</div>
		</section>

		<nav class="d-flex justify-content-between gap-3 mt-4" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_NAVIGATION'); ?>">
			<?php if ($data['navigation']['previous_id']) : ?><a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $data['navigation']['previous_id']); ?>"><span class="icon-chevron-left" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_PREVIOUS'); ?></a><?php else : ?><span></span><?php endif; ?>
			<?php if ($data['navigation']['next_id']) : ?><a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $data['navigation']['next_id']); ?>"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_NEXT'); ?> <span class="icon-chevron-right" aria-hidden="true"></span></a><?php endif; ?>
		</nav>
	<?php endif; ?>
</div>
