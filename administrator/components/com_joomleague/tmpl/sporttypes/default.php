<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.multiselect');
$user = Factory::getApplication()->getIdentity();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=sporttypes'); ?>" method="post" name="adminForm" id="adminForm">
	<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
	<div class="table-responsive">
		<table class="table table-striped" id="sporttypeList">
			<thead><tr>
				<td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
				<th scope="col"><?php echo HTMLHelper::_('searchtools.sort', 'COM_JOOMLEAGUE_FIELD_NAME_LABEL', 'a.name', $listDirn, $listOrder); ?></th>
				<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_CODE_LABEL'); ?></th>
				<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_PROFILE'); ?></th>
				<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_VERSION'); ?></th>
				<th scope="col" class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_PROJECT_COUNT_LABEL'); ?></th>
				<th scope="col" class="w-5 text-center"><?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?></th>
				<th scope="col" class="w-5 text-center"><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ($this->items as $i => $item) :
				$canEdit = $user->authorise('core.edit', 'com_joomleague');
				$canChange = $user->authorise('core.edit.state', 'com_joomleague');
				$checkedOut = (int) $item->checked_out !== 0 && (int) $item->checked_out !== (int) $user->id;
			?>
				<tr>
					<td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
					<th scope="row">
						<?php if ($checkedOut) echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor_name, $item->checked_out_time, 'sporttypes.', $canChange); ?>
						<?php if ($canEdit && !$checkedOut) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&task=sporttype.edit&id=' . (int) $item->id); ?>"><?php echo $this->escape($item->name); ?></a><?php else : ?><?php echo $this->escape($item->name); ?><?php endif; ?>
					</th>
					<td><code class="text-break"><?php echo $this->escape($item->code); ?></code></td>
					<td><?php echo Text::_($item->profile_name_key); ?></td>
					<td><?php echo $this->escape($item->profile_version); ?><?php if ($item->profile_state !== 'active') : ?><div class="small text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_STATE_SUPERSEDED'); ?></div><?php endif; ?></td>
					<td class="text-center"><?php echo (int) $item->project_count; ?></td>
					<td class="text-center"><?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'sporttypes.', $canChange, 'cb'); ?></td>
					<td class="text-center"><?php echo (int) $item->id; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php echo $this->pagination->getListFooter(); ?>
	<input type="hidden" name="task" value=""><input type="hidden" name="boxchecked" value="0">
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"><input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
