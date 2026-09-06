<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\CanonicalJson;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectTemplateConfigRepository;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectContextRepository;
use Joomleague\Component\Joomleague\Administrator\Service\TemplateConfigResolver;
use Joomleague\Component\Joomleague\Administrator\Service\TemplateDefinitionRegistry;
use SimpleXMLElement;

final class ProjecttemplatesModel extends FormModel
{
	/** @var list<array<string,mixed>> */
	private array $templateGroups = [];

	public function getProject(int $projectId): object
	{
		return (new ProjectContextRepository($this->getDatabase()))->get($projectId);
	}

	public function getForm($data = [], $loadData = true): Form|false
	{
		$this->templateGroups = [];
		$projectId = is_int($data) ? $data : Factory::getApplication()->getInput()->getInt('project_id');
		$project = $this->getProject($projectId);
		$registry = new TemplateDefinitionRegistry();
		$resolver = new TemplateConfigResolver($registry);
		$projectOverrides = (new ProjectTemplateConfigRepository($this->getDatabase(), $registry))->getAll($projectId);
		$profileOverrides = $this->loadProfileOverrides((int) $project->profile_version_id, $registry);
		$posted = Factory::getApplication()->getUserState('com_joomleague.edit.projecttemplates.data', []);
		Factory::getApplication()->setUserState('com_joomleague.edit.projecttemplates.data', null);
		if ((int) ($posted['project_id'] ?? 0) !== $projectId) {
			$posted = [];
		}
		$form = $this->loadForm('com_joomleague.projecttemplates', 'projecttemplates', ['control' => 'jform', 'load_data' => false]);
		if (!$form instanceof Form) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_FORM_LOAD'));
		$form->setValue('project_id', null, $projectId);

		foreach (($project->profile['template_defaults'] ?? []) as $templateCode => $unusedDefaults) {
			$definition = $registry->get((string) $templateCode);
			$inherited = $resolver->resolveProfileTemplate((string) $templateCode, $project->profile, $profileOverrides[$templateCode] ?? []);
			$currentOverrides = $projectOverrides[$templateCode] ?? [];
			$effective = $resolver->resolveProfileTemplate((string) $templateCode, $project->profile, $profileOverrides[$templateCode] ?? [], $currentOverrides);
			$fields = [];

			foreach ($definition['fields'] as $fieldName => $metadata) {
				$key = $this->fieldKey((string) $templateCode, (string) $fieldName);
				$enabled = array_key_exists($fieldName, $currentOverrides);
				$value = $enabled ? $currentOverrides[$fieldName] : $inherited[$fieldName];
				if ($posted !== []) {
					$submitted = (array) ($posted['templates'] ?? []);
					$enabled = (string) ($submitted[$key . '_enabled'] ?? '0') === '1';
					$value = $submitted[$key . '_value'] ?? $value;
				}
				$form->setField(new SimpleXMLElement('<field name="' . $key . '_enabled" type="checkbox" value="1" label="COM_JOOMLEAGUE_PROJECTTEMPLATES_OVERRIDE_LABEL" description="COM_JOOMLEAGUE_PROJECTTEMPLATES_OVERRIDE_DESC" />'), 'templates', true, 'templates');
				$form->setValue($key . '_enabled', 'templates', $enabled ? '1' : '0');
				$form->setField(new SimpleXMLElement($this->buildValueFieldXml($key . '_value', $metadata)), 'templates', true, 'templates');
				$form->setValue($key . '_value', 'templates', $this->formatValue($value));
				$fields[] = ['name' => $fieldName, 'label_key' => $metadata['label_key'], 'description_key' => $metadata['description_key'], 'inherited' => $inherited[$fieldName], 'effective' => $effective[$fieldName], 'enabled_field' => $form->getField($key . '_enabled', 'templates'), 'value_field' => $form->getField($key . '_value', 'templates')];
			}
			$this->templateGroups[] = ['code' => $templateCode, 'name_key' => $definition['name_key'], 'description_key' => $definition['description_key'], 'fields' => $fields];
		}
		return $form;
	}

	/** @return list<array<string,mixed>> */
	public function getTemplateGroups(): array { return $this->templateGroups; }

