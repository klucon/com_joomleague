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
			->where('entry.project_id = project.id')
			->where('entry.published = 1');
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
			->order($db->quoteName('project.ordering') . ' ASC, ' . $db->quoteName('project.id') . ' DESC');

		return $db->setQuery($query)->loadObjectList();
	}
}
