<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\Database\DatabaseInterface;

final class SportsBootstrapService
{
	private const ALL_PROFILE = 'all';

	public function __construct(private readonly DatabaseInterface $database)
	{
	}

	public function create(string $profile, bool $createPositions, bool $createEvents, bool $createStatistics): array
	{
		if ($profile === self::ALL_PROFILE) {
			return $this->createAll($createPositions, $createEvents, $createStatistics);
		}

		$definition = $this->loadDefinition($profile);
		$created = [
			'sports' => 0,
			'positions' => 0,
			'events' => 0,
			'statistics' => 0,
			'position_events' => 0,
			'position_statistics' => 0,
		];

		$this->database->transactionStart();

		try {
			$sportId = $this->ensureSport($definition['sport'], $created);

			if ($createPositions) {
				$positionIds = $this->ensurePositions($sportId, $definition['positions'] ?? [], $created);
			} else {
				$positionIds = $this->getExistingPlayerPositionIds($sportId);
			}

			if ($createEvents) {
				$eventIds = $this->ensureEvents($sportId, $definition['events'] ?? [], $created);
			} else {
				$eventIds = $this->getExistingEventIds($sportId);
			}

			if ($createStatistics) {
				$statisticIds = $this->ensureStatistics($sportId, $definition['statistics'] ?? [], $created);
			} else {
				$statisticIds = $this->getExistingStatisticIds($sportId);
			}

			if ($positionIds !== [] && $eventIds !== []) {
				$created['position_events'] += $this->ensurePositionEventLinks($positionIds, $eventIds);
			}

			if ($positionIds !== [] && $statisticIds !== []) {
				$created['position_statistics'] += $this->ensurePositionStatisticLinks($positionIds, $statisticIds);
			}

			$this->database->transactionCommit();
		} catch (\Throwable $exception) {
			$this->database->transactionRollback();
			throw $exception;
		}

		return $created;
	}

	private function createAll(bool $createPositions, bool $createEvents, bool $createStatistics): array
	{
		$total = [
			'sports' => 0,
			'positions' => 0,
			'events' => 0,
			'statistics' => 0,
			'position_events' => 0,
			'position_statistics' => 0,
		];

		foreach ($this->getAvailableProfiles() as $profile) {
			$result = $this->create($profile, $createPositions, $createEvents, $createStatistics);

			foreach ($total as $key => $value) {
				$total[$key] += (int) ($result[$key] ?? 0);
			}
		}

		return $total;
	}

	private function getAvailableProfiles(): array
	{
		$componentPath = \defined('JPATH_COMPONENT_ADMINISTRATOR')
			? \JPATH_COMPONENT_ADMINISTRATOR
			: JPATH_ADMINISTRATOR . '/components/com_joomleague';
		$files = glob($componentPath . '/resources/sports/*.json') ?: [];
		$profiles = [];

		foreach ($files as $file) {
			$profile = basename($file, '.json');

			if ($profile !== self::ALL_PROFILE) {
				$profiles[] = $profile;
			}
		}

		sort($profiles, SORT_STRING);

		return $profiles;
	}

	private function loadDefinition(string $profile): array
	{
		$profile = preg_replace('/[^a-z0-9_-]/', '', strtolower($profile));
		$componentPath = \defined('JPATH_COMPONENT_ADMINISTRATOR')
			? \JPATH_COMPONENT_ADMINISTRATOR
			: JPATH_ADMINISTRATOR . '/components/com_joomleague';
		$file = $componentPath . '/resources/sports/' . $profile . '.json';

		if ($profile === '' || !is_file($file)) {
			throw new \InvalidArgumentException('COM_JOOMLEAGUE_SPORTS_BOOTSTRAP_ERROR_PROFILE_NOT_FOUND');
		}

		$json = (string) file_get_contents($file);

		if (!json_validate($json)) {
			throw new \RuntimeException('COM_JOOMLEAGUE_SPORTS_BOOTSTRAP_ERROR_INVALID_JSON');
		}

		$definition = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

		if (!is_array($definition) || empty($definition['sport']['name'])) {
			throw new \RuntimeException('COM_JOOMLEAGUE_SPORTS_BOOTSTRAP_ERROR_INVALID_DEFINITION');
		}

		return $definition;
	}

	private function ensureSport(array $sport, array &$created): int
	{
		$name = trim((string) $sport['name']);
		$id = $this->findId('#__joomleague_sports_type', ['name' => $name]);

		if ($id > 0) {
			return $id;
		}

		$row = (object) [
			'name' => $name,
			'icon' => (string) ($sport['icon'] ?? ''),
			'published' => 1,
			'ordering' => $this->nextOrdering('#__joomleague_sports_type'),
		];

		$this->database->insertObject('#__joomleague_sports_type', $row, 'id');
		$created['sports']++;

		return (int) $row->id;
	}

	private function ensurePositions(int $sportId, array $positions, array &$created): array
	{
		$ids = [];
		$ordering = 0;

		foreach ($positions as $position) {
			$name = trim((string) ($position['name'] ?? ''));
			$personType = (int) ($position['personType'] ?? 1);

			if ($name === '') {
				continue;
			}

			$id = $this->findId('#__joomleague_position', [
				'name' => $name,
				'persontype' => $personType,
				'sports_type_id' => $sportId,
			]);

			if ($id < 1) {
				$row = (object) [
					'name' => $name,
					'alias' => OutputFilter::stringURLSafe($name),
					'parent_id' => null,
					'persontype' => $personType,
					'sports_type_id' => $sportId,
					'published' => 1,
					'ordering' => ++$ordering,
				];
				$this->database->insertObject('#__joomleague_position', $row, 'id');
				$id = (int) $row->id;
				$created['positions']++;
			}

			if ($personType === 1) {
				$ids[] = $id;
			}
		}

		return $ids;
	}

