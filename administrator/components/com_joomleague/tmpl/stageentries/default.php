<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$inherit = $this->stage->entry_selection_mode === 'inherit_project';
$kindLabels = ['team' => 'COM_JOOMLEAGUE_ENTRY_KIND_TEAM', 'person' => 'COM_JOOMLEAGUE_ENTRY_KIND_PERSON', 'group' => 'COM_JOOMLEAGUE_ENTRY_KIND_GROUP'];
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=stageentries&stage_id=' . (int) $this->stage->id); ?>" method="post" name="adminForm" id="adminForm">
	<div class="main-card">
		<div class="alert alert-info" role="status"><strong><?php echo $this->escape($this->stage->project_name); ?></strong><span class="mx-2">/</span><?php echo $this->escape($this->stage->name); ?></div>
		<fieldset class="options-form mb-4"><legend><?php echo Text::_('COM_JOOMLEAGUE_FIELD_STAGE_ENTRY_MODE_LABEL'); ?></legend>
			<p><?php echo Text::_('COM_JOOMLEAGUE_FIELD_STAGE_ENTRY_MODE_DESC'); ?></p>
			<div class="btn-group" role="group" aria-label="<?php echo Text::_('COM_JOOMLEAGUE_FIELD_STAGE_ENTRY_MODE_LABEL'); ?>">
				<input class="btn-check" type="radio" name="entry_selection_mode" id="entry-mode-inherit" value="inherit_project"<?php echo $inherit ? ' checked' : ''; ?>><label class="btn btn-outline-secondary" for="entry-mode-inherit"><?php echo Text::_('COM_JOOMLEAGUE_STAGE_ENTRY_MODE_INHERIT'); ?></label>
				<input class="btn-check" type="radio" name="entry_selection_mode" id="entry-mode-explicit" value="explicit"<?php echo !$inherit ? ' checked' : ''; ?>><label class="btn btn-outline-secondary" for="entry-mode-explicit"><?php echo Text::_('COM_JOOMLEAGUE_STAGE_ENTRY_MODE_EXPLICIT'); ?></label>
			</div>
		</fieldset>
		<div class="table-responsive"><table class="table table-striped"><thead><tr><th class="w-1 text-center"><span class="visually-hidden"><?php echo Text::_('JSELECT'); ?></span></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_NAME_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_PROJECTENTRIES_COLUMN_KIND'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_PROJECTENTRIES_COLUMN_CODE'); ?></th><th class="text-center"><?php echo Text::_('JSTATUS'); ?></th></tr></thead><tbody>
		<?php foreach ($this->entries as $entry) :
			$name = $entry->entry_kind === 'team' ? $entry->team_name : ($entry->entry_kind === 'person' ? trim($entry->first_name . ' ' . $entry->last_name) : $entry->display_name);
			$checked = $inherit || (int) $entry->assigned === 1;
		?>
			<tr><td class="text-center"><input class="form-check-input" type="checkbox" name="entry_ids[]" value="<?php echo (int) $entry->id; ?>"<?php echo $checked ? ' checked' : ''; ?><?php echo $this->canEdit ? '' : ' disabled'; ?> aria-label="<?php echo Text::sprintf('COM_JOOMLEAGUE_STAGE_ENTRY_SELECT', $this->escape($name)); ?>"></td><th scope="row"><?php echo $this->escape($name); ?></th><td><?php echo Text::_($kindLabels[$entry->entry_kind]); ?></td><td><?php echo $this->escape($entry->entry_code ?: Text::_('JNONE')); ?></td><td class="text-center"><?php echo Text::_((int) $entry->published === 1 ? 'JPUBLISHED' : 'JUNPUBLISHED'); ?></td></tr>
		<?php endforeach; ?>
		<?php if ($this->entries === []) : ?><tr><td colspan="5"><div class="alert alert-warning mb-0"><?php echo Text::_('COM_JOOMLEAGUE_STAGE_ENTRIES_EMPTY'); ?></div></td></tr><?php endif; ?>
		</tbody></table></div>
	</div>
	<input type="hidden" name="task" value=""><input type="hidden" name="stage_id" value="<?php echo (int) $this->stage->id; ?>"><?php echo HTMLHelper::_('form.token'); ?>
</form>
