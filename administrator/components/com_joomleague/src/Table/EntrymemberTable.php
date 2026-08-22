<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\String\StringHelper;

final class EntrymemberTable extends Table
{
	protected $_supportNullValue = true;
	protected $_trackAssets = false;

	public function __construct(DatabaseDriver $database)
	{
		parent::__construct('#__joomleague_project_entry_member', 'id', $database);
		$this->_trackAssets = false;
	}

	public function check(): bool
	{
		$this->entry_id = (int) ($this->entry_id ?? 0);
		$this->person_id = (int) ($this->person_id ?? 0);
		$this->member_person_type = strtolower(StringHelper::trim((string) ($this->member_person_type ?? '')));
		$this->role_code = StringHelper::trim((string) ($this->role_code ?? '')) ?: null;
		$this->shirt_number = StringHelper::trim((string) ($this->shirt_number ?? '')) ?: null;
		$this->valid_from = StringHelper::trim((string) ($this->valid_from ?? '')) ?: null;
		$this->valid_until = StringHelper::trim((string) ($this->valid_until ?? '')) ?: null;
		$this->lifecycle_state = strtolower(StringHelper::trim((string) ($this->lifecycle_state ?? 'active')));
		$this->is_captain = !empty($this->is_captain) ? 1 : 0;

		if ($this->entry_id < 1 || $this->person_id < 1 || preg_match('/^[a-z][a-z0-9_]{0,49}$/', $this->member_person_type) !== 1
			|| StringHelper::strlen((string) $this->role_code) > 100 || StringHelper::strlen((string) $this->shirt_number) > 20
			|| !in_array($this->lifecycle_state, ['active', 'inactive', 'injured', 'suspended', 'departed'], true)
			|| !$this->validDate($this->valid_from) || !$this->validDate($this->valid_until)
			|| ($this->valid_from !== null && $this->valid_until !== null && $this->valid_until < $this->valid_from)) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_ENTRYMEMBER_VALUES_INVALID'));

			return false;
		}

		return parent::check();
	}

	private function validDate(?string $value): bool
	{
		if ($value === null) {
			return true;
		}

		$date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

		return $date !== false && $date->format('Y-m-d') === $value;
	}
}
