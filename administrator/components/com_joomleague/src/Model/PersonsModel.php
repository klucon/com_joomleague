<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

final class PersonsModel extends ListModel
{
	public function __construct($config = [])
	{
		$config['filter_fields'] ??= ['id', 'a.id', 'first_name', 'a.first_name', 'last_name', 'a.last_name', 'nickname', 'a.nickname', 'country_code', 'a.country_code', 'published', 'a.published', 'ordering', 'a.ordering'];
		parent::__construct($config);
	}

	protected function populateState($ordering = 'a.id', $direction = 'desc'): void
	{
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search'));
		$this->setState('filter.published', $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', ''));
		parent::populateState($ordering, $direction);
	}

	protected function getListQuery()
	{
		$database = $this->getDatabase();
		$entryCount = $database->getQuery(true)->select('COUNT(*)')->from($database->quoteName('#__joomleague_project_entry', 'entry'))->where('entry.person_id = a.id');
		$membershipCount = $database->getQuery(true)->select('COUNT(*)')->from($database->quoteName('#__joomleague_project_entry_member', 'membership'))->where('membership.person_id = a.id');
		$query = $database->getQuery(true)
			->select('a.*')
			->select('(' . $entryCount . ') AS project_count')
			->select('(' . $membershipCount . ') AS membership_count')
			->select($database->quoteName('editor.name', 'editor_name'))
			->from($database->quoteName('#__joomleague_person', 'a'))
			->leftJoin($database->quoteName('#__users', 'editor') . ' ON editor.id = a.checked_out');

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
				$query->where('(a.first_name LIKE :searchFirstName OR a.last_name LIKE :searchLastName OR a.nickname LIKE :searchNickname OR a.external_code LIKE :searchExternalCode)')
					->bind(':searchFirstName', $search)
					->bind(':searchLastName', $search)
					->bind(':searchNickname', $search)
					->bind(':searchExternalCode', $search);
			}
		}

		$query->order($database->escape((string) $this->getState('list.ordering', 'a.id')) . ' ' . ($this->getState('list.direction') === 'desc' ? 'DESC' : 'ASC'));

		return $query;
	}
}
