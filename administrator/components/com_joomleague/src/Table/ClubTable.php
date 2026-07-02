<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Asset;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Event\DispatcherInterface;

final class ClubTable extends Table
{
	use MediaFieldTrait;

	protected $_supportNullValue = true;

	public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null)
	{
		parent::__construct('#__joomleague_club', 'id', $db, $dispatcher);
	}

	public function check(): bool
	{
		if (!parent::check()) { return false; }
		$this->name = trim((string) $this->name);
		$this->alias = OutputFilter::stringURLSafe(trim((string) $this->alias) ?: $this->name);

		if ($this->name === '') { $this->setError(Text::_('COM_JOOMLEAGUE_CLUB_ERROR_NAME_REQUIRED')); return false; }
		if ($this->email !== '' && filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) { $this->setError(Text::_('COM_JOOMLEAGUE_CLUB_ERROR_EMAIL_INVALID')); return false; }
		if ($this->founded && $this->dissolved && $this->dissolved < $this->founded) { $this->setError(Text::_('COM_JOOMLEAGUE_CLUB_ERROR_DATES_INVALID')); return false; }
		$this->normalizeMediaFields(['logo_small', 'logo_middle', 'logo_big']);

		$db = $this->getDatabase(); $id = (int) $this->id;
		$query = $db->createQuery()->select('COUNT(*)')->from($db->quoteName('#__joomleague_club'))
			->where($db->quoteName('name') . ' = :name')->where($db->quoteName('id') . ' <> :id')
			->bind(':name', $this->name)->bind(':id', $id, ParameterType::INTEGER);
		$db->setQuery($query);

		if ((int) $db->loadResult() > 0) { $this->setError(Text::_('COM_JOOMLEAGUE_CLUB_ERROR_NAME_NOT_UNIQUE')); return false; }

		return true;
	}

	protected function _getAssetName(): string
	{
		return 'com_joomleague.club.' . (int) $this->id;
	}

	protected function _getAssetTitle(): string
	{
		return $this->name;
	}

	protected function _getAssetParentId(?Table $table = null, $id = null): int
	{
		$asset = new Asset($this->getDatabase(), $this->getDispatcher());

		return $asset->loadByName('com_joomleague') ? (int) $asset->id : parent::_getAssetParentId($table, $id);
	}
}
