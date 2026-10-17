<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

use Joomleague\Component\Joomleague\Domain\Service\CanonicalJson;

defined('_JEXEC') or die;

/** Produces a complete contract without changing its immutable base profile. */
final class SportVariantCompiler
{
	public function __construct(
		private readonly ProjectRuleValidator $rules = new ProjectRuleValidator(),
		private readonly SportProfileSchemaValidator $schema = new SportProfileSchemaValidator(),
	) {}

	public function compile(array $base, array $overrides): array
	{
		$this->schema->validate($base);
		$this->rules->validateOverrides($base, $overrides);
		// Standings awards and nested-score topology need dedicated compilers.
		foreach (array_keys($overrides) as $section) {
			if (!in_array($section, ['lineup', 'match_structure', 'match'], true)) {
				throw new \InvalidArgumentException('This variant rule section is not supported.');
			}
		}
		if (isset($overrides['match']) && (!isset($base['match']['encounter']) || array_keys($overrides['match']) !== ['encounter'])) {
			throw new \InvalidArgumentException('Match variants may only override declared encounter rules.');
		}
		if (isset($overrides['match_structure'])
			&& ($base['match']['structure']['type'] ?? null) !== 'timed_periods') {
			throw new \InvalidArgumentException('Match structure variants require a timed-period contract.');
		}
		if (isset($overrides['match_structure'])
			&& ($base['match_structure'] ?? null) !== $base['match']['structure']) {
			throw new \InvalidArgumentException('Base match structures disagree.');
		}
		$compiled = $this->rules->resolve($base, $overrides);
		if (isset($overrides['match_structure'])) {
			$compiled['match']['structure'] = $compiled['match_structure'];
			if (isset($overrides['match_structure']['extra_time']['period_count'])) {
				$found = false;
				foreach ($compiled['match']['score']['segment_types'] as &$segment) {
					if (($segment['code'] ?? '') !== 'extra_time') continue;
					if (($segment['expected_count'] ?? null) !== ($base['match_structure']['extra_time']['period_count'] ?? null)) throw new \InvalidArgumentException('Overtime score topology does not match the base rules.');
					$segment['expected_count'] = $compiled['match_structure']['extra_time']['period_count'];
					$found = true;
				}
				unset($segment);
				if (!$found) throw new \InvalidArgumentException('Overtime variants require an explicit overtime score segment.');
			}
			if (isset($overrides['match_structure']['period_count'])) {
				$oldCount = $base['match']['structure']['period_count'];
				$count = $compiled['match_structure']['period_count'];
				$matched = 0;
				foreach ($compiled['match']['score']['segment_types'] as &$segment) {
					if (($segment['code'] ?? null) !== 'period') continue;
					if (($segment['expected_count'] ?? null) !== $oldCount) {
						throw new \InvalidArgumentException('Period score topology does not match the base rules.');
					}
					$segment['expected_count'] = $count;
					++$matched;
				}
				unset($segment);
				if ($matched !== 1) {
					throw new \InvalidArgumentException('Period variants require one explicit period score segment.');
				}
			}
			$removed = [];
			foreach (['extra_time', 'shootout'] as $code) {
				if (isset($overrides['match_structure'][$code]['enabled']) && $compiled['match_structure'][$code]['enabled'] === false) $removed[] = $code;
			}
			if ($removed !== []) {
				$compiled['match']['score']['segment_types'] = array_values(array_filter($compiled['match']['score']['segment_types'], static fn (array $segment): bool => !in_array($segment['code'], $removed, true)));
				$compiled['match']['score']['aggregation']['from'] = array_values(array_diff($compiled['match']['score']['aggregation']['from'], $removed));
				if (in_array('extra_time', $removed, true)) {
					foreach ($compiled['match']['score']['segment_types'] as &$segment) {
						if (($segment['condition_code'] ?? '') === 'tied_after_extra_time') $segment['condition_code'] = 'tied_after_regulation';
					}
					unset($segment);
				}
			}
			if (isset($overrides['match_structure']['period_count'], $compiled['match_structure']['breaks'])) {
				$count = $compiled['match_structure']['period_count'];
				$compiled['match_structure']['breaks'] = array_values(array_filter($compiled['match_structure']['breaks'], static fn (array $break): bool => ($break['after_period'] ?? 0) < $count));
				$compiled['match']['structure'] = $compiled['match_structure'];
			}
		}
		$this->schema->validate($compiled);
		$this->rules->validateProfileSchema($compiled);
		return [
			'profile' => $compiled,
			'base_checksum' => CanonicalJson::checksum($base),
			'payload_checksum' => CanonicalJson::checksum($compiled),
			'overrides_checksum' => $this->rules->checksum($overrides),
		];
	}
}
