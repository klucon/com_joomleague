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
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectContextRepository;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

final class ProjectentryModel extends AdminModel implements CurrentUserInterface
{
	use CurrentUserTrait;

	protected $text_prefix = 'COM_JOOMLEAGUE_PROJECTENTRY';

	public function getTable($name = 'Projectentry', $prefix = 'Administrator', $options = []): Table
	{
		return parent::getTable($name, $prefix, $options);
	}

	public function getForm($data = [], $loadData = true): Form|false
	{
		return $this->loadForm('com_joomleague.projectentry', 'projectentry', ['control' => 'jform', 'load_data' => $loadData]);
	}

	protected function loadFormData(): array|object
	{
		$data = Factory::getApplication()->getUserState('com_joomleague.edit.projectentry.data', []);

		if ($data) {
			return $data;
		}

		$item = $this->getItem();

		if ((int) ($item->project_id ?? 0) < 1) {
			$item->project_id = Factory::getApplication()->getInput()->getInt('project_id');
		}

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
		$entryId = (int) ($data['id'] ?? 0);
		$kind = strtolower(trim((string) ($data['entry_kind'] ?? '')));

		try {
			$projectId = $entryId > 0 ? $this->loadStoredProjectId($entryId) : (int) ($data['project_id'] ?? 0);
			$project = (new ProjectContextRepository($this->getDatabase()))->get($projectId);
		} catch (\Throwable) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_PROJECTENTRY_PROJECT_INVALID'));

			return false;
		}

		$data['project_id'] = $projectId;

		$allowedKinds = $project->profile['entry_model']['allowed_kinds'] ?? [];

		if (!is_array($allowedKinds) || !in_array($kind, $allowedKinds, true)) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_PROJECTENTRY_KIND_UNSUPPORTED'));

			return false;
		}

		$data['entry_kind'] = $kind;
		$data['team_id'] = $kind === 'team' ? (int) ($data['team_id'] ?? 0) : null;
		$data['person_id'] = $kind === 'person' ? (int) ($data['person_id'] ?? 0) : null;
		$data['display_name'] = $kind === 'group' ? trim((string) ($data['display_name'] ?? '')) : '';

		if (($kind === 'team' && !$this->targetExists('#__joomleague_team', (int) $data['team_id']))
			|| ($kind === 'person' && !$this->targetExists('#__joomleague_person', (int) $data['person_id']))) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_PROJECTENTRY_TARGET_INVALID'));

			return false;
		}

		return parent::save($data);
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

	private function targetExists(string $table, int $id): bool
	{
		if ($id < 1) {
			return false;
		}

		$query = $this->getDatabase()->getQuery(true)
			->select('COUNT(*)')
			->from($this->getDatabase()->quoteName($table))
			->where($this->getDatabase()->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		return (int) $this->getDatabase()->setQuery($query)->loadResult() === 1;
	}

	private function loadStoredProjectId(int $entryId): int
	{
		$query = $this->getDatabase()->getQuery(true)
			->select($this->getDatabase()->quoteName('project_id'))
			->from($this->getDatabase()->quoteName('#__joomleague_project_entry'))
			->where($this->getDatabase()->quoteName('id') . ' = :entryId')
			->bind(':entryId', $entryId, ParameterType::INTEGER);
		$projectId = (int) $this->getDatabase()->setQuery($query)->loadResult();

		if ($projectId < 1) {
			throw new \RuntimeException('The project participant does not exist.');
		}

		return $projectId;
	}
}
