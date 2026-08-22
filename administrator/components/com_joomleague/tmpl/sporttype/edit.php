<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="sporttype-form" class="form-validate">
	<div class="main-card">
		<?php echo HTMLHelper::_('uitab.startTabSet', 'sporttypeTabs', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'sporttypeTabs', 'details', Text::_('COM_JOOMLEAGUE_FIELDSET_SPORTTYPE_DETAILS')); ?>
		<div class="row"><div class="col-12">
			<fieldset class="options-form"><legend><?php echo Text::_('COM_JOOMLEAGUE_FIELDSET_SPORTTYPE_DETAILS'); ?></legend><?php echo $this->form->renderFieldset('details'); ?></fieldset>
		</div></div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php if ((int) $this->item->id === 0) : ?>
			<?php echo HTMLHelper::_('uitab.addTab', 'sporttypeTabs', 'initialization', Text::_('COM_JOOMLEAGUE_FIELDSET_SPORTTYPE_INITIALIZATION')); ?>
			<div class="row"><div class="col-12">
				<fieldset class="options-form"><legend><?php echo Text::_('COM_JOOMLEAGUE_FIELDSET_SPORTTYPE_INITIALIZATION'); ?></legend><?php echo $this->form->renderFieldset('initialization'); ?></fieldset>
			</div></div>
			<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php endif; ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'sporttypeTabs', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING')); ?>
		<div class="row"><div class="col-12">
			<fieldset class="options-form"><legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend><?php echo $this->form->renderFieldset('publishing'); ?></fieldset>
		</div></div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
	</div>
	<input type="hidden" name="task" value=""><?php echo HTMLHelper::_('form.token'); ?>
</form>
