<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Danger-zone tool: wipes all competition data so an installation can be
 * reset to a blank slate. Two table groups are involved:
 *  - USER_TABLES:      the actual competition history (leagues, clubs, teams,
 *                       matches, rosters, brackets, races, predictions...).
 *  - REFERENCE_TABLES:  shared taxonomy (sport types, positions, event types,
 *                       statistics) that CAN be recreated afterwards via the
 *                       Sports Bootstrap tool, so wiping it is opt-in.
 * `#__joomleague_version` (the installed component's own version marker) and
 * `#__joomleague_country` (the static ISO country/flag lookup) are never
 * touched by this tool -- they are not truncatable through any code path here.
 */
final class SqltruncateModel extends BaseDatabaseModel
{
	public const USER_TABLES = [
		'league', 'season', 'club', 'team', 'person', 'playground',
		'project', 'division', 'round', 'template_config',
		'match', 'match_event', 'match_player', 'match_referee', 'match_staff', 'match_staff_statistic', 'match_statistic',
		'project_team', 'project_referee', 'project_position', 'team_player', 'team_staff', 'team_trainingdata',
		'treeto', 'treeto_node', 'treeto_match',
		'race_category', 'race_participant', 'race_result',
		'prediction_game', 'prediction_score', 'prediction_tip',
	];

	public const REFERENCE_TABLES = [
		'sports_type', 'position', 'eventtype', 'statistic', 'position_eventtype', 'position_statistic',
	];

	/** @return array{user: array<string,int>, reference: array<string,int>} */
	public function getCounts(): array
	{
		return [
			'user' => $this->countTables(self::USER_TABLES),
			'reference' => $this->countTables(self::REFERENCE_TABLES),
		];
	}

	/** @return array<int,string> the short table names that were truncated */
	public function truncate(bool $includeReference): array
	{
		$tables = $includeReference ? array_merge(self::USER_TABLES, self::REFERENCE_TABLES) : self::USER_TABLES;
		$db = $this->getDatabase();

		$db->setQuery('SET FOREIGN_KEY_CHECKS = 0')->execute();

		try {
			foreach ($tables as $short) {
				$db->setQuery('TRUNCATE TABLE ' . $db->quoteName('#__joomleague_' . $short))->execute();
			}
		} finally {
			$db->setQuery('SET FOREIGN_KEY_CHECKS = 1')->execute();
		}

		return $tables;
	}

	/** @param array<int,string> $shortNames @return array<string,int> */
	private function countTables(array $shortNames): array
	{
		$db = $this->getDatabase();
		$counts = [];

		foreach ($shortNames as $short) {
			$query = $db->createQuery()->select('COUNT(*)')->from($db->quoteName('#__joomleague_' . $short));
			$counts[$short] = (int) $db->setQuery($query)->loadResult();
		}

		return $counts;
	}
}
