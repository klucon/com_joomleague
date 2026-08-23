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

final class TeamModel extends BaseDatabaseModel
{
	protected function populateState($ordering = null, $direction = null): void
	{
		$this->setState('team_id', Factory::getApplication()->getInput()->getInt('team_id', 0));
	}

	/** @return array<string,mixed> */
	public function getTeam(): array
	{
		$teamId = (int) $this->getState('team_id');

		if ($teamId < 1) {
			return ['error' => 'COM_JOOMLEAGUE_TEAM_NOT_CONFIGURED'];
		}

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$team = $db->setQuery(
			$db->getQuery(true)
				->select([
					'team.id', 'team.name', 'team.middle_name', 'team.short_name', 'team.website',
					'team.logo', 'team.picture', 'team.description',
					$db->quoteName('club.id', 'club_id'), $db->quoteName('club.name', 'club_name'),
				])
				->from($db->quoteName('#__joomleague_team', 'team'))
				->leftJoin($db->quoteName('#__joomleague_club', 'club') . ' ON club.id = team.club_id AND club.published = 1')
				->where('team.id = :teamId')
				->where('team.published = 1')
				->bind(':teamId', $teamId, ParameterType::INTEGER)
		)->loadObject();

		if (!$team) {
			return ['error' => 'COM_JOOMLEAGUE_TEAM_UNAVAILABLE'];
		}

		$projects = $db->setQuery(
			$db->getQuery(true)
				->select([
					'entry.id', $db->quoteName('project.id', 'project_id'),
					$db->quoteName('project.name', 'project_name'),
					$db->quoteName('competition.name', 'competition_name'),
					$db->quoteName('season.name', 'season_name'),
					$db->quoteName('sport_type.name', 'sport_type_name'),
				])
				->from($db->quoteName('#__joomleague_project_entry', 'entry'))
				->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = entry.project_id AND project.published = 1')
				->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id AND competition.published = 1')
				->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id AND season.published = 1')
				->innerJoin($db->quoteName('#__joomleague_sport_type', 'sport_type') . ' ON sport_type.id = project.sport_type_id AND sport_type.published = 1')
				->where('entry.team_id = :teamId')
				->where('entry.entry_kind = ' . $db->quote('team'))
				->where('entry.published = 1')
				->bind(':teamId', $teamId, ParameterType::INTEGER)
				->order('season.name DESC, project.name ASC, entry.id ASC')
		)->loadObjectList();

		return ['team' => $team, 'projects' => $projects];
	}
}
