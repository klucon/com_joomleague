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

final class ParticipantsModel extends BaseDatabaseModel
{
	protected function populateState($ordering = null, $direction = null): void
	{
		$this->setState('project_id', Factory::getApplication()->getInput()->getInt('project_id', 0));
	}

	/** @return array<string,mixed> */
	public function getParticipants(): array
	{
		$projectId = (int) $this->getState('project_id');

		if ($projectId < 1) {
			return ['error' => 'COM_JOOMLEAGUE_PARTICIPANTS_NO_PROJECT'];
		}

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$project = $db->setQuery(
			$db->getQuery(true)
				->select(['project.id', 'project.name', $db->quoteName('sport_type.name', 'sport_type_name')])
				->from($db->quoteName('#__joomleague_project', 'project'))
				->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id AND competition.published = 1')
				->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id AND season.published = 1')
				->innerJoin($db->quoteName('#__joomleague_sport_type', 'sport_type') . ' ON sport_type.id = project.sport_type_id AND sport_type.published = 1')
				->where('project.id = :projectId')
				->where('project.published = 1')
				->bind(':projectId', $projectId, ParameterType::INTEGER)
		)->loadObject();

		if (!$project) {
			return ['error' => 'COM_JOOMLEAGUE_PARTICIPANTS_UNAVAILABLE'];
		}

		$memberCount = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__joomleague_project_entry_member', 'member'))
			->innerJoin($db->quoteName('#__joomleague_person', 'member_person') . ' ON member_person.id = member.person_id AND member_person.published = 1')
			->where('member.entry_id = entry.id')
			->where('member.published = 1');

		$query = $db->getQuery(true)
			->select([
				'entry.id', 'entry.entry_kind', 'entry.entry_code', 'entry.seed_number', 'entry.bib_number',
				$db->quoteName('team.logo', 'team_logo'), $db->quoteName('team.picture', 'team_picture'),
				$db->quoteName('person.picture', 'person_picture'), $db->quoteName('club.name', 'club_name'),
				'COALESCE(NULLIF(' . $db->quoteName('entry.display_name') . ", ''), "
					. $db->quoteName('team.name') . ', NULLIF(TRIM(CONCAT('
					. $db->quoteName('person.first_name') . ", ' ', " . $db->quoteName('person.last_name')
					. ")), ''), CONCAT('ID ', " . $db->quoteName('entry.id') . ')) AS ' . $db->quoteName('display_name'),
				'(' . $memberCount . ') AS member_count',
			])
			->from($db->quoteName('#__joomleague_project_entry', 'entry'))
			->leftJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id AND team.published = 1')
			->leftJoin($db->quoteName('#__joomleague_club', 'club') . ' ON club.id = team.club_id AND club.published = 1')
			->leftJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id AND person.published = 1')
			->where('entry.project_id = :projectId')
			->where('entry.published = 1')
			->where("(entry.entry_kind = 'group' OR (entry.entry_kind = 'team' AND team.id IS NOT NULL) OR (entry.entry_kind = 'person' AND person.id IS NOT NULL))")
			->bind(':projectId', $projectId, ParameterType::INTEGER)
			->order('entry.ordering ASC, CASE WHEN entry.seed_number IS NULL THEN 1 ELSE 0 END ASC, entry.seed_number ASC, entry.id ASC');

		return ['project' => $project, 'participants' => $db->setQuery($query)->loadObjectList()];
	}
}
