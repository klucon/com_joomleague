<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondrej Klucka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Component\Joomleague\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class ProjectModel extends BaseDatabaseModel
{
	protected function populateState($ordering = null, $direction = null): void
	{
		$this->setState('project_id', Factory::getApplication()->getInput()->getInt('project_id', 0));
	}

	/** @return array<string,mixed> */
	public function getProject(): array
	{
		$projectId = (int) $this->getState('project_id');

		if ($projectId < 1) {
			return ['error' => 'COM_JOOMLEAGUE_PROJECT_NO_PROJECT'];
		}

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$project = $db->setQuery(
			$db->getQuery(true)
				->select([
					'project.id', 'project.name', 'project.description', 'project.picture', 'project.project_type',
					'project.start_date', 'project.end_date',
					$db->quoteName('competition.name', 'competition_name'),
					$db->quoteName('season.name', 'season_name'),
					$db->quoteName('sport_type.name', 'sport_type_name'),
				])
				->from($db->quoteName('#__joomleague_project', 'project'))
				->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id AND competition.published = 1')
				->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id AND season.published = 1')
				->innerJoin($db->quoteName('#__joomleague_sport_type', 'sport_type') . ' ON sport_type.id = project.sport_type_id AND sport_type.published = 1')
				->where('project.id = :projectId')
				->where('project.published = 1')
				->bind(':projectId', $projectId, ParameterType::INTEGER)
		)->loadObject();

		if (!$project) {
			return ['error' => 'COM_JOOMLEAGUE_PROJECT_UNAVAILABLE'];
		}

		$entryCountQuery = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__joomleague_project_entry', 'entry'))
			->leftJoin($db->quoteName('#__joomleague_team', 'entry_team') . ' ON entry_team.id = entry.team_id AND entry_team.published = 1')
			->leftJoin($db->quoteName('#__joomleague_person', 'entry_person') . ' ON entry_person.id = entry.person_id AND entry_person.published = 1')
			->where('entry.project_id = :projectId')
			->where('entry.published = 1')
			->where("(entry.entry_kind = 'group' OR (entry.entry_kind = 'team' AND entry_team.id IS NOT NULL) OR (entry.entry_kind = 'person' AND entry_person.id IS NOT NULL))")
			->bind(':projectId', $projectId, ParameterType::INTEGER);
		$counts = ['entries' => (int) $db->setQuery($entryCountQuery)->loadResult()];
		foreach ([
			'stages' => ['#__joomleague_project_stage', 'project_id', true],
			'rounds' => ['#__joomleague_project_round', 'project_id', true],
			'program' => ['#__joomleague_project_match', 'project_id', true],
		] as $key => [$table, $column, $published]) {
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->quoteName($table))
				->where($db->quoteName($column) . ' = :projectId')
				->bind(':projectId', $projectId, ParameterType::INTEGER);
			if ($published) {
				$query->where($db->quoteName('published') . ' = 1');
			}
			$counts[$key] = (int) $db->setQuery($query)->loadResult();
		}

		return ['project' => $project, 'counts' => $counts];
	}
}
