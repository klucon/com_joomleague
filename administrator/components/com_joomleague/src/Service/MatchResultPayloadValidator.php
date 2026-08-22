<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

final class MatchResultPayloadValidator
{
	public function __construct(private readonly MatchResultAggregationValidator $aggregationValidator = new MatchResultAggregationValidator())
	{
	}

	/** @param array<string,mixed> $profile @param list<int> $participantIds @param array<string,mixed> $payload @return array<string,mixed> */
	public function validate(array $profile, array $participantIds, array $payload): array
	{
		$score = $profile['match']['score'] ?? null;
		if (!is_array($score) || ($payload['result_type'] ?? null) !== ($score['type'] ?? null)) throw new \InvalidArgumentException('Result type does not match the sport profile.');
		$status = $this->allowedCode($payload['status_code'] ?? 'draft', $profile['match']['result_status_codes'] ?? [], 'result status');
		$outcome = $this->nullableAllowedCode($payload['outcome_code'] ?? null, $profile['match']['outcome_codes'] ?? [], 'outcome');
		if ($status === 'final' && $outcome === null) throw new MatchResultValidationException('COM_JOOMLEAGUE_MATCHRESULT_ERROR_FINAL_OUTCOME_REQUIRED');
		if ($status === 'final' && $participantIds === []) throw new MatchResultValidationException('COM_JOOMLEAGUE_MATCHRESULT_ERROR_FINAL_PARTICIPANTS_REQUIRED');
		$metadata = $this->metadata($payload['metadata'] ?? []);
		$legacyAggregateOnly = ($metadata['legacy_aggregate_only'] ?? false) === true
			&& (($score['aggregation']['allow_legacy_aggregate_only'] ?? false) === true);
		$segments = $payload['segments'] ?? null;
		if (!is_array($segments) || count($segments) !== 1) throw new \InvalidArgumentException('A result requires exactly one root segment.');
		$segmentTypes = [];
		foreach ($score['segment_types'] ?? [] as $segmentType) if (is_array($segmentType) && is_string($segmentType['code'] ?? null)) $segmentTypes[$segmentType['code']] = $segmentType;
		$participants = array_fill_keys($participantIds, true);
		$segmentBudget = 1000; $valueBudget = 10000;
		$root = $this->segment($segments[0], $participants, $segmentTypes, null, $profile['match']['participant_status_codes'] ?? [], $status === 'final' && !$legacyAggregateOnly, $segmentBudget, $valueBudget);
		if ($root['level_code'] !== 'result') throw new \InvalidArgumentException('The root score segment must use the result level.');
		$root = $this->aggregationValidator->normalize($score, $root, $participantIds, $status, $legacyAggregateOnly);
		if ($status === 'final' && count($root['values']) !== count($participantIds)) throw new MatchResultValidationException('COM_JOOMLEAGUE_MATCHRESULT_ERROR_FINAL_ROOT_VALUES_REQUIRED');
		$this->aggregationValidator->validate($score, $root, $participantIds, $status, $legacyAggregateOnly);
		return ['result_type' => $score['type'], 'status_code' => $status, 'outcome_code' => $outcome, 'finalized_at' => $this->dateTime($payload['finalized_at'] ?? null), 'notes' => $this->longText($payload['notes'] ?? null, 65535), 'metadata' => $metadata, 'segments' => [$root]];
	}

