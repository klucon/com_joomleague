<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Pagination\Pagination;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Domain\Service\ClubPersonHistoryReader;

final class ClubstaffhistoryModel extends BaseDatabaseModel
{
	protected function populateState($ordering = null, $direction = null): void
	{
		$input = Factory::getApplication()->getInput();
		$this->setState('club_id', $input->getInt('club_id', 0));
		$this->setState('team_id', $input->getInt('filter_team_id', $input->getInt('team_id', 0)));
		foreach (['project_id', 'season_id'] as $key) $this->setState($key, $input->getInt('filter_' . $key, 0));
		$this->setState('project_type', $input->getCmd('filter_project_type', ''));
		$this->setState('filter_search', trim($input->getString('filter_search', '')));
		$this->setState('manager_id', $input->getInt('manager_id', 0));
		$this->setState('list.ordering', $input->getCmd('sort', 'name'));
		$this->setState('list.direction', strtolower($input->getCmd('direction', 'asc')) === 'desc' ? 'desc' : 'asc');
		$limit = $input->getInt('limit', 25);
		$this->setState('list.limit', in_array($limit, [0, 10, 25, 50, 100], true) ? $limit : 25);
		$this->setState('list.start', max(0, $input->getInt('limitstart', $input->getInt('start', 0))));
	}

	/** @return array<string,mixed> */
	public function getStaffHistory(): array
	{
		$app = Factory::getApplication();
		$clubId = (int) $this->getState('club_id');
		if ($clubId < 1) $clubId = (int) ComponentHelper::getParams('com_joomleague')->get('site_club_id', 0);
		if ($clubId < 1) return ['error' => 'COM_JOOMLEAGUE_CLUBSTAFFHISTORY_NO_CLUB'];
		$app->bootComponent('com_joomleague');
		$menuParams = $app->getParams();
		$sortingEnabled = (bool) $menuParams->get('enable_column_sorting', 1);
		$visibleColumns = [];
		foreach (['played', 'wins', 'draws', 'losses', 'score', 'period'] as $column) {
			$visibleColumns[$column] = (bool) $menuParams->get('show_' . $column, 1);
		}
		$requestedSort = (string) $this->getState('list.ordering');
		if (isset($visibleColumns[$requestedSort]) && !$visibleColumns[$requestedSort]) $requestedSort = 'name';
		$data = (new ClubPersonHistoryReader(Factory::getContainer()->get(DatabaseInterface::class)))->read($clubId, ['team_id' => (int) $this->getState('team_id'),
			'project_id'=>(int)$this->getState('project_id'), 'season_id'=>(int)$this->getState('season_id'),
			'project_type'=>(string)$this->getState('project_type'), 'person_type'=>'staff',
			'search'=>(string)$this->getState('filter_search'), 'limit'=>(int)$this->getState('list.limit'), 'start'=>(int)$this->getState('list.start'),
			'sort'=>$sortingEnabled ? $requestedSort : 'name',
			'direction'=>$sortingEnabled ? (string)$this->getState('list.direction') : 'asc',
			'staff_only'=>true, 'manager_id'=>(int)$this->getState('manager_id'), 'detail_start'=>max(0,$app->getInput()->getInt('detail_limitstart',0)),
		], $app->getIdentity()->getAuthorisedViewLevels());
		if (isset($data['error'])) {
			$data['error'] = $data['error'] === 'club_required' ? 'COM_JOOMLEAGUE_CLUBSTAFFHISTORY_NO_CLUB' : 'COM_JOOMLEAGUE_CLUBSTAFFHISTORY_UNAVAILABLE';
			return $data;
		}
		$data['managers'] = $data['people'];
		$data['visible_columns'] = $visibleColumns;
		$data['show_boundary_matches'] = (bool) $menuParams->get('show_boundary_matches', 1);
		$data['menu_id'] = max(0, $app->getInput()->getInt('Itemid', 0));
		$state = $data['pagination_state'];
		$pagination = new Pagination($state['total'],$state['start'],$state['limit']);
		$params = ['option'=>'com_joomleague','view'=>'clubstaffhistory','club_id'=>$clubId,
			'filter_team_id'=>(int)$this->getState('team_id'),'filter_project_id'=>(int)$this->getState('project_id'),'filter_season_id'=>(int)$this->getState('season_id'),
			'filter_project_type'=>(string)$this->getState('project_type'),
			'filter_search'=>$state['search'],'limit'=>$state['requested_limit'],'sort'=>$data['sorting']['column'],'direction'=>$data['sorting']['direction']];
		if ($data['menu_id'] > 0) $params['Itemid'] = $data['menu_id'];
		foreach ($params as $key=>$value) $pagination->setAdditionalUrlParam($key,$value);
		$data['pagination'] = $pagination;
		$data['sorting']['enabled'] = $sortingEnabled;
		$data['detail_pagination'] = null;
		if ($data['detail_state'] !== null) {
			$detail = $data['detail_state'];
			$pager = new Pagination($detail['total'],$detail['start'],$detail['limit'],'detail_');
			foreach ($params + ['manager_id'=>(int)$this->getState('manager_id'),'start'=>$state['start']] as $key=>$value) $pager->setAdditionalUrlParam($key,$value);
			$data['detail_pagination'] = $pager;
		}
		return $data;
	}
}
