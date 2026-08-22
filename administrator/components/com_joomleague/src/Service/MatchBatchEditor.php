<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Applies a venue and/or date shift to a batch of schedule items at once. Items that already
 * carry competition data (result, lineup, events, statistics) are skipped rather than touched,
 * for the same reason MatchScheduleEditor refuses to change their participants.
 */
final class MatchBatchEditor
{
    public function __construct(private readonly DatabaseInterface $database)
    {
    }

    /**
     * @param list<int> $matchIds
     * @return array{applied:int,skipped:int}
     */
    public function apply(array $matchIds, int $roundId, ?int $venueId, ?int $shiftDays, int $userId): array
    {
        $matchIds = array_values(array_unique(array_filter(array_map('intval', $matchIds), static fn (int $id): bool => $id > 0)));
        if ($matchIds === [] || ($venueId === null && $shiftDays === null)) {
            return ['applied' => 0, 'skipped' => 0];
        }

        if ($venueId !== null && $venueId > 0 && !$this->venueExists($venueId)) {
            throw new \UnexpectedValueException(Text::_('COM_JOOMLEAGUE_ERROR_MATCH_VENUE_INVALID'));
        }

        $guard = new MatchCompetitionDataGuard($this->database);
        $applied = 0;
        $skipped = 0;

        $this->database->transactionStart();
        try {
            foreach ($matchIds as $matchId) {
                $match = $this->matchRow($matchId, $roundId);
                if ($match === null) {
                    continue;
                }
                if ($guard->hasCompetitionData($matchId)) {
                    $skipped++;
                    continue;
                }

                $scheduledStart = $match->scheduled_start;
                if ($shiftDays !== null && $scheduledStart !== null) {
                    $scheduledStart = Factory::getDate($scheduledStart, 'UTC')
                        ->modify(($shiftDays >= 0 ? '+' : '') . $shiftDays . ' days')
                        ->toSql();
                }

                $newVenueId = $venueId !== null ? ($venueId > 0 ? $venueId : null) : ($match->venue_id !== null ? (int) $match->venue_id : null);

                $modified = Factory::getDate()->toSql();
                $query = $this->database->getQuery(true)
                    ->update($this->database->quoteName('#__joomleague_project_match'))
                    ->set($this->database->quoteName('venue_id') . ' = :venueId')
                    ->set($this->database->quoteName('scheduled_start') . ' = :scheduledStart')
                    ->set($this->database->quoteName('modified') . ' = :modified')
                    ->set($this->database->quoteName('modified_by') . ' = :modifiedBy')
                    ->where($this->database->quoteName('id') . ' = :matchId')
                    ->bind(':venueId', $newVenueId, $newVenueId === null ? ParameterType::NULL : ParameterType::INTEGER)
                    ->bind(':scheduledStart', $scheduledStart, $scheduledStart === null ? ParameterType::NULL : ParameterType::STRING)
                    ->bind(':modified', $modified)
                    ->bind(':modifiedBy', $userId, ParameterType::INTEGER)
                    ->bind(':matchId', $matchId, ParameterType::INTEGER);
                $this->database->setQuery($query)->execute();
                $applied++;
            }
            $this->database->transactionCommit();
        } catch (\Throwable $error) {
            $this->database->transactionRollback();
            throw $error;
        }

        return ['applied' => $applied, 'skipped' => $skipped];
    }

    private function venueExists(int $venueId): bool
    {
        $query = $this->database->getQuery(true)->select('COUNT(*)')->from($this->database->quoteName('#__joomleague_venue'))
            ->where($this->database->quoteName('id') . ' = :venueId')->bind(':venueId', $venueId, ParameterType::INTEGER);
        return (int) $this->database->setQuery($query)->loadResult() > 0;
    }

    private function matchRow(int $matchId, int $roundId): ?object
    {
        $query = $this->database->getQuery(true)->select(['id', 'scheduled_start', 'venue_id'])
            ->from($this->database->quoteName('#__joomleague_project_match'))
            ->where($this->database->quoteName('id') . ' = :matchId')
            ->where($this->database->quoteName('round_id') . ' = :roundId')
            ->bind(':matchId', $matchId, ParameterType::INTEGER)
            ->bind(':roundId', $roundId, ParameterType::INTEGER);
        $row = $this->database->setQuery($query)->loadObject();
        return $row ?: null;
    }
}
