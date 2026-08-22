<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\FormModel;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectRuleConfigRepository;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectRuleValidator;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectContextRepository;
use SimpleXMLElement;

final class ProjectrulesModel extends FormModel
{
	/** @var list<array<string,mixed>> */
	private array $ruleFields = [];

	public function getProject(int $projectId): object
	{
		$project = (new ProjectContextRepository($this->getDatabase()))->get($projectId);
		(new ProjectRuleValidator())->validateProfileSchema($project->profile);
		return $project;
	}

	public function getForm($data = [], $loadData = true): Form|false
	{
		$projectId = is_int($data) ? $data : Factory::getApplication()->getInput()->getInt('project_id');
		$project = $this->getProject($projectId);
		$repository = new ProjectRuleConfigRepository($this->getDatabase());
		$overrides = $repository->get($projectId);
		$posted = Factory::getApplication()->getUserState('com_joomleague.edit.projectrules.data', []);
		Factory::getApplication()->setUserState('com_joomleague.edit.projectrules.data', null);
		$form = $this->loadForm('com_joomleague.projectrules', 'projectrules', ['control' => 'jform', 'load_data' => false]);
		if (!$form instanceof Form) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_FORM_LOAD'));
		$form->setValue('project_id', null, $projectId);

		foreach ($project->profile['project_rule_schema']['fields'] as $pointer => $definition) {
			$key = $this->fieldKey($pointer);
			$hasOverride = $this->hasPointer($overrides, $pointer);
			$default = $this->readPointer($project->profile, $pointer);
			$value = $hasOverride ? $this->readPointer($overrides, $pointer) : $default;
			if ($posted !== []) {
				$submitted = (array) ($posted['rules'] ?? []);
				$hasOverride = (string) ($submitted[$key . '_enabled'] ?? '0') === '1';
				$value = $submitted[$key . '_value'] ?? $value;
			}

			$enabledXml = new SimpleXMLElement('<field name="' . $key . '_enabled" type="checkbox" value="1" label="COM_JOOMLEAGUE_PROJECTRULES_OVERRIDE_LABEL" description="COM_JOOMLEAGUE_PROJECTRULES_OVERRIDE_DESC" />');
			$form->setField($enabledXml, 'rules', true, 'rules');
			$form->setValue($key . '_enabled', 'rules', $hasOverride ? '1' : '0');
			$valueXml = new SimpleXMLElement($this->buildValueFieldXml($key . '_value', $definition));
			$form->setField($valueXml, 'rules', true, 'rules');
			$form->setValue($key . '_value', 'rules', $this->formatValue($value));
			$this->ruleFields[] = ['pointer' => $pointer, 'label' => $this->humanizePointer($pointer), 'description' => Text::sprintf('COM_JOOMLEAGUE_PROJECTRULES_RULE_DESC', $this->humanizePointer($pointer)), 'default' => $default, 'enabled_field' => $form->getField($key . '_enabled', 'rules'), 'value_field' => $form->getField($key . '_value', 'rules')];
		}
		return $form;
	}

	/** @return list<array<string,mixed>> */
	public function getRuleFields(): array
	{
		return $this->ruleFields;
	}

	public function saveSubmittedRules(int $projectId, array $submitted, int $actorId): void
	{
		$project = $this->getProject($projectId);
		$overrides = [];
		foreach ($project->profile['project_rule_schema']['fields'] as $pointer => $definition) {
			$key = $this->fieldKey($pointer);
			if ((string) ($submitted[$key . '_enabled'] ?? '0') !== '1') continue;
			if (!array_key_exists($key . '_value', $submitted)) throw new \InvalidArgumentException(Text::sprintf('COM_JOOMLEAGUE_PROJECTRULES_VALUE_REQUIRED', $this->humanizePointer($pointer)));
			$this->writePointer($overrides, $pointer, $this->parseValue($submitted[$key . '_value'], $definition, $pointer));
		}
		$this->saveRules($projectId, $overrides, $actorId);
	}

	public function saveRules(int $projectId, array $overrides, int $actorId): void
	{
		(new ProjectRuleConfigRepository($this->getDatabase()))->save($projectId, $overrides, $actorId);
	}

