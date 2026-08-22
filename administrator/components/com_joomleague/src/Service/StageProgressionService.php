<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\CanonicalJson;
use Joomleague\Component\Joomleague\Domain\Service\StandingsDecimal;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

final class StageProgressionService
{
	public function __construct(private readonly DatabaseInterface $database) {}

	/** @return array{transition:object,entries:list<array{id:int,name:string,rank:?int,seed:?int}>,checksum:string,executable:bool,last_run:?object} */
	public function preview(int $transitionId): array
	{
		$transition = $this->transition($transitionId);
		$config = json_decode((string) ($transition->selector_config_json ?? '{}'), true, 32, JSON_THROW_ON_ERROR) ?: [];
		$entries = match ((string) $transition->selector_type) {
			'all_entries' => $this->sourceEntries($transition),
			'standing_rank_range' => $this->standingEntries($transition, $config),
			'match_outcome' => $this->outcomeEntries($transition, $config),
			'manual' => [],
			default => throw new \UnexpectedValueException('Unsupported stage transition selector.'),
		};
		$checksum = CanonicalJson::checksum([
			'transition_id' => (int) $transition->id,
			'source_stage_id' => (int) $transition->source_stage_id,
			'target_stage_id' => (int) $transition->target_stage_id,
			'selector_type' => (string) $transition->selector_type,
			'selector_config' => $config,
			'carry_over_mode' => (string) $transition->carry_over_mode,
			'target_seed_start' => $transition->target_seed_start === null ? null : (int) $transition->target_seed_start,
			'entries' => $entries,
		]);

		$query = $this->database->getQuery(true)->select('*')->from($this->database->quoteName('#__joomleague_stage_transition_run'))
			->where('transition_id = :transition')->order('id DESC')->bind(':transition', $transitionId, ParameterType::INTEGER);
		$lastRun = $this->database->setQuery($query, 0, 1)->loadObject();

		return ['transition' => $transition, 'entries' => $entries, 'checksum' => $checksum, 'executable' => $transition->selector_type !== 'manual', 'last_run' => $lastRun ?: null];
	}

