<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\CMS\User\CurrentUserTrait;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;
use Joomleague\Component\Joomleague\Administrator\Service\StandingsCascadeTrigger;

final class RoundModel extends AdminModel implements CurrentUserInterface
{
	use CurrentUserTrait;
	protected $text_prefix = 'COM_JOOMLEAGUE_ROUND';
	public function getTable($name = 'Round', $prefix = 'Administrator', $options = []): Table { return parent::getTable($name, $prefix, $options); }
	public function getForm($data = [], $loadData = true): Form|false { return $this->loadForm('com_joomleague.round', 'round', ['control' => 'jform', 'load_data' => $loadData]); }

	protected function loadFormData(): array|object
	{
		$data = Factory::getApplication()->getUserState('com_joomleague.edit.round.data', []);
		if ($data) return $data;
		$item = $this->getItem();
		if ((int) ($item->stage_id ?? 0) < 1) $item->stage_id = Factory::getApplication()->getInput()->getInt('stage_id');
		if ((int) ($item->project_id ?? 0) < 1 && (int) $item->stage_id > 0) $item->project_id = (int) $this->getStage((int) $item->stage_id)->project_id;
		return $item;
	}

	public function getStage(?int $stageId = null): object
	{
		$item = $this->getItem(); $stageId ??= (int) ($item->stage_id ?? Factory::getApplication()->getInput()->getInt('stage_id'));
		$query = $this->getDatabase()->getQuery(true)->select(['stage.*', $this->getDatabase()->quoteName('project.name', 'project_name')])
			->from($this->getDatabase()->quoteName('#__joomleague_project_stage', 'stage'))->innerJoin($this->getDatabase()->quoteName('#__joomleague_project', 'project') . ' ON project.id = stage.project_id')
			->where($this->getDatabase()->quoteName('stage.id') . ' = :stageId')->bind(':stageId', $stageId, ParameterType::INTEGER);
		$stage = $this->getDatabase()->setQuery($query)->loadObject();
		if (!$stage) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_ROUND_STAGE_INVALID'));
		return $stage;
	}

	public function save($data): bool
	{
		$id = (int) ($data['id'] ?? 0);
		$oldPublished = null;
		if ($id > 0) { $stored = $this->storedOwner($id); $data['stage_id'] = $stored->stage_id; $data['project_id'] = $stored->project_id; $oldPublished = (int) $stored->published; }
		else {
			$stage = $this->getStage((int) ($data['stage_id'] ?? 0));
			$data['project_id'] = (int) $stage->project_id;
			if (trim((string) ($data['code'] ?? '')) === '') {
				$data['code'] = $this->availableCode((int) $stage->id, (string) ($data['name'] ?? 'round'));
			}
		}
		$result = parent::save($data);
		if ($result && $oldPublished !== null && array_key_exists('published', $data) && (int) $data['published'] !== $oldPublished) $this->refreshStandings([[(int) $data['project_id'], (int) $data['stage_id']]]);
		return $result;
	}

	public function publish(&$pks, $value = 1): bool
	{
		$contexts = $this->standingsContexts((array) $pks);
		$result = parent::publish($pks, $value);
		if ($result) $this->refreshStandings($contexts);
		return $result;
	}

	public function delete(&$pks): bool
	{
		$contexts = $this->standingsContexts((array) $pks);
		$result = parent::delete($pks);
		if ($result) $this->refreshStandings($contexts);
		return $result;
	}

	protected function canDelete($record): bool
	{
		return $this->canEditSchedule((int) ($record->project_id ?? 0));
	}

	protected function canEditState($record): bool
	{
		return $this->canEditSchedule((int) ($record->project_id ?? 0));
	}

	private function canEditSchedule(int $projectId): bool
	{
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		return $this->getCurrentUser()->authorise('joomleague.project.edit.schedule', $asset);
	}

	protected function prepareTable($table): void
	{
		$now = Factory::getDate()->toSql(); $userId = (int) $this->getCurrentUser()->id;
		if ((int) $table->id === 0) { $table->uuid = UuidFactory::v4(); $table->created = $now; $table->created_by = $userId; $table->ordering = $table->ordering ?: $table->getNextOrder('stage_id = ' . (int) $table->stage_id); }
		else { $table->modified = $now; $table->modified_by = $userId; }
	}

	private function storedOwner(int $id): object
	{
		$query = $this->getDatabase()->getQuery(true)->select(['stage_id', 'project_id', 'published'])->from($this->getDatabase()->quoteName('#__joomleague_project_round'))->where($this->getDatabase()->quoteName('id') . ' = :id')->bind(':id', $id, ParameterType::INTEGER);
		$owner = $this->getDatabase()->setQuery($query)->loadObject(); if (!$owner) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_ROUND_INVALID')); return $owner;
	}

	private function availableCode(int $stageId, string $name): string
	{
		$base = str_replace('-', '_', ApplicationHelper::stringURLSafe($name)) ?: 'round';
		$code = $base;
		$suffix = 2;

		while (true) {
			$query = $this->getDatabase()->getQuery(true)->select('COUNT(*)')
				->from($this->getDatabase()->quoteName('#__joomleague_project_round'))
				->where('stage_id = :stage')->where('code = :code')
				->bind(':stage', $stageId, ParameterType::INTEGER)->bind(':code', $code);
			if ((int) $this->getDatabase()->setQuery($query)->loadResult() === 0) return $code;
			$code = $base . '_' . $suffix++;
		}
	}

	/** @param list<int|string> $ids @return list<array{0:int,1:int}> */
	private function standingsContexts(array $ids): array
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
		if ($ids === []) return [];
		$query = $this->getDatabase()->getQuery(true)->select(['project_id', 'stage_id'])->from($this->getDatabase()->quoteName('#__joomleague_project_round'))->whereIn('id', $ids, ParameterType::INTEGER)->group(['project_id', 'stage_id']);
		return array_map(static fn (object $row): array => [(int) $row->project_id, (int) $row->stage_id], $this->getDatabase()->setQuery($query)->loadObjectList());
	}

	/** @param list<array{0:int,1:int}> $contexts */
	private function refreshStandings(array $contexts): void
	{
		$trigger = new StandingsCascadeTrigger($this->getDatabase());
		foreach ($contexts as [$projectId, $stageId]) $trigger->trigger($projectId, $stageId, (int) $this->getCurrentUser()->id);
	}
}
