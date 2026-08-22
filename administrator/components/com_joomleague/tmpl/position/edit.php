<?php
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
HTMLHelper::_('behavior.formvalidator');
$this->getDocument()->getWebAssetManager()->useScript('com_joomleague.duallist');
$renderDualList = function (string $name, array $data): void {
	$label = static fn ($item): string => $item->name !== '' ? $item->name : Text::_((string) $item->name_key);
	?>
	<div class="row g-3 align-items-center" data-jl-duallist>
		<div class="col-md-5"><label class="form-label"><?php echo Text::_('COM_JOOMLEAGUE_AVAILABLE'); ?></label><select class="form-select" multiple size="14" data-available><?php foreach ($data['available'] as $item) : ?><option value="<?php echo (int)$item->id; ?>"><?php echo htmlspecialchars($label($item), ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
		<div class="col-md-2 d-grid gap-2"><button type="button" class="btn btn-secondary" data-add><span class="icon-chevron-right" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_ASSIGN_SELECTED'); ?></button><button type="button" class="btn btn-secondary" data-remove><span class="icon-chevron-left" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_UNASSIGN_SELECTED'); ?></button></div>
		<div class="col-md-5"><label class="form-label"><?php echo Text::_('COM_JOOMLEAGUE_ASSIGNED'); ?></label><input type="hidden" name="jform[<?php echo $name; ?>][]" value=""><select class="form-select" name="jform[<?php echo $name; ?>][]" multiple size="14" data-assigned><?php foreach ($data['assigned'] as $item) : ?><option value="<?php echo (int)$item->id; ?>"><?php echo htmlspecialchars($label($item), ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
	</div>
	<?php
};
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="position-form" class="form-validate">
	<div class="main-card">
		<?php echo HTMLHelper::_('uitab.startTabSet', 'positionTabs', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'positionTabs', 'details', Text::_('COM_JOOMLEAGUE_FIELDSET_DETAILS')); ?>
		<div class="row"><div class="col-12"><fieldset class="options-form"><legend><?php echo Text::_('COM_JOOMLEAGUE_FIELDSET_DETAILS'); ?></legend><?php echo $this->form->renderFieldset('details'); ?></fieldset></div></div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php if ((int)$this->item->id > 0) : ?>
			<?php echo HTMLHelper::_('uitab.addTab', 'positionTabs', 'events', Text::_('COM_JOOMLEAGUE_EVENTS')); ?><div class="p-3"><?php $renderDualList('assigned_events', $this->eventCapabilities); ?></div><?php echo HTMLHelper::_('uitab.endTab'); ?>
			<?php echo HTMLHelper::_('uitab.addTab', 'positionTabs', 'statistics', Text::_('COM_JOOMLEAGUE_STATISTICS')); ?><div class="p-3"><?php $renderDualList('assigned_statistics', $this->statisticCapabilities); ?></div><?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php endif; ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'positionTabs', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING')); ?>
		<div class="row"><div class="col-12"><fieldset class="options-form"><legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend><?php echo $this->form->renderFieldset('publishing'); ?></fieldset></div></div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
	</div>
	<input type="hidden" name="task" value=""><?php echo HTMLHelper::_('form.token'); ?>
</form>
