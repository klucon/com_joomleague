<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var Joomleague\Component\Joomleague\Administrator\View\Sportstypes\HtmlView $this */

$this->getDocument()->getWebAssetManager()->useScript('multiselect');

$user = $this->getCurrentUser();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirection = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=sportstypes'); ?>" method="post" name="adminForm" id="adminForm">
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
				<caption class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_SPORTSTYPES_TABLE_CAPTION'); ?></caption>
				<thead>
					<tr>
						<td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
						<th scope="col" class="w-1 text-center">
							<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.published', $listDirection, $listOrder); ?>
						</th>
						<th scope="col">
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_JOOMLEAGUE_SPORTSTYPE_FIELD_NAME_LABEL', 'a.name', $listDirection, $listOrder); ?>
						</th>
						<th scope="col" class="d-none d-md-table-cell"><?php echo Text::_('COM_JOOMLEAGUE_SPORTSTYPE_FIELD_ICON_LABEL'); ?></th>
						<th scope="col" class="w-10 d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ORDERING', 'a.ordering', $listDirection, $listOrder); ?>
						</th>
						<th scope="col" class="w-5 d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirection, $listOrder); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($this->items as $i => $item) :
						$canEdit = $user->authorise('core.edit', 'com_joomleague');
						$canCheckin = $user->authorise('core.manage', 'com_checkin') || (int) $item->checked_out === (int) $user->id || empty($item->checked_out);
						$canChange = $user->authorise('core.edit.state', 'com_joomleague') && $canCheckin;
						$displayName = Text::_((string) $item->name);
						$image = HTMLHelper::cleanImageURL((string) $item->icon);
						$imageAttributes = [
							'src' => $image->url === '' ? '' : Uri::root(true) . '/' . ltrim($image->url, '/'),
							'alt' => $displayName,
							'class' => 'rounded',
							'loading' => 'lazy',
							'width' => $image->attributes['width'] ?: 32,
							'height' => $image->attributes['height'] ?: 32,
						];
						?>
						<tr>
							<td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $displayName); ?></td>
							<td class="text-center"><?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'sportstypes.', $canChange); ?></td>
							<th scope="row">
								<?php if ($item->checked_out) : ?>
									<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'sportstypes.', $canCheckin); ?>
								<?php endif; ?>
								<?php if ($canEdit) : ?>
									<a href="<?php echo Route::_('index.php?option=com_joomleague&task=sportstype.edit&id=' . (int) $item->id); ?>">
										<?php echo $this->escape($displayName); ?>
									</a>
								<?php else : ?>
									<?php echo $this->escape($displayName); ?>
								<?php endif; ?>
							</th>
							<td class="d-none d-md-table-cell">
								<?php if ($imageAttributes['src'] !== '') : ?>
									<?php echo LayoutHelper::render('joomla.html.image', $imageAttributes); ?>
								<?php else : ?>
									<span class="icon-minus text-muted" aria-hidden="true"></span>
								<?php endif; ?>
							</td>
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
