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

final class TreetoTable extends Table
{
	protected $_supportNullValue = true;

	public function __construct(DatabaseInterface $database, ?DispatcherInterface $dispatcher = null)
	{
		parent::__construct('#__joomleague_treeto', 'id', $database, $dispatcher);
	}

	public function check(): bool
	{
		if (!parent::check()) {
			return false;
		}

		$this->project_id = (int) $this->project_id ?: null;
		$this->division_id = (int) $this->division_id ?: null;
		$this->tree_i = max(0, (int) $this->tree_i);
		$this->name = trim((string) $this->name);
		$this->published = (int) $this->published;

		if ($this->project_id === null || $this->name === '') {
			$this->setError(Text::_('COM_JOOMLEAGUE_TREETO_ERROR_REQUIRED'));

			return false;
		}

		return true;
	}
}
