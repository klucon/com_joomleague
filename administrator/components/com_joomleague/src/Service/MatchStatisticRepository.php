<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

final class MatchStatisticRepository
{
	private const WRITABLE_SOURCES = ['manual', 'manual_or_import'];

	public function __construct(
		private readonly DatabaseInterface $database,
		private readonly EntryModelValidator $profileValidator = new EntryModelValidator()
	) {
	}

	/** @return array{match:object,project:object,profile:array<string,mixed>,statistics:array<string,array<string,mixed>>,participants:list<object>,lineup:list<object>,segments:list<object>} */
	public function getContext(int $matchId): array
	{
		if ($matchId < 1) throw new \InvalidArgumentException('A positive match ID is required.');
		$boundId = $matchId;
		$query = $this->database->getQuery(true)
			->select(['match.id', 'match.project_id', 'match.round_id', 'match.match_number', 'project.name AS project_name', 'project.sport_type_id', 'version.payload_json'])
			->from($this->database->quoteName('#__joomleague_project_match', 'match'))
			->innerJoin($this->database->quoteName('#__joomleague_project', 'project') . ' ON project.id = match.project_id')
			->innerJoin($this->database->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')
			->where('match.id = :matchId')->bind(':matchId', $boundId, ParameterType::INTEGER);
		$match = $this->database->setQuery($query)->loadObject();
		if (!$match) throw new \RuntimeException('The match does not exist.');
		$profile = json_decode((string) $match->payload_json, true, 512, JSON_THROW_ON_ERROR);
		$this->profileValidator->validate($profile); unset($match->payload_json);

		return [
			'match' => $match,
			'project' => (object) ['id' => (int) $match->project_id, 'name' => (string) $match->project_name, 'sport_type_id' => (int) $match->sport_type_id],
			'profile' => $profile, 'statistics' => $this->definitions($profile),
			'participants' => $this->participants($matchId), 'lineup' => $this->lineup($matchId), 'segments' => $this->segments($matchId),
		];
	}

	/** @return list<object> */
	public function getValues(int $matchId): array
	{
		$this->getContext($matchId); $boundId = $matchId;
		$query = $this->database->getQuery(true)->select('value.*')->from($this->database->quoteName('#__joomleague_match_statistic_value', 'value'))
			->where('value.match_id = :matchId')->bind(':matchId', $boundId, ParameterType::INTEGER)
			->order('value.scope_code ASC, value.statistic_code ASC, value.target_name_snapshot ASC, value.segment_key ASC, value.id ASC');
		return $this->database->setQuery($query)->loadObjectList();
	}

	/** @param array<string,mixed> $data */
	public function save(int $matchId, array $data, int $actorId): int
	{
		$context = $this->getContext($matchId);
		$code = $this->code((string) ($data['statistic_code'] ?? ''), 'statistic code');
		$definition = $context['statistics'][$code] ?? null;
		if (!is_array($definition)) throw new \InvalidArgumentException('The statistic is not defined by the project sport profile.');
		if (!in_array((string) $definition['source'], self::WRITABLE_SOURCES, true)) throw new \InvalidArgumentException('This statistic is owned by an event, calculation or import source and cannot be entered manually.');

		[$targetKind, $targetId] = $this->targetReference((string) ($data['target'] ?? ''));
		$participant = null; $lineup = null;
		if ($targetKind === 'participant') {
			$participant = $this->find($context['participants'], $targetId, 'The participant does not belong to this match.');
			if (!$this->participantSupportsScope($participant, (string) $definition['scope'])) throw new \InvalidArgumentException('The participant does not support this statistic scope.');
			$targetKey = 'participant:' . (int) $participant->id; $targetName = (string) $participant->display_name;
		} else {
			$lineup = $this->find($context['lineup'], $targetId, 'The lineup person does not belong to this match.');
			if (!$this->lineupSupportsScope($lineup, (string) $definition['scope'], $context['profile'])) throw new \InvalidArgumentException('The lineup person does not support this statistic scope.');
			$participant = $this->find($context['participants'], (int) $lineup->match_participant_id, 'The lineup participant does not belong to this match.');
			$targetKey = 'person:' . (int) $participant->id . ':' . (int) $lineup->person_id; $targetName = (string) $lineup->display_name;
		}

		$segmentId = $this->positiveOrNull($data['score_segment_id'] ?? null);
		$segment = $segmentId === null ? null : $this->find($context['segments'], $segmentId, 'The score segment does not belong to this match.');
		[$numericValue, $textValue] = $this->value((string) ($definition['value_type'] ?? 'integer'), (string) ($data['value'] ?? ''));
		$now = gmdate('Y-m-d H:i:s'); $segmentKey = $segment ? (int) $segment->id : 0;
		$existingId = $this->existingId($matchId, $code, $targetKey, $segmentKey);
		$record = (object) [
			'match_id' => $matchId, 'project_id' => (int) $context['project']->id,
			'source_statistic_id' => $this->catalogStatisticId((int) $context['project']->sport_type_id, $code),
			'statistic_code' => $code, 'statistic_name_key' => (string) $definition['name_key'],
			'abbreviation_key' => is_string($definition['abbreviation_key'] ?? null) ? $definition['abbreviation_key'] : null,
			'statistic_type' => (string) ($definition['statistic_type'] ?? 'basic'), 'scope_code' => (string) $definition['scope'],
			'value_type' => (string) ($definition['value_type'] ?? 'integer'), 'calculation_source' => (string) $definition['source'],
			'target_kind' => $targetKind, 'match_participant_id' => (int) $participant->id,
			'lineup_member_id' => $lineup ? (int) $lineup->id : null, 'person_id' => $lineup ? (int) $lineup->person_id : null,
			'target_key' => $targetKey, 'target_name_snapshot' => $targetName,
			'score_segment_id' => $segment ? (int) $segment->id : null, 'segment_key' => $segmentKey,
			'segment_code_snapshot' => $segment ? (string) $segment->level_code : null,
			'segment_sequence_snapshot' => $segment ? (int) $segment->sequence_number : null,
			'numeric_value' => $numericValue, 'text_value' => $textValue, 'notes' => $this->text($data['notes'] ?? null, 65535),
			'profile_metadata_json' => json_encode($definition, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
			'modified' => $now, 'modified_by' => $actorId,
		];

		if ($existingId > 0) { $record->id = $existingId; $this->database->updateObject('#__joomleague_match_statistic_value', $record, 'id'); return $existingId; }
		$record->uuid = UuidFactory::v4(); $record->created = $now; $record->created_by = $actorId; $record->modified = null; $record->modified_by = 0;
		$this->database->insertObject('#__joomleague_match_statistic_value', $record, 'id'); return (int) $record->id;
	}

	public function remove(int $matchId, int $valueId): void
	{
		$this->getContext($matchId); $boundId = $valueId; $boundMatchId = $matchId;
		$query = $this->database->getQuery(true)->delete($this->database->quoteName('#__joomleague_match_statistic_value'))
			->where('id = :id')->where('match_id = :matchId')->bind(':id', $boundId, ParameterType::INTEGER)->bind(':matchId', $boundMatchId, ParameterType::INTEGER);
		$this->database->setQuery($query)->execute();
		if ($this->database->getAffectedRows() !== 1) throw new \InvalidArgumentException('The statistic value does not belong to this match.');
	}

	/** @param array<string,mixed> $profile @return array<string,array<string,mixed>> */
	private function definitions(array $profile): array
	{
		$result = [];
		foreach ($profile['statistics'] ?? [] as $definition) if (is_array($definition) && is_string($definition['code'] ?? null) && is_string($definition['name_key'] ?? null) && is_string($definition['scope'] ?? null) && is_string($definition['source'] ?? null)) $result[$definition['code']] = $definition;
		return $result;
	}

	/** @return list<object> */
	private function participants(int $matchId): array
	{
		$boundId = $matchId; $query = $this->database->getQuery(true)->select(['participant.id', 'participant.slot_number', 'entry.display_name', 'entry.entry_kind'])
			->from($this->database->quoteName('#__joomleague_match_participant', 'participant'))->innerJoin($this->database->quoteName('#__joomleague_project_entry', 'entry') . ' ON entry.id = participant.project_entry_id')
			->where('participant.match_id = :matchId')->bind(':matchId', $boundId, ParameterType::INTEGER)->order('participant.slot_number ASC, participant.id ASC');
		return $this->database->setQuery($query)->loadObjectList();
	}

	/** @return list<object> */
	private function lineup(int $matchId): array
	{
		$boundId = $matchId; $query = $this->database->getQuery(true)->select(['lineup.id', 'lineup.match_participant_id', 'lineup.person_id', 'lineup.member_person_type', 'lineup.role_code', 'person.first_name', 'person.last_name', 'person.nickname'])
			->from($this->database->quoteName('#__joomleague_match_lineup_member', 'lineup'))->innerJoin($this->database->quoteName('#__joomleague_person', 'person') . ' ON person.id = lineup.person_id')
			->where('lineup.match_id = :matchId')->where('lineup.published = 1')->bind(':matchId', $boundId, ParameterType::INTEGER)->order('lineup.match_participant_id ASC, lineup.ordering ASC, lineup.id ASC');
		$rows = $this->database->setQuery($query)->loadObjectList();
		foreach ($rows as $row) $row->display_name = trim((string) $row->first_name . ((string) $row->nickname !== '' ? ' \'' . (string) $row->nickname . '\'' : '') . ' ' . (string) $row->last_name);
		return $rows;
	}

	/** @return list<object> */
	private function segments(int $matchId): array
	{
		$boundId = $matchId; $query = $this->database->getQuery(true)->select(['id', 'level_code', 'sequence_number'])->from($this->database->quoteName('#__joomleague_match_score_segment'))
			->where('match_id = :matchId')->where('parent_id IS NOT NULL')->bind(':matchId', $boundId, ParameterType::INTEGER)->order('segment_type_ordinal ASC, sequence_number ASC, id ASC');
		return $this->database->setQuery($query)->loadObjectList();
	}

	/** @param list<object> $rows */
	private function find(array $rows, int $id, string $message): object { foreach ($rows as $row) if ((int) $row->id === $id) return $row; throw new \InvalidArgumentException($message); }
	private function participantSupportsScope(object $participant, string $scope): bool { return $scope === 'participant' || (string) $participant->entry_kind === $scope; }

	/** @param array<string,mixed> $profile */
	private function lineupSupportsScope(object $lineup, string $scope, array $profile): bool
	{
		if ((string) $lineup->member_person_type === $scope || (string) $lineup->role_code === $scope) return true;
		foreach ($profile['positions'] ?? [] as $position) if (is_array($position) && ($position['code'] ?? null) === $lineup->role_code && ($position['lineup_group'] ?? null) === $scope) return true;
		return false;
	}

	/** @return array{0:string,1:int} */
	private function targetReference(string $value): array { if (preg_match('/^(participant|person):(\d+)$/', trim($value), $match) !== 1 || (int) $match[2] < 1) throw new \InvalidArgumentException('The statistic target is invalid.'); return [$match[1], (int) $match[2]]; }

	/** @return array{0:?string,1:?string} */
	private function value(string $type, string $value): array
	{
		$value = trim($value); if ($value === '') throw new \InvalidArgumentException('A statistic value is required.');
		if ($type === 'duration') return [MatchResultDuration::parse($value), null];
		if ($type === 'text' || $type === 'string') { if (strlen($value) > 1000) throw new \LengthException('The statistic text is too long.'); return [null, $value]; }
		if ($type === 'integer') { if (preg_match('/^-?\d{1,21}$/', $value) !== 1) throw new \InvalidArgumentException('The statistic requires an integer value.'); return [$value, null]; }
		if (!in_array($type, ['decimal', 'percentage'], true) || preg_match('/^-?\d{1,21}(?:\.\d{1,9})?$/', $value) !== 1) throw new \InvalidArgumentException('The statistic requires a supported numeric value.');
		if ($type === 'percentage' && (str_starts_with($value, '-') || (int) explode('.', $value, 2)[0] > 100 || ((int) explode('.', $value, 2)[0] === 100 && trim(explode('.', $value, 2)[1] ?? '', '0') !== ''))) throw new \InvalidArgumentException('A percentage must be between zero and one hundred.');
		return [$value, null];
	}

	private function existingId(int $matchId, string $code, string $targetKey, int $segmentKey): int
	{
		$match = $matchId; $statistic = $code; $target = $targetKey; $segment = $segmentKey;
		$query = $this->database->getQuery(true)->select('id')->from($this->database->quoteName('#__joomleague_match_statistic_value'))
			->where('match_id = :matchId')->where('statistic_code = :code')->where('target_key = :target')->where('segment_key = :segment')
			->bind(':matchId', $match, ParameterType::INTEGER)->bind(':code', $statistic)->bind(':target', $target)->bind(':segment', $segment, ParameterType::INTEGER);
		return (int) $this->database->setQuery($query)->loadResult();
	}

	private function catalogStatisticId(int $sportTypeId, string $code): ?int
	{
		$sport = $sportTypeId; $statistic = $code; $query = $this->database->getQuery(true)->select('id')->from($this->database->quoteName('#__joomleague_statistic'))
			->where('sport_type_id = :sport')->where('code = :code')->where('published = 1')->bind(':sport', $sport, ParameterType::INTEGER)->bind(':code', $statistic);
		$id = (int) $this->database->setQuery($query)->loadResult(); return $id > 0 ? $id : null;
	}

	private function positiveOrNull(mixed $value): ?int { if ($value === null || $value === '') return null; $value = filter_var($value, FILTER_VALIDATE_INT); if ($value === false || $value < 1) throw new \InvalidArgumentException('A referenced ID must be positive.'); return $value; }
	private function code(string $value, string $label): string { $value = trim($value); if (preg_match('/^[a-z][a-z0-9_]*$/', $value) !== 1) throw new \InvalidArgumentException('The ' . $label . ' is invalid.'); return $value; }
	private function text(mixed $value, int $maximum): ?string { $value = trim((string) $value); if ($value === '') return null; if (strlen($value) > $maximum) throw new \LengthException('The text is too long.'); return $value; }
}
