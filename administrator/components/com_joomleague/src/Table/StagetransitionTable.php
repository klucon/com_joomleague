<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;
use Joomla\String\StringHelper;
use Joomleague\Component\Joomleague\Domain\Service\CanonicalJson;
use Joomleague\Component\Joomleague\Administrator\Service\StageTransitionValidator;

final class StagetransitionTable extends Table
{
	protected $_supportNullValue = true;
	protected $_trackAssets = false;
	public function __construct(DatabaseDriver $database) { parent::__construct('#__joomleague_stage_transition', 'id', $database); $this->_trackAssets = false; }

	public function check(): bool
	{
		$this->id = (int) ($this->id ?? 0); $this->project_id = (int) ($this->project_id ?? 0); $this->source_stage_id = (int) ($this->source_stage_id ?? 0); $this->target_stage_id = (int) ($this->target_stage_id ?? 0);
		$this->code = strtolower(StringHelper::trim((string) ($this->code ?? ''))); $this->name = StringHelper::trim((string) ($this->name ?? '')); $this->selector_type = strtolower(StringHelper::trim((string) ($this->selector_type ?? ''))); $this->carry_over_mode = strtolower(StringHelper::trim((string) ($this->carry_over_mode ?? 'none'))); $this->target_seed_start = (int) ($this->target_seed_start ?? 0) ?: null;
		if ($this->project_id < 1 || $this->source_stage_id < 1 || $this->target_stage_id < 1 || $this->source_stage_id === $this->target_stage_id || $this->name === '' || preg_match('/^[a-z][a-z0-9_]{0,99}$/', $this->code) !== 1 || ($this->target_seed_start !== null && $this->target_seed_start < 1)) { $this->setError(Text::_('COM_JOOMLEAGUE_ERROR_STAGE_TRANSITION_VALUES_INVALID')); return false; }
		try { $config = (new StageTransitionValidator())->validate($this->selector_type, $this->selector_config_json ?? null, $this->carry_over_mode); $this->selector_config_json = $config === [] ? null : CanonicalJson::encodeObject($config); }
		catch (\Throwable) { $this->setError(Text::_('COM_JOOMLEAGUE_ERROR_STAGE_TRANSITION_CONFIG_INVALID')); return false; }
		$ids = [$this->source_stage_id, $this->target_stage_id]; $query = $this->getDatabase()->getQuery(true)->select('COUNT(*)')->from($this->getDatabase()->quoteName('#__joomleague_project_stage'))->where('project_id = :project')->whereIn('id', $ids)->bind(':project', $this->project_id, ParameterType::INTEGER);
		if ((int) $this->getDatabase()->setQuery($query)->loadResult() !== 2) { $this->setError(Text::_('COM_JOOMLEAGUE_ERROR_STAGE_TRANSITION_STAGE_INVALID')); return false; }
		if (isset($config['round_id'])) { $roundId = (int) $config['round_id']; $query = $this->getDatabase()->getQuery(true)->select('COUNT(*)')->from($this->getDatabase()->quoteName('#__joomleague_project_round'))->where('id = :round')->where('project_id = :project')->where('stage_id = :stage')->bind(':round',$roundId,ParameterType::INTEGER)->bind(':project',$this->project_id,ParameterType::INTEGER)->bind(':stage',$this->source_stage_id,ParameterType::INTEGER); if ((int)$this->getDatabase()->setQuery($query)->loadResult() !== 1) { $this->setError(Text::_('COM_JOOMLEAGUE_ERROR_STAGE_TRANSITION_ROUND_INVALID')); return false; } }
		$query = $this->getDatabase()->getQuery(true)->select('COUNT(*)')->from($this->getDatabase()->quoteName('#__joomleague_stage_transition'))->where('project_id = :project')->where('code = :code')->where('id <> :id')->bind(':project', $this->project_id, ParameterType::INTEGER)->bind(':code', $this->code)->bind(':id', $this->id, ParameterType::INTEGER);
		if ((int) $this->getDatabase()->setQuery($query)->loadResult() > 0) { $this->setError(Text::_('COM_JOOMLEAGUE_ERROR_STAGE_TRANSITION_CODE_DUPLICATE')); return false; }
		$query = $this->getDatabase()->getQuery(true)->select(['source_stage_id', 'target_stage_id'])->from($this->getDatabase()->quoteName('#__joomleague_stage_transition'))->where('project_id = :project')->where('id <> :id')->bind(':project', $this->project_id, ParameterType::INTEGER)->bind(':id', $this->id, ParameterType::INTEGER);
		$edges = [['source' => $this->source_stage_id, 'target' => $this->target_stage_id]]; foreach ($this->getDatabase()->setQuery($query)->loadObjectList() as $edge) $edges[] = ['source' => (int) $edge->source_stage_id, 'target' => (int) $edge->target_stage_id];
		try { (new StageTransitionValidator())->assertAcyclic($edges); } catch (\Throwable) { $this->setError(Text::_('COM_JOOMLEAGUE_ERROR_STAGE_TRANSITION_CYCLE')); return false; }
		return parent::check();
	}
}
