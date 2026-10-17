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
	require_once dirname(__DIR__, 2) . '/modules/mod_joomleague_latest_results/src/Configuration/LatestResultsConfiguration.php';

	use Joomla\Registry\Registry;
	use Joomleague\Module\LatestResults\Site\Configuration\LatestResultsConfiguration;

	$defaults = LatestResultsConfiguration::fromRegistry(new Registry());
	$expectedDefaults = [
		'sport_type_id' => 0, 'season_id' => 0, 'project_id' => 0, 'stage_id' => 0,
		'round_id' => 0, 'club_id' => 0, 'entry_id' => 0, 'venue_id' => 0,
		'status_codes' => [], 'days_before' => 365, 'limit' => 5, 'participant_limit' => 4,
		'order' => 'newest', 'orientation' => 'vertical', 'density' => 'standard',
		'group_by' => 'none', 'date_format' => 'medium', 'time_format' => 'short', 'image_size' => 40,
		'show_project' => true, 'show_scores' => true, 'show_empty' => true,
		'detail_override' => '',
	];
	foreach ($expectedDefaults as $name => $expected) {
		if (($defaults[$name] ?? null) !== $expected) throw new \RuntimeException('Invalid default for ' . $name);
	}

	$config = LatestResultsConfiguration::fromRegistry(new Registry([
		'sport_type_id' => -2, 'season_id' => '3', 'project_id' => '4', 'stage_id' => '5',
		'round_id' => '6', 'club_id' => '7', 'entry_id' => '8', 'venue_id' => '9',
		'status_codes' => ['finished', 'finished', 'bad code', 'completed', str_repeat('a', 65)],
		'days_before' => 999999, 'limit' => 0, 'participant_limit' => -4,
		'order' => 'random', 'orientation' => 'diagonal', 'density' => 'huge',
		'group_by' => 'SQL', 'date_format' => 'raw', 'time_format' => 'raw', 'image_size' => '999',
		'heading' => str_repeat('x', 200), 'show_images' => '2', 'show_scores' => '0',
		'template_show_match_detail_button' => 'yes',
	]));
	$expected = [
		'sport_type_id' => 0, 'season_id' => 3, 'project_id' => 4, 'stage_id' => 5,
		'round_id' => 6, 'club_id' => 7, 'entry_id' => 8, 'venue_id' => 9,
		'status_codes' => ['finished', 'completed'], 'days_before' => 5000,
		'limit' => 1, 'participant_limit' => 0, 'order' => 'newest',
		'orientation' => 'vertical', 'density' => 'standard', 'group_by' => 'none',
		'date_format' => 'medium', 'time_format' => 'short', 'image_size' => 40, 'show_images' => false,
		'show_scores' => false, 'detail_override' => '',
	];
	foreach ($expected as $name => $value) {
		if (($config[$name] ?? null) !== $value) throw new \RuntimeException(sprintf('Normalization failed for %s.', $name));
	}
	if (strlen($config['heading']) !== 150) throw new \RuntimeException('Heading was not bounded.');

	$choices = [
		'order' => ['newest', 'oldest'],
		'orientation' => ['vertical', 'horizontal'],
		'density' => ['compact', 'standard', 'detailed'],
		'group_by' => ['none', 'date', 'project', 'round'],
		'date_format' => ['short', 'medium', 'long'],
		'time_format' => ['short', 'medium'],
	];
	foreach ($choices as $name => $values) {
		foreach ($values as $value) {
			$selected = LatestResultsConfiguration::fromRegistry(new Registry([$name => $value]));
			if ($selected[$name] !== $value) throw new \RuntimeException(sprintf('Valid %s option %s was not preserved.', $name, $value));
		}
	}
	foreach (['32', '40', '48', '64'] as $size) {
		if (LatestResultsConfiguration::fromRegistry(new Registry(['image_size' => $size]))['image_size'] !== (int) $size) {
			throw new \RuntimeException('Valid image size was not preserved.');
		}
	}
	foreach (['show_sport', 'show_season', 'show_project', 'show_stage', 'show_round', 'show_date', 'show_time', 'show_venue', 'show_status', 'show_contest_type', 'show_images', 'show_scores', 'show_decider', 'emphasize_winner', 'show_participant_links', 'show_project_link', 'show_venue_link', 'show_empty'] as $boolean) {
		if (LatestResultsConfiguration::fromRegistry(new Registry([$boolean => '0']))[$boolean] !== false
			|| LatestResultsConfiguration::fromRegistry(new Registry([$boolean => '1']))[$boolean] !== true) {
			throw new \RuntimeException('Boolean normalization failed for ' . $boolean);
		}
	}
	foreach (['', '0', '1'] as $override) {
		if (LatestResultsConfiguration::fromRegistry(new Registry(['template_show_match_detail_button' => $override]))['detail_override'] !== $override) {
			throw new \RuntimeException('Detail override was not preserved.');
		}
	}

	echo "Latest Results module configuration OK\n";
}
