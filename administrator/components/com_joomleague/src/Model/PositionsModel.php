<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

final class PositionsModel extends ListModel
{
	public function __construct($config = [])
	{
		$config['filter_fields'] ??= ['id', 'position.id', 'name', 'position.name', 'code', 'position.code', 'sport_type', 'sport_type.name', 'person_type', 'position.person_type', 'published', 'position.published', 'ordering', 'position.ordering'];
		parent::__construct($config);
	}

	protected function populateState($ordering = 'position.id', $direction = 'desc'): void
	{
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search'));
		$this->setState('filter.sport_type_id', $this->getUserStateFromRequest($this->context . '.filter.sport_type_id', 'filter_sport_type_id', ''));
		$this->setState('filter.person_type', $this->getUserStateFromRequest($this->context . '.filter.person_type', 'filter_person_type', ''));
		$this->setState('filter.published', $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', ''));
		parent::populateState($ordering, $direction);
	}

	protected function getListQuery()
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select(['position.*', 'sport_type.name AS sport_type_name', 'sport_type.code AS sport_type_code', 'parent.name AS parent_name', 'parent.name_key AS parent_name_key'])
			->from($db->quoteName('#__joomleague_sport_position', 'position'))
			->innerJoin($db->quoteName('#__joomleague_sport_type', 'sport_type') . ' ON sport_type.id = position.sport_type_id')
			->leftJoin($db->quoteName('#__joomleague_sport_position', 'parent') . ' ON parent.id = position.parent_id');

		$sportTypeId = $this->getState('filter.sport_type_id');
		if ($sportTypeId !== '') {
			$sportTypeId = (int) $sportTypeId;
			$query->where('position.sport_type_id = :sportTypeId')->bind(':sportTypeId', $sportTypeId, ParameterType::INTEGER);
		}

		$personType = (string) $this->getState('filter.person_type');
		if ($personType !== '') {
			$query->where('position.person_type = :personType')->bind(':personType', $personType);
		}

		$published = $this->getState('filter.published');
		if ($published !== '') {
			$published = (int) $published;
			$query->where('position.published = :published')->bind(':published', $published, ParameterType::INTEGER);
		}

		$search = trim((string) $this->getState('filter.search'));
		if ($search !== '') {
			if (str_starts_with($search, 'id:')) {
				$id = (int) substr($search, 3);
				$query->where('position.id = :id')->bind(':id', $id, ParameterType::INTEGER);
			} else {
				$like = '%' . str_replace(' ', '%', $search) . '%';
				$query->where('(position.name LIKE :search OR position.name_key LIKE :search OR position.code LIKE :search OR sport_type.name LIKE :search)')->bind(':search', $like);
			}
		}

		$order = $db->escape((string) $this->getState('list.ordering', 'position.id'));
		$direction = strtoupper((string) $this->getState('list.direction', 'DESC')) === 'DESC' ? 'DESC' : 'ASC';
		$query->order($db->quoteName($order) . ' ' . $direction)->order('position.ordering ASC')->order('position.id ASC');

		return $query;
	}

	/** @return array{sport_types:int,positions:int} */
	public function getSummary(): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select(['COUNT(*) AS positions', 'COUNT(DISTINCT sport_type_id) AS sport_types'])
			->from($db->quoteName('#__joomleague_sport_position'));
		$row = $db->setQuery($query)->loadObject();

		return ['sport_types' => (int) ($row->sport_types ?? 0), 'positions' => (int) ($row->positions ?? 0)];
	}
}
