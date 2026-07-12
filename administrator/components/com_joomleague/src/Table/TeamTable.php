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
use Joomla\CMS\Table\Asset;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;

final class TeamTable extends Table
{
	use MediaFieldTrait;

	protected $_supportNullValue = true;
	public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null) { parent::__construct('#__joomleague_team', 'id', $db, $dispatcher); }
	public function check(): bool
	{
		if (!parent::check()) { return false; }
		$this->name = trim((string) $this->name);
		$this->short_name = trim((string) $this->short_name);
		$this->middle_name = trim((string) $this->middle_name) ?: mb_substr($this->name, 0, 25);
		$this->alias = OutputFilter::stringURLSafe(trim((string) $this->alias) ?: $this->name);
		if ($this->name === '') { $this->setError(Text::_('COM_JOOMLEAGUE_TEAM_ERROR_NAME_REQUIRED')); return false; }
		if ($this->short_name === '') { $this->setError(Text::_('COM_JOOMLEAGUE_TEAM_ERROR_SHORT_REQUIRED')); return false; }
		if ((int) $this->club_id < 1) { $this->setError(Text::_('COM_JOOMLEAGUE_TEAM_ERROR_CLUB_REQUIRED')); return false; }
		$this->normalizeMediaField('picture');
		return true;
	}
	protected function _getAssetName(): string { return 'com_joomleague.team.' . (int) $this->id; }
	protected function _getAssetTitle(): string { return $this->name; }
	protected function _getAssetParentId(?Table $table = null, $id = null): int
	{
		$asset = new Asset($this->getDatabase(), $this->getDispatcher());
		return $asset->loadByName('com_joomleague') ? (int) $asset->id : parent::_getAssetParentId($table, $id);
	}
}
