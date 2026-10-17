<?php

declare(strict_types=1);

namespace Joomleague\Module\Program\Site\Configuration;

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

final class ProgramConfiguration
{
	/** @return array<string,mixed> */
	public static function fromRegistry(Registry $params): array
	{
		$scope = self::choice((string) $params->get('scope', 'project'), ['project', 'entry', 'club'], 'project');
		$mode = self::choice((string) $params->get('mode', 'upcoming'), ['upcoming', 'played', 'all'], 'upcoming');
		$sortDirection = self::choice((string) $params->get('sort_direction', 'auto'), ['auto', 'asc', 'desc'], 'auto');
		if ($sortDirection === 'auto') {
			$sortDirection = $mode === 'played' ? 'desc' : 'asc';
		}

		$statusCodes = $params->get('status_codes', []);
		$statusCodes = is_array($statusCodes) ? $statusCodes : preg_split('/\s*,\s*/', (string) $statusCodes, -1, PREG_SPLIT_NO_EMPTY);
		$statusCodes = array_values(array_unique(array_filter(array_map(
			static fn (mixed $value): string => preg_match('/^[a-z0-9][a-z0-9_.-]{0,63}$/i', trim((string) $value)) === 1 ? trim((string) $value) : '',
			$statusCodes ?: []
		))));

		return [
			'project_id' => max(0, (int) $params->get('project_id', 0)),
			'scope' => $scope,
			'scope_id' => match ($scope) {
				'entry' => max(0, (int) $params->get('entry_id', 0)),
				'club' => max(0, (int) $params->get('club_id', 0)),
				default => 0,
			},
			'mode' => $mode,
			'stage_id' => max(0, (int) $params->get('stage_id', 0)),
			'round_id' => max(0, (int) $params->get('round_id', 0)),
			'venue_id' => max(0, (int) $params->get('venue_id', 0)),
			'status_codes' => $statusCodes,
			'days_before' => self::integer($params->get('days_before', 0), 0, 5000),
			'days_after' => self::integer($params->get('days_after', 0), 0, 5000),
			'limit' => self::integer($params->get('limit', 5), 1, 100),
			'participant_limit' => self::integer($params->get('participant_limit', 0), 0, 100),
			'sort_by' => self::choice((string) $params->get('sort_by', 'date'), ['date', 'round', 'venue', 'status'], 'date'),
			'sort_direction' => $sortDirection,
			'orientation' => self::choice((string) $params->get('orientation', 'vertical'), ['vertical', 'horizontal'], 'vertical'),
			'density' => self::choice((string) $params->get('density', 'standard'), ['compact', 'standard', 'detailed'], 'standard'),
			'group_by' => self::choice((string) $params->get('group_by', 'none'), ['none', 'date', 'round'], 'none'),
			'image_size' => (int) self::choice((string) $params->get('image_size', '40'), ['32', '40', '48', '64'], '40'),
			'date_format' => self::choice((string) $params->get('date_format', 'medium'), ['short', 'medium', 'long'], 'medium'),
			'heading' => self::truncate(trim((string) $params->get('heading', '')), 150),
			'show_project_name' => self::boolean($params, 'show_project_name', true),
			'show_round' => self::boolean($params, 'show_round', true),
			'show_date' => self::boolean($params, 'show_date', true),
			'show_venue' => self::boolean($params, 'show_venue', true),
			'show_result' => self::boolean($params, 'show_result', true),
			'show_status' => self::boolean($params, 'show_status', false),
			'show_contest_type' => self::boolean($params, 'show_contest_type', false),
			'show_images' => self::boolean($params, 'show_images', false),
			'show_participant_links' => self::boolean($params, 'show_participant_links', true),
			'show_venue_link' => self::boolean($params, 'show_venue_link', true),
			'show_calendar' => self::boolean($params, 'show_calendar', true),
			'show_empty' => self::boolean($params, 'show_empty', true),
			'detail_override' => self::choice((string) $params->get('template_show_match_detail_button', ''), ['', '0', '1'], ''),
		];
	}

	/** @param list<string> $allowed */
	private static function choice(string $value, array $allowed, string $default): string
	{
		return in_array($value, $allowed, true) ? $value : $default;
	}

	private static function integer(mixed $value, int $minimum, int $maximum): int
	{
		return min($maximum, max($minimum, (int) $value));
	}

	private static function boolean(Registry $params, string $name, bool $default): bool
	{
		return (int) $params->get($name, $default ? 1 : 0) === 1;
	}

	private static function truncate(string $value, int $length): string
	{
		if (preg_match('/^.{0,' . $length . '}/us', $value, $match) === 1) {
			return $match[0];
		}

		return substr($value, 0, $length);
	}
}
