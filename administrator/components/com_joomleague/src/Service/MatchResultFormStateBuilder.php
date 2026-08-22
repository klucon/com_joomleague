<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

final class MatchResultFormStateBuilder
{
	/** @param array<string,mixed> $schema @param list<array<string,mixed>> $participants @param array<string,mixed>|null $result @return array<string,mixed> */
	public function build(array $schema, array $participants, ?array $result): array
	{
		if ($result !== null) {
			$stored = $this->decorateExisting($result, $schema, $participants);
			$template = $this->build($schema, $participants, null);
			$stored['segments'][0] = $this->mergeSegment($template['segments'][0], $stored['segments'][0]);
			$stored['conditions'] = $template['conditions'];
			$this->collectActiveConditions($stored['segments'][0], $stored['conditions']);
			return $stored;
		}

		$types = $this->typesByParent($schema['segment_types'] ?? []);
		$root = $this->emptySegment('result', 0, 1, $schema['value_type'] ?? 'integer', $schema['editor_control'] ?? 'number', $participants, true);
		$root['children'] = $this->children('result', $types, $participants);

		return [
			'result_type' => (string) ($schema['result_type'] ?? ''),
			'status_code' => 'draft',
			'outcome_code' => null,
			'notes' => null,
			'conditions' => $this->conditions($schema['segment_types'] ?? []),
			'segments' => [$root],
		];
	}

	/** @param list<array<string,mixed>> $segmentTypes @return array<string,list<array<string,mixed>>> */
	private function typesByParent(array $segmentTypes): array
	{
		$types = [];
		foreach ($segmentTypes as $type) {
			if (!is_array($type)) continue;
			$parent = (string) (($type['parent_code'] ?? null) ?: 'result');
			$types[$parent][] = $type;
		}
		foreach ($types as &$children) usort($children, static fn (array $a, array $b): int => [(int) ($a['ordinal'] ?? 0), (string) ($a['code'] ?? '')] <=> [(int) ($b['ordinal'] ?? 0), (string) ($b['code'] ?? '')]);
		return $types;
	}

	/** @param array<string,list<array<string,mixed>>> $types @param list<array<string,mixed>> $participants @return list<array<string,mixed>> */
	private function children(string $parent, array $types, array $participants): array
	{
		$children = [];
		foreach ($types[$parent] ?? [] as $type) {
			$required = isset($type['expected_count']) && !isset($type['condition_code']);
			$count = max(1, (int) ($type['expected_count'] ?? 1));
			for ($sequence = 1; $sequence <= $count; $sequence++) {
				$segment = $this->emptySegment((string) $type['code'], (int) $type['ordinal'], $sequence, (string) $type['value_type'], $this->editorControl($type), $participants, $required);
				$segment['name_key'] = (string) $type['name_key'];
				$segment['condition_code'] = $type['condition_code'] ?? null;
				$segment['children'] = $this->children((string) $type['code'], $types, $participants);
				$children[] = $segment;
			}
		}
		return $children;
	}

	/** @param list<array<string,mixed>> $participants @return array<string,mixed> */
	private function emptySegment(string $code, int $ordinal, int $sequence, string $valueType, string $editorControl, array $participants, bool $required): array
	{
		$values = [];
		if ($editorControl !== 'none') foreach ($participants as $participant) $values[] = ['participant_id' => (int) $participant['id'], 'numeric_value' => null, 'duration_value' => '', 'text_value' => null, 'status_code' => null, 'result_rank' => null];
		return ['level_code' => $code, 'segment_type_ordinal' => $ordinal, 'sequence_number' => $sequence, 'value_type' => $valueType, 'editor_control' => $editorControl, 'required' => $required, 'included' => $required, 'values' => $values, 'children' => []];
	}

