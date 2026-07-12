<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

\defined('_JEXEC') or die;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use RuntimeException;
use Throwable;

final class ScheduleGeneratorService
{
	public const ROUND_ROBIN_FIRST_HALF = 'round-robin-first-half-v1';
	public const ROUND_ROBIN_SECOND_HALF = 'round-robin-second-half-v1';

	public function __construct(
		private readonly DatabaseInterface $database,
		private readonly ScheduleTemplateService $templateService
	) {
	}

	public function generateRoundRobin(
		int $projectId,
		string $startDate,
		string $startTime,
		int $intervalDays,
		bool $includeReturnLegs,
		int $firstMatchNumber = 0,
		string $roundNamePattern = '%d. kolo',
		string $templateId = self::ROUND_ROBIN_FIRST_HALF
	): array {
		$project = $this->getProject($projectId);
		$this->ensureProjectHasNoRounds($projectId);

		$teams = $this->getProjectTeamIds($projectId);

		if (count($teams) < 2) {
			throw new RuntimeException('COM_JOOMLEAGUE_SCHEDULE_NEEDS_TEAMS');
		}

		if (!in_array($templateId, [self::ROUND_ROBIN_FIRST_HALF, self::ROUND_ROBIN_SECOND_HALF], true)) {
			throw new RuntimeException('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_UNSUPPORTED');
		}

		$templateSchedules = [
			$this->templateService->getRoundRobinSchedule($templateId, count($teams)),
		];

		if ($includeReturnLegs && $templateId === self::ROUND_ROBIN_FIRST_HALF) {
			$templateSchedules[] = $this->templateService->getRoundRobinSchedule(self::ROUND_ROBIN_SECOND_HALF, count($teams));
		}

		$timezone = new DateTimeZone($project->timezone ?: 'UTC');
		$start = new DateTimeImmutable(trim($startDate) . ' ' . trim($startTime), $timezone);
		$nextMatchNumber = $firstMatchNumber;
		$roundNumber = 0;
		$matchCount = 0;

		$this->database->transactionStart();

		try {
			foreach ($templateSchedules as $templateSchedule) {
				foreach ($templateSchedule as $templateRound) {
					$roundNumber++;
					$roundDate = $start->add(new DateInterval('P' . (($roundNumber - 1) * max(1, $intervalDays)) . 'D'));
					$roundId = $this->createRound($projectId, $roundNumber, $roundDate, $roundNamePattern);

					foreach ($templateRound->matches ?? [] as $templateMatch) {
						$homeId = $this->resolveSeed($templateMatch->home ?? null, $teams);
						$awayId = $this->resolveSeed($templateMatch->away ?? null, $teams);

						if ($homeId < 1 || $awayId < 1) {
							continue;
						}

						$this->createMatch($roundId, $homeId, $awayId, $roundDate, $nextMatchNumber);
						$matchCount++;
					}
				}
			}

			$this->database->transactionCommit();

			return ['rounds' => $roundNumber, 'matches' => $matchCount];
		} catch (Throwable $exception) {
			$this->database->transactionRollback();
			throw $exception;
		}
	}

	public function createEmptyRounds(int $projectId, string $startDate, int $intervalDays, int $count, string $roundNamePattern = '%d. kolo'): array
	{
		if ($count < 1 || $count > 200) {
			throw new RuntimeException('COM_JOOMLEAGUE_SCHEDULE_INVALID_COUNT');
		}

		$this->getProject($projectId);

		$start = new DateTimeImmutable($startDate);
		$currentMax = $this->getMaxRoundCode($projectId);

		$this->database->transactionStart();

		try {
			for ($i = 1; $i <= $count; $i++) {
				$roundNumber = $currentMax + $i;
				$roundDate = $start->add(new DateInterval('P' . (($i - 1) * max(1, $intervalDays)) . 'D'));
				$this->createRound($projectId, $roundNumber, $roundDate, $roundNamePattern);
			}

			$this->database->transactionCommit();

			return ['rounds' => $count, 'matches' => 0];
		} catch (Throwable $exception) {
			$this->database->transactionRollback();
			throw $exception;
		}
	}

	private function getProject(int $projectId): object
	{
		$query = $this->database->createQuery()
			->select('*')
			->from($this->database->quoteName('#__joomleague_project'))
			->where($this->database->quoteName('id') . ' = :id')
			->bind(':id', $projectId, ParameterType::INTEGER);

		$project = $this->database->setQuery($query)->loadObject();

		if (!$project) {
			throw new RuntimeException('COM_JOOMLEAGUE_PROJECT_NOT_FOUND');
		}

		return $project;
	}

