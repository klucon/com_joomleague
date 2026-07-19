<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\ParameterType;

/**
 * Dependent select field for menu request parameters.
 */
class DynamicoptionsField extends FormField
{
	protected $type = 'Dynamicoptions';

	protected function getInput(): string
	{
		Factory::getApplication()->getLanguage()->load('com_joomleague', JPATH_ADMINISTRATOR);

		$task       = (string) ($this->element['task'] ?? '');
		$parent     = (string) ($this->element['parent'] ?? 'project_id');
		$parent2    = (string) ($this->element['parent2'] ?? '');
		$multiple   = ((string) ($this->element['multiple'] ?? '') === 'true') || ((string) ($this->element['multiple'] ?? '') === 'multiple') || ((string) ($this->element['multiple'] ?? '') === '1');
		$values     = $this->normaliseValues($this->value);
		$value      = $values[0] ?? 0;
		$parentValue = $this->getRequestValue($parent);
		$parent2Value = $parent2 !== '' ? $this->getRequestValue($parent2) : 0;
		$disabled   = ($this->disabled || $parentValue <= 0 || ($parent2 !== '' && $parent2Value <= 0)) ? ' disabled' : '';
		$required   = $this->required ? ' required' : '';
		$endpoint   = (new Uri())->setPath(Uri::base(true) . '/index.php');
		$endpoint->setQuery([
			'option'                => 'com_joomleague',
			'task'                  => 'ajax.' . $task,
			'format'                => 'json',
			Session::getFormToken() => 1,
		]);

		$options = $parentValue > 0 ? $this->getOptionsForTask($task, $parentValue, $parent2Value) : [];
		$html = [];
		$name = (string) $this->name;

		if ($multiple && !str_ends_with($name, '[]')) {
			$name .= '[]';
		}

		$html[] = '<select name="' . htmlspecialchars($name, ENT_COMPAT, 'UTF-8') . '" id="' . htmlspecialchars($this->id, ENT_COMPAT, 'UTF-8') . '" class="form-select" data-joomleague-dynamic-options="1" data-endpoint="' . htmlspecialchars((string) $endpoint, ENT_COMPAT, 'UTF-8') . '" data-parent="' . htmlspecialchars($parent, ENT_COMPAT, 'UTF-8') . '" data-parent2="' . htmlspecialchars($parent2, ENT_COMPAT, 'UTF-8') . '" data-selected="' . htmlspecialchars(json_encode($values), ENT_COMPAT, 'UTF-8') . '"' . ($multiple ? ' multiple' : '') . $required . $disabled . '>';
		$html[] = '<option value="">' . Text::_('JGLOBAL_SELECT_AN_OPTION') . '</option>';

		foreach ($options as $option) {
			$optionValue = (int) ($option['value'] ?? 0);
			$selected = in_array($optionValue, $values, true) ? ' selected' : '';
			$html[] = '<option value="' . $optionValue . '"' . $selected . '>' . htmlspecialchars((string) ($option['text'] ?? $optionValue), ENT_COMPAT, 'UTF-8') . '</option>';
		}

		if ($parentValue <= 0 && $value > 0) {
			$html[] = '<option value="' . $value . '" selected>' . htmlspecialchars($this->getValueTitle($task, $value), ENT_COMPAT, 'UTF-8') . '</option>';
		}

		$html[] = '</select>';
		$html[] = '<script>';
		$html[] = '(function(){';
		$html[] = 'const select=document.getElementById(' . json_encode($this->id) . ');';
		$html[] = 'if(!select||select.dataset.joomleagueReady==="1"){return;}select.dataset.joomleagueReady="1";';
		$html[] = 'const form=select.closest("form")||document;';
		$html[] = 'const parentName=select.dataset.parent;';
		$html[] = 'const parent2Name=select.dataset.parent2;';
		$html[] = 'function findRequestField(name){if(!name){return null;}const fullName="jform[request]["+name+"]";if(form.elements&&form.elements[fullName]){return form.elements[fullName];}return form.querySelector("[name$=\'["+name+"]\']")||document.getElementById("jform_request_"+name)||document.getElementById("jform_"+name);}';
		$html[] = 'function fieldValue(name){const field=findRequestField(name);return field?parseInt(field.value||"0",10):0;}';
		$html[] = 'function isParentField(target,name){return !!(target&&name&&(target.name==="jform[request]["+name+"]"||target.name&&target.name.endsWith("["+name+"]")||target.id==="jform_request_"+name||target.id==="jform_"+name));}';
		$html[] = 'const placeholder=select.options[0]?select.options[0].text:"";';
		$html[] = 'function selectedValues(){try{const parsed=JSON.parse(select.dataset.selected||"[]");return Array.isArray(parsed)?parsed.map(String):[];}catch(e){return (select.dataset.selected||"").split(",").filter(Boolean);}}';
		$html[] = 'function currentValues(){return Array.from(select.selectedOptions).map(function(option){return option.value;}).filter(Boolean);}';
		$html[] = 'let lastKey="";';
		$html[] = 'function syncFancy(){const wrapper=select.closest("joomla-field-fancy-select");if(!wrapper||!wrapper.choicesInstance){return;}const choices=Array.from(select.options).map(function(option){return {value:option.value,label:option.text,selected:option.selected,disabled:option.disabled};});wrapper.choicesInstance.clearStore();wrapper.choicesInstance.setChoices(choices,"value","label",true);if(select.value){wrapper.choicesInstance.setChoiceByValue(select.value);}}';
		$html[] = 'function reset(){select.replaceChildren(new Option(placeholder,""));select.disabled=true;syncFancy();}';
		$html[] = 'function load(force){const p=fieldValue(parentName);const p2=fieldValue(parent2Name);const key=p+":"+p2;if(!force&&key===lastKey){return;}lastKey=key;if(!p||(parent2Name&&!p2)){reset();return;}const selected=selectedValues();select.disabled=true;fetch(select.dataset.endpoint+"&p="+encodeURIComponent(p)+"&pt="+encodeURIComponent(p2),{credentials:"same-origin",headers:{"Accept":"application/json"}}).then(function(response){return response.ok?response.json():{items:[]};}).then(function(data){select.replaceChildren(new Option(placeholder,""));(data.items||[]).forEach(function(item){const isSelected=selected.includes(String(item.value));select.add(new Option(item.text,item.value,false,isSelected));});select.disabled=false;select.dataset.selected=JSON.stringify(currentValues());syncFancy();select.dispatchEvent(new Event("change",{bubbles:true}));}).catch(reset);}';
		$html[] = 'function parentChanged(event){if(isParentField(event.target,parentName)||isParentField(event.target,parent2Name)){select.dataset.selected="[]";load(true);}}';
		$html[] = 'document.addEventListener("change",parentChanged,true);';
		$html[] = 'document.addEventListener("input",parentChanged,true);';
		$html[] = 'document.addEventListener("DOMContentLoaded",function(){load(true);});';
		$html[] = 'setTimeout(function(){load(true);},100);';
		$html[] = 'setTimeout(function(){load(false);},500);';
		$html[] = 'setTimeout(function(){load(false);},1000);';
		$html[] = 'setInterval(function(){load(false);},750);';
		$html[] = 'load(true);';
		$html[] = '})();';
		$html[] = '</script>';

		return implode("\n", $html);
	}

