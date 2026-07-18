<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;

final class TeamstaffTable extends Table
{
	use AssetTableTrait;
	use MediaFieldTrait;

	protected $_supportNullValue = true;

	public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null)
	{
		parent::__construct('#__joomleague_team_staff', 'id', $db, $dispatcher);
	}

	public function check(): bool
	{
		if (!parent::check()) {
			return false;
		}

		if ((int) $this->projectteam_id < 1 || (int) $this->person_id < 1) {
			$this->setError(Text::_('COM_JOOMLEAGUE_TEAMSTAFF_ERROR_REQUIRED'));
			return false;
		}

		$this->project_position_id = (int) $this->project_position_id ?: null;
		$this->notes = trim((string) $this->notes);
		$this->normalizeMediaField('picture');
		$this->injury_detail = trim((string) $this->injury_detail);
		$this->suspension_detail = trim((string) $this->suspension_detail);
		$this->away_detail = trim((string) $this->away_detail);
		$this->alias = OutputFilter::stringURLSafe(trim((string) $this->alias) ?: 'person-' . (int) $this->person_id);

		foreach (['injury', 'suspension', 'away', 'active', 'published'] as $field) {
			$this->{$field} = (int) $this->{$field};
		}

		foreach (['injury_date_start', 'injury_date_end', 'susp_date_start', 'susp_date_end', 'away_date_start', 'away_date_end', 'checked_out_time', 'modified'] as $field) {
			$this->{$field} = trim((string) $this->{$field}) ?: null;
		}

		return true;
	}
}
