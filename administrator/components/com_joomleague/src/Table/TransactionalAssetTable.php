<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Table\Asset;

/** PostgreSQL asset writes inside a transaction owned by the caller. */
final class TransactionalAssetTable extends Asset
{
	protected function _lock()
	{
		$db = $this->getDatabase();
		if ($db->getServerType() !== 'postgresql') {
			throw new \LogicException('Transactional asset locks require PostgreSQL.');
		}
		$db->setQuery('LOCK TABLE ' . $db->quoteName($this->_tbl) . ' IN ACCESS EXCLUSIVE MODE')->execute();
		$this->_locked = true;
		return true;
	}

	protected function _unlock()
	{
		// PostgreSQL releases this lock only when the caller commits or rolls back.
		$this->_locked = false;
		return true;
	}
}
