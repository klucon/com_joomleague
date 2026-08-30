<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\CanonicalJson;
use Joomleague\Component\Joomleague\Domain\Service\StandingsDecimal;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

/**
 * Recalculates and publishes standings snapshots. Ordinary site reads use
 * StandingsReader; StandingsSnapshotSynchronizer may invoke this service to
 * repair scopes missing after an external import.
 */
final class StandingsRecalculator
{
	public function __construct(
		private readonly DatabaseInterface $database,
		private readonly StandingsReader $reader,
		private readonly StandingsCalculator $calculator = new StandingsCalculator(),
	) {}

	public function recalculate(int $projectId, ?int $stageId, string $scope, int $actorId): int
	{
		if ($actorId < 0) throw new \InvalidArgumentException('Standings actor is invalid.');
		$context = $this->reader->context($projectId, $stageId, $scope); $input = $this->inputForCalculation($context, $scope); $rows = $this->calculator->calculate($context['contract'], $input['entries'], $input['matches'], $scope, $input['adjustments']);
		$inputChecksum = CanonicalJson::checksum(['profile_checksum' => (string) $context['project']->payload_checksum, 'scope' => $scope, 'stage_id' => $context['stage_id'], 'contract' => $context['contract'], 'entries' => $input['entries'], 'matches' => $input['matches'], 'adjustments' => $input['adjustments']]);
		$existing = $this->existingSnapshot($projectId, $context['stage_id'], $scope, $inputChecksum);
		if ($existing > 0) { $this->database->transactionStart(); try { $this->publish($projectId, $context['stage_id'], $scope, $existing, $actorId); $this->database->transactionCommit(); return $existing; } catch (\Throwable $error) { $this->database->transactionRollback(); throw $error; } }
		$this->database->transactionStart();
		try {
			$snapshotId = $this->insertSnapshot($context, $scope, $inputChecksum, count($rows), $actorId);
			foreach ($rows as $sequence => $row) $this->insertRow($snapshotId, $row, $sequence + 1);
			$this->publish($projectId, $context['stage_id'], $scope, $snapshotId, $actorId);
			$this->database->transactionCommit(); return $snapshotId;
		} catch (\Throwable $error) { $this->database->transactionRollback(); throw $error; }
	}

	/** @param array<string,mixed> $context @return array{entries:list<array{id:int,name:string,included:bool}>,matches:list<array<string,mixed>>,adjustments:list<array{entry_id:int,metric:string,value:string}>} */
	public function inputForCalculation(array $context, string $scope): array
	{
		$projectId = (int) $context['project']->id; $stageId = $context['stage_id'];
		$query = $this->database->getQuery(true)->select(['entry.id', 'entry.display_name', 'entry.entry_kind', 'team.name AS team_name', 'person.first_name', 'person.last_name'])
			->from($this->database->quoteName('#__joomleague_project_entry', 'entry'))->leftJoin($this->database->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id')->leftJoin($this->database->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id')
			->where('entry.project_id = :project')->where('entry.published = 1')->where('entry.included_in_standings = 1')->bind(':project', $projectId, ParameterType::INTEGER)->order('entry.id ASC');
		if ($stageId !== null && ($context['stage']->entry_selection_mode ?? 'inherit_project') === 'explicit') { $query->innerJoin($this->database->quoteName('#__joomleague_stage_entry', 'stage_entry') . ' ON stage_entry.entry_id = entry.id')->where('stage_entry.stage_id = :stage')->bind(':stage', $stageId, ParameterType::INTEGER); }
		$entries = [];
		foreach ($this->database->setQuery($query)->loadObjectList() as $entry) { $name = match ((string) $entry->entry_kind) { 'team' => (string) $entry->team_name, 'person' => trim((string) $entry->first_name . ' ' . (string) $entry->last_name), default => (string) $entry->display_name }; $entries[] = ['id' => (int) $entry->id, 'name' => $name, 'included' => true]; }
		$matches = $this->matches((int) $projectId, $stageId === null ? null : (int) $stageId, $context['profile']);
		if ($stageId !== null && ($context['stage']->entry_selection_mode ?? 'inherit_project') === 'explicit') $matches = $this->carryOverMatches((int) $projectId, (int) $stageId, $context['profile'], $entries, $matches);
		$adjustments = $this->adjustments((int) $projectId, $stageId === null ? null : (int) $stageId, $scope);
		return compact('entries', 'matches', 'adjustments');
	}

