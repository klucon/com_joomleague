<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

use Joomleague\Component\Joomleague\Domain\Service\StandingsContractValidator;

defined('_JEXEC') or die;

final class SportProfileSchemaValidator
{
	public const CURRENT_VERSION = '1.5.0';
	private const LEGACY_VERSION = '1.4.0';
	private const SCORE_TYPES = ['numeric_score', 'nested_score', 'time_result', 'decision_result'];
	private const EDITOR_CONTROLS = ['number', 'duration', 'text', 'status_rank', 'none'];

	public function __construct(private readonly StandingsContractValidator $standingsValidator = new StandingsContractValidator()) {}

	/** @param array<string, mixed> $profile */
	public function validate(array $profile): void
	{
		$schemaVersion = $profile['schema_version'] ?? null;
		if (!in_array($schemaVersion, [self::CURRENT_VERSION, self::LEGACY_VERSION], true)) {
			throw new \UnexpectedValueException('Unsupported sport profile schema version.');
		}

		$this->code($profile['code'] ?? null, 'profile code');
		$this->code($profile['contest']['type'] ?? null, 'contest type');
		$this->entryModel($profile['entry_model'] ?? null);
		$this->lineup($profile['lineup'] ?? null);
		$this->match($profile['match'] ?? null);
		if (isset($profile['match']['encounter']) && ($profile['entry_model']['allowed_kinds'] !== ['team'] || $profile['entry_model']['default_kind'] !== 'team')) {
			throw new \UnexpectedValueException('Team encounters require team entries.');
		}
		$this->standings($profile['standings'] ?? null);
		$this->catalog($profile['positions'] ?? null, 'position');
		if ($schemaVersion === self::CURRENT_VERSION) $this->positionDomains($profile['positions']);
		$this->catalog($profile['event_types'] ?? null, 'event');
		$this->statistics($profile['statistics'] ?? null, $profile['event_types'] ?? []);
	}

	/** @param list<array<string,mixed>> $positions */
	private function positionDomains(array $positions): void
	{
		foreach ($positions as $position) {
			if (isset($position['tracks_team_record']) && !is_bool($position['tracks_team_record'])) {
				throw new \UnexpectedValueException('Position team-record tracking flag must be boolean.');
			}
			$domain = $position['role_domain'] ?? null;
			if (!is_string($domain) || !in_array($domain, RoleDomain::VALUES, true)) {
				throw new \UnexpectedValueException('Position role domain is invalid.');
			}

			$personType = $position['person_type'] ?? null;
			if (!is_string($personType)) throw new \UnexpectedValueException('Position person type is required.');
			$isLegacyEntryType = in_array($personType, ['player', 'participant', 'staff'], true);
			if (RoleDomain::isEntryMember($domain) !== $isLegacyEntryType
				|| (RoleDomain::isCompetitionActor($domain) && $personType !== 'official')) {
				throw new \UnexpectedValueException('Position role domain does not match its assignment model.');
			}
			$actorKinds = $position['actor_kinds'] ?? null;
			if (RoleDomain::isCompetitionActor($domain)) {
				if (!is_array($actorKinds) || $actorKinds === [] || array_values(array_unique($actorKinds)) !== $actorKinds) {
					throw new \UnexpectedValueException('Competition position actor kinds are required and must be unique.');
				}
				foreach ($actorKinds as $actorKind) {
					if (!in_array($actorKind, ['person', 'team'], true)) throw new \UnexpectedValueException('Position actor kind is invalid.');
				}
			} elseif ($actorKinds !== null) {
				throw new \UnexpectedValueException('Entry-member positions cannot define competition actor kinds.');
			}
			if (($position['tracks_team_record'] ?? false) === true && $domain !== RoleDomain::TEAM_STAFF) {
				throw new \UnexpectedValueException('Only team-staff positions can track a team record.');
			}
		}
	}

