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
use Joomla\Database\ParameterType;
use Joomla\Event\DispatcherInterface;

final class LeagueTable extends Table
{
	use AssetTableTrait;

	protected $_supportNullValue = true;

	public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null)
	{
		parent::__construct('#__joomleague_league', 'id', $db, $dispatcher);
	}

	public function check(): bool
	{
		if (!parent::check()) {
			return false;
		}

		$this->name = trim((string) $this->name);
		$this->short_name = trim((string) $this->short_name);
		$this->alias = OutputFilter::stringURLSafe(trim((string) $this->alias) ?: $this->name);

		if ($this->name === '') {
			$this->setError(Text::_('COM_JOOMLEAGUE_LEAGUE_ERROR_NAME_REQUIRED'));

			return false;
		}

		if ($this->short_name === '') {
			$this->setError(Text::_('COM_JOOMLEAGUE_LEAGUE_ERROR_SHORT_NAME_REQUIRED'));

			return false;
		}

		$db = $this->getDatabase();
		$id = (int) $this->id;
		$query = $db->createQuery()
			->select('COUNT(*)')
			->from($db->quoteName('#__joomleague_league'))
			->where($db->quoteName('name') . ' = :name')
			->where($db->quoteName('id') . ' <> :id')
			->bind(':name', $this->name)
			->bind(':id', $id, ParameterType::INTEGER);
		$db->setQuery($query);

		if ((int) $db->loadResult() > 0) {
			$this->setError(Text::_('COM_JOOMLEAGUE_LEAGUE_ERROR_NAME_NOT_UNIQUE'));

			return false;
		}

		return true;
	}
}
