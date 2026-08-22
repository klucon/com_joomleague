<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
$description = $this->form->getField('description');
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="project-form" class="form-validate">
	<div class="main-card">
		<?php echo HTMLHelper::_('uitab.startTabSet', 'projectTabs', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'projectTabs', 'details', Text::_('COM_JOOMLEAGUE_FIELDSET_PROJECT_DETAILS')); ?>
		<div class="row"><div class="col-12">
			<fieldset class="options-form"><legend><?php echo Text::_('COM_JOOMLEAGUE_FIELDSET_PROJECT_DETAILS'); ?></legend><?php echo $this->form->renderFieldset('details'); ?></fieldset>
		</div></div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'projectTabs', 'schedule', Text::_('COM_JOOMLEAGUE_FIELDSET_PROJECT_SCHEDULE')); ?>
		<div class="row g-4">
			<div class="col-12"><fieldset class="options-form"><legend><?php echo Text::_('COM_JOOMLEAGUE_FIELDSET_PROJECT_SCHEDULE'); ?></legend><?php echo $this->form->renderFieldset('schedule'); ?></fieldset></div>
			<div class="col-12"><fieldset class="options-form"><legend><?php echo Text::_('COM_JOOMLEAGUE_FIELDSET_PROJECT_AUTOMATION'); ?></legend><?php echo $this->form->renderFieldset('automation'); ?></fieldset></div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'projectTabs', 'presentation', Text::_('COM_JOOMLEAGUE_FIELDSET_PROJECT_PRESENTATION')); ?>
		<div class="row"><div class="col-12">
			<fieldset class="options-form"><legend><?php echo Text::_('COM_JOOMLEAGUE_FIELDSET_PROJECT_PRESENTATION'); ?></legend><?php echo $this->form->renderFieldset('presentation'); ?></fieldset>
		</div></div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'projectTabs', 'description', Text::_('JGLOBAL_DESCRIPTION')); ?>
		<div class="row"><div class="col-12">
			<fieldset class="options-form"><div class="mb-2"><?php echo $description->label; ?></div><div><?php echo $description->input; ?></div></fieldset>
		</div></div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'projectTabs', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING')); ?>
		<div class="row"><div class="col-12">
			<fieldset class="options-form"><legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend><?php echo $this->form->renderFieldset('publishing'); ?></fieldset>
		</div></div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php if ($this->canDo->get('core.admin')) : ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'projectTabs', 'permissions', Text::_('JFIELD_RULES_LABEL')); ?>
		<div class="row"><div class="col-12">
			<fieldset id="fieldset-rules" class="options-form"><legend><?php echo Text::_('JFIELD_RULES_LABEL'); ?></legend>
				<?php echo $this->form->getInput('rules'); ?>
			</fieldset>
		</div></div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php endif; ?>

		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
	</div>
	<?php if ($this->return !== null) : ?><input type="hidden" name="return" value="<?php echo $this->escape($this->return); ?>"><?php endif; ?>
	<input type="hidden" name="task" value=""><?php echo HTMLHelper::_('form.token'); ?>
</form>
