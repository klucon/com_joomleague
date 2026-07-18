<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
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
		return $this->getDatabase()->setQuery('SELECT * FROM #__joomleague_match_event WHERE match_id=' . (int) $m . ' ORDER BY CAST(NULLIF(event_time, "") AS UNSIGNED) ASC,id ASC')->loadObjectList();
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

	public function getMatchStaff(int $m): array
	{
		return $this->getDatabase()->setQuery('SELECT * FROM #__joomleague_match_staff WHERE match_id=' . (int) $m . ' ORDER BY ordering,id')->loadObjectList();
	}

	public function getEventTypes(int $p): array
	{
		$rows = $this->getDatabase()->setQuery('SELECT e.id,e.name FROM #__joomleague_eventtype e JOIN #__joomleague_project p ON p.sports_type_id=e.sports_type_id WHERE p.id=' . (int) $p . ' AND e.published=1')->loadObjectList();

		return $this->sortByTranslatedName($rows);
	}

	public function getStatisticsTypes(int $p): array
	{
		$rows = $this->getDatabase()->setQuery('SELECT s.id,s.name FROM #__joomleague_statistic s JOIN #__joomleague_project p ON p.sports_type_id=s.sports_type_id WHERE p.id=' . (int) $p . ' AND s.published=1')->loadObjectList();

		return $this->sortByTranslatedName($rows);
	}

	public function getPlayers(int $m): array
	{
		$rows = $this->getDatabase()->setQuery('SELECT tp.id,pt.id AS projectteam_id,pe.lastname,pe.firstname,CONCAT(t.name," · ",COALESCE(tp.jerseynumber,"-")," · ",TRIM(CONCAT_WS(" ",pe.lastname,pe.firstname))) AS name FROM #__joomleague_match x JOIN #__joomleague_team_player tp ON tp.projectteam_id IN (x.projectteam1_id,x.projectteam2_id) JOIN #__joomleague_person pe ON pe.id=tp.person_id JOIN #__joomleague_project_team pt ON pt.id=tp.projectteam_id JOIN #__joomleague_team t ON t.id=pt.team_id WHERE x.id=' . (int) $m . ' AND tp.published=1')->loadObjectList();

		return $this->sortCzechNames($rows, true);
	}

	/**
	 * Řadí podle příjmení/jména správnou českou abecední kolací (Š je samostatné písmeno
	 * za S, "ch" samostatné písmeno za H) přes PHP intl Collator – MariaDB kolace pro
	 * utf8mb4 (unicode_ci i czech_ci) tohle neumí správně, zejména spřežku "ch", proto se
	 * řadí až tady, ne v SQL ORDER BY.
	 *
	 * @param object[] $rows  musí mít vlastnosti lastname, firstname (a projectteam_id, pokud $byTeamFirst)
	 * @return object[]
	 */
	private function sortCzechNames(array $rows, bool $byTeamFirst = false): array
	{
		$collator = new \Collator('cs_CZ');
		usort($rows, static function (object $a, object $b) use ($collator, $byTeamFirst): int {
			if ($byTeamFirst) {
				$teamCmp = ((int) ($a->projectteam_id ?? 0)) <=> ((int) ($b->projectteam_id ?? 0));

				if ($teamCmp !== 0) {
					return $teamCmp;
				}
			}

			$lastCmp = $collator->compare((string) ($a->lastname ?? ''), (string) ($b->lastname ?? ''));

			return $lastCmp !== 0 ? $lastCmp : $collator->compare((string) ($a->firstname ?? ''), (string) ($b->firstname ?? ''));
		});

		return $rows;
	}

	/**
	 * Přeloží konstantu ve vlastnosti name a teprve podle přeloženého textu seřadí českou
	 * kolací – řazení podle syrové konstanty (COM_JOOMLEAGUE_...) ani podle sloupce
	 * ordering (pořadí vytvoření) neodpovídá abecednímu pořadí, které admin očekává
	 * v rozbalovacích seznamech (typ události, pozice, statistika).
	 *
	 * @param object[] $rows  musí mít vlastnost name
	 * @return object[]
	 */
	private function sortByTranslatedName(array $rows): array
	{
		$collator = new \Collator('cs_CZ');

		foreach ($rows as $row) {
			$row->name = Text::_((string) $row->name);
		}

		usort($rows, static fn (object $a, object $b): int => $collator->compare((string) $a->name, (string) $b->name));

		return $rows;
	}

	public function getPlayerPositions(int $p): array
	{
		$rows = $this->getDatabase()->setQuery('SELECT pp.id,po.name FROM #__joomleague_project_position pp JOIN #__joomleague_position po ON po.id=pp.position_id WHERE pp.project_id=' . (int) $p . ' AND po.persontype=1')->loadObjectList();

		return $this->sortByTranslatedName($rows);
	}

	public function getProjectReferees(int $p): array
	{
		$rows = $this->getDatabase()->setQuery('SELECT pr.id,pe.lastname,pe.firstname,CONCAT_WS(" ",pe.lastname,pe.firstname) AS name FROM #__joomleague_project_referee pr JOIN #__joomleague_person pe ON pe.id=pr.person_id WHERE pr.project_id=' . (int) $p)->loadObjectList();

		return $this->sortCzechNames($rows);
	}

	public function getRefereePositions(int $p): array
	{
		$rows = $this->getDatabase()->setQuery('SELECT pp.id,po.name FROM #__joomleague_project_position pp JOIN #__joomleague_position po ON po.id=pp.position_id WHERE pp.project_id=' . (int) $p . ' AND po.persontype=3')->loadObjectList();

		return $this->sortByTranslatedName($rows);
	}

	public function getStaff(int $m): array
	{
		$rows = $this->getDatabase()->setQuery('SELECT ts.id,pt.id AS projectteam_id,pe.lastname,pe.firstname,CONCAT(t.name," · ",TRIM(CONCAT_WS(" ",pe.lastname,pe.firstname))) AS name FROM #__joomleague_match x JOIN #__joomleague_team_staff ts ON ts.projectteam_id IN (x.projectteam1_id,x.projectteam2_id) JOIN #__joomleague_person pe ON pe.id=ts.person_id JOIN #__joomleague_project_team pt ON pt.id=ts.projectteam_id JOIN #__joomleague_team t ON t.id=pt.team_id WHERE x.id=' . (int) $m . ' AND ts.published=1')->loadObjectList();

		return $this->sortCzechNames($rows, true);
	}

	public function getStaffPositions(int $p): array
	{
		$rows = $this->getDatabase()->setQuery('SELECT pp.id,po.name FROM #__joomleague_project_position pp JOIN #__joomleague_position po ON po.id=pp.position_id WHERE pp.project_id=' . (int) $p . ' AND po.persontype IN (2,4)')->loadObjectList();

		return $this->sortByTranslatedName($rows);
	}

	public function replace(int $match, string $section, array $rows): void
	{
		$table = [
			'events' => '#__joomleague_match_event',
			'players' => '#__joomleague_match_player',
			'statistics' => '#__joomleague_match_statistic',
			'referees' => '#__joomleague_match_referee',
			'staff' => '#__joomleague_match_staff',
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
			$seenStaff = [];

			foreach ($rows as $r) {
				if (!is_array($r)) {
					continue;
				}

				$row = null;

				if ($section === 'events' && !empty($r['event_type_id'])) {
					$eventTypeId = (int) $r['event_type_id'];
					$projectTeamId = $this->nullableInt($r['projectteam_id'] ?? null);
					$teamplayerId = $this->nullableInt($r['teamplayer_id'] ?? null);
					$externalPersonName = mb_substr($this->nullableString($r['external_person_name'] ?? '') ?? '', 0, 100);
					$secondTeamplayerId = $this->nullableInt($r['teamplayer_id2'] ?? null);
					$eventTime = $this->nullableString($r['event_time'] ?? '') ?? '';
					$notice = $this->nullableString($r['notice'] ?? '') ?? '';

					if ($projectTeamId === null || ($teamplayerId === null && $externalPersonName === '')) {
						continue;
					}

					$eventKey = implode(':', [
						$eventTypeId,
						$projectTeamId,
						$teamplayerId ?? 0,
						$externalPersonName,
						$secondTeamplayerId ?? 0,
						$eventTime,
						$notice,
					]);

					if (isset($seenEvents[$eventKey])) {
						continue;
					}

					$seenEvents[$eventKey] = true;
					$row = (object) [
						'match_id' => $match,
						'projectteam_id' => $projectTeamId,
						'teamplayer_id' => $teamplayerId,
						'external_person_name' => $externalPersonName,
						'teamplayer_id2' => $secondTeamplayerId,
						'event_time' => $eventTime,
						'event_type_id' => $eventTypeId,
						'event_sum' => $this->nullableFloat($r['event_sum'] ?? null) ?? 1.0,
						'notice' => $notice,
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
						'is_substitute' => !empty($r['is_substitute']) ? 1 : 0,
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

				if ($section === 'referees') {
					$projectRefereeId = $this->nullableInt($r['project_referee_id'] ?? null);
					$externalRefereeName = mb_substr($this->nullableString($r['external_referee_name'] ?? '') ?? '', 0, 100);

					if ($projectRefereeId === null && $externalRefereeName === '') {
						continue;
					}

					$refereeKey = ($projectRefereeId ?? 0) . ':' . $externalRefereeName;

					if (isset($seenReferees[$refereeKey])) {
						continue;
					}

					$seenReferees[$refereeKey] = true;
					$row = (object) [
						'match_id' => $match,
						'project_referee_id' => $projectRefereeId,
						'external_referee_name' => $externalRefereeName,
						'project_position_id' => $this->nullableInt($r['project_position_id'] ?? null),
						'ordering' => ++$order,
					];
				}

				if ($section === 'staff' && !empty($r['team_staff_id'])) {
					$teamStaffId = (int) $r['team_staff_id'];
					$projectPositionId = $this->nullableInt($r['project_position_id'] ?? null);
					$staffKey = $teamStaffId . ':' . ($projectPositionId ?? 0);

					if (isset($seenStaff[$staffKey])) {
						continue;
					}

					$seenStaff[$staffKey] = true;
					$row = (object) [
						'match_id' => $match,
						'team_staff_id' => $teamStaffId,
						'project_position_id' => $projectPositionId,
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
