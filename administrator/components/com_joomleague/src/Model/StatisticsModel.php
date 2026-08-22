<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

final class StatisticsModel extends ListModel
{
	public function __construct($config = [])
	{
		$config['filter_fields'] ??= ['id', 'statistic.id', 'name', 'statistic.name', 'code', 'statistic.code', 'sport_type', 'sport_type.name', 'statistic_type', 'statistic.statistic_type', 'scope', 'statistic.scope', 'published', 'statistic.published', 'ordering', 'statistic.ordering'];
		parent::__construct($config);
	}

	protected function populateState($ordering = 'statistic.id', $direction = 'desc'): void
	{
		foreach (['search', 'sport_type_id', 'statistic_type', 'scope', 'published'] as $filter) {
			$this->setState('filter.' . $filter, $this->getUserStateFromRequest($this->context . '.filter.' . $filter, 'filter_' . $filter, ''));
		}
		parent::populateState($ordering, $direction);
	}

	protected function getListQuery()
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select(['statistic.*', 'sport_type.name AS sport_type_name', 'sport_type.code AS sport_type_code'])
			->from($db->quoteName('#__joomleague_statistic', 'statistic'))
			->innerJoin($db->quoteName('#__joomleague_sport_type', 'sport_type') . ' ON sport_type.id = statistic.sport_type_id');

		$sportTypeId = $this->getState('filter.sport_type_id');
		if ($sportTypeId !== '') {
			$sportTypeId = (int) $sportTypeId;
			$query->where('statistic.sport_type_id = :sportTypeId')->bind(':sportTypeId', $sportTypeId, ParameterType::INTEGER);
		}
		foreach (['statistic_type' => 'statisticType', 'scope' => 'scope'] as $filter => $parameter) {
			$value = (string) $this->getState('filter.' . $filter);
			if ($value !== '') $query->where('statistic.' . $filter . ' = :' . $parameter)->bind(':' . $parameter, $value);
		}
		$published = $this->getState('filter.published');
		if ($published !== '') {
			$published = (int) $published;
			$query->where('statistic.published = :published')->bind(':published', $published, ParameterType::INTEGER);
		}

		$search = trim((string) $this->getState('filter.search'));
		if ($search !== '') {
			if (str_starts_with($search, 'id:')) {
				$id = (int) substr($search, 3);
				$query->where('statistic.id = :id')->bind(':id', $id, ParameterType::INTEGER);
			} else {
				$like = '%' . str_replace(' ', '%', $search) . '%';
				$query->where('(statistic.name LIKE :search OR statistic.name_key LIKE :search OR statistic.code LIKE :search OR sport_type.name LIKE :search)')->bind(':search', $like);
			}
		}

		$order = $db->escape((string) $this->getState('list.ordering', 'statistic.id'));
		$direction = strtoupper((string) $this->getState('list.direction', 'DESC')) === 'DESC' ? 'DESC' : 'ASC';
		$query->order($db->quoteName($order) . ' ' . $direction)->order('statistic.ordering ASC')->order('statistic.id ASC');

		return $query;
	}

	/** @return array{sport_types:int,statistics:int} */
	public function getSummary(): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)->select(['COUNT(*) AS statistics', 'COUNT(DISTINCT sport_type_id) AS sport_types'])->from($db->quoteName('#__joomleague_statistic'));
		$row = $db->setQuery($query)->loadObject();
		return ['sport_types' => (int) ($row->sport_types ?? 0), 'statistics' => (int) ($row->statistics ?? 0)];
	}
}
