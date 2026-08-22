<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
$yesNo = static fn ($value): string => (int) $value === 1 ? Text::_('JYES') : Text::_('JNO');
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=events'); ?>" method="post" name="adminForm" id="adminForm">
	<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_EVENTS_RUNTIME_DESC'); ?></div>
	<div class="row g-3 mb-3">
		<div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><div class="fs-3"><?php echo (int) $this->summary['sport_types']; ?></div><div class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_SPORT_TYPES'); ?></div></div></div></div>
		<div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><div class="fs-3"><?php echo (int) $this->summary['event_types']; ?></div><div class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_EVENTS_TITLE'); ?></div></div></div></div>
	</div>
	<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
	<div class="table-responsive"><table class="table table-striped align-middle" id="eventList">
		<thead><tr>
			<td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
			<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_EVENT_NAME_LABEL'); ?></th>
			<th scope="col" class="d-none d-sm-table-cell"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_CODE'); ?></th>
			<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_SPORT_TYPE_LABEL'); ?></th>
			<th scope="col" class="d-none d-md-table-cell text-center"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_TIMELINE_LABEL'); ?></th>
			<th scope="col" class="d-none d-lg-table-cell text-center"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_AFFECTS_SCORE_LABEL'); ?></th>
			<th scope="col" class="d-none d-lg-table-cell"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_SCORE_DELTA_LABEL'); ?></th>
			<th scope="col" class="d-none d-xl-table-cell"><?php echo Text::_('JSTATUS'); ?></th>
			<th scope="col" class="d-none d-xl-table-cell"><?php echo Text::_('JGRID_HEADING_ID'); ?></th>
		</tr></thead>
		<tbody>
		<?php if (!$this->items) : ?><tr><td colspan="9"><div class="alert alert-info mb-0"><?php echo Text::_('COM_JOOMLEAGUE_EVENTS_EMPTY'); ?></div></td></tr><?php endif; ?>
		<?php foreach ($this->items as $i => $item) : ?>
			<?php $name = $item->name !== '' ? $item->name : Text::_((string) $item->name_key); ?>
			<tr><td class="text-center"><?php echo HTMLHelper::_('grid.id',$i,$item->id); ?></td><th scope="row"><a href="<?php echo Route::_('index.php?option=com_joomleague&task=event.edit&id='.(int)$item->id); ?>"><?php echo $this->escape($name); ?></a></th>
				<td class="d-none d-sm-table-cell"><code class="text-break"><?php echo $this->escape($item->code); ?></code></td>
				<td><strong><?php echo $this->escape($item->sport_type_name); ?></strong><div class="small text-body-secondary"><?php echo $this->escape($item->sport_type_code); ?></div></td>
				<td class="d-none d-md-table-cell text-center"><?php echo $yesNo($item->timeline); ?></td>
				<td class="d-none d-lg-table-cell text-center"><?php echo $yesNo($item->affects_score); ?></td>
				<td class="d-none d-lg-table-cell"><?php echo $item->score_delta === null ? Text::_('JNONE') : $this->escape((string) $item->score_delta); ?></td>
				<td class="d-none d-xl-table-cell"><?php echo (int) $item->published === 1 ? Text::_('JPUBLISHED') : Text::_('JUNPUBLISHED'); ?></td>
				<td class="d-none d-xl-table-cell"><?php echo (int) $item->id; ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table></div>
	<?php echo $this->pagination->getListFooter(); ?>
	<input type="hidden" name="task" value=""><input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"><input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
