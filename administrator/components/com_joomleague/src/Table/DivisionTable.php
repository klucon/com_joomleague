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

final class DivisionTable extends Table
{
	use AssetTableTrait;
	use MediaFieldTrait;

	protected $_supportNullValue = true;

	public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null)
	{
		parent::__construct('#__joomleague_division', 'id', $db, $dispatcher);
	}

	public function check(): bool
	{
		if (!parent::check()) {
			return false;
		}

		$this->name = trim((string) $this->name);
		$this->alias = OutputFilter::stringURLSafe(trim((string) $this->alias) ?: $this->name);
		$this->shortname = trim((string) $this->shortname);
		$this->notes = trim((string) $this->notes);
		$this->normalizeMediaField('picture');
		$this->parent_id = (int) $this->parent_id ?: null;

		if ((int) $this->project_id < 1 || $this->name === '') {
			$this->setError(Text::_('COM_JOOMLEAGUE_DIVISION_ERROR_REQUIRED'));

			return false;
		}

		if ((int) $this->id > 0 && (int) $this->parent_id === (int) $this->id) {
			$this->setError(Text::_('COM_JOOMLEAGUE_DIVISION_ERROR_PARENT_SELF'));

			return false;
		}

		return true;
	}
}
