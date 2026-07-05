<?php

/**
 * Modální seznam osob pro výběr do menu položky (hledání + stránkování).
 *
 * @author   Ondřej Klučka
 * @package  Klucon.Joomleague
 * @license  GNU General Public License version 2 or later
 */

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

$app      = Factory::getApplication();
$input    = $app->getInput();
$function = $input->getCmd('function', 'jSelectPerson');
$search   = trim((string) $input->getString('search', ''));
$start    = $input->getInt('limitstart', 0);
$limit    = 20;

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('core')->useScript('modal-content-select');

$db = Factory::getContainer()->get(DatabaseInterface::class);

$build = static function ($countOnly) use ($db, $search) {
	$query = $db->createQuery();
	if ($countOnly) {
		$query->select('COUNT(*)')->from($db->quoteName('#__joomleague_person', 'p'));
	} else {
		$query->select([
			$db->quoteName('p.id'),
			$db->quoteName('p.firstname'),
			$db->quoteName('p.lastname'),
			$db->quoteName('p.nickname'),
			$db->quoteName('pos.name', 'position'),
		])
			->from($db->quoteName('#__joomleague_person', 'p'))
			->join('LEFT', $db->quoteName('#__joomleague_position', 'pos') . ' ON ' . $db->quoteName('pos.id') . ' = ' . $db->quoteName('p.position_id'))
			->order($db->quoteName('p.lastname') . ' ASC, ' . $db->quoteName('p.firstname') . ' ASC');
	}
	if ($search !== '') {
		$like = '%' . $search . '%';
		$query->where('(' . $db->quoteName('p.firstname') . ' LIKE :s1 OR ' . $db->quoteName('p.lastname') . ' LIKE :s2 OR ' . $db->quoteName('p.nickname') . ' LIKE :s3)')
			->bind(':s1', $like)->bind(':s2', $like)->bind(':s3', $like);
	}
	return $query;
};

$total = (int) $db->setQuery($build(true))->loadResult();
$items = $db->setQuery($build(false), $start, $limit)->loadObjectList();
$pagination = new Pagination($total, $start, $limit);

$actionUri = (new Uri())->setPath(Uri::base(true) . '/index.php');
$actionUri->setQuery([
	'option'                => 'com_joomleague',
	'view'                  => 'personpicker',
	'layout'                => 'modal',
	'tmpl'                  => 'component',
	'function'              => $function,
	Session::getFormToken() => 1,
]);
?>
<div class="container-fluid">
	<form action="<?php echo $this->escape((string) $actionUri); ?>" method="post" name="modalPersonForm" id="modalPersonForm">
		<div class="input-group mb-3">
			<input type="text" name="search" class="form-control" value="<?php echo $this->escape($search); ?>" placeholder="<?php echo Text::_('COM_JOOMLEAGUE_SELECT_PERSON'); ?>" autofocus>
			<button type="submit" class="btn btn-primary"><span class="icon-search" aria-hidden="true"></span> <?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
		</div>

		<?php if (!$items) : ?>
			<div class="alert alert-info"><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></div>
		<?php else : ?>
			<table class="table table-striped">
				<thead>
					<tr>
						<th><?php echo Text::_('COM_JOOMLEAGUE_FIELD_NAME'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_PERSON_FIELD_NICKNAME'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_PERSON_FIELD_POSITION'); ?></th>
						<th class="w-1 text-center"><?php echo Text::_('JGRID_HEADING_ID'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($items as $item) : ?>
						<?php $name = trim(($item->firstname ?? '') . ' ' . ($item->lastname ?? '')) ?: ('#' . (int) $item->id); ?>
						<tr>
							<td>
								<a class="select-link" href="javascript:void(0)" data-content-select
									data-function="<?php echo $this->escape($function); ?>"
									data-id="<?php echo (int) $item->id; ?>"
									data-title="<?php echo $this->escape($name); ?>"><?php echo $this->escape($name); ?></a>
							</td>
							<td><?php echo $this->escape((string) ($item->nickname ?? '')); ?></td>
							<td><?php echo $this->escape((string) ($item->position ?? '')); ?></td>
							<td class="text-center"><?php echo (int) $item->id; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php echo $pagination->getListFooter(); ?>
		<?php endif; ?>
		<input type="hidden" name="function" value="<?php echo $this->escape($function); ?>">
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