	/** @param array<string,mixed> $profile @param list<array{id:int,name:string,included:bool}> $entries @param list<array<string,mixed>> $matches @return list<array<string,mixed>> */
	private function carryOverMatches(int $projectId, int $targetStageId, array $profile, array $entries, array $matches): array
	{
		$included = array_fill_keys(array_column($entries, 'id'), true);
		$query = $this->database->getQuery(true)->select(['transition.source_stage_id','transition.carry_over_mode'])
			->from($this->database->quoteName('#__joomleague_stage_transition','transition'))
			->innerJoin($this->database->quoteName('#__joomleague_stage_transition_assignment','assignment') . ' ON assignment.transition_id = transition.id')
			->where('transition.project_id = :project')->where('transition.target_stage_id = :stage')->where("transition.carry_over_mode <> 'none'")->where('transition.published = 1')
			->bind(':project',$projectId,ParameterType::INTEGER)->bind(':stage',$targetStageId,ParameterType::INTEGER)->group(['transition.id','transition.source_stage_id','transition.carry_over_mode']);
		$seen = [];
		foreach ($matches as $index => $match) $seen[CanonicalJson::checksum(['match' => $match])] = $index;
		foreach ($this->database->setQuery($query)->loadObjectList() as $transition) {
			foreach ($this->matches((int) $projectId, (int) $transition->source_stage_id, $profile) as $match) {
				$participantIds = array_map(static fn(array $participant): int => (int) ($participant['entry_id'] ?? 0), $match['participants'] ?? []);
				$qualified = array_filter($participantIds, static fn(int $id): bool => isset($included[$id]));
				$accept = $transition->carry_over_mode === 'all_results' ? $qualified !== [] : ($participantIds !== [] && count($qualified) === count($participantIds));
				if (!$accept) continue;
				$key = CanonicalJson::checksum(['match' => $match]); if (!isset($seen[$key])) { $seen[$key] = count($matches); $matches[] = $match; }
			}
		}
		return $matches;
	}

	/** @return list<array{entry_id:int,metric:string,value:string}> */
	private function adjustments(int $projectId, ?int $stageId, string $scope): array
	{
		$stageKey = $stageId ?? 0;
		$effectiveDate = gmdate('Y-m-d');
		$query = $this->database->getQuery(true)
			->select(['project_entry_id', 'metric_code', 'adjustment_value', 'effective_date'])
			->from($this->database->quoteName('#__joomleague_standing_adjustment'))
			->where('project_id = :project')
			->where('stage_key = :stage')
			->where('(scope_code = :scope OR scope_code = ' . $this->database->quote('all') . ')')
			->where('published = 1')
			->where('(effective_date IS NULL OR effective_date <= :effectiveDate)')
			->bind(':project', $projectId, ParameterType::INTEGER)
			->bind(':stage', $stageKey, ParameterType::INTEGER)
			->bind(':scope', $scope)
			->bind(':effectiveDate', $effectiveDate)
			->order(['ordering ASC', 'id ASC']);
		$result = [];
		foreach ($this->database->setQuery($query)->loadObjectList() as $row) $result[] = ['entry_id' => (int) $row->project_entry_id, 'metric' => (string) $row->metric_code, 'value' => (string) $row->adjustment_value, 'effective_date' => $row->effective_date === null ? null : (string) $row->effective_date];
		return $result;
	}

