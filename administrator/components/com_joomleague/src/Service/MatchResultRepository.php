<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\CanonicalJson;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

final class MatchResultRepository
{
	public function __construct(private readonly DatabaseInterface $database, private readonly MatchResultPayloadValidator $validator = new MatchResultPayloadValidator()) {}

	/** @return array<string,mixed>|null */
	public function get(int $matchId): ?array
	{
		if ($matchId < 1) throw new \InvalidArgumentException('Match identifier is invalid.');

		$query = $this->database->getQuery(true)
			->select($this->database->quoteName(['id', 'result_type', 'status_code', 'outcome_code', 'finalized_at', 'notes', 'metadata_json']))
			->from($this->database->quoteName('#__joomleague_match_result'))
			->where('match_id = :matchId')
			->bind(':matchId', $matchId, ParameterType::INTEGER);
		$result = $this->database->setQuery($query)->loadAssoc();

		if (!$result) return null;

		$resultId = (int) $result['id'];
		$query = $this->database->getQuery(true)
			->select($this->database->quoteName(['id', 'parent_id', 'level_code', 'segment_type_ordinal', 'sequence_number', 'status_code', 'metadata_json']))
			->from($this->database->quoteName('#__joomleague_match_score_segment'))
			->where('match_id = :matchId')
			->order($this->database->quoteName('segment_type_ordinal') . ' ASC')
			->order($this->database->quoteName('sequence_number') . ' ASC')
			->order($this->database->quoteName('id') . ' ASC')
			->bind(':matchId', $matchId, ParameterType::INTEGER);
		$segmentRows = $this->database->setQuery($query)->loadAssocList();

		$query = $this->database->getQuery(true)
			->select([
				$this->database->quoteName('value.segment_id'),
				$this->database->quoteName('value.participant_id'),
				$this->database->quoteName('value.numeric_value'),
				$this->database->quoteName('value.text_value'),
				$this->database->quoteName('value.status_code'),
				$this->database->quoteName('value.result_rank'),
				$this->database->quoteName('value.metadata_json'),
			])
			->from($this->database->quoteName('#__joomleague_match_score_value', 'value'))
			->innerJoin($this->database->quoteName('#__joomleague_match_participant', 'participant') . ' ON participant.id = value.participant_id')
			->where('value.match_id = :matchId')
			->order($this->database->quoteName('value.segment_id') . ' ASC')
			->order($this->database->quoteName('participant.slot_number') . ' ASC')
			->order($this->database->quoteName('value.id') . ' ASC')
			->bind(':matchId', $matchId, ParameterType::INTEGER);
		$valueRows = $this->database->setQuery($query)->loadAssocList();

		$valuesBySegment = [];

		foreach ($valueRows as $value) {
			$segmentId = (int) $value['segment_id'];
			$valuesBySegment[$segmentId][] = [
				'participant_id' => (int) $value['participant_id'],
				'numeric_value' => $value['numeric_value'] === null ? null : (string) $value['numeric_value'],
				'text_value' => $value['text_value'] === null ? null : (string) $value['text_value'],
				'status_code' => $value['status_code'] === null ? null : (string) $value['status_code'],
				'result_rank' => $value['result_rank'] === null ? null : (int) $value['result_rank'],
				'metadata' => $this->decodeMetadata($value['metadata_json']),
			];
		}

		$segments = [];
		$childrenByParent = [];
		$rootIds = [];

		foreach ($segmentRows as $row) {
			$id = (int) $row['id'];
			$parentId = $row['parent_id'] === null ? null : (int) $row['parent_id'];

			if (isset($segments[$id])) throw new \UnexpectedValueException('Duplicate result segment identifier.');

			$segments[$id] = [
				'level_code' => (string) $row['level_code'],
				'segment_type_ordinal' => (int) $row['segment_type_ordinal'],
				'sequence_number' => (int) $row['sequence_number'],
				'status_code' => $row['status_code'] === null ? null : (string) $row['status_code'],
				'metadata' => $this->decodeMetadata($row['metadata_json']),
				'values' => $valuesBySegment[$id] ?? [],
				'children' => [],
			];

			if ($parentId === null) $rootIds[] = $id;
			else $childrenByParent[$parentId][] = $id;
		}

		if (count($rootIds) !== 1) throw new \UnexpectedValueException('Stored result must contain exactly one root segment.');
		if (($segments[$rootIds[0]]['level_code'] ?? null) !== 'result') throw new \UnexpectedValueException('Stored result root level is invalid.');

		$states = [];
		$build = function (int $id) use (&$build, &$states, $segments, $childrenByParent): array {
			if (!isset($segments[$id])) throw new \UnexpectedValueException('Stored result contains an orphan segment.');
			if (($states[$id] ?? 0) === 1) throw new \UnexpectedValueException('Stored result contains a segment cycle.');
			if (($states[$id] ?? 0) === 2) throw new \UnexpectedValueException('Stored result segment is referenced more than once.');

			$states[$id] = 1;
			$segment = $segments[$id];

			foreach ($childrenByParent[$id] ?? [] as $childId) $segment['children'][] = $build($childId);

			$states[$id] = 2;
			return $segment;
		};

		$root = $build($rootIds[0]);

		if (count($states) !== count($segments)) throw new \UnexpectedValueException('Stored result contains unreachable segments.');

		return [
			'result_type' => (string) $result['result_type'],
			'status_code' => (string) $result['status_code'],
			'outcome_code' => $result['outcome_code'] === null ? null : (string) $result['outcome_code'],
			'finalized_at' => $result['finalized_at'] === null ? null : (string) $result['finalized_at'],
			'notes' => $result['notes'] === null ? null : (string) $result['notes'],
			'metadata' => $this->decodeMetadata($result['metadata_json']),
			'segments' => [$root],
		];
	}