	private function lineup(mixed $lineup): void
	{
		if ($lineup === null) return;
		if (!is_array($lineup)) throw new \UnexpectedValueException('Lineup contract is invalid.');
		$substitutions = $lineup['substitutions'] ?? null;
		if ($substitutions === null) return;
		if (!is_array($substitutions) || !is_bool($substitutions['supported'] ?? null)) {
			throw new \UnexpectedValueException('Lineup substitution support must be boolean.');
		}
		if (($substitutions['supported'] ?? false) !== true) return;
		if (!is_bool($substitutions['reentry_supported'] ?? null)) {
			throw new \UnexpectedValueException('Lineup substitution re-entry support must be boolean.');
		}
		if (!in_array($substitutions['limit_scope'] ?? null, ['match', 'segment', 'unlimited'], true)) {
			throw new \UnexpectedValueException('Lineup substitution limit scope is invalid.');
		}
		$maximum = $substitutions['maximum_per_scope'] ?? null;
		if ($substitutions['limit_scope'] === 'unlimited') {
			if ($maximum !== null) throw new \UnexpectedValueException('Unlimited substitutions cannot define a maximum.');
		} elseif (!is_int($maximum) || $maximum < 1) {
			throw new \UnexpectedValueException('Limited substitutions require a positive maximum.');
		}
	}

	private function entryModel(mixed $model): void
	{
		if (!is_array($model) || !is_array($model['allowed_kinds'] ?? null) || $model['allowed_kinds'] === []) throw new \UnexpectedValueException('Entry model is invalid.');
		foreach ($model['allowed_kinds'] as $kind) $this->code($kind, 'entry kind');
		if (!in_array($model['default_kind'] ?? null, $model['allowed_kinds'], true)) throw new \UnexpectedValueException('Default entry kind is not allowed.');
	}

