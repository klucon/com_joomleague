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

$project = $this->project;
$rowsByEvent = [];
$translateLegacyName = static function ($value): string {
	$value = trim((string) $value);

	return preg_match('/^(COM|JLM)_[A-Z0-9_-]+$/', $value) ? Text::_($value) : $value;
};

foreach ($this->items as $row) {
	$rowsByEvent[(int) $row->event_type_id][] = $row;
}
?>
<div class="com-joomleague-site">
	<?php if (!$project) : ?><div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT_NOT_FOUND'); ?></div><?php return; endif; ?>
	<section class="jl-site-hero mb-4">
		<div class="jl-site-eyebrow"><?php echo $this->escape($project->name); ?></div>
		<h1 class="jl-site-title"><?php echo Text::_('COM_JOOMLEAGUE_SITE_EVENTS_RANKING'); ?></h1>
	</section>

	<?php foreach ($this->eventTypes as $eventType) : ?>
		<?php $eventRows = $rowsByEvent[(int) $eventType->id] ?? []; ?>
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
							<td><?php if ($row->person_id) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=person&id=' . (int) $row->person_id . '&project_id=' . (int) $project->id); ?>"><?php echo $this->escape($row->person_name ?: $row->nickname); ?></a><?php else : ?><?php echo Text::_('COM_JOOMLEAGUE_SITE_NOT_SET'); ?><?php endif; ?></td>
							<td><?php if ($row->projectteam_id) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=team&id=' . (int) $row->projectteam_id); ?>"><?php echo $this->escape($row->team_name ?? ''); ?></a><?php else : ?><?php echo $this->escape($row->team_name ?? ''); ?><?php endif; ?></td>
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
