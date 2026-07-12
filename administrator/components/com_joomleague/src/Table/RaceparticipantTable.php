<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;

final class RaceparticipantTable extends Table
{
	use AssetTableTrait;

	protected $_supportNullValue = true;

	public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null)
	{
		parent::__construct('#__joomleague_race_participant', 'id', $db, $dispatcher);
	}

	public function check(): bool
	{
		if (!parent::check()) {
			return false;
		}

		$this->project_id = (int) $this->project_id;
		$this->person_id = (int) $this->person_id;
		$this->category_id = (int) $this->category_id ?: null;
		$this->club_id = (int) $this->club_id ?: null;
		$this->team_id = (int) $this->team_id ?: null;
		$this->bib_number = trim((string) $this->bib_number);
		$this->sex = strtoupper(trim((string) $this->sex));
		$this->country = strtoupper(trim((string) $this->country));
		$this->date_of_birth = trim((string) $this->date_of_birth) ?: null;
		$this->note = trim((string) $this->note);

		if ($this->project_id < 1 || $this->person_id < 1) {
			$this->setError(Text::_('COM_JOOMLEAGUE_RACEPARTICIPANT_ERROR_REQUIRED'));
			return false;
		}

		if ($this->bib_number === '') {
			$this->setError(Text::_('COM_JOOMLEAGUE_RACEPARTICIPANT_ERROR_BIB_REQUIRED'));
			return false;
		}

		if ($this->sex !== '' && !in_array($this->sex, ['M', 'F', 'X'], true)) {
			$this->setError(Text::_('COM_JOOMLEAGUE_RACE_ERROR_SEX_INVALID'));
			return false;
		}

		return true;
	}
}
