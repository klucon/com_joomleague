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
	require_once dirname(__DIR__, 2) . '/modules/mod_joomleague_program/src/Configuration/ProgramConfiguration.php';

	use Joomla\Registry\Registry;
	use Joomleague\Module\Program\Site\Configuration\ProgramConfiguration;

	$defaults = ProgramConfiguration::fromRegistry(new Registry());
	$expectedDefaults = [
		'project_id' => 0, 'scope' => 'project', 'mode' => 'upcoming', 'limit' => 5,
		'sort_by' => 'date', 'sort_direction' => 'asc', 'orientation' => 'vertical',
		'density' => 'standard', 'group_by' => 'none', 'show_empty' => true,
	];
	foreach ($expectedDefaults as $name => $expected) {
		if (($defaults[$name] ?? null) !== $expected) throw new \RuntimeException('Invalid default for ' . $name);
	}

	$config = ProgramConfiguration::fromRegistry(new Registry([
		'project_id' => -4,
		'scope' => 'invalid',
		'mode' => 'invalid',
		'stage_id' => -1,
		'round_id' => '12',
		'venue_id' => '8',
		'status_codes' => ['scheduled', 'scheduled', 'bad code', 'started', str_repeat('a', 65)],
		'days_before' => -1,
		'days_after' => 999999,
		'limit' => 999,
		'participant_limit' => -5,
		'sort_by' => 'SQL',
		'sort_direction' => 'sideways',
		'orientation' => 'diagonal',
		'density' => 'huge',
		'group_by' => 'project',
		'image_size' => '999',
		'date_format' => 'raw',
		'heading' => str_repeat('x', 200),
		'show_images' => '2',
		'show_calendar' => '0',
		'template_show_match_detail_button' => 'yes',
	]));

	$expected = [
		'project_id' => 0, 'scope' => 'project', 'scope_id' => 0, 'mode' => 'upcoming',
		'stage_id' => 0, 'round_id' => 12, 'venue_id' => 8,
		'status_codes' => ['scheduled', 'started'], 'days_before' => 0, 'days_after' => 5000,
		'limit' => 100, 'participant_limit' => 0, 'sort_by' => 'date', 'sort_direction' => 'asc',
		'orientation' => 'vertical', 'density' => 'standard', 'group_by' => 'none',
		'image_size' => 40, 'date_format' => 'medium', 'show_images' => false,
		'show_calendar' => false, 'detail_override' => '',
	];
	foreach ($expected as $name => $value) {
		if (($config[$name] ?? null) !== $value) throw new \RuntimeException(sprintf('Normalization failed for %s.', $name));
	}
	if (strlen($config['heading']) !== 150) throw new \RuntimeException('Heading was not bounded.');

	$played = ProgramConfiguration::fromRegistry(new Registry(['mode' => 'played', 'sort_direction' => 'auto']));
	if ($played['sort_direction'] !== 'desc') throw new \RuntimeException('Played automatic ordering must be descending.');

	$entry = ProgramConfiguration::fromRegistry(new Registry(['scope' => 'entry', 'entry_id' => 17, 'club_id' => 19]));
	$club = ProgramConfiguration::fromRegistry(new Registry(['scope' => 'club', 'entry_id' => 17, 'club_id' => 19]));
	if ($entry['scope_id'] !== 17 || $club['scope_id'] !== 19) throw new \RuntimeException('Scope ID normalization failed.');

	$choices = [
		'scope' => ['project', 'entry', 'club'],
		'mode' => ['upcoming', 'played', 'all'],
		'sort_by' => ['date', 'round', 'venue', 'status'],
		'sort_direction' => ['asc', 'desc'],
		'orientation' => ['vertical', 'horizontal'],
		'density' => ['compact', 'standard', 'detailed'],
		'group_by' => ['none', 'date', 'round'],
		'date_format' => ['short', 'medium', 'long'],
	];
	foreach ($choices as $name => $values) {
		foreach ($values as $value) {
			$selected = ProgramConfiguration::fromRegistry(new Registry([$name => $value]));
			if ($selected[$name] !== $value) throw new \RuntimeException(sprintf('Valid %s option %s was not preserved.', $name, $value));
		}
	}
	foreach (['show_project_name', 'show_round', 'show_date', 'show_venue', 'show_result', 'show_status', 'show_contest_type', 'show_images', 'show_participant_links', 'show_venue_link', 'show_calendar', 'show_empty'] as $boolean) {
		if (ProgramConfiguration::fromRegistry(new Registry([$boolean => '0']))[$boolean] !== false
			|| ProgramConfiguration::fromRegistry(new Registry([$boolean => '1']))[$boolean] !== true) {
			throw new \RuntimeException('Boolean normalization failed for ' . $boolean);
		}
	}

	echo "Programme module configuration OK\n";
}
