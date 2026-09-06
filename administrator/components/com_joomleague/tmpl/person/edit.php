<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
$description = $this->form->getField('description');
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="person-form" class="form-validate">
	<div class="main-card">
		<?php echo HTMLHelper::_('uitab.startTabSet', 'personTabs', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'personTabs', 'details', Text::_('COM_JOOMLEAGUE_FIELDSET_DETAILS')); ?>
		<div class="row"><div class="col-12"><fieldset class="options-form"><legend><?php echo Text::_('COM_JOOMLEAGUE_FIELDSET_DETAILS'); ?></legend><?php echo $this->form->renderFieldset('details'); ?></fieldset></div></div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'personTabs', 'biography', Text::_('COM_JOOMLEAGUE_FIELDSET_BIOGRAPHY')); ?>
		<div class="row"><div class="col-12"><fieldset class="options-form"><legend><?php echo Text::_('COM_JOOMLEAGUE_FIELDSET_BIOGRAPHY'); ?></legend><?php echo $this->form->renderFieldset('biography'); ?></fieldset></div></div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'personTabs', 'media', Text::_('COM_JOOMLEAGUE_FIELDSET_MEDIA')); ?>
		<div class="row"><div class="col-12"><fieldset class="options-form"><legend><?php echo Text::_('COM_JOOMLEAGUE_FIELDSET_MEDIA'); ?></legend><?php echo $this->form->renderFieldset('media'); ?></fieldset></div></div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'personTabs', 'description', Text::_('JGLOBAL_DESCRIPTION')); ?>
		<div class="row"><div class="col-12"><fieldset class="options-form"><div class="mb-2"><?php echo $description->label; ?></div><div><?php echo $description->input; ?></div></fieldset></div></div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'personTabs', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING')); ?>
		<div class="row"><div class="col-12"><fieldset class="options-form"><legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend><?php echo $this->form->renderFieldset('publishing'); ?></fieldset></div></div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php echo LayoutHelper::render('joomleague.edit.customfields', ['form' => $this->form, 'tabSet' => 'personTabs'], JPATH_ADMINISTRATOR . '/components/com_joomleague/layouts'); ?>
		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
	</div>
	<input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