	private function ensureProjectHasNoRounds(int $projectId): void
	{
		$query = $this->database->createQuery()
			->select('COUNT(*)')
			->from($this->database->quoteName('#__joomleague_round'))
			->where($this->database->quoteName('project_id') . ' = :project_id')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		if ((int) $this->database->setQuery($query)->loadResult() > 0) {
			throw new RuntimeException('COM_JOOMLEAGUE_SCHEDULE_HAS_ROUNDS');
		}
	}

	private function getProjectTeamIds(int $projectId): array
	{
		$query = $this->database->createQuery()
			->select($this->database->quoteName('id'))
			->from($this->database->quoteName('#__joomleague_project_team'))
			->where($this->database->quoteName('project_id') . ' = :project_id')
			->order($this->database->quoteName('ordering') . ' ASC, ' . $this->database->quoteName('id') . ' ASC')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		return array_map('intval', $this->database->setQuery($query)->loadColumn());
	}

	private function getMaxRoundCode(int $projectId): int
	{
		$query = $this->database->createQuery()
			->select('COALESCE(MAX(' . $this->database->quoteName('roundcode') . '), 0)')
			->from($this->database->quoteName('#__joomleague_round'))
			->where($this->database->quoteName('project_id') . ' = :project_id')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		return (int) $this->database->setQuery($query)->loadResult();
	}

	private function resolveSeed(?object $participant, array $projectTeamIds): int
	{
		if ($participant === null || !isset($participant->seed)) {
			return 0;
		}

		$seed = (int) $participant->seed;

		return $projectTeamIds[$seed - 1] ?? 0;
	}

	private function createRound(int $projectId, int $roundNumber, DateTimeImmutable $roundDate, string $roundNamePattern): int
	{
		$name = sprintf($roundNamePattern, $roundNumber);

		$round = (object) [
			'project_id' => $projectId,
			'roundcode' => $roundNumber,
			'name' => $name,
			'alias' => OutputFilter::stringURLSafe($name),
			'round_date_first' => $roundDate->format('Y-m-d'),
			'round_date_last' => $roundDate->format('Y-m-d'),
			'published' => 1,
			'ordering' => $roundNumber,
		];

		$this->database->insertObject('#__joomleague_round', $round, 'id');

		return (int) $round->id;
	}

	private function createMatch(int $roundId, int $homeId, int $awayId, DateTimeImmutable $matchDate, int &$nextMatchNumber): void
	{
		$match = (object) [
			'round_id' => $roundId,
			'match_number' => $nextMatchNumber > 0 ? (string) $nextMatchNumber++ : null,
			'projectteam1_id' => $homeId,
			'projectteam2_id' => $awayId,
			// Stadion domácího týmu (project_team → klub), s provázaností jako v editaci.
			'playground_id' => $this->resolvePlayground($homeId) ?: null,
			// match_date je v celém systému naivní lokální čas (form filter="string"),
			// proto ukládáme zadaný lokální čas beze změny – žádná konverze na UTC.
			'match_date' => $matchDate->format('Y-m-d H:i:s'),
			'summary' => '',
			'preview' => '',
			'decision_info' => '',
			'cancel_reason' => '',
			'match_result_detail' => '',
			'published' => 1,
			'alias' => '',
		];

		$this->database->insertObject('#__joomleague_match', $match, 'id');
	}

	/** Stadion domácího týmu: project_team.standard_playground → club.standard_playground → 0. */
	private function resolvePlayground(int $projectTeamId): int
	{
		if ($projectTeamId < 1) {
			return 0;
		}

		$db = $this->database;
		$row = $db->setQuery(
			$db->createQuery()
				->select([$db->quoteName('pt.standard_playground', 'pt_pg'), $db->quoteName('t.club_id')])
				->from($db->quoteName('#__joomleague_project_team', 'pt'))
				->join('LEFT', $db->quoteName('#__joomleague_team', 't') . ' ON t.id = pt.team_id')
				->where('pt.id = :id')
				->bind(':id', $projectTeamId, ParameterType::INTEGER)
		)->loadObject();

		if (!$row) {
			return 0;
		}

		if ((int) $row->pt_pg > 0) {
			return (int) $row->pt_pg;
		}

		if ((int) $row->club_id > 0) {
			$clubPg = (int) $db->setQuery(
				$db->createQuery()->select($db->quoteName('standard_playground'))
					->from($db->quoteName('#__joomleague_club'))
					->where('id = :cid')->bind(':cid', $row->club_id, ParameterType::INTEGER)
			)->loadResult();

			if ($clubPg > 0) {
				return $clubPg;
			}
		}

		return 0;
	}
}
