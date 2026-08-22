<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class ProjectEntryContextRepository
{
	public function __construct(private readonly DatabaseInterface $database)
	{
	}

	public function get(int $entryId): object
	{
		if ($entryId < 1) {
			throw new \InvalidArgumentException('A positive project participant ID is required.');
		}

		$query = $this->database->getQuery(true)
			->select([
				'entry.*',
				$this->database->quoteName('project.name', 'project_name'),
				$this->database->quoteName('team.name', 'team_name'),
				$this->database->quoteName('person.first_name', 'person_first_name'),
				$this->database->quoteName('person.last_name', 'person_last_name'),
				$this->database->quoteName('version.profile_version'),
				$this->database->quoteName('version.payload_json'),
				$this->database->quoteName('profile.name_key', 'profile_name_key'),
			])
			->from($this->database->quoteName('#__joomleague_project_entry', 'entry'))
			->innerJoin($this->database->quoteName('#__joomleague_project', 'project') . ' ON project.id = entry.project_id')
			->innerJoin($this->database->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')
			->innerJoin($this->database->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id = version.profile_id')
			->leftJoin($this->database->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id')
			->leftJoin($this->database->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id')
			->where($this->database->quoteName('entry.id') . ' = :entryId')
			->bind(':entryId', $entryId, ParameterType::INTEGER);
		$entry = $this->database->setQuery($query)->loadObject();

		if ($entry === null) {
			throw new \RuntimeException('The selected project participant does not exist.');
		}

		$profile = json_decode((string) $entry->payload_json, true, 512, JSON_THROW_ON_ERROR);
		$personName = trim((string) $entry->person_first_name . ' ' . (string) $entry->person_last_name);
		$entry->resolved_name = match ((string) $entry->entry_kind) {
			'team' => (string) $entry->team_name,
			'person' => $personName,
			default => (string) $entry->display_name,
		};
		$entry->profile = $profile;
		unset($entry->payload_json);

		return $entry;
	}
}
