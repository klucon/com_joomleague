<?php

declare(strict_types=1);

namespace Joomla\Registry {
	final class Registry
	{
		/** @param array<string,mixed> $data */
		public function __construct(private array $data = []) {}
		public function get(string $name, mixed $default = null): mixed { return $this->data[$name] ?? $default; }
	}
}

namespace {
	define('_JEXEC', 1);
	require_once dirname(__DIR__, 2) . '/modules/mod_joomleague_next_event/src/Configuration/NextEventConfiguration.php';

	use Joomla\Registry\Registry;
	use Joomleague\Module\NextEvent\Site\Configuration\NextEventConfiguration;

	$defaults = NextEventConfiguration::fromRegistry(new Registry());
	$expectedDefaults = [
		'project_id' => 0, 'sport_type_id' => 0, 'stage_id' => 0, 'venue_id' => 0,
		'scope' => 'project', 'scope_id' => 0, 'status_codes' => [], 'previous_days' => 365,
		'future_days' => 365, 'upcoming_limit' => 1, 'previous_limit' => 0,
		'participant_limit' => 4, 'unscheduled_mode' => 'hide', 'tie_breaker' => 'id_asc',
		'orientation' => 'vertical', 'density' => 'standard', 'group_by' => 'type',
		'date_format' => 'medium', 'time_format' => 'short', 'image_size' => 40,
		'image_style' => 'contain', 'show_project' => true, 'show_time' => true,
		'show_calendar' => true, 'show_empty' => true,
	];
	foreach ($expectedDefaults as $name => $expected) {
		if (($defaults[$name] ?? null) !== $expected) throw new \RuntimeException('Invalid default for ' . $name);
	}

	$config = NextEventConfiguration::fromRegistry(new Registry([
		'project_id' => -1, 'sport_type_id' => '2', 'stage_id' => '3', 'venue_id' => '4',
		'scope' => 'invalid', 'entry_id' => 8, 'club_id' => 9,
		'status_codes' => ['scheduled', 'scheduled', 'bad code', 'started', str_repeat('x', 65)],
		'previous_days' => -5, 'future_days' => 99999, 'upcoming_limit' => 999,
		'previous_limit' => -2, 'participant_limit' => 999, 'unscheduled_mode' => 'random',
		'tie_breaker' => 'random', 'orientation' => 'diagonal', 'density' => 'huge',
		'group_by' => 'project', 'date_format' => 'raw', 'time_format' => 'raw',
		'image_size' => '999', 'image_style' => 'stretch', 'heading' => str_repeat('x', 200),
		'show_images' => '2', 'show_calendar' => '0',
	]));
	$expected = [
		'project_id' => 0, 'sport_type_id' => 2, 'stage_id' => 3, 'venue_id' => 4,
		'scope' => 'project', 'scope_id' => 0, 'status_codes' => ['scheduled', 'started'],
		'previous_days' => 0, 'future_days' => 5000, 'upcoming_limit' => 100,
		'previous_limit' => 0, 'participant_limit' => 100, 'unscheduled_mode' => 'hide',
		'tie_breaker' => 'id_asc', 'orientation' => 'vertical', 'density' => 'standard',
		'group_by' => 'type', 'date_format' => 'medium', 'time_format' => 'short',
		'image_size' => 40, 'image_style' => 'contain', 'show_images' => false, 'show_calendar' => false,
	];
	foreach ($expected as $name => $value) {
		if (($config[$name] ?? null) !== $value) throw new \RuntimeException(sprintf('Normalization failed for %s.', $name));
	}
	if (strlen($config['heading']) !== 150) throw new \RuntimeException('Heading was not bounded.');

	$entry = NextEventConfiguration::fromRegistry(new Registry(['scope' => 'entry', 'entry_id' => 17, 'club_id' => 19]));
	$club = NextEventConfiguration::fromRegistry(new Registry(['scope' => 'club', 'entry_id' => 17, 'club_id' => 19]));
	if ($entry['scope_id'] !== 17 || $club['scope_id'] !== 19) throw new \RuntimeException('Scope ID normalization failed.');

	$choices = [
		'scope' => ['project', 'entry', 'club'],
		'unscheduled_mode' => ['hide', 'after_upcoming'],
		'tie_breaker' => ['id_asc', 'id_desc'],
		'orientation' => ['vertical', 'horizontal'],
		'density' => ['compact', 'standard', 'detailed'],
		'group_by' => ['none', 'type', 'date', 'round'],
		'date_format' => ['short', 'medium', 'long'],
		'time_format' => ['short', 'medium'],
		'image_style' => ['contain', 'cover', 'circle'],
	];
	foreach ($choices as $name => $values) {
		foreach ($values as $value) {
			$selected = NextEventConfiguration::fromRegistry(new Registry([$name => $value]));
			if ($selected[$name] !== $value) throw new \RuntimeException(sprintf('Valid %s option %s was not preserved.', $name, $value));
		}
	}
	foreach (['32', '40', '48', '64'] as $size) {
		if (NextEventConfiguration::fromRegistry(new Registry(['image_size' => $size]))['image_size'] !== (int) $size) throw new \RuntimeException('Valid image size was not preserved.');
	}
	foreach (['show_sport', 'show_season', 'show_project_name', 'show_stage', 'show_round', 'show_date', 'show_time', 'show_venue', 'show_status', 'show_contest_type', 'show_scores', 'show_decider', 'emphasize_winner', 'show_images', 'show_participant_links', 'show_project_link', 'show_venue_link', 'show_detail_link', 'show_calendar', 'show_empty'] as $boolean) {
		$key = $boolean === 'show_project_name' ? 'show_project' : $boolean;
		if (NextEventConfiguration::fromRegistry(new Registry([$boolean => '0']))[$key] !== false
			|| NextEventConfiguration::fromRegistry(new Registry([$boolean => '1']))[$key] !== true) {
			throw new \RuntimeException('Boolean normalization failed for ' . $boolean);
		}
	}

	echo "Next Programme Event module configuration OK\n";
}