	private function match(mixed $match): void
	{
		if (!is_array($match) || !is_array($match['structure'] ?? null) || !is_array($match['score'] ?? null)) throw new \UnexpectedValueException('Match contract is invalid.');
		$this->code($match['structure']['type'] ?? null, 'match structure type'); $score = $match['score'];
		if (!in_array($score['type'] ?? null, self::SCORE_TYPES, true)) throw new \UnexpectedValueException('Score type is invalid.');
		$this->code($score['unit'] ?? null, 'score unit');
		$this->editorControl($score['editor_control'] ?? null, $score['value_type'] ?? null);
		$this->segmentTypes($score['segment_types'] ?? null);
		$this->aggregation($score['aggregation'] ?? null, $score['segment_types']);
		$this->codes($match['result_status_codes'] ?? null, 'result status');
		if ($match['result_status_codes'] !== ['draft', 'in_progress', 'final']) throw new \UnexpectedValueException('Core result statuses must be draft, in_progress and final.');
		$this->codes($match['outcome_codes'] ?? null, 'outcome');
		$this->codes($match['participant_status_codes'] ?? null, 'participant status');
		if (isset($match['encounter'])) {
			$encounter = $match['encounter'];
			if (isset($encounter['draw_supported']) && !is_bool($encounter['draw_supported'])) throw new \UnexpectedValueException('Encounter draw support must be boolean.');
			if (isset($encounter['duel_points'])) {
				$points = $encounter['duel_points'];
				if (!is_array($points) || count($points) !== 3) throw new \UnexpectedValueException('Encounter awards require win, draw and loss.');
				foreach (['win', 'draw', 'loss'] as $outcome) if (!isset($points[$outcome]) || !is_int($points[$outcome]) || $points[$outcome] < 0 || $points[$outcome] > 1000) throw new \UnexpectedValueException('Invalid encounter award.');
				if ($points['win'] <= $points['loss']) throw new \UnexpectedValueException('Encounter win award must exceed loss award.');
				if ($points['draw'] < $points['loss'] || $points['draw'] > $points['win']) throw new \UnexpectedValueException('Encounter draw award must be between loss and win awards.');
			}
			if (isset($encounter['game_rules'])) {
				$rules = $encounter['game_rules'];
				if (!is_array($rules) || !is_bool($rules['equal_innings'] ?? null)) throw new \UnexpectedValueException('Invalid encounter game rules.');
				foreach (['games_per_encounter' => [1, 128], 'target_score' => [1, 100000], 'inning_limit' => [0, 100000]] as $field => [$min, $max]) {
					if (!is_int($rules[$field] ?? null) || $rules[$field] < $min || $rules[$field] > $max) throw new \UnexpectedValueException('Invalid encounter game rule bounds.');
				}
			}
			if (!is_array($encounter) || ($encounter['aggregation'] ?? null) !== 'duel_wins' || !is_array($encounter['formats'] ?? null) || $encounter['formats'] === [] || count($encounter['formats']) > 32) throw new \UnexpectedValueException('Invalid team encounter contract.');
			foreach ($encounter['formats'] as $code => $size) {
				$this->code($code, 'encounter format');
				if (!is_int($size) || $size < 1 || $size > 64) throw new \UnexpectedValueException('Invalid encounter side size.');
			}
			$this->codes($encounter['member_person_types'] ?? null, 'encounter member type');
			if (($score['value_type'] ?? null) !== 'integer' || ($score['editor_control'] ?? null) !== 'number' || ($score['higher_is_better'] ?? null) !== true) throw new \UnexpectedValueException('Encounter scores must be non-negative integer win counts.');
			$types = array_column($score['segment_types'], null, 'code');
			if (($score['type'] ?? null) !== 'nested_score' || ($score['aggregation']['mode'] ?? null) !== 'none' || !isset($types['duel']) || ($types['duel']['parent_code'] ?? null) !== null || array_diff(array_keys($types), ['duel', 'set', 'penalty_round']) !== [] || (isset($types['set']) && ($types['set']['parent_code'] ?? null) !== 'duel')) throw new \UnexpectedValueException('Encounter requires a duel level and optionally set/penalty levels.');
			if (isset($types['penalty_round']) !== isset($encounter['penalty_series'])) throw new \UnexpectedValueException('Penalty rounds require a penalty-series contract.');
			if (isset($encounter['penalty_series'])) {
				$penalty = $encounter['penalty_series'];
				if (isset($penalty['resolution']) && !in_array($penalty['resolution'], ['sudden_death', 'team_cycle'], true)) throw new \UnexpectedValueException('Invalid penalty resolution.');
				if (isset($penalty['cycle_length']) && (!is_int($penalty['cycle_length']) || $penalty['cycle_length'] < 1 || $penalty['cycle_length'] > 64)) throw new \UnexpectedValueException('Invalid penalty cycle size.');
				if (isset($penalty['player_order']) && !in_array($penalty['player_order'], ['manual', 'ascending', 'descending', 'descending_last'], true)) throw new \UnexpectedValueException('Invalid penalty player order.');
				if (($penalty['player_order'] ?? '') === 'descending_last' && ($penalty['resolution'] ?? 'sudden_death') !== 'sudden_death') throw new \UnexpectedValueException('Last-board continuation requires sudden death.');
				if ((($penalty['resolution'] ?? '') === 'team_cycle' || ($penalty['player_order'] ?? 'manual') !== 'manual') && !isset($penalty['cycle_length'])) throw new \UnexpectedValueException('Team cycles and ordered nominations require a cycle size.');
				if (isset($penalty['nominations']) && $penalty['nominations'] !== 'optional_single') throw new \UnexpectedValueException('Invalid penalty nomination mode.');
				if (isset($types['penalty_round']['value_fields']['lineup_member_id']) && ($penalty['nominations'] ?? null) !== 'optional_single') throw new \UnexpectedValueException('Lineup references require penalty nomination support.');
				if (!is_array($penalty) || !is_bool($penalty['enabled'] ?? null) || !is_bool($penalty['required'] ?? null) || ($penalty['required'] && !$penalty['enabled']) || !is_int($penalty['maximum_points'] ?? null) || $penalty['maximum_points'] < 1 || $penalty['maximum_points'] > 100000 || ($types['penalty_round']['parent_code'] ?? null) !== null || isset($types['penalty_round']['expected_count'])) throw new \UnexpectedValueException('Invalid penalty-series contract.');
				if (!in_array('winner', $match['participant_status_codes'], true) || !in_array('loser', $match['participant_status_codes'], true)) throw new \UnexpectedValueException('Penalty decisions require winner/loser statuses.');
			}
			foreach ($types as $type) if ($type['value_type'] !== 'integer' || $type['editor_control'] !== 'number' || !isset($type['maximum_count']) || $type['maximum_count'] > 128) throw new \UnexpectedValueException('Invalid encounter result limits.');
		}
	}

