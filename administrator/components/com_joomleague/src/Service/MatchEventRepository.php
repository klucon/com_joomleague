<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

final class MatchEventRepository
{
	public function __construct(
		private readonly DatabaseInterface $database,
		private readonly EntryModelValidator $profileValidator = new EntryModelValidator()
	) {
	}

	/** @return array{match:object,project:object,profile:array<string,mixed>,events:array<string,array<string,mixed>>,participants:list<object>,lineup:list<object>,officials:list<object>,segments:list<object>} */
	public function getContext(int $matchId): array
	{
		if ($matchId < 1) {
			throw new \InvalidArgumentException('A positive match ID is required.');
		}

		$boundId = $matchId;
		$query = $this->database->getQuery(true)
			->select(['match.id', 'match.project_id', 'match.round_id', 'match.match_number', 'project.name AS project_name', 'project.sport_type_id', 'version.payload_json'])
			->from($this->database->quoteName('#__joomleague_project_match', 'match'))
			->innerJoin($this->database->quoteName('#__joomleague_project', 'project') . ' ON project.id = match.project_id')
			->innerJoin($this->database->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')
			->where('match.id = :matchId')
			->bind(':matchId', $boundId, ParameterType::INTEGER);
		$match = $this->database->setQuery($query)->loadObject();

		if (!$match) {
			throw new \RuntimeException('The match does not exist.');
		}

		$profile = json_decode((string) $match->payload_json, true, 512, JSON_THROW_ON_ERROR);
		$this->profileValidator->validate($profile);
		unset($match->payload_json);

		$project = (object) ['id' => (int) $match->project_id, 'name' => (string) $match->project_name, 'sport_type_id' => (int) $match->sport_type_id];

		return [
			'match' => $match,
			'project' => $project,
			'profile' => $profile,
			'events' => $this->eventDefinitions($profile),
			'participants' => $this->participants($matchId),
			'lineup' => $this->lineup($matchId),
			'officials' => $this->officials($matchId),
			'segments' => $this->segments($matchId),
		];
	}

	/** @return list<object> */
	public function getEvents(int $matchId): array
	{
		$this->getContext($matchId);
		$boundId = $matchId;
		$query = $this->database->getQuery(true)
			->select('event.*')
			->from($this->database->quoteName('#__joomleague_match_event', 'event'))
			->where('event.match_id = :matchId')
			->bind(':matchId', $boundId, ParameterType::INTEGER)
			->order('event.sequence_number ASC, event.id ASC');

		return $this->database->setQuery($query)->loadObjectList();
	}

	/** @param array<string,mixed> $data */
	public function add(int $matchId, array $data, int $actorId): int
	{
		$context = $this->getContext($matchId);
		$eventCode = $this->code((string) ($data['event_code'] ?? ''), 'event code');
		$definition = $context['events'][$eventCode] ?? null;

		if (!is_array($definition)) {
			throw new \InvalidArgumentException('The event is not defined by the project sport profile.');
		}

		$participantId = $this->positiveOrNull($data['match_participant_id'] ?? null);
		$primary = $this->lineupMember($context['lineup'], $this->positiveOrNull($data['primary_lineup_member_id'] ?? null));
		$secondary = $this->lineupMember($context['lineup'], $this->positiveOrNull($data['secondary_lineup_member_id'] ?? null));
		$official = $this->official($context['officials'], $this->positiveOrNull($data['source_match_actor_role_id'] ?? null));

		if ($primary && $participantId === null) {
			$participantId = (int) $primary->match_participant_id;
		}

		if ($primary && (int) $primary->match_participant_id !== $participantId) {
			throw new \InvalidArgumentException('The primary person does not belong to the selected match participant.');
		}

		if ($secondary && (!$primary || (int) $secondary->match_participant_id !== (int) $primary->match_participant_id || (int) $secondary->person_id === (int) $primary->person_id)) {
			throw new \InvalidArgumentException('The second person must be a different lineup member of the same match participant.');
		}

		if (!empty($definition['requires_second_person']) && (!$primary || !$secondary)) {
			throw new \InvalidArgumentException('This event requires two lineup persons.');
		}

		if (!empty($definition['system_event']) && ($participantId !== null || $primary || $secondary || $official)) {
			throw new \InvalidArgumentException('A system event cannot be assigned to a participant or actor.');
		}

		if ($primary && $official) {
			throw new \InvalidArgumentException('An event cannot use a lineup person and an official as simultaneous primary actors.');
		}

		$this->participant($context['participants'], $participantId);
		$phaseCode = $this->nullableCode($data['phase_code'] ?? null, 'phase code');
		$phaseSequence = $this->positiveOrNull($data['phase_sequence'] ?? null);
		$segmentId = $this->positiveOrNull($data['score_segment_id'] ?? null);
		$segment = $this->segment($context['segments'], $segmentId);
		$allowedPhases = $this->phaseCodes($context['profile']);

		if ($phaseCode !== null && !isset($allowedPhases[$phaseCode])) {
			throw new \InvalidArgumentException('The phase is not defined by the project sport profile.');
		}

		if ($phaseSequence !== null && $phaseCode === null) {
			throw new \InvalidArgumentException('A phase sequence requires a phase.');
		}

		if ($segment && $phaseCode !== null && (string) $segment->level_code !== $phaseCode) {
			throw new \InvalidArgumentException('The score segment does not match the selected phase.');
		}

		$clockValue = $this->decimalOrNull($data['clock_value'] ?? null, 'clock value', true);
		$clockUnit = $this->nullableCode($data['clock_unit'] ?? null, 'clock unit');

		if (($clockValue === null) !== ($clockUnit === null)) {
			throw new \InvalidArgumentException('Clock value and unit must be provided together.');
		}

		$record = (object) [
			'uuid' => UuidFactory::v4(),
			'match_id' => $matchId,
			'project_id' => (int) $context['project']->id,
			'match_participant_id' => $participantId,
			'source_event_type_id' => $this->catalogEventId((int) $context['project']->sport_type_id, $eventCode),
			'event_code' => $eventCode,
			'event_name_key' => (string) $definition['name_key'],
			'event_person_type' => is_string($definition['person_type'] ?? null) ? $definition['person_type'] : null,
			'sequence_number' => $this->nextSequence($matchId),
			'primary_lineup_member_id' => $primary ? (int) $primary->id : null,
			'primary_person_id' => $primary ? (int) $primary->person_id : null,
			'primary_name_snapshot' => $primary ? (string) $primary->display_name : null,
			'secondary_lineup_member_id' => $secondary ? (int) $secondary->id : null,
			'secondary_person_id' => $secondary ? (int) $secondary->person_id : null,
			'secondary_name_snapshot' => $secondary ? (string) $secondary->display_name : null,
			'source_match_actor_role_id' => $official ? (int) $official->id : null,
			'actor_name_snapshot' => $official ? (string) $official->display_name_snapshot : null,
			'score_segment_id' => $segmentId,
			'phase_code' => $phaseCode,
			'phase_sequence' => $phaseSequence,
			'clock_value' => $clockValue,
			'clock_unit' => $clockUnit,
			'occurred_at' => $this->dateTimeOrNull($data['occurred_at'] ?? null),
			'numeric_value' => $this->decimalOrNull($data['numeric_value'] ?? null, 'numeric value', false),
			'text_value' => $this->shortText($data['text_value'] ?? null, 255),
			'notes' => $this->shortText($data['notes'] ?? null, 65535),
			'profile_metadata_json' => json_encode($definition, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
			'created' => gmdate('Y-m-d H:i:s'),
			'created_by' => $actorId,
		];

		$this->database->insertObject('#__joomleague_match_event', $record, 'id');

		return (int) $record->id;
	}

	public function remove(int $matchId, int $eventId): void
	{
		$this->getContext($matchId);
		$boundId = $eventId;
		$boundMatchId = $matchId;
		$query = $this->database->getQuery(true)
			->delete($this->database->quoteName('#__joomleague_match_event'))
			->where('id = :id')->where('match_id = :matchId')
			->bind(':id', $boundId, ParameterType::INTEGER)->bind(':matchId', $boundMatchId, ParameterType::INTEGER);
		$this->database->setQuery($query)->execute();

		if ($this->database->getAffectedRows() !== 1) {
			throw new \InvalidArgumentException('The event does not belong to this match.');
		}
	}

	/** @param array<string,mixed> $profile @return array<string,array<string,mixed>> */
	private function eventDefinitions(array $profile): array
	{
		$result = [];

		foreach ($profile['event_types'] ?? [] as $event) {
			if (!is_array($event) || !is_string($event['code'] ?? null) || !is_string($event['name_key'] ?? null)) {
				continue;
			}

			$result[$event['code']] = $event;
		}

		return $result;
	}

	/** @return list<object> */
	private function participants(int $matchId): array
	{
		$boundId = $matchId;
		$query = $this->database->getQuery(true)->select(['participant.id', 'participant.slot_number', 'entry.display_name'])
			->from($this->database->quoteName('#__joomleague_match_participant', 'participant'))
			->innerJoin($this->database->quoteName('#__joomleague_project_entry', 'entry') . ' ON entry.id = participant.project_entry_id')
			->where('participant.match_id = :matchId')->bind(':matchId', $boundId, ParameterType::INTEGER)
			->order('participant.slot_number ASC, participant.id ASC');

		return $this->database->setQuery($query)->loadObjectList();
	}

	/** @return list<object> */
	private function lineup(int $matchId): array
	{
		$boundId = $matchId;
		$query = $this->database->getQuery(true)->select(['lineup.id', 'lineup.match_participant_id', 'lineup.person_id', 'lineup.member_person_type', 'lineup.role_code', 'person.first_name', 'person.last_name', 'person.nickname'])
			->from($this->database->quoteName('#__joomleague_match_lineup_member', 'lineup'))
			->innerJoin($this->database->quoteName('#__joomleague_person', 'person') . ' ON person.id = lineup.person_id')
			->where('lineup.match_id = :matchId')->where('lineup.published = 1')->bind(':matchId', $boundId, ParameterType::INTEGER)
			->order('lineup.match_participant_id ASC, lineup.ordering ASC, lineup.id ASC');
		$rows = $this->database->setQuery($query)->loadObjectList();

		foreach ($rows as $row) {
			$row->display_name = trim((string) $row->first_name . ((string) $row->nickname !== '' ? ' \'' . (string) $row->nickname . '\'' : '') . ' ' . (string) $row->last_name);
		}

		return $rows;
	}

	/** @return list<object> */
	private function officials(int $matchId): array
	{
		$boundId = $matchId;
		$query = $this->database->getQuery(true)->select(['id', 'role_code', 'person_type', 'display_name_snapshot'])
			->from($this->database->quoteName('#__joomleague_match_actor_role'))->where('match_id = :matchId')->where('published = 1')
			->bind(':matchId', $boundId, ParameterType::INTEGER)->order('ordering ASC, id ASC');

		return $this->database->setQuery($query)->loadObjectList();
	}

	/** @return list<object> */
	private function segments(int $matchId): array
	{
		$boundId = $matchId;
		$query = $this->database->getQuery(true)->select(['id', 'level_code', 'sequence_number'])
			->from($this->database->quoteName('#__joomleague_match_score_segment'))->where('match_id = :matchId')->where('parent_id IS NOT NULL')
			->bind(':matchId', $boundId, ParameterType::INTEGER)->order('segment_type_ordinal ASC, sequence_number ASC, id ASC');

		return $this->database->setQuery($query)->loadObjectList();
	}

	/** @param array<string,mixed> $profile @return array<string,true> */
	private function phaseCodes(array $profile): array
	{
		$result = [];

		foreach ($profile['match']['score']['segment_types'] ?? [] as $segment) {
			if (is_array($segment) && is_string($segment['code'] ?? null)) {
				$result[$segment['code']] = true;
			}
		}

		return $result;
	}

	/** @param list<object> $rows */
	private function participant(array $rows, ?int $id): ?object { if ($id === null) return null; foreach ($rows as $row) if ((int) $row->id === $id) return $row; throw new \InvalidArgumentException('The participant does not belong to this match.'); }
	/** @param list<object> $rows */
	private function lineupMember(array $rows, ?int $id): ?object { if ($id === null) return null; foreach ($rows as $row) if ((int) $row->id === $id) return $row; throw new \InvalidArgumentException('The lineup person does not belong to this match.'); }
	/** @param list<object> $rows */
	private function official(array $rows, ?int $id): ?object { if ($id === null) return null; foreach ($rows as $row) if ((int) $row->id === $id) return $row; throw new \InvalidArgumentException('The official does not belong to this match.'); }
	/** @param list<object> $rows */
	private function segment(array $rows, ?int $id): ?object { if ($id === null) return null; foreach ($rows as $row) if ((int) $row->id === $id) return $row; throw new \InvalidArgumentException('The score segment does not belong to this match.'); }

	private function catalogEventId(int $sportTypeId, string $code): ?int
	{
		$boundSport = $sportTypeId; $boundCode = $code;
		$query = $this->database->getQuery(true)->select('id')->from($this->database->quoteName('#__joomleague_event_type'))
			->where('sport_type_id = :sportType')->where('code = :code')->where('published = 1')
			->bind(':sportType', $boundSport, ParameterType::INTEGER)->bind(':code', $boundCode);
		$id = (int) $this->database->setQuery($query)->loadResult();

		return $id > 0 ? $id : null;
	}

	private function nextSequence(int $matchId): int
	{
		$boundId = $matchId;
		$query = $this->database->getQuery(true)->select('MAX(sequence_number)')->from($this->database->quoteName('#__joomleague_match_event'))
			->where('match_id = :matchId')->bind(':matchId', $boundId, ParameterType::INTEGER);

		return (int) $this->database->setQuery($query)->loadResult() + 1;
	}

	private function positiveOrNull(mixed $value): ?int { if ($value === null || $value === '') return null; $value = filter_var($value, FILTER_VALIDATE_INT); if ($value === false || $value < 1) throw new \InvalidArgumentException('A referenced ID or sequence must be a positive integer.'); return $value; }
	private function code(string $value, string $label): string { $value = trim($value); if (preg_match('/^[a-z][a-z0-9_]*$/', $value) !== 1) throw new \InvalidArgumentException('The ' . $label . ' is invalid.'); return $value; }
	private function nullableCode(mixed $value, string $label): ?string { $value = trim((string) $value); return $value === '' ? null : $this->code($value, $label); }
	private function decimalOrNull(mixed $value, string $label, bool $nonNegative): ?string { $value = trim((string) $value); if ($value === '') return null; if (preg_match('/^-?\d{1,21}(?:\.\d{1,9})?$/', $value) !== 1 || ($nonNegative && str_starts_with($value, '-'))) throw new \InvalidArgumentException('The ' . $label . ' is invalid.'); return $value; }
	private function dateTimeOrNull(mixed $value): ?string { $value = trim((string) $value); if ($value === '') return null; $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, new \DateTimeZone('UTC')); if (!$date || $date->format('Y-m-d\TH:i') !== $value) throw new \InvalidArgumentException('The event timestamp is invalid.'); return $date->format('Y-m-d H:i:s'); }
	private function shortText(mixed $value, int $maximum): ?string { $value = trim((string) $value); if ($value === '') return null; if (strlen($value) > $maximum) throw new \LengthException('The text value is too long.'); return $value; }
}
