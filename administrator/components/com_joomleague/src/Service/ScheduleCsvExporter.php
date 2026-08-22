<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

final class ScheduleCsvExporter
{
	/** @param array<int,object> $items */
	public function export(array $items): string
	{
		$stream = fopen('php://temp', 'r+');

		fwrite($stream, "\xEF\xBB\xBF");
		fputcsv($stream, [
			Text::_('COM_JOOMLEAGUE_FIELD_MATCH_NUMBER_SHORT_LABEL'),
			Text::_('COM_JOOMLEAGUE_PROJECTSCHEDULE_COLUMN_STAGE_ROUND'),
			Text::_('COM_JOOMLEAGUE_MATCH_COLUMN_PARTICIPANTS'),
			Text::_('COM_JOOMLEAGUE_MATCH_COLUMN_DATE'),
			Text::_('COM_JOOMLEAGUE_PROJECTSCHEDULE_COLUMN_VENUE'),
			Text::_('JSTATUS'),
			Text::_('COM_JOOMLEAGUE_PROJECTSCHEDULE_COLUMN_RESULT'),
		], ';', '"', '\\');

		foreach ($items as $item) {
			$statusKey = 'COM_JOOMLEAGUE_MATCH_STATUS_' . strtoupper((string) $item->status_code);
			$statusLabel = Text::_($statusKey);
			if ($statusLabel === $statusKey) {
				$statusLabel = (string) $item->status_code;
			}

			fputcsv($stream, [
				(string) $item->match_number,
				$item->stage_name . ' / ' . $item->round_name,
				implode(' – ', $item->participant_names),
				(string) $item->scheduled_display,
				(string) ($item->venue_name ?? ''),
				$statusLabel,
				(string) ($item->result_display ?? ''),
			], ';', '"', '\\');
		}

		rewind($stream);
		$csv = stream_get_contents($stream);
		fclose($stream);

		return (string) $csv;
	}
}