	/** @param array<string,mixed> $result @param array<string,mixed> $schema @param list<array<string,mixed>> $participants @return array<string,mixed> */
	private function decorateExisting(array $result, array $schema, array $participants): array
	{
		$types = [];
		foreach ($schema['segment_types'] ?? [] as $type) if (is_array($type)) $types[(string) $type['code']] = $type;
		$decorate = function (array $segment) use (&$decorate, $types, $participants, $schema): array {
			$type = $segment['level_code'] === 'result' ? ['value_type' => $schema['value_type'], 'editor_control' => $schema['editor_control'], 'name_key' => null] : ($types[$segment['level_code']] ?? []);
			$editorControl = $this->editorControl($type);
			$stored = [];
			foreach ($segment['values'] ?? [] as $value) $stored[(int) $value['participant_id']] = $value;
			$values = [];
			foreach ($editorControl === 'none' ? [] : $participants as $participant) {
				$value = array_replace(['participant_id' => (int) $participant['id'], 'numeric_value' => null, 'duration_value' => '', 'text_value' => null, 'status_code' => null, 'result_rank' => null], $stored[(int) $participant['id']] ?? []);
				if (($type['value_type'] ?? null) === 'duration') $value['duration_value'] = MatchResultDuration::format($value['numeric_value']);
				$values[] = $value;
			}
			$segment['value_type'] = (string) ($type['value_type'] ?? 'integer');
			$segment['editor_control'] = $editorControl;
			$segment['name_key'] = $type['name_key'] ?? null;
			$segment['condition_code'] = $type['condition_code'] ?? null;
			$segment['required'] = isset($type['expected_count']) && !isset($type['condition_code']);
			$segment['included'] = true;
			$segment['values'] = $values;
			$segment['children'] = array_map($decorate, $segment['children'] ?? []);
			return $segment;
		};
		$result['segments'] = array_map($decorate, $result['segments'] ?? []);
		return $result;
	}

	/** @param array<string,mixed> $definition */
	private function editorControl(array $definition): string
	{
		if (is_string($definition['editor_control'] ?? null)) return $definition['editor_control'];
		return match ($definition['value_type'] ?? null) {
			'duration' => 'duration',
			'structured' => 'text',
			default => 'number',
		};
	}

	/** @param array<string,mixed> $template @param array<string,mixed> $stored @return array<string,mixed> */
	private function mergeSegment(array $template, array $stored): array
	{
		$merged = array_replace($template, $stored);
		$templateChildren = [];
		foreach ($template['children'] ?? [] as $child) $templateChildren[$child['level_code'] . ':' . $child['sequence_number']] = $child;
		$children = [];
		foreach ($stored['children'] ?? [] as $child) {
			$key = $child['level_code'] . ':' . $child['sequence_number'];
			$children[] = isset($templateChildren[$key]) ? $this->mergeSegment($templateChildren[$key], $child) : $child;
			unset($templateChildren[$key]);
		}
		foreach ($templateChildren as $child) $children[] = $child;
		usort($children, static fn (array $a, array $b): int => [(int) ($a['segment_type_ordinal'] ?? 0), (int) ($a['sequence_number'] ?? 0)] <=> [(int) ($b['segment_type_ordinal'] ?? 0), (int) ($b['sequence_number'] ?? 0)]);
		$merged['children'] = $children;
		return $merged;
	}

	/** @param list<array<string,mixed>> $segmentTypes @return array<string,bool> */
	private function conditions(array $segmentTypes): array
	{
		$conditions = [];

		foreach ($segmentTypes as $type) {
			if (is_array($type) && is_string($type['condition_code'] ?? null)) $conditions[$type['condition_code']] = false;
		}

		return $conditions;
	}

	/** @param array<string,mixed> $segment @param array<string,bool> $conditions */
	private function collectActiveConditions(array $segment, array &$conditions): void
	{
		$condition = $segment['condition_code'] ?? null;

		if (is_string($condition) && ($segment['included'] ?? false)) $conditions[$condition] = true;

		foreach ($segment['children'] ?? [] as $child) {
			if (is_array($child)) $this->collectActiveConditions($child, $conditions);
		}
	}
}
