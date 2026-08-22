<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

final class VenuesModel extends ListModel
{
	public function __construct($config = [])
	{
		$config['filter_fields'] ??= ['id', 'a.id', 'name', 'a.name', 'short_name', 'a.short_name', 'city', 'a.city', 'owner_club_name', 'club.name', 'capacity', 'a.capacity', 'published', 'a.published', 'ordering', 'a.ordering'];
		parent::__construct($config);
	}

	protected function populateState($ordering = 'a.id', $direction = 'desc'): void
	{
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search'));
		$this->setState('filter.owner_club_id', $this->getUserStateFromRequest($this->context . '.filter.owner_club_id', 'filter_owner_club_id', ''));
		$this->setState('filter.published', $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', ''));
		parent::populateState($ordering, $direction);
	}

	protected function getListQuery()
	{
		$database = $this->getDatabase();
		$query = $database->getQuery(true)
			->select('a.*')
			->select($database->quoteName('club.name', 'owner_club_name'))
			->select($database->quoteName('editor.name', 'editor_name'))
			->from($database->quoteName('#__joomleague_venue', 'a'))
			->leftJoin($database->quoteName('#__joomleague_club', 'club') . ' ON club.id = a.owner_club_id')
			->leftJoin($database->quoteName('#__users', 'editor') . ' ON editor.id = a.checked_out');

		$ownerClubId = $this->getState('filter.owner_club_id');
		if ($ownerClubId !== '') {
			$ownerClubId = (int) $ownerClubId;
			$query->where('a.owner_club_id = :ownerClubId')->bind(':ownerClubId', $ownerClubId, ParameterType::INTEGER);
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
				$query->where('(a.name LIKE :searchName OR a.short_name LIKE :searchShort OR a.city LIKE :searchCity OR a.external_code LIKE :searchCode)')
					->bind(':searchName', $search)->bind(':searchShort', $search)->bind(':searchCity', $search)->bind(':searchCode', $search);
			}
		}
		$query->order($database->escape((string) $this->getState('list.ordering', 'a.id')) . ' ' . ($this->getState('list.direction') === 'desc' ? 'DESC' : 'ASC'));
		return $query;
	}
}
