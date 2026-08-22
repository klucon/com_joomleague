<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class MatchResultSummaryProvider
{
	public function __construct(private readonly DatabaseInterface $db)
	{
	}

	/** @param list<int> $matchIds @return array<int,list<array<string,mixed>>> */
	public function loadRootValues(array $matchIds): array
	{
		$matchIds = array_values(array_unique(array_filter(array_map('intval', $matchIds), static fn (int $id): bool => $id > 0)));

		if ($matchIds === []) return [];

		$query = $this->db->getQuery(true)
			->select([
				$this->db->quoteName('segment.match_id'),
				$this->db->quoteName('participant.slot_number'),
				$this->db->quoteName('value.numeric_value'),
				$this->db->quoteName('value.text_value'),
				$this->db->quoteName('value.status_code'),
				$this->db->quoteName('value.result_rank'),
			])
			->from($this->db->quoteName('#__joomleague_match_score_segment', 'segment'))
			->innerJoin($this->db->quoteName('#__joomleague_match_score_value', 'value') . ' ON value.segment_id = segment.id AND value.match_id = segment.match_id')
			->innerJoin($this->db->quoteName('#__joomleague_match_participant', 'participant') . ' ON participant.id = value.participant_id AND participant.match_id = segment.match_id')
			->where($this->db->quoteName('segment.parent_id') . ' IS NULL')
			->whereIn($this->db->quoteName('segment.match_id'), $matchIds, ParameterType::INTEGER)
			->order($this->db->quoteName('segment.match_id') . ' ASC')
			->order($this->db->quoteName('participant.slot_number') . ' ASC');
		$values = [];

		foreach ($this->db->setQuery($query)->loadAssocList() as $value) $values[(int) $value['match_id']][] = $value;

		return $values;
	}

	/** @param list<array<string,mixed>> $values */
	public function format(string $resultType, array $values): string
	{
		$displayValues = [];

		foreach ($values as $value) {
			if ($value['numeric_value'] !== null) {
				$display = $resultType === 'time_result'
					? MatchResultDuration::format((string) $value['numeric_value'])
					: rtrim(rtrim((string) $value['numeric_value'], '0'), '.');
				$displayValues[] = $display === '' || $display === '-' ? '0' : $display;
			} elseif ($value['text_value'] !== null) {
				$displayValues[] = (string) $value['text_value'];
			} elseif ($value['status_code'] !== null) {
				$displayValues[] = Text::_('COM_JOOMLEAGUE_RESULT_CODE_' . strtoupper((string) $value['status_code']));
			} elseif ($value['result_rank'] !== null) {
				$displayValues[] = Text::sprintf('COM_JOOMLEAGUE_MATCHRESULT_RANK_VALUE', (int) $value['result_rank']);
			}
		}

		return implode(' / ', $displayValues);
	}
}
