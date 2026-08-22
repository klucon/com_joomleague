<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Read-only access to published standings snapshots. Safe to call from admin,
 * site views and modules alike — no write/recalculation logic lives here.
 */
final class StandingsReader
{
	public function __construct(private readonly DatabaseInterface $database) {}

	/** @return array<string,mixed> */
	public function describe(int $projectId, ?int $stageId): array
	{
		return $this->context($projectId, $stageId, null);
	}

	/** @return array{snapshot:?object,rows:list<object>} */
	public function current(int $projectId, ?int $stageId, string $scope): array
	{
		$context = $this->context($projectId, $stageId, $scope); $stageKey = $context['stage_id'] ?? 0;
		$query = $this->database->getQuery(true)->select('snapshot.*')->from($this->database->quoteName('#__joomleague_standing_current', 'current'))
			->innerJoin($this->database->quoteName('#__joomleague_standing_snapshot', 'snapshot') . ' ON snapshot.id = current.snapshot_id')
			->where('current.project_id = :project')->where('current.stage_key = :stage')->where('current.scope_code = :scope')
			->bind(':project', $projectId, ParameterType::INTEGER)->bind(':stage', $stageKey, ParameterType::INTEGER)->bind(':scope', $scope);
		$snapshot = $this->database->setQuery($query)->loadObject();
		if (!$snapshot) return ['snapshot' => null, 'rows' => []];
		$snapshotId = (int) $snapshot->id;
		$query = $this->database->getQuery(true)->select('*')->from($this->database->quoteName('#__joomleague_standing_snapshot_row'))->where('snapshot_id = :snapshot')->order('sequence_number ASC')->bind(':snapshot', $snapshotId, ParameterType::INTEGER);
		$rows = $this->database->setQuery($query)->loadObjectList();
		foreach ($rows as $row) $row->metrics = json_decode((string) $row->metrics_json, true, 512, JSON_THROW_ON_ERROR);
		return compact('snapshot', 'rows');
	}

