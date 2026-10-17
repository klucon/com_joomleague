<?php

declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Service;
defined('_JEXEC') or die;

final class SportVariantRules
{
	private const POINTERS = [
		'/match_structure/period_count', '/match_structure/period_length_minutes',
		'/match_structure/default_start_time', '/match_structure/default_match_duration_minutes',
		'/match_structure/extra_time/enabled', '/match_structure/extra_time/period_length_minutes',
		'/match_structure/shootout/enabled', '/match_structure/shootout/initial_attempts_per_team',
		'/match_structure/stoppage_time', '/match_structure/extra_time/period_count',
		'/match_structure/shootout/initial_kicks_per_team', '/lineup/players_on_field',
		'/lineup/captain_supported', '/lineup/minimum_players_to_start',
		'/lineup/default_substitutes_allowed', '/lineup/default_substitution_windows',
		'/lineup/players_on_ice', '/lineup/skaters_on_ice', '/lineup/goalkeepers_on_ice', '/lineup/players_on_court',
		'/match/encounter/duel_points/win', '/match/encounter/duel_points/draw', '/match/encounter/duel_points/loss',
		'/match/encounter/draw_supported', '/match/encounter/game_rules/games_per_encounter',
		'/match/encounter/game_rules/target_score', '/match/encounter/game_rules/inning_limit', '/match/encounter/game_rules/equal_innings',
	];

	public function fields(array $profile): array
	{
		$fields = [];
		foreach (self::POINTERS as $pointer) {
			$definition = $profile['project_rule_schema']['fields'][$pointer] ?? null;
			if (!$definition || (str_starts_with($pointer, '/match_structure/') && ($profile['match']['structure']['type'] ?? '') !== 'timed_periods')) continue;
			$value = $profile;
			foreach (explode('/', trim($pointer, '/')) as $part) $value = $value[$part];
			$key = 'rule_' . substr(hash('sha256', $pointer), 0, 24);
			$fields[$key] = ['pointer' => $pointer, 'label' => $definition['name_key'] ?? ('COM_JOOMLEAGUE_SPORTVARIANT_RULE_' . strtoupper(str_replace('/', '_', trim($pointer, '/')))), 'default' => $value, 'definition' => $definition];
		}
		return $fields;
	}

	public function parse(array $profile, array $submitted): array
	{
		$fields = $this->fields($profile);
		if (array_diff(array_keys($submitted), array_keys($fields)) !== []) throw new \InvalidArgumentException('Unknown variant rule.');
		$overrides = [];
		foreach ($submitted as $key => $input) {
			if (!is_array($input) || array_diff(array_keys($input), ['enabled', 'value']) !== []) throw new \InvalidArgumentException('Invalid variant rule input.');
			if (($input['enabled'] ?? '0') !== '1') continue;
			$value = $input['value'] ?? null;
			if (!is_string($value) || strlen($value) > 255) throw new \InvalidArgumentException('Invalid variant value.');
			$definition = $fields[$key]['definition'];
			$value = match ($definition['type']) {
				'boolean' => match ($value) { '1' => true, '0' => false, default => throw new \InvalidArgumentException('Invalid boolean rule.') },
				'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : throw new \InvalidArgumentException('Invalid integer rule.'),
				'string' => trim($value),
				default => throw new \InvalidArgumentException('Unsupported variant input type.'),
			};
			$reference =& $overrides;
			foreach (explode('/', trim($fields[$key]['pointer'], '/')) as $part) $reference =& $reference[$part];
			$reference = $value;
			unset($reference);
		}
		(new SportVariantCompiler())->compile($profile, $overrides);
		return $overrides;
	}
}
