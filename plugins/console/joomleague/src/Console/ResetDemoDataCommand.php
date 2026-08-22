<?php

declare(strict_types=1);

namespace Joomleague\Plugin\Console\Joomleague\Console;

defined('_JEXEC') or die;

use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ResetDemoDataCommand extends AbstractCommand
{
	protected static $defaultName = 'joomleague:reset-demo-data';

	private const TABLES = [
		'#__joomleague_standing_current',
		'#__joomleague_standing_snapshot_row',
		'#__joomleague_standing_snapshot',
		'#__joomleague_standing_adjustment',
		'#__joomleague_match_statistic_value',
		'#__joomleague_match_event',
		'#__joomleague_match_actor_role',
		'#__joomleague_project_actor_role',
		'#__joomleague_match_lineup_change',
		'#__joomleague_match_lineup_member',
		'#__joomleague_match_score_value',
		'#__joomleague_match_score_segment',
		'#__joomleague_match_result',
		'#__joomleague_schedule_generation_match',
		'#__joomleague_schedule_generation',
		'#__joomleague_match_participant',
		'#__joomleague_project_match',
		'#__joomleague_stage_entry',
		'#__joomleague_stage_transition_assignment',
		'#__joomleague_stage_transition_run',
		'#__joomleague_stage_transition',
		'#__joomleague_project_round',
		'#__joomleague_position_event_type',
		'#__joomleague_position_statistic',
		'#__joomleague_project_entry_member',
		'#__joomleague_migration_issue',
		'#__joomleague_migration_record',
		'#__joomleague_project_entry',
		'#__joomleague_project_rule_config',
		'#__joomleague_project_template_config',
		'#__joomleague_project_stage',
		'#__joomleague_profile_template_config',
		'#__joomleague_event_type',
		'#__joomleague_statistic',
		'#__joomleague_sport_position',
		'#__joomleague_project',
		'#__joomleague_organization_media_history',
		'#__joomleague_organization_name_history',
		'#__joomleague_team',
		'#__joomleague_person',
		'#__joomleague_venue',
		'#__joomleague_club',
		'#__joomleague_competition',
		'#__joomleague_season',
		'#__joomleague_sport_type',
		'#__joomleague_migration_batch',
	];

	public function __construct(private readonly DatabaseInterface $database)
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->setDescription('Delete JoomLeague demo/runtime data while preserving bundled sport profiles and component settings.');
		$this->addOption('force', null, InputOption::VALUE_NONE, 'Confirm the destructive reset.');
	}

	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$io = new SymfonyStyle($input, $output);
		$io->title('JoomLeague demo data reset');

		if (getenv('JOOMLEAGUE_ALLOW_DEMO_RESET') !== '1') {
			$io->error('Reset is disabled. Set JOOMLEAGUE_ALLOW_DEMO_RESET=1 for this CLI process.');

			return Command::FAILURE;
		}

		if (!$input->getOption('force')) {
			$io->error('The --force option is required.');

			return Command::FAILURE;
		}

		$installedTables = $this->database->getTableList();

		foreach (self::TABLES as $table) {
			if (!in_array($this->database->replacePrefix($table), $installedTables, true)) {
				$io->error(sprintf('Required table is missing: %s', $table));

				return Command::FAILURE;
			}
		}

		try {
			$this->truncateTables();
		} catch (\Throwable $exception) {
			$io->error($exception->getMessage());

			return Command::FAILURE;
		}

		$io->success(sprintf(
			'%d runtime tables reset. Bundled sport profiles and Joomla/component settings were preserved.',
			count(self::TABLES),
		));

		return Command::SUCCESS;
	}

	private function truncateTables(): void
	{
		if ($this->database->getName() === 'pgsql') {
			$tables = array_map(fn (string $table): string => $this->database->quoteName($table), self::TABLES);
			$this->database->setQuery('TRUNCATE TABLE ' . implode(', ', $tables) . ' RESTART IDENTITY CASCADE')->execute();

			return;
		}

		$this->database->setQuery('SET FOREIGN_KEY_CHECKS = 0')->execute();

		try {
			foreach (self::TABLES as $table) {
				$this->database->setQuery('TRUNCATE TABLE ' . $this->database->quoteName($table))->execute();
			}
		} finally {
			$this->database->setQuery('SET FOREIGN_KEY_CHECKS = 1')->execute();
		}
	}
}
