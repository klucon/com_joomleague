<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\Service\RaceTimeParserService;

final class RaceresultModel extends EntityAdminModel
{
	protected string $entityName = 'raceresult';

	protected function prepareTable($table): void
	{
		$table->project_id = (int) $table->project_id;
		$table->round_id = (int) $table->round_id ?: null;
		$table->participant_id = (int) $table->participant_id;
		$table->status = strtoupper(trim((string) $table->status)) ?: 'FINISHED';
		$table->duration_text = trim((string) $table->duration_text);
		$table->duration_ms = $table->duration_ms === '' ? null : (int) $table->duration_ms;
		$table->overall_place = (int) $table->overall_place ?: null;
		$table->category_place = (int) $table->category_place ?: null;
		$table->sex_place = (int) $table->sex_place ?: null;
		$table->start_time = trim((string) $table->start_time) ?: null;
		$table->finish_time = trim((string) $table->finish_time) ?: null;
		$table->status_note = trim((string) $table->status_note);

		$this->hydrateParticipantSnapshot($table);

		if ($table->duration_ms === null && $table->duration_text !== '') {
			$table->duration_ms = (new RaceTimeParserService())->parseToMilliseconds($table->duration_text);
		}

		parent::prepareTable($table);
	}

	private function hydrateParticipantSnapshot(object $table): void
	{
		if ((int) $table->participant_id < 1) {
			return;
		}

		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select($db->quoteName(['person_id', 'category_id', 'bib_number']))
			->from($db->quoteName('#__joomleague_race_participant'))
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $table->participant_id);
		$row = $db->setQuery($query)->loadObject();

		if ($row === null) {
			return;
		}

		$table->person_id = (int) $row->person_id ?: null;
		$table->category_id = (int) $row->category_id ?: null;
		$table->bib_number = trim((string) $table->bib_number) ?: (string) $row->bib_number;
	}
}
