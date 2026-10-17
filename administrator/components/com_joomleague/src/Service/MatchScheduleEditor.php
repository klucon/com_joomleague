<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

final class MatchScheduleEditor
{
	/** Joomla's time field can submit zero seconds for a minute-precision schedule. */
	public static function normalizeTime(string $time): string
	{
		$time = trim($time);
		if ($time === '') return '';
		if (preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9](?::00)?$/D', $time) !== 1) {
			throw new \UnexpectedValueException(Text::_('COM_JOOMLEAGUE_ERROR_MATCH_VALUES_INVALID'));
		}
		return substr($time, 0, 5);
	}
	public function __construct(private readonly DatabaseInterface $database)
	{
	}

	/**
	 * Head-to-head matches keep their participants fixed to exactly two slots, assigned right
	 * here alongside the schedule. Any other contest type (currently only "race") can have any
	 * number of participants, so this method leaves the participant list untouched for them -
	 * that is handled by the dedicated Matchparticipants screen instead.
	 *
	 * @param array<string,mixed> $data
	 */
	public function save(int $matchId, int $roundId, array $data, int $userId): void
	{
		$context = $this->context($matchId, $roundId);
		$db = $this->database;
		$db->transactionStart(true);
		$statementGuard = null;
		try {
			$statementGuard = PostgresqlStatementGuard::attach($db);
			$db->setQuery('SELECT id FROM ' . $db->quoteName('#__joomleague_project') . ' WHERE id = ' . (int) $context->project_id . ' FOR UPDATE')->loadResult();
			$db->setQuery('SELECT id FROM ' . $db->quoteName('#__joomleague_project_match') . ' WHERE id = ' . $matchId . ' FOR UPDATE')->loadResult();
			$this->saveLocked($matchId, $roundId, $data, $userId);
			$db->transactionCommit(true);
		} catch (\Throwable $error) {
			$db->transactionRollback(true);
			throw $error;
		} finally {
			$statementGuard?->detach($db);
		}
	}

	/** @param array<string,mixed> $data */
	private function saveLocked(int $matchId, int $roundId, array $data, int $userId): void
	{
		$context = $this->context($matchId, $roundId);
		$headToHead = (new ProjectContextRepository($this->database))->get((int) $context->project_id)->profile['contest']['type'] === 'head_to_head';
		$home = 0;
		$away = 0;

		if ($headToHead) {
			$home = (int) ($data['participant_slot_1'] ?? 0);
			$away = (int) ($data['participant_slot_2'] ?? 0);

			if ($home < 1 || $away < 1 || $home === $away
				|| !(new StageEntryOptionsProvider($this->database))->contains((int) $context->project_id, (int) $context->stage_id, [$home, $away])) {
				throw new \UnexpectedValueException(Text::_('COM_JOOMLEAGUE_ERROR_MATCH_PARTICIPANTS_INVALID'));
			}
		}

		$matchNumber = trim((string) ($data['match_number'] ?? ''));
		$date = trim((string) ($data['scheduled_date'] ?? ''));
		$time = self::normalizeTime((string) ($data['scheduled_time'] ?? ''));
		$attendance = trim((string) ($data['attendance'] ?? ''));
		if (mb_strlen($matchNumber) > 100 || (($date === '') !== ($time === ''))
			|| ($date !== '' && !$this->validDateTime($date, $time))
			|| ($attendance !== '' && (!ctype_digit($attendance) || (int) $attendance < 0))) {
			throw new \UnexpectedValueException(Text::_('COM_JOOMLEAGUE_ERROR_MATCH_VALUES_INVALID'));
		}

		$scheduledStart = null;
		if ($date !== '') {
			$timezone = (string) ($context->timezone ?: $context->project_timezone ?: Factory::getApplication()->get('offset', 'UTC'));
			$scheduledStart = Factory::getDate($date . ' ' . $time . ':00', $timezone)->setTimezone(new \DateTimeZone('UTC'))->toSql();
		}

		$existing = $headToHead ? $this->participants($matchId) : [];
		$desired = [1 => $home, 2 => $away];
		$changed = $headToHead && $existing !== $desired;
		if ($changed && $existing !== [] && (new MatchCompetitionDataGuard($this->database))->hasCompetitionData($matchId, true)) {
			throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_MATCH_PARTICIPANTS_LOCKED'));
		}

		$matchNumberValue = $matchNumber !== '' ? $matchNumber : null;
		$attendanceValue = $attendance !== '' ? (int) $attendance : null;
		$modified = Factory::getDate()->toSql();
		$query = $this->database->getQuery(true)
			->update($this->database->quoteName('#__joomleague_project_match'))
			->set($this->database->quoteName('match_number') . ' = :matchNumber')
			->set($this->database->quoteName('scheduled_start') . ' = :scheduledStart')
			->set($this->database->quoteName('attendance') . ' = :attendance')
			->set($this->database->quoteName('modified') . ' = :modified')
			->set($this->database->quoteName('modified_by') . ' = :modifiedBy')
			->where($this->database->quoteName('id') . ' = :matchId')
			->where($this->database->quoteName('round_id') . ' = :roundId')
			->bind(':matchNumber', $matchNumberValue, $matchNumberValue === null ? ParameterType::NULL : ParameterType::STRING)
			->bind(':scheduledStart', $scheduledStart)
			->bind(':attendance', $attendanceValue, $attendanceValue === null ? ParameterType::NULL : ParameterType::INTEGER)
			->bind(':modified', $modified)
			->bind(':modifiedBy', $userId, ParameterType::INTEGER)
			->bind(':matchId', $matchId, ParameterType::INTEGER)
			->bind(':roundId', $roundId, ParameterType::INTEGER);
		$this->database->setQuery($query)->execute();

		if ($changed) {
			$delete = $this->database->getQuery(true)->delete($this->database->quoteName('#__joomleague_match_participant'))
				->where($this->database->quoteName('match_id') . ' = :matchId')->bind(':matchId', $matchId, ParameterType::INTEGER);
			$this->database->setQuery($delete)->execute();
			foreach ($desired as $slot => $entryId) {
				$row = (object) [
					'uuid' => UuidFactory::v4(), 'match_id' => $matchId, 'project_id' => (int) $context->project_id,
					'project_entry_id' => $entryId, 'role_code' => $slot === 1 ? 'home' : 'away', 'slot_number' => $slot,
					'result_status' => 'scheduled', 'published' => 1, 'ordering' => $slot,
					'created' => Factory::getDate()->toSql(), 'created_by' => $userId,
				];
				$this->database->insertObject('#__joomleague_match_participant', $row);
			}
		}
	}

	private function context(int $matchId, int $roundId): object
	{
		$query = $this->database->getQuery(true)->select(['match.project_id', 'match.stage_id', 'match.timezone', 'project.timezone AS project_timezone'])
			->from($this->database->quoteName('#__joomleague_project_match', 'match'))
			->innerJoin($this->database->quoteName('#__joomleague_project', 'project') . ' ON project.id = match.project_id')
			->where($this->database->quoteName('match.id') . ' = :matchId')->where($this->database->quoteName('match.round_id') . ' = :roundId')
			->bind(':matchId', $matchId, ParameterType::INTEGER)->bind(':roundId', $roundId, ParameterType::INTEGER);
		$context = $this->database->setQuery($query)->loadObject();
		if (!$context) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_MATCH_INVALID'));
		return $context;
	}

	/** @return array<int,int> */
	private function participants(int $matchId): array
	{
		$query = $this->database->getQuery(true)->select(['slot_number', 'project_entry_id'])->from($this->database->quoteName('#__joomleague_match_participant'))
			->where($this->database->quoteName('match_id') . ' = ' . $matchId)->where($this->database->quoteName('slot_number') . ' IN (1, 2)')
			->order($this->database->quoteName('slot_number'));
		$result = [];
		// A locking read must see the latest committed participants, even when
		// the caller already established a repeatable-read snapshot.
		foreach ($this->database->setQuery((string) $query . ' FOR UPDATE')->loadObjectList() as $row) $result[(int) $row->slot_number] = (int) $row->project_entry_id;
		return $result;
	}

	private function validDateTime(string $date, string $time): bool
	{
		$value = \DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time);
		return $value !== false && $value->format('Y-m-d H:i') === $date . ' ' . $time;
	}
}
