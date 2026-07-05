<?php

/**
 * Modální seznam zápasů pro výběr do menu položky (hledání + stránkování).
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

$app      = Factory::getApplication();
$input    = $app->getInput();
$function = $input->getCmd('function', 'jSelectMatch');
$search   = trim((string) $input->getString('search', ''));
$start    = $input->getInt('limitstart', 0);
$limit    = 20;

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('core')->useScript('modal-content-select');

$db = Factory::getContainer()->get(DatabaseInterface::class);

$build = static function ($countOnly) use ($db, $search) {
	$query = $db->createQuery();
	if ($countOnly) {
		$query->select('COUNT(*)');
	} else {
		$query->select([
			$db->quoteName('m.id'),
			$db->quoteName('m.match_date'),
			$db->quoteName('m.match_number'),
			$db->quoteName('th.name', 'home'),
			$db->quoteName('tg.name', 'away'),
			$db->quoteName('r.name', 'round'),
			$db->quoteName('pr.name', 'project'),
			'CONCAT(COALESCE(' . $db->quoteName('m.team1_result') . ',"-")," : ",COALESCE(' . $db->quoteName('m.team2_result') . ',"-")) AS result',
		])->order($db->quoteName('m.match_date') . ' DESC, ' . $db->quoteName('m.id') . ' DESC');
	}
	$query->from($db->quoteName('#__joomleague_match', 'm'))
		->join('INNER', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
		->join('INNER', $db->quoteName('#__joomleague_project', 'pr') . ' ON ' . $db->quoteName('pr.id') . ' = ' . $db->quoteName('r.project_id'))
		->join('LEFT', $db->quoteName('#__joomleague_project_team', 'ph') . ' ON ' . $db->quoteName('ph.id') . ' = ' . $db->quoteName('m.projectteam1_id'))
		->join('LEFT', $db->quoteName('#__joomleague_team', 'th') . ' ON ' . $db->quoteName('th.id') . ' = ' . $db->quoteName('ph.team_id'))
		->join('LEFT', $db->quoteName('#__joomleague_project_team', 'pg') . ' ON ' . $db->quoteName('pg.id') . ' = ' . $db->quoteName('m.projectteam2_id'))
		->join('LEFT', $db->quoteName('#__joomleague_team', 'tg') . ' ON ' . $db->quoteName('tg.id') . ' = ' . $db->quoteName('pg.team_id'));
	if ($search !== '') {
		$like = '%' . $search . '%';
		$query->where('(' . $db->quoteName('th.name') . ' LIKE :s1 OR ' . $db->quoteName('tg.name') . ' LIKE :s2 OR ' . $db->quoteName('pr.name') . ' LIKE :s3)')
			->bind(':s1', $like)->bind(':s2', $like)->bind(':s3', $like);
	}
	return $query;
};

$total      = (int) $db->setQuery($build(true))->loadResult();
$items      = $db->setQuery($build(false), $start, $limit)->loadObjectList();
$pagination = new Pagination($total, $start, $limit);

$actionUri = (new Uri())->setPath(Uri::base(true) . '/index.php');
$actionUri->setQuery([
	'option'                => 'com_joomleague',
	'view'                  => 'matchpicker',
	'layout'                => 'modal',
	'tmpl'                  => 'component',
	'function'              => $function,
	Session::getFormToken() => 1,
]);
?>
<div class="container-fluid">
	<form action="<?php echo $this->escape((string) $actionUri); ?>" method="post" name="modalMatchForm" id="modalMatchForm">
		<div class="input-group mb-3">
			<input type="text" name="search" class="form-control" value="<?php echo $this->escape($search); ?>" placeholder="<?php echo Text::_('COM_JOOMLEAGUE_SELECT_MATCH'); ?>" autofocus>
			<button type="submit" class="btn btn-primary"><span class="icon-search" aria-hidden="true"></span> <?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
		</div>

		<?php if (!$items) : ?>
			<div class="alert alert-info"><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></div>
		<?php else : ?>
			<table class="table table-striped">
				<thead>
					<tr>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_DATE'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_MATCH'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_SCORE'); ?></th>
						<th><?php echo Text::_('COM_JOOMLEAGUE_SITE_PROJECT'); ?></th>
						<th class="w-1 text-center"><?php echo Text::_('JGRID_HEADING_ID'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($items as $item) : ?>
						<?php
						$hasDate = !empty($item->match_date) && strpos((string) $item->match_date, '0000-00-00') !== 0;
						$dateStr = $hasDate ? HTMLHelper::_('date', $item->match_date, 'd.m.Y') : '';
						$label   = trim(($item->home ?? '') . ' – ' . ($item->away ?? ''), ' –') ?: ('#' . (int) $item->id);
						$title   = $label . ($dateStr !== '' ? ' (' . $dateStr . ')' : '');
						?>
						<tr>
							<td class="text-nowrap"><?php echo $this->escape($dateStr); ?></td>
							<td>
								<a class="select-link" href="javascript:void(0)" data-content-select
									data-function="<?php echo $this->escape($function); ?>"
									data-id="<?php echo (int) $item->id; ?>"
									data-title="<?php echo $this->escape($title); ?>"><?php echo $this->escape($label); ?></a>
								<div class="small text-muted"><?php echo $this->escape((string) ($item->round ?? '')); ?></div>
							</td>
							<td><?php echo $this->escape((string) ($item->result ?? '')); ?></td>
							<td><?php echo $this->escape((string) ($item->project ?? '')); ?></td>
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
