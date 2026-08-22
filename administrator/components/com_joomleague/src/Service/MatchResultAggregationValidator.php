<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

final class MatchResultAggregationValidator
{
	/** @param array<string,mixed> $score @param array<string,mixed> $root @param list<int> $participantIds @return array<string,mixed> */
	public function normalize(array $score, array $root, array $participantIds, string $status, bool $legacyAggregateOnly = false): array
	{
		$aggregation = $score['aggregation'] ?? null;
		if ($legacyAggregateOnly || !is_array($aggregation) || ($aggregation['mode'] ?? null) !== 'derive') return $root;
		if (($aggregation['final_only'] ?? false) && $status !== 'final') return $root;

		$segments = $this->sourceSegments($aggregation, $root);
		if ($segments === []) throw new MatchResultValidationException('COM_JOOMLEAGUE_MATCHRESULT_ERROR_AGGREGATION_SEGMENTS_REQUIRED');
		$rootValues = $this->valuesByParticipant($root['values'] ?? []);

		foreach ($participantIds as $participantId) {
			$parts = [];
			foreach ($segments as $segment) {
				$values = $this->valuesByParticipant($segment['values'] ?? []);
				$value = $values[$participantId]['numeric_value'] ?? null;
				if (!is_string($value)) throw new MatchResultValidationException('COM_JOOMLEAGUE_MATCHRESULT_ERROR_AGGREGATION_SEGMENT_NUMERIC');
				$parts[] = $value;
			}
			$derived = MatchResultDecimal::sum($parts);
			if (preg_match('/^-?\d{1,21}(?:\.\d{1,9})?$/', $derived) !== 1) throw new MatchResultValidationException('COM_JOOMLEAGUE_MATCHRESULT_ERROR_NUMERIC_VALUE');
			$rootValues[$participantId] = array_replace([
				'participant_id' => $participantId, 'numeric_value' => null, 'text_value' => null,
				'status_code' => null, 'result_rank' => null, 'metadata' => [],
			], $rootValues[$participantId] ?? [], ['numeric_value' => $derived]);
		}

		$root['values'] = array_values(array_map(static fn (int $participantId): array => $rootValues[$participantId], $participantIds));
		return $root;
	}

	/** @param array<string,mixed> $score @param array<string,mixed> $root @param list<int> $participantIds */
	public function validate(array $score, array $root, array $participantIds, string $status, bool $legacyAggregateOnly = false): void
	{
		$aggregation = $score['aggregation'] ?? null;

		if ($legacyAggregateOnly || !is_array($aggregation) || ($aggregation['mode'] ?? null) !== 'validate' || $status !== 'final') return;

		$segments = $this->sourceSegments($aggregation, $root);

		if ($segments === []) throw new MatchResultValidationException('COM_JOOMLEAGUE_MATCHRESULT_ERROR_AGGREGATION_SEGMENTS_REQUIRED');

		$rootValues = $this->valuesByParticipant($root['values'] ?? []);

		foreach ($participantIds as $participantId) {
			$expected = $rootValues[$participantId]['numeric_value'] ?? null;

			if (!is_string($expected)) throw new MatchResultValidationException('COM_JOOMLEAGUE_MATCHRESULT_ERROR_AGGREGATION_ROOT_NUMERIC');

			$parts = [];

			foreach ($segments as $segment) {
				$values = $this->valuesByParticipant($segment['values'] ?? []);
				$value = $values[$participantId]['numeric_value'] ?? null;

				if (!is_string($value)) throw new MatchResultValidationException('COM_JOOMLEAGUE_MATCHRESULT_ERROR_AGGREGATION_SEGMENT_NUMERIC');

				$parts[] = $value;
			}

			if (!MatchResultDecimal::sumEquals($expected, $parts)) {
				throw new MatchResultValidationException('COM_JOOMLEAGUE_MATCHRESULT_ERROR_AGGREGATION_MISMATCH');
			}
		}
	}

	/** @param array<string,mixed> $aggregation @param array<string,mixed> $root @return list<array<string,mixed>> */
	private function sourceSegments(array $aggregation, array $root): array
	{
		$from = array_fill_keys(array_map('strval', $aggregation['from'] ?? []), true);
		return array_values(array_filter(
			$root['children'] ?? [],
			static fn (mixed $segment): bool => is_array($segment) && isset($from[$segment['level_code'] ?? ''])
		));
	}

	/** @param list<array<string,mixed>> $values @return array<int,array<string,mixed>> */
	private function valuesByParticipant(array $values): array
	{
		$result = [];

		foreach ($values as $value) {
			if (is_array($value)) $result[(int) ($value['participant_id'] ?? 0)] = $value;
		}

		return $result;
	}
}
