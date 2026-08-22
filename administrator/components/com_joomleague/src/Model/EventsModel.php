<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

final class EventsModel extends ListModel
{
	public function __construct($config = [])
	{
		$config['filter_fields'] ??= ['id', 'event.id', 'name', 'event.name', 'code', 'event.code', 'sport_type', 'sport_type.name', 'timeline', 'event.timeline', 'published', 'event.published', 'ordering', 'event.ordering'];
		parent::__construct($config);
	}

	protected function populateState($ordering = 'event.id', $direction = 'desc'): void
	{
		foreach (['search', 'sport_type_id', 'timeline', 'published'] as $filter) {
			$this->setState('filter.' . $filter, $this->getUserStateFromRequest($this->context . '.filter.' . $filter, 'filter_' . $filter, ''));
		}
		parent::populateState($ordering, $direction);
	}

	protected function getListQuery()
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select(['event.*', 'sport_type.name AS sport_type_name', 'sport_type.code AS sport_type_code'])
			->from($db->quoteName('#__joomleague_event_type', 'event'))
			->innerJoin($db->quoteName('#__joomleague_sport_type', 'sport_type') . ' ON sport_type.id = event.sport_type_id');

		foreach (['sport_type_id' => 'sportTypeId', 'timeline' => 'timeline', 'published' => 'published'] as $filter => $parameter) {
			$value = $this->getState('filter.' . $filter);
			if ($value !== '') {
				$value = (int) $value;
				$query->where('event.' . $filter . ' = :' . $parameter)->bind(':' . $parameter, $value, ParameterType::INTEGER);
			}
		}

		$search = trim((string) $this->getState('filter.search'));
		if ($search !== '') {
			if (str_starts_with($search, 'id:')) {
				$id = (int) substr($search, 3);
				$query->where('event.id = :id')->bind(':id', $id, ParameterType::INTEGER);
			} else {
				$like = '%' . str_replace(' ', '%', $search) . '%';
				$query->where('(event.name LIKE :search OR event.name_key LIKE :search OR event.code LIKE :search OR sport_type.name LIKE :search)')->bind(':search', $like);
			}
		}

		$order = $db->escape((string) $this->getState('list.ordering', 'event.id'));
		$direction = strtoupper((string) $this->getState('list.direction', 'DESC')) === 'DESC' ? 'DESC' : 'ASC';
		$query->order($db->quoteName($order) . ' ' . $direction)->order('event.ordering ASC')->order('event.id ASC');

		return $query;
	}

	/** @return array{sport_types:int,event_types:int} */
	public function getSummary(): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)->select(['COUNT(*) AS event_types', 'COUNT(DISTINCT sport_type_id) AS sport_types'])->from($db->quoteName('#__joomleague_event_type'));
		$row = $db->setQuery($query)->loadObject();
		return ['sport_types' => (int) ($row->sport_types ?? 0), 'event_types' => (int) ($row->event_types ?? 0)];
	}
}
