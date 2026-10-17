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
use Joomleague\Component\Joomleague\Administrator\Service\ProjectContextRepository;
use Joomleague\Component\Joomleague\Administrator\Service\StandingsCascadeTrigger;
use Joomleague\Component\Joomleague\Administrator\Service\StageEncounterRules;
use Joomleague\Component\Joomleague\Domain\Service\CanonicalJson;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

final class StageModel extends AdminModel implements CurrentUserInterface
{
	use CurrentUserTrait;

	protected $text_prefix = 'COM_JOOMLEAGUE_STAGE';

	public function getTable($name = 'Stage', $prefix = 'Administrator', $options = []): Table
	{
		return parent::getTable($name, $prefix, $options);
	}

	public function getForm($data = [], $loadData = true): Form|false
	{
		return $this->loadForm('com_joomleague.stage', 'stage', ['control' => 'jform', 'load_data' => $loadData]);
	}

	protected function loadFormData(): array|object
	{
		$data = Factory::getApplication()->getUserState('com_joomleague.edit.stage.data', []);

		if ($data) {
			return $data;
		}

		$item = $this->getItem();
		$metadata = empty($item->metadata_json) ? [] : json_decode($item->metadata_json, true, 512, JSON_THROW_ON_ERROR);
		$item->encounter_decision_policy = $metadata['encounter_decision_policy'] ?? 'inherit';
		if ((int) ($item->project_id ?? 0) < 1) $item->project_id = Factory::getApplication()->getInput()->getInt('project_id');

		return $item;
	}

	public function getProject(): object
	{
		$item = $this->getItem();
		$projectId = (int) ($item->project_id ?? Factory::getApplication()->getInput()->getInt('project_id'));

		return (new ProjectContextRepository($this->getDatabase()))->get($projectId);
	}

	public function save($data): bool
	{
		$id = (int) ($data['id'] ?? $this->getState($this->getName() . '.id'));
		$data['id'] = $id;
		$oldPublished = $id > 0 ? $this->storedPublished($id) : null;

		try {
			$projectId = $id > 0 ? $this->storedProjectId($id) : (int) ($data['project_id'] ?? 0);
			(new ProjectContextRepository($this->getDatabase()))->get($projectId);
		} catch (\Throwable) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_STAGE_PROJECT_INVALID'));

			return false;
		}

