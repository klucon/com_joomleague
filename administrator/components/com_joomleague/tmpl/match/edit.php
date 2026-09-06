<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator'); $roundId = (int) $this->round->id;
$matchReturn = (string) (Factory::getApplication()->getInput()->get('return', '', 'base64') ?? '');
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&layout=edit&id=' . (int) $this->item->id . '&round_id=' . $roundId); ?>" method="post" name="adminForm" id="match-form" class="form-validate">
	<div class="main-card"><div class="alert alert-info" role="status"><strong><?php echo $this->escape($this->round->project_name); ?></strong><span class="mx-2">/</span><?php echo $this->escape($this->round->stage_name); ?><span class="mx-2">/</span><?php echo $this->escape($this->round->name); ?></div>
	<?php echo HTMLHelper::_('uitab.startTabSet', 'matchTabs', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'matchTabs', 'details', Text::_('COM_JOOMLEAGUE_FIELDSET_DETAILS')); ?><div class="row"><div class="col-12"><fieldset class="options-form"><legend><?php echo Text::_('COM_JOOMLEAGUE_FIELDSET_DETAILS'); ?></legend><?php echo $this->form->renderFieldset('details'); ?></fieldset></div></div><?php echo HTMLHelper::_('uitab.endTab'); ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'matchTabs', 'description', Text::_('COM_JOOMLEAGUE_FIELDSET_DESCRIPTION')); ?><div class="row"><div class="col-12"><fieldset class="options-form"><legend><?php echo Text::_('COM_JOOMLEAGUE_FIELDSET_DESCRIPTION'); ?></legend><?php echo $this->form->renderField('description'); ?></fieldset></div></div><?php echo HTMLHelper::_('uitab.endTab'); ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'matchTabs', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING')); ?><div class="row"><div class="col-12"><fieldset class="options-form"><legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend><?php echo $this->form->renderFieldset('publishing'); ?></fieldset></div></div><?php echo HTMLHelper::_('uitab.endTab'); ?>
	<?php echo LayoutHelper::render('joomleague.edit.customfields', ['form' => $this->form, 'tabSet' => 'matchTabs'], JPATH_ADMINISTRATOR . '/components/com_joomleague/layouts'); ?>
	<?php echo HTMLHelper::_('uitab.endTabSet'); ?></div>
	<input type="hidden" name="task" value=""><input type="hidden" name="round_id" value="<?php echo $roundId; ?>"><?php if ($matchReturn !== '') : ?><input type="hidden" name="return" value="<?php echo $this->escape($matchReturn); ?>"><?php endif; ?><?php echo HTMLHelper::_('form.token'); ?>
</form>
