<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectContextRepository;

final class StagesModel extends ListModel
{
	public function __construct($config = [])
	{
		$config['filter_fields'] ??= ['id', 'a.id', 'name', 'a.name', 'code', 'a.code', 'stage_type', 'a.stage_type', 'published', 'a.published', 'ordering', 'a.ordering'];
		parent::__construct($config);
	}

	protected function populateState($ordering = 'a.id', $direction = 'desc'): void
	{
		$this->setState('project_id', $this->getUserStateFromRequest($this->context . '.project_id', 'project_id', 0, 'uint'));
		foreach (['search', 'published'] as $filter) $this->setState('filter.' . $filter, $this->getUserStateFromRequest($this->context . '.filter.' . $filter, 'filter_' . $filter, ''));
		parent::populateState($ordering, $direction);
	}

	public function getProject(): object
	{
		return (new ProjectContextRepository($this->getDatabase()))->get((int) $this->getState('project_id'));
	}

	protected function getListQuery()
	{
		$db = $this->getDatabase();
		$projectId = (int) $this->getState('project_id');
		$query = $db->getQuery(true)->select('a.*')
			->select([$db->quoteName('parent.name', 'parent_name'), $db->quoteName('editor.name', 'editor_name')])
			->from($db->quoteName('#__joomleague_project_stage', 'a'))
			->leftJoin($db->quoteName('#__joomleague_project_stage', 'parent') . ' ON parent.id = a.parent_id')
			->leftJoin($db->quoteName('#__users', 'editor') . ' ON editor.id = a.checked_out')
			->where('a.project_id = :projectId')->bind(':projectId', $projectId, ParameterType::INTEGER);

		$published = $this->getState('filter.published');
		if ($published !== '') { $published = (int) $published; $query->where('a.published = :published')->bind(':published', $published, ParameterType::INTEGER); }
		$search = trim((string) $this->getState('filter.search'));
		if ($search !== '') {
			if (str_starts_with($search, 'id:')) { $id = (int) substr($search, 3); $query->where('a.id = :id')->bind(':id', $id, ParameterType::INTEGER); }
			else { $search = '%' . $search . '%'; $query->where('(a.name LIKE :name OR a.code LIKE :code OR a.stage_type LIKE :type)')->bind(':name', $search)->bind(':code', $search)->bind(':type', $search); }
		}

		$query->order($db->escape((string) $this->getState('list.ordering', 'a.id')) . ' ' . ($this->getState('list.direction') === 'desc' ? 'DESC' : 'ASC'));

		return $query;
	}
}
