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
use Joomleague\Component\Joomleague\Administrator\Service\SportVariantService;

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

	protected function preprocessForm(Form $form, $data, $group = 'content'): void
	{
		parent::preprocessForm($form, $data, $group);
		$service = new SportVariantService($this->getDatabase());
		$field = new \SimpleXMLElement('<field name="sport_variant_version_id" type="list" label="COM_JOOMLEAGUE_PROJECT_VARIANT_LABEL" description="COM_JOOMLEAGUE_PROJECT_VARIANT_DESC" filter="uint"><option value="0">COM_JOOMLEAGUE_PROJECT_VARIANT_BASE</option></field>');
		$values = (array) $data;
		$selected = 0;
		if ((int) ($values['id'] ?? 0) > 0) {
			$selected = (int) ($this->getItem((int) $values['id'])->sport_variant_version_id ?? 0);
			$field->addAttribute('disabled', 'true');
		} else {
			foreach ($service->options() as $option) {
				$node = $field->addChild('option', htmlspecialchars(Text::_($option['name_key']) . ' / ' . $option['name'] . ' / ' . $option['revision'], ENT_XML1));
				$node->addAttribute('value', (string) $option['id']);
			}
		}
		if ($selected > 0) {
			$revision = $service->revision($selected);
			$node = $field->addChild('option', htmlspecialchars(Text::sprintf('COM_JOOMLEAGUE_PROJECT_VARIANT_PINNED', $revision['revision']), ENT_XML1));
			$node->addAttribute('value', (string) $selected);
		}
		$form->setField($field, null, true, 'details');
		if ((int) ($values['id'] ?? 0) > 0) $form->setValue('sport_variant_version_id', null, $selected);
	}

	public function save($data): bool
	{
		$sportTypeId = (int) ($data['sport_type_id'] ?? 0);
		if ($sportTypeId < 1) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_PROJECT_SPORT_TYPE_REQUIRED'));
			return false;
		}

		$existing = (int) ($data['id'] ?? 0) > 0 ? $this->getItem((int) $data['id']) : null;
		if ($existing !== null && (!is_object($existing) || (int) ($existing->id ?? 0) !== (int) $data['id'])) {
			$this->setError(Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_NOT_FOUND'));
			return false;
		}
		$rawVariant = $data['sport_variant_version_id'] ?? ($existing->sport_variant_version_id ?? 0);
		if ($rawVariant === null || $rawVariant === '') $rawVariant = 0;
		if ((!is_int($rawVariant) && !is_string($rawVariant)) || filter_var($rawVariant, FILTER_VALIDATE_INT) === false || (int) $rawVariant < 0) {
			$this->setError(Text::_('COM_JOOMLEAGUE_PROJECT_VARIANT_INVALID'));
			return false;
		}
		$variantId = (int) $rawVariant;
		if ($existing !== null && $variantId !== (int) ($existing->sport_variant_version_id ?? 0)) {
			$this->setError(Text::_('COM_JOOMLEAGUE_PROJECT_VARIANT_LOCKED'));
			return false;
		}
		$profile = $this->loadSportProfile($sportTypeId, $existing !== null ? (int) $existing->profile_version_id : null);
		if ($profile === null) {
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_PROJECT_PROFILE_UNAVAILABLE'));
			return false;
		}

		if ($existing === null && $variantId > 0) {
			try {
				$revision = (new SportVariantService($this->getDatabase()))->revision($variantId);
				if ((int) $revision['profile_id'] !== $profile['profile_id']) throw new \InvalidArgumentException('Different sport family.');
				$profile['payload'] = $revision['profile'];
				$profile['project_types'] = $revision['profile']['project_types'];
			} catch (\Throwable $error) {
				$this->setError(Text::_('COM_JOOMLEAGUE_PROJECT_VARIANT_INVALID'));
				return false;
			}
		}
		$projectType = trim((string) ($data['project_type'] ?? ''));
		if (!in_array($projectType, $profile['project_types'], true)) {
			$this->setError(Text::sprintf('COM_JOOMLEAGUE_ERROR_PROJECT_TYPE_UNSUPPORTED', $projectType, $profile['sport_type_name']));
			return false;
		}

		if ($existing !== null && (int) $existing->sport_type_id !== $sportTypeId) {
			$this->setError(Text::_('COM_JOOMLEAGUE_PROFILE_MIGRATION_SPORT_LOCKED'));
			return false;
		}
		$data['profile_version_id'] = $existing !== null ? (int) $existing->profile_version_id : $profile['profile_version_id'];
		$data['sport_variant_version_id'] = $variantId ?: null;
		if ($existing === null && $variantId > 0) {
			try {
				$data['profile_version_id'] = (new SportVariantService($this->getDatabase()))->materialize($variantId, $profile['profile_id'], (int) $this->getCurrentUser()->id);
			} catch (\Throwable $error) {
				$this->setError(Text::_('COM_JOOMLEAGUE_PROJECT_VARIANT_INVALID'));
				return false;
			}
		}
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
	private function loadSportProfile(int $sportTypeId, ?int $versionId = null): ?array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select(['version.id AS profile_version_id', 'version.profile_id', 'version.profile_version', 'sport_type.name AS sport_type_name', 'version.payload_json'])
			->from($db->quoteName('#__joomleague_sport_type', 'sport_type'))
			->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'binding') . ' ON binding.id = sport_type.profile_version_id')
			->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.profile_id = binding.profile_id')
			->where('sport_type.id = :sportTypeId')
			->bind(':sportTypeId', $sportTypeId, ParameterType::INTEGER);
		if ($versionId !== null) {
			$query->where('version.id = :versionId')->bind(':versionId', $versionId, ParameterType::INTEGER);
		} else {
			$query->where('version.state = ' . $db->quote('active'));
		}
		$rows = $db->setQuery($query)->loadAssocList();
		usort($rows, static fn (array $left, array $right): int => version_compare($right['profile_version'], $left['profile_version']));
		$row = $rows[0] ?? null;
		if (!$row) {
			return null;
		}

		try {
			$payload = json_decode((string) $row['payload_json'], true, 512, JSON_THROW_ON_ERROR);
		} catch (JsonException) {
			return null;
		}

		$types = array_values(array_filter($payload['project_types'] ?? [], static fn ($value): bool => is_string($value) && $value !== ''));
		return ['profile_version_id' => (int) $row['profile_version_id'], 'profile_id' => (int) $row['profile_id'], 'sport_type_name' => (string) $row['sport_type_name'], 'project_types' => $types, 'payload' => $payload];
	}

}