	private function segmentTypes(mixed $segments): void
	{
		if (!is_array($segments) || $segments === []) throw new \UnexpectedValueException('Score segment types are required.');
		$byCode = [];

		foreach ($segments as $segment) {
			if (!is_array($segment)) throw new \UnexpectedValueException('Score segment type is invalid.');
			$code = $segment['code'] ?? null;
			$this->code($code, 'score segment type');
			if ($code === 'result' || isset($byCode[$code])) throw new \UnexpectedValueException('Score segment type is reserved or duplicated.');
			$this->code($segment['unit'] ?? null, 'score segment unit');
			if (!in_array($segment['value_type'] ?? null, ['integer', 'decimal', 'duration', 'structured'], true)) throw new \UnexpectedValueException('Score segment value type is invalid.');
			$this->editorControl($segment['editor_control'] ?? null, $segment['value_type'] ?? null);
			if (isset($segment['value_fields'])) {
				if (!is_array($segment['value_fields']) || array_is_list($segment['value_fields']) || count($segment['value_fields']) > 20) throw new \UnexpectedValueException('Invalid score statistic definitions.');
				foreach ($segment['value_fields'] as $field => $definition) {
					if (isset($definition['reference']) && ($definition['reference'] !== 'encounter_lineup' || $field !== 'lineup_member_id' || $segment['code'] !== 'penalty_round')) throw new \UnexpectedValueException('Invalid lineup reference field.');
					$this->code($field, 'score statistic');
					if (!is_array($definition) || !is_string($definition['name_key'] ?? null) || !preg_match('/^COM_JOOMLEAGUE_[A-Z0-9_]+$/', $definition['name_key']) || !is_int($definition['minimum'] ?? null) || !is_int($definition['maximum'] ?? null) || $definition['minimum'] < 0 || $definition['maximum'] < $definition['minimum'] || $definition['maximum'] > 1000000000 || (isset($definition['bounded_by_score']) && !is_bool($definition['bounded_by_score']))) throw new \UnexpectedValueException('Invalid score statistic bounds or label.');
				}
			}
			if (!is_string($segment['name_key'] ?? null) || trim($segment['name_key']) === '') throw new \UnexpectedValueException('Score segment name key is required.');
			if (!is_int($segment['ordinal'] ?? null) || $segment['ordinal'] < 0) throw new \UnexpectedValueException('Score segment ordinal is invalid.');
			if (!is_bool($segment['repeatable'] ?? null)) throw new \UnexpectedValueException('Score segment repeatable flag is required.');
			foreach (['expected_count', 'maximum_count'] as $count) if (isset($segment[$count]) && (!is_int($segment[$count]) || $segment[$count] < 1)) throw new \UnexpectedValueException('Score segment count is invalid.');
			if (($segment['expected_count'] ?? 1) > 1 && $segment['repeatable'] === false) throw new \UnexpectedValueException('A non-repeatable score segment cannot expect multiple instances.');
			if (isset($segment['expected_count'], $segment['maximum_count']) && $segment['expected_count'] > $segment['maximum_count']) throw new \UnexpectedValueException('Expected score segment count exceeds the maximum.');
			if (isset($segment['condition_code'])) $this->code($segment['condition_code'], 'score segment condition');
			$byCode[$code] = $segment;
		}

		foreach ($byCode as $code => $segment) {
			$parent = $segment['parent_code'] ?? null;
			if ($parent !== null && (!is_string($parent) || !isset($byCode[$parent]))) throw new \UnexpectedValueException('Score segment references an unknown parent.');
			$visited = [$code => true];
			while ($parent !== null) {
				if (isset($visited[$parent])) throw new \UnexpectedValueException('Score segment graph contains a cycle.');
				$visited[$parent] = true;
				$parent = $byCode[$parent]['parent_code'] ?? null;
			}
		}
	}

