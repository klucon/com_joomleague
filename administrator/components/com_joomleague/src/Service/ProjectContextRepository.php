<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class ProjectContextRepository
{
	public function __construct(private readonly DatabaseInterface $database)
	{
	}

	public function get(int $projectId): object
	{
		if ($projectId < 1) {
			throw new \InvalidArgumentException('A positive project ID is required.');
		}

		$query = $this->database->getQuery(true)
			->select([
				$this->database->quoteName('project.id'),
				$this->database->quoteName('project.name'),
				$this->database->quoteName('project.code'),
				$this->database->quoteName('project.project_type'),
				$this->database->quoteName('project.lifecycle_state'),
				$this->database->quoteName('project.published'),
				$this->database->quoteName('project.timezone'),
				$this->database->quoteName('project.default_start_time'),
				$this->database->quoteName('project.profile_version_id'),
				$this->database->quoteName('competition.name', 'competition_name'),
				$this->database->quoteName('season.name', 'season_name'),
				$this->database->quoteName('sport_type.name', 'sport_type_name'),
				$this->database->quoteName('profile.name_key', 'profile_name_key'),
				$this->database->quoteName('version.profile_version'),
				$this->database->quoteName('version.payload_json'),
			])
			->from($this->database->quoteName('#__joomleague_project', 'project'))
			->innerJoin($this->database->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id')
			->innerJoin($this->database->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id')
			->innerJoin($this->database->quoteName('#__joomleague_sport_type', 'sport_type') . ' ON sport_type.id = project.sport_type_id')
			->innerJoin($this->database->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')
			->innerJoin($this->database->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id = version.profile_id')
			->where($this->database->quoteName('project.id') . ' = :projectId')
			->bind(':projectId', $projectId, ParameterType::INTEGER);
		$project = $this->database->setQuery($query)->loadObject();

		if ($project === null) {
			throw new \RuntimeException('The selected project does not exist.');
		}

		$profile = json_decode((string) $project->payload_json, true, 512, JSON_THROW_ON_ERROR);

		if (!is_array($profile) || (array_is_list($profile) && $profile !== [])) {
			throw new \UnexpectedValueException('The project sport profile must be a JSON object.');
		}

		$project->profile = $profile;
		unset($project->payload_json);

		return $project;
	}
}
