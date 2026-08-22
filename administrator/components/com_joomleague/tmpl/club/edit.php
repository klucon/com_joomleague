<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
$description = $this->form->getField('description');
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="club-form" class="form-validate">
	<div class="main-card">
		<?php echo HTMLHelper::_('uitab.startTabSet', 'clubTabs', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'clubTabs', 'details', Text::_('COM_JOOMLEAGUE_FIELDSET_DETAILS')); ?>
		<div class="row"><div class="col-12">
			<fieldset class="options-form"><legend><?php echo Text::_('COM_JOOMLEAGUE_FIELDSET_DETAILS'); ?></legend><?php echo $this->form->renderFieldset('details'); ?></fieldset>
		</div></div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php if ((int) $this->item->id === 0) : ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'clubTabs', 'related', Text::_('COM_JOOMLEAGUE_FIELDSET_CLUB_RELATED')); ?>
		<div class="row"><div class="col-12">
			<fieldset class="options-form"><legend><?php echo Text::_('COM_JOOMLEAGUE_FIELDSET_CLUB_RELATED'); ?></legend><?php echo $this->form->renderFieldset('related'); ?></fieldset>
		</div></div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php endif; ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'clubTabs', 'media', Text::_('COM_JOOMLEAGUE_FIELDSET_MEDIA')); ?>
		<div class="row"><div class="col-12">
			<fieldset class="options-form"><legend><?php echo Text::_('COM_JOOMLEAGUE_FIELDSET_MEDIA'); ?></legend><?php echo $this->form->renderFieldset('media'); ?></fieldset>
		</div></div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'clubTabs', 'history', Text::_('COM_JOOMLEAGUE_FIELDSET_CLUB_HISTORY')); ?>
		<div class="row"><div class="col-12">
			<fieldset class="options-form"><legend><?php echo Text::_('COM_JOOMLEAGUE_FIELDSET_CLUB_HISTORY'); ?></legend><?php echo $this->form->renderFieldset('history'); ?></fieldset>
		</div></div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'clubTabs', 'description', Text::_('JGLOBAL_DESCRIPTION')); ?>
		<div class="row"><div class="col-12">
			<fieldset class="options-form"><div class="mb-2"><?php echo $description->label; ?></div><div><?php echo $description->input; ?></div></fieldset>
		</div></div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'clubTabs', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING')); ?>
		<div class="row"><div class="col-12">
			<fieldset class="options-form"><legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend><?php echo $this->form->renderFieldset('publishing'); ?></fieldset>
		</div></div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
	</div>
	<input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