	/** @param array<int,true> $participants @param array<string,array<string,mixed>> $segmentTypes @param list<string> $participantStatuses @return array<string,mixed> */
	private function segment(mixed $input, array $participants, array $segmentTypes, ?string $parentCode, array $participantStatuses, bool $enforceExpectedCounts, int &$segmentBudget, int &$valueBudget): array
	{
		if (!is_array($input)) throw new \InvalidArgumentException('Score segment must be an object.');
		if (--$segmentBudget < 0) throw new \LengthException('Result contains too many score segments.');
		$level = $this->code($input['level_code'] ?? null);
		$type = $level === 'result' ? null : ($segmentTypes[$level] ?? null);
		if ($parentCode === null && $level !== 'result') throw new \InvalidArgumentException('The root score segment must use the result level.');
		if ($parentCode !== null && (!is_array($type) || (($type['parent_code'] ?? null) ?: 'result') !== $parentCode)) throw new \InvalidArgumentException('Score segment hierarchy does not match the sport profile.');
		$sequence = filter_var($input['sequence_number'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]); if ($sequence === false) throw new \InvalidArgumentException('Segment sequence must be positive.');
		$values = []; $seen = [];
		foreach (($input['values'] ?? []) as $value) {
			if (--$valueBudget < 0) throw new \LengthException('Result contains too many score values.');
			if (!is_array($value)) throw new \InvalidArgumentException('Score value must be an object.'); $participantId = filter_var($value['participant_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
			if ($participantId === false || !isset($participants[$participantId]) || isset($seen[$participantId])) throw new \InvalidArgumentException('Score value participant is invalid or duplicated.'); $seen[$participantId] = true;
			$numeric = $this->decimal($value['numeric_value'] ?? null); $text = $this->text($value['text_value'] ?? null, 255); $status = $this->nullableAllowedCode($value['status_code'] ?? null, $participantStatuses, 'participant status'); $rank = $value['result_rank'] ?? null;
			if ($rank !== null && $rank !== '') { $rank = filter_var($rank, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]); if ($rank === false) throw new MatchResultValidationException('COM_JOOMLEAGUE_MATCHRESULT_ERROR_RANK_POSITIVE'); } else $rank = null;
			if ($numeric === null && $text === null && $status === null && $rank === null) throw new MatchResultValidationException('COM_JOOMLEAGUE_MATCHRESULT_ERROR_VALUE_REQUIRED');
			$values[] = ['participant_id' => $participantId, 'numeric_value' => $numeric, 'text_value' => $text, 'status_code' => $status, 'result_rank' => $rank, 'metadata' => $this->metadata($value['metadata'] ?? [])];
		}
		$children = []; foreach (($input['children'] ?? []) as $child) $children[] = $this->segment($child, $participants, $segmentTypes, $level, $participantStatuses, $enforceExpectedCounts, $segmentBudget, $valueBudget);
		$counts = array_count_values(array_column($children, 'level_code'));
		foreach ($segmentTypes as $childType) {
			if ((($childType['parent_code'] ?? null) ?: 'result') !== $level) continue;
			$count = $counts[$childType['code']] ?? 0;
			if (($childType['repeatable'] ?? false) === false && $count > 1) throw new \InvalidArgumentException('A non-repeatable score segment is duplicated.');
			if (isset($childType['maximum_count']) && $count > $childType['maximum_count']) throw new MatchResultValidationException('COM_JOOMLEAGUE_MATCHRESULT_ERROR_SEGMENT_MAXIMUM');
			if ($enforceExpectedCounts && isset($childType['expected_count']) && (!isset($childType['condition_code']) || $count > 0) && $count !== $childType['expected_count']) throw new MatchResultValidationException('COM_JOOMLEAGUE_MATCHRESULT_ERROR_SEGMENT_COUNT');
		}
		$ordinal = $level === 'result' ? 0 : (int) ($type['ordinal'] ?? 0);
		return ['level_code' => $level, 'segment_type_ordinal' => $ordinal, 'sequence_number' => $sequence, 'status_code' => $this->code($input['status_code'] ?? 'completed'), 'metadata' => $this->metadata($input['metadata'] ?? []), 'values' => $values, 'children' => $children];
	}

	private function decimal(mixed $value): ?string { if ($value === null || $value === '') return null; $value = (string) $value; if (preg_match('/^-?\d{1,21}(?:\.\d{1,9})?$/', $value) !== 1) throw new MatchResultValidationException('COM_JOOMLEAGUE_MATCHRESULT_ERROR_NUMERIC_VALUE'); return $value; }
	private function code(mixed $value): string { if (!is_string($value) || preg_match('/^[a-z][a-z0-9_]{0,99}$/', $value) !== 1) throw new \InvalidArgumentException('Extensible code is invalid.'); return $value; }
	private function nullableCode(mixed $value): ?string { return $value === null || $value === '' ? null : $this->code($value); }
	private function allowedCode(mixed $value, array $allowed, string $label): string { $value = $this->code($value); if (!in_array($value, $allowed, true)) throw new \InvalidArgumentException(ucfirst($label) . ' is not supported by the sport profile.'); return $value; }
	private function nullableAllowedCode(mixed $value, array $allowed, string $label): ?string { return $value === null || $value === '' ? null : $this->allowedCode($value, $allowed, $label); }
	private function text(mixed $value, int $limit): ?string { if ($value === null || $value === '') return null; $value = trim((string) $value); $length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value); if ($length > $limit) throw new \LengthException('Text value is too long.'); return $value === '' ? null : $value; }
	private function longText(mixed $value, int $byteLimit): ?string { if ($value === null || $value === '') return null; $value = trim((string) $value); if (strlen($value) > $byteLimit) throw new \LengthException('Text value is too long.'); return $value === '' ? null : $value; }
	/** @return array<string,mixed> */ private function metadata(mixed $value): array { if (!is_array($value) || (array_is_list($value) && $value !== [])) throw new \InvalidArgumentException('Metadata must be an object.'); return $value; }
	private function dateTime(mixed $value): ?string { if ($value === null || $value === '') return null; $date = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', (string) $value, new \DateTimeZone('UTC')); if (!$date || $date->format('Y-m-d H:i:s') !== $value) throw new \InvalidArgumentException('Finalization time must be UTC in Y-m-d H:i:s format.'); return (string) $value; }
}
