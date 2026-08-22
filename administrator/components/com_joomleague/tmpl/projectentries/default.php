<?php

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$kindLabels = [
	'team' => 'COM_JOOMLEAGUE_ENTRY_KIND_TEAM',
	'person' => 'COM_JOOMLEAGUE_ENTRY_KIND_PERSON',
	'group' => 'COM_JOOMLEAGUE_ENTRY_KIND_GROUP',
];
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=projectentries&project_id=' . (int) $this->project->id); ?>" method="post" name="adminForm" id="adminForm">
	<div class="container-fluid">
	<div class="alert alert-info" role="status">
		<h2 class="h4 mb-2"><?php echo $this->escape($this->project->name); ?></h2>
		<p class="mb-2"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTENTRIES_INTRO'); ?></p>
		<div class="d-flex flex-wrap gap-2">
			<?php foreach ($this->entryModel['allowed_kinds'] as $kind) : ?>
				<span class="badge bg-info text-dark"><?php echo Text::_($kindLabels[$kind]); ?></span>
			<?php endforeach; ?>
			<?php if ($this->entryModel['members_supported']) : ?>
				<span class="badge bg-success"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTENTRIES_MEMBERS_SUPPORTED'); ?></span>
			<?php endif; ?>
		</div>
	</div>

	<div class="row g-2 mb-3">
		<div class="col-auto">
			<label class="visually-hidden" for="search"><?php echo Text::_('COM_JOOMLEAGUE_FILTER_SEARCH_LABEL'); ?></label>
			<input type="text" class="form-control form-control-sm" id="search" name="search" value="<?php echo $this->escape($this->search); ?>" placeholder="<?php echo Text::_('COM_JOOMLEAGUE_FILTER_SEARCH_LABEL'); ?>">
		</div>
		<div class="col-auto">
			<label class="visually-hidden" for="lifecycle_state"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_LIFECYCLE_STATE_LABEL'); ?></label>
			<select class="form-select form-select-sm" id="lifecycle_state" name="lifecycle_state" onchange="this.form.submit();">
				<option value=""><?php echo Text::_('COM_JOOMLEAGUE_OPTION_SELECT_LIFECYCLE'); ?></option>
				<?php foreach (['active', 'inactive', 'withdrawn', 'disqualified'] as $state) : ?>
					<option value="<?php echo $state; ?>" <?php echo $state === $this->lifecycleState ? 'selected' : ''; ?>><?php echo Text::_('COM_JOOMLEAGUE_LIFECYCLE_' . strtoupper($state)); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="col-auto">
			<button type="submit" class="btn btn-sm btn-secondary"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
			<?php if ($this->search !== '' || $this->lifecycleState !== '') : ?>
				<a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=projectentries&project_id=' . (int) $this->project->id); ?>"><?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?></a>
			<?php endif; ?>
		</div>
	</div>

	<div class="table-responsive">
		<table class="table table-striped">
			<thead><tr>
				<td class="text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
				<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_NAME_LABEL'); ?></th>
				<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTENTRIES_COLUMN_KIND'); ?></th>
				<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTENTRIES_COLUMN_CODE'); ?></th>
				<th scope="col" class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTENTRIES_COLUMN_SEED'); ?></th>
				<th scope="col" class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTENTRIES_COLUMN_MEMBERS'); ?></th>
				<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_LIFECYCLE_STATE_LABEL'); ?></th>
				<th scope="col" class="text-center"><?php echo Text::_('JGRID_HEADING_ID'); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ($this->entries as $index => $entry) : ?>
				<tr>
					<td class="text-center"><?php echo HTMLHelper::_('grid.id', $index, (int) $entry->id); ?></td>
					<th scope="row">
						<?php if ($this->canEdit) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&task=projectentry.edit&id=' . (int) $entry->id . '&project_id=' . (int) $this->project->id); ?>"><?php echo $this->escape($entry->resolved_name); ?></a><?php else : ?><?php echo $this->escape($entry->resolved_name); ?><?php endif; ?>
					</th>
					<td><?php echo Text::_($kindLabels[$entry->entry_kind]); ?></td>
					<td><?php echo $this->escape((string) $entry->entry_code); ?></td>
					<td class="text-center"><?php echo $entry->seed_number === null ? Text::_('JNONE') : (int) $entry->seed_number; ?></td>
					<td class="text-center"><?php if ($this->entryModel['members_supported']) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&view=entrymembers&entry_id=' . (int) $entry->id); ?>" aria-label="<?php echo $this->escape(Text::sprintf('COM_JOOMLEAGUE_ENTRYMEMBERS_OPEN', $entry->resolved_name)); ?>"><?php echo (int) $entry->member_count; ?></a><?php else : ?><?php echo Text::_('JNONE'); ?><?php endif; ?></td>
					<td><?php echo Text::_('COM_JOOMLEAGUE_LIFECYCLE_' . strtoupper((string) $entry->lifecycle_state)); ?></td>
					<td class="text-center"><?php echo (int) $entry->id; ?></td>
				</tr>
			<?php endforeach; ?>
			<?php if ($this->entries === []) : ?>
				<tr><td colspan="8"><div class="alert alert-warning mb-0"><?php echo Text::_('COM_JOOMLEAGUE_PROJECTENTRIES_EMPTY'); ?></div></td></tr>
			<?php endif; ?>
			</tbody>
		</table>
	</div>
	</div>
	<input type="hidden" name="task" value="">
	<input type="hidden" name="boxchecked" value="0">
	<input type="hidden" name="project_id" value="<?php echo (int) $this->project->id; ?>">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
