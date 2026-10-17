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

final class ClubpeopleModel extends BaseDatabaseModel
{
	protected function populateState($ordering = null, $direction = null): void
	{
		$input = Factory::getApplication()->getInput();
		$this->setState('club_id', $input->getInt('club_id', 0));
		foreach (['project_id', 'season_id'] as $key) $this->setState($key, $input->getInt('filter_' . $key, 0));
		foreach (['project_type', 'person_type'] as $key) $this->setState($key, $input->getCmd('filter_' . $key, ''));
		$this->setState('filter_search', trim($input->getString('filter_search', '')));
		$this->setState('list.ordering', $input->getCmd('sort', 'name'));
		$this->setState('list.direction', strtolower($input->getCmd('direction', 'asc')) === 'desc' ? 'desc' : 'asc');
		$limit = $input->getInt('limit', 25);
		$this->setState('list.limit', in_array($limit, [0, 10, 25, 50, 100], true) ? $limit : 25);
		$this->setState('list.start', max(0, $input->getInt('limitstart', $input->getInt('start', 0))));
	}

	/** @return array<string,mixed> */
	public function getPeople(): array
	{
		$app = Factory::getApplication();
		$clubId = (int) $this->getState('club_id');
		if ($clubId < 1) $clubId = (int) ComponentHelper::getParams('com_joomleague')->get('site_club_id', 0);
		if ($clubId < 1) return ['error' => 'COM_JOOMLEAGUE_CLUBPEOPLE_NO_CLUB'];
		$app->bootComponent('com_joomleague');
		$sortingEnabled = (bool) $app->getParams()->get('enable_column_sorting', 1);
		$data = (new ClubPersonHistoryReader(Factory::getContainer()->get(DatabaseInterface::class)))->read($clubId, [
			'project_id'=>(int)$this->getState('project_id'), 'season_id'=>(int)$this->getState('season_id'),
			'project_type'=>(string)$this->getState('project_type'), 'person_type'=>(string)$this->getState('person_type'),
			'search'=>(string)$this->getState('filter_search'), 'limit'=>(int)$this->getState('list.limit'), 'start'=>(int)$this->getState('list.start'),
			'sort'=>$sortingEnabled ? (string)$this->getState('list.ordering') : 'name',
			'direction'=>$sortingEnabled ? (string)$this->getState('list.direction') : 'asc',
		], $app->getIdentity()->getAuthorisedViewLevels());
		if (isset($data['error'])) {
			$data['error'] = $data['error'] === 'club_required' ? 'COM_JOOMLEAGUE_CLUBPEOPLE_NO_CLUB' : 'COM_JOOMLEAGUE_CLUBPEOPLE_UNAVAILABLE';
			return $data;
		}
		$state = $data['pagination_state'];
		$pagination = new Pagination($state['total'],$state['start'],$state['limit']);
		$params = ['option'=>'com_joomleague','view'=>'clubpeople','club_id'=>$clubId,
			'filter_project_id'=>(int)$this->getState('project_id'),'filter_season_id'=>(int)$this->getState('season_id'),
			'filter_project_type'=>(string)$this->getState('project_type'),'filter_person_type'=>(string)$this->getState('person_type'),
			'filter_search'=>$state['search'],'limit'=>$state['requested_limit'],'sort'=>$data['sorting']['column'],'direction'=>$data['sorting']['direction']];
		foreach ($params as $key=>$value) $pagination->setAdditionalUrlParam($key,$value);
		$data['pagination'] = $pagination;
		$data['sorting']['enabled'] = $sortingEnabled;
		return $data;
	}
}
