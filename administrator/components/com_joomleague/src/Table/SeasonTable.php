<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Event\DispatcherInterface;

final class SeasonTable extends Table
{
	use AssetTableTrait;

	protected $_supportNullValue = true;

	public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null)
	{
		parent::__construct('#__joomleague_season', 'id', $db, $dispatcher);
	}

	public function check(): bool
	{
		if (!parent::check()) {
			return false;
		}

		$this->name = trim((string) $this->name);
		$this->alias = OutputFilter::stringURLSafe(trim((string) $this->alias) ?: $this->name);

		if ($this->name === '') {
			$this->setError(Text::_('COM_JOOMLEAGUE_SEASON_ERROR_NAME_REQUIRED'));

			return false;
		}

		$db = $this->getDatabase();
		$id = (int) $this->id;
		$query = $db->createQuery()->select('COUNT(*)')->from($db->quoteName('#__joomleague_season'))
			->where($db->quoteName('name') . ' = :name')->where($db->quoteName('id') . ' <> :id')
			->bind(':name', $this->name)->bind(':id', $id, ParameterType::INTEGER);
		$db->setQuery($query);

		if ((int) $db->loadResult() > 0) {
			$this->setError(Text::_('COM_JOOMLEAGUE_SEASON_ERROR_NAME_NOT_UNIQUE'));

			return false;
		}

		return true;
	}
}
