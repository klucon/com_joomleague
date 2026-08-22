<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectEntryContextRepository;

final class EntrymembersModel extends BaseDatabaseModel
{
	public function getEntry(int $entryId): object
	{
		return (new ProjectEntryContextRepository($this->getDatabase()))->get($entryId);
	}

	/** @return list<object> */
	public function getMembers(int $entryId): array
	{
		$this->getEntry($entryId);
		$query = $this->getDatabase()->getQuery(true)
			->select(['member.*', 'person.first_name', 'person.last_name', 'person.picture'])
			->from($this->getDatabase()->quoteName('#__joomleague_project_entry_member', 'member'))
			->innerJoin($this->getDatabase()->quoteName('#__joomleague_person', 'person') . ' ON person.id = member.person_id')
			->where($this->getDatabase()->quoteName('member.entry_id') . ' = :entryId')
			->order($this->getDatabase()->quoteName('member.ordering') . ' ASC, ' . $this->getDatabase()->quoteName('member.id') . ' ASC')
			->bind(':entryId', $entryId, ParameterType::INTEGER);

		return $this->getDatabase()->setQuery($query)->loadObjectList();
	}
}
