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

final class EventtypeTable extends Table
{
	use AssetTableTrait;
	use MediaFieldTrait;

	protected $_supportNullValue = true;
	public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null) { parent::__construct('#__joomleague_eventtype', 'id', $db, $dispatcher); }
	public function check(): bool
	{
		if (!parent::check()) { return false; }
		$this->name = trim((string) $this->name); $this->alias = OutputFilter::stringURLSafe(trim((string) $this->alias) ?: $this->name);
		if ($this->name === '') { $this->setError(Text::_('COM_JOOMLEAGUE_EVENTTYPE_ERROR_NAME_REQUIRED')); return false; }
		if ((int) $this->sports_type_id < 1) { $this->setError(Text::_('COM_JOOMLEAGUE_COMMON_ERROR_SPORT_REQUIRED')); return false; }
		if (!in_array($this->direction, ['ASC', 'DESC'], true)) { $this->direction = 'DESC'; }
		$this->normalizeMediaField('icon');
		$db=$this->getDatabase(); $id=(int)$this->id; $sport=(int)$this->sports_type_id; $parent=(int)$this->parent;
		$query=$db->createQuery()->select('COUNT(*)')->from($db->quoteName('#__joomleague_eventtype'))->where($db->quoteName('name').' = :name')->where($db->quoteName('sports_type_id').' = :sport')->where('COALESCE('.$db->quoteName('parent').',0) = :parent')->where($db->quoteName('id').' <> :id')->bind(':name',$this->name)->bind(':sport',$sport,ParameterType::INTEGER)->bind(':parent',$parent,ParameterType::INTEGER)->bind(':id',$id,ParameterType::INTEGER); $db->setQuery($query);
		if ((int)$db->loadResult()>0) { $this->setError(Text::_('COM_JOOMLEAGUE_EVENTTYPE_ERROR_NOT_UNIQUE')); return false; }
		return true;
	}
}