	/**
	 * Resolves and validates the project/stage/scope context against the
	 * project's sport-profile standings contract. Public because
	 * StandingsRecalculator (a write-side service) needs the same
	 * resolution logic without duplicating it.
	 *
	 * @return array<string,mixed>
	 */
	public function context(int $projectId, ?int $stageId, ?string $scope): array
	{
		if ($projectId < 1 || ($stageId !== null && $stageId < 1) || ($scope !== null && preg_match('/^[a-z][a-z0-9_]*$/', $scope) !== 1)) throw new \InvalidArgumentException('Standings context is invalid.');
		$query = $this->database->getQuery(true)->select(['project.id', 'project.name', 'project.profile_version_id', 'version.profile_id', 'version.payload_json', 'version.payload_checksum'])
			->from($this->database->quoteName('#__joomleague_project', 'project'))->innerJoin($this->database->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')
			->where('project.id = :project')->bind(':project', $projectId, ParameterType::INTEGER);
		$project = $this->database->setQuery($query)->loadObject(); if (!$project) throw new \RuntimeException('Standings project does not exist.');
		$stage = null;
		if ($stageId !== null) {
			$query = $this->database->getQuery(true)->select(['id', 'name', 'entry_selection_mode'])->from($this->database->quoteName('#__joomleague_project_stage'))->where('id = :stage')->where('project_id = :project')->bind(':stage', $stageId, ParameterType::INTEGER)->bind(':project', $projectId, ParameterType::INTEGER);
			$stage = $this->database->setQuery($query)->loadObject();
			if (!$stage) throw new \InvalidArgumentException('Standings stage does not belong to the project.');
		}
		$canonicalStageId = $stage === null ? null : (int) $stage->id;
		$profile = json_decode((string) $project->payload_json, true, 512, JSON_THROW_ON_ERROR); $standings = $profile['standings'] ?? null;
		if (!is_array($standings)) throw new \UnexpectedValueException('Project profile has no standings contract.');
		if (!is_array($standings['calculation'] ?? null)) {
			$query = $this->database->getQuery(true)->select('payload_json')->from($this->database->quoteName('#__joomleague_sport_profile_version'))->where('profile_id = :profile')->where('state = ' . $this->database->quote('active'))->bind(':profile', $project->profile_id, ParameterType::INTEGER)->order('id DESC');
			$currentProfile = json_decode((string) $this->database->setQuery($query, 0, 1)->loadResult(), true);
			if (!is_array($currentProfile['standings']['calculation'] ?? null)) throw new \UnexpectedValueException('Project profile has no executable standings contract.');
			$standings['calculation'] = $currentProfile['standings']['calculation'];
		}
		if (!is_array($standings['calculation']['scopes'] ?? null) || $standings['calculation']['scopes'] === []) {
			$legacyScope = ($standings['calculation']['mode'] ?? null) === 'classification' ? 'overall' : 'total';
			$standings['calculation']['scopes'] = [['code' => $legacyScope, 'filter' => ['type' => 'always']]];
		}
		$availableScopes = [];
		foreach ($standings['calculation']['scopes'] ?? [] as $definition) if (is_array($definition) && is_string($definition['code'] ?? null)) $availableScopes[] = $definition['code'];
		if ($availableScopes === []) throw new \UnexpectedValueException('Project profile has no executable standings scope.');
		$defaultScope = $availableScopes[0];
		if ($scope !== null && !in_array($scope, $availableScopes, true)) throw new \InvalidArgumentException('Standings scope is not defined by the project profile.');
		unset($project->payload_json);
		return ['project' => $project, 'stage' => $stage, 'profile' => $profile, 'contract' => $standings['calculation'], 'standings_type' => (string) $standings['type'], 'stage_id' => $canonicalStageId, 'default_scope' => $defaultScope, 'available_scopes' => $availableScopes];
	}

	/**
	 * Recent match-by-match form (win/draw/loss) per entry, most-recent
	 * first. Only supports two-participant head-to-head matches — the only
	 * mode this component's standings engine currently implements outcome
	 * derivation for (see StandingsCalculator::outcomes()); reuses the exact
	 * same comparison this component's admin standings screen uses
	 * (StandingsDecimal, not float comparison) so a displayed form guide
	 * never disagrees with the table it sits next to.
	 *
	 * @param list<int> $entryIds
	 * @param list<string> $includedStatuses
	 * @return array<int,list<'win'|'draw'|'loss'>>
	 */
	public function recentForm(int $projectId, ?int $stageId, array $entryIds, int $count, string $outcomeSource, array $includedStatuses): array
	{
		if ($entryIds === [] || $count < 1 || $includedStatuses === []) {
			return [];
		}

		$query = $this->database->getQuery(true)
			->select([
				$this->database->quoteName('match.id', 'match_id'),
				$this->database->quoteName('match.scheduled_start'),
				$this->database->quoteName('participant.project_entry_id', 'entry_id'),
				$this->database->quoteName('value.numeric_value', 'root_value'),
				$this->database->quoteName('value.status_code'),
			])
			->from($this->database->quoteName('#__joomleague_project_match', 'match'))
			->innerJoin($this->database->quoteName('#__joomleague_match_result', 'result') . ' ON result.match_id = match.id')
			->innerJoin($this->database->quoteName('#__joomleague_match_participant', 'participant') . ' ON participant.match_id = match.id')
			->innerJoin($this->database->quoteName('#__joomleague_match_score_segment', 'segment') . ' ON segment.match_id = match.id AND segment.parent_id IS NULL')
			->leftJoin($this->database->quoteName('#__joomleague_match_score_value', 'value') . ' ON value.segment_id = segment.id AND value.participant_id = participant.id')
			->where('match.project_id = :project')
			->whereIn($this->database->quoteName('result.status_code'), $includedStatuses, ParameterType::STRING)
			->order('match.scheduled_start DESC')
			->bind(':project', $projectId, ParameterType::INTEGER);

		if ($stageId !== null) {
			$query->where('match.stage_id = :stage')->bind(':stage', $stageId, ParameterType::INTEGER);
		}

		$matches = [];
		foreach ($this->database->setQuery($query)->loadObjectList() as $row) {
			$matches[(int) $row->match_id]['scheduled_start'] ??= $row->scheduled_start;
			$matches[(int) $row->match_id]['participants'][] = [
				'entry_id' => (int) $row->entry_id,
				'root_value' => $row->root_value === null ? null : (string) $row->root_value,
				'status' => (string) ($row->status_code ?? ''),
			];
		}

		// Rows arrive per-participant (already project.scheduled_start DESC),
		// but grouping by match_id does not preserve that order — matches()
		// itself must be re-sorted before entries are trimmed to $count.
		uasort($matches, static fn (array $a, array $b) => strcmp((string) $b['scheduled_start'], (string) $a['scheduled_start']));

		$wanted = array_fill_keys($entryIds, true);
		$form = [];

		foreach ($matches as $match) {
			$participants = $match['participants'];
			if (\count($participants) !== 2) {
				continue;
			}

			[$left, $right] = $participants;
			$outcomes = $this->matchOutcomes($left, $right, $outcomeSource);

			if ($outcomes === null) {
				continue;
			}

			foreach ([$left, $right] as $participant) {
				$entryId = $participant['entry_id'];
				if (!isset($wanted[$entryId]) || \count($form[$entryId] ?? []) >= $count) {
					continue;
				}
				$form[$entryId][] = $outcomes[$entryId];
			}
		}

		return $form;
	}

	/** @return ?array{int,'win'|'draw'|'loss'} keyed by entry id, or null if undetermined */
	private function matchOutcomes(array $left, array $right, string $outcomeSource): ?array
	{
		if ($outcomeSource === 'participant_status') {
			try {
				return [
					$left['entry_id'] => match ($left['status']) { 'winner' => 'win', 'loser' => 'loss', 'draw', 'no_contest' => 'draw', default => throw new \UnexpectedValueException('') },
					$right['entry_id'] => match ($right['status']) { 'winner' => 'win', 'loser' => 'loss', 'draw', 'no_contest' => 'draw', default => throw new \UnexpectedValueException('') },
				];
			} catch (\UnexpectedValueException) {
				return null;
			}
		}

		if ($left['root_value'] === null || $right['root_value'] === null) {
			return null;
		}

		$comparison = StandingsDecimal::compare($left['root_value'], $right['root_value']);

		if ($comparison === 0) {
			return [$left['entry_id'] => 'draw', $right['entry_id'] => 'draw'];
		}

		return $comparison > 0
			? [$left['entry_id'] => 'win', $right['entry_id'] => 'loss']
			: [$left['entry_id'] => 'loss', $right['entry_id'] => 'win'];
	}
}
