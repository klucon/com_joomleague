<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var Joomleague\Component\Joomleague\Administrator\View\Leagues\HtmlView $this */

$this->getDocument()->getWebAssetManager()->useScript('multiselect');
$user = $this->getCurrentUser();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirection = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=leagues'); ?>" method="post" name="adminForm" id="adminForm">
	<div id="j-main-container" class="j-main-container">
		<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
		<?php if ($this->items === []) : ?>
			<div class="alert alert-info">
				<span class="icon-info-circle" aria-hidden="true"></span>
				<span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>
			<table class="table">
				<caption class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_LEAGUES_TABLE_CAPTION'); ?></caption>
				<thead>
					<tr>
						<td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
						<th scope="col"><?php echo HTMLHelper::_('searchtools.sort', 'COM_JOOMLEAGUE_LEAGUE_FIELD_NAME_LABEL', 'a.name', $listDirection, $listOrder); ?></th>
						<th scope="col" class="d-none d-md-table-cell"><?php echo HTMLHelper::_('searchtools.sort', 'COM_JOOMLEAGUE_LEAGUE_FIELD_SHORT_NAME_LABEL', 'a.short_name', $listDirection, $listOrder); ?></th>
						<th scope="col" class="d-none d-lg-table-cell"><?php echo Text::_('COM_JOOMLEAGUE_LEAGUE_FIELD_COUNTRY_LABEL'); ?></th>
						<th scope="col" class="w-10 d-none d-md-table-cell"><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ORDERING', 'a.ordering', $listDirection, $listOrder); ?></th>
						<th scope="col" class="w-5 d-none d-md-table-cell"><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirection, $listOrder); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($this->items as $i => $item) :
						$canEdit = $user->authorise('core.edit', 'com_joomleague');
						$canCheckin = $user->authorise('core.manage', 'com_checkin') || (int) $item->checked_out === (int) $user->id || empty($item->checked_out);
						?>
						<tr>
							<td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->name); ?></td>
							<th scope="row">
								<?php if ($item->checked_out) : ?><?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'leagues.', $canCheckin); ?><?php endif; ?>
								<?php if ($canEdit) : ?>
									<a href="<?php echo Route::_('index.php?option=com_joomleague&task=league.edit&id=' . (int) $item->id); ?>"><?php echo $this->escape($item->name); ?></a>
								<?php else : ?><?php echo $this->escape($item->name); ?><?php endif; ?>
							</th>
							<td class="d-none d-md-table-cell"><?php echo $this->escape($item->short_name); ?></td>
							<td class="d-none d-lg-table-cell"><?php echo \Joomleague\Component\Joomleague\Administrator\Helper\FlagHelper::render($item->country ?? ''); ?></td>
							<td class="d-none d-md-table-cell"><?php echo (int) $item->ordering; ?></td>
							<td class="d-none d-md-table-cell"><?php echo (int) $item->id; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php echo $this->pagination->getListFooter(); ?>
		<?php endif; ?>
		<?php echo $this->filterForm->renderControlFields(); ?>
	</div>
</form>
