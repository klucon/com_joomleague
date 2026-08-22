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
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=projects'); ?>" method="post" name="adminForm" id="adminForm">
	<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
	<div class="table-responsive">
		<table class="table table-striped" id="projectList">
			<thead><tr>
				<td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
				<th scope="col" class="w-1"><span class="visually-hidden"><?php echo Text::_('JACTION_EDIT'); ?></span></th>
				<th scope="col"><?php echo HTMLHelper::_('searchtools.sort', 'COM_JOOMLEAGUE_FIELD_NAME_LABEL', 'a.name', $listDirn, $listOrder); ?></th>
				<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_COMPETITION_LABEL'); ?></th>
				<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_SEASON_LABEL'); ?></th>
				<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_SPORT_PROFILE_LABEL'); ?></th>
				<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_PROJECT_TYPE_LABEL'); ?></th>
				<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_LIFECYCLE_STATE_LABEL'); ?></th>
				<th scope="col" class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_OVERRIDES_LABEL'); ?></th>
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
					<td>
						<?php if ($canEdit && !$checkedOut) : ?><a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&task=project.edit&id=' . (int) $item->id); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?>"><span class="icon-edit" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('JACTION_EDIT'); ?></span></a><?php endif; ?>
					</td>
					<th scope="row">
						<?php if ($checkedOut) echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor_name, $item->checked_out_time, 'projects.', $canChange); ?>
						<a href="<?php echo Route::_('index.php?option=com_joomleague&view=projectpanel&project_id=' . (int) $item->id); ?>"><?php echo $this->escape($item->name); ?></a>
						<?php if ($item->code) : ?><div class="small text-body-secondary"><?php echo $this->escape($item->code); ?></div><?php endif; ?>
					</th>
					<td><?php echo $this->escape($item->competition_name); ?></td>
					<td><?php echo $this->escape($item->season_name); ?></td>
					<td><?php echo $this->escape($item->sport_type_name); ?><div class="small text-body-secondary"><?php echo $this->escape(Text::_($item->profile_name_key) . ' ' . $item->profile_version); ?></div></td>
					<td><?php echo Text::_('COM_JOOMLEAGUE_PROJECT_TYPE_' . strtoupper($item->project_type)); ?></td>
					<td><?php echo Text::_('COM_JOOMLEAGUE_LIFECYCLE_' . strtoupper($item->lifecycle_state)); ?></td>
					<td class="text-center">
						<a href="<?php echo Route::_('index.php?option=com_joomleague&view=projectrules&project_id=' . (int) $item->id); ?>"><?php echo Text::sprintf('COM_JOOMLEAGUE_PROJECT_RULE_OVERRIDES_COUNT', (int) $item->rule_override_count); ?></a>
						<span class="text-body-secondary" aria-hidden="true"> | </span>
						<a href="<?php echo Route::_('index.php?option=com_joomleague&view=projecttemplates&project_id=' . (int) $item->id); ?>"><?php echo Text::sprintf('COM_JOOMLEAGUE_PROJECT_TEMPLATE_OVERRIDES_COUNT', (int) $item->template_override_count); ?></a>
					</td>
					<td class="text-center"><?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'projects.', $canChange, 'cb'); ?></td>
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
