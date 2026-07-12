<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\Service\RaceTimeParserService;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;

final class RaceresultTable extends Table
{
	use AssetTableTrait;

	protected $_supportNullValue = true;

	public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null)
	{
		parent::__construct('#__joomleague_race_result', 'id', $db, $dispatcher);
	}

	public function check(): bool
	{
		if (!parent::check()) {
			return false;
		}

		$this->project_id = (int) $this->project_id;
		$this->round_id = (int) $this->round_id ?: null;
		$this->participant_id = (int) $this->participant_id;
		$this->person_id = (int) $this->person_id ?: null;
		$this->category_id = (int) $this->category_id ?: null;
		$this->bib_number = trim((string) $this->bib_number);
		$this->status = strtoupper(trim((string) $this->status)) ?: 'FINISHED';
		$this->duration_text = trim((string) $this->duration_text);
		$this->duration_ms = $this->duration_ms === '' ? null : (int) $this->duration_ms;
		$this->overall_place = (int) $this->overall_place ?: null;
		$this->category_place = (int) $this->category_place ?: null;
		$this->sex_place = (int) $this->sex_place ?: null;
		$this->start_time = trim((string) $this->start_time) ?: null;
		$this->finish_time = trim((string) $this->finish_time) ?: null;
		$this->status_note = trim((string) $this->status_note);

		if ($this->project_id < 1 || $this->participant_id < 1) {
			$this->setError(Text::_('COM_JOOMLEAGUE_RACERESULT_ERROR_REQUIRED'));
			return false;
		}

		if (!in_array($this->status, ['FINISHED', 'DNS', 'DNF', 'DSQ', 'NC'], true)) {
			$this->setError(Text::_('COM_JOOMLEAGUE_RACERESULT_ERROR_STATUS_INVALID'));
			return false;
		}

		if ($this->status === 'FINISHED' && $this->duration_ms === null && $this->duration_text !== '') {
			$this->duration_ms = (new RaceTimeParserService())->parseToMilliseconds($this->duration_text);
		}

		if ($this->status === 'FINISHED' && $this->duration_ms === null) {
			$this->setError(Text::_('COM_JOOMLEAGUE_RACERESULT_ERROR_TIME_REQUIRED'));
			return false;
		}

		if ($this->duration_ms !== null && $this->duration_text === '') {
			$this->duration_text = (new RaceTimeParserService())->formatMilliseconds((int) $this->duration_ms);
		}

		return true;
	}
}