	/** @param array<string,mixed> $payload */
	public function replace(int $matchId, array $payload, int $actorId): int
	{
		if ($matchId < 1 || $actorId < 0) throw new \InvalidArgumentException('Match and actor identifiers are invalid.');
		$context = $this->context($matchId); $participantIds = $this->participantIds($matchId); $result = $this->validator->validate($context['profile'], $participantIds, $payload);
		$this->database->transactionStart();
		try {
			$this->deleteCurrent($matchId);
			$resultId = $this->insertResult($matchId, $result, $actorId);
			foreach ($result['segments'] as $segment) $this->insertSegment($matchId, null, $segment, $actorId);
			$this->database->transactionCommit(); return $resultId;
		} catch (\Throwable $exception) { $this->database->transactionRollback(); throw $exception; }
	}

	/** @return array{profile:array<string,mixed>} */
	private function context(int $matchId): array
	{
		$query = $this->database->getQuery(true)->select($this->database->quoteName('version.payload_json'))->from($this->database->quoteName('#__joomleague_project_match', 'match'))
			->innerJoin($this->database->quoteName('#__joomleague_project', 'project') . ' ON project.id = match.project_id')->innerJoin($this->database->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')
			->where('match.id = :matchId')->bind(':matchId', $matchId, ParameterType::INTEGER);
		$payload = $this->database->setQuery($query)->loadResult(); if ($payload === null) throw new \RuntimeException('The selected match or profile does not exist.');
		$profile = json_decode((string) $payload, true, 512, JSON_THROW_ON_ERROR); if (!is_array($profile)) throw new \UnexpectedValueException('Match profile payload is invalid.'); return ['profile' => $profile];
	}

	/** @return list<int> */
	private function participantIds(int $matchId): array
	{
		$query = $this->database->getQuery(true)->select($this->database->quoteName('id'))->from($this->database->quoteName('#__joomleague_match_participant'))->where('match_id = :matchId')->where('published = 1')->order('slot_number ASC')->bind(':matchId', $matchId, ParameterType::INTEGER);
		return array_map('intval', $this->database->setQuery($query)->loadColumn());
	}

	private function deleteCurrent(int $matchId): void
	{
		$query = $this->database->getQuery(true)->delete($this->database->quoteName('#__joomleague_match_score_segment'))->where('match_id = :matchId')->bind(':matchId', $matchId, ParameterType::INTEGER); $this->database->setQuery($query)->execute();
		$query = $this->database->getQuery(true)->delete($this->database->quoteName('#__joomleague_match_result'))->where('match_id = :matchId')->bind(':matchId', $matchId, ParameterType::INTEGER); $this->database->setQuery($query)->execute();
	}

	/** @param array<string,mixed> $result */
	private function insertResult(int $matchId, array $result, int $actorId): int
	{
		$uuid = UuidFactory::v4(); $metadata = $this->metadataJson($result['metadata']);
		$query = $this->database->getQuery(true)->insert($this->database->quoteName('#__joomleague_match_result'))->columns($this->database->quoteName(['uuid','match_id','result_type','status_code','outcome_code','finalized_at','notes','metadata_json','created_by']))
			->values(':uuid,:matchId,:resultType,:statusCode,:outcomeCode,:finalizedAt,:notes,:metadata,:actorId')->bind(':uuid',$uuid)->bind(':matchId',$matchId,ParameterType::INTEGER)->bind(':resultType',$result['result_type'])->bind(':statusCode',$result['status_code'])->bind(':outcomeCode',$result['outcome_code'])->bind(':finalizedAt',$result['finalized_at'])->bind(':notes',$result['notes'])->bind(':metadata',$metadata)->bind(':actorId',$actorId,ParameterType::INTEGER);
		$this->database->setQuery($query)->execute(); return (int) $this->database->insertid();
	}

	/** @param array<string,mixed> $segment */
	private function insertSegment(int $matchId, ?int $parentId, array $segment, int $actorId): int
	{
		$uuid = UuidFactory::v4(); $metadata = $this->metadataJson($segment['metadata']);
		$query = $this->database->getQuery(true)->insert($this->database->quoteName('#__joomleague_match_score_segment'))->columns($this->database->quoteName(['uuid','match_id','parent_id','level_code','segment_type_ordinal','sequence_number','status_code','metadata_json','created_by']))
			->values(':uuid,:matchId,:parentId,:levelCode,:segmentTypeOrdinal,:sequenceNumber,:statusCode,:metadata,:actorId')->bind(':uuid',$uuid)->bind(':matchId',$matchId,ParameterType::INTEGER)->bind(':parentId',$parentId,ParameterType::INTEGER)->bind(':levelCode',$segment['level_code'])->bind(':segmentTypeOrdinal',$segment['segment_type_ordinal'],ParameterType::INTEGER)->bind(':sequenceNumber',$segment['sequence_number'],ParameterType::INTEGER)->bind(':statusCode',$segment['status_code'])->bind(':metadata',$metadata)->bind(':actorId',$actorId,ParameterType::INTEGER);
		$this->database->setQuery($query)->execute(); $segmentId = (int) $this->database->insertid();
		foreach ($segment['values'] as $value) $this->insertValue((int) $matchId, (int) $segmentId, $value, (int) $actorId);
		foreach ($segment['children'] as $child) $this->insertSegment((int) $matchId, (int) $segmentId, $child, (int) $actorId);
		return $segmentId;
	}

	/** @param array<string,mixed> $value */
	private function insertValue(int $matchId, int $segmentId, array $value, int $actorId): void
	{
		$uuid=UuidFactory::v4(); $metadata=$this->metadataJson($value['metadata']);
		$query=$this->database->getQuery(true)->insert($this->database->quoteName('#__joomleague_match_score_value'))->columns($this->database->quoteName(['uuid','match_id','segment_id','participant_id','numeric_value','text_value','status_code','result_rank','metadata_json','created_by']))
			->values(':uuid,:matchId,:segmentId,:participantId,:numericValue,:textValue,:statusCode,:resultRank,:metadata,:actorId')->bind(':uuid',$uuid)->bind(':matchId',$matchId,ParameterType::INTEGER)->bind(':segmentId',$segmentId,ParameterType::INTEGER)->bind(':participantId',$value['participant_id'],ParameterType::INTEGER)->bind(':numericValue',$value['numeric_value'])->bind(':textValue',$value['text_value'])->bind(':statusCode',$value['status_code'])->bind(':resultRank',$value['result_rank'],ParameterType::INTEGER)->bind(':metadata',$metadata)->bind(':actorId',$actorId,ParameterType::INTEGER);
		$this->database->setQuery($query)->execute();
	}

	/** @param array<string,mixed> $metadata */
	private function metadataJson(array $metadata): ?string { return $metadata === [] ? null : CanonicalJson::encodeObject($metadata); }

	/** @return array<string,mixed> */
	private function decodeMetadata(mixed $metadata): array
	{
		if ($metadata === null || $metadata === '') return [];

		$decoded = json_decode((string) $metadata, true, 512, JSON_THROW_ON_ERROR);

		if (!is_array($decoded) || array_is_list($decoded)) throw new \UnexpectedValueException('Stored result metadata must be a JSON object.');

		return $decoded;
	}
}
