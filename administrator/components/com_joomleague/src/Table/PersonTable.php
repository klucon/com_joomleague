<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\String\StringHelper;

final class PersonTable extends Table
{
	protected $_supportNullValue = true;
	protected $_trackAssets = false;

	public function __construct(DatabaseDriver $database)
	{
		parent::__construct('#__joomleague_person', 'id', $database);
		$this->_trackAssets = false;
	}

	public function check(): bool
	{
		$this->contact_id = (int) ($this->contact_id ?? 0) ?: null;
		$this->club_id = (int) ($this->club_id ?? 0) ?: null;
		$this->first_name = StringHelper::trim((string) $this->first_name);
		$this->last_name = StringHelper::trim((string) $this->last_name);
		$this->nickname = StringHelper::trim((string) $this->nickname);
		$this->alias = StringHelper::trim((string) $this->alias);
		$this->country_code = strtoupper(StringHelper::trim((string) ($this->country_code ?? ''))) ?: null;
		$this->birth_date = StringHelper::trim((string) ($this->birth_date ?? '')) ?: null;
		$this->death_date = StringHelper::trim((string) ($this->death_date ?? '')) ?: null;
		$this->picture = StringHelper::trim((string) ($this->picture ?? '')) ?: null;
		$this->external_code = StringHelper::trim((string) ($this->external_code ?? '')) ?: null;

		if ($this->first_name === '' && $this->last_name === '') {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_PERSON_NAME_REQUIRED'));

			return false;
		}

		if ($this->alias === '') {
			$this->alias = OutputFilter::stringURLSafe(trim($this->first_name . ' ' . $this->last_name));
		}

		if ($this->country_code !== null && preg_match('/^[A-Z]{2,8}$/', $this->country_code) !== 1) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_COUNTRY_CODE_INVALID'));

			return false;
		}

		if ($this->birth_date !== null && $this->death_date !== null && $this->death_date < $this->birth_date) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_PERSON_DATES_INVALID'));

			return false;
		}

		if ($this->uuid !== '' && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', (string) $this->uuid) !== 1) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_UUID_INVALID'));

			return false;
		}

		return parent::check();
	}
}
