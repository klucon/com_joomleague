<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\CMS\User\CurrentUserTrait;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;
use Joomleague\Component\Joomleague\Domain\Service\CanonicalJson;
use Joomleague\Component\Joomleague\Administrator\Service\MatchProjectResolver;

final class StagetransitionModel extends AdminModel implements CurrentUserInterface
{
	use CurrentUserTrait;
	protected $text_prefix = 'COM_JOOMLEAGUE_STAGE_TRANSITION';
	public function getTable($name = 'Stagetransition', $prefix = 'Administrator', $options = []): Table { return parent::getTable($name, $prefix, $options); }
	public function getForm($data = [], $loadData = true): Form|false { return $this->loadForm('com_joomleague.stagetransition', 'stagetransition', ['control' => 'jform', 'load_data' => $loadData]); }
	protected function loadFormData(): array|object { $data = Factory::getApplication()->getUserState('com_joomleague.edit.stagetransition.data', []); if ($data) return $data; $item = $this->getItem(); if ((int) ($item->project_id ?? 0) < 1) $item->project_id = Factory::getApplication()->getInput()->getInt('project_id'); $config = json_decode((string) ($item->selector_config_json ?? ''), true); if (is_array($config)) { $item->rank_from = $config['from'] ?? 1; $item->rank_to = $config['to'] ?? 1; $item->standing_scope = $config['scope'] ?? 'total'; $item->match_outcome = $config['outcome'] ?? 'winner'; $item->source_round_id = $config['round_id'] ?? null; } return $item; }
	public function save($data): bool
	{
		$id = (int) ($data['id'] ?? $this->getState($this->getName() . '.id'));
		$data['id'] = $id;
		$projectId = $id > 0
			? (new MatchProjectResolver($this->getDatabase()))->resolveProjectIdFromTransition($id)
			: (int) ($data['project_id'] ?? 0);
		if ($projectId < 1 || !$this->canRunTransitions($projectId)) {
			$this->setError(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'));
			return false;
		}
		$data['project_id'] = $projectId;
		$selector = (string) ($data['selector_type'] ?? '');
		$config = match ($selector) {
			'standing_rank_range' => ['from' => (int) ($data['rank_from'] ?? 0), 'to' => (int) ($data['rank_to'] ?? 0), 'scope' => (string) ($data['standing_scope'] ?? '')],
			'match_outcome' => ['outcome' => (string) ($data['match_outcome'] ?? 'winner')] + ((int) ($data['source_round_id'] ?? 0) > 0 ? ['round_id' => (int) $data['source_round_id']] : []),
			default => [],
		};
		$data['selector_config_json'] = $config === [] ? null : CanonicalJson::encodeObject($config);
		return parent::save($data);
	}
	protected function getReorderConditions($table): array
	{
		$ownerId = (int) ($table->project_id ?? 0);
		return [$ownerId > 0 ? $this->getDatabase()->quoteName('project_id') . ' = ' . $ownerId : '1 = 0'];
	}

	protected function canDelete($record): bool { return $this->canRunTransitions((int) ($record->project_id ?? 0)); }
	protected function canEditState($record): bool { return $this->canRunTransitions((int) ($record->project_id ?? 0)); }
	private function canRunTransitions(int $projectId): bool
	{
		return $projectId > 0 && $this->getCurrentUser()->authorise('joomleague.project.run.transitions', 'com_joomleague.project.' . $projectId);
	}
	protected function prepareTable($table): void { $now = Factory::getDate()->toSql(); $userId = (int) $this->getCurrentUser()->id; if ((int) $table->id === 0) { $table->uuid = UuidFactory::v4(); $table->created = $now; $table->created_by = $userId; $table->ordering = $table->ordering ?: $table->getNextOrder('project_id = ' . (int) $table->project_id); } else { $table->modified = $now; $table->modified_by = $userId; } }
}