	/** @param array<string,mixed> $profile @return list<array<string,mixed>> */
	private function matches(int $projectId, ?int $stageId, array $profile): array
	{
		$query = $this->database->getQuery(true)->select(['match.id', 'match.scheduled_start', 'round.id AS round_id', 'round.name AS round_name', 'round.sequence_number AS round_sequence', 'result.status_code'])->from($this->database->quoteName('#__joomleague_project_match', 'match'))
			->innerJoin($this->database->quoteName('#__joomleague_project_round', 'round') . ' ON round.id = match.round_id')
			->innerJoin($this->database->quoteName('#__joomleague_project_stage', 'stage') . ' ON stage.id = match.stage_id')
			->innerJoin($this->database->quoteName('#__joomleague_match_result', 'result') . ' ON result.match_id = match.id')
			->where('match.project_id = :project')->where('match.published = 1')->where('round.published = 1')->where('stage.published = 1')
			->bind(':project', $projectId, ParameterType::INTEGER)->order('match.id ASC');
		if ($stageId !== null) $query->where('match.stage_id = :stage')->bind(':stage', $stageId, ParameterType::INTEGER);
		$matches = []; foreach ($this->database->setQuery($query)->loadObjectList() as $match) $matches[(int) $match->id] = ['status' => (string) $match->status_code, 'scheduled_start' => $match->scheduled_start, 'round_id' => (int) $match->round_id, 'round_name' => (string) $match->round_name, 'round_sequence' => (int) $match->round_sequence, 'participants' => [], 'segments' => [], 'statistics' => []];
		if ($matches === []) return [];
		$query = $this->database->getQuery(true)->select(['participant.match_id', 'participant.id AS participant_id', 'participant.project_entry_id', 'participant.slot_number', 'value.numeric_value', 'value.status_code', 'value.result_rank'])
			->from($this->database->quoteName('#__joomleague_match_participant', 'participant'))->innerJoin($this->database->quoteName('#__joomleague_project_match', 'match') . ' ON match.id = participant.match_id')->innerJoin($this->database->quoteName('#__joomleague_match_score_segment', 'segment') . ' ON segment.match_id = participant.match_id AND segment.parent_id IS NULL')->leftJoin($this->database->quoteName('#__joomleague_match_score_value', 'value') . ' ON value.segment_id = segment.id AND value.participant_id = participant.id')->where('match.project_id = :project')->bind(':project', $projectId, ParameterType::INTEGER);
		if ($stageId !== null) $query->where('match.stage_id = :stage')->bind(':stage', $stageId, ParameterType::INTEGER);
		$participantEntries = [];
		foreach ($this->database->setQuery($query)->loadObjectList() as $row) { $matchId = (int) $row->match_id; if (!isset($matches[$matchId])) continue; $participantEntries[(int) $row->participant_id] = (int) $row->project_entry_id; $matches[$matchId]['participants'][] = ['entry_id' => (int) $row->project_entry_id, 'root_value' => $row->numeric_value === null ? null : (string) $row->numeric_value, 'result_rank' => $row->result_rank === null ? null : (int) $row->result_rank, 'status' => (string) ($row->status_code ?? ''), 'slot' => (int) $row->slot_number]; }
		$typedProjectId = (int) $projectId;
		$typedStageId = $stageId === null ? null : (int) $stageId;
		$this->segments($matches, $typedProjectId, $typedStageId, $participantEntries);
		$this->statistics($matches, $typedProjectId, $typedStageId, $participantEntries, $profile);
		return array_values($matches);
	}

