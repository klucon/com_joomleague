<?php

declare(strict_types=1);

namespace Joomleague\Module\ProgrammeTicker\Site\Configuration;

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

final class ProgrammeTickerConfiguration
{
	/** @return array<string,mixed> */
	public static function fromRegistry(Registry $params): array
	{
		$statuses = $params->get('status_codes', []);
		$statuses = is_array($statuses) ? $statuses : preg_split('/\s*,\s*/', (string) $statuses, -1, PREG_SPLIT_NO_EMPTY);
		$statuses = array_values(array_unique(array_filter(array_map(static fn (mixed $value): string => self::code($value), $statuses ?: []))));

		return [
			'sport_type_id' => self::id($params->get('sport_type_id', 0)),
			'season_id' => self::id($params->get('season_id', 0)),
			'project_id' => self::id($params->get('project_id', 0)),
			'stage_id' => self::id($params->get('stage_id', 0)),
			'round_id' => self::id($params->get('round_id', 0)),
			'club_id' => self::id($params->get('club_id', 0)),
			'entry_id' => self::id($params->get('entry_id', 0)),
			'venue_id' => self::id($params->get('venue_id', 0)),
			'status_codes' => $statuses,
			'days_before' => self::integer($params->get('days_before', 30), 0, 5000),
			'days_after' => self::integer($params->get('days_after', 30), 0, 5000),
			'content_mode' => self::choice((string) $params->get('content_mode', 'both'), ['both', 'completed', 'upcoming'], 'both'),
			'completed_limit' => self::integer($params->get('completed_limit', 3), 0, 100),
			'upcoming_limit' => self::integer($params->get('upcoming_limit', 5), 0, 100),
			'item_limit' => self::integer($params->get('item_limit', 8), 1, 100),
			'participant_limit' => self::integer($params->get('participant_limit', 4), 0, 100),
			'orientation' => self::choice((string) $params->get('orientation', 'horizontal'), ['horizontal', 'vertical'], 'horizontal'),
			'direction' => self::choice((string) $params->get('direction', 'left'), ['left', 'right'], 'left'),
			'speed' => self::integer($params->get('speed', 35), 5, 300),
			'pause_on_hover' => self::boolean($params, 'pause_on_hover', true),
			'loop' => self::boolean($params, 'loop', true),
			'separator' => self::truncate(trim((string) $params->get('separator', '|')), 20),
			'density' => self::choice((string) $params->get('density', 'standard'), ['compact', 'standard', 'detailed'], 'standard'),
			'date_format' => self::choice((string) $params->get('date_format', 'medium'), ['short', 'medium', 'long'], 'medium'),
			'time_format' => self::choice((string) $params->get('time_format', 'short'), ['short', 'medium'], 'short'),
			'image_size' => (int) self::choice((string) $params->get('image_size', '32'), ['24', '32', '40', '48'], '32'),
			'image_style' => self::choice((string) $params->get('image_style', 'contain'), ['contain', 'cover', 'circle'], 'contain'),
			'heading' => self::truncate(trim((string) $params->get('heading', '')), 150),
			'show_sport' => self::boolean($params, 'show_sport', false),
			'show_season' => self::boolean($params, 'show_season', false),
			'show_project' => self::boolean($params, 'show_project', false),
			'show_stage' => self::boolean($params, 'show_stage', false),
			'show_round' => self::boolean($params, 'show_round', true),
			'show_date' => self::boolean($params, 'show_date', true),
			'show_time' => self::boolean($params, 'show_time', true),
			'show_venue' => self::boolean($params, 'show_venue', false),
			'show_status' => self::boolean($params, 'show_status', false),
			'show_contest_type' => self::boolean($params, 'show_contest_type', false),
			'show_participants' => self::boolean($params, 'show_participants', true),
			'show_images' => self::boolean($params, 'show_images', false),
			'show_result' => self::boolean($params, 'show_result', true),
			'show_decider' => self::boolean($params, 'show_decider', false),
			'emphasize_winner' => self::boolean($params, 'emphasize_winner', true),
			'show_participant_links' => self::boolean($params, 'show_participant_links', true),
			'show_project_link' => self::boolean($params, 'show_project_link', true),
			'show_venue_link' => self::boolean($params, 'show_venue_link', true),
			'show_empty' => self::boolean($params, 'show_empty', true),
			'detail_override' => self::choice((string) $params->get('template_show_match_detail_button', ''), ['', '0', '1'], ''),
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
