<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Pagination\Pagination;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Domain\Service\ClubRivalHistoryReader;
use Joomleague\Component\Joomleague\Domain\Service\StandingsDecimal;

final class ClubrivalsModel extends BaseDatabaseModel
{
	protected function populateState($ordering = null, $direction = null): void
	{
		$input = Factory::getApplication()->getInput();
		$this->setState('club_id', $input->getInt('club_id', 0));
		foreach (['project_id', 'season_id', 'opponent_club_id'] as $key) $this->setState($key, $input->getInt('filter_' . $key, 0));
		$this->setState('project_type', $input->getCmd('filter_project_type', ''));
		$this->setState('filter_search', trim($input->getString('filter_search', '')));
		$this->setState('list.ordering', $input->getCmd('sort', 'opponent'));
		$this->setState('list.direction', strtolower($input->getCmd('direction', 'asc')) === 'desc' ? 'desc' : 'asc');
		$limit = $input->getInt('limit', 25);
		$this->setState('list.limit', in_array($limit, [0, 10, 25, 50, 100], true) ? $limit : 25);
		$this->setState('list.start', max(0, $input->getInt('start', 0)));
	}

	/** @return array<string,mixed> */
	public function getRivals(): array
	{
		$app = Factory::getApplication();
		$clubId = (int) $this->getState('club_id');
		if ($clubId < 1) $clubId = (int) ComponentHelper::getParams('com_joomleague')->get('site_club_id', 0);
		if ($clubId < 1) return ['error' => 'COM_JOOMLEAGUE_CLUBRIVALS_NO_CLUB'];
		$app->bootComponent('com_joomleague');
		$data = (new ClubRivalHistoryReader(Factory::getContainer()->get(DatabaseInterface::class)))->read($clubId, [
			'project_id' => (int) $this->getState('project_id'), 'season_id' => (int) $this->getState('season_id'),
			'project_type' => (string) $this->getState('project_type'), 'opponent_club_id' => (int) $this->getState('opponent_club_id'),
		], $app->getIdentity()->getAuthorisedViewLevels());
		if (isset($data['error'])) $data['error'] = $data['error'] === 'club_required' ? 'COM_JOOMLEAGUE_CLUBRIVALS_NO_CLUB' : 'COM_JOOMLEAGUE_CLUBRIVALS_UNAVAILABLE';
		if (isset($data['error'])) return $data;
		$search = (string) $this->getState('filter_search');
		if ($search !== '') $data['opponents'] = array_values(array_filter($data['opponents'], static fn (array $row): bool => mb_stripos((string) $row['club_name'], $search) !== false));
		$sortingEnabled = (bool) $app->getParams()->get('enable_column_sorting', 1);
		$sort = $sortingEnabled ? (string) $this->getState('list.ordering') : 'opponent';
		$direction = $sortingEnabled ? (string) $this->getState('list.direction') : 'asc';
		if (!in_array($sort, ['opponent', 'played', 'wins', 'draws', 'losses', 'score', 'period'], true)) $sort = 'opponent';
		$this->sortRivals($data['opponents'], $sort, $direction);
		$total = count($data['opponents']);
		$requestedLimit = (int) $this->getState('list.limit');
		$limit = $requestedLimit === 0 ? max(1, min(1000, $total)) : $requestedLimit;
		$start = min((int) $this->getState('list.start'), max(0, (int) (floor(max(0, $total - 1) / $limit) * $limit)));
		$data['opponents'] = array_slice($data['opponents'], $start, $limit);
		$pagination = new Pagination($total, $start, $limit);
		foreach (['option' => 'com_joomleague', 'view' => 'clubrivals', 'club_id' => $clubId, 'filter_project_id' => (int) $this->getState('project_id'), 'filter_season_id' => (int) $this->getState('season_id'), 'filter_project_type' => (string) $this->getState('project_type'), 'filter_search' => $search, 'limit' => $requestedLimit, 'sort' => $sort, 'direction' => $direction] as $key => $value) $pagination->setAdditionalUrlParam($key, $value);
		$data['pagination'] = $pagination;
		$data['pagination_state'] = ['total' => $total, 'start' => $start, 'limit' => $limit, 'requested_limit' => $requestedLimit, 'search' => $search, 'all_capped' => $requestedLimit === 0 && $total > 1000];
		$data['sorting'] = ['enabled' => $sortingEnabled, 'column' => $sort, 'direction' => $direction];
		return $data;
	}

	/** @param list<array<string,mixed>> $rivals */
	private function sortRivals(array &$rivals, string $sort, string $direction): void
	{
		usort($rivals, static function (array $left, array $right) use ($sort, $direction): int {
			$comparison = match ($sort) {
				'played', 'wins', 'draws', 'losses' => (int) $left[$sort] <=> (int) $right[$sort],
				'score' => StandingsDecimal::compare(StandingsDecimal::subtract((string) $left['scored'], (string) $left['conceded']), StandingsDecimal::subtract((string) $right['scored'], (string) $right['conceded'])),
				'period' => strcmp((string) ($left['first_match'] ?? ''), (string) ($right['first_match'] ?? '')),
				default => strnatcasecmp((string) $left['club_name'], (string) $right['club_name']),
			};
			if ($direction === 'desc') $comparison *= -1;
			return $comparison ?: strnatcasecmp((string) $left['club_name'], (string) $right['club_name']);
		});
	}
}
