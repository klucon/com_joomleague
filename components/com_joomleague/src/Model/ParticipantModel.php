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

final class ParticipantModel extends BaseDatabaseModel
{
	protected function populateState($ordering = null, $direction = null): void
	{
		$input = Factory::getApplication()->getInput();
		$this->setState('project_id', $input->getInt('project_id', 0));
		$this->setState('entry_id', $input->getInt('entry_id', 0));
	}

	/** @return array<string,mixed> */
	public function getParticipant(): array
	{
		$projectId = (int) $this->getState('project_id');
		$entryId = (int) $this->getState('entry_id');

		if ($projectId < 1 || $entryId < 1) {
			return ['error' => 'COM_JOOMLEAGUE_PARTICIPANT_NOT_CONFIGURED'];
		}

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)
			->select([
				'entry.id', 'entry.entry_kind', 'entry.entry_code', 'entry.seed_number', 'entry.bib_number', 'entry.notes',
				$db->quoteName('project.id', 'project_id'), $db->quoteName('project.name', 'project_name'),
				$db->quoteName('sport_type.name', 'sport_type_name'),
				$db->quoteName('team.logo', 'team_logo'), $db->quoteName('team.picture', 'team_picture'),
				$db->quoteName('team.description', 'team_description'), $db->quoteName('team.website', 'team_website'),
				$db->quoteName('person.picture', 'person_picture'), $db->quoteName('person.description', 'person_description'),
				$db->quoteName('person.country_code', 'person_country_code'), $db->quoteName('club.id', 'club_id'), $db->quoteName('club.name', 'club_name'),
				'COALESCE(NULLIF(' . $db->quoteName('entry.display_name') . ", ''), "
					. $db->quoteName('team.name') . ', NULLIF(TRIM(CONCAT('
					. $db->quoteName('person.first_name') . ", ' ', " . $db->quoteName('person.last_name')
					. ")), ''), CONCAT('ID ', " . $db->quoteName('entry.id') . ')) AS ' . $db->quoteName('display_name'),
			])
			->from($db->quoteName('#__joomleague_project_entry', 'entry'))
			->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = entry.project_id AND project.published = 1')
			->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id AND competition.published = 1')
			->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id AND season.published = 1')
			->innerJoin($db->quoteName('#__joomleague_sport_type', 'sport_type') . ' ON sport_type.id = project.sport_type_id AND sport_type.published = 1')
			->leftJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id AND team.published = 1 AND ' . \Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'team'))
			->leftJoin($db->quoteName('#__joomleague_club', 'club') . ' ON club.id = team.club_id AND club.published = 1 AND ' . \Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'club'))
			->leftJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id AND person.published = 1 AND ' . \Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'person'))
			->where('entry.id = :entryId')
			->where('entry.project_id = :projectId')
			->where('entry.published = 1')
			->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'project'))
			->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'competition'))
			->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'season'))
			->where("(entry.entry_kind = 'group' OR (entry.entry_kind = 'team' AND team.id IS NOT NULL) OR (entry.entry_kind = 'person' AND person.id IS NOT NULL))")
			->bind(':entryId', $entryId, ParameterType::INTEGER)
			->bind(':projectId', $projectId, ParameterType::INTEGER);
		$participant = $db->setQuery($query)->loadObject();

		if (!$participant) {
			return ['error' => 'COM_JOOMLEAGUE_PARTICIPANT_UNAVAILABLE'];
		}

		$today = Factory::getDate()->format('Y-m-d');
		$memberQuery = $db->getQuery(true)
			->select([
				'member.id', 'member.member_person_type', 'member.shirt_number', 'member.is_captain',
				$db->quoteName('person.id', 'person_id'), 'person.first_name', 'person.last_name', 'person.nickname', 'person.picture',
			])
			->from($db->quoteName('#__joomleague_project_entry_member', 'member'))
			->innerJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = member.person_id AND person.published = 1 AND ' . \Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'person'))
			->where('member.entry_id = :entryId')
			->where('member.published = 1')
			->where('(member.valid_from IS NULL OR member.valid_from <= :todayFrom)')
			->where('(member.valid_until IS NULL OR member.valid_until >= :todayUntil)')
			->bind(':entryId', $entryId, ParameterType::INTEGER)
			->bind(':todayFrom', $today)
			->bind(':todayUntil', $today)
			->order('member.ordering ASC, person.last_name ASC, person.first_name ASC, member.id ASC');

		return ['participant' => $participant, 'members' => $db->setQuery($memberQuery)->loadObjectList()];
	}
}
