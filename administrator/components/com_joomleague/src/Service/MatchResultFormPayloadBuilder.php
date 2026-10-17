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
		$encounter = is_array($schema['encounter'] ?? null);
		$root = $this->segment($segments[0], $types, $conditions, true, $encounter);
		if ($root === null) throw new \InvalidArgumentException('Result form has no root segment.');

		$payload = [
			'result_type' => (string) ($input['result_type'] ?? ''),
			'status_code' => (string) ($input['status_code'] ?? 'draft'),
			'outcome_code' => ($input['outcome_code'] ?? '') === '' ? null : (string) $input['outcome_code'],
			'finalized_at' => ($input['finalized_at'] ?? '') === '' ? null : (string) $input['finalized_at'],
			'notes' => ($input['notes'] ?? '') === '' ? null : (string) $input['notes'],
			'metadata' => [],
			'segments' => [$root],
		];
		if (is_array($schema['encounter'] ?? null)) {
			$payload['encounter_revision'] = (string) ($input['encounter_revision'] ?? '');
			$payload['duels'] = [];
			foreach ($segments[0]['children'] ?? [] as $segment) {
				if (!is_array($segment) || ($segment['level_code'] ?? null) !== 'duel') continue;
				$sequence = (int) ($segment['sequence_number'] ?? 0);
				if (!in_array($sequence, array_column(array_filter($root['children'], static fn (array $child): bool => $child['level_code'] === 'duel'), 'sequence_number'), true)) continue;
				$sides = [];
				foreach ($segment['nominations'] ?? [] as $id => $members) $sides[] = ['participant_id' => $id, 'lineup_member_ids' => is_array($members) ? array_values(array_filter($members, static fn($v): bool => $v !== '')) : []];
				$payload['duels'][] = ['sequence_number' => $sequence, 'format_code' => (string) ($segment['encounter_format'] ?? ''), 'sides' => $sides];
			}
		}
		return $payload;
	}

	/** @param array<string,mixed> $input @param array<string,array<string,mixed>> $types @param array<string,mixed> $conditions @return array<string,mixed>|null */
	private function segment(array $input, array $types, array $conditions, bool $root = false, bool $encounter = false): ?array
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

			$metadata = [];
			foreach ($type['value_fields'] ?? [] as $field => $definition) {
				$raw = $value['metadata'][$field] ?? null;
				if ($raw !== null && $raw !== '') $metadata[$field] = $raw;
			}
			$normalized = [
				'participant_id' => $participantId,
				'numeric_value' => $numericValue,
				'text_value' => $this->nullable($value['text_value'] ?? null),
				'status_code' => $this->nullable($value['status_code'] ?? null),
				'result_rank' => $this->nullable($value['result_rank'] ?? null),
				'metadata' => $metadata,
			];
			if ($normalized['numeric_value'] !== null || $normalized['text_value'] !== null || $normalized['status_code'] !== null || $normalized['result_rank'] !== null) $values[] = $normalized;
		}

		$children = [];
		foreach (($input['children'] ?? []) as $child) {
			if (!is_array($child)) continue;
			$normalized = $this->segment($child, $types, $conditions, false, $encounter);
			if ($normalized !== null) $children[] = $normalized;
		}

		$included = $root || is_string($condition) || (int) ($input['included'] ?? 0) === 1 || $values !== [] || $children !== [];
		if (!$included) return null;
		if ($encounter && $levelCode === 'duel' && ($input['duel_status'] ?? null) === 'cancelled') { $values = []; $children = []; }

		return [
			'level_code' => $levelCode,
			'sequence_number' => (int) ($input['sequence_number'] ?? 1),
			'status_code' => $encounter && $levelCode === 'duel' ? (string) ($input['duel_status'] ?? 'completed') : 'completed',
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
