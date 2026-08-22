<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\CMS\User\CurrentUserTrait;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Administrator\Service\SportTypeProfileMaterializer;

final class SporttypeModel extends AdminModel implements CurrentUserInterface
{
	use CurrentUserTrait;

	protected $text_prefix = 'COM_JOOMLEAGUE_SPORTTYPE';

	public function getTable($name = 'Sporttype', $prefix = 'Administrator', $options = []): Table
	{
		return parent::getTable($name, $prefix, $options);
	}

	public function getForm($data = [], $loadData = true): Form|false
	{
		return $this->loadForm('com_joomleague.sporttype', 'sporttype', ['control' => 'jform', 'load_data' => $loadData]);
	}

	protected function loadFormData(): array|object
	{
		$data = Factory::getApplication()->getUserState('com_joomleague.edit.sporttype.data', []);
		return $data ?: $this->getItem();
	}

	protected function preprocessForm(Form $form, $data, $group = 'content'): void
	{
		parent::preprocessForm($form, $data, $group);
		$id = (int) (is_array($data) ? ($data['id'] ?? 0) : ($data->id ?? 0));
		if ($id > 0) {
			foreach (['create_positions', 'create_event_types', 'create_statistics'] as $fieldName) {
				$form->removeField($fieldName);
			}
		}
		$field = $form->getField('profile_version_id');
		if ($field === null) return;

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select(['version.id', 'version.profile_version', 'profile.name_key'])
			->from($db->quoteName('#__joomleague_sport_profile_version', 'version'))
			->innerJoin($db->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id = version.profile_id')
			->where('profile.published = 1')
			->where("version.state = " . $db->quote('active'))
			->order(['profile.code ASC', 'version.id DESC']);
		foreach ($db->setQuery($query)->loadObjectList() as $version) {
			$field->addOption(Text::_($version->name_key) . ' · ' . $version->profile_version, ['value' => (int) $version->id]);
		}
	}

	public function save($data): bool
	{
		$isNew = (int) ($data['id'] ?? 0) === 0;
		$options = [
			'positions' => $isNew && (int) ($data['create_positions'] ?? 1) === 1,
			'event_types' => $isNew && (int) ($data['create_event_types'] ?? 1) === 1,
			'statistics' => $isNew && (int) ($data['create_statistics'] ?? 1) === 1,
		];
		unset($data['create_positions'], $data['create_event_types'], $data['create_statistics']);

		$versionId = (int) ($data['profile_version_id'] ?? 0);
		if (!$this->profileVersionExists($versionId)) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_SPORTTYPE_PROFILE_INVALID'));
			return false;
		}

		$id = (int) ($data['id'] ?? 0);
		if ($id > 0) {
			$current = $this->getTable();
			if ($current->load($id) && (int) $current->profile_version_id !== $versionId && $this->countProjects($id) > 0) {
				$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_SPORTTYPE_PROFILE_IN_USE'));
				return false;
			}
		}

		$db = $this->getDatabase();
		$db->transactionStart();

		try {
			if (!parent::save($data)) {
				$db->transactionRollback();
				return false;
			}

			if ($isNew) {
				$sportTypeId = (int) $this->getState($this->getName() . '.id');
				(new SportTypeProfileMaterializer($db))->materialize(
					$sportTypeId,
					$versionId,
					$options,
					(int) $this->getCurrentUser()->id
				);
			}

			$db->transactionCommit();
			return true;
		} catch (\Throwable $exception) {
			$db->transactionRollback();
			Log::add($exception->getMessage(), Log::ERROR, 'com_joomleague');
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_SPORTTYPE_INITIALIZATION_FAILED'));
			return false;
		}
	}

	protected function prepareTable($table): void
	{
		$now = Factory::getDate()->toSql();
		$userId = (int) $this->getCurrentUser()->id;
		if ((int) $table->id === 0) {
			$table->created = $now;
			$table->created_by = $userId;
			$table->ordering = $table->ordering ?: $table->getNextOrder();
		} else {
			$table->modified = $now;
			$table->modified_by = $userId;
		}
	}

	protected function canDelete($record): bool
	{
		if (!parent::canDelete($record)) return false;
		$count = $this->countProjects((int) $record->id);
		if ($count > 0) {
			$this->setError(Text::sprintf('COM_JOOMLEAGUE_ERROR_SPORTTYPE_IN_USE', $count));
			return false;
		}
		return true;
	}

	private function profileVersionExists(int $versionId): bool
	{
		if ($versionId < 1) return false;
		$db = $this->getDatabase();
		$query = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_sport_profile_version', 'version'))->innerJoin($db->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id = version.profile_id')->where('version.id = :versionId')->where('profile.published = 1')->where("version.state = " . $db->quote('active'))->bind(':versionId', $versionId, ParameterType::INTEGER);
		return (int) $db->setQuery($query)->loadResult() === 1;
	}

	private function countProjects(int $sportTypeId): int
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_project'))->where('sport_type_id = :sportTypeId')->bind(':sportTypeId', $sportTypeId, ParameterType::INTEGER);
		return (int) $db->setQuery($query)->loadResult();
	}
}
