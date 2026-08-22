<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Administrator\Service\MatchParticipantSummaryProvider;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultSummaryProvider;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectContextRepository;

defined('_JEXEC') or die;

final class ProjectscheduleModel extends ListModel
{
	public function __construct($config = [])
	{
		$config['filter_fields'] ??= [
			'id', 'a.id',
			'match_number', 'a.match_number',
			'scheduled_start', 'a.scheduled_start',
			'published', 'a.published',
			'stage_id', 'a.stage_id',
			'round_id', 'a.round_id',
		];
		parent::__construct($config);
	}

	protected function populateState($ordering = 'a.scheduled_start', $direction = 'asc'): void
	{
		$this->setState('project_id', $this->getUserStateFromRequest($this->context . '.project_id', 'project_id', 0, 'uint'));
		foreach (['search', 'published', 'status_code', 'date_from', 'date_to'] as $filter) {
			$this->setState('filter.' . $filter, $this->getUserStateFromRequest($this->context . '.filter.' . $filter, 'filter_' . $filter, ''));
		}
		foreach (['stage_id', 'round_id'] as $filter) {
			$this->setState('filter.' . $filter, $this->getUserStateFromRequest($this->context . '.filter.' . $filter, 'filter_' . $filter, 0, 'uint'));
		}
		parent::populateState($ordering, $direction);
	}

	public function getProject(int $projectId): object
	{
		return (new ProjectContextRepository($this->getDatabase()))->get($projectId);
	}

	protected function getListQuery()
	{
		$db = $this->getDatabase();
		$projectId = (int) $this->getState('project_id');
		$query = $db->getQuery(true)
			->select('a.*')
			->select([
				$db->quoteName('stage.name', 'stage_name'),
				$db->quoteName('round.name', 'round_name'),
				$db->quoteName('round.sequence_number', 'round_sequence_number'),
				$db->quoteName('result.result_type', 'result_type'),
				$db->quoteName('result.status_code', 'result_status'),
				$db->quoteName('venue.name', 'venue_name'),
			])
			->from($db->quoteName('#__joomleague_project_match', 'a'))
			->innerJoin($db->quoteName('#__joomleague_project_stage', 'stage') . ' ON stage.id = a.stage_id')
			->innerJoin($db->quoteName('#__joomleague_project_round', 'round') . ' ON round.id = a.round_id')
			->leftJoin($db->quoteName('#__joomleague_match_result', 'result') . ' ON result.match_id = a.id')
			->leftJoin($db->quoteName('#__joomleague_venue', 'venue') . ' ON venue.id = a.venue_id')
			->where($db->quoteName('a.project_id') . ' = :projectId')
			->bind(':projectId', $projectId, ParameterType::INTEGER);

		$published = $this->getState('filter.published');
		if ($published !== '') {
			$published = (int) $published;
			$query->where('a.published = :published')->bind(':published', $published, ParameterType::INTEGER);
		}

		$status = trim((string) $this->getState('filter.status_code'));
		if ($status !== '') {
			$query->where('a.status_code = :status')->bind(':status', $status);
		}

		$stageId = (int) $this->getState('filter.stage_id');
		if ($stageId > 0) {
			$query->where('a.stage_id = :stageId')->bind(':stageId', $stageId, ParameterType::INTEGER);
		}

		$roundId = (int) $this->getState('filter.round_id');
		if ($roundId > 0) {
			$query->where('a.round_id = :roundId')->bind(':roundId', $roundId, ParameterType::INTEGER);
		}

		$dateFrom = trim((string) $this->getState('filter.date_from'));
		if ($dateFrom !== '') {
			$dateFromBound = $dateFrom . ' 00:00:00';
			$query->where('a.scheduled_start >= :dateFrom')->bind(':dateFrom', $dateFromBound);
		}

		$dateTo = trim((string) $this->getState('filter.date_to'));
		if ($dateTo !== '') {
			$dateToBound = $dateTo . ' 23:59:59';
			$query->where('a.scheduled_start <= :dateTo')->bind(':dateTo', $dateToBound);
		}

		$search = trim((string) $this->getState('filter.search'));
		if ($search !== '') {
			if (str_starts_with($search, 'id:')) {
				$id = (int) substr($search, 3);
				$query->where('a.id = :id')->bind(':id', $id, ParameterType::INTEGER);
			} else {
				$search = '%' . $search . '%';
				$query->where('(a.code LIKE :code OR a.match_number LIKE :number)')
					->bind(':code', $search)->bind(':number', $search);
			}
		}

		$query->order($db->escape((string) $this->getState('list.ordering', 'a.scheduled_start')) . ' ' . ($this->getState('list.direction') === 'desc' ? 'DESC' : 'ASC'));

		return $query;
	}

	/** @return array<int,object> */
	public function getItems(): array
	{
		$items = parent::getItems();

		if (!is_array($items) || $items === []) {
			return is_array($items) ? $items : [];
		}

		$ids = array_map(static fn (object $item): int => (int) $item->id, $items);
		$participants = (new MatchParticipantSummaryProvider($this->getDatabase()))->load($ids);
		$resultProvider = new MatchResultSummaryProvider($this->getDatabase());
		$resultValues = $resultProvider->loadRootValues($ids);
		$project = $this->getProject((int) $this->getState('project_id'));

		foreach ($items as $item) {
			$item->participant_names = $participants[(int) $item->id] ?? [];
			$item->scheduled_display = $this->displayDate($item->scheduled_start, $item->timezone, $project);
			$values = $resultValues[(int) $item->id] ?? [];
			$item->result_display = $values === [] ? '' : $resultProvider->format((string) $item->result_type, $values);
		}

		return $items;
	}

	/** @return array<int,object> */
	public function getAllFilteredItems(): array
	{
		$this->setState('list.limit', 0);
		$this->setState('list.start', 0);

		return $this->getItems();
	}

	/** @return list<object> */
	public function getStageOptions(int $projectId): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([$db->quoteName('id', 'value'), $db->quoteName('name', 'text')])
			->from($db->quoteName('#__joomleague_project_stage'))
			->where($db->quoteName('project_id') . ' = :projectId')
			->order($db->quoteName('sequence_number') . ' ASC, ' . $db->quoteName('id') . ' ASC')
			->bind(':projectId', $projectId, ParameterType::INTEGER);

		return $db->setQuery($query)->loadObjectList();
	}

	/** @return list<object> */
	public function getRoundOptions(int $projectId): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([$db->quoteName('id', 'value'), $db->quoteName('name', 'text'), $db->quoteName('stage_id')])
			->from($db->quoteName('#__joomleague_project_round'))
			->where($db->quoteName('project_id') . ' = :projectId')
			->order($db->quoteName('sequence_number') . ' ASC, ' . $db->quoteName('id') . ' ASC')
			->bind(':projectId', $projectId, ParameterType::INTEGER);

		return $db->setQuery($query)->loadObjectList();
	}

	private function displayDate(?string $utc, ?string $override, object $project): string
	{
		if (!$utc) {
			return '';
		}

		$timezone = $override ?: ($project->timezone ?: (string) Factory::getApplication()->get('offset', 'UTC'));

		return Factory::getDate($utc, 'UTC')->setTimezone(new \DateTimeZone($timezone))->format(Text::_('COM_JOOMLEAGUE_DATETIME_FORMAT'));
	}
}
