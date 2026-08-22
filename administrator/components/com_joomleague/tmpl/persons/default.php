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
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=persons'); ?>" method="post" name="adminForm" id="adminForm">
	<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
	<div class="table-responsive">
		<table class="table table-striped" id="personList">
			<thead><tr>
				<td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
				<th scope="col"><?php echo HTMLHelper::_('searchtools.sort', 'COM_JOOMLEAGUE_FIELD_LAST_NAME_LABEL', 'a.last_name', $listDirn, $listOrder); ?></th>
				<th scope="col" class="d-none d-md-table-cell"><?php echo HTMLHelper::_('searchtools.sort', 'COM_JOOMLEAGUE_FIELD_FIRST_NAME_LABEL', 'a.first_name', $listDirn, $listOrder); ?></th>
				<th scope="col" class="d-none d-md-table-cell"><?php echo HTMLHelper::_('searchtools.sort', 'COM_JOOMLEAGUE_FIELD_NICKNAME_LABEL', 'a.nickname', $listDirn, $listOrder); ?></th>
				<th scope="col" class="d-none d-lg-table-cell"><?php echo HTMLHelper::_('searchtools.sort', 'COM_JOOMLEAGUE_FIELD_COUNTRY_CODE_LABEL', 'a.country_code', $listDirn, $listOrder); ?></th>
				<th scope="col" class="d-none d-lg-table-cell text-center"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_ASSIGNMENT_COUNT_LABEL'); ?></th>
				<th scope="col" class="w-5 text-center"><?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?></th>
				<th scope="col" class="d-none d-lg-table-cell w-5 text-center"><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ($this->items as $i => $item) :
				$canEdit = $user->authorise('core.edit', 'com_joomleague');
				$canChange = $user->authorise('core.edit.state', 'com_joomleague');
				$checkedOut = (int) $item->checked_out !== 0 && (int) $item->checked_out !== (int) $user->id;
				$primaryName = (string) ($item->last_name !== '' ? $item->last_name : $item->first_name);
			?>
				<tr>
					<td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
					<th scope="row">
						<?php if ($checkedOut) echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor_name, $item->checked_out_time, 'persons.', $canChange); ?>
						<?php if ($canEdit && !$checkedOut) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&task=person.edit&id=' . (int) $item->id); ?>"><?php echo $this->escape($primaryName); ?></a><?php else : ?><?php echo $this->escape($primaryName); ?><?php endif; ?>
					</th>
					<td class="d-none d-md-table-cell"><?php echo $this->escape((string) $item->first_name); ?></td>
					<td class="d-none d-md-table-cell"><?php echo $this->escape((string) $item->nickname); ?></td>
					<td class="d-none d-lg-table-cell"><?php echo $this->escape((string) $item->country_code); ?></td>
					<td class="d-none d-lg-table-cell text-center"><?php echo (int) $item->project_count + (int) $item->membership_count; ?></td>
					<td class="text-center"><?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'persons.', $canChange, 'cb'); ?></td>
					<td class="d-none d-lg-table-cell text-center"><?php echo (int) $item->id; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php echo $this->pagination->getListFooter(); ?>
	<input type="hidden" name="task" value="">
	<input type="hidden" name="boxchecked" value="0">
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>">
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
