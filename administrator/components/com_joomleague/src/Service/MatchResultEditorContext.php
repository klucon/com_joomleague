<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class MatchResultEditorContext
{
	public function __construct(
		private readonly DatabaseInterface $database,
		private readonly MatchResultRepository $repository,
		private readonly MatchResultEditorSchemaBuilder $schemaBuilder = new MatchResultEditorSchemaBuilder(),
		private readonly MatchResultFormStateBuilder $formStateBuilder = new MatchResultFormStateBuilder()
	) {
	}

	/** @return array<string,mixed> */
	public function get(int $matchId): array
	{
		if ($matchId < 1) throw new \InvalidArgumentException('Match identifier is invalid.');

		$query = $this->database->getQuery(true)
			->select([
				$this->database->quoteName('match.id', 'match_id'),
				$this->database->quoteName('match.match_number'),
				$this->database->quoteName('match.status_code', 'match_status_code'),
				$this->database->quoteName('match.scheduled_start'),
				$this->database->quoteName('project.id', 'project_id'),
				$this->database->quoteName('project.name', 'project_name'),
				$this->database->quoteName('round.id', 'round_id'),
				$this->database->quoteName('round.name', 'round_name'),
				$this->database->quoteName('profile.code', 'profile_code'),
				$this->database->quoteName('version.profile_version'),
				$this->database->quoteName('version.payload_json'),
			])
			->from($this->database->quoteName('#__joomleague_project_match', 'match'))
			->innerJoin($this->database->quoteName('#__joomleague_project', 'project') . ' ON project.id = match.project_id')
			->innerJoin($this->database->quoteName('#__joomleague_project_round', 'round') . ' ON round.id = match.round_id')
			->innerJoin($this->database->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')
			->innerJoin($this->database->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id = version.profile_id')
			->where('match.id = :matchId')
			->bind(':matchId', $matchId, ParameterType::INTEGER);
		$row = $this->database->setQuery($query)->loadAssoc();

		if (!$row) throw new \RuntimeException('The selected match does not exist.');

		$profile = json_decode((string) $row['payload_json'], true, 512, JSON_THROW_ON_ERROR);

		if (!is_array($profile)) throw new \UnexpectedValueException('Match profile payload is invalid.');

		$query = $this->database->getQuery(true)
			->select([
				$this->database->quoteName('participant.id'),
				$this->database->quoteName('participant.slot_number'),
				$this->database->quoteName('entry.entry_kind'),
				$this->database->quoteName('entry.display_name'),
				$this->database->quoteName('team.name', 'team_name'),
				$this->database->quoteName('person.first_name'),
				$this->database->quoteName('person.last_name'),
			])
			->from($this->database->quoteName('#__joomleague_match_participant', 'participant'))
			->innerJoin($this->database->quoteName('#__joomleague_project_entry', 'entry') . ' ON entry.id = participant.project_entry_id')
			->leftJoin($this->database->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id')
			->leftJoin($this->database->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id')
			->where('participant.match_id = :matchId')
			->where('participant.published = 1')
			->order('participant.slot_number ASC')
			->order('participant.id ASC')
			->bind(':matchId', $matchId, ParameterType::INTEGER);
		$participants = [];

		foreach ($this->database->setQuery($query)->loadAssocList() as $participant) {
			$name = match ((string) $participant['entry_kind']) {
				'team' => (string) $participant['team_name'],
				'person' => trim((string) $participant['first_name'] . ' ' . (string) $participant['last_name']),
				default => (string) $participant['display_name'],
			};
			$participants[] = [
				'id' => (int) $participant['id'],
				'slot_number' => (int) $participant['slot_number'],
				'entry_kind' => (string) $participant['entry_kind'],
				'name' => $name,
			];
		}

		$editorSchema = $this->schemaBuilder->build($profile);
		$result = $this->repository->get((int) $matchId);

		return [
			'match' => [
				'id' => (int) $row['match_id'],
				'match_number' => $row['match_number'] === null ? null : (string) $row['match_number'],
				'status_code' => (string) $row['match_status_code'],
				'scheduled_start' => $row['scheduled_start'] === null ? null : (string) $row['scheduled_start'],
			],
			'project' => ['id' => (int) $row['project_id'], 'name' => (string) $row['project_name']],
			'round' => ['id' => (int) $row['round_id'], 'name' => (string) $row['round_name']],
			'profile' => ['code' => (string) $row['profile_code'], 'version' => (string) $row['profile_version']],
			'profile_payload' => $profile,
			'editor_schema' => $editorSchema,
			'participants' => $participants,
			'result' => $result,
			'form_state' => $this->formStateBuilder->build($editorSchema, $participants, $result),
		];
	}
}
