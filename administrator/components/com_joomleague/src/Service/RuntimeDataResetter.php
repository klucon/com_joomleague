<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use RuntimeException;

final class RuntimeDataResetter
{
	public const CONFIRMATION = 'REMOVE AND CLEAN DEMO DATA';

	private const TABLES = [
		'#__joomleague_standing_freshness',
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
	}

	public function reset(): int
	{
		$this->assertSchemaComplete();

		if ($this->database->getName() === 'pgsql') {
			$tables = array_map(fn (string $table): string => $this->database->quoteName($table), self::TABLES);
			$this->database->setQuery('TRUNCATE TABLE ' . implode(', ', $tables) . ' RESTART IDENTITY CASCADE')->execute();

			return count(self::TABLES);
		}

		$this->database->setQuery('SET FOREIGN_KEY_CHECKS = 0')->execute();

		try {
			foreach (self::TABLES as $table) {
				$this->database->setQuery('TRUNCATE TABLE ' . $this->database->quoteName($table))->execute();
			}
		} finally {
			$this->database->setQuery('SET FOREIGN_KEY_CHECKS = 1')->execute();
		}

		return count(self::TABLES);
	}

	public function hasRuntimeData(): bool
	{
		foreach (['#__joomleague_project', '#__joomleague_sport_type', '#__joomleague_migration_batch'] as $table) {
			$count = (int) $this->database->setQuery(
				$this->database->getQuery(true)->select('COUNT(*)')->from($this->database->quoteName($table))
			)->loadResult();

			if ($count > 0) {
				return true;
			}
		}

		return false;
	}

	private function assertSchemaComplete(): void
	{
		$installed = $this->database->getTableList();

		foreach (self::TABLES as $table) {
			if (!in_array($this->database->replacePrefix($table), $installed, true)) {
				throw new RuntimeException(sprintf('Required table is missing: %s', $table));
			}
		}
	}
}
