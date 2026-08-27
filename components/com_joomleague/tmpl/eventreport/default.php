<?php
declare(strict_types=1);

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

/** @var Joomleague\Component\Joomleague\Site\View\Eventreport\HtmlView $this */
$data = $this->eventReport;
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$number = static function ($value): string {
	$value = rtrim(rtrim(number_format((float) $value, 9, '.', ''), '0'), '.');
	return $value === '' || $value === '-' ? '0' : $value;
};
$value = static function (object $row) use ($number): string {
	if ($row->numeric_value !== null) return $number($row->numeric_value);
	if ($row->text_value !== null && $row->text_value !== '') return (string) $row->text_value;
	if ($row->status_code !== null && $row->status_code !== '') return Text::_('COM_JOOMLEAGUE_RESULT_CODE_' . strtoupper((string) $row->status_code));
	return $row->result_rank !== null ? Text::sprintf('COM_JOOMLEAGUE_EVENTREPORT_RANK', (int) $row->result_rank) : Text::_('JNONE');
};
$clock = static function (object $row) use ($number): string {
	if ($row->clock_value !== null) return trim($number($row->clock_value) . ' ' . (string) $row->clock_unit);
	return $row->phase_code ? trim((string) $row->phase_code . ($row->phase_sequence ? ' ' . (int) $row->phase_sequence : '')) : '';
};
?>
<div class="com-joomleague-eventreport">
<?php if (isset($data['error'])) : ?>
	<div class="alert alert-warning"><?php echo Text::_($data['error']); ?></div>
