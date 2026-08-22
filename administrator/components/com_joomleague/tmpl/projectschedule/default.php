<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$projectId = (int) $this->project->id;
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
$stageId = (int) $this->state->get('filter.stage_id');
$roundId = (int) $this->state->get('filter.round_id');
// Uri::isInternal() requires an absolute URL (scheme+host), not the base-relative
// string Route::_() returns - see Joomla core's own use of this pattern in
// com_content's Icon::create() (Uri::getInstance() there is already absolute).
$scheduleReturn = base64_encode(Uri::getInstance()->toString(['scheme', 'host', 'port']) . Route::_('index.php?option=com_joomleague&view=projectschedule&project_id=' . $projectId, false));
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=projectschedule&project_id=' . $projectId); ?>" method="post" name="adminForm" id="adminForm">
	<div class="alert alert-info" role="status"><strong><?php echo $this->escape($this->project->name); ?></strong></div>

	<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

	<div class="row g-2 mb-3">
		<div class="col-auto">
			<label class="visually-hidden" for="filter_stage_id"><?php echo Text::_('COM_JOOMLEAGUE_FILTER_PROJECTSCHEDULE_STAGE_LABEL'); ?></label>
			<select class="form-select form-select-sm" id="filter_stage_id" name="filter[stage_id]" onchange="this.form.submit();">
				<option value="0"><?php echo Text::_('COM_JOOMLEAGUE_FILTER_PROJECTSCHEDULE_STAGE_ALL'); ?></option>
				<?php foreach ($this->stageOptions as $option) : ?>
					<option value="<?php echo (int) $option->value; ?>" <?php echo (int) $option->value === $stageId ? 'selected' : ''; ?>><?php echo $this->escape($option->text); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="col-auto">
			<label class="visually-hidden" for="filter_round_id"><?php echo Text::_('COM_JOOMLEAGUE_FILTER_PROJECTSCHEDULE_ROUND_LABEL'); ?></label>
			<select class="form-select form-select-sm" id="filter_round_id" name="filter[round_id]" onchange="this.form.submit();">
				<option value="0"><?php echo Text::_('COM_JOOMLEAGUE_FILTER_PROJECTSCHEDULE_ROUND_ALL'); ?></option>
				<?php foreach ($this->roundOptions as $option) : ?>
					<?php if ($stageId > 0 && (int) $option->stage_id !== $stageId) : ?><?php continue; ?><?php endif; ?>
					<option value="<?php echo (int) $option->value; ?>" <?php echo (int) $option->value === $roundId ? 'selected' : ''; ?>><?php echo $this->escape($option->text); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>

	<div class="table-responsive">
		<table class="table table-striped align-middle">
			<thead>
				<tr>
					<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_JOOMLEAGUE_FIELD_MATCH_NUMBER_SHORT_LABEL', 'a.match_number', $listDirn, $listOrder); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_PROJECTSCHEDULE_COLUMN_STAGE_ROUND'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_MATCH_COLUMN_PARTICIPANTS'); ?></th>
					<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_JOOMLEAGUE_MATCH_COLUMN_DATE', 'a.scheduled_start', $listDirn, $listOrder); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_PROJECTSCHEDULE_COLUMN_VENUE'); ?></th>
					<th class="text-center"><?php echo Text::_('JSTATUS'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_PROJECTSCHEDULE_COLUMN_RESULT'); ?></th>
					<th class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_MATCH_COLUMN_ACTIONS'); ?></th>
					<th class="text-center"><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($this->items as $item) : ?>
				<?php
				$statusKey = 'COM_JOOMLEAGUE_MATCH_STATUS_' . strtoupper((string) $item->status_code);
				$statusLabel = Text::_($statusKey);
				if ($statusLabel === $statusKey) {
					$statusLabel = (string) $item->status_code;
				}
				?>
				<tr>
					<td><?php echo $this->escape((string) $item->match_number); ?></td>
					<td><?php echo $this->escape((string) $item->stage_name); ?><span class="mx-1">/</span><?php echo $this->escape((string) $item->round_name); ?></td>
					<td><?php echo $item->participant_names === [] ? Text::_('JNONE') : $this->escape(implode(' – ', $item->participant_names)); ?></td>
					<td><?php echo $this->escape((string) $item->scheduled_display); ?></td>
					<td><?php echo $this->escape((string) ($item->venue_name ?? '')); ?></td>
					<td class="text-center"><span class="badge bg-secondary"><?php echo $this->escape($statusLabel); ?></span></td>
					<td><?php echo $this->escape((string) ($item->result_display ?? '')); ?></td>
					<td class="text-center"><a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=matches&round_id=' . (int) $item->round_id . '&return=' . $scheduleReturn); ?>"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTSCHEDULE_OPEN_ROUND'); ?></a></td>
					<td class="text-center"><?php echo (int) $item->id; ?></td>
				</tr>
			<?php endforeach; ?>
			<?php if ($this->items === []) : ?>
				<tr><td colspan="9"><div class="alert alert-warning mb-0"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTSCHEDULE_EMPTY'); ?></div></td></tr>
			<?php endif; ?>
			</tbody>
		</table>
	</div>

	<div style="overflow-x: auto;"><?php echo $this->pagination->getListFooter(); ?></div>

	<input type="hidden" name="task" value="">
	<input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
