<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

final class RaceparticipantModel extends EntityAdminModel
{
	protected string $entityName = 'raceparticipant';

	protected function prepareTable($table): void
	{
		$table->project_id = (int) $table->project_id;
		$table->person_id = (int) $table->person_id;
		$table->category_id = (int) $table->category_id ?: null;
		$table->club_id = (int) $table->club_id ?: null;
		$table->team_id = (int) $table->team_id ?: null;
		$table->bib_number = trim((string) $table->bib_number);
		$table->sex = strtoupper(trim((string) $table->sex));
		$table->date_of_birth = trim((string) $table->date_of_birth) ?: null;
		$table->country = strtoupper(trim((string) $table->country));
		$table->note = trim((string) $table->note);
		parent::prepareTable($table);
	}
}
