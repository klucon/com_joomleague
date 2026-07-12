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

final class RacecategoryTable extends Table
{
	use AssetTableTrait;

	protected $_supportNullValue = true;

	public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null)
	{
		parent::__construct('#__joomleague_race_category', 'id', $db, $dispatcher);
	}

	public function check(): bool
	{
		if (!parent::check()) {
			return false;
		}

		$this->project_id = (int) $this->project_id;
		$this->name = trim((string) $this->name);
		$this->alias = OutputFilter::stringURLSafe(trim((string) $this->alias) ?: $this->name);
		$this->sex = strtoupper(trim((string) $this->sex)) ?: 'ANY';
		$this->age_min = $this->age_min === '' ? null : (int) $this->age_min;
		$this->age_max = $this->age_max === '' ? null : (int) $this->age_max;

		if ($this->project_id < 1) {
			$this->setError(Text::_('COM_JOOMLEAGUE_RACE_ERROR_PROJECT_REQUIRED'));
			return false;
		}

		if ($this->name === '') {
			$this->setError(Text::_('COM_JOOMLEAGUE_RACECATEGORY_ERROR_NAME_REQUIRED'));
			return false;
		}

		if (!in_array($this->sex, ['ANY', 'M', 'F', 'X'], true)) {
			$this->setError(Text::_('COM_JOOMLEAGUE_RACE_ERROR_SEX_INVALID'));
			return false;
		}

		if ($this->age_min !== null && $this->age_max !== null && $this->age_min > $this->age_max) {
			$this->setError(Text::_('COM_JOOMLEAGUE_RACECATEGORY_ERROR_AGE_RANGE_INVALID'));
			return false;
		}

		return true;
	}
}
