<?php
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
HTMLHelper::_('behavior.multiselect');
$user = Factory::getApplication()->getIdentity();
$projectId = (int) $this->project->id;
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=stagetransitions&project_id=' . $projectId); ?>" method="post" name="adminForm" id="adminForm">
	<div class="alert alert-info"><strong><?php echo $this->escape($this->project->name); ?></strong><div><?php echo Text::_('COM_JOOMLEAGUE_STAGE_TRANSITIONS_DESC'); ?></div></div>
	<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
	<div class="table-responsive"><table class="table table-striped align-middle" id="stageTransitionList"><thead><tr>
		<td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_NAME_LABEL'); ?></th><th class="w-1 text-center"><?php echo Text::_('COM_JOOMLEAGUE_STAGE_PROGRESSION_PREVIEW'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_SOURCE_STAGE_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_TARGET_STAGE_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_TRANSITION_SELECTOR_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_CARRY_OVER_MODE_LABEL'); ?></th><th class="text-center"><?php echo Text::_('JSTATUS'); ?></th><th class="text-center"><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?></th>
	</tr></thead><tbody>
	<?php foreach ($this->items as $i => $item) : $canChange = $user->authorise('core.edit.state', 'com_joomleague'); ?>
		<tr><td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?></td>
			<th scope="row"><a href="<?php echo Route::_('index.php?option=com_joomleague&task=stagetransition.edit&id=' . (int) $item->id . '&project_id=' . $projectId); ?>"><?php echo $this->escape($item->name); ?></a><div class="small text-body-secondary"><?php echo $this->escape($item->code); ?></div></th>
			<td class="text-center"><a class="btn btn-sm btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomleague&view=stageprogression&transition_id=' . (int) $item->id); ?>" title="<?php echo Text::_('COM_JOOMLEAGUE_STAGE_PROGRESSION_PREVIEW'); ?>"><span class="icon-eye" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_STAGE_PROGRESSION_PREVIEW'); ?></span></a></td>
			<td><?php echo $this->escape($item->source_name); ?></td><td><?php echo $this->escape($item->target_name); ?></td><td><?php echo Text::_('COM_JOOMLEAGUE_TRANSITION_SELECTOR_' . strtoupper($item->selector_type)); ?></td><td><?php echo Text::_('COM_JOOMLEAGUE_CARRY_OVER_' . strtoupper($item->carry_over_mode)); ?></td><td class="text-center"><?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'stagetransitions.', $canChange, 'cb'); ?></td><td class="text-center"><?php echo (int) $item->id; ?></td></tr>
	<?php endforeach; ?>
	<?php if ($this->items === []) : ?><tr><td colspan="9"><div class="alert alert-warning mb-0"><?php echo Text::_('COM_JOOMLEAGUE_STAGE_TRANSITIONS_EMPTY'); ?></div></td></tr><?php endif; ?>
	</tbody></table></div>
	<?php echo $this->pagination->getListFooter(); ?>
	<input type="hidden" name="task" value=""><input type="hidden" name="boxchecked" value="0"><input type="hidden" name="project_id" value="<?php echo $projectId; ?>"><input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"><input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"><?php echo HTMLHelper::_('form.token'); ?>
</form>
