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
use Joomleague\Component\Joomleague\Administrator\Service\ProjectEntryContextRepository;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

final class EntrymemberModel extends AdminModel implements CurrentUserInterface
{
	use CurrentUserTrait;

	protected $text_prefix = 'COM_JOOMLEAGUE_ENTRYMEMBER';

	public function getTable($name = 'Entrymember', $prefix = 'Administrator', $options = []): Table
	{
		return parent::getTable($name, $prefix, $options);
	}

	public function getForm($data = [], $loadData = true): Form|false
	{
		return $this->loadForm('com_joomleague.entrymember', 'entrymember', ['control' => 'jform', 'load_data' => $loadData]);
	}

	protected function loadFormData(): array|object
	{
		$data = Factory::getApplication()->getUserState('com_joomleague.edit.entrymember.data', []);

		if ($data) {
			return $data;
		}

		$item = $this->getItem();

		if ((int) ($item->entry_id ?? 0) < 1) {
			$item->entry_id = Factory::getApplication()->getInput()->getInt('entry_id');
		}

		return $item;
	}

	public function getEntry(): object
	{
		$item = $this->getItem();
		$entryId = (int) ($item->entry_id ?? Factory::getApplication()->getInput()->getInt('entry_id'));

		return (new ProjectEntryContextRepository($this->getDatabase()))->get($entryId);
	}

	public function save($data): bool
	{
		$memberId = (int) ($data['id'] ?? $this->getState($this->getName() . '.id'));
		$data['id'] = $memberId;

		try {
			$entryId = $memberId > 0 ? $this->loadStoredEntryId($memberId) : (int) ($data['entry_id'] ?? 0);
			$entry = (new ProjectEntryContextRepository($this->getDatabase()))->get($entryId);
		} catch (\Throwable) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_ENTRYMEMBER_ENTRY_INVALID'));

			return false;
		}

		if ((int) $entry->project_id < 1 || !$this->getCurrentUser()->authorise($memberId > 0 ? 'core.edit' : 'core.create', 'com_joomleague.project.' . (int) $entry->project_id)) {
			$this->setError(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'));
			return false;
		}
		$model = $entry->profile['entry_model'] ?? [];
		$personType = strtolower(trim((string) ($data['member_person_type'] ?? '')));
		$roleCode = trim((string) ($data['role_code'] ?? ''));

		if (($model['members_supported'] ?? false) !== true
			|| !in_array($personType, $model['member_person_types'] ?? [], true)
			|| ($roleCode !== '' && !$this->profileHasRole($entry->profile, $roleCode, $personType))) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_ENTRYMEMBER_PROFILE_INVALID'));

			return false;
		}

		$data['entry_id'] = $entryId;
		$data['member_person_type'] = $personType;
		$data['role_code'] = $roleCode !== '' ? $roleCode : null;

		if (!$this->personExists((int) ($data['person_id'] ?? 0), $memberId)) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_ENTRYMEMBER_PERSON_INVALID'));

			return false;
		}

		return parent::save($data);
	}

	protected function getReorderConditions($table): array
	{
		$ownerId = (int) ($table->entry_id ?? 0);
		return [$ownerId > 0 ? $this->getDatabase()->quoteName('entry_id') . ' = ' . $ownerId : '1 = 0'];
	}

	protected function canDelete($record): bool
	{
		return $this->canEditEntry((int) ($record->entry_id ?? 0), 'core.delete');
	}

	protected function canEditState($record): bool
	{
		return $this->canEditEntry((int) ($record->entry_id ?? 0), 'core.edit.state');
	}

	private function canEditEntry(int $entryId, string $action): bool
	{
		if ($entryId < 1) {
			return false;
		}

		try {
			$entry = (new ProjectEntryContextRepository($this->getDatabase()))->get($entryId);
		} catch (\Throwable) {
			return false;
		}

		$projectId = (int) $entry->project_id;
		return $projectId > 0 && $this->getCurrentUser()->authorise($action, 'com_joomleague.project.' . $projectId);
	}

	protected function prepareTable($table): void
	{
		$now = Factory::getDate()->toSql();
		$userId = (int) $this->getCurrentUser()->id;

		if ((int) $table->id === 0) {
			$table->uuid = UuidFactory::v4();
			$table->created = $now;
			$table->created_by = $userId;
			$table->ordering = $table->ordering ?: $table->getNextOrder('entry_id = ' . (int) $table->entry_id);
		} else {
			$table->modified = $now;
			$table->modified_by = $userId;
		}
	}

	private function loadStoredEntryId(int $memberId): int
	{
		$query = $this->getDatabase()->getQuery(true)
			->select($this->getDatabase()->quoteName('entry_id'))
			->from($this->getDatabase()->quoteName('#__joomleague_project_entry_member'))
			->where($this->getDatabase()->quoteName('id') . ' = :memberId')
			->bind(':memberId', $memberId, ParameterType::INTEGER);
		$entryId = (int) $this->getDatabase()->setQuery($query)->loadResult();

		if ($entryId < 1) {
			throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_ENTRYMEMBER_INVALID'));
		}

		return $entryId;
	}

	private function personExists(int $personId, int $memberId): bool
	{
		if ($personId < 1) {
			return false;
		}

		$query = $this->getDatabase()->getQuery(true)
			->select('COUNT(*)')
			->from($this->getDatabase()->quoteName('#__joomleague_person'))
			->where($this->getDatabase()->quoteName('id') . ' = :personId')
			->bind(':personId', $personId, ParameterType::INTEGER);
		$levels = array_values(array_filter(array_map('intval', $this->getCurrentUser()->getAuthorisedViewLevels()), static fn($id) => $id > 0));
		if (!$levels) return false;
		$query->where('access IN (' . implode(',', $levels) . ')');
		$query->where('(published = 1 OR EXISTS (SELECT 1 FROM ' . $this->getDatabase()->quoteName('#__joomleague_project_entry_member', 'stored')
			. ' WHERE stored.id = :memberId AND stored.person_id = :storedPerson))')
			->bind(':memberId', $memberId, ParameterType::INTEGER)->bind(':storedPerson', $personId, ParameterType::INTEGER);

		return (int) $this->getDatabase()->setQuery($query)->loadResult() === 1;
	}

	/** @param array<string, mixed> $profile */
	private function profileHasRole(array $profile, string $roleCode, string $personType): bool
	{
		foreach ($profile['positions'] ?? [] as $position) {
			if (($position['code'] ?? null) === $roleCode && ($position['person_type'] ?? null) === $personType) {
				return true;
			}
		}

		return false;
	}
}
