<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator'); $projectId = (int) $this->project->id; $stageId = (int) ($this->item->stage_id ?? 0);
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&layout=edit&id=' . (int) $this->item->id . '&project_id=' . $projectId . ($stageId > 0 ? '&stage_id=' . $stageId : '')); ?>" method="post" name="adminForm" id="standingadjustment-form" class="form-validate">
	<div class="main-card"><div class="alert alert-info" role="status"><strong><?php echo $this->escape($this->project->name); ?></strong><div><?php echo Text::_('COM_JOOMLEAGUE_STANDING_ADJUSTMENT_DESC'); ?></div></div>
	<?php echo HTMLHelper::_('uitab.startTabSet', 'standingAdjustmentTabs', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'standingAdjustmentTabs', 'details', Text::_('COM_JOOMLEAGUE_FIELDSET_DETAILS')); ?><div class="row"><div class="col-12"><fieldset class="options-form"><legend><?php echo Text::_('COM_JOOMLEAGUE_FIELDSET_DETAILS'); ?></legend><?php echo $this->form->renderFieldset('details'); ?></fieldset></div></div><?php echo HTMLHelper::_('uitab.endTab'); ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'standingAdjustmentTabs', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING')); ?><div class="row"><div class="col-12"><fieldset class="options-form"><legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend><?php echo $this->form->renderFieldset('publishing'); ?></fieldset></div></div><?php echo HTMLHelper::_('uitab.endTab'); ?><?php echo HTMLHelper::_('uitab.endTabSet'); ?></div>
	<input type="hidden" name="task" value=""><input type="hidden" name="project_id" value="<?php echo $projectId; ?>"><input type="hidden" name="stage_id" value="<?php echo $stageId; ?>"><?php echo HTMLHelper::_('form.token'); ?>
</form>
