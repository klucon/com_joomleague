<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Throwable;

final class MatchdataModel extends BaseDatabaseModel
{
	public function getContext(int $match): object
	{
		$d = $this->getDatabase();
		$sql = 'SELECT m.*,r.project_id,r.name AS round_name,th.name AS home,tg.name AS away FROM #__joomleague_match m JOIN #__joomleague_round r ON r.id=m.round_id LEFT JOIN #__joomleague_project_team ph ON ph.id=m.projectteam1_id LEFT JOIN #__joomleague_team th ON th.id=ph.team_id LEFT JOIN #__joomleague_project_team pg ON pg.id=m.projectteam2_id LEFT JOIN #__joomleague_team tg ON tg.id=pg.team_id WHERE m.id=' . (int) $match;

		return $d->setQuery($sql)->loadObject() ?? throw new \RuntimeException('COM_JOOMLEAGUE_MATCH_NOT_FOUND');
	}

	public function getEvents(int $m): array
	{
		return $this->getDatabase()->setQuery('SELECT * FROM #__joomleague_match_event WHERE match_id=' . (int) $m . ' ORDER BY event_time,id')->loadObjectList();
	}

	public function getMatchPlayers(int $m): array
	{
		return $this->getDatabase()->setQuery('SELECT mp.*,tp.projectteam_id FROM #__joomleague_match_player mp LEFT JOIN #__joomleague_team_player tp ON tp.id=mp.teamplayer_id WHERE mp.match_id=' . (int) $m . ' ORDER BY tp.projectteam_id,mp.ordering,mp.id')->loadObjectList();
	}

	public function getStatistics(int $m): array
	{
		return $this->getDatabase()->setQuery('SELECT * FROM #__joomleague_match_statistic WHERE match_id=' . (int) $m . ' ORDER BY id')->loadObjectList();
	}

	public function getReferees(int $m): array
	{
		return $this->getDatabase()->setQuery('SELECT * FROM #__joomleague_match_referee WHERE match_id=' . (int) $m . ' ORDER BY ordering,id')->loadObjectList();
	}

	public function getEventTypes(int $p): array
	{
		return $this->getDatabase()->setQuery('SELECT e.id,e.name FROM #__joomleague_eventtype e JOIN #__joomleague_project p ON p.sports_type_id=e.sports_type_id WHERE p.id=' . (int) $p . ' AND e.published=1 ORDER BY e.ordering,e.name')->loadObjectList();
	}

	public function getStatisticsTypes(int $p): array
	{
		return $this->getDatabase()->setQuery('SELECT s.id,s.name FROM #__joomleague_statistic s JOIN #__joomleague_project p ON p.sports_type_id=s.sports_type_id WHERE p.id=' . (int) $p . ' AND s.published=1 ORDER BY s.ordering,s.name')->loadObjectList();
	}

	public function getPlayers(int $m): array
	{
		return $this->getDatabase()->setQuery('SELECT tp.id,pt.id AS projectteam_id,CONCAT(t.name," · ",COALESCE(tp.jerseynumber,"-")," · ",TRIM(CONCAT_WS(" ",pe.firstname,pe.lastname))) AS name FROM #__joomleague_match x JOIN #__joomleague_team_player tp ON tp.projectteam_id IN (x.projectteam1_id,x.projectteam2_id) JOIN #__joomleague_person pe ON pe.id=tp.person_id JOIN #__joomleague_project_team pt ON pt.id=tp.projectteam_id JOIN #__joomleague_team t ON t.id=pt.team_id WHERE x.id=' . (int) $m . ' AND tp.published=1 ORDER BY pt.id,tp.ordering,pe.lastname,pe.firstname')->loadObjectList();
	}

	public function getPlayerPositions(int $p): array
	{
		return $this->getDatabase()->setQuery('SELECT pp.id,po.name FROM #__joomleague_project_position pp JOIN #__joomleague_position po ON po.id=pp.position_id WHERE pp.project_id=' . (int) $p . ' AND po.persontype=1 ORDER BY po.ordering,po.name')->loadObjectList();
	}

	public function getProjectReferees(int $p): array
	{
		return $this->getDatabase()->setQuery('SELECT pr.id,CONCAT_WS(" ",pe.firstname,pe.lastname) AS name FROM #__joomleague_project_referee pr JOIN #__joomleague_person pe ON pe.id=pr.person_id WHERE pr.project_id=' . (int) $p . ' ORDER BY pe.lastname')->loadObjectList();
	}

	public function getRefereePositions(int $p): array
	{
		return $this->getDatabase()->setQuery('SELECT pp.id,po.name FROM #__joomleague_project_position pp JOIN #__joomleague_position po ON po.id=pp.position_id WHERE pp.project_id=' . (int) $p . ' AND po.persontype=3 ORDER BY po.ordering')->loadObjectList();
	}

