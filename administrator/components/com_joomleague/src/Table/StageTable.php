<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;
use Joomla\String\StringHelper;

final class StageTable extends Table
{
	protected $_supportNullValue = true;
	protected $_trackAssets = false;

	public function __construct(DatabaseDriver $database)
	{
		parent::__construct('#__joomleague_project_stage', 'id', $database);
		$this->_trackAssets = false;
	}

	public function check(): bool
	{
		$this->project_id = (int) ($this->project_id ?? 0);
		$this->parent_id = (int) ($this->parent_id ?? 0) ?: null;
		$this->name = StringHelper::trim((string) ($this->name ?? ''));
		$this->alias = StringHelper::trim((string) ($this->alias ?? ''));
		$this->code = strtolower(StringHelper::trim((string) ($this->code ?? '')));
		$this->stage_type = strtolower(StringHelper::trim((string) ($this->stage_type ?? '')));
		$this->entry_selection_mode = strtolower(StringHelper::trim((string) ($this->entry_selection_mode ?? 'inherit_project')));
		$this->sequence_number = (int) ($this->sequence_number ?? 0) ?: null;
		$this->start_date = StringHelper::trim((string) ($this->start_date ?? '')) ?: null;
		$this->end_date = StringHelper::trim((string) ($this->end_date ?? '')) ?: null;

		if ($this->alias === '') {
			$this->alias = OutputFilter::stringURLSafe($this->name);
		}

		if ($this->project_id < 1 || $this->name === ''
			|| preg_match('/^[a-z][a-z0-9_]{0,99}$/', $this->code) !== 1
			|| preg_match('/^[a-z][a-z0-9_]{0,99}$/', $this->stage_type) !== 1
			|| !in_array($this->entry_selection_mode, ['inherit_project', 'explicit'], true)
			|| ($this->sequence_number !== null && $this->sequence_number < 1)
			|| !$this->validDate($this->start_date) || !$this->validDate($this->end_date)
			|| ($this->start_date !== null && $this->end_date !== null && $this->end_date < $this->start_date)) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_STAGE_VALUES_INVALID'));

			return false;
		}

		if (!$this->uniqueCode() || !$this->validParent()) {
			return false;
		}

		return parent::check();
	}

	private function uniqueCode(): bool
	{
		$query = $this->getDatabase()->getQuery(true)
			->select('COUNT(*)')->from($this->getDatabase()->quoteName('#__joomleague_project_stage'))
			->where($this->getDatabase()->quoteName('project_id') . ' = :projectId')
			->where($this->getDatabase()->quoteName('code') . ' = :code')
			->where($this->getDatabase()->quoteName('id') . ' <> :id')
			->bind(':projectId', $this->project_id, ParameterType::INTEGER)
			->bind(':code', $this->code)
			->bind(':id', $this->id, ParameterType::INTEGER);

		if ((int) $this->getDatabase()->setQuery($query)->loadResult() > 0) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_STAGE_CODE_DUPLICATE'));

			return false;
		}

		return true;
	}

	private function validParent(): bool
	{
		if ($this->parent_id === null) {
			return true;
		}

		$id = (int) $this->id;
		$parentId = (int) $this->parent_id;
		$visited = [];

		while ($parentId > 0) {
			if ($parentId === $id || isset($visited[$parentId])) {
				$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_STAGE_PARENT_INVALID'));

				return false;
			}

			$visited[$parentId] = true;
			$query = $this->getDatabase()->getQuery(true)
				->select([$this->getDatabase()->quoteName('project_id'), $this->getDatabase()->quoteName('parent_id')])
				->from($this->getDatabase()->quoteName('#__joomleague_project_stage'))
				->where($this->getDatabase()->quoteName('id') . ' = :parentId')
				->bind(':parentId', $parentId, ParameterType::INTEGER);
			$parent = $this->getDatabase()->setQuery($query)->loadObject();

			if (!$parent || (int) $parent->project_id !== $this->project_id) {
				$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_STAGE_PARENT_INVALID'));

				return false;
			}

			$parentId = (int) ($parent->parent_id ?? 0);
		}

		return true;
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
