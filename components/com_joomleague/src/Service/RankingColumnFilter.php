<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Service;

defined('_JEXEC') or die;

/** Applies the sport-profile-aware ranking presentation contract to metric columns. */
final class RankingColumnFilter
{
	/** @param list<array<string,mixed>> $columns @param array<string,mixed> $config @return list<array<string,mixed>> */
	public function apply(array $columns, array $config): array
	{
		return array_values(array_filter($columns, fn (array $column): bool => $this->visible($column, $config)));
	}

	/** @param array<string,mixed> $column @param array<string,mixed> $config */
	private function visible(array $column, array $config): bool
	{
		$codes = ($column['type'] ?? 'single') === 'combined'
			? [(string) ($column['for'] ?? ''), (string) ($column['against'] ?? '')]
			: [(string) ($column['code'] ?? '')];

		foreach ($codes as $code) {
			if (!($config['show_goal_difference'] ?? true) && str_contains($code, 'difference')) return false;
			if (!($config['show_sets'] ?? true) && str_contains($code, 'set')) return false;
			if (!($config['show_points'] ?? true) && in_array($code, ['points', 'match_points', 'game_points'], true)) return false;
			if (!($config['show_score'] ?? true) && (str_contains($code, 'score') || preg_match('/^(goal|point|pin)s?_(for|against)$/', $code) === 1)) return false;
		}

		return true;
	}
}
