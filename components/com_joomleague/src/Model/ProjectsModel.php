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

final class ProjectsModel extends BaseDatabaseModel
{
	/** @return list<object> */
	public function getProjects(): array
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$entryCount = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__joomleague_project_entry', 'entry'))
			->leftJoin($db->quoteName('#__joomleague_team', 'entry_team') . ' ON entry_team.id = entry.team_id AND entry_team.published = 1 AND ' . \Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'entry_team'))
			->leftJoin($db->quoteName('#__joomleague_person', 'entry_person') . ' ON entry_person.id = entry.person_id AND entry_person.published = 1 AND ' . \Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'entry_person'))
			->where('entry.project_id = project.id')
			->where('entry.published = 1')
			->where("(entry.entry_kind = 'group' OR (entry.entry_kind = 'team' AND entry_team.id IS NOT NULL) OR (entry.entry_kind = 'person' AND entry_person.id IS NOT NULL))");
		$matchCount = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__joomleague_project_match', 'match_item'))
			->where('match_item.project_id = project.id')
			->where('match_item.published = 1');

		$query = $db->getQuery(true)
			->select([
				'project.id', 'project.name', 'project.alias', 'project.description', 'project.picture',
				'project.start_date', 'project.end_date', 'project.project_type',
				$db->quoteName('competition.name', 'competition_name'),
				$db->quoteName('season.name', 'season_name'),
				$db->quoteName('sport_type.name', 'sport_type_name'),
				'(' . $entryCount . ') AS entry_count',
				'(' . $matchCount . ') AS match_count',
			])
			->from($db->quoteName('#__joomleague_project', 'project'))
			->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id AND competition.published = 1')
			->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id AND season.published = 1')
			->innerJoin($db->quoteName('#__joomleague_sport_type', 'sport_type') . ' ON sport_type.id = project.sport_type_id AND sport_type.published = 1')
			->where('project.published = 1')
			->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'project'))
			->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'competition'))
			->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'season'))
			->order($db->quoteName('project.ordering') . ' ASC, ' . $db->quoteName('project.id') . ' DESC');

		return $db->setQuery($query)->loadObjectList();
	}
}
