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

final class PersonModel extends BaseDatabaseModel
{
	protected function populateState($ordering = null, $direction = null): void
	{
		$this->setState('person_id', Factory::getApplication()->getInput()->getInt('person_id', 0));
	}

	/** @return array<string,mixed> */
	public function getPerson(): array
	{
		$personId = (int) $this->getState('person_id');

		if ($personId < 1) {
			return ['error' => 'COM_JOOMLEAGUE_PERSON_NOT_CONFIGURED'];
		}

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$person = $db->setQuery(
			$db->getQuery(true)
				->select([
					'person.id', 'person.first_name', 'person.last_name', 'person.nickname', 'person.country_code',
					'person.picture', 'person.description', $db->quoteName('club.id', 'club_id'), $db->quoteName('club.name', 'club_name'),
				])
				->from($db->quoteName('#__joomleague_person', 'person'))
				->leftJoin($db->quoteName('#__joomleague_club', 'club') . ' ON club.id = person.club_id AND club.published = 1 AND ' . \Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'club'))
				->where('person.id = :personId')
				->where('person.published = 1')
				->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'person'))
				->bind(':personId', $personId, ParameterType::INTEGER)
		)->loadObject();

		if (!$person) {
			return ['error' => 'COM_JOOMLEAGUE_PERSON_UNAVAILABLE'];
		}

		$today = Factory::getDate()->format('Y-m-d');
		$memberships = $db->setQuery(
			$db->getQuery(true)
				->select([
					'member.member_person_type', 'member.role_code', 'member.shirt_number', 'member.is_captain',
					'member.valid_from', 'member.valid_until', 'member.lifecycle_state',
					$db->quoteName('entry.id', 'entry_id'), $db->quoteName('project.id', 'project_id'),
					$db->quoteName('project.name', 'project_name'), $db->quoteName('sport_type.name', 'sport_type_name'),
					$db->quoteName('position.name', 'role_name'), $db->quoteName('position.name_key', 'role_name_key'),
					'COALESCE(NULLIF(' . $db->quoteName('entry.display_name') . ", ''), "
						. $db->quoteName('team.name') . ', NULLIF(TRIM(CONCAT('
						. $db->quoteName('entry_person.first_name') . ", ' ', " . $db->quoteName('entry_person.last_name')
						. ")), ''), CONCAT('ID ', " . $db->quoteName('entry.id') . ')) AS ' . $db->quoteName('entry_name'),
				])
				->from($db->quoteName('#__joomleague_project_entry_member', 'member'))
				->innerJoin($db->quoteName('#__joomleague_project_entry', 'entry') . ' ON entry.id = member.entry_id AND entry.published = 1')
				->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = entry.project_id AND project.published = 1')
				->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id AND competition.published = 1')
				->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id AND season.published = 1')
				->innerJoin($db->quoteName('#__joomleague_sport_type', 'sport_type') . ' ON sport_type.id = project.sport_type_id AND sport_type.published = 1')
				->leftJoin($db->quoteName('#__joomleague_sport_position', 'position') . ' ON position.sport_type_id = project.sport_type_id AND position.code = member.role_code AND position.published = 1')
				->leftJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id AND team.published = 1')
				->leftJoin($db->quoteName('#__joomleague_person', 'entry_person') . ' ON entry_person.id = entry.person_id AND entry_person.published = 1')
				->where('member.person_id = :personId')
				->where('member.published = 1')
				->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'project'))
				->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'competition'))
				->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'season'))
				->where("(entry.entry_kind = 'group' OR (entry.entry_kind = 'team' AND team.id IS NOT NULL) OR (entry.entry_kind = 'person' AND entry_person.id IS NOT NULL))")
				->bind(':personId', $personId, ParameterType::INTEGER)
				->order('member.valid_from DESC, project.name ASC, entry.ordering ASC, entry.id ASC')
		)->loadObjectList();

		$currentMemberships = [];
		$membershipHistory = [];

		foreach ($memberships as $membership) {
			$isCurrent = ($membership->valid_from === null || $membership->valid_from <= $today)
				&& ($membership->valid_until === null || $membership->valid_until >= $today)
				&& (string) $membership->lifecycle_state !== 'departed';

			if ($isCurrent) {
				$currentMemberships[] = $membership;
			} else {
				$membershipHistory[] = $membership;
			}
		}

		return ['person' => $person, 'memberships' => $currentMemberships, 'membership_history' => $membershipHistory];
	}
}
