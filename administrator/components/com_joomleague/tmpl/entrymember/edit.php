<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
$this->getDocument()->getWebAssetManager()->registerAndUseScript(
	'com_joomleague.entrymember',
	'com_joomleague/js/entrymember.js',
	[],
	['defer' => true]
);
$entryId = (int) $this->entry->id;
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&layout=edit&id=' . (int) $this->item->id . '&entry_id=' . $entryId); ?>" method="post" name="adminForm" id="entrymember-form" class="form-validate">
	<div class="alert alert-info" role="status"><strong><?php echo $this->escape($this->entry->resolved_name); ?></strong><span class="ms-2"><?php echo $this->escape($this->entry->project_name); ?></span></div>
	<div class="main-card">
		<?php echo HTMLHelper::_('uitab.startTabSet', 'entrymemberTabs', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'entrymemberTabs', 'details', Text::_('COM_JOOMLEAGUE_FIELDSET_DETAILS')); ?><div class="row"><div class="col-12"><fieldset class="options-form"><legend><?php echo Text::_('COM_JOOMLEAGUE_FIELDSET_DETAILS'); ?></legend><?php echo $this->form->renderFieldset('details'); ?></fieldset></div></div><?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'entrymemberTabs', 'status', Text::_('COM_JOOMLEAGUE_FIELDSET_MEMBERSHIP_STATUS')); ?><div class="row"><div class="col-12"><fieldset class="options-form"><legend><?php echo Text::_('COM_JOOMLEAGUE_FIELDSET_MEMBERSHIP_STATUS'); ?></legend><?php echo $this->form->renderFieldset('status'); ?></fieldset></div></div><?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'entrymemberTabs', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING')); ?><div class="row"><div class="col-12"><fieldset class="options-form"><legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend><?php echo $this->form->renderFieldset('publishing'); ?></fieldset></div></div><?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
	</div>
	<input type="hidden" name="task" value=""><input type="hidden" name="entry_id" value="<?php echo $entryId; ?>"><?php echo HTMLHelper::_('form.token'); ?>
</form>
