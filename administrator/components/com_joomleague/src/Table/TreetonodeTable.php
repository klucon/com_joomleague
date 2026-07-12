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

final class TreetonodeTable extends Table
{
	protected $_supportNullValue = true;

	public function __construct(DatabaseInterface $database, ?DispatcherInterface $dispatcher = null)
	{
		parent::__construct('#__joomleague_treeto_node', 'id', $database, $dispatcher);
	}

	public function check(): bool
	{
		if (!parent::check()) {
			return false;
		}

		$this->treeto_id = (int) $this->treeto_id;
		$this->node = (int) $this->node;
		$this->row = (int) $this->row;
		$this->team_id = (int) $this->team_id ?: null;
		$this->title = trim((string) $this->title);
		$this->content = trim((string) $this->content);
		$this->published = (int) $this->published;

		if ($this->treeto_id < 1 || $this->node < 1) {
			$this->setError(Text::_('COM_JOOMLEAGUE_TREETONODE_ERROR_REQUIRED'));

			return false;
		}

		return true;
	}
}
