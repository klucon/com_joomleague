<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\Database\ParameterType;

final class PredictiongameModel extends EntityAdminModel
{
	protected string $entityName = 'predictiongame';

	protected function prepareTable($table): void
	{
		$userId = (int) $this->getCurrentUser()->id ?: null;
		$now = (new Date())->toSql();

		$table->project_id = (int) $table->project_id;
		$table->name = trim((string) $table->name);
		$table->deadline_minutes = max(0, (int) $table->deadline_minutes);
		$table->points_exact = max(0, (int) $table->points_exact);
		$table->points_tendency = max(0, (int) $table->points_tendency);
		$table->points_goal_diff = max(0, (int) $table->points_goal_diff);
		$table->show_ranking = (int) $table->show_ranking;
		$table->published = (int) $table->published;
		$table->modified = $now;
		$table->modified_by = $userId;

		if ((int) $table->id === 0) {
			$table->created = $table->created ?: $now;
			$table->created_by = (int) $table->created_by ?: $userId;
		}
	}

	public function recalculate(int $gameId): void
	{
		$db = $this->getDatabase();
		$userIds = $db->setQuery(
			$db->getQuery(true)
				->select('DISTINCT ' . $db->quoteName('user_id'))
				->from($db->quoteName('#__joomleague_prediction_tip'))
				->where($db->quoteName('game_id') . ' = :game_id')
				->bind(':game_id', $gameId, ParameterType::INTEGER)
		)->loadColumn();

		foreach ($userIds as $userId) {
			$this->recalculateUser($gameId, (int) $userId);
		}
	}

	private function recalculateUser(int $gameId, int $userId): void
	{
		$db = $this->getDatabase();
		$game = $db->setQuery(
			$db->getQuery(true)
				->select('*')
				->from($db->quoteName('#__joomleague_prediction_game'))
				->where($db->quoteName('id') . ' = :game_id')
				->bind(':game_id', $gameId, ParameterType::INTEGER)
		)->loadObject();

		if (!$game || $userId < 1) {
			return;
		}

		$query = $db->getQuery(true)
			->select([
				't.*',
				$db->quoteName('m.team1_result'),
				$db->quoteName('m.team2_result'),
				$db->quoteName('m.count_result'),
				$db->quoteName('m.round_id'),
			])
			->from($db->quoteName('#__joomleague_prediction_tip', 't'))
			->join('INNER', $db->quoteName('#__joomleague_match', 'm') . ' ON ' . $db->quoteName('m.id') . ' = ' . $db->quoteName('t.match_id'))
			->where($db->quoteName('t.game_id') . ' = :game_id')
			->where($db->quoteName('t.user_id') . ' = :user_id')
			->bind(':game_id', $gameId, ParameterType::INTEGER)
			->bind(':user_id', $userId, ParameterType::INTEGER);

		$byRound = [];

		foreach ($db->setQuery($query)->loadObjectList() as $tip) {
			if ($tip->team1_result === null || $tip->team2_result === null || (int) $tip->count_result !== 1) {
				continue;
			}

			$roundId = (int) $tip->round_id;
			$byRound[$roundId] ??= ['tips' => 0, 'points' => 0, 'exact' => 0, 'tendency' => 0];
			$points = $this->calculatePoints($tip, $game);
			$byRound[$roundId]['tips']++;
			$byRound[$roundId]['points'] += $points;
			$byRound[$roundId]['exact'] += ((int) $tip->home_score === (int) $tip->team1_result && (int) $tip->away_score === (int) $tip->team2_result) ? 1 : 0;
			$byRound[$roundId]['tendency'] += $this->outcome((int) $tip->home_score, (int) $tip->away_score) === $this->outcome((int) $tip->team1_result, (int) $tip->team2_result) ? 1 : 0;

			$db->setQuery(
				$db->getQuery(true)
					->update($db->quoteName('#__joomleague_prediction_tip'))
					->set($db->quoteName('points') . ' = :points')
					->set($db->quoteName('calculated') . ' = 1')
					->where($db->quoteName('id') . ' = :id')
					->bind(':points', $points, ParameterType::INTEGER)
					->bind(':id', (int) $tip->id, ParameterType::INTEGER)
			)->execute();
		}

		$db->setQuery(
			$db->getQuery(true)
				->delete($db->quoteName('#__joomleague_prediction_score'))
				->where($db->quoteName('game_id') . ' = :game_id')
				->where($db->quoteName('user_id') . ' = :user_id')
				->bind(':game_id', $gameId, ParameterType::INTEGER)
				->bind(':user_id', $userId, ParameterType::INTEGER)
		)->execute();

		$now = Factory::getDate()->toSql();

		foreach ($byRound as $roundId => $row) {
			$db->setQuery(
				$db->getQuery(true)
					->insert($db->quoteName('#__joomleague_prediction_score'))
					->columns($db->quoteName(['game_id', 'user_id', 'round_id', 'tips', 'points', 'exact_hits', 'tendency_hits', 'modified']))
					->values(':game_id, :user_id, :round_id, :tips, :points, :exact_hits, :tendency_hits, :modified')
					->bind(':game_id', $gameId, ParameterType::INTEGER)
					->bind(':user_id', $userId, ParameterType::INTEGER)
					->bind(':round_id', $roundId, ParameterType::INTEGER)
					->bind(':tips', $row['tips'], ParameterType::INTEGER)
					->bind(':points', $row['points'], ParameterType::INTEGER)
					->bind(':exact_hits', $row['exact'], ParameterType::INTEGER)
					->bind(':tendency_hits', $row['tendency'], ParameterType::INTEGER)
					->bind(':modified', $now)
			)->execute();
		}
	}

	private function calculatePoints(object $tip, object $game): int
	{
		$homeTip = (int) $tip->home_score;
		$awayTip = (int) $tip->away_score;
		$home = (int) $tip->team1_result;
		$away = (int) $tip->team2_result;

		if ($homeTip === $home && $awayTip === $away) {
			return (int) $game->points_exact;
		}

		if (($homeTip - $awayTip) === ($home - $away)) {
			return (int) $game->points_goal_diff;
		}

		return $this->outcome($homeTip, $awayTip) === $this->outcome($home, $away) ? (int) $game->points_tendency : 0;
	}

	private function outcome(int $home, int $away): int
	{
		return $home <=> $away;
	}
}
