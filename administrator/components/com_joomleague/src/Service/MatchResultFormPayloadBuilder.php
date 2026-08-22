<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

final class MatchResultFormPayloadBuilder
{
	/** @param array<string,mixed> $input @param array<string,mixed> $schema @return array<string,mixed> */
	public function build(array $input, array $schema = []): array
	{
		$segments = $input['segments'] ?? [];
		if (!is_array($segments) || !isset($segments[0]) || !is_array($segments[0])) throw new \InvalidArgumentException('Result form has no root segment.');
		$types = [];
		if (isset($schema['value_type'])) $types['result'] = ['value_type' => (string) $schema['value_type']];

		foreach ($schema['segment_types'] ?? [] as $type) {
			if (is_array($type) && is_string($type['code'] ?? null)) $types[$type['code']] = $type;
		}

		$conditions = is_array($input['conditions'] ?? null) ? $input['conditions'] : [];
		$root = $this->segment($segments[0], $types, $conditions, true);
		if ($root === null) throw new \InvalidArgumentException('Result form has no root segment.');

		return [
			'result_type' => (string) ($input['result_type'] ?? ''),
			'status_code' => (string) ($input['status_code'] ?? 'draft'),
			'outcome_code' => ($input['outcome_code'] ?? '') === '' ? null : (string) $input['outcome_code'],
			'finalized_at' => ($input['finalized_at'] ?? '') === '' ? null : (string) $input['finalized_at'],
			'notes' => ($input['notes'] ?? '') === '' ? null : (string) $input['notes'],
			'metadata' => [],
			'segments' => [$root],
		];
	}

	/** @param array<string,mixed> $input @param array<string,array<string,mixed>> $types @param array<string,mixed> $conditions @return array<string,mixed>|null */
	private function segment(array $input, array $types, array $conditions, bool $root = false): ?array
	{
		$levelCode = (string) ($input['level_code'] ?? '');
		$type = $types[$levelCode] ?? null;
		$condition = is_array($type) ? ($type['condition_code'] ?? null) : null;
		$valueType = is_array($type) ? (string) ($type['value_type'] ?? '') : '';

		if (!$root && is_string($condition) && (int) ($conditions[$condition] ?? 0) !== 1) return null;

		$values = [];
		$inputValues = is_array($input['values'] ?? null) ? $input['values'] : [];
		$listValues = array_is_list($inputValues);

		foreach ($inputValues as $participantId => $value) {
			if (!is_array($value)) continue;
			$participantId = $listValues ? (int) ($value['participant_id'] ?? 0) : (int) $participantId;
			try {
				$numericValue = $valueType === 'duration'
					? MatchResultDuration::parse((string) ($value['duration_value'] ?? ''))
					: $this->nullable($value['numeric_value'] ?? null);
			} catch (\InvalidArgumentException) {
				throw new MatchResultValidationException('COM_JOOMLEAGUE_MATCHRESULT_ERROR_DURATION_FORMAT');
			}

			$normalized = [
				'participant_id' => $participantId,
				'numeric_value' => $numericValue,
				'text_value' => $this->nullable($value['text_value'] ?? null),
				'status_code' => $this->nullable($value['status_code'] ?? null),
				'result_rank' => $this->nullable($value['result_rank'] ?? null),
				'metadata' => [],
			];
			if ($normalized['numeric_value'] !== null || $normalized['text_value'] !== null || $normalized['status_code'] !== null || $normalized['result_rank'] !== null) $values[] = $normalized;
		}

		$children = [];
		foreach (($input['children'] ?? []) as $child) {
			if (!is_array($child)) continue;
			$normalized = $this->segment($child, $types, $conditions);
			if ($normalized !== null) $children[] = $normalized;
		}

		$included = $root || is_string($condition) || (int) ($input['included'] ?? 0) === 1 || $values !== [] || $children !== [];
		if (!$included) return null;

		return [
			'level_code' => $levelCode,
			'sequence_number' => (int) ($input['sequence_number'] ?? 1),
			'status_code' => 'completed',
			'metadata' => [],
			'values' => $values,
			'children' => $children,
		];
	}

	private function nullable(mixed $value): int|string|null
	{
		if ($value === null || $value === '') return null;
		return is_int($value) ? $value : trim((string) $value);
	}
}
