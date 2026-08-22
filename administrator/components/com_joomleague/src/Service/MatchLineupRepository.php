<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

final class MatchLineupRepository
{
	public function __construct(
		private readonly DatabaseInterface $database,
		private readonly string $systemTimezone = 'UTC',
		private readonly EntryModelValidator $profileValidator = new EntryModelValidator()
	) {
	}

	/** @return array{match:object,profile:array<string,mixed>,participants:list<object>} */
	public function getContext(int $matchId): array
	{
		if ($matchId < 1) throw new \InvalidArgumentException('A positive match ID is required.');
		$boundMatchId = $matchId;
		$query = $this->database->getQuery(true)
			->select([
				'match.id', 'match.project_id', 'match.round_id', 'match.match_number', 'match.scheduled_start', 'match.timezone',
				'project.name AS project_name', 'project.timezone AS project_timezone',
				'version.payload_json',
			])
			->from($this->database->quoteName('#__joomleague_project_match', 'match'))
			->innerJoin($this->database->quoteName('#__joomleague_project', 'project') . ' ON project.id = match.project_id')
			->innerJoin($this->database->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')
			->where('match.id = :matchId')
			->bind(':matchId', $boundMatchId, ParameterType::INTEGER);
		$match = $this->database->setQuery($query)->loadObject();

		if (!$match) throw new \RuntimeException('The match does not exist.');
		$profile = json_decode((string) $match->payload_json, true, 512, JSON_THROW_ON_ERROR);
		$this->profileValidator->validate($profile);
		unset($match->payload_json);

		$boundMatchId = $matchId;
		$query = $this->database->getQuery(true)
			->select([
				'participant.*', 'entry.entry_kind', 'entry.display_name', 'team.name AS team_name', 'person.first_name', 'person.last_name',
				'(SELECT COUNT(*) FROM ' . $this->database->quoteName('#__joomleague_project_entry_member', 'entry_member')
					. ' WHERE entry_member.entry_id = entry.id AND entry_member.published = 1) AS available_member_count',
			])
			->from($this->database->quoteName('#__joomleague_match_participant', 'participant'))
			->innerJoin($this->database->quoteName('#__joomleague_project_entry', 'entry') . ' ON entry.id = participant.project_entry_id')
			->leftJoin($this->database->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id')
			->leftJoin($this->database->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id')
			->where('participant.match_id = :matchId')
			->order('participant.slot_number ASC, participant.id ASC')
			->bind(':matchId', $boundMatchId, ParameterType::INTEGER);
		$participants = $this->database->setQuery($query)->loadObjectList();

		foreach ($participants as $participant) {
			$personName = trim((string) $participant->first_name . ' ' . (string) $participant->last_name);
			$participant->resolved_name = match ((string) $participant->entry_kind) {
				'team' => (string) $participant->team_name,
				'person' => $personName,
				default => (string) $participant->display_name,
			};
		}

		return compact('match', 'profile', 'participants');
	}

	/** @return list<object> */
	public function getAvailableMembers(int $matchId, int $participantId): array
	{
		[$context, $participant] = $this->participantContext($matchId, $participantId);
		$matchDate = $this->matchDate($context['match']);
		$boundEntryId = (int) $participant->project_entry_id;
		$query = $this->database->getQuery(true)
			->select(['member.*', 'person.first_name', 'person.last_name', 'lineup.id AS lineup_id', 'lineup.lineup_status'])
			->from($this->database->quoteName('#__joomleague_project_entry_member', 'member'))
			->innerJoin($this->database->quoteName('#__joomleague_person', 'person') . ' ON person.id = member.person_id')
			->leftJoin($this->database->quoteName('#__joomleague_match_lineup_member', 'lineup') . ' ON lineup.source_entry_member_id = member.id AND lineup.match_participant_id = ' . (int) $participantId)
			->where('member.entry_id = :entryId')
			->where('member.published = 1')
			->where('person.published = 1')
			->bind(':entryId', $boundEntryId, ParameterType::INTEGER)
			->order('member.member_person_type ASC, member.ordering ASC, person.last_name ASC, person.first_name ASC');

		if ($matchDate !== null) {
			$boundStart = $matchDate;
			$boundEnd = $matchDate;
			$query->where('(member.valid_from IS NULL OR member.valid_from <= :matchDateStart)')
				->where('(member.valid_until IS NULL OR member.valid_until >= :matchDateEnd)')
				->bind(':matchDateStart', $boundStart)
				->bind(':matchDateEnd', $boundEnd);
		}

		return $this->database->setQuery($query)->loadObjectList();
	}

	/** @return list<object> */
	public function getAssignedMembers(int $matchId, int $participantId): array
	{
		$this->participantContext($matchId, $participantId);
		$boundMatchId = $matchId;
		$boundParticipantId = $participantId;
		$query = $this->database->getQuery(true)
			->select(['lineup.*', 'person.first_name', 'person.last_name'])
			->from($this->database->quoteName('#__joomleague_match_lineup_member', 'lineup'))
			->innerJoin($this->database->quoteName('#__joomleague_person', 'person') . ' ON person.id = lineup.person_id')
			->where('lineup.match_id = :matchId')
			->where('lineup.match_participant_id = :participantId')
			->bind(':matchId', $boundMatchId, ParameterType::INTEGER)
			->bind(':participantId', $boundParticipantId, ParameterType::INTEGER)
			->order('lineup.member_person_type ASC, lineup.ordering ASC, lineup.id ASC');

		return $this->database->setQuery($query)->loadObjectList();
	}

	public function assign(int $matchId, int $participantId, int $entryMemberId, string $lineupStatus, bool $captain, int $actorId): int
	{
		if ($entryMemberId < 1 || $actorId < 0) throw new \InvalidArgumentException('Lineup identifiers are invalid.');
		[$context, $participant] = $this->participantContext($matchId, $participantId);
		$boundMemberId = $entryMemberId;
		$boundEntryId = (int) $participant->project_entry_id;
		$query = $this->database->getQuery(true)
			->select('member.*')
			->from($this->database->quoteName('#__joomleague_project_entry_member', 'member'))
			->where('member.id = :memberId')
			->where('member.entry_id = :entryId')
			->where('member.published = 1')
			->bind(':memberId', $boundMemberId, ParameterType::INTEGER)
			->bind(':entryId', $boundEntryId, ParameterType::INTEGER);
		$member = $this->database->setQuery($query)->loadObject();

		if (!$member || !$this->validOnMatchDate($member, $this->matchDate($context['match']))) {
			throw new \InvalidArgumentException('The roster member is not available for this match.');
		}

		$personType = (string) $member->member_person_type;
		if (!in_array($personType, ['player', 'staff'], true)
			|| !in_array($personType, $context['profile']['entry_model']['member_person_types'] ?? [], true)
			|| !$this->validRole($context['profile'], (string) ($member->role_code ?? ''), $personType)) {
			throw new \InvalidArgumentException('The roster member role is not allowed by the match profile.');
		}

		$allowedStatuses = $personType === 'player' ? ['starter', 'substitute', 'available'] : ['active', 'available'];
		if (!in_array($lineupStatus, $allowedStatuses, true)) throw new \InvalidArgumentException('The lineup status is invalid for this person type.');
		if ($captain && ($personType !== 'player' || ($context['profile']['lineup']['captain_supported'] ?? false) !== true)) {
			throw new \InvalidArgumentException('Captain assignment is not supported for this roster member.');
		}
		if ($personType === 'player' && $lineupStatus === 'starter') {
			$maximum = (int) ($context['profile']['lineup']['players_on_field'] ?? 0);
			if ($maximum > 0 && $this->assignedCount($participantId, 'player', 'starter', false) >= $maximum) {
				throw new \InvalidArgumentException('The profile maximum number of starters has been reached.');
			}
		}
		if ($captain && $this->assignedCount($participantId, 'player', null, true) > 0) {
			throw new \InvalidArgumentException('Only one captain may be assigned to a match participant.');
		}

		$record = (object) [
			'uuid' => UuidFactory::v4(), 'match_id' => $matchId, 'match_participant_id' => $participantId,
			'source_entry_member_id' => $entryMemberId, 'person_id' => (int) $member->person_id,
			'member_person_type' => $personType, 'role_code' => $member->role_code ?: null,
			'shirt_number' => $member->shirt_number ?: null, 'lineup_status' => $lineupStatus,
			'is_captain' => $captain ? 1 : 0, 'created' => gmdate('Y-m-d H:i:s'), 'created_by' => $actorId,
		];
		$this->database->insertObject('#__joomleague_match_lineup_member', $record, 'id');

		return (int) $record->id;
	}

	public function remove(int $matchId, int $participantId, int $lineupId): void
	{
		$this->participantContext($matchId, $participantId);
		$boundId = $lineupId; $boundMatchId = $matchId; $boundParticipantId = $participantId;
		$query = $this->database->getQuery(true)->delete($this->database->quoteName('#__joomleague_match_lineup_member'))
			->where('id = :id')->where('match_id = :matchId')->where('match_participant_id = :participantId')
			->bind(':id', $boundId, ParameterType::INTEGER)->bind(':matchId', $boundMatchId, ParameterType::INTEGER)
			->bind(':participantId', $boundParticipantId, ParameterType::INTEGER);
		$this->database->setQuery($query)->execute();
		if ($this->database->getAffectedRows() !== 1) throw new \InvalidArgumentException('The lineup row does not belong to this match participant.');
	}

	/** @return list<object> */
	public function getSubstitutions(int $matchId, int $participantId): array
	{
		$this->participantContext($matchId, $participantId);
		$boundMatchId = $matchId; $boundParticipantId = $participantId;
		$query = $this->database->getQuery(true)
			->select(['lineup_change.*', 'out_person.first_name AS outgoing_first_name', 'out_person.last_name AS outgoing_last_name', 'in_person.first_name AS incoming_first_name', 'in_person.last_name AS incoming_last_name'])
			->from($this->database->quoteName('#__joomleague_match_lineup_change', 'lineup_change'))
			->innerJoin($this->database->quoteName('#__joomleague_match_lineup_member', 'out_member') . ' ON out_member.id = lineup_change.outgoing_lineup_member_id')
			->innerJoin($this->database->quoteName('#__joomleague_person', 'out_person') . ' ON out_person.id = out_member.person_id')
			->innerJoin($this->database->quoteName('#__joomleague_match_lineup_member', 'in_member') . ' ON in_member.id = lineup_change.incoming_lineup_member_id')
			->innerJoin($this->database->quoteName('#__joomleague_person', 'in_person') . ' ON in_person.id = in_member.person_id')
			->where('lineup_change.match_id = :matchId')->where('lineup_change.match_participant_id = :participantId')
			->bind(':matchId', $boundMatchId, ParameterType::INTEGER)->bind(':participantId', $boundParticipantId, ParameterType::INTEGER)
			->order('lineup_change.sequence_number ASC, lineup_change.id ASC');
		return $this->database->setQuery($query)->loadObjectList();
	}

	public function addSubstitution(int $matchId, int $participantId, int $outgoingId, int $incomingId, ?string $phaseCode, ?int $phaseSequence, ?string $clockValue, ?string $clockUnit, ?string $notes, int $actorId): int
	{
		if ($actorId < 0 || $outgoingId < 1 || $incomingId < 1 || $outgoingId === $incomingId) throw new \InvalidArgumentException('Substitution identifiers are invalid.');
		[$context] = $this->participantContext($matchId, $participantId);
		if (!$this->substitutionsSupported($context['profile'])) throw new \InvalidArgumentException('The match profile does not support substitutions.');
		$members = $this->playerLineupMap($matchId, $participantId);
		if (!isset($members[$outgoingId], $members[$incomingId])) throw new \InvalidArgumentException('Substitution members must be players in the same match lineup.');
		$phaseCode = $this->phaseCode($context['profile'], $phaseCode);
		if ($phaseSequence !== null && ($phaseSequence < 1 || $phaseCode === null)) throw new \InvalidArgumentException('A positive phase sequence requires a profile phase.');
		$clockValue = $this->decimalOrNull($clockValue);
		$clockUnit = $this->codeOrNull($clockUnit, 50);
		$notes = $this->textOrNull($notes);
		$changes = $this->getSubstitutions($matchId, $participantId);
		$maximum = (int) ($context['profile']['lineup']['substitutions']['default_allowed'] ?? $context['profile']['lineup']['default_substitutes_allowed'] ?? 0);
		if ($maximum > 0 && count($changes) >= $maximum) throw new \InvalidArgumentException('The profile substitution limit has been reached.');
		$sequence = count($changes) + 1;
		$candidate = (object) ['outgoing_lineup_member_id' => $outgoingId, 'incoming_lineup_member_id' => $incomingId, 'sequence_number' => $sequence];
		$this->validateSubstitutionSequence($members, [...$changes, $candidate]);
		$record = (object) [
			'uuid' => UuidFactory::v4(), 'match_id' => $matchId, 'match_participant_id' => $participantId,
			'outgoing_lineup_member_id' => $outgoingId, 'incoming_lineup_member_id' => $incomingId,
			'change_type' => 'substitution', 'sequence_number' => $sequence, 'phase_code' => $phaseCode,
			'phase_sequence' => $phaseSequence, 'clock_value' => $clockValue, 'clock_unit' => $clockUnit,
			'notes' => $notes, 'created' => gmdate('Y-m-d H:i:s'), 'created_by' => $actorId,
		];
		$this->database->insertObject('#__joomleague_match_lineup_change', $record, 'id');
		return (int) $record->id;
	}

	public function removeSubstitution(int $matchId, int $participantId, int $changeId): void
	{
		$members = $this->playerLineupMap($matchId, $participantId);
		$changes = $this->getSubstitutions($matchId, $participantId);
		$remaining = array_values(array_filter($changes, static fn (object $change): bool => (int) $change->id !== $changeId));
		if (count($remaining) === count($changes)) throw new \InvalidArgumentException('The substitution does not belong to this match participant.');
		foreach ($remaining as $index => $change) $change->sequence_number = $index + 1;
		$this->validateSubstitutionSequence($members, $remaining);
		$boundId = $changeId; $boundMatchId = $matchId; $boundParticipantId = $participantId;
		$query = $this->database->getQuery(true)->delete($this->database->quoteName('#__joomleague_match_lineup_change'))
			->where('id = :id')->where('match_id = :matchId')->where('match_participant_id = :participantId')
			->bind(':id', $boundId, ParameterType::INTEGER)->bind(':matchId', $boundMatchId, ParameterType::INTEGER)->bind(':participantId', $boundParticipantId, ParameterType::INTEGER);
		$this->database->setQuery($query)->execute();
		foreach ($remaining as $change) {
			$record = (object) ['id' => (int) $change->id, 'sequence_number' => (int) $change->sequence_number];
			$this->database->updateObject('#__joomleague_match_lineup_change', $record, 'id');
		}
	}

	/** @return array{0:array{match:object,profile:array<string,mixed>,participants:list<object>},1:object} */
	private function participantContext(int $matchId, int $participantId): array
	{
		$context = $this->getContext($matchId);
		foreach ($context['participants'] as $participant) if ((int) $participant->id === $participantId) return [$context, $participant];
		throw new \InvalidArgumentException('The participant does not belong to this match.');
	}

	private function matchDate(object $match): ?string
	{
		if (!$match->scheduled_start) return null;
		$timezone = (string) ($match->timezone ?: ($match->project_timezone ?: $this->systemTimezone));
		return (new \DateTimeImmutable((string) $match->scheduled_start, new \DateTimeZone('UTC')))
			->setTimezone(new \DateTimeZone($timezone))->format('Y-m-d');
	}

	private function validOnMatchDate(object $member, ?string $matchDate): bool
	{
		return $matchDate === null || ((!$member->valid_from || $member->valid_from <= $matchDate) && (!$member->valid_until || $member->valid_until >= $matchDate));
	}

	private function assignedCount(int $participantId, string $personType, ?string $lineupStatus, bool $captainOnly): int
	{
		$boundParticipantId = $participantId;
		$boundPersonType = $personType;
		$query = $this->database->getQuery(true)->select('COUNT(*)')
			->from($this->database->quoteName('#__joomleague_match_lineup_member'))
			->where('match_participant_id = :participantId')
			->where('member_person_type = :personType')
			->bind(':participantId', $boundParticipantId, ParameterType::INTEGER)
			->bind(':personType', $boundPersonType);
		if ($lineupStatus !== null) {
			$boundLineupStatus = $lineupStatus;
			$query->where('lineup_status = :lineupStatus')->bind(':lineupStatus', $boundLineupStatus);
		}
		if ($captainOnly) $query->where('is_captain = 1');
		return (int) $this->database->setQuery($query)->loadResult();
	}

	/** @return array<int,object> */
	private function playerLineupMap(int $matchId, int $participantId): array
	{
		$members = [];
		foreach ($this->getAssignedMembers($matchId, $participantId) as $member) if ($member->member_person_type === 'player') $members[(int) $member->id] = $member;
		return $members;
	}

	/** @param array<int,object> $members @param list<object> $changes */
	private function validateSubstitutionSequence(array $members, array $changes): void
	{
		$active = [];
		foreach ($members as $id => $member) if ($member->lineup_status === 'starter') $active[$id] = true;
		usort($changes, static fn (object $left, object $right): int => (int) $left->sequence_number <=> (int) $right->sequence_number);
		foreach ($changes as $change) {
			$outgoing = (int) $change->outgoing_lineup_member_id; $incoming = (int) $change->incoming_lineup_member_id;
			if (!isset($members[$outgoing], $members[$incoming]) || !isset($active[$outgoing]) || isset($active[$incoming])) {
				throw new \InvalidArgumentException('The substitution sequence is inconsistent with the active lineup.');
			}
			unset($active[$outgoing]); $active[$incoming] = true;
		}
	}

	/** @param array<string,mixed> $profile */
	private function substitutionsSupported(array $profile): bool
	{
		return ($profile['lineup']['substitutions']['supported'] ?? false) === true || (int) ($profile['lineup']['default_substitutes_allowed'] ?? 0) > 0;
	}

	/** @param array<string,mixed> $profile */
	private function phaseCode(array $profile, ?string $phaseCode): ?string
	{
		$phaseCode = $this->codeOrNull($phaseCode, 100);
		if ($phaseCode === null) return null;
		foreach ($profile['match']['score']['segment_types'] ?? [] as $segment) if (($segment['code'] ?? null) === $phaseCode) return $phaseCode;
		throw new \InvalidArgumentException('The substitution phase is not defined by the match profile.');
	}

	private function decimalOrNull(?string $value): ?string
	{
		$value = trim((string) $value);
		if ($value === '') return null;
		if (preg_match('/^\d{1,21}(?:\.\d{1,9})?$/', $value) !== 1) throw new \InvalidArgumentException('The substitution clock value is invalid.');
		return $value;
	}

	private function codeOrNull(?string $value, int $maximum): ?string
	{
		$value = trim((string) $value);
		if ($value === '') return null;
		if (mb_strlen($value) > $maximum || preg_match('/^[a-z0-9][a-z0-9_.-]*$/', $value) !== 1) throw new \InvalidArgumentException('The substitution code is invalid.');
		return $value;
	}

	private function textOrNull(?string $value): ?string
	{
		$value = trim((string) $value);
		if ($value === '') return null;
		if (strlen($value) > 65535) throw new \LengthException('Substitution notes are too long.');
		return $value;
	}

	/** @param array<string,mixed> $profile */
	private function validRole(array $profile, string $roleCode, string $personType): bool
	{
		if ($roleCode === '') return true;
		foreach ($profile['positions'] ?? [] as $position) {
			if (($position['code'] ?? null) === $roleCode && ($position['person_type'] ?? null) === $personType) return true;
		}
		return false;
	}
}