	/** @param array<int,array<string,mixed>> $matches @param array<int,int> $participantEntries */
	private function segments(array &$matches, int $projectId, ?int $stageId, array $participantEntries): void
	{
		$query = $this->database->getQuery(true)->select(['segment.id', 'segment.match_id', 'segment.level_code', 'segment.sequence_number', 'value.participant_id', 'value.numeric_value'])->from($this->database->quoteName('#__joomleague_match_score_segment', 'segment'))->innerJoin($this->database->quoteName('#__joomleague_project_match', 'match') . ' ON match.id = segment.match_id')->leftJoin($this->database->quoteName('#__joomleague_match_score_value', 'value') . ' ON value.segment_id = segment.id')->where('segment.parent_id IS NOT NULL')->where('match.project_id = :project')->bind(':project', $projectId, ParameterType::INTEGER)->order('segment.id ASC');
		if ($stageId !== null) $query->where('match.stage_id = :stage')->bind(':stage', $stageId, ParameterType::INTEGER);
		$segments = [];
		foreach ($this->database->setQuery($query)->loadObjectList() as $row) { $matchId = (int) $row->match_id; if (!isset($matches[$matchId])) continue; $key = $matchId . ':' . (int) $row->id; $segments[$key] ??= ['code' => (string) $row->level_code, 'sequence' => (int) $row->sequence_number, 'values' => []]; if ($row->participant_id !== null && $row->numeric_value !== null && isset($participantEntries[(int) $row->participant_id])) $segments[$key]['values'][$participantEntries[(int) $row->participant_id]] = (string) $row->numeric_value; }
		foreach ($segments as $key => $segment) { $matchId = (int) explode(':', $key, 2)[0]; $matches[$matchId]['segments'][] = $segment; }
	}

	/** @param array<int,array<string,mixed>> $matches @param array<int,int> $participantEntries @param array<string,mixed> $profile */
	private function statistics(array &$matches, int $projectId, ?int $stageId, array $participantEntries, array $profile): void
	{
		$eventStatistics = []; foreach ($profile['event_types'] ?? [] as $event) if (is_array($event) && is_string($event['code'] ?? null) && is_string($event['statistic_code'] ?? null)) $eventStatistics[$event['code']] = $event['statistic_code'];
		$query = $this->database->getQuery(true)->select(['event.match_id', 'event.match_participant_id', 'event.event_code'])->from($this->database->quoteName('#__joomleague_match_event', 'event'))->innerJoin($this->database->quoteName('#__joomleague_project_match', 'match') . ' ON match.id = event.match_id')->where('event.published = 1')->where('match.project_id = :project')->bind(':project', $projectId, ParameterType::INTEGER);
		if ($stageId !== null) $query->where('match.stage_id = :stage')->bind(':stage', $stageId, ParameterType::INTEGER);
		foreach ($this->database->setQuery($query)->loadObjectList() as $row) { $entryId = $participantEntries[(int) $row->match_participant_id] ?? null; $code = $eventStatistics[(string) $row->event_code] ?? null; if ($entryId && $code && isset($matches[(int) $row->match_id])) $matches[(int) $row->match_id]['statistics'][$entryId][$code] = StandingsDecimal::add($matches[(int) $row->match_id]['statistics'][$entryId][$code] ?? '0', '1'); }
		$query = $this->database->getQuery(true)->select(['statistic.match_id', 'statistic.match_participant_id', 'statistic.statistic_code', 'statistic.numeric_value'])->from($this->database->quoteName('#__joomleague_match_statistic_value', 'statistic'))->innerJoin($this->database->quoteName('#__joomleague_project_match', 'match') . ' ON match.id = statistic.match_id')->where('statistic.published = 1')->where('statistic.numeric_value IS NOT NULL')->where('match.project_id = :project')->bind(':project', $projectId, ParameterType::INTEGER);
		if ($stageId !== null) $query->where('match.stage_id = :stage')->bind(':stage', $stageId, ParameterType::INTEGER);
		foreach ($this->database->setQuery($query)->loadObjectList() as $row) { $entryId = $participantEntries[(int) $row->match_participant_id] ?? null; if ($entryId && isset($matches[(int) $row->match_id])) $matches[(int) $row->match_id]['statistics'][$entryId][(string) $row->statistic_code] = StandingsDecimal::add($matches[(int) $row->match_id]['statistics'][$entryId][(string) $row->statistic_code] ?? '0', (string) $row->numeric_value); }
	}

	private function existingSnapshot(int $projectId, ?int $stageId, string $scope, string $checksum): int
	{
		$stageKey = $stageId ?? 0; $query = $this->database->getQuery(true)->select('id')->from($this->database->quoteName('#__joomleague_standing_snapshot'))->where('project_id = :project')->where('stage_key = :stage')->where('scope_code = :scope')->where('input_checksum = :checksum')->bind(':project', $projectId, ParameterType::INTEGER)->bind(':stage', $stageKey, ParameterType::INTEGER)->bind(':scope', $scope)->bind(':checksum', $checksum); return (int) $this->database->setQuery($query)->loadResult();
	}

