<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

final class TeamsModel extends ListModel
{
	public function __construct($config = [])
	{
		$config['filter_fields'] ??= ['id', 'a.id', 'name', 'a.name', 'middle_name', 'a.middle_name', 'short_name', 'a.short_name', 'club_name', 'club.name', 'published', 'a.published', 'ordering', 'a.ordering'];
		parent::__construct($config);
	}

	protected function populateState($ordering = 'a.id', $direction = 'desc'): void
	{
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search'));
		$this->setState('filter.club_id', $this->getUserStateFromRequest($this->context . '.filter.club_id', 'filter_club_id', ''));
		$this->setState('filter.published', $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', ''));
		parent::populateState($ordering, $direction);
	}

	protected function getListQuery()
	{
		$database = $this->getDatabase();
		$entryCount = $database->getQuery(true)
			->select('COUNT(*)')
			->from($database->quoteName('#__joomleague_project_entry', 'entry'))
			->where('entry.team_id = a.id');
		$query = $database->getQuery(true)
			->select('a.*')
			->select($database->quoteName('club.name', 'club_name'))
			->select('(' . $entryCount . ') AS project_count')
			->select($database->quoteName('editor.name', 'editor_name'))
			->from($database->quoteName('#__joomleague_team', 'a'))
			->leftJoin($database->quoteName('#__joomleague_club', 'club') . ' ON club.id = a.club_id')
			->leftJoin($database->quoteName('#__users', 'editor') . ' ON editor.id = a.checked_out');

		$clubId = $this->getState('filter.club_id');
		if ($clubId !== '') {
			$clubId = (int) $clubId;
			$query->where('a.club_id = :clubId')->bind(':clubId', $clubId, ParameterType::INTEGER);
		}

		$published = $this->getState('filter.published');
		if ($published !== '') {
			$published = (int) $published;
			$query->where('a.published = :published')->bind(':published', $published, ParameterType::INTEGER);
		}

		$search = trim((string) $this->getState('filter.search'));
		if ($search !== '') {
			if (str_starts_with($search, 'id:')) {
				$id = (int) substr($search, 3);
				$query->where('a.id = :id')->bind(':id', $id, ParameterType::INTEGER);
			} else {
				$search = '%' . $search . '%';
				$query->where('(a.name LIKE :searchName OR a.middle_name LIKE :searchMiddleName OR a.short_name LIKE :searchShortName OR a.external_code LIKE :searchExternalCode)')
					->bind(':searchName', $search)
					->bind(':searchMiddleName', $search)
					->bind(':searchShortName', $search)
					->bind(':searchExternalCode', $search);
			}
		}

		$query->order($database->escape((string) $this->getState('list.ordering', 'a.id')) . ' ' . ($this->getState('list.direction') === 'desc' ? 'DESC' : 'ASC'));

		return $query;
	}
}
