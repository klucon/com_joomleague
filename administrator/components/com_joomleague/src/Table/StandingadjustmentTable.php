<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;
use Joomla\String\StringHelper;

final class StandingadjustmentTable extends Table
{
	protected $_supportNullValue = true;
	protected $_trackAssets = false;
	public function __construct(DatabaseDriver $database) { parent::__construct('#__joomleague_standing_adjustment', 'id', $database); $this->_trackAssets = false; }

	public function check(): bool
	{
		$this->project_id = (int) ($this->project_id ?? 0); $this->stage_id = (int) ($this->stage_id ?? 0) ?: null; $this->stage_key = $this->stage_id ?? 0;
		$this->project_entry_id = (int) ($this->project_entry_id ?? 0); $this->scope_code = strtolower(StringHelper::trim((string) ($this->scope_code ?? 'all'))); $this->metric_code = strtolower(StringHelper::trim((string) ($this->metric_code ?? '')));
		$this->reason = StringHelper::trim((string) ($this->reason ?? '')); $this->effective_date = StringHelper::trim((string) ($this->effective_date ?? '')) ?: null;
		$value = StringHelper::trim((string) ($this->adjustment_value ?? ''));
		if ($this->project_id < 1 || $this->project_entry_id < 1 || preg_match('/^[a-z][a-z0-9_]*$/', $this->scope_code) !== 1 || preg_match('/^[a-z][a-z0-9_]*$/', $this->metric_code) !== 1 || preg_match('/^-?\d{1,21}(?:\.\d{1,9})?$/', $value) !== 1 || (float) $value == 0.0 || $this->reason === '' || StringHelper::strlen($this->reason) > 500 || !$this->validDate($this->effective_date)) { $this->setError(Text::_('COM_JOOMLEAGUE_ERROR_STANDING_ADJUSTMENT_VALUES_INVALID')); return false; }
		$this->adjustment_value = $value;
		$query = $this->getDatabase()->getQuery(true)->select('COUNT(*)')->from($this->getDatabase()->quoteName('#__joomleague_project_entry'))->where('id = :entry')->where('project_id = :project')->bind(':entry', $this->project_entry_id, ParameterType::INTEGER)->bind(':project', $this->project_id, ParameterType::INTEGER);
		if ((int) $this->getDatabase()->setQuery($query)->loadResult() !== 1) { $this->setError(Text::_('COM_JOOMLEAGUE_ERROR_STANDING_ADJUSTMENT_ENTRY_INVALID')); return false; }
		if ($this->stage_id !== null) {
			$query = $this->getDatabase()->getQuery(true)->select('entry_selection_mode')->from($this->getDatabase()->quoteName('#__joomleague_project_stage'))->where('id = :stage')->where('project_id = :project')->bind(':stage', $this->stage_id, ParameterType::INTEGER)->bind(':project', $this->project_id, ParameterType::INTEGER);
			$selectionMode = $this->getDatabase()->setQuery($query)->loadResult();
			if (!is_string($selectionMode)) { $this->setError(Text::_('COM_JOOMLEAGUE_ERROR_STANDING_ADJUSTMENT_STAGE_INVALID')); return false; }
			if ($selectionMode === 'explicit') {
				$query = $this->getDatabase()->getQuery(true)->select('COUNT(*)')->from($this->getDatabase()->quoteName('#__joomleague_stage_entry'))->where('stage_id = :stage')->where('project_id = :project')->where('entry_id = :entry')->bind(':stage', $this->stage_id, ParameterType::INTEGER)->bind(':project', $this->project_id, ParameterType::INTEGER)->bind(':entry', $this->project_entry_id, ParameterType::INTEGER);
				if ((int) $this->getDatabase()->setQuery($query)->loadResult() !== 1) { $this->setError(Text::_('COM_JOOMLEAGUE_ERROR_STANDING_ADJUSTMENT_STAGE_ENTRY_INVALID')); return false; }
			}
		}
		$query = $this->getDatabase()->getQuery(true)->select('version.payload_json')->from($this->getDatabase()->quoteName('#__joomleague_project', 'project'))->innerJoin($this->getDatabase()->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')->where('project.id = :project')->bind(':project', $this->project_id, ParameterType::INTEGER);
		$profile = json_decode((string) $this->getDatabase()->setQuery($query)->loadResult(), true); $contract = $profile['standings']['calculation'] ?? [];
		if (!is_array($contract) || $contract === []) { $query = $this->getDatabase()->getQuery(true)->select('version.payload_json')->from($this->getDatabase()->quoteName('#__joomleague_project', 'project'))->innerJoin($this->getDatabase()->quoteName('#__joomleague_sport_profile_version', 'pinned') . ' ON pinned.id = project.profile_version_id')->innerJoin($this->getDatabase()->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.profile_id = pinned.profile_id AND version.state = ' . $this->getDatabase()->quote('active'))->where('project.id = :project')->bind(':project', $this->project_id, ParameterType::INTEGER)->order('version.id DESC'); $current = json_decode((string) $this->getDatabase()->setQuery($query, 0, 1)->loadResult(), true); $contract = $current['standings']['calculation'] ?? []; }
		$scopes = ['all']; foreach ($contract['scopes'] ?? [] as $scope) if (is_array($scope)) $scopes[] = $scope['code'] ?? '';
		$metrics = []; foreach ($contract['metrics'] ?? [] as $metric) if (is_array($metric) && !in_array($metric['operation'] ?? null, ['difference', 'ratio'], true)) $metrics[] = $metric['code'] ?? '';
		if (!in_array($this->scope_code, $scopes, true) || !in_array($this->metric_code, $metrics, true)) { $this->setError(Text::_('COM_JOOMLEAGUE_ERROR_STANDING_ADJUSTMENT_CONTRACT_INVALID')); return false; }
		return parent::check();
	}

	private function validDate(?string $value): bool { if ($value === null) return true; $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value); return $date !== false && $date->format('Y-m-d') === $value; }
}
