<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Asset;
use Joomla\Component\Finder\Administrator\Indexer\Indexer;
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
		'#__joomleague_match_actor_role_change',
		'#__joomleague_match_actor_role',
		'#__joomleague_project_actor_role',
		'#__joomleague_match_lineup_change',
		'#__joomleague_match_duel_member',
		'#__joomleague_match_duel',
		'#__joomleague_match_encounter_revision',
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
		'#__joomleague_project_profile_migration',
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
		Factory::getApplication()->getLanguage()->load('com_joomleague', JPATH_ADMINISTRATOR);
		$this->assertNoExternalReferences();
		$this->removeSearchIndexEntries();
		$this->removeProjectAssets();

		if ($this->database->getName() === 'pgsql') {
			$tables = array_map(fn (string $table): string => $this->database->quoteName($table), self::TABLES);
			$this->database->setQuery('TRUNCATE TABLE ' . implode(', ', $tables) . ' RESTART IDENTITY')->execute();

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

	private function assertNoExternalReferences(): void
	{
		if ($this->database->getName() === 'pgsql') {
			$sql = "SELECT source.relname AS source_table, source_ns.nspname AS source_schema, target.relname AS target_table, target_ns.nspname AS target_schema"
				. " FROM pg_constraint AS relation"
				. " INNER JOIN pg_class AS source ON source.oid = relation.conrelid"
				. " INNER JOIN pg_namespace AS source_ns ON source_ns.oid = source.relnamespace"
				. " INNER JOIN pg_class AS target ON target.oid = relation.confrelid"
				. " INNER JOIN pg_namespace AS target_ns ON target_ns.oid = target.relnamespace"
				. " WHERE relation.contype = 'f' AND target_ns.nspname = current_schema()";
		} else {
			$sql = 'SELECT TABLE_NAME AS source_table, TABLE_SCHEMA AS source_schema, REFERENCED_TABLE_NAME AS target_table, REFERENCED_TABLE_SCHEMA AS target_schema'
				. ' FROM information_schema.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = DATABASE()';
		}

		$runtimeTables = array_map($this->database->replacePrefix(...), self::TABLES);

		foreach ($this->database->setQuery($sql)->loadAssocList() as $reference) {
			if (in_array($reference['target_table'], $runtimeTables, true)
				&& ($reference['source_schema'] !== $reference['target_schema'] || !in_array($reference['source_table'], $runtimeTables, true))) {
				throw new RuntimeException(Factory::getApplication()->getLanguage()->_('COM_JOOMLEAGUE_DEMODATA_ERROR_EXTERNAL_REFERENCE'));
			}
		}
	}

	private function removeSearchIndexEntries(): void
	{
		if (!in_array($this->database->replacePrefix('#__finder_links'), $this->database->getTableList(), true)) {
			return;
		}

		Factory::getApplication()->bootComponent('com_finder');
		$query = $this->database->getQuery(true)
			->select($this->database->quoteName(['link_id', 'url']))
			->from($this->database->quoteName('#__finder_links'))
			->where($this->database->quoteName('url') . ' LIKE ' . $this->database->quote('%com_joomleague%'));
		$links = $this->database->setQuery($query)->loadObjectList();
		$indexer = new Indexer($this->database);

		foreach ($links as $link) {
			parse_str((string) parse_url((string) $link->url, PHP_URL_QUERY), $parameters);

			if (($parameters['option'] ?? null) === 'com_joomleague') {
				// Keep shared taxonomy nodes; the native indexer removes this link's mappings.
				$indexer->remove((int) $link->link_id, false);
			}
		}
	}

	private function removeProjectAssets(): void
	{
		$query = $this->database->getQuery(true)
			->select($this->database->quoteName(['id', 'name']))
			->from($this->database->quoteName('#__assets'))
			->where($this->database->quoteName('name') . ' LIKE ' . $this->database->quote('com_joomleague.project.%'))
			->order($this->database->quoteName('lft') . ' DESC');
		$assets = $this->database->setQuery($query)->loadObjectList();

		foreach ($assets as $row) {
			if (preg_match('/^com_joomleague\\.project\\.[1-9][0-9]*$/D', (string) $row->name) !== 1) {
				continue;
			}

			$asset = new Asset($this->database);

			// Do not recursively delete assets owned by other extensions.
			if (!$asset->delete((int) $row->id, false)) {
				throw new RuntimeException(Factory::getApplication()->getLanguage()->_('COM_JOOMLEAGUE_DEMODATA_ERROR_ASSET_REMOVE'));
			}
		}

		Access::clearStatics();
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
