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

final class ClubModel extends BaseDatabaseModel
{
	protected function populateState($ordering = null, $direction = null): void
	{
		$this->setState('club_id', Factory::getApplication()->getInput()->getInt('club_id', 0));
	}

	/** @return array<string,mixed> */
	public function getClub(): array
	{
		$clubId = (int) $this->getState('club_id');

		if ($clubId < 1) {
			return ['error' => 'COM_JOOMLEAGUE_CLUB_NOT_CONFIGURED'];
		}

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$club = $db->setQuery(
			$db->getQuery(true)
				->select([
					'id', 'name', 'short_name', 'country_code', 'website', 'logo',
					'founded_date', 'dissolved_date', 'description',
				])
				->from($db->quoteName('#__joomleague_club', 'club'))
				->where('id = :clubId')
				->where('club.published = 1')->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'club'))
				->bind(':clubId', $clubId, ParameterType::INTEGER)
		)->loadObject();

		if (!$club) {
			return ['error' => 'COM_JOOMLEAGUE_CLUB_UNAVAILABLE'];
		}

		$teams = $db->setQuery(
			$db->getQuery(true)
				->select(['id', 'name', 'middle_name', 'short_name', 'website', 'logo', 'picture', 'description'])
				->from($db->quoteName('#__joomleague_team', 'team'))
				->where('club_id = :clubId')
				->where('team.published = 1')->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'team'))
				->bind(':clubId', $clubId, ParameterType::INTEGER)
				->order('ordering ASC, name ASC, id ASC')
		)->loadObjectList();

		$teamsById = [];

		foreach ($teams as $team) {
			$team->projects = [];
			$teamsById[(int) $team->id] = $team;
		}

		if ($teamsById !== []) {
			$entries = $db->setQuery(
				$db->getQuery(true)
					->select([
						'entry.id', 'entry.team_id', $db->quoteName('project.id', 'project_id'),
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
					->whereIn('entry.team_id', array_keys($teamsById), ParameterType::INTEGER)
					->where('entry.entry_kind = ' . $db->quote('team'))
					->where('entry.published = 1')
					->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'project'))
					->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'competition'))
					->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'season'))
					->order('season.name DESC, project.name ASC, entry.id ASC')
			)->loadObjectList();

			foreach ($entries as $entry) {
				if (isset($teamsById[(int) $entry->team_id])) {
					$teamsById[(int) $entry->team_id]->projects[] = $entry;
				}
			}
		}

		return ['club' => $club, 'teams' => array_values($teamsById)];
	}
}
