<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

final class SporttypesModel extends ListModel
{
	public function __construct($config = [])
	{
		$config['filter_fields'] ??= ['id', 'a.id', 'name', 'a.name', 'code', 'a.code', 'profile', 'profile.name_key', 'published', 'a.published', 'ordering', 'a.ordering'];
		parent::__construct($config);
	}

	protected function populateState($ordering = 'a.id', $direction = 'desc'): void
	{
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search'));
		$this->setState('filter.published', $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', ''));
		$this->setState('filter.profile_id', $this->getUserStateFromRequest($this->context . '.filter.profile_id', 'filter_profile_id', ''));
		parent::populateState($ordering, $direction);
	}

	protected function getListQuery()
	{
		$db = $this->getDatabase();
		$projectCount = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_project', 'project'))->where('project.sport_type_id = a.id');
		$query = $db->getQuery(true)
			->select('a.*')
			->select([$db->quoteName('profile.id', 'profile_id'), $db->quoteName('profile.name_key', 'profile_name_key'), $db->quoteName('version.profile_version'), $db->quoteName('version.state', 'profile_state'), $db->quoteName('editor.name', 'editor_name')])
			->select('(' . $projectCount . ') AS project_count')
			->from($db->quoteName('#__joomleague_sport_type', 'a'))
			->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = a.profile_version_id')
			->innerJoin($db->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id = version.profile_id')
			->leftJoin($db->quoteName('#__users', 'editor') . ' ON editor.id = a.checked_out');

		$published = $this->getState('filter.published');
		if ($published !== '') {
			$published = (int) $published;
			$query->where('a.published = :published')->bind(':published', $published, ParameterType::INTEGER);
		}
		$profileId = (int) $this->getState('filter.profile_id');
		if ($profileId > 0) {
			$query->where('profile.id = :profileId')->bind(':profileId', $profileId, ParameterType::INTEGER);
		}
		$search = trim((string) $this->getState('filter.search'));
		if ($search !== '') {
			if (str_starts_with($search, 'id:')) {
				$id = (int) substr($search, 3);
				$query->where('a.id = :id')->bind(':id', $id, ParameterType::INTEGER);
			} else {
				$search = '%' . $search . '%';
				$query->where('(a.name LIKE :searchName OR a.code LIKE :searchCode)')->bind(':searchName', $search)->bind(':searchCode', $search);
			}
		}
		$query->order($db->escape((string) $this->getState('list.ordering', 'a.id')) . ' ' . ($this->getState('list.direction') === 'desc' ? 'DESC' : 'ASC'));
		return $query;
	}
}