	private function normaliseValues(mixed $value): array
	{
		if (is_array($value)) {
			$values = [];

			foreach ($value as $item) {
				array_push($values, ...$this->normaliseValues($item));
			}

			return array_values(array_unique(array_filter($values)));
		}

		if (is_string($value) && str_contains($value, ',')) {
			return $this->normaliseValues(explode(',', $value));
		}

		$number = (int) $value;

		return $number > 0 ? [$number] : [];
	}

	private function getRequestValue(string $name): int
	{
		$value = (int) ($this->form?->getValue($name, $this->group) ?: 0);

		if ($value > 0) {
			return $value;
		}

		$menuItemId = Factory::getApplication()->getInput()->getInt('id', 0);

		if ($menuItemId <= 0) {
			return 0;
		}

		try {
			$db = $this->getDatabase();
			$query = $db->createQuery()
				->select($db->quoteName('link'))
				->from($db->quoteName('#__menu'))
				->where($db->quoteName('id') . ' = :id')
				->bind(':id', $menuItemId, ParameterType::INTEGER);
			parse_str((string) parse_url((string) $db->setQuery($query)->loadResult(), PHP_URL_QUERY), $queryValues);

			return (int) ($queryValues[$name] ?? 0);
		} catch (\Throwable $e) {
			return 0;
		}
	}