	private function ensureEvents(int $sportId, array $events, array &$created): array
	{
		$ids = [];
		$ordering = 0;

		foreach ($events as $event) {
			$name = trim((string) ($event['name'] ?? ''));

			if ($name === '') {
				continue;
			}

			$id = $this->findId('#__joomleague_eventtype', [
				'name' => $name,
				'sports_type_id' => $sportId,
			]);

			if ($id < 1) {
				$row = (object) [
					'name' => $name,
					'alias' => OutputFilter::stringURLSafe($name),
					'icon' => (string) ($event['icon'] ?? ''),
					'parent' => null,
					'splitt' => 0,
					'direction' => 'DESC',
					'double' => 0,
					'suspension' => (int) ($event['suspension'] ?? 0),
					'sports_type_id' => $sportId,
					'published' => 1,
					'ordering' => ++$ordering,
				];
				$this->database->insertObject('#__joomleague_eventtype', $row, 'id');
				$id = (int) $row->id;
				$created['events']++;
			}

			$ids[] = $id;
		}

		return $ids;
	}

	private function ensureStatistics(int $sportId, array $statistics, array &$created): array
	{
		$ids = [];
		$ordering = 0;

		foreach ($statistics as $statistic) {
			$name = trim((string) ($statistic['name'] ?? ''));

			if ($name === '') {
				continue;
			}

			$id = $this->findId('#__joomleague_statistic', [
				'name' => $name,
				'sports_type_id' => $sportId,
			]);

			if ($id < 1) {
				$row = (object) [
					'name' => $name,
					'alias' => OutputFilter::stringURLSafe($name),
					'short' => (string) ($statistic['short'] ?? mb_substr($name, 0, 10)),
					'icon' => (string) ($statistic['icon'] ?? ''),
					'class' => 'generic',
					'calculated' => 0,
					'params' => '{}',
					'baseparams' => '{}',
					'note' => null,
					'sports_type_id' => $sportId,
					'published' => 1,
					'ordering' => ++$ordering,
				];
				$this->database->insertObject('#__joomleague_statistic', $row, 'id');
				$id = (int) $row->id;
				$created['statistics']++;
			}

			$ids[] = $id;
		}

		return $ids;
	}

	private function ensurePositionEventLinks(array $positionIds, array $eventIds): int
	{
		$created = 0;

		foreach ($positionIds as $positionId) {
			foreach ($eventIds as $eventId) {
				if ($this->findId('#__joomleague_position_eventtype', ['position_id' => $positionId, 'eventtype_id' => $eventId]) > 0) {
					continue;
				}

				$row = (object) [
					'position_id' => $positionId,
					'eventtype_id' => $eventId,
					'ordering' => 0,
				];
				$this->database->insertObject('#__joomleague_position_eventtype', $row);
				$created++;
			}
		}

		return $created;
	}

	private function ensurePositionStatisticLinks(array $positionIds, array $statisticIds): int
	{
		$created = 0;

		foreach ($positionIds as $positionId) {
			foreach ($statisticIds as $statisticId) {
				if ($this->findId('#__joomleague_position_statistic', ['position_id' => $positionId, 'statistic_id' => $statisticId]) > 0) {
					continue;
				}

				$row = (object) [
					'position_id' => $positionId,
					'statistic_id' => $statisticId,
					'ordering' => 0,
				];
				$this->database->insertObject('#__joomleague_position_statistic', $row);
				$created++;
			}
		}

		return $created;
	}

	private function getExistingPlayerPositionIds(int $sportId): array
	{
		return array_map('intval', (array) $this->database->setQuery(
			'SELECT id FROM #__joomleague_position WHERE sports_type_id=' . $sportId . ' AND persontype=1'
		)->loadColumn());
	}

	private function getExistingEventIds(int $sportId): array
	{
		return array_map('intval', (array) $this->database->setQuery(
			'SELECT id FROM #__joomleague_eventtype WHERE sports_type_id=' . $sportId
		)->loadColumn());
	}

	private function getExistingStatisticIds(int $sportId): array
	{
		return array_map('intval', (array) $this->database->setQuery(
			'SELECT id FROM #__joomleague_statistic WHERE sports_type_id=' . $sportId
		)->loadColumn());
	}

	private function findId(string $table, array $conditions): int
	{
		$query = $this->database->createQuery()
			->select($this->database->quoteName('id'))
			->from($this->database->quoteName($table));

		foreach ($conditions as $column => $value) {
			if ($value === null) {
				$query->where($this->database->quoteName($column) . ' IS NULL');
				continue;
			}

			$query->where($this->database->quoteName($column) . ' = ' . $this->database->quote($value));
		}

		return (int) $this->database->setQuery($query, 0, 1)->loadResult();
	}

	private function nextOrdering(string $table): int
	{
		return (int) $this->database->setQuery(
			'SELECT COALESCE(MAX(ordering), 0) + 1 FROM ' . $this->database->quoteName($table)
		)->loadResult();
	}
}
