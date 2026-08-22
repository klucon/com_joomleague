<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

final class MatchResultEditorSchemaBuilder
{
	/** @param array<string,mixed> $profile
	 *  @return array<string,mixed>
	 */
	public function build(array $profile): array
	{
		$score = $profile['match']['score'] ?? null;
		$structure = $profile['match']['structure'] ?? null;

		if (!is_array($score) || !is_array($structure)) throw new \UnexpectedValueException('Profile match result contract is missing.');

		$type = (string) ($score['type'] ?? '');

		if (!in_array($type, ['numeric_score', 'nested_score', 'time_result', 'decision_result'], true)) throw new \UnexpectedValueException('Profile score type is not supported.');

		$valueType = (string) ($score['value_type'] ?? '');
		$allowedValueTypes = $type === 'decision_result' ? ['structured'] : ['integer', 'decimal', 'duration'];

		if (!in_array($valueType, $allowedValueTypes, true)) throw new \UnexpectedValueException('Profile score value type is not supported.');

		$segmentTypes = $score['segment_types'] ?? null;
		if (!is_array($segmentTypes) || $segmentTypes === []) throw new \UnexpectedValueException('Profile score segment types are missing.');
		usort($segmentTypes, static fn (array $left, array $right): int => [(string) ($left['parent_code'] ?? ''), (int) ($left['ordinal'] ?? 0), (string) ($left['code'] ?? '')] <=> [(string) ($right['parent_code'] ?? ''), (int) ($right['ordinal'] ?? 0), (string) ($right['code'] ?? '')]);

		return [
			'result_type' => $type,
			'contest_type' => (string) ($profile['contest']['type'] ?? ''),
			'value_type' => $valueType,
			'editor_control' => $this->editorControl($score),
			'unit' => (string) ($score['unit'] ?? ''),
			'higher_is_better' => array_key_exists('higher_is_better', $score) ? $score['higher_is_better'] : null,
			'precision' => $score['precision'] ?? null,
			'levels' => array_merge([['code' => 'result', 'unit' => (string) ($score['unit'] ?? ''), 'parent_code' => null, 'ordinal' => 0]], $segmentTypes),
			'segment_types' => $segmentTypes,
			'aggregation' => $score['aggregation'],
			'statuses' => $profile['match']['result_status_codes'],
			'outcomes' => $profile['match']['outcome_codes'],
			'participant_statuses' => $profile['match']['participant_status_codes'],
		];
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
}
