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

final class PredictionscoresModel extends EntityListModel
{
	protected array $searchColumns = ['u.name', 'g.name', 'r.name'];

	public function __construct($config = [], $factory = null)
	{
		$config['filter_fields'] = ['id', 'a.id', 'user_name', 'u.name', 'game_name', 'g.name', 'round_name', 'r.name', 'points', 'a.points', 'tips', 'a.tips'];
		parent::__construct($config, $factory);
	}

	protected function populateState($ordering = 'a.points', $direction = 'desc'): void
	{
		$app = Factory::getApplication();
		$gameId = $app->getInput()->getInt('game_id', 0);

		if ($gameId > 0) {
			$app->setUserState('com_joomleague.predictionscores.game_id', $gameId);
		}

		$this->setState('filter.game_id', $gameId ?: (int) $app->getUserState('com_joomleague.predictionscores.game_id'));
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
				'r.name AS round_name',
			])
			->from('#__joomleague_prediction_score a')
			->join('INNER', '#__joomleague_prediction_game g ON g.id = a.game_id')
			->join('INNER', '#__users u ON u.id = a.user_id')
			->join('LEFT', '#__joomleague_round r ON r.id = a.round_id');

		if ($gameId > 0) {
			$query->where('a.game_id = :game_id')->bind(':game_id', $gameId, ParameterType::INTEGER);
		}

		return $query;
	}
}