	private function getOptionsForTask(string $task, int $parentValue, int $parent2Value): array
	{
		$db = $this->getDatabase();

		try {
			if ($task === 'projecteventtypesoptions') {
				$query = $db->createQuery()
					->select('DISTINCT et.id AS value, et.name AS text')
					->from($db->quoteName('#__joomleague_eventtype', 'et'))
					->join('INNER', $db->quoteName('#__joomleague_match_event', 'e') . ' ON ' . $db->quoteName('e.event_type_id') . ' = ' . $db->quoteName('et.id'))
					->join('INNER', $db->quoteName('#__joomleague_match', 'm') . ' ON ' . $db->quoteName('m.id') . ' = ' . $db->quoteName('e.match_id'))
					->join('INNER', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
					->where($db->quoteName('r.project_id') . ' = :project_id')
					->where($db->quoteName('m.published') . ' = 1')
					->where($db->quoteName('et.published') . ' = 1')
					->order($db->quoteName('et.ordering') . ' ASC, ' . $db->quoteName('et.name') . ' ASC')
					->bind(':project_id', $parentValue, ParameterType::INTEGER);
			} elseif ($task === 'projectstatisticsoptions') {
				$query = $db->createQuery()
					->select('DISTINCT s.id AS value, s.name AS text')
					->from($db->quoteName('#__joomleague_statistic', 's'))
					->join('INNER', $db->quoteName('#__joomleague_match_statistic', 'ms') . ' ON ' . $db->quoteName('ms.statistic_id') . ' = ' . $db->quoteName('s.id'))
					->join('INNER', $db->quoteName('#__joomleague_match', 'm') . ' ON ' . $db->quoteName('m.id') . ' = ' . $db->quoteName('ms.match_id'))
					->join('INNER', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
					->where($db->quoteName('r.project_id') . ' = :project_id')
					->where($db->quoteName('s.published') . ' = 1')
					->order($db->quoteName('s.ordering') . ' ASC, ' . $db->quoteName('s.name') . ' ASC')
					->bind(':project_id', $parentValue, ParameterType::INTEGER);
			} elseif ($task === 'projecttreesoptions') {
				$query = $db->createQuery()
					->select('t.id AS value, t.name AS text')
					->from($db->quoteName('#__joomleague_treeto', 't'))
					->where($db->quoteName('t.project_id') . ' = :project_id')
					->where($db->quoteName('t.published') . ' = 1')
					->order($db->quoteName('t.name') . ' ASC, ' . $db->quoteName('t.id') . ' ASC')
					->bind(':project_id', $parentValue, ParameterType::INTEGER);
			} elseif ($task === 'projectpredictiongamesoptions') {
				$query = $db->createQuery()
					->select('g.id AS value, g.name AS text')
					->from($db->quoteName('#__joomleague_prediction_game', 'g'))
					->where($db->quoteName('g.project_id') . ' = :project_id')
					->where($db->quoteName('g.published') . ' = 1')
					->order($db->quoteName('g.name') . ' ASC, ' . $db->quoteName('g.id') . ' ASC')
					->bind(':project_id', $parentValue, ParameterType::INTEGER);
			} elseif ($task === 'roundsoptions') {
				$query = $db->createQuery()
					->select('r.id AS value, r.name AS text')
					->from($db->quoteName('#__joomleague_round', 'r'))
					->where($db->quoteName('r.project_id') . ' = :project_id')
					->order($db->quoteName('r.roundcode') . ' ASC')
					->bind(':project_id', $parentValue, ParameterType::INTEGER);
			} elseif ($task === 'projectdivisionsoptions') {
				$query = $db->createQuery()
					->select('d.id AS value, d.name AS text')
					->from($db->quoteName('#__joomleague_division', 'd'))
					->where($db->quoteName('d.project_id') . ' = :project_id')
					->order($db->quoteName('d.name') . ' ASC')
					->bind(':project_id', $parentValue, ParameterType::INTEGER);
			} elseif ($task === 'projectclubsoptions') {
				$query = $db->createQuery()
					->select('DISTINCT c.id AS value, c.name AS text')
					->from($db->quoteName('#__joomleague_club', 'c'))
					->join('INNER', $db->quoteName('#__joomleague_team', 't') . ' ON ' . $db->quoteName('t.club_id') . ' = ' . $db->quoteName('c.id'))
					->join('INNER', $db->quoteName('#__joomleague_project_team', 'pt') . ' ON ' . $db->quoteName('pt.team_id') . ' = ' . $db->quoteName('t.id'))
					->where($db->quoteName('pt.project_id') . ' = :project_id')
					->order($db->quoteName('c.name') . ' ASC')
					->bind(':project_id', $parentValue, ParameterType::INTEGER);
			} elseif ($task === 'projectteamsoptions') {
				$query = $db->createQuery()
					->select('pt.id AS value, t.name AS text')
					->from($db->quoteName('#__joomleague_project_team', 'pt'))
					->join('INNER', $db->quoteName('#__joomleague_team', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('pt.team_id'))
					->where($db->quoteName('pt.project_id') . ' = :project_id')
					->order($db->quoteName('pt.ordering') . ' ASC, ' . $db->quoteName('t.name') . ' ASC')
					->bind(':project_id', $parentValue, ParameterType::INTEGER);
			} elseif ($task === 'projectteamsbaseoptions') {
				$query = $db->createQuery()
					->select('DISTINCT t.id AS value, t.name AS text')
					->from($db->quoteName('#__joomleague_team', 't'))
					->join('INNER', $db->quoteName('#__joomleague_project_team', 'pt') . ' ON ' . $db->quoteName('pt.team_id') . ' = ' . $db->quoteName('t.id'))
					->where($db->quoteName('pt.project_id') . ' = :project_id')
					->order($db->quoteName('t.name') . ' ASC')
					->bind(':project_id', $parentValue, ParameterType::INTEGER);
			} elseif ($task === 'matchesoptions') {
				$query = $db->createQuery()
					->select("m.id AS value, CONCAT(COALESCE(th.name, ''), ' - ', COALESCE(ta.name, ''), CASE WHEN m.match_date IS NULL OR m.match_date LIKE '0000-00-00%' THEN '' ELSE CONCAT(' (', DATE_FORMAT(m.match_date, '%d.%m.%Y'), ')') END) AS text")
					->from($db->quoteName('#__joomleague_match', 'm'))
					->join('INNER', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
					->join('LEFT', $db->quoteName('#__joomleague_project_team', 'pth') . ' ON ' . $db->quoteName('pth.id') . ' = ' . $db->quoteName('m.projectteam1_id'))
					->join('LEFT', $db->quoteName('#__joomleague_project_team', 'pta') . ' ON ' . $db->quoteName('pta.id') . ' = ' . $db->quoteName('m.projectteam2_id'))
					->join('LEFT', $db->quoteName('#__joomleague_team', 'th') . ' ON ' . $db->quoteName('th.id') . ' = ' . $db->quoteName('pth.team_id'))
					->join('LEFT', $db->quoteName('#__joomleague_team', 'ta') . ' ON ' . $db->quoteName('ta.id') . ' = ' . $db->quoteName('pta.team_id'))
					->where($db->quoteName('r.project_id') . ' = :project_id')
					->order($db->quoteName('m.match_date') . ' ASC, ' . $db->quoteName('m.match_number') . ' ASC')
					->bind(':project_id', $parentValue, ParameterType::INTEGER);

				if ($parent2Value > 0) {
					$query->where('(' . $db->quoteName('m.projectteam1_id') . ' = :projectteam_id OR ' . $db->quoteName('m.projectteam2_id') . ' = :projectteam_id)')
						->bind(':projectteam_id', $parent2Value, ParameterType::INTEGER);
				}
			} else {
				return [];
			}

			$rows = $db->setQuery($query)->loadAssocList() ?: [];

			foreach ($rows as &$row) {
				$row['text'] = Text::_((string) $row['text']);
			}

			return $rows;
		} catch (\Throwable $e) {
			return [];
		}
	}

	private function getValueTitle(string $task, int $value): string
	{
		try {
			$db = $this->getDatabase();

			if ($task === 'projecteventtypesoptions') {
				$query = $db->createQuery()
					->select($db->quoteName('name'))
					->from($db->quoteName('#__joomleague_eventtype'))
					->where($db->quoteName('id') . ' = :id')
					->bind(':id', $value, ParameterType::INTEGER);

				return (string) ($db->setQuery($query)->loadResult() ?: $value);
			}

			if ($task === 'projectstatisticsoptions') {
				$query = $db->createQuery()
					->select($db->quoteName('name'))
					->from($db->quoteName('#__joomleague_statistic'))
					->where($db->quoteName('id') . ' = :id')
					->bind(':id', $value, ParameterType::INTEGER);

				return (string) ($db->setQuery($query)->loadResult() ?: $value);
			}

			if ($task === 'projecttreesoptions') {
				$query = $db->createQuery()
					->select($db->quoteName('name'))
					->from($db->quoteName('#__joomleague_treeto'))
					->where($db->quoteName('id') . ' = :id')
					->bind(':id', $value, ParameterType::INTEGER);

				return (string) ($db->setQuery($query)->loadResult() ?: $value);
			}

			if ($task === 'projectpredictiongamesoptions') {
				$query = $db->createQuery()
					->select($db->quoteName('name'))
					->from($db->quoteName('#__joomleague_prediction_game'))
					->where($db->quoteName('id') . ' = :id')
					->bind(':id', $value, ParameterType::INTEGER);

				return (string) ($db->setQuery($query)->loadResult() ?: $value);
			}

			if ($task === 'roundsoptions') {
				$query = $db->createQuery()
					->select($db->quoteName('name'))
					->from($db->quoteName('#__joomleague_round'))
					->where($db->quoteName('id') . ' = :id')
					->bind(':id', $value, ParameterType::INTEGER);

				return (string) ($db->setQuery($query)->loadResult() ?: $value);
			}

			if ($task === 'projectdivisionsoptions') {
				$query = $db->createQuery()
					->select($db->quoteName('name'))
					->from($db->quoteName('#__joomleague_division'))
					->where($db->quoteName('id') . ' = :id')
					->bind(':id', $value, ParameterType::INTEGER);

				return (string) ($db->setQuery($query)->loadResult() ?: $value);
			}

			if ($task === 'projectclubsoptions') {
				$query = $db->createQuery()
					->select($db->quoteName('name'))
					->from($db->quoteName('#__joomleague_club'))
					->where($db->quoteName('id') . ' = :id')
					->bind(':id', $value, ParameterType::INTEGER);

				return (string) ($db->setQuery($query)->loadResult() ?: $value);
			}

			if ($task === 'projectteamsoptions') {
				$query = $db->createQuery()
					->select($db->quoteName('t.name'))
					->from($db->quoteName('#__joomleague_project_team', 'pt'))
					->join('INNER', $db->quoteName('#__joomleague_team', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('pt.team_id'))
					->where($db->quoteName('pt.id') . ' = :id')
					->bind(':id', $value, ParameterType::INTEGER);

				return (string) ($db->setQuery($query)->loadResult() ?: $value);
			}

			if ($task === 'projectteamsbaseoptions') {
				$query = $db->createQuery()
					->select($db->quoteName('name'))
					->from($db->quoteName('#__joomleague_team'))
					->where($db->quoteName('id') . ' = :id')
					->bind(':id', $value, ParameterType::INTEGER);

				return (string) ($db->setQuery($query)->loadResult() ?: $value);
			}

			if ($task === 'matchesoptions') {
				$query = $db->createQuery()
					->select("CONCAT(COALESCE(th.name, ''), ' - ', COALESCE(ta.name, ''))")
					->from($db->quoteName('#__joomleague_match', 'm'))
					->join('LEFT', $db->quoteName('#__joomleague_project_team', 'pth') . ' ON ' . $db->quoteName('pth.id') . ' = ' . $db->quoteName('m.projectteam1_id'))
					->join('LEFT', $db->quoteName('#__joomleague_project_team', 'pta') . ' ON ' . $db->quoteName('pta.id') . ' = ' . $db->quoteName('m.projectteam2_id'))
					->join('LEFT', $db->quoteName('#__joomleague_team', 'th') . ' ON ' . $db->quoteName('th.id') . ' = ' . $db->quoteName('pth.team_id'))
					->join('LEFT', $db->quoteName('#__joomleague_team', 'ta') . ' ON ' . $db->quoteName('ta.id') . ' = ' . $db->quoteName('pta.team_id'))
					->where($db->quoteName('m.id') . ' = :id')
					->bind(':id', $value, ParameterType::INTEGER);

				return (string) ($db->setQuery($query)->loadResult() ?: $value);
			}
		} catch (\Throwable $e) {
			return (string) $value;
		}

		return (string) $value;
	}
}
