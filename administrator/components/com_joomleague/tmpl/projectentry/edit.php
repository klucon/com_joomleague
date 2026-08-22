<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
$projectId = (int) $this->project->id;
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&layout=edit&id=' . (int) $this->item->id . '&project_id=' . $projectId); ?>" method="post" name="adminForm" id="projectentry-form" class="form-validate">
	<div class="alert alert-info" role="status">
		<strong><?php echo $this->escape($this->project->name); ?></strong>
		<span class="ms-2"><?php echo Text::_($this->project->profile_name_key); ?> <?php echo $this->escape($this->project->profile_version); ?></span>
	</div>
	<div class="main-card">
		<?php echo HTMLHelper::_('uitab.startTabSet', 'projectentryTabs', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'projectentryTabs', 'details', Text::_('COM_JOOMLEAGUE_FIELDSET_DETAILS')); ?>
		<div class="row"><div class="col-12"><fieldset class="options-form"><legend><?php echo Text::_('COM_JOOMLEAGUE_FIELDSET_DETAILS'); ?></legend><?php echo $this->form->renderFieldset('details'); ?></fieldset></div></div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'projectentryTabs', 'competition', Text::_('COM_JOOMLEAGUE_FIELDSET_COMPETITION_DATA')); ?>
		<div class="row"><div class="col-12"><fieldset class="options-form"><legend><?php echo Text::_('COM_JOOMLEAGUE_FIELDSET_COMPETITION_DATA'); ?></legend><?php echo $this->form->renderFieldset('competition'); ?></fieldset></div></div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'projectentryTabs', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING')); ?>
		<div class="row"><div class="col-12"><fieldset class="options-form"><legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend><?php echo $this->form->renderFieldset('publishing'); ?></fieldset></div></div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
	</div>
	<input type="hidden" name="task" value="">
	<input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
