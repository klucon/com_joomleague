<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

final class ProjectsModel extends ListModel
{
	public function __construct($config = [])
	{
		$config['filter_fields'] ??= ['id', 'a.id', 'name', 'a.name', 'competition', 'competition.name', 'season', 'season.name', 'sport_type', 'sport_type.name', 'project_type', 'a.project_type', 'lifecycle_state', 'a.lifecycle_state', 'published', 'a.published', 'ordering', 'a.ordering'];
		parent::__construct($config);
	}

	protected function populateState($ordering = 'a.id', $direction = 'desc'): void
	{
		foreach (['search', 'published', 'competition_id', 'season_id', 'sport_type_id', 'project_type', 'lifecycle_state'] as $filter) {
			$this->setState('filter.' . $filter, $this->getUserStateFromRequest($this->context . '.filter.' . $filter, 'filter_' . $filter, ''));
		}
		parent::populateState($ordering, $direction);
	}

	protected function getListQuery()
	{
		$db = $this->getDatabase();
		$ruleCount = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_project_rule_config', 'rule_config'))->where('rule_config.project_id = a.id');
		$templateCount = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_project_template_config', 'template_config'))->where('template_config.project_id = a.id');
		$query = $db->getQuery(true)
			->select('a.*')
			->select([$db->quoteName('competition.name', 'competition_name'), $db->quoteName('season.name', 'season_name'), $db->quoteName('sport_type.name', 'sport_type_name')])
			->select([$db->quoteName('profile.name_key', 'profile_name_key'), $db->quoteName('version.profile_version'), $db->quoteName('editor.name', 'editor_name')])
			->select('(' . $ruleCount . ') AS rule_override_count')
			->select('(' . $templateCount . ') AS template_override_count')
			->from($db->quoteName('#__joomleague_project', 'a'))
			->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = a.competition_id')
			->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = a.season_id')
			->innerJoin($db->quoteName('#__joomleague_sport_type', 'sport_type') . ' ON sport_type.id = a.sport_type_id')
			->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = a.profile_version_id')
			->innerJoin($db->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id = version.profile_id')
			->leftJoin($db->quoteName('#__users', 'editor') . ' ON editor.id = a.checked_out');

		foreach (['competition_id', 'season_id', 'sport_type_id'] as $filter) {
			$value = (int) $this->getState('filter.' . $filter);
			if ($value > 0) {
				$query->where('a.' . $filter . ' = :' . $filter)->bind(':' . $filter, $value, ParameterType::INTEGER);
			}
		}

		$published = $this->getState('filter.published');
		if ($published !== '') {
			$published = (int) $published;
			$query->where('a.published = :published')->bind(':published', $published, ParameterType::INTEGER);
		}

		foreach (['project_type', 'lifecycle_state'] as $filter) {
			$value = trim((string) $this->getState('filter.' . $filter));
			if ($value !== '') {
				$query->where('a.' . $filter . ' = :' . $filter)->bind(':' . $filter, $value);
			}
		}

		$search = trim((string) $this->getState('filter.search'));
		if ($search !== '') {
			if (str_starts_with($search, 'id:')) {
				$id = (int) substr($search, 3);
				$query->where('a.id = :id')->bind(':id', $id, ParameterType::INTEGER);
			} else {
				$search = '%' . $search . '%';
				$query->where('(a.name LIKE :searchName OR a.code LIKE :searchCode OR a.external_code LIKE :searchExternal)')
					->bind(':searchName', $search)->bind(':searchCode', $search)->bind(':searchExternal', $search);
			}
		}

		$query->order($db->escape((string) $this->getState('list.ordering', 'a.id')) . ' ' . ($this->getState('list.direction') === 'desc' ? 'DESC' : 'ASC'));
		return $query;
	}
}
