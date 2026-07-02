<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;

final class PositionTable extends Table
{
	use AssetTableTrait;

	protected $_supportNullValue = true;
	public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null) { parent::__construct('#__joomleague_position', 'id', $db, $dispatcher); }
	public function check(): bool
	{
		if (!parent::check()) { return false; }
		$this->name=trim((string)$this->name); $this->alias=OutputFilter::stringURLSafe(trim((string)$this->alias) ?: $this->name);
		if ($this->name==='') { $this->setError(Text::_('COM_JOOMLEAGUE_POSITION_ERROR_NAME_REQUIRED')); return false; }
		if ((int)$this->sports_type_id<1) { $this->setError(Text::_('COM_JOOMLEAGUE_COMMON_ERROR_SPORT_REQUIRED')); return false; }
		if (!in_array((int)$this->persontype,[1,2,3,4],true)) { $this->setError(Text::_('COM_JOOMLEAGUE_POSITION_ERROR_PERSONTYPE_INVALID')); return false; }
		if ((int)$this->parent_id === (int)$this->id && (int)$this->id>0) { $this->setError(Text::_('COM_JOOMLEAGUE_POSITION_ERROR_PARENT_SELF')); return false; }
		return true;
	}
}