	private function buildValueFieldXml(string $name, array $definition): string
	{
		$type = $definition['type'];
		$attributes = ' name="' . $name . '" label="COM_JOOMLEAGUE_PROJECTRULES_VALUE_LABEL" description="COM_JOOMLEAGUE_PROJECTRULES_VALUE_DESC"';
		if ($type === 'boolean') return '<field' . $attributes . ' type="radio" layout="joomla.form.field.radio.switcher"><option value="0">JNO</option><option value="1">JYES</option></field>';
		if (in_array($type, ['integer', 'number'], true)) {
			$step = $type === 'integer' ? '1' : 'any';
			$range = isset($definition['minimum']) ? ' min="' . $definition['minimum'] . '"' : '';
			$range .= isset($definition['maximum']) ? ' max="' . $definition['maximum'] . '"' : '';
			return '<field' . $attributes . ' type="number" step="' . $step . '"' . $range . ' />';
		}
		if (isset($definition['enum'])) {
			$options = '';
			foreach ($definition['enum'] as $option) $options .= '<option value="' . htmlspecialchars((string) $option, ENT_XML1) . '">' . htmlspecialchars((string) $option, ENT_XML1) . '</option>';
			return '<field' . $attributes . ' type="list">' . $options . '</field>';
		}
		return '<field' . $attributes . ' type="text" maxlength="' . (int) ($definition['max_length'] ?? 255) . '" />';
	}

	private function parseValue(mixed $raw, array $definition, string $pointer): mixed
	{
		$type = $definition['type'];
		$value = is_string($raw) ? trim($raw) : $raw;
		if ($type === 'boolean') return (string) $value === '1';
		if ($type === 'integer' && is_string($value) && preg_match('/^-?\d+$/', $value) === 1) return (int) $value;
		if ($type === 'number' && is_numeric($value)) return (float) $value;
		if ($type === 'string' && is_string($value)) return $value;
		if ($type === 'array' && is_string($value)) {
			$items = $value === '' ? [] : array_map('trim', explode(',', $value));
			return array_map(function (string $item) use ($definition, $pointer): int|float|string {
				return match ($definition['items']['type']) { 'integer' => preg_match('/^-?\d+$/', $item) === 1 ? (int) $item : throw new \InvalidArgumentException(Text::sprintf('COM_JOOMLEAGUE_PROJECTRULES_VALUE_INVALID', $this->humanizePointer($pointer))), 'number' => is_numeric($item) ? (float) $item : throw new \InvalidArgumentException(Text::sprintf('COM_JOOMLEAGUE_PROJECTRULES_VALUE_INVALID', $this->humanizePointer($pointer))), default => $item };
			}, $items);
		}
		throw new \InvalidArgumentException(Text::sprintf('COM_JOOMLEAGUE_PROJECTRULES_VALUE_INVALID', $this->humanizePointer($pointer)));
	}

	private function formatValue(mixed $value): string|int|float
	{
		if (is_bool($value)) return $value ? '1' : '0';
		if (is_array($value)) return implode(', ', array_map(static fn ($item): string => (string) $item, $value));
		return $value;
	}

	private function humanizePointer(string $pointer): string
	{
		return implode(' / ', array_map(static fn (string $part): string => ucwords(str_replace('_', ' ', $part)), explode('/', trim($pointer, '/'))));
	}

	private function fieldKey(string $pointer): string
	{
		return 'rule_' . substr(hash('sha256', $pointer), 0, 24);
	}

	private function hasPointer(array $source, string $pointer): bool
	{
		$value = $source;
		foreach (explode('/', trim($pointer, '/')) as $key) {
			if (!is_array($value) || !array_key_exists($key, $value)) return false;
			$value = $value[$key];
		}
		return true;
	}

	private function readPointer(array $source, string $pointer): mixed
	{
		$value = $source;
		foreach (explode('/', trim($pointer, '/')) as $key) $value = $value[$key];
		return $value;
	}

	private function writePointer(array &$target, string $pointer, mixed $value): void
	{
		$reference =& $target;
		foreach (explode('/', trim($pointer, '/')) as $key) $reference =& $reference[$key];
		$reference = $value;
	}
}
