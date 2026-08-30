<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\ProgrammeReader;
use Joomleague\Component\Joomleague\Domain\Service\ProgrammeScopeResolver;
use Joomleague\Component\Joomleague\Site\Service\PublicAccess;

/** Read-only programme for all project entries belonging to one club. */
final class ClubplanModel extends BaseDatabaseModel
{
	protected function populateState($ordering = null, $direction = null): void
	{
		$input = Factory::getApplication()->getInput();
		$this->setState('project_id', $input->getInt('project_id', 0));
		$this->setState('club_id', $input->getInt('club_id', 0));
		$this->setState('entry_id', $input->getInt('entry_id', 0));
		$this->setState('period', $input->getCmd('period', ''));
	}

	/** @return array<string,mixed> */
	public function getPlan(): array
	{
		$projectId = (int) $this->getState('project_id');
		$clubId = (int) $this->getState('club_id');
		if ($projectId < 1) {
			return ['error' => 'COM_JOOMLEAGUE_CLUBPLAN_NO_PROJECT'];
		}
		if ($clubId < 1) {
			return ['error' => 'COM_JOOMLEAGUE_CLUBPLAN_NO_CLUB'];
		}

		$app = Factory::getApplication();
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		if (!PublicAccess::projectAllowed($db, $projectId)) {
			return ['error' => 'COM_JOOMLEAGUE_CLUBPLAN_UNAVAILABLE'];
		}

		$viewLevels = $app->getIdentity()->getAuthorisedViewLevels();
		$viewLevels = array_values(array_unique(array_filter(array_map('intval', $viewLevels), static fn (int $id): bool => $id > 0)));
		$viewLevels = $viewLevels === [] ? [1] : $viewLevels;
		$club = $db->setQuery(
			$db->getQuery(true)
				->select(['club.id', 'club.name'])
				->from($db->quoteName('#__joomleague_club', 'club'))
				->where('club.id = :clubId')
				->where('club.published = 1')
				->where(PublicAccess::condition($db, 'club'))
				->bind(':clubId', $clubId, ParameterType::INTEGER)
		)->loadObject();
		if (!$club) {
			return ['error' => 'COM_JOOMLEAGUE_CLUBPLAN_UNAVAILABLE'];
		}

		$entryIds = (new ProgrammeScopeResolver($db))->resolve($projectId, 'club', $clubId, $viewLevels) ?? [];
		$entries = $this->loadEntries($db, $projectId, $entryIds, $viewLevels);
		$requestedEntryId = (int) $this->getState('entry_id');
		$selectedEntryId = in_array($requestedEntryId, $entryIds, true) ? $requestedEntryId : 0;
		$events = (new ProgrammeReader($db))->forProject($projectId, $selectedEntryId > 0 ? [$selectedEntryId] : $entryIds, $viewLevels);

		$params = $app->getParams();
		$period = (string) $this->getState('period');
		if (!in_array($period, ['all', 'upcoming', 'played'], true)) {
			$period = (string) $params->get('scope', 'all');
		}
		if (!in_array($period, ['all', 'upcoming', 'played'], true)) {
			$period = 'all';
		}
		$now = Factory::getDate()->toSql();
		$events = array_values(array_filter($events, static function (array $event) use ($period, $now): bool {
			if ($period === 'played') {
				return (bool) $event['played'];
			}
			if ($period === 'upcoming') {
				return !$event['played'] && $event['scheduled_start'] !== null && $event['scheduled_start'] >= $now;
			}
			return true;
		}));

		$limit = max(0, (int) $params->get('limit', 0));
		if ($limit > 0) {
			$events = array_slice($events, 0, $limit);
		}
		$projectName = $events[0]['project_name'] ?? (string) $db->setQuery(
			$db->getQuery(true)->select('name')->from($db->quoteName('#__joomleague_project'))->where('id = :id')->bind(':id', $projectId, ParameterType::INTEGER)
		)->loadResult();

		return [
			'project_id' => $projectId,
			'project_name' => $projectName,
			'club' => $club,
			'entries' => $entries,
			'events' => $events,
			'selected_entry_id' => $selectedEntryId,
			'period' => $period,
			'show_round' => (int) $params->get('show_round', 1) === 1,
			'show_venue' => (int) $params->get('show_venue', 1) === 1,
			'show_calendar' => (int) $params->get('show_calendar', 1) === 1,
		];
	}

	/** @param list<int> $entryIds @param list<int> $viewLevels @return list<object> */
	private function loadEntries(DatabaseInterface $db, int $projectId, array $entryIds, array $viewLevels): array
	{
		if ($entryIds === []) {
			return [];
		}

		return $db->setQuery(
			$db->getQuery(true)
				->select(['entry.id', "COALESCE(NULLIF(entry.display_name, ''), team.name, CONCAT('ID ', entry.id)) AS display_name"])
				->from($db->quoteName('#__joomleague_project_entry', 'entry'))
				->innerJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id AND team.published = 1 AND team.access IN (' . implode(',', array_map('intval', $viewLevels)) . ')')
				->where('entry.project_id = :projectId')
				->whereIn('entry.id', $entryIds, ParameterType::INTEGER)
				->bind(':projectId', $projectId, ParameterType::INTEGER)
				->order('display_name ASC, entry.id ASC')
		)->loadObjectList();
	}
}
