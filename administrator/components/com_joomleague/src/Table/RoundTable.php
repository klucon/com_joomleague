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

final class RoundTable extends Table
{
	protected $_supportNullValue = true;
	protected $_trackAssets = false;

	public function __construct(DatabaseDriver $database) { parent::__construct('#__joomleague_project_round', 'id', $database); $this->_trackAssets = false; }

	public function check(): bool
	{
		$this->project_id = (int) ($this->project_id ?? 0); $this->stage_id = (int) ($this->stage_id ?? 0);
		$this->name = StringHelper::trim((string) ($this->name ?? '')); $this->alias = StringHelper::trim((string) ($this->alias ?? ''));
		$this->code = strtolower(StringHelper::trim((string) ($this->code ?? ''))); $this->round_type = strtolower(StringHelper::trim((string) ($this->round_type ?? 'standard')));
		$this->sequence_number = (int) ($this->sequence_number ?? 0); $this->start_date = StringHelper::trim((string) ($this->start_date ?? '')) ?: null;
		$this->end_date = StringHelper::trim((string) ($this->end_date ?? '')) ?: null; $this->lifecycle_state = strtolower(StringHelper::trim((string) ($this->lifecycle_state ?? 'draft')));
		if ($this->alias === '') $this->alias = OutputFilter::stringURLSafe($this->name);

		if ($this->project_id < 1 || $this->stage_id < 1 || $this->name === '' || $this->sequence_number < 1
			|| preg_match('/^[a-z][a-z0-9_]{0,99}$/', $this->code) !== 1 || preg_match('/^[a-z][a-z0-9_]{0,99}$/', $this->round_type) !== 1
			|| !in_array($this->lifecycle_state, ['draft', 'active', 'completed', 'cancelled'], true)
			|| !$this->validDate($this->start_date) || !$this->validDate($this->end_date)
			|| ($this->start_date !== null && $this->end_date !== null && $this->end_date < $this->start_date)) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_ROUND_VALUES_INVALID')); return false;
		}

		$query = $this->getDatabase()->getQuery(true)->select('COUNT(*)')->from($this->getDatabase()->quoteName('#__joomleague_project_stage'))
			->where($this->getDatabase()->quoteName('id') . ' = :stageId')->where($this->getDatabase()->quoteName('project_id') . ' = :projectId')
			->bind(':stageId', $this->stage_id, ParameterType::INTEGER)->bind(':projectId', $this->project_id, ParameterType::INTEGER);
		if ((int) $this->getDatabase()->setQuery($query)->loadResult() !== 1) { $this->setError(Text::_('COM_JOOMLEAGUE_ERROR_ROUND_STAGE_INVALID')); return false; }

		foreach (['code' => $this->code, 'sequence_number' => $this->sequence_number] as $column => $value) {
			$query = $this->getDatabase()->getQuery(true)->select('COUNT(*)')->from($this->getDatabase()->quoteName('#__joomleague_project_round'))
				->where($this->getDatabase()->quoteName('stage_id') . ' = :stageId')->where($this->getDatabase()->quoteName($column) . ' = :value')
				->where($this->getDatabase()->quoteName('id') . ' <> :id')->bind(':stageId', $this->stage_id, ParameterType::INTEGER)
				->bind(':value', $value, $column === 'sequence_number' ? ParameterType::INTEGER : ParameterType::STRING)->bind(':id', $this->id, ParameterType::INTEGER);
			if ((int) $this->getDatabase()->setQuery($query)->loadResult() > 0) { $this->setError(Text::_($column === 'code' ? 'COM_JOOMLEAGUE_ERROR_ROUND_CODE_DUPLICATE' : 'COM_JOOMLEAGUE_ERROR_ROUND_SEQUENCE_DUPLICATE')); return false; }
		}

		return parent::check();
	}

	private function validDate(?string $value): bool
	{
		if ($value === null) return true;
		$date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
		return $date !== false && $date->format('Y-m-d') === $value;
	}
}
