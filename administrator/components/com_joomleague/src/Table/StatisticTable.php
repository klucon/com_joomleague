<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;

final class StatisticTable extends Table
{
	use AssetTableTrait;
	use MediaFieldTrait;

	protected $_supportNullValue = true;
	public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null) { parent::__construct('#__joomleague_statistic', 'id', $db, $dispatcher); }
	public function check(): bool
	{
		if (!parent::check()) { return false; }
		$this->name=trim((string)$this->name); $this->short=trim((string)$this->short) ?: mb_strtoupper(mb_substr($this->name,0,4)); $this->alias=OutputFilter::stringURLSafe(trim((string)$this->alias) ?: $this->name);
		if ($this->name==='') { $this->setError(Text::_('COM_JOOMLEAGUE_STATISTIC_ERROR_NAME_REQUIRED')); return false; }
		if ($this->short==='') { $this->setError(Text::_('COM_JOOMLEAGUE_STATISTIC_ERROR_SHORT_REQUIRED')); return false; }
		if ((int)$this->sports_type_id<1) { $this->setError(Text::_('COM_JOOMLEAGUE_COMMON_ERROR_SPORT_REQUIRED')); return false; }
		$this->normalizeMediaField('icon');
		$classes=['basic','complexsum','complexsumpergame','difference','eventpergame','percentage','pergame','sumevents','sumstats','winpergame'];
		if (!in_array($this->class,$classes,true)) { $this->setError(Text::_('COM_JOOMLEAGUE_STATISTIC_ERROR_CLASS_INVALID')); return false; }
		foreach (['params','baseparams'] as $field) { if (trim((string)$this->$field)==='') { $this->$field='{}'; } if (json_decode((string)$this->$field,true)===null && json_last_error()!==JSON_ERROR_NONE) { $this->setError(Text::_('COM_JOOMLEAGUE_STATISTIC_ERROR_PARAMS_INVALID')); return false; } }
		$this->calculated=$this->class==='basic' ? 0 : 1;
		return true;
	}
}
