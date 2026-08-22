<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\String\StringHelper;

final class ProjectentryTable extends Table
{
	protected $_supportNullValue = true;
	protected $_trackAssets = false;

	public function __construct(DatabaseDriver $database)
	{
		parent::__construct('#__joomleague_project_entry', 'id', $database);
		$this->_trackAssets = false;
	}

	public function check(): bool
	{
		$this->project_id = (int) ($this->project_id ?? 0);
		$this->entry_kind = strtolower(StringHelper::trim((string) ($this->entry_kind ?? '')));
		$this->team_id = (int) ($this->team_id ?? 0) ?: null;
		$this->person_id = (int) ($this->person_id ?? 0) ?: null;
		$this->display_name = StringHelper::trim((string) ($this->display_name ?? ''));
		$this->entry_code = StringHelper::trim((string) ($this->entry_code ?? '')) ?: null;
		$this->bib_number = StringHelper::trim((string) ($this->bib_number ?? '')) ?: null;
		$this->seed_number = (int) ($this->seed_number ?? 0) ?: null;
		$this->lifecycle_state = strtolower(StringHelper::trim((string) ($this->lifecycle_state ?? 'active')));

		if (StringHelper::strlen($this->display_name) > 255
			|| StringHelper::strlen((string) $this->entry_code) > 100
			|| StringHelper::strlen((string) $this->bib_number) > 50
			|| ($this->seed_number !== null && $this->seed_number < 1)
			|| !in_array($this->lifecycle_state, ['active', 'inactive', 'withdrawn', 'disqualified'], true)) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_PROJECTENTRY_VALUES_INVALID'));

			return false;
		}

		$validTarget = match ($this->entry_kind) {
			'team' => $this->team_id !== null && $this->person_id === null,
			'person' => $this->person_id !== null && $this->team_id === null,
			'group' => $this->team_id === null && $this->person_id === null && $this->display_name !== '',
			default => false,
		};

		if ($this->project_id < 1 || !$validTarget) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_PROJECTENTRY_TARGET_INVALID'));

			return false;
		}

		return parent::check();
	}
}
