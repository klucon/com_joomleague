<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\String\StringHelper;

final class SporttypeTable extends Table
{
	protected $_supportNullValue = true;
	protected $_trackAssets = false;

	public function __construct(DatabaseDriver $database)
	{
		parent::__construct('#__joomleague_sport_type', 'id', $database);
		$this->_trackAssets = false;
	}

	public function check(): bool
	{
		$this->name = StringHelper::trim((string) $this->name);
		$this->alias = StringHelper::trim((string) $this->alias);
		$this->code = strtolower(StringHelper::trim((string) $this->code));

		if ($this->name === '') {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_SPORTTYPE_NAME_REQUIRED'));
			return false;
		}
		if ($this->code === '' || preg_match('/^[a-z0-9]+(?:_[a-z0-9]+)*$/', $this->code) !== 1) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_SPORTTYPE_CODE_INVALID'));
			return false;
		}
		if ((int) $this->profile_version_id < 1) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_SPORTTYPE_PROFILE_REQUIRED'));
			return false;
		}
		if ($this->alias === '') {
			$this->alias = OutputFilter::stringURLSafe($this->name);
		}

		return parent::check();
	}
}
