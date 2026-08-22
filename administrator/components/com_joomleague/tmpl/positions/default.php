<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
$capability = static function ($value): string {
	if ($value === null) return '<span class="badge text-bg-warning">' . Text::_('COM_JOOMLEAGUE_VALUE_NOT_DECLARED') . '</span>';
	return (bool) $value
		? '<span class="badge text-bg-success">' . Text::_('JYES') . '</span>'
		: '<span class="badge text-bg-secondary">' . Text::_('JNO') . '</span>';
};
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=positions'); ?>" method="post" name="adminForm" id="adminForm">
	<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_POSITIONS_RUNTIME_DESC'); ?></div>
	<div class="row g-3 mb-3">
		<div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><div class="fs-3"><?php echo (int) $this->summary['sport_types']; ?></div><div class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_SPORT_TYPES'); ?></div></div></div></div>
		<div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><div class="fs-3"><?php echo (int) $this->summary['positions']; ?></div><div class="text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_POSITIONS'); ?></div></div></div></div>
	</div>
	<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
	<div class="table-responsive"><table class="table table-striped align-middle" id="positionList">
		<thead><tr>
			<td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
			<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_POSITION_NAME_LABEL'); ?></th>
			<th scope="col" class="d-none d-sm-table-cell"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_CODE'); ?></th>
			<th scope="col" class="d-none d-lg-table-cell"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_PARENT_POSITION_LABEL'); ?></th>
			<th scope="col"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_SPORT_TYPE_LABEL'); ?></th>
			<th scope="col" class="d-none d-md-table-cell"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_PERSON_TYPE_LABEL'); ?></th>
			<th scope="col" class="d-none d-lg-table-cell text-center"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_HAS_EVENTS_LABEL'); ?></th>
			<th scope="col" class="d-none d-lg-table-cell text-center"><?php echo Text::_('COM_JOOMLEAGUE_FIELD_HAS_STATISTICS_LABEL'); ?></th>
			<th scope="col" class="d-none d-xl-table-cell"><?php echo Text::_('JSTATUS'); ?></th>
			<th scope="col" class="d-none d-xl-table-cell"><?php echo Text::_('JGRID_HEADING_ID'); ?></th>
		</tr></thead>
		<tbody><?php if (!$this->items) : ?><tr><td colspan="10"><div class="alert alert-info mb-0"><?php echo Text::_('COM_JOOMLEAGUE_POSITIONS_EMPTY'); ?></div></td></tr><?php endif; ?><?php foreach ($this->items as $i => $item) : ?><tr>
			<?php $positionName = $item->name !== '' ? $item->name : Text::_((string) $item->name_key); ?>
			<td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
			<th scope="row"><a href="<?php echo Route::_('index.php?option=com_joomleague&task=position.edit&id=' . (int) $item->id); ?>"><?php echo $this->escape($positionName); ?></a></th>
			<td class="d-none d-sm-table-cell"><code class="text-break"><?php echo $this->escape($item->code); ?></code></td>
			<td class="d-none d-lg-table-cell"><?php echo $item->parent_id === null ? Text::_('JNONE') : $this->escape($item->parent_name !== '' ? $item->parent_name : Text::_((string) $item->parent_name_key)); ?></td>
			<td><strong><?php echo $this->escape($item->sport_type_name); ?></strong><div class="small text-body-secondary"><?php echo $this->escape($item->sport_type_code); ?></div></td>
			<td class="d-none d-md-table-cell"><?php echo Text::_('COM_JOOMLEAGUE_PERSON_TYPE_' . strtoupper($item->person_type)); ?></td>
			<td class="d-none d-lg-table-cell text-center"><?php echo $capability($item->has_events); ?></td>
			<td class="d-none d-lg-table-cell text-center"><?php echo $capability($item->has_statistics); ?></td>
			<td class="d-none d-xl-table-cell"><?php echo (int) $item->published === 1 ? Text::_('JPUBLISHED') : Text::_('JUNPUBLISHED'); ?></td>
			<td class="d-none d-xl-table-cell"><?php echo (int) $item->id; ?></td>
		</tr><?php endforeach; ?></tbody>
	</table></div>
	<div class="d-none d-sm-block"><?php echo $this->pagination->getListFooter(); ?></div>
	<?php $paginationData = $this->pagination->getData(); ?>
	<nav class="d-sm-none" aria-label="<?php echo Text::_('JLIB_HTML_PAGINATION'); ?>">
		<div class="text-end mb-2"><?php echo $this->pagination->getResultsCounter(); ?></div>
		<ul class="pagination justify-content-end mb-0">
			<?php echo LayoutHelper::render('joomla.pagination.link', ['data' => $paginationData->previous, 'active' => $paginationData->previous->link !== null]); ?>
			<li class="page-item disabled"><span class="page-link"><?php echo $this->pagination->getPagesCounter(); ?></span></li>
			<?php echo LayoutHelper::render('joomla.pagination.link', ['data' => $paginationData->next, 'active' => $paginationData->next->link !== null]); ?>
		</ul>
	</nav>
	<input type="hidden" name="task" value=""><input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"><input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