	public function replace(int $match, string $section, array $rows): void
	{
		$table = [
			'events' => '#__joomleague_match_event',
			'players' => '#__joomleague_match_player',
			'statistics' => '#__joomleague_match_statistic',
			'referees' => '#__joomleague_match_referee',
		][$section] ?? throw new \InvalidArgumentException();

		if ($match < 1) {
			throw new \RuntimeException('COM_JOOMLEAGUE_MATCH_NOT_FOUND');
		}

		$d = $this->getDatabase();
		$d->transactionStart();

		try {
			$d->setQuery('DELETE FROM ' . $table . ' WHERE match_id=' . (int) $match)->execute();
			$order = 0;
			$seenPlayers = [];
			$seenEvents = [];
			$seenStatistics = [];
			$seenReferees = [];

			foreach ($rows as $r) {
				if (!is_array($r)) {
					continue;
				}

				$row = null;

				if ($section === 'events' && !empty($r['event_type_id'])) {
					$eventTypeId = (int) $r['event_type_id'];
					$eventKey = implode(':', [
						$eventTypeId,
						$this->nullableInt($r['projectteam_id'] ?? null) ?? 0,
						$this->nullableInt($r['teamplayer_id'] ?? null) ?? 0,
						$this->nullableInt($r['teamplayer_id2'] ?? null) ?? 0,
						$this->nullableString($r['event_time'] ?? '') ?? '',
						$this->nullableString($r['notice'] ?? '') ?? '',
					]);

					if (isset($seenEvents[$eventKey])) {
						continue;
					}

					$seenEvents[$eventKey] = true;
					$row = (object) [
						'match_id' => $match,
						'projectteam_id' => $this->nullableInt($r['projectteam_id'] ?? null),
						'teamplayer_id' => $this->nullableInt($r['teamplayer_id'] ?? null),
						'teamplayer_id2' => $this->nullableInt($r['teamplayer_id2'] ?? null),
						'event_time' => $this->nullableString($r['event_time'] ?? '') ?? '',
						'event_type_id' => $eventTypeId,
						'event_sum' => $this->nullableFloat($r['event_sum'] ?? null) ?? 1.0,
						'notice' => $this->nullableString($r['notice'] ?? '') ?? '',
						'notes' => $this->nullableString($r['notes'] ?? null),
					];
				}

				if ($section === 'players' && !empty($r['teamplayer_id'])) {
					$teamplayerId = (int) $r['teamplayer_id'];

					if (isset($seenPlayers[$teamplayerId])) {
						continue;
					}

					$seenPlayers[$teamplayerId] = true;
					$row = (object) [
						'match_id' => $match,
						'teamplayer_id' => $teamplayerId,
						'project_position_id' => $this->nullableInt($r['project_position_id'] ?? null),
						'came_in' => !empty($r['came_in']) ? 1 : 0,
						'in_for' => $this->nullableInt($r['in_for'] ?? null),
						'out' => !empty($r['out']) ? 1 : 0,
						'in_out_time' => $this->nullableString($r['in_out_time'] ?? null),
						'ordering' => ++$order,
					];
				}

				if ($section === 'statistics' && !empty($r['statistic_id'])) {
					$projectTeamId = $this->nullableInt($r['projectteam_id'] ?? null);

					if ($projectTeamId === null) {
						continue;
					}

					$statisticId = (int) $r['statistic_id'];
					$teamplayerId = $this->nullableInt($r['teamplayer_id'] ?? null);
					$statisticKey = implode(':', [$projectTeamId, $teamplayerId ?? 0, $statisticId]);

					if (isset($seenStatistics[$statisticKey])) {
						continue;
					}

					$seenStatistics[$statisticKey] = true;
					$row = (object) [
						'match_id' => $match,
						'projectteam_id' => $projectTeamId,
						'teamplayer_id' => $teamplayerId,
						'statistic_id' => $statisticId,
						'value' => $this->nullableFloat($r['value'] ?? null) ?? 0.0,
					];
				}

				if ($section === 'referees' && !empty($r['project_referee_id'])) {
					$projectRefereeId = (int) $r['project_referee_id'];

					if (isset($seenReferees[$projectRefereeId])) {
						continue;
					}

					$seenReferees[$projectRefereeId] = true;
					$row = (object) [
						'match_id' => $match,
						'project_referee_id' => $projectRefereeId,
						'project_position_id' => $this->nullableInt($r['project_position_id'] ?? null),
						'ordering' => ++$order,
					];
				}

				if ($row !== null) {
					$d->insertObject($table, $row);
				}
			}

			$d->transactionCommit();
		} catch (Throwable $e) {
			$d->transactionRollback();
			throw $e;
		}
	}

	private function nullableInt(mixed $value): ?int
	{
		if ($value === null || $value === '' || (int) $value === 0) {
			return null;
		}

		return (int) $value;
	}

	private function nullableFloat(mixed $value): ?float
	{
		if ($value === null || $value === '') {
			return null;
		}

		return (float) $value;
	}

	private function nullableString(mixed $value): ?string
	{
		if ($value === null) {
			return null;
		}

		$value = trim((string) $value);

		return $value !== '' ? $value : null;
	}
}