<?php else : ?>
	<?php
	$item = $data['item'];
	$participants = $data['participants'];
	$template = $data['template'];
	$participantNames = $lineups = $changes = [];
	foreach ($participants as $participant) $participantNames[(int) $participant->id] = (string) $participant->name;
	foreach ($data['lineup'] as $member) {
		$isPlayer = (string) $member->member_person_type === 'player';
		if (($isPlayer && !empty($template['show_lineups'])) || (!$isPlayer && !empty($template['show_staff']))) {
			$lineups[(int) $member->match_participant_id][] = $member;
		}
	}
	foreach ($data['substitutions'] as $change) $changes[(int) $change->match_participant_id][] = $change;
	$timezone = (string) ($item->timezone ?: Factory::getApplication()->get('offset', 'UTC'));
	$date = null;
	if ($item->scheduled_start) {
		try {
			$date = Factory::getDate((string) $item->scheduled_start, 'UTC');
			$date->setTimezone(new DateTimeZone($timezone));
		} catch (Throwable) {}
	}
	$title = $participants === [] ? Text::_('COM_JOOMLEAGUE_EVENTREPORT_VIEW_TITLE') : implode(' · ', array_values($participantNames));
	$structuredData = [
		'@context' => 'https://schema.org',
		'@type' => 'Event',
		'name' => $title,
		'eventStatus' => 'https://schema.org/EventScheduled',
		'location' => $item->venue_name ? ['@type' => 'Place', 'name' => (string) $item->venue_name] : null,
		'competitor' => array_map(static fn (object $participant): array => [
			'@type' => (string) $participant->entry_kind === 'person' ? 'Person' : 'Organization',
			'name' => (string) $participant->name,
		], $participants),
	];
	if ($date) $structuredData['startDate'] = $date->format(DATE_ATOM);
	$structuredData = array_filter($structuredData, static fn ($item): bool => $item !== null && $item !== []);
	?>
	<?php if (!empty($template['show_schema_org'])) : ?><script type="application/ld+json"><?php echo json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?></script><?php endif; ?>
	<header class="border-bottom pb-3 mb-4">
		<nav aria-label="<?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_CONTEXT'); ?>" class="mb-2">
			<a href="<?php echo Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $item->project_id); ?>"><?php echo $escape($item->project_name); ?></a>
			<span class="text-body-secondary mx-2">/</span><span><?php echo $escape($item->stage_name); ?></span>
			<span class="text-body-secondary mx-2">/</span><span><?php echo $escape($item->round_name); ?></span>
		</nav>
		<h1 class="mb-2"><?php echo $escape($title); ?></h1>
		<div class="d-flex flex-wrap gap-2">
			<span class="badge text-bg-primary"><?php echo $escape($item->sport_name); ?></span>
			<span class="badge text-bg-secondary"><?php echo $escape($item->competition_name); ?></span>
			<span class="badge text-bg-light border"><?php echo $escape($item->season_name); ?></span>
			<?php if ($item->match_number) : ?><span class="badge text-bg-light border"><?php echo Text::sprintf('COM_JOOMLEAGUE_EVENTREPORT_NUMBER', $escape($item->match_number)); ?></span><?php endif; ?>
		</div>
	</header>

	<div class="row row-cols-1 row-cols-md-3 g-3 mb-4">
		<?php if ($date) : ?><div class="col"><div class="card h-100"><div class="card-body"><div class="text-body-secondary small"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_DATE_TIME'); ?></div><strong><?php echo $escape($date->format(Text::_('DATE_FORMAT_LC2'))); ?></strong><div class="small text-body-secondary"><?php echo $escape($timezone); ?></div></div></div></div><?php endif; ?>
		<?php if ($item->venue_name) : ?><div class="col"><div class="card h-100"><div class="card-body"><div class="text-body-secondary small"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_VENUE'); ?></div><a href="<?php echo Route::_('index.php?option=com_joomleague&view=venue&venue_id=' . (int) $item->venue_id); ?>"><strong><?php echo $escape($item->venue_name); ?></strong></a></div></div></div><?php endif; ?>
		<div class="col"><div class="card h-100"><div class="card-body"><div class="text-body-secondary small"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_STATUS'); ?></div><strong><?php echo Text::_('COM_JOOMLEAGUE_EVENT_STATUS_' . strtoupper((string) $item->status_code)); ?></strong><?php if ($item->duration_minutes) : ?><div class="small text-body-secondary"><?php echo Text::sprintf('COM_JOOMLEAGUE_EVENTREPORT_DURATION', (int) $item->duration_minutes); ?></div><?php endif; ?></div></div></div>
	</div>

	<section class="mb-4"><h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_PARTICIPANTS'); ?></h2>
	<?php if ($participants === []) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_NO_PARTICIPANTS'); ?></div><?php else : ?><div class="row row-cols-1 row-cols-md-2 g-3">
	<?php foreach ($participants as $participant) : ?><div class="col"><div class="card h-100"><div class="card-body d-flex align-items-center justify-content-between gap-3"><div><span class="badge text-bg-light border me-2"><?php echo (int) $participant->slot_number; ?></span><?php if ($participant->team_id) : ?><a class="fw-semibold" href="<?php echo Route::_('index.php?option=com_joomleague&view=team&team_id=' . (int) $participant->team_id); ?>"><?php echo $escape($participant->name); ?></a><?php elseif ($participant->person_id) : ?><a class="fw-semibold" href="<?php echo Route::_('index.php?option=com_joomleague&view=person&person_id=' . (int) $participant->person_id); ?>"><?php echo $escape($participant->name); ?></a><?php else : ?><strong><?php echo $escape($participant->name); ?></strong><?php endif; ?></div><?php if ($participant->result_rank) : ?><span class="badge text-bg-primary"><?php echo Text::sprintf('COM_JOOMLEAGUE_EVENTREPORT_RANK', (int) $participant->result_rank); ?></span><?php endif; ?></div></div></div><?php endforeach; ?>
	</div><?php endif; ?></section>

	<?php if ($data['segments'] !== []) : ?><section class="mb-4"><h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_RESULT'); ?></h2><div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_SEGMENT'); ?></th><?php foreach ($participants as $participant) : ?><th class="text-end"><?php echo $escape($participant->name); ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach ($data['segments'] as $segment) : ?><tr><th><?php echo $escape($segment->level_code); ?><?php if ($segment->parent_id !== null) echo ' ' . (int) $segment->sequence_number; ?></th><?php foreach ($participants as $participant) : $score = $data['valuesBySegment'][(int) $segment->id][(int) $participant->id] ?? null; ?><td class="text-end fw-semibold"><?php echo $score ? $escape($value($score)) : Text::_('JNONE'); ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div><?php if ($item->result_notes) : ?><p class="text-body-secondary"><?php echo nl2br($escape($item->result_notes)); ?></p><?php endif; ?></section><?php endif; ?>

	<?php if ($lineups !== []) : ?><section class="mb-4"><h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_LINEUP'); ?></h2><div class="row row-cols-1 row-cols-lg-2 g-3"><?php foreach ($lineups as $participantId => $members) : ?><div class="col"><div class="card h-100"><div class="card-header fw-semibold"><?php echo $escape($participantNames[$participantId] ?? Text::_('COM_JOOMLEAGUE_EVENTREPORT_PARTICIPANT')); ?></div><ul class="list-group list-group-flush"><?php foreach ($members as $member) : ?><li class="list-group-item d-flex justify-content-between gap-3"><span><?php if ($member->shirt_number) : ?><span class="badge text-bg-light border me-2"><?php echo $escape($member->shirt_number); ?></span><?php endif; ?><?php echo $escape($member->name); ?><?php if ($member->is_captain) : ?> <span class="badge text-bg-secondary"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_CAPTAIN'); ?></span><?php endif; ?></span><span class="text-body-secondary"><?php echo $escape($member->role_code); ?></span></li><?php endforeach; ?></ul></div></div><?php endforeach; ?></div></section><?php endif; ?>

	<?php if (!empty($template['show_timeline']) && $changes !== []) : ?><section class="mb-4"><h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_SUBSTITUTIONS'); ?></h2><div class="list-group"><?php foreach ($changes as $participantId => $items) : foreach ($items as $change) : ?><div class="list-group-item d-flex justify-content-between gap-3"><div><strong><?php echo $escape($participantNames[$participantId] ?? Text::_('COM_JOOMLEAGUE_EVENTREPORT_PARTICIPANT')); ?></strong><div><?php echo $escape($change->outgoing_name); ?> <span aria-hidden="true">→</span> <?php echo $escape($change->incoming_name); ?></div></div><?php if ($clock($change) !== '') : ?><span class="badge text-bg-light border align-self-center"><?php echo $escape($clock($change)); ?></span><?php endif; ?></div><?php endforeach; endforeach; ?></div></section><?php endif; ?>

	<?php if (!empty($template['show_timeline']) && $data['events'] !== []) : ?><section class="mb-4"><h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_EVENTS'); ?></h2><div class="list-group"><?php foreach ($data['events'] as $event) : ?><div class="list-group-item d-flex justify-content-between gap-3"><div><strong><?php echo $escape(Text::_((string) $event->event_name_key)); ?></strong><?php if ($event->match_participant_id && isset($participantNames[(int) $event->match_participant_id])) : ?><span class="text-body-secondary"> · <?php echo $escape($participantNames[(int) $event->match_participant_id]); ?></span><?php endif; ?><?php $names = array_filter([(string) $event->primary_name_snapshot, (string) $event->secondary_name_snapshot, (string) $event->actor_name_snapshot]); if ($names !== []) : ?><div><?php echo $escape(implode(' · ', $names)); ?></div><?php endif; ?></div><?php if ($clock($event) !== '') : ?><span class="badge text-bg-light border align-self-center"><?php echo $escape($clock($event)); ?></span><?php endif; ?></div><?php endforeach; ?></div></section><?php endif; ?>

	<?php if ($data['statistics'] !== []) : ?><section class="mb-4"><h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_STATISTICS'); ?></h2><div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_STATISTIC'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_TARGET'); ?></th><th class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_VALUE'); ?></th></tr></thead><tbody><?php foreach ($data['statistics'] as $statistic) : ?><tr><th><?php echo $escape(Text::_((string) $statistic->statistic_name_key)); ?></th><td><?php echo $escape($statistic->target_name_snapshot); ?></td><td class="text-end fw-semibold"><?php echo $escape($statistic->numeric_value !== null ? $number($statistic->numeric_value) : $statistic->text_value); ?></td></tr><?php endforeach; ?></tbody></table></div></section><?php endif; ?>

	<?php if (!empty($template['show_officials']) && $data['officials'] !== []) : ?><section class="mb-4"><h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_OFFICIALS'); ?></h2><div class="table-responsive"><table class="table align-middle"><tbody><?php foreach ($data['officials'] as $official) : ?><tr><th><?php echo $escape($official->role_code); ?></th><td><?php echo $escape($official->display_name_snapshot); ?></td></tr><?php endforeach; ?></tbody></table></div></section><?php endif; ?>
	<?php if ($item->description) : ?><section><h2 class="h4"><?php echo Text::_('COM_JOOMLEAGUE_EVENTREPORT_DESCRIPTION'); ?></h2><div><?php echo nl2br($escape($item->description)); ?></div></section><?php endif; ?>
<?php endif; ?>
</div>
