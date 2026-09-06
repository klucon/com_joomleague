<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\FormModel;
use Joomleague\Component\Joomleague\Administrator\Service\ProfileTemplateConfigRepository;
use Joomleague\Component\Joomleague\Administrator\Service\TemplateConfigResolver;
use Joomleague\Component\Joomleague\Administrator\Service\TemplateDefinitionRegistry;
use SimpleXMLElement;

final class ProfiletemplatesModel extends FormModel
{
	/** @var list<array<string,mixed>> */
	private array $templateGroups = [];

	public function getProfile(int $profileVersionId): object
	{
		if ($profileVersionId < 1) {
			throw new \InvalidArgumentException(Text::_('COM_JOOMLEAGUE_PROFILETEMPLATES_PROFILE_REQUIRED'));
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select(['profile.code AS profile_code', 'profile.name_key', 'profile.description_key'])
			->select(['version.id AS profile_version_id', 'version.profile_version', 'version.schema_version', 'version.payload_json'])
			->from($db->quoteName('#__joomleague_sport_profile_version', 'version'))
			->innerJoin($db->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id = version.profile_id')
			->where($db->quoteName('version.id') . ' = :versionId')
			->where($db->quoteName('version.state') . ' = ' . $db->quote('active'))
			->where($db->quoteName('profile.published') . ' = 1')
			->bind(':versionId', $profileVersionId);
		$profile = $db->setQuery($query)->loadObject();

		if ($profile === null) {
			throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_PROFILETEMPLATES_PROFILE_NOT_FOUND'));
		}

		$payload = json_decode((string) $profile->payload_json, true, 512, JSON_THROW_ON_ERROR);

		if (!is_array($payload) || array_is_list($payload)) {
			throw new \UnexpectedValueException('COM_JOOMLEAGUE_PROFILETEMPLATES_PROFILE_INVALID');
		}

		$profile->profile = $payload;
		unset($profile->payload_json);

		return $profile;
	}

	public function getForm($data = [], $loadData = true): Form|false
	{
		$this->templateGroups = [];
		$profileVersionId = is_int($data) ? $data : Factory::getApplication()->getInput()->getInt('profile_version_id');
		$profile = $this->getProfile($profileVersionId);
		$registry = new TemplateDefinitionRegistry();
		$resolver = new TemplateConfigResolver($registry);
		$overrides = (new ProfileTemplateConfigRepository($this->getDatabase(), $registry))->getAll($profileVersionId);
		$app = Factory::getApplication();
		$posted = $app->getUserState('com_joomleague.edit.profiletemplates.data', []);
		$app->setUserState('com_joomleague.edit.profiletemplates.data', null);
		if ((int) ($posted['profile_version_id'] ?? 0) !== $profileVersionId) {
			$posted = [];
		}
		$form = $this->loadForm('com_joomleague.profiletemplates', 'profiletemplates', ['control' => 'jform', 'load_data' => false]);

		if (!$form instanceof Form) {
			throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_FORM_LOAD'));
		}

		$form->setValue('profile_version_id', null, $profileVersionId);

		foreach (($profile->profile['template_defaults'] ?? []) as $templateCode => $unusedDefaults) {
			$definition = $registry->get((string) $templateCode);
			$bundled = $resolver->resolveProfileTemplate((string) $templateCode, $profile->profile);
			$currentOverrides = $overrides[$templateCode] ?? [];
			$effective = $resolver->resolveProfileTemplate((string) $templateCode, $profile->profile, $currentOverrides);
			$fields = [];

			foreach ($definition['fields'] as $fieldName => $metadata) {
				$key = $this->fieldKey((string) $templateCode, (string) $fieldName);
				$enabled = array_key_exists($fieldName, $currentOverrides);
				$value = $enabled ? $currentOverrides[$fieldName] : $bundled[$fieldName];

				if ($posted !== []) {
					$submitted = (array) ($posted['templates'] ?? []);
					$enabled = (string) ($submitted[$key . '_enabled'] ?? '0') === '1';
					$value = $submitted[$key . '_value'] ?? $value;
				}

				$form->setField(new SimpleXMLElement('<field name="' . $key . '_enabled" type="checkbox" value="1" label="COM_JOOMLEAGUE_PROFILETEMPLATES_OVERRIDE_LABEL" description="COM_JOOMLEAGUE_PROFILETEMPLATES_OVERRIDE_DESC" />'), 'templates', true, 'templates');
				$form->setValue($key . '_enabled', 'templates', $enabled ? '1' : '0');
				$form->setField(new SimpleXMLElement($this->buildValueFieldXml($key . '_value', $metadata)), 'templates', true, 'templates');
				$form->setValue($key . '_value', 'templates', $this->formatValue($value));
				$fields[] = [
					'label_key' => $metadata['label_key'],
					'description_key' => $metadata['description_key'],
					'bundled' => $bundled[$fieldName],
					'effective' => $effective[$fieldName],
					'enabled_field' => $form->getField($key . '_enabled', 'templates'),
					'value_field' => $form->getField($key . '_value', 'templates'),
				];
			}

			$this->templateGroups[] = [
				'code' => $templateCode,
				'name_key' => $definition['name_key'],
				'description_key' => $definition['description_key'],
				'fields' => $fields,
			];
		}

		return $form;
	}

	/** @return list<array<string,mixed>> */
	public function getTemplateGroups(): array
	{
		return $this->templateGroups;
	}

	/** @param array<string,mixed> $submitted */
	public function saveSubmittedTemplates(int $profileVersionId, array $submitted, int $actorId): void
	{
		$profile = $this->getProfile($profileVersionId);
		$registry = new TemplateDefinitionRegistry();
		$configs = [];

		foreach (($profile->profile['template_defaults'] ?? []) as $templateCode => $unusedDefaults) {
			$definition = $registry->get((string) $templateCode);
			$params = [];

			foreach ($definition['fields'] as $fieldName => $metadata) {
				$key = $this->fieldKey((string) $templateCode, (string) $fieldName);

				if ((string) ($submitted[$key . '_enabled'] ?? '0') !== '1') {
					continue;
				}

				if (!array_key_exists($key . '_value', $submitted)) {
					throw new \InvalidArgumentException(Text::sprintf('COM_JOOMLEAGUE_PROFILETEMPLATES_VALUE_REQUIRED', Text::_($metadata['label_key'])));
				}

				$params[$fieldName] = $this->parseValue($submitted[$key . '_value'], $metadata, (string) $metadata['label_key']);
			}

			$registry->validateValues((string) $templateCode, $params);
			$configs[(string) $templateCode] = $params;
		}

		(new ProfileTemplateConfigRepository($this->getDatabase(), $registry))->saveAll($profileVersionId, $configs, $actorId);
	}

	/** @param array<string,mixed> $metadata */
	private function buildValueFieldXml(string $name, array $metadata): string
	{
		$attributes = ' name="' . $name . '" label="' . $metadata['label_key'] . '" description="' . $metadata['description_key'] . '"';

		if ($metadata['type'] === 'boolean') {
			return '<field' . $attributes . ' type="radio" layout="joomla.form.field.radio.switcher"><option value="0">JNO</option><option value="1">JYES</option></field>';
		}

		if ($metadata['type'] === 'integer') {
			return '<field' . $attributes . ' type="number" step="1" />';
		}

		if (isset($metadata['enum'])) {
			$options = '';

			foreach ($metadata['enum'] as $option) {
				$key = 'COM_JOOMLEAGUE_TEMPLATE_OPTION_' . strtoupper((string) $option);
				$options .= '<option value="' . htmlspecialchars((string) $option, ENT_XML1) . '">' . $key . '</option>';
			}

			return '<field' . $attributes . ' type="list">' . $options . '</field>';
		}

		return '<field' . $attributes . ' type="text" />';
	}

	/** @param array<string,mixed> $metadata */
	private function parseValue(mixed $value, array $metadata, string $labelKey): bool|int|string
	{
		if (!is_string($value) && !is_int($value)) {
			throw new \InvalidArgumentException(Text::sprintf('COM_JOOMLEAGUE_PROFILETEMPLATES_VALUE_INVALID', Text::_($labelKey)));
		}
		$value = is_string($value) ? trim($value) : $value;

		return match ($metadata['type']) {
			'boolean' => in_array((string) $value, ['0', '1'], true)
				? (string) $value === '1'
				: throw new \InvalidArgumentException(Text::sprintf('COM_JOOMLEAGUE_PROFILETEMPLATES_VALUE_INVALID', Text::_($labelKey))),
			'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false
				? (int) $value
				: throw new \InvalidArgumentException(Text::sprintf('COM_JOOMLEAGUE_PROFILETEMPLATES_VALUE_INVALID', Text::_($labelKey))),
			'string' => is_string($value)
				? $value
				: throw new \InvalidArgumentException(Text::sprintf('COM_JOOMLEAGUE_PROFILETEMPLATES_VALUE_INVALID', Text::_($labelKey))),
			default => throw new \InvalidArgumentException(Text::sprintf('COM_JOOMLEAGUE_PROFILETEMPLATES_VALUE_INVALID', Text::_($labelKey))),
		};
	}

	private function formatValue(mixed $value): string|int
	{
		return is_bool($value) ? ($value ? '1' : '0') : $value;
	}

	private function fieldKey(string $templateCode, string $fieldName): string
	{
		return 'template_' . substr(hash('sha256', $templateCode . ':' . $fieldName), 0, 24);
	}
}
