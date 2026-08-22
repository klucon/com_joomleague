<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.multiselect'); $user = Factory::getApplication()->getIdentity(); $roundId = (int) $this->round->id; $listOrder = $this->escape($this->state->get('list.ordering')); $listDirn = $this->escape($this->state->get('list.direction')); $model = $this->getModel(); $headToHead = $this->contestType === 'head_to_head';
$matchReturn = (string) (Factory::getApplication()->getInput()->get('return', '', 'base64') ?? '');
$matchReturnAppend = $matchReturn !== '' ? '&return=' . $matchReturn : '';
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=matches&round_id=' . $roundId); ?>" method="post" name="adminForm" id="adminForm">
	<div class="alert alert-info" role="status"><strong><?php echo $this->escape($this->round->project_name); ?></strong><span class="mx-2">/</span><?php echo $this->escape($this->round->stage_name); ?><span class="mx-2">/</span><?php echo $this->escape($this->round->name); ?></div><?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
	<div class="table-responsive">
		<table class="table table-striped align-middle" id="matchList" data-round-id="<?php echo $roundId; ?>" data-saving="<?php echo $this->escape(Text::_('COM_JOOMLEAGUE_MATCH_AUTOSAVE_SAVING')); ?>" data-saved="<?php echo $this->escape(Text::_('COM_JOOMLEAGUE_MATCH_AUTOSAVE_SAVED')); ?>" data-failed="<?php echo $this->escape(Text::_('COM_JOOMLEAGUE_MATCH_AUTOSAVE_FAILED')); ?>">
			<thead>
				<tr>
					<td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
					<th class="w-1"><span class="visually-hidden"><?php echo Text::_('JACTION_EDIT'); ?></span></th>
					<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_JOOMLEAGUE_FIELD_MATCH_NUMBER_SHORT_LABEL', 'a.match_number', $listDirn, $listOrder); ?></th>
					<?php if ($headToHead) : ?>
						<th><?php echo Text::_('COM_JOOMLEAGUE_MATCH_COLUMN_HOME'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_MATCH_COLUMN_AWAY'); ?></th>
					<?php else : ?>
						<th><?php echo Text::_('COM_JOOMLEAGUE_MATCH_COLUMN_PARTICIPANTS'); ?></th>
					<?php endif; ?>
					<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_JOOMLEAGUE_MATCH_COLUMN_DATE', 'a.scheduled_start', $listDirn, $listOrder); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_MATCH_COLUMN_TIME'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_ATTENDANCE_LABEL'); ?></th>
					<th class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_MATCHRESULT_COLUMN_RESULT'); ?></th>
					<th class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_MATCH_COLUMN_ACTIONS'); ?></th>
					<th class="text-center"><?php echo Text::_('JSTATUS'); ?></th>
					<th class="text-center"><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($this->items as $i => $item) : ?>
				<?php
				$canEdit = $user->authorise('core.edit', 'com_joomleague');
				$canChange = $user->authorise('core.edit.state', 'com_joomleague');
				$checkedOut = (int) $item->checked_out !== 0 && (int) $item->checked_out !== (int) $user->id;
				$resultSummary = $model->displayResultSummary($item);
				$resultStatus = (string) ($item->result_status_code ?? '');
				$resultBadge = $resultStatus === 'final' ? 'bg-success' : ($resultStatus === 'in_progress' ? 'bg-info text-dark' : 'bg-secondary');
				$statusKey = 'COM_JOOMLEAGUE_MATCH_STATUS_' . strtoupper((string) $item->status_code);
				$statusLabel = Text::_($statusKey);
				if ($statusLabel === $statusKey) $statusLabel = (string) $item->status_code;
				$canInlineEdit = $canEdit && !$checkedOut;
				$homeEntry = (int) ($item->participant_entries[1] ?? 0);
				$awayEntry = (int) ($item->participant_entries[2] ?? 0);
				?>
				<tr data-match-id="<?php echo (int) $item->id; ?>" data-editable="<?php echo $canInlineEdit ? '1' : '0'; ?>">
					<td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?></td>
					<td><?php if ($canEdit && !$checkedOut) : ?><a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&task=match.edit&id=' . (int) $item->id . '&round_id=' . $roundId . $matchReturnAppend); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?>"><span class="icon-edit" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('JACTION_EDIT'); ?></span></a><?php endif; ?></td>
					<td><input class="form-control form-control-sm" type="text" value="<?php echo $this->escape((string) $item->match_number); ?>" data-initial-value="<?php echo $this->escape((string) $item->match_number); ?>" data-schedule-field="match_number" maxlength="100" <?php echo $canInlineEdit ? '' : 'disabled'; ?>></td>
					<?php if ($headToHead) : ?>
						<td><select class="form-select form-select-sm" data-schedule-field="participant_slot_1" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_MATCH_COLUMN_HOME'); ?>" <?php echo $canInlineEdit ? '' : 'disabled'; ?>><option value=""><?php echo Text::_('COM_JOOMLEAGUE_OPTION_SELECT_PARTICIPANT'); ?></option><?php foreach ($this->entryOptions as $option) : ?><option value="<?php echo (int) $option->value; ?>" <?php echo (int) $option->value === $homeEntry ? 'selected' : ''; ?>><?php echo $this->escape($option->text); ?></option><?php endforeach; ?></select></td>
						<td><select class="form-select form-select-sm" data-schedule-field="participant_slot_2" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_MATCH_COLUMN_AWAY'); ?>" <?php echo $canInlineEdit ? '' : 'disabled'; ?>><option value=""><?php echo Text::_('COM_JOOMLEAGUE_OPTION_SELECT_PARTICIPANT'); ?></option><?php foreach ($this->entryOptions as $option) : ?><option value="<?php echo (int) $option->value; ?>" <?php echo (int) $option->value === $awayEntry ? 'selected' : ''; ?>><?php echo $this->escape($option->text); ?></option><?php endforeach; ?></select></td>
					<?php else : ?>
						<td class="text-center">
							<span class="badge text-bg-light border"><?php echo count($item->participant_details); ?></span>
							<a class="btn btn-sm btn-outline-secondary ms-2" href="<?php echo Route::_('index.php?option=com_joomleague&view=matchparticipants&match_id=' . (int) $item->id); ?>"><?php echo Text::_('COM_JOOMLEAGUE_MATCHPARTICIPANTS_MANAGE'); ?></a>
						</td>
					<?php endif; ?>
					<td><input class="form-control form-control-sm" type="date" value="<?php echo $this->escape($item->scheduled_date_local); ?>" data-initial-value="<?php echo $this->escape($item->scheduled_date_local); ?>" data-schedule-field="scheduled_date" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_MATCH_COLUMN_DATE'); ?>" <?php echo $canInlineEdit ? '' : 'disabled'; ?>></td>
					<td><input class="form-control form-control-sm" type="time" value="<?php echo $this->escape($item->scheduled_time_local); ?>" data-initial-value="<?php echo $this->escape($item->scheduled_time_local); ?>" data-schedule-field="scheduled_time" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_MATCH_COLUMN_TIME'); ?>" <?php echo $canInlineEdit ? '' : 'disabled'; ?>></td>
					<td><input class="form-control form-control-sm" type="number" min="0" value="<?php echo $item->attendance === null ? '' : (int) $item->attendance; ?>" data-initial-value="<?php echo $item->attendance === null ? '' : (int) $item->attendance; ?>" data-schedule-field="attendance" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_FIELD_ATTENDANCE_LABEL'); ?>" <?php echo $canInlineEdit ? '' : 'disabled'; ?>><div data-save-status class="small text-body-secondary text-nowrap" aria-live="polite"></div></td>
					<td class="text-center"><?php if ($resultStatus !== '') : ?><a class="fw-semibold" href="<?php echo Route::_('index.php?option=com_joomleague&view=matchresult&match_id=' . (int) $item->id); ?>"><?php echo $this->escape($resultSummary ?: Text::_('JNONE')); ?></a><div><span class="badge <?php echo $resultBadge; ?>"><?php echo $this->escape(Text::_('COM_JOOMLEAGUE_RESULT_CODE_' . strtoupper($resultStatus))); ?></span></div><?php else : ?><?php echo Text::_('JNONE'); ?><?php endif; ?></td>
					<td class="text-center"><div class="btn-group" role="group" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_MATCH_COLUMN_ACTIONS'); ?>">
						<a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=matchlineup&match_id=' . (int) $item->id); ?>" title="<?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_OPEN'); ?>"><span class="icon-users" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_MATCHLINEUP_OPEN'); ?></span></a>
						<a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=matchofficials&match_id=' . (int) $item->id); ?>" title="<?php echo Text::_('COM_JOOMLEAGUE_MATCHOFFICIALS_OPEN'); ?>"><span class="icon-user" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_MATCHOFFICIALS_OPEN'); ?></span></a>
						<a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=matchevents&match_id=' . (int) $item->id); ?>" title="<?php echo Text::_('COM_JOOMLEAGUE_MATCHEVENTS_OPEN'); ?>"><span class="icon-bolt" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_MATCHEVENTS_OPEN'); ?></span></a>
						<a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=matchstatistics&match_id=' . (int) $item->id); ?>" title="<?php echo Text::_('COM_JOOMLEAGUE_MATCHSTATISTICS_OPEN'); ?>"><span class="icon-chart" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_MATCHSTATISTICS_OPEN'); ?></span></a>
						<a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=matchresult&match_id=' . (int) $item->id); ?>" title="<?php echo Text::_('COM_JOOMLEAGUE_MATCHRESULT_OPEN'); ?>"><span class="icon-trophy" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_MATCHRESULT_OPEN'); ?></span></a>
					</div></td>
					<td class="text-center"><?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'matches.', $canChange, 'cb'); ?></td>
					<td class="text-center"><?php echo (int) $item->id; ?></td>
				</tr>
			<?php endforeach; ?>
			<?php if ($this->items === []) : ?><tr><td colspan="<?php echo $headToHead ? 13 : 12; ?>"><div class="alert alert-warning mb-0"><?php echo Text::_('COM_JOOMLEAGUE_MATCHES_EMPTY'); ?></div></td></tr><?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php echo $this->pagination->getListFooter(); ?>
	<input type="hidden" name="task" value=""><input type="hidden" name="boxchecked" value="0"><input type="hidden" name="round_id" value="<?php echo $roundId; ?>"><input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"><input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"><?php echo HTMLHelper::_('form.token'); ?>
	<?php if ($user->authorise('core.edit', 'com_joomleague')) : ?>
	<div class="modal fade" id="matches-batch-modal" tabindex="-1" aria-labelledby="matches-batch-modal-label" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h2 class="modal-title fs-5" id="matches-batch-modal-label"><?php echo Text::_('COM_JOOMLEAGUE_MATCHES_BATCH_TITLE'); ?></h2>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
				</div>
				<div class="modal-body">
					<p class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_MATCHES_BATCH_DESC'); ?></p>
					<div class="mb-3">
						<label class="form-label" for="batch_venue_id"><?php echo Text::_('COM_JOOMLEAGUE_MATCHES_BATCH_VENUE_LABEL'); ?></label>
						<select class="form-select" id="batch_venue_id" name="batch_venue_id">
							<option value=""><?php echo Text::_('COM_JOOMLEAGUE_MATCHES_BATCH_VENUE_NO_CHANGE'); ?></option>
							<?php foreach ($this->venueOptions as $venue) : ?>
								<option value="<?php echo (int) $venue->value; ?>"><?php echo $this->escape($venue->text); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="mb-3">
						<label class="form-label" for="batch_shift_days"><?php echo Text::_('COM_JOOMLEAGUE_MATCHES_BATCH_SHIFT_LABEL'); ?></label>
						<input type="number" class="form-control" id="batch_shift_days" name="batch_shift_days" step="1">
						<div class="form-text"><?php echo Text::_('COM_JOOMLEAGUE_MATCHES_BATCH_SHIFT_DESC'); ?></div>
					</div>
					<div id="matches-batch-warning" class="alert alert-warning d-none" role="alert" data-none-selected="<?php echo $this->escape(Text::_('COM_JOOMLEAGUE_MATCHES_BATCH_NONE_SELECTED')); ?>" data-no-changes="<?php echo $this->escape(Text::_('COM_JOOMLEAGUE_MATCHES_BATCH_NO_CHANGES')); ?>"></div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Text::_('JCANCEL'); ?></button>
					<button type="button" class="btn btn-primary" id="matches-batch-apply"><?php echo Text::_('COM_JOOMLEAGUE_MATCHES_BATCH_APPLY'); ?></button>
				</div>
			</div>
		</div>
	</div>
	<?php endif; ?>
</form>
