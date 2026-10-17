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
	require_once dirname(__DIR__, 2) . '/modules/mod_joomleague_programme_ticker/src/Configuration/ProgrammeTickerConfiguration.php';

	use Joomla\Registry\Registry;
	use Joomleague\Module\ProgrammeTicker\Site\Configuration\ProgrammeTickerConfiguration;

	$defaults = ProgrammeTickerConfiguration::fromRegistry(new Registry());
	$expectedDefaults = [
		'sport_type_id' => 0, 'season_id' => 0, 'project_id' => 0, 'stage_id' => 0, 'round_id' => 0,
		'club_id' => 0, 'entry_id' => 0, 'venue_id' => 0, 'status_codes' => [], 'days_before' => 30,
		'days_after' => 30, 'content_mode' => 'both', 'completed_limit' => 3, 'upcoming_limit' => 5,
		'item_limit' => 8, 'participant_limit' => 4, 'orientation' => 'horizontal', 'direction' => 'left',
		'speed' => 35, 'pause_on_hover' => true, 'loop' => true, 'separator' => '|', 'density' => 'standard',
		'date_format' => 'medium', 'time_format' => 'short', 'image_size' => 32, 'image_style' => 'contain',
		'show_participants' => true, 'show_result' => true, 'show_empty' => true, 'detail_override' => '',
	];
	foreach ($expectedDefaults as $name => $expected) if (($defaults[$name] ?? null) !== $expected) throw new \RuntimeException('Invalid default for ' . $name);

	$config = ProgrammeTickerConfiguration::fromRegistry(new Registry([
		'sport_type_id' => -1, 'season_id' => '2', 'project_id' => '3', 'stage_id' => '4', 'round_id' => '5',
		'club_id' => '6', 'entry_id' => '7', 'venue_id' => '8',
		'status_codes' => ['scheduled', 'scheduled', 'bad code', 'finished', str_repeat('x', 65)],
		'days_before' => -1, 'days_after' => 99999, 'completed_limit' => 999, 'upcoming_limit' => -1,
		'item_limit' => 0, 'participant_limit' => 999, 'content_mode' => 'invalid', 'orientation' => 'invalid',
		'direction' => 'invalid', 'speed' => 999, 'density' => 'invalid', 'date_format' => 'invalid',
		'time_format' => 'invalid', 'image_size' => '999', 'image_style' => 'invalid',
		'heading' => str_repeat('x', 200), 'separator' => str_repeat('x', 30), 'show_images' => '2',
		'template_show_match_detail_button' => 'invalid',
	]));
	$expected = [
		'sport_type_id' => 0, 'season_id' => 2, 'project_id' => 3, 'stage_id' => 4, 'round_id' => 5,
		'club_id' => 6, 'entry_id' => 7, 'venue_id' => 8, 'status_codes' => ['scheduled', 'finished'],
		'days_before' => 0, 'days_after' => 5000, 'completed_limit' => 100, 'upcoming_limit' => 0,
		'item_limit' => 1, 'participant_limit' => 100, 'content_mode' => 'both', 'orientation' => 'horizontal',
		'direction' => 'left', 'speed' => 300, 'density' => 'standard', 'date_format' => 'medium',
		'time_format' => 'short', 'image_size' => 32, 'image_style' => 'contain', 'show_images' => false,
		'detail_override' => '',
	];
	foreach ($expected as $name => $value) if (($config[$name] ?? null) !== $value) throw new \RuntimeException('Normalization failed for ' . $name);
	if (strlen($config['heading']) !== 150 || strlen($config['separator']) !== 20) throw new \RuntimeException('Text bounds failed.');

	$choices = ['content_mode' => ['both', 'completed', 'upcoming'], 'orientation' => ['horizontal', 'vertical'], 'direction' => ['left', 'right'], 'density' => ['compact', 'standard', 'detailed'], 'date_format' => ['short', 'medium', 'long'], 'time_format' => ['short', 'medium'], 'image_style' => ['contain', 'cover', 'circle'], 'detail_override' => ['', '0', '1']];
	foreach ($choices as $name => $values) foreach ($values as $value) {
		$input = $name === 'detail_override' ? 'template_show_match_detail_button' : $name;
		if (ProgrammeTickerConfiguration::fromRegistry(new Registry([$input => $value]))[$name] !== $value) throw new \RuntimeException("Valid $name option was not preserved.");
	}
	foreach (['24', '32', '40', '48'] as $size) if (ProgrammeTickerConfiguration::fromRegistry(new Registry(['image_size' => $size]))['image_size'] !== (int) $size) throw new \RuntimeException('Image size failed.');
	foreach (['pause_on_hover', 'loop', 'show_sport', 'show_season', 'show_project', 'show_stage', 'show_round', 'show_date', 'show_time', 'show_venue', 'show_status', 'show_contest_type', 'show_participants', 'show_images', 'show_result', 'show_decider', 'emphasize_winner', 'show_participant_links', 'show_project_link', 'show_venue_link', 'show_empty'] as $boolean) {
		if (ProgrammeTickerConfiguration::fromRegistry(new Registry([$boolean => '0']))[$boolean] !== false || ProgrammeTickerConfiguration::fromRegistry(new Registry([$boolean => '1']))[$boolean] !== true) throw new \RuntimeException('Boolean normalization failed for ' . $boolean);
	}
	echo "Programme Ticker module configuration OK\n";
}
