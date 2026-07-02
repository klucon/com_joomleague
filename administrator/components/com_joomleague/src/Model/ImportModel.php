<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

final class ImportModel extends BaseDatabaseModel
{
	public const TABLES = [
		'sportstypes' => ['table' => '#__joomleague_sports_type', 'label' => 'COM_JOOMLEAGUE_MENU_SPORTS_TYPES'],
		'leagues' => ['table' => '#__joomleague_league', 'label' => 'COM_JOOMLEAGUE_MENU_LEAGUES'],
		'seasons' => ['table' => '#__joomleague_season', 'label' => 'COM_JOOMLEAGUE_MENU_SEASONS'],
		'clubs' => ['table' => '#__joomleague_club', 'label' => 'COM_JOOMLEAGUE_MENU_CLUBS'],
		'teams' => ['table' => '#__joomleague_team', 'label' => 'COM_JOOMLEAGUE_MENU_TEAMS'],
		'persons' => ['table' => '#__joomleague_person', 'label' => 'COM_JOOMLEAGUE_MENU_PERSONS'],
		'eventtypes' => ['table' => '#__joomleague_eventtype', 'label' => 'COM_JOOMLEAGUE_MENU_EVENT_TYPES'],
		'statistics' => ['table' => '#__joomleague_statistic', 'label' => 'COM_JOOMLEAGUE_MENU_STATISTICS'],
		'positions' => ['table' => '#__joomleague_position', 'label' => 'COM_JOOMLEAGUE_MENU_POSITIONS'],
		'stadiums' => ['table' => '#__joomleague_playground', 'label' => 'COM_JOOMLEAGUE_MENU_STADIUMS'],
	];

	public function getTargets(): array
	{
		return self::TABLES;
	}

	public function getColumns(string $target): array
	{
		$table = self::TABLES[$target]['table'] ?? null;

		if ($table === null) {
			return [];
		}

		return array_keys($this->getDatabase()->getTableColumns($table) ?: []);
	}

	public function importCsv(string $target, string $file, string $delimiter, bool $replace): object
	{
		$table = self::TABLES[$target]['table'] ?? null;

		if ($table === null || !is_readable($file)) {
			return (object) ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => ['invalid-target']];
		}

		$delimiter = $delimiter !== '' ? mb_substr($delimiter, 0, 1) : ';';
		$handle = fopen($file, 'rb');

		if ($handle === false) {
			return (object) ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => ['unreadable-file']];
		}

		$db = $this->getDatabase();
		$allowed = $this->getColumns($target);
		$header = fgetcsv($handle, 0, $delimiter);
		$fields = [];

		foreach (($header ?: []) as $index => $name) {
			$name = preg_replace('/^\xEF\xBB\xBF/', '', trim((string) $name));

			if (in_array($name, $allowed, true)) {
				$fields[$index] = $name;
			}
		}

		$result = (object) ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

		if ($fields === []) {
			fclose($handle);
			$result->errors[] = 'no-valid-fields';

			return $result;
		}

		while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
			$object = new \stdClass();

			foreach ($fields as $index => $field) {
				$object->{$field} = $row[$index] ?? null;
			}

			try {
				if ($replace && isset($object->id) && (int) $object->id > 0) {
					$db->updateObject($table, $object, 'id');
					$result->updated++;
				} else {
					if (!$replace && isset($object->id)) {
						unset($object->id);
					}
					$db->insertObject($table, $object);
					$result->inserted++;
				}
			} catch (\Throwable $error) {
				$result->skipped++;
				$result->errors[] = $error->getMessage();
			}
		}

		fclose($handle);

		return $result;
	}
}