	/** @return array{run_id:int,reused:bool,resolved_count:int} */
	public function apply(int $transitionId, int $actorId): array
	{
		$preview = $this->preview($transitionId);
		if (!$preview['executable']) throw new \DomainException('Manual stage transitions cannot be applied automatically.');
		$transition = $preview['transition'];
		$query = $this->database->getQuery(true)->select('id')->from($this->database->quoteName('#__joomleague_stage_transition_run'))
			->where('transition_id = :transition')->where('input_checksum = :checksum')
			->bind(':transition', $transitionId, ParameterType::INTEGER)->bind(':checksum', $preview['checksum']);
		$existing = (int) $this->database->setQuery($query)->loadResult();
		if ($existing > 0) {
			$this->database->transactionStart();
			try { $this->synchronise($transition, $preview['entries'], $existing, $actorId); $this->database->transactionCommit(); }
			catch (\Throwable $error) { $this->database->transactionRollback(); throw $error; }
			return ['run_id' => $existing, 'reused' => true, 'resolved_count' => count($preview['entries'])];
		}

		$this->database->transactionStart();
		try {
			$config = json_decode((string) ($transition->selector_config_json ?? '{}'), true) ?: [];
			$uuid = UuidFactory::v4();
			$selectorSnapshot = CanonicalJson::encodeObject(['type' => (string) $transition->selector_type, 'config' => $config]);
			$resolvedEntries = json_encode($preview['entries'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			$resolvedCount = count($preview['entries']);
			$status = 'applied';
			$appliedBy = $actorId;
			$query = $this->database->getQuery(true)->insert($this->database->quoteName('#__joomleague_stage_transition_run'))
				->columns($this->database->quoteName(['uuid','transition_id','project_id','input_checksum','selector_snapshot_json','resolved_entries_json','resolved_count','status','created_by']))
				->values(':uuid,:transition,:project,:checksum,:selector,:entries,:count,:status,:actor')
				->bind(':uuid', $uuid)->bind(':transition', $transitionId, ParameterType::INTEGER)->bind(':project', $transition->project_id, ParameterType::INTEGER)
				->bind(':checksum', $preview['checksum'])->bind(':selector', $selectorSnapshot)->bind(':entries', $resolvedEntries)
				->bind(':count', $resolvedCount, ParameterType::INTEGER)->bind(':status', $status)->bind(':actor', $appliedBy, ParameterType::INTEGER);
			$this->database->setQuery($query)->execute();
			$runId = (int) $this->database->insertid();
			$this->synchronise($transition, $preview['entries'], $runId, $actorId);
			$this->database->transactionCommit();
			return ['run_id' => $runId, 'reused' => false, 'resolved_count' => count($preview['entries'])];
		} catch (\Throwable $error) {
			$this->database->transactionRollback();
			throw $error;
		}
	}

	private function transition(int $id): object
	{
		$query = $this->database->getQuery(true)->select(['transition.*','project.name AS project_name','version.payload_json AS profile_payload_json','source.name AS source_name','target.name AS target_name'])
			->from($this->database->quoteName('#__joomleague_stage_transition','transition'))
			->innerJoin($this->database->quoteName('#__joomleague_project','project') . ' ON project.id = transition.project_id')
			->innerJoin($this->database->quoteName('#__joomleague_sport_profile_version','version') . ' ON version.id = project.profile_version_id')
			->innerJoin($this->database->quoteName('#__joomleague_project_stage','source') . ' ON source.id = transition.source_stage_id')
			->innerJoin($this->database->quoteName('#__joomleague_project_stage','target') . ' ON target.id = transition.target_stage_id')
			->where('transition.id = :id')->bind(':id', $id, ParameterType::INTEGER);
		$result = $this->database->setQuery($query)->loadObject();
		if (!$result || (int) $result->published !== 1) throw new \RuntimeException('Published stage transition does not exist.');
		return $result;
	}

	/** @return list<array{id:int,name:string,rank:?int,seed:?int}> */
	private function sourceEntries(object $transition): array
	{
		$query = $this->entryQuery((int) $transition->project_id);
		$query->leftJoin($this->database->quoteName('#__joomleague_stage_entry','stage_entry') . ' ON stage_entry.entry_id = entry.id AND stage_entry.stage_id = ' . (int) $transition->source_stage_id);
		$mode = (string) $this->database->setQuery($this->database->getQuery(true)->select('entry_selection_mode')->from($this->database->quoteName('#__joomleague_project_stage'))->where('id = ' . (int) $transition->source_stage_id))->loadResult();
		if ($mode === 'explicit') $query->where('stage_entry.entry_id IS NOT NULL');
		$query->order(['stage_entry.ordering ASC', 'entry.ordering ASC', 'entry.id ASC']);
		return $this->mapEntries($this->database->setQuery($query)->loadObjectList());
	}

	/** @param array<string,mixed> $config @return list<array{id:int,name:string,rank:?int,seed:?int}> */
	private function standingEntries(object $transition, array $config): array
	{
		$query = $this->database->getQuery(true)->select(['row.project_entry_id AS id','row.entry_name_snapshot AS name','row.rank_number AS rank','entry.seed_number AS seed'])
			->from($this->database->quoteName('#__joomleague_standing_current','current'))
			->innerJoin($this->database->quoteName('#__joomleague_standing_snapshot_row','row') . ' ON row.snapshot_id = current.snapshot_id')
			->innerJoin($this->database->quoteName('#__joomleague_project_entry','entry') . ' ON entry.id = row.project_entry_id')
			->where('current.project_id = :project')->where('current.stage_key = :stage')->where('current.scope_code = :scope')
			->where('row.rank_number >= :from')->where('row.rank_number <= :to')->order(['row.rank_number ASC','row.sequence_number ASC'])
			->bind(':project', $transition->project_id, ParameterType::INTEGER)->bind(':stage', $transition->source_stage_id, ParameterType::INTEGER)
			->bind(':scope', $config['scope'])->bind(':from', $config['from'], ParameterType::INTEGER)->bind(':to', $config['to'], ParameterType::INTEGER);
		return $this->mapEntries($this->database->setQuery($query)->loadObjectList());
	}

	/** @param array<string,mixed> $config @return list<array{id:int,name:string,rank:?int,seed:?int}> */
	private function outcomeEntries(object $transition, array $config): array
	{
		$profile = json_decode((string) $transition->profile_payload_json, true, 512, JSON_THROW_ON_ERROR);
		$eligibleStatuses = array_values(array_filter($profile['standings']['calculation']['included_result_statuses'] ?? ['final'], static fn(mixed $status): bool => is_string($status) && preg_match('/^[a-z][a-z0-9_]*$/', $status) === 1));
		if ($eligibleStatuses === []) throw new \UnexpectedValueException('The project profile has no eligible progression result status.');
		$query = $this->database->getQuery(true)->select(['match.id AS match_id','entry.id','entry.display_name','entry.entry_kind','entry.seed_number AS seed','team.name AS team_name','person.first_name','person.last_name','value.numeric_value','value.result_rank','value.status_code'])
			->from($this->database->quoteName('#__joomleague_project_match','match'))
			->innerJoin($this->database->quoteName('#__joomleague_match_result','result') . ' ON result.match_id = match.id')
			->innerJoin($this->database->quoteName('#__joomleague_match_participant','participant') . ' ON participant.match_id = match.id')
			->innerJoin($this->database->quoteName('#__joomleague_project_entry','entry') . ' ON entry.id = participant.project_entry_id')
			->innerJoin($this->database->quoteName('#__joomleague_match_score_segment','segment') . ' ON segment.match_id = match.id AND segment.parent_id IS NULL')
			->leftJoin($this->database->quoteName('#__joomleague_match_score_value','value') . ' ON value.segment_id = segment.id AND value.participant_id = participant.id')
			->leftJoin($this->database->quoteName('#__joomleague_team','team') . ' ON team.id = entry.team_id')->leftJoin($this->database->quoteName('#__joomleague_person','person') . ' ON person.id = entry.person_id')
			->where('match.stage_id = :stage')->whereIn('result.status_code', $eligibleStatuses, ParameterType::STRING)->bind(':stage', $transition->source_stage_id, ParameterType::INTEGER)->order(['match.id ASC','participant.slot_number ASC']);
		if (isset($config['round_id'])) $query->where('match.round_id = :round')->bind(':round', $config['round_id'], ParameterType::INTEGER);
		$rows = $this->database->setQuery($query)->loadObjectList(); $grouped = [];
		foreach ($rows as $row) $grouped[(int) $row->match_id][] = $row;
		$selected = [];
		$higherIsBetter = ($profile['match']['score']['higher_is_better'] ?? true) !== false;
		foreach ($grouped as $participants) {
			$resolved = [];
			foreach ($participants as $row) {
				$status = (string) ($row->status_code ?? ''); $rank = $row->result_rank === null ? null : (int) $row->result_rank;
				$wanted = $config['outcome'] === 'winner' ? ($status === 'winner' || $rank === 1) : ($status === 'loser' || ($rank !== null && $rank > 1));
				if ($wanted) $resolved[] = $row;
			}
			if ($resolved === [] && count($participants) >= 2 && !in_array(null, array_column($participants, 'numeric_value'), true)) {
				usort($participants, static function (object $left, object $right) use ($higherIsBetter): int { $comparison = StandingsDecimal::compare((string) $left->numeric_value, (string) $right->numeric_value); return $higherIsBetter ? -$comparison : $comparison; });
				$first = reset($participants); $last = end($participants);
				if (StandingsDecimal::compare((string) $first->numeric_value, (string) $last->numeric_value) !== 0) $resolved[] = $config['outcome'] === 'winner' ? $first : $last;
			}
			foreach ($resolved as $row) $selected[(int) $row->id] = $row;
		}
		return $this->mapEntries(array_values($selected));
	}

	private function entryQuery(int $projectId)
	{
		return $this->database->getQuery(true)->select(['entry.id','entry.display_name','entry.entry_kind','entry.seed_number AS seed','team.name AS team_name','person.first_name','person.last_name'])
			->from($this->database->quoteName('#__joomleague_project_entry','entry'))->leftJoin($this->database->quoteName('#__joomleague_team','team') . ' ON team.id = entry.team_id')->leftJoin($this->database->quoteName('#__joomleague_person','person') . ' ON person.id = entry.person_id')
			->where('entry.project_id = :project')->where('entry.published = 1')->bind(':project', $projectId, ParameterType::INTEGER);
	}

	/** @return list<array{id:int,name:string,rank:?int,seed:?int}> */
	private function mapEntries(array $rows): array
	{
		$result = [];
		foreach ($rows as $row) {
			$name = isset($row->name) ? (string) $row->name : match ((string) $row->entry_kind) { 'team' => (string) $row->team_name, 'person' => trim((string) $row->first_name . ' ' . (string) $row->last_name), default => (string) $row->display_name };
			$result[] = ['id' => (int) $row->id, 'name' => $name, 'rank' => isset($row->rank) ? (int) $row->rank : null, 'seed' => isset($row->seed) ? (int) $row->seed : null];
		}
		return $result;
	}

	private function synchronise(object $transition, array $entries, int $runId, int $actorId): void
	{
		$ids = array_column($entries, 'id');
		$query = $this->database->getQuery(true)->select('project_entry_id')->from($this->database->quoteName('#__joomleague_stage_transition_assignment'))->where('transition_id = :transition')->bind(':transition', $transition->id, ParameterType::INTEGER);
		$old = array_map('intval', $this->database->setQuery($query)->loadColumn());
		$query = $this->database->getQuery(true)->delete($this->database->quoteName('#__joomleague_stage_transition_assignment'))->where('transition_id = :transition')->bind(':transition', $transition->id, ParameterType::INTEGER);
		$this->database->setQuery($query)->execute();
		$seedStart = $transition->target_seed_start === null ? null : (int) $transition->target_seed_start;
		foreach ($entries as $index => $entry) {
			$id = (int) $entry['id']; $seed = $seedStart === null ? null : $seedStart + $index;
			$query = $this->database->getQuery(true)->select('COUNT(*)')->from($this->database->quoteName('#__joomleague_stage_entry'))->where('stage_id = :stage')->where('entry_id = :entry')->bind(':stage',$transition->target_stage_id,ParameterType::INTEGER)->bind(':entry',$id,ParameterType::INTEGER);
			if ((int) $this->database->setQuery($query)->loadResult() === 0) {
				$query = $this->database->getQuery(true)->insert($this->database->quoteName('#__joomleague_stage_entry'))->columns($this->database->quoteName(['stage_id','entry_id','project_id','ordering','seed_number','manual_assignment','created_by']))->values(':stage,:entry,:project,:ordering,:seed,0,:actor')->bind(':stage',$transition->target_stage_id,ParameterType::INTEGER)->bind(':entry',$id,ParameterType::INTEGER)->bind(':project',$transition->project_id,ParameterType::INTEGER)->bind(':ordering',$index,ParameterType::INTEGER)->bind(':seed',$seed,ParameterType::INTEGER)->bind(':actor',$actorId,ParameterType::INTEGER);
				$this->database->setQuery($query)->execute();
			}
			$query = $this->database->getQuery(true)->insert($this->database->quoteName('#__joomleague_stage_transition_assignment'))->columns($this->database->quoteName(['transition_id','target_stage_id','project_entry_id','project_id','run_id','target_seed','created_by']))->values(':transition,:stage,:entry,:project,:run,:seed,:actor')->bind(':transition',$transition->id,ParameterType::INTEGER)->bind(':stage',$transition->target_stage_id,ParameterType::INTEGER)->bind(':entry',$id,ParameterType::INTEGER)->bind(':project',$transition->project_id,ParameterType::INTEGER)->bind(':run',$runId,ParameterType::INTEGER)->bind(':seed',$seed,ParameterType::INTEGER)->bind(':actor',$actorId,ParameterType::INTEGER);
			$this->database->setQuery($query)->execute();
		}
		$removed = array_values(array_diff($old, $ids));
		foreach ($removed as $id) {
			$query = $this->database->getQuery(true)->select('COUNT(*)')->from($this->database->quoteName('#__joomleague_stage_transition_assignment'))->where('target_stage_id = :stage')->where('project_entry_id = :entry')->bind(':stage',$transition->target_stage_id,ParameterType::INTEGER)->bind(':entry',$id,ParameterType::INTEGER);
			if ((int) $this->database->setQuery($query)->loadResult() === 0) { $query = $this->database->getQuery(true)->delete($this->database->quoteName('#__joomleague_stage_entry'))->where('stage_id = :stage')->where('entry_id = :entry')->where('manual_assignment = 0')->bind(':stage',$transition->target_stage_id,ParameterType::INTEGER)->bind(':entry',$id,ParameterType::INTEGER); $this->database->setQuery($query)->execute(); }
		}
		$query = $this->database->getQuery(true)->update($this->database->quoteName('#__joomleague_project_stage'))->set("entry_selection_mode = 'explicit'")->where('id = :stage')->bind(':stage',$transition->target_stage_id,ParameterType::INTEGER);
		$this->database->setQuery($query)->execute();
	}
}
