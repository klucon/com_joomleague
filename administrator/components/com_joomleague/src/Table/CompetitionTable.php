<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\String\StringHelper;

final class CompetitionTable extends Table
{
	protected $_supportNullValue = true;
	protected $_trackAssets = false;

	public function __construct(DatabaseDriver $database)
	{
		parent::__construct('#__joomleague_competition', 'id', $database);
		$this->_trackAssets = false;
	}

	public function check(): bool
	{
		$this->name = StringHelper::trim((string) $this->name);
		$this->alias = StringHelper::trim((string) $this->alias);
		$this->middle_name = StringHelper::trim((string) $this->middle_name);
		$this->short_name = StringHelper::trim((string) $this->short_name);
		$this->country_code = strtoupper(StringHelper::trim((string) ($this->country_code ?? ''))) ?: null;

		if ($this->name === '') {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_COMPETITION_NAME_REQUIRED'));

			return false;
		}

		if ($this->alias === '') {
			$this->alias = OutputFilter::stringURLSafe($this->name);
		}

		if ($this->uuid !== '' && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', (string) $this->uuid) !== 1) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_UUID_INVALID'));

			return false;
		}

		return parent::check();
	}
}
