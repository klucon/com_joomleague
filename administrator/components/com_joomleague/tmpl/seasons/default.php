<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var Joomleague\Component\Joomleague\Administrator\View\Seasons\HtmlView $this */

$this->getDocument()->getWebAssetManager()->useScript('multiselect');
$user = $this->getCurrentUser();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirection = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=seasons'); ?>" method="post" name="adminForm" id="adminForm">
	<div id="j-main-container" class="j-main-container">
		<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
		<?php if ($this->items === []) : ?><div class="alert alert-info"><span class="icon-info-circle" aria-hidden="true"></span> <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></div>
		<?php else : ?><table class="table"><caption class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_SEASONS_TABLE_CAPTION'); ?></caption>
			<thead><tr><td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
				<th class="w-1 text-center"><?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.published', $listDirection, $listOrder); ?></th>
				<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_JOOMLEAGUE_SEASON_FIELD_NAME_LABEL', 'a.name', $listDirection, $listOrder); ?></th>
				<th class="w-10 d-none d-md-table-cell"><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ORDERING', 'a.ordering', $listDirection, $listOrder); ?></th>
				<th class="w-5 d-none d-md-table-cell"><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirection, $listOrder); ?></th></tr></thead>
			<tbody><?php foreach ($this->items as $i => $item) :
				$canEdit = $user->authorise('core.edit', 'com_joomleague');
				$canCheckin = $user->authorise('core.manage', 'com_checkin') || (int) $item->checked_out === (int) $user->id || empty($item->checked_out);
				$canChange = $user->authorise('core.edit.state', 'com_joomleague') && $canCheckin;
			?><tr><td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->name); ?></td>
				<td class="text-center"><?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'seasons.', $canChange); ?></td>
				<th scope="row"><?php if ($item->checked_out) { echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'seasons.', $canCheckin); } ?>
					<?php if ($canEdit) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&task=season.edit&id=' . (int) $item->id); ?>"><?php echo $this->escape($item->name); ?></a><?php else : echo $this->escape($item->name); endif; ?></th>
				<td class="d-none d-md-table-cell"><?php echo (int) $item->ordering; ?></td><td class="d-none d-md-table-cell"><?php echo (int) $item->id; ?></td></tr><?php endforeach; ?></tbody>
		</table><?php echo $this->pagination->getListFooter(); ?><?php endif; ?>
		<?php echo $this->filterForm->renderControlFields(); ?>
	</div>
</form>
