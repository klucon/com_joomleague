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
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;
use JsonException;

final class ProjectModel extends AdminModel implements CurrentUserInterface
{
	use CurrentUserTrait;

	protected $text_prefix = 'COM_JOOMLEAGUE_PROJECT';

	public function getTable($name = 'Project', $prefix = 'Administrator', $options = []): Table
	{
		return parent::getTable($name, $prefix, $options);
	}

	public function getForm($data = [], $loadData = true): Form|false
	{
		return $this->loadForm('com_joomleague.project', 'project', ['control' => 'jform', 'load_data' => $loadData]);
	}

	protected function loadFormData(): array|object
	{
		$data = Factory::getApplication()->getUserState('com_joomleague.edit.project.data', []);
		if ($data) {
			return $data;
		}

		return $this->getItem();
	}

	public function save($data): bool
	{
		$sportTypeId = (int) ($data['sport_type_id'] ?? 0);
		if ($sportTypeId < 1) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_PROJECT_SPORT_TYPE_REQUIRED'));
			return false;
		}

		$profile = $this->loadSportProfile($sportTypeId);
		if ($profile === null) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_PROJECT_PROFILE_UNAVAILABLE'));
			return false;
		}

		$projectType = trim((string) ($data['project_type'] ?? ''));
		if (!in_array($projectType, $profile['project_types'], true)) {
			$this->setError(Text::sprintf('COM_JOOMLEAGUE_ERROR_PROJECT_TYPE_UNSUPPORTED', $projectType, $profile['sport_type_name']));
			return false;
		}

		$data['profile_version_id'] = $profile['profile_version_id'];
		$data['timezone'] = trim((string) ($data['timezone'] ?? ''));
		if (trim((string) ($data['default_start_time'] ?? '')) === '' && isset($profile['payload']['match_structure']['default_start_time'])) {
			$data['default_start_time'] = (string) $profile['payload']['match_structure']['default_start_time'];
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
			$table->ordering = $table->ordering ?: $table->getNextOrder();
		} else {
			$table->modified = $now;
			$table->modified_by = $userId;
		}
	}

	/** @return array{profile_version_id:int,sport_type_name:string,project_types:list<string>,payload:array<string,mixed>}|null */
	private function loadSportProfile(int $sportTypeId): ?array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select(['sport_type.profile_version_id', 'sport_type.name AS sport_type_name', 'version.payload_json'])
			->from($db->quoteName('#__joomleague_sport_type', 'sport_type'))
			->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = sport_type.profile_version_id')
			->where('sport_type.id = :sportTypeId')
			->bind(':sportTypeId', $sportTypeId, ParameterType::INTEGER);
		$row = $db->setQuery($query)->loadAssoc();
		if (!$row) {
			return null;
		}

		try {
			$payload = json_decode((string) $row['payload_json'], true, 512, JSON_THROW_ON_ERROR);
		} catch (JsonException) {
			return null;
		}

		$types = array_values(array_filter($payload['project_types'] ?? [], static fn ($value): bool => is_string($value) && $value !== ''));
		return ['profile_version_id' => (int) $row['profile_version_id'], 'sport_type_name' => (string) $row['sport_type_name'], 'project_types' => $types, 'payload' => $payload];
	}

}