		$data['project_id'] = $projectId;
		if (!$this->canEditSchedule($projectId)) {
			$this->setError(Text::_('JERROR_ALERTNOAUTHOR'));
			return false;
		}
		try {
			// Only the dedicated field is writable; preserve other server-owned metadata.
			$item = $id > 0 ? $this->getItem($id) : null;
			$metadata = empty($item->metadata_json) ? [] : json_decode($item->metadata_json, true, 512, JSON_THROW_ON_ERROR);
			if (!is_array($metadata)) throw new \UnexpectedValueException('Stage metadata must be an object.');
			$policy = StageEncounterRules::policy($data['encounter_decision_policy'] ?? ($metadata['encounter_decision_policy'] ?? 'inherit'));
			if ($policy !== 'inherit') {
				$query = $this->getDatabase()->getQuery(true)->select('version.payload_json')
					->from($this->getDatabase()->quoteName('#__joomleague_project', 'project'))
					->innerJoin($this->getDatabase()->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')
					->where('project.id = :project')->bind(':project', $projectId, ParameterType::INTEGER);
				$profile = json_decode((string) $this->getDatabase()->setQuery($query)->loadResult(), true, 512, JSON_THROW_ON_ERROR);
				StageEncounterRules::apply($profile, ['encounter_decision_policy' => $policy]);
			}
			$metadata['encounter_decision_policy'] = $policy;
			$data['metadata_json'] = CanonicalJson::encodeObject($metadata);
			unset($data['encounter_decision_policy']);
		} catch (\Throwable $exception) {
			$this->setError(Text::_($exception->getMessage()));
			return false;
		}
		if ($id === 0 && trim((string) ($data['code'] ?? '')) === '') {
			$data['code'] = $this->availableCode($projectId, (string) ($data['name'] ?? 'stage'));
		}

		$result = parent::save($data);
		if ($result && $oldPublished !== null && array_key_exists('published', $data) && (int) $data['published'] !== $oldPublished) $this->refreshStandings([[$projectId, $id]]);
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

	protected function getReorderConditions($table): array
	{
		$ownerId = (int) ($table->project_id ?? 0);
		return [$ownerId > 0 ? $this->getDatabase()->quoteName('project_id') . ' = ' . $ownerId : '1 = 0'];
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
		if ($projectId < 1) return false;
		$asset = 'com_joomleague.project.' . $projectId;
		return $this->getCurrentUser()->authorise('joomleague.project.edit.schedule', $asset);
	}

	protected function prepareTable($table): void
	{
		$now = Factory::getDate()->toSql();
		$userId = (int) $this->getCurrentUser()->id;

		if ((int) $table->id === 0) {
			$table->uuid = UuidFactory::v4();
			$table->created = $now;
			$table->created_by = $userId;
			$table->ordering = $table->ordering ?: $table->getNextOrder('project_id = ' . (int) $table->project_id);
		} else {
			$table->modified = $now;
			$table->modified_by = $userId;
		}
	}

	private function storedProjectId(int $id): int
	{
		$query = $this->getDatabase()->getQuery(true)->select($this->getDatabase()->quoteName('project_id'))
			->from($this->getDatabase()->quoteName('#__joomleague_project_stage'))
			->where($this->getDatabase()->quoteName('id') . ' = :id')->bind(':id', $id, ParameterType::INTEGER);
		$projectId = (int) $this->getDatabase()->setQuery($query)->loadResult();

		if ($projectId < 1) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_STAGE_PROJECT_INVALID'));

		return $projectId;
	}

	private function storedPublished(int $id): int
	{
		$query = $this->getDatabase()->getQuery(true)->select($this->getDatabase()->quoteName('published'))
			->from($this->getDatabase()->quoteName('#__joomleague_project_stage'))
			->where($this->getDatabase()->quoteName('id') . ' = :id')->bind(':id', $id, ParameterType::INTEGER);
		return (int) $this->getDatabase()->setQuery($query)->loadResult();
	}

	private function availableCode(int $projectId, string $name): string
	{
		$base = str_replace('-', '_', ApplicationHelper::stringURLSafe($name)) ?: 'stage';
		$code = $base;
		$suffix = 2;

		while (true) {
			$query = $this->getDatabase()->getQuery(true)->select('COUNT(*)')
				->from($this->getDatabase()->quoteName('#__joomleague_project_stage'))
				->where('project_id = :project')->where('code = :code')
				->bind(':project', $projectId, ParameterType::INTEGER)->bind(':code', $code);
			if ((int) $this->getDatabase()->setQuery($query)->loadResult() === 0) return $code;
			$code = $base . '_' . $suffix++;
		}
	}

	/** @param list<int|string> $ids @return list<array{0:int,1:int}> */
	private function standingsContexts(array $ids): array
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
		if ($ids === []) return [];
		$query = $this->getDatabase()->getQuery(true)->select(['project_id', 'id'])->from($this->getDatabase()->quoteName('#__joomleague_project_stage'))->whereIn('id', $ids, ParameterType::INTEGER)->group(['project_id', 'id']);
		return array_map(static fn (object $row): array => [(int) $row->project_id, (int) $row->id], $this->getDatabase()->setQuery($query)->loadObjectList());
	}

	/** @param list<array{0:int,1:int}> $contexts */
	private function refreshStandings(array $contexts): void
	{
		$trigger = new StandingsCascadeTrigger($this->getDatabase());
		foreach ($contexts as [$projectId, $stageId]) $trigger->trigger($projectId, $stageId, (int) $this->getCurrentUser()->id);
	}
}