	private function aggregation(mixed $aggregation, array $segments): void
	{
		if (!is_array($aggregation) || !in_array($aggregation['mode'] ?? null, ['none', 'validate', 'derive'], true)) throw new \UnexpectedValueException('Score aggregation is invalid.');
		if (!is_array($aggregation['from'] ?? null) || !is_bool($aggregation['final_only'] ?? null)) throw new \UnexpectedValueException('Score aggregation contract is incomplete.');
		if (isset($aggregation['allow_legacy_aggregate_only']) && !is_bool($aggregation['allow_legacy_aggregate_only'])) throw new \UnexpectedValueException('Legacy aggregate-only support must be boolean.');
		$codes = array_fill_keys(array_column($segments, 'code'), true);
		foreach ($aggregation['from'] as $code) if (!is_string($code) || !isset($codes[$code])) throw new \UnexpectedValueException('Score aggregation references an unknown segment type.');
		if ($aggregation['mode'] === 'none' && $aggregation['from'] !== []) throw new \UnexpectedValueException('Aggregation mode none cannot declare source segments.');
		if ($aggregation['mode'] !== 'none' && $aggregation['from'] === []) throw new \UnexpectedValueException('Score aggregation requires source segments.');
	}

	private function codes(mixed $codes, string $label): void
	{
		if (!is_array($codes) || $codes === []) throw new \UnexpectedValueException(ucfirst($label) . ' codes are required.');
		$seen = [];
		foreach ($codes as $code) {
			$this->code($code, $label);
			if (isset($seen[$code])) throw new \UnexpectedValueException('Duplicate ' . $label . ' code.');
			$seen[$code] = true;
		}
	}

	private function standings(mixed $standings): void
	{
		if (!is_array($standings)) throw new \UnexpectedValueException('Standings contract is invalid.');
		$this->code($standings['type'] ?? null, 'standings type');
		if (!is_array($standings['sort_order'] ?? null) || !is_array($standings['columns'] ?? null)) throw new \UnexpectedValueException('Standings columns and sorting are required.');
		$regular = $standings['points']['regular'] ?? null;
		if ($regular !== null && (!is_array($regular) || !array_key_exists('win', $regular) || !array_key_exists('loss', $regular))) throw new \UnexpectedValueException('Regular standings points are invalid.');
		if (!is_array($standings['calculation'] ?? null)) throw new \UnexpectedValueException('Executable standings calculation contract is required.');
		$this->standingsValidator->validate($standings['calculation']);
	}

	private function catalog(mixed $items, string $label): void
	{
		if (!is_array($items)) throw new \UnexpectedValueException(ucfirst($label) . ' catalog is invalid.'); $seen = [];
		foreach ($items as $item) { if (!is_array($item)) throw new \UnexpectedValueException(ucfirst($label) . ' is invalid.'); $code = $item['code'] ?? null; $this->code($code, $label . ' code'); if (isset($seen[$code])) throw new \UnexpectedValueException('Duplicate ' . $label . ' code.'); $seen[$code] = true; }
	}

	private function statistics(mixed $statistics, mixed $events): void
	{
		$this->catalog($statistics, 'statistic'); $eventCodes = [];
		foreach (is_array($events) ? $events : [] as $event) if (is_array($event) && is_string($event['code'] ?? null)) $eventCodes[$event['code']] = true;
		foreach ($statistics as $statistic) {
			$source = $statistic['source'] ?? null;
			if (!in_array($source, ['event', 'calculated', 'manual', 'manual_or_import', 'import'], true)) throw new \UnexpectedValueException('Statistic source is invalid.');
			if ($source === 'event') {
				$references = $statistic['event_codes'] ?? null;
				if (!is_array($references) || $references === []) throw new \UnexpectedValueException('Event-sourced statistic requires event codes.');
				foreach ($references as $eventCode) if (!is_string($eventCode) || !isset($eventCodes[$eventCode])) throw new \UnexpectedValueException('Event-sourced statistic references an unknown event.');
			}
		}
	}

	private function code(mixed $value, string $label): void
	{
		if (!is_string($value) || preg_match('/^[a-z][a-z0-9_]{0,99}$/', $value) !== 1) throw new \UnexpectedValueException(ucfirst($label) . ' is invalid.');
	}

	private function editorControl(mixed $control, mixed $valueType): void
	{
		if ($control === null) return;
		if (!is_string($control) || !in_array($control, self::EDITOR_CONTROLS, true)) throw new \UnexpectedValueException('Score editor control is invalid.');
		$allowed = match ($valueType) {
			'structured' => ['text', 'status_rank', 'none'],
			'duration' => ['duration', 'none'],
			'integer', 'decimal' => ['number', 'none'],
			default => [],
		};
		if (!in_array($control, $allowed, true)) throw new \UnexpectedValueException('Score editor control does not match its value type.');
	}
}
