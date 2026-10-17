<?php

declare(strict_types=1);

namespace Joomleague\Module\NextEvent\Site\Configuration;

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

final class NextEventConfiguration
{
	/** @return array<string,mixed> */
	public static function fromRegistry(Registry $params): array
	{
		$statuses = $params->get('status_codes', []);
		$statuses = is_array($statuses) ? $statuses : preg_split('/\s*,\s*/', (string) $statuses, -1, PREG_SPLIT_NO_EMPTY);
		$statuses = array_values(array_unique(array_filter(array_map(static fn (mixed $value): string => self::code($value), $statuses ?: []))));
		$scope = self::choice((string) $params->get('scope', 'project'), ['project', 'entry', 'club'], 'project');

		return [
			'project_id' => self::id($params->get('project_id', 0)),
			'sport_type_id' => self::id($params->get('sport_type_id', 0)),
			'stage_id' => self::id($params->get('stage_id', 0)),
			'venue_id' => self::id($params->get('venue_id', 0)),
			'scope' => $scope,
			'scope_id' => $scope === 'entry' ? self::id($params->get('entry_id', 0)) : ($scope === 'club' ? self::id($params->get('club_id', 0)) : 0),
			'status_codes' => $statuses,
			'previous_days' => self::integer($params->get('previous_days', 365), 0, 5000),
			'future_days' => self::integer($params->get('future_days', 365), 0, 5000),
			'upcoming_limit' => self::integer($params->get('upcoming_limit', 1), 0, 100),
			'previous_limit' => self::integer($params->get('previous_limit', 0), 0, 100),
			'participant_limit' => self::integer($params->get('participant_limit', 4), 0, 100),
			'unscheduled_mode' => self::choice((string) $params->get('unscheduled_mode', 'hide'), ['hide', 'after_upcoming'], 'hide'),
			'tie_breaker' => self::choice((string) $params->get('tie_breaker', 'id_asc'), ['id_asc', 'id_desc'], 'id_asc'),
			'orientation' => self::choice((string) $params->get('orientation', 'vertical'), ['vertical', 'horizontal'], 'vertical'),
			'density' => self::choice((string) $params->get('density', 'standard'), ['compact', 'standard', 'detailed'], 'standard'),
			'group_by' => self::choice((string) $params->get('group_by', 'type'), ['none', 'type', 'date', 'round'], 'type'),
			'date_format' => self::choice((string) $params->get('date_format', 'medium'), ['short', 'medium', 'long'], 'medium'),
			'time_format' => self::choice((string) $params->get('time_format', 'short'), ['short', 'medium'], 'short'),
			'image_size' => (int) self::choice((string) $params->get('image_size', '40'), ['32', '40', '48', '64'], '40'),
			'image_style' => self::choice((string) $params->get('image_style', 'contain'), ['contain', 'cover', 'circle'], 'contain'),
			'heading' => self::truncate(trim((string) $params->get('heading', '')), 150),
			'show_sport' => self::boolean($params, 'show_sport', false),
			'show_season' => self::boolean($params, 'show_season', false),
			'show_project' => self::boolean($params, 'show_project_name', true),
			'show_stage' => self::boolean($params, 'show_stage', false),
			'show_round' => self::boolean($params, 'show_round', true),
			'show_date' => self::boolean($params, 'show_date', true),
			'show_time' => self::boolean($params, 'show_time', true),
			'show_venue' => self::boolean($params, 'show_venue', true),
			'show_status' => self::boolean($params, 'show_status', false),
			'show_contest_type' => self::boolean($params, 'show_contest_type', false),
			'show_scores' => self::boolean($params, 'show_scores', true),
			'show_decider' => self::boolean($params, 'show_decider', true),
			'emphasize_winner' => self::boolean($params, 'emphasize_winner', true),
			'show_images' => self::boolean($params, 'show_images', true),
			'show_participant_links' => self::boolean($params, 'show_participant_links', true),
			'show_project_link' => self::boolean($params, 'show_project_link', true),
			'show_venue_link' => self::boolean($params, 'show_venue_link', true),
			'show_detail_link' => self::boolean($params, 'show_detail_link', true),
			'show_calendar' => self::boolean($params, 'show_calendar', true),
			'show_empty' => self::boolean($params, 'show_empty', true),
		];
	}

	private static function id(mixed $value): int { return max(0, (int) $value); }
	private static function integer(mixed $value, int $min, int $max): int { return min($max, max($min, (int) $value)); }
	/** @param list<string> $allowed */
	private static function choice(string $value, array $allowed, string $default): string { return in_array($value, $allowed, true) ? $value : $default; }
	private static function boolean(Registry $params, string $name, bool $default): bool { return (int) $params->get($name, $default ? 1 : 0) === 1; }
	private static function code(mixed $value): string { $value = trim((string) $value); return preg_match('/^[a-z0-9][a-z0-9_.-]{0,63}$/i', $value) === 1 ? $value : ''; }
	private static function truncate(string $value, int $length): string { return preg_match('/^.{0,' . $length . '}/us', $value, $match) === 1 ? $match[0] : substr($value, 0, $length); }
}