	/** @param array<string,mixed> $context */
	private function insertSnapshot(array $context, string $scope, string $checksum, int $rowCount, int $actorId): int
	{
		$project = $context['project']; $uuid = UuidFactory::v4(); $stageId = $context['stage_id']; $stageKey = $stageId ?? 0; $contract = CanonicalJson::encodeObject($context['contract']);
		$query = $this->database->getQuery(true)->insert($this->database->quoteName('#__joomleague_standing_snapshot'))->columns($this->database->quoteName(['uuid','project_id','stage_id','stage_key','scope_code','standings_type','profile_version_id','profile_checksum','input_checksum','contract_json','row_count','generated_by']))->values(':uuid,:project,:stageId,:stageKey,:scope,:type,:profile,:profileChecksum,:inputChecksum,:contract,:rows,:actor')->bind(':uuid',$uuid)->bind(':project',$project->id,ParameterType::INTEGER)->bind(':stageId',$stageId,ParameterType::INTEGER)->bind(':stageKey',$stageKey,ParameterType::INTEGER)->bind(':scope',$scope)->bind(':type',$context['standings_type'])->bind(':profile',$project->profile_version_id,ParameterType::INTEGER)->bind(':profileChecksum',$project->payload_checksum)->bind(':inputChecksum',$checksum)->bind(':contract',$contract)->bind(':rows',$rowCount,ParameterType::INTEGER)->bind(':actor',$actorId,ParameterType::INTEGER); $this->database->setQuery($query)->execute(); return (int) $this->database->insertid();
	}

	/** @param array{id:int,name:string,rank:int,metrics:array<string,?string>} $row */
	private function insertRow(int $snapshotId, array $row, int $sequence): void
	{
		$metrics = CanonicalJson::encodeObject($row['metrics']); $query = $this->database->getQuery(true)->insert($this->database->quoteName('#__joomleague_standing_snapshot_row'))->columns($this->database->quoteName(['snapshot_id','project_entry_id','entry_id_snapshot','entry_name_snapshot','rank_number','sequence_number','metrics_json']))->values(':snapshot,:entry,:entrySnapshot,:name,:rank,:sequence,:metrics')->bind(':snapshot',$snapshotId,ParameterType::INTEGER)->bind(':entry',$row['id'],ParameterType::INTEGER)->bind(':entrySnapshot',$row['id'],ParameterType::INTEGER)->bind(':name',$row['name'])->bind(':rank',$row['rank'],ParameterType::INTEGER)->bind(':sequence',$sequence,ParameterType::INTEGER)->bind(':metrics',$metrics); $this->database->setQuery($query)->execute();
	}

	private function publish(int $projectId, ?int $stageId, string $scope, int $snapshotId, int $actorId): void
	{
		$stageKey = $stageId ?? 0; $now = gmdate('Y-m-d H:i:s'); $query = $this->database->getQuery(true)->delete($this->database->quoteName('#__joomleague_standing_current'))->where('project_id = :project')->where('stage_key = :stage')->where('scope_code = :scope')->bind(':project',$projectId,ParameterType::INTEGER)->bind(':stage',$stageKey,ParameterType::INTEGER)->bind(':scope',$scope); $this->database->setQuery($query)->execute();
		$query = $this->database->getQuery(true)->insert($this->database->quoteName('#__joomleague_standing_current'))->columns($this->database->quoteName(['project_id','stage_key','scope_code','snapshot_id','updated_at','updated_by']))->values(':project,:stage,:scope,:snapshot,:updated,:actor')->bind(':project',$projectId,ParameterType::INTEGER)->bind(':stage',$stageKey,ParameterType::INTEGER)->bind(':scope',$scope)->bind(':snapshot',$snapshotId,ParameterType::INTEGER)->bind(':updated',$now)->bind(':actor',$actorId,ParameterType::INTEGER); $this->database->setQuery($query)->execute();
	}
}
