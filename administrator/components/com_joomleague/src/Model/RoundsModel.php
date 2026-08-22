<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

final class RoundsModel extends ListModel
{
	public function __construct($config = [])
	{
		$config['filter_fields'] ??= ['id', 'a.id', 'name', 'a.name', 'sequence_number', 'a.sequence_number', 'start_date', 'a.start_date', 'published', 'a.published', 'ordering', 'a.ordering']; parent::__construct($config);
	}

	protected function populateState($ordering = 'a.id', $direction = 'desc'): void
	{
		$this->setState('stage_id', $this->getUserStateFromRequest($this->context . '.stage_id', 'stage_id', 0, 'uint'));
		foreach (['search', 'published', 'lifecycle_state'] as $filter) $this->setState('filter.' . $filter, $this->getUserStateFromRequest($this->context . '.filter.' . $filter, 'filter_' . $filter, ''));
		parent::populateState($ordering, $direction);
	}

	public function getStage(): object
	{
		$db = $this->getDatabase(); $stageId = (int) $this->getState('stage_id');
		$query = $db->getQuery(true)->select(['stage.*', $db->quoteName('project.name', 'project_name')])->from($db->quoteName('#__joomleague_project_stage', 'stage'))
			->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = stage.project_id')->where($db->quoteName('stage.id') . ' = :stageId')->bind(':stageId', $stageId, ParameterType::INTEGER);
		$stage = $db->setQuery($query)->loadObject(); if (!$stage) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_ROUND_STAGE_INVALID')); return $stage;
	}

	protected function getListQuery()
	{
		$db = $this->getDatabase(); $stageId = (int) $this->getState('stage_id'); $query = $db->getQuery(true)->select('a.*')->select($db->quoteName('editor.name', 'editor_name'))
			->from($db->quoteName('#__joomleague_project_round', 'a'))->leftJoin($db->quoteName('#__users', 'editor') . ' ON editor.id = a.checked_out')
			->where($db->quoteName('a.stage_id') . ' = :stageId')->bind(':stageId', $stageId, ParameterType::INTEGER);
		$published = $this->getState('filter.published'); if ($published !== '') { $published = (int) $published; $query->where('a.published = :published')->bind(':published', $published, ParameterType::INTEGER); }
		$lifecycle = trim((string) $this->getState('filter.lifecycle_state')); if ($lifecycle !== '') $query->where('a.lifecycle_state = :lifecycle')->bind(':lifecycle', $lifecycle);
		$search = trim((string) $this->getState('filter.search'));
		if ($search !== '') { if (str_starts_with($search, 'id:')) { $id = (int) substr($search, 3); $query->where('a.id = :id')->bind(':id', $id, ParameterType::INTEGER); } else { $search = '%' . $search . '%'; $query->where('(a.name LIKE :name OR a.code LIKE :code OR a.round_type LIKE :type)')->bind(':name', $search)->bind(':code', $search)->bind(':type', $search); } }
		$query->order($db->escape((string) $this->getState('list.ordering', 'a.id')) . ' ' . ($this->getState('list.direction') === 'desc' ? 'DESC' : 'ASC')); return $query;
	}
}
