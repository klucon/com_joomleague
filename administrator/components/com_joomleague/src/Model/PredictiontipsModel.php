<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

final class PredictiontipsModel extends EntityListModel
{
	protected array $searchColumns = ['u.name', 'g.name', 'th.name', 'ta.name'];

	public function __construct($config = [], $factory = null)
	{
		$config['filter_fields'] = ['id', 'a.id', 'user_name', 'u.name', 'game_name', 'g.name', 'match_date', 'm.match_date', 'points', 'a.points'];
		parent::__construct($config, $factory);
	}

	protected function populateState($ordering = 'm.match_date', $direction = 'desc'): void
	{
		$app = Factory::getApplication();
		$gameId = $app->getInput()->getInt('game_id');

		if ($gameId > 0) {
			$app->setUserState('com_joomleague.predictiontips.game_id', $gameId);
		}

		$this->setState('filter.game_id', $gameId ?: (int) $app->getUserState('com_joomleague.predictiontips.game_id'));
		parent::populateState($ordering, $direction);
	}

	protected function buildQuery(): QueryInterface
	{
		$db = $this->getDatabase();
		$gameId = (int) $this->getState('filter.game_id');
		$query = $db->createQuery()
			->select([
				'a.*',
				'g.name AS game_name',
				'u.name AS user_name',
				'm.match_date',
				'CONCAT(th.name, " - ", ta.name) AS match_name',
				'CONCAT(a.home_score, ":", a.away_score) AS tip_score',
				'CONCAT(COALESCE(m.team1_result, "-"), ":", COALESCE(m.team2_result, "-")) AS result_score',
			])
			->from('#__joomleague_prediction_tip a')
			->join('INNER', '#__joomleague_prediction_game g ON g.id = a.game_id')
			->join('INNER', '#__users u ON u.id = a.user_id')
			->join('INNER', '#__joomleague_match m ON m.id = a.match_id')
			->join('LEFT', '#__joomleague_project_team ph ON ph.id = m.projectteam1_id')
			->join('LEFT', '#__joomleague_team th ON th.id = ph.team_id')
			->join('LEFT', '#__joomleague_project_team pa ON pa.id = m.projectteam2_id')
			->join('LEFT', '#__joomleague_team ta ON ta.id = pa.team_id');

		if ($gameId > 0) {
			$query->where('a.game_id = :game_id')->bind(':game_id', $gameId, ParameterType::INTEGER);
		}

		return $query;
	}
}
