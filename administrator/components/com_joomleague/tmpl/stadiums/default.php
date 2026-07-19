<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var Joomleague\Component\Joomleague\Administrator\View\Stadiums\HtmlView $this */

$this->getDocument()->getWebAssetManager()->useScript('multiselect');
$user = $this->getCurrentUser(); $listOrder = $this->escape($this->state->get('list.ordering')); $listDirection = $this->escape($this->state->get('list.direction'));
$excerpt = static function (?string $value): string {
	$text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8')) ?? '');

	return mb_strlen($text) > 120 ? mb_substr($text, 0, 117) . '...' : $text;
};
?>
<form action="<?php echo Route::_('index.php?option=com_joomleague&view=stadiums'); ?>" method="post" name="adminForm" id="adminForm"><div id="j-main-container" class="j-main-container">
	<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
	<?php if ($this->items === []) : ?><div class="alert alert-info"><span class="icon-info-circle" aria-hidden="true"></span> <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></div>
	<?php else : ?><table class="table"><caption class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_STADIUMS_TABLE_CAPTION'); ?></caption><thead><tr>
		<td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td><th class="w-5"><?php echo Text::_('COM_JOOMLEAGUE_PLAYGROUND_FIELD_PICTURE_LABEL'); ?></th>
			<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_JOOMLEAGUE_PLAYGROUND_FIELD_NAME_LABEL', 'a.name', $listDirection, $listOrder); ?></th>
			<th class="d-none d-xl-table-cell"><?php echo Text::_('COM_JOOMLEAGUE_PLAYGROUND_FIELD_INFO_LABEL'); ?></th>
			<th class="d-none d-md-table-cell"><?php echo HTMLHelper::_('searchtools.sort', 'COM_JOOMLEAGUE_PLAYGROUND_FIELD_CITY_LABEL', 'a.city', $listDirection, $listOrder); ?></th>
		<th class="d-none d-lg-table-cell"><?php echo Text::_('COM_JOOMLEAGUE_PLAYGROUND_FIELD_CLUB_LABEL'); ?></th>
		<th class="w-5 text-center d-none d-md-table-cell"><?php echo Text::_('COM_JOOMLEAGUE_LIST_COORDINATES_LABEL'); ?></th>
		<th class="w-10 d-none d-md-table-cell"><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ORDERING', 'a.ordering', $listDirection, $listOrder); ?></th>
		<th class="w-5 d-none d-md-table-cell"><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirection, $listOrder); ?></th></tr></thead><tbody>
	<?php foreach ($this->items as $i => $item) :
		$canEdit = $user->authorise('core.edit', 'com_joomleague'); $canCheckin = $user->authorise('core.manage', 'com_checkin') || (int) $item->checked_out === (int) $user->id || empty($item->checked_out);
		$playgroundPicture = trim((string) $item->picture) !== '' ? (string) $item->picture : (string) ComponentHelper::getParams('com_joomleague')->get('placeholder_playground_picture', '');
		$image = HTMLHelper::cleanImageURL($playgroundPicture); $src = $image->url === '' ? '' : Uri::root(true) . '/' . ltrim($image->url, '/');
	?><tr><td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->name); ?></td>
		<td><?php if ($src !== '') : ?><img src="<?php echo $this->escape($src); ?>" alt="" width="40" height="40" loading="lazy" class="rounded object-fit-cover"><?php else : ?><span class="icon-image text-muted" aria-hidden="true"></span><?php endif; ?></td>
			<th scope="row"><?php if ($item->checked_out) { echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'stadiums.', $canCheckin); } ?>
			<?php echo \Joomleague\Component\Joomleague\Administrator\Helper\FlagHelper::render($item->country ?? '', false); ?> <?php if ($canEdit) : ?><a href="<?php echo Route::_('index.php?option=com_joomleague&task=playground.edit&id=' . (int) $item->id); ?>"><?php echo $this->escape($item->name); ?></a><?php else : echo $this->escape($item->name); endif; ?></th>
			<td class="d-none d-xl-table-cell text-muted"><?php echo $this->escape($excerpt($item->info ?? '')); ?></td>
			<td class="d-none d-md-table-cell"><?php echo $this->escape($item->city); ?></td><td class="d-none d-lg-table-cell"><?php echo $this->escape((string) $item->club); ?></td>
		<td class="text-center d-none d-md-table-cell"><?php if ($item->latitude !== null && $item->longitude !== null) : ?><span class="icon-location text-success" aria-hidden="true" title="<?php echo Text::_('COM_JOOMLEAGUE_LIST_COORDINATES_SET'); ?>"></span><?php else : ?><span class="icon-warning-circle text-muted" aria-hidden="true" title="<?php echo Text::_('COM_JOOMLEAGUE_LIST_COORDINATES_MISSING'); ?>"></span><?php endif; ?></td>
		<td class="d-none d-md-table-cell"><?php echo (int) $item->ordering; ?></td><td class="d-none d-md-table-cell"><?php echo (int) $item->id; ?></td></tr><?php endforeach; ?>
	</tbody></table><?php echo $this->pagination->getListFooter(); ?><?php endif; ?><?php echo $this->filterForm->renderControlFields(); ?>
</div></form>