	public function saveSubmittedTemplates(int $projectId, array $submitted, int $actorId): void
	{
		$project = $this->getProject($projectId);
		$registry = new TemplateDefinitionRegistry();
		$configs = [];
		foreach (($project->profile['template_defaults'] ?? []) as $templateCode => $unusedDefaults) {
			$definition = $registry->get((string) $templateCode);
			$params = [];
			foreach ($definition['fields'] as $fieldName => $metadata) {
				$key = $this->fieldKey((string) $templateCode, (string) $fieldName);
				if ((string) ($submitted[$key . '_enabled'] ?? '0') !== '1') continue;
				if (!array_key_exists($key . '_value', $submitted)) throw new \InvalidArgumentException(Text::sprintf('COM_JOOMLEAGUE_PROJECTTEMPLATES_VALUE_REQUIRED', Text::_($metadata['label_key'])));
				$params[$fieldName] = $this->parseValue($submitted[$key . '_value'], $metadata, (string) $metadata['label_key']);
			}
			$registry->validateValues((string) $templateCode, $params);
			$configs[(string) $templateCode] = $params;
		}
		$repository = new ProjectTemplateConfigRepository($this->getDatabase(), $registry);
		$repository->saveAll($projectId, $configs, $actorId);
	}

	/** @return array<string,array<string,mixed>> */
	private function loadProfileOverrides(int $profileVersionId, TemplateDefinitionRegistry $registry): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)->select(['template_code','schema_version','params_json','params_checksum'])->from($db->quoteName('#__joomleague_profile_template_config'))->where('profile_version_id = :versionId')->where('published = 1')->bind(':versionId', $profileVersionId, ParameterType::INTEGER);
		$result = [];
		foreach ($db->setQuery($query)->loadObjectList() as $row) {
			if (!hash_equals(TemplateDefinitionRegistry::SCHEMA_VERSION, (string) $row->schema_version)) throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_PROFILE_CONFIG_UNSUPPORTED');
			$params = $this->decodeObject((string) $row->params_json);
			$registry->validateValues((string) $row->template_code, $params);
			if (!hash_equals(CanonicalJson::checksum($params), (string) $row->params_checksum)) throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_PROFILE_CHECKSUM');
			$result[(string) $row->template_code] = $params;
		}
		return $result;
	}

	private function buildValueFieldXml(string $name, array $metadata): string
	{
		$attributes = ' name="' . $name . '" label="' . $metadata['label_key'] . '" description="' . $metadata['description_key'] . '"';
		if ($metadata['type'] === 'boolean') return '<field' . $attributes . ' type="radio" layout="joomla.form.field.radio.switcher"><option value="0">JNO</option><option value="1">JYES</option></field>';
		if ($metadata['type'] === 'integer') return '<field' . $attributes . ' type="number" step="1" />';
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

	private function parseValue(mixed $value, array $metadata, string $labelKey): bool|int|string
	{
		if (!is_string($value) && !is_int($value)) {
			throw new \InvalidArgumentException(Text::sprintf('COM_JOOMLEAGUE_PROJECTTEMPLATES_VALUE_INVALID', Text::_($labelKey)));
		}
		$value = is_string($value) ? trim($value) : $value;
		return match ($metadata['type']) {
			'boolean' => in_array((string) $value, ['0', '1'], true)
				? (string) $value === '1'
				: throw new \InvalidArgumentException(Text::sprintf('COM_JOOMLEAGUE_PROJECTTEMPLATES_VALUE_INVALID', Text::_($labelKey))),
			'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : throw new \InvalidArgumentException(Text::sprintf('COM_JOOMLEAGUE_PROJECTTEMPLATES_VALUE_INVALID', Text::_($labelKey))),
			'string' => is_string($value) ? $value : throw new \InvalidArgumentException(Text::sprintf('COM_JOOMLEAGUE_PROJECTTEMPLATES_VALUE_INVALID', Text::_($labelKey))),
			default => throw new \InvalidArgumentException(Text::sprintf('COM_JOOMLEAGUE_PROJECTTEMPLATES_VALUE_INVALID', Text::_($labelKey))),
		};
	}

	private function formatValue(mixed $value): string|int { return is_bool($value) ? ($value ? '1' : '0') : $value; }
	private function fieldKey(string $templateCode, string $fieldName): string { return 'template_' . substr(hash('sha256', $templateCode . ':' . $fieldName), 0, 24); }
	/** @return array<string,mixed> */
	private function decodeObject(string $json): array
	{
		$value = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
		if (!is_array($value) || (array_is_list($value) && $value !== [])) throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_CONFIG_INVALID');
		return $value;
	}
}
