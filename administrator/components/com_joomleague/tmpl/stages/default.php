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
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=stages&project_id=' . $projectId); ?>" method="post" name="adminForm" id="adminForm">
	<div class="alert alert-info" role="status"><strong><?php echo $this->escape($this->project->name); ?></strong><span class="ms-2"><?php echo Text::_('COM_JOOMLEAGUE_STAGES_CONTEXT_DESC'); ?></span></div>
	<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
	<div class="table-responsive"><table class="table table-striped" id="stageList"><thead><tr>
		<td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td><th class="w-1"><span class="visually-hidden"><?php echo Text::_('JACTION_EDIT'); ?></span></th>
		<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_JOOMLEAGUE_FIELD_NAME_LABEL', 'a.name', $listDirn, $listOrder); ?></th><th class="w-1"><span class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_STAGE_ENTRIES_MANAGE'); ?></span></th><th class="w-1"><span class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_STAGE_ROUNDS_MANAGE'); ?></span></th><th class="w-1"><span class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_STAGE_STANDINGS_MANAGE'); ?></span></th>
		<th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_STAGE_TYPE_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_PARENT_STAGE_LABEL'); ?></th><th class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_SEQUENCE_NUMBER_LABEL'); ?></th><th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_DATE_RANGE_LABEL'); ?></th><th class="text-center"><?php echo Text::_('JSTATUS'); ?></th><th class="text-center"><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?></th>
	</tr></thead><tbody>
	<?php foreach ($this->items as $i => $item) : $canEdit = $user->authorise('core.edit', 'com_joomleague'); $canChange = $user->authorise('core.edit.state', 'com_joomleague'); $checkedOut = (int) $item->checked_out !== 0 && (int) $item->checked_out !== (int) $user->id; ?>
		<tr><td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?></td><td><?php if ($canEdit && !$checkedOut) : ?><a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&task=stage.edit&id=' . (int) $item->id . '&project_id=' . $projectId); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?>"><span class="icon-edit" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('JACTION_EDIT'); ?></span></a><?php endif; ?></td>
		<th scope="row"><?php if ($checkedOut) echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor_name, $item->checked_out_time, 'stages.', $canChange); ?><a href="<?php echo Route::_('index.php?option=com_joomleague&task=stage.edit&id=' . (int) $item->id . '&project_id=' . $projectId); ?>"><?php echo $this->escape($item->name); ?></a><div class="small text-body-secondary"><?php echo $this->escape($item->code); ?></div></th><td><a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=stageentries&stage_id=' . (int) $item->id); ?>" title="<?php echo Text::_('COM_JOOMLEAGUE_STAGE_ENTRIES_MANAGE'); ?>"><span class="icon-users" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_STAGE_ENTRIES_MANAGE'); ?></span></a></td><td><a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=rounds&stage_id=' . (int) $item->id); ?>" title="<?php echo Text::_('COM_JOOMLEAGUE_STAGE_ROUNDS_MANAGE'); ?>"><span class="icon-list" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_STAGE_ROUNDS_MANAGE'); ?></span></a></td><td><a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=standings&project_id=' . $projectId . '&stage_id=' . (int) $item->id); ?>" title="<?php echo Text::_('COM_JOOMLEAGUE_STAGE_STANDINGS_MANAGE'); ?>"><span class="icon-chart" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_STAGE_STANDINGS_MANAGE'); ?></span></a></td>
		<td><?php echo $this->escape($item->stage_type); ?></td><td><?php echo $item->parent_name ? $this->escape($item->parent_name) : Text::_('JNONE'); ?></td><td class="text-center"><?php echo $item->sequence_number === null ? Text::_('JNONE') : (int) $item->sequence_number; ?></td><td><?php echo Text::sprintf('COM_JOOMLEAGUE_STAGE_DATE_RANGE_VALUE', $item->start_date ?: Text::_('JNONE'), $item->end_date ?: Text::_('JNONE')); ?></td><td class="text-center"><?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'stages.', $canChange, 'cb'); ?></td><td class="text-center"><?php echo (int) $item->id; ?></td></tr>
	<?php endforeach; ?>
	<?php if ($this->items === []) : ?><tr><td colspan="13"><div class="alert alert-warning mb-0"><?php echo Text::_('COM_JOOMLEAGUE_STAGES_EMPTY'); ?></div></td></tr><?php endif; ?>
	</tbody></table></div>
	<?php echo $this->pagination->getListFooter(); ?>
	<input type="hidden" name="task" value=""><input type="hidden" name="boxchecked" value="0"><input type="hidden" name="project_id" value="<?php echo $projectId; ?>"><input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"><input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"><?php echo HTMLHelper::_('form.token'); ?>
</form>
