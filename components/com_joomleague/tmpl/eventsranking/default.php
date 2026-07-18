<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomleague\Component\Joomleague\Site\Service\StructuredDataHelper;

$project = $this->project;
$rowsByEvent = [];
$translateLegacyName = static function ($value): string {
	$value = trim((string) $value);

	return preg_match('/^(COM|JLM)_[A-Z0-9_-]+$/', $value) ? Text::_($value) : $value;
};

$params = $this->templateParams;
$showSectionheader = (bool) ($params['show_sectionheader'] ?? true);
$maxEvents = max(1, (int) ($params['max_events'] ?? 20));
$linkToPlayer = (bool) ($params['link_to_player'] ?? true);
$linkToTeam = (bool) ($params['link_to_team'] ?? true);

foreach ($this->items as $row) {
	$rowsByEvent[(int) $row->event_type_id][] = $row;
}

if ($project) {
	StructuredDataHelper::add($this->getDocument(), [
		'@context' => 'https://schema.org',
		'@type' => 'Dataset',
		'@id' => StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=eventsranking&project_id=' . (int) $project->id, false)) . '#dataset',
		'name' => Text::_('COM_JOOMLEAGUE_SITE_EVENTS_RANKING') . ' - ' . (string) $project->name,
		'url' => StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=eventsranking&project_id=' . (int) $project->id, false)),
		'isPartOf' => [
			'@type' => 'SportsOrganization',
			'@id' => StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $project->id, false)) . '#competition',
			'name' => (string) $project->name,
		],
		'mainEntityOfPage' => StructuredDataHelper::collectionPage(
			Text::_('COM_JOOMLEAGUE_SITE_EVENTS_RANKING'),
			array_map(
				static fn (object $row): array => [
					'@type' => 'Observation',
					'name' => $translateLegacyName($row->event_name ?? ''),
					'value' => (string) ($row->value ?? ''),
					'about' => array_values(array_filter([
						!empty($row->person_id) ? [
							'@type' => 'Person',
							'@id' => StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $row->person_id . '&project_id=' . (int) $project->id, false)) . '#person',
							'name' => (string) (($row->person_name ?? '') ?: ($row->nickname ?? '')),
						] : null,
						!empty($row->projectteam_id) ? [
							'@type' => 'SportsTeam',
							'@id' => StructuredDataHelper::absoluteUrl(Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $row->projectteam_id, false)) . '#sportsteam',
							'name' => (string) ($row->team_name ?? ''),
						] : null,
					])),
				],
				$this->items
			),
			$this->projectLabel($project)
		),
	]);
}
?>
<div class="com-joomleague-site">
	<?php if (!$project) : ?><div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT_NOT_FOUND'); ?></div><?php return; endif; ?>
	<?php if ($showSectionheader) : ?>
	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo $this->escape($this->projectLabel($project)); ?></div>
		<h1 class="jl-site-title"><?php echo Text::_('COM_JOOMLEAGUE_SITE_EVENTS_RANKING'); ?></h1>
	</section>
	<?php endif; ?>

	<?php foreach ($this->eventTypes as $eventType) : ?>
		<?php $eventRows = \array_slice($rowsByEvent[(int) $eventType->id] ?? [], 0, $maxEvents); ?>
		<div class="jl-site-panel table-responsive mb-4">
			<h2><?php echo $this->escape($translateLegacyName($eventType->name)); ?></h2>
			<table class="table jl-site-table align-middle">
				<thead>
					<tr>
						<th>#</th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PERSON'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_TEAM'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_VALUE'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($eventRows as $i => $row) : ?>
						<tr>
							<td><?php echo $i + 1; ?></td>
							<td><?php if ($linkToPlayer && $row->person_id) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $row->person_id . '&project_id=' . (int) $project->id); ?>"><?php echo $this->escape($row->person_name ?: $row->nickname); ?></a><?php elseif ($row->person_id) : ?><?php echo $this->escape($row->person_name ?: $row->nickname); ?><?php else : ?><?php echo Text::_('COM_JOOMLEAGUE_SITE_NOT_SET'); ?><?php endif; ?></td>
							<td><?php if ($linkToTeam && $row->projectteam_id) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $row->projectteam_id); ?>"><?php echo $this->escape($row->team_name ?? ''); ?></a><?php else : ?><?php echo $this->escape($row->team_name ?? ''); ?><?php endif; ?></td>
							<td><strong><?php echo (int) $row->value; ?></strong></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php if (!$eventRows) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></div><?php endif; ?>
		</div>
	<?php endforeach; ?>

	<?php if (!$this->eventTypes) : ?><div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_SITE_NO_DATA'); ?></div><?php endif; ?>
</div>
