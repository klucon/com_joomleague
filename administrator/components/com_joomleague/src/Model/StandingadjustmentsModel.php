<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

final class StandingadjustmentsModel extends ListModel
{
	public function __construct($config = []) { $config['filter_fields'] ??= ['id', 'a.id', 'effective_date', 'a.effective_date', 'ordering', 'a.ordering', 'published', 'a.published']; parent::__construct($config); }
	protected function populateState($ordering = 'a.id', $direction = 'desc'): void
	{
		$this->setState('project_id', $this->getUserStateFromRequest($this->context . '.project_id', 'project_id', 0, 'uint')); $this->setState('stage_id', $this->getUserStateFromRequest($this->context . '.stage_id', 'stage_id', 0, 'uint'));
		foreach (['search', 'published'] as $filter) $this->setState('filter.' . $filter, $this->getUserStateFromRequest($this->context . '.filter.' . $filter, 'filter_' . $filter, '')); parent::populateState($ordering, $direction);
	}
	public function getProject(): object
	{
		$projectId = (int) $this->getState('project_id'); $query = $this->getDatabase()->getQuery(true)->select(['id', 'name'])->from($this->getDatabase()->quoteName('#__joomleague_project'))->where('id = :project')->bind(':project', $projectId, ParameterType::INTEGER); $project = $this->getDatabase()->setQuery($query)->loadObject(); if (!$project) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_STANDING_ADJUSTMENT_PROJECT_INVALID')); return $project;
	}
	protected function getListQuery()
	{
		$db = $this->getDatabase(); $projectId = (int) $this->getState('project_id'); $stageKey = (int) $this->getState('stage_id');
		$query = $db->getQuery(true)->select(['a.*', $db->quoteName('entry.display_name', 'entry_name'), $db->quoteName('team.name', 'team_name'), $db->quoteName('person.first_name'), $db->quoteName('person.last_name')])->from($db->quoteName('#__joomleague_standing_adjustment', 'a'))->innerJoin($db->quoteName('#__joomleague_project_entry', 'entry') . ' ON entry.id = a.project_entry_id')->leftJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id')->leftJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id')->where('a.project_id = :project')->where('a.stage_key = :stage')->bind(':project', $projectId, ParameterType::INTEGER)->bind(':stage', $stageKey, ParameterType::INTEGER);
		$published = $this->getState('filter.published'); if ($published !== '') { $published = (int) $published; $query->where('a.published = :published')->bind(':published', $published, ParameterType::INTEGER); }
		$search = trim((string) $this->getState('filter.search')); if ($search !== '') { $search = '%' . $search . '%'; $query->where('(a.reason LIKE :reason OR a.metric_code LIKE :metric OR entry.display_name LIKE :entry)')->bind(':reason', $search)->bind(':metric', $search)->bind(':entry', $search); }
		$query->order($db->escape((string) $this->getState('list.ordering', 'a.id')) . ' ' . ($this->getState('list.direction') === 'desc' ? 'DESC' : 'ASC')); return $query;
	}
}
