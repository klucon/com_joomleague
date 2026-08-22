<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;
use Joomla\String\StringHelper;

final class MatchTable extends Table
{
	protected $_supportNullValue = true;
	protected $_trackAssets = false;
	public function __construct(DatabaseDriver $database) { parent::__construct('#__joomleague_project_match', 'id', $database); $this->_trackAssets = false; }
	public function check(): bool
	{
		foreach (['project_id', 'stage_id', 'round_id', 'venue_id', 'duration_minutes', 'attendance'] as $field) $this->$field = (int) ($this->$field ?? 0) ?: null;
		$this->project_id = (int) ($this->project_id ?? 0); $this->stage_id = (int) ($this->stage_id ?? 0); $this->round_id = (int) ($this->round_id ?? 0);
		foreach (['code', 'match_number', 'contest_type', 'timezone', 'status_code'] as $field) $this->$field = StringHelper::trim((string) ($this->$field ?? '')) ?: null;
		$this->contest_type ??= 'head_to_head'; $this->status_code ??= 'scheduled'; $this->scheduled_start = StringHelper::trim((string) ($this->scheduled_start ?? '')) ?: null;
		if ($this->project_id < 1 || $this->stage_id < 1 || $this->round_id < 1 || preg_match('/^[a-z][a-z0-9_]{0,99}$/', $this->contest_type) !== 1
			|| preg_match('/^[a-z][a-z0-9_]{0,99}$/', $this->status_code) !== 1 || ($this->code !== null && preg_match('/^[a-z][a-z0-9_]{0,99}$/', $this->code) !== 1)
			|| ($this->duration_minutes !== null && $this->duration_minutes < 1) || ($this->attendance !== null && $this->attendance < 0)) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_MATCH_VALUES_INVALID')); return false;
		}
		if ($this->timezone !== null) {
			try { new \DateTimeZone($this->timezone); } catch (\Exception) { $this->setError(Text::_('COM_JOOMLEAGUE_ERROR_MATCH_TIMEZONE_INVALID')); return false; }
		}
		$query = $this->getDatabase()->getQuery(true)->select('COUNT(*)')->from($this->getDatabase()->quoteName('#__joomleague_project_round'))
			->where($this->getDatabase()->quoteName('id') . ' = :roundId')->where($this->getDatabase()->quoteName('stage_id') . ' = :stageId')->where($this->getDatabase()->quoteName('project_id') . ' = :projectId')
			->bind(':roundId', $this->round_id, ParameterType::INTEGER)->bind(':stageId', $this->stage_id, ParameterType::INTEGER)->bind(':projectId', $this->project_id, ParameterType::INTEGER);
		if ((int) $this->getDatabase()->setQuery($query)->loadResult() !== 1) { $this->setError(Text::_('COM_JOOMLEAGUE_ERROR_MATCH_ROUND_INVALID')); return false; }
		return parent::check();
	}
}
