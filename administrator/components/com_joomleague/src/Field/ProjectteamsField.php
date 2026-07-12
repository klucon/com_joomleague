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

class ProjectteamsField extends FormField
{
	protected $type = 'Projectteams';

	protected function getInput(): string
	{
		$value = (int) $this->value;
		$projectId = $this->getProjectId();
		$options = $projectId > 0 ? $this->getTeams($projectId) : [];
		$endpoint = (new Uri())->setPath(Uri::base(true) . '/index.php');
		$endpoint->setQuery([
			'option'                => 'com_joomleague',
			'task'                  => 'ajax.projectteamsoptions',
			'format'                => 'json',
			Session::getFormToken() => 1,
		]);
		$html = [];

		$html[] = '<select name="' . htmlspecialchars($this->name, ENT_COMPAT, 'UTF-8') . '" id="' . htmlspecialchars($this->id, ENT_COMPAT, 'UTF-8') . '" class="form-select" data-joomleague-projectteams="1" data-endpoint="' . htmlspecialchars((string) $endpoint, ENT_COMPAT, 'UTF-8') . '" data-selected="' . $value . '" data-initial-count="' . count($options) . '">';
		$html[] = '<option value="">' . Text::_('JGLOBAL_SELECT_AN_OPTION') . '</option>';

		foreach ($options as $option) {
			$optionValue = (int) $option['value'];
			$html[] = '<option value="' . $optionValue . '"' . ($optionValue === $value ? ' selected' : '') . '>' . htmlspecialchars((string) $option['text'], ENT_COMPAT, 'UTF-8') . '</option>';
		}

		$html[] = '</select>';
		$html[] = '<script>';
		$html[] = '(function(){';
		$html[] = 'const select=document.getElementById(' . json_encode($this->id) . ');if(!select||select.dataset.ready==="1"){return;}select.dataset.ready="1";';
		$html[] = 'const form=select.closest("form")||document;const placeholder=select.options[0]?select.options[0].text:"";let lastProject="";';
		$html[] = 'function projectField(){return (form.elements&&form.elements["jform[request][project_id]"])||form.querySelector("[name$=\'[project_id]\']")||document.getElementById("jform_request_project_id")||document.getElementById("jform_project_id");}';
		$html[] = 'function projectValue(){const field=projectField();return field?String(field.value||""):"";}';
		$html[] = 'function syncFancy(){const wrapper=select.closest("joomla-field-fancy-select");if(!wrapper||!wrapper.choicesInstance){return;}const choices=Array.from(select.options).map(function(option){return {value:option.value,label:option.text,selected:option.selected,disabled:option.disabled};});wrapper.choicesInstance.clearStore();wrapper.choicesInstance.setChoices(choices,"value","label",true);if(select.value){wrapper.choicesInstance.setChoiceByValue(select.value);}}';
		$html[] = 'function fill(items){const selected=select.dataset.selected||select.value||"";select.replaceChildren(new Option(placeholder,""));items.forEach(function(item){select.add(new Option(item.text,item.value,false,String(item.value)===String(selected)));});select.disabled=false;if(select.value){select.dataset.selected=select.value;}syncFancy();select.dispatchEvent(new Event("change",{bubbles:true}));}';
		$html[] = 'function load(force){const project=projectValue();if(!project){lastProject="";fill([]);return;}if(!force&&project===lastProject){return;}lastProject=project;select.disabled=true;fetch(select.dataset.endpoint+"&p="+encodeURIComponent(project),{credentials:"same-origin",headers:{"Accept":"application/json"}}).then(function(response){return response.ok?response.json():{items:[]};}).then(function(data){fill(data.items||[]);}).catch(function(){fill([]);});}';
		$html[] = 'document.addEventListener("change",function(event){const field=projectField();if(field&&event.target===field){select.dataset.selected="";load(true);}},true);';
		$html[] = 'document.addEventListener("input",function(event){const field=projectField();if(field&&event.target===field){select.dataset.selected="";load(true);}},true);';
		$html[] = 'setTimeout(function(){load(true);},100);setInterval(function(){load(false);},500);';
		$html[] = '})();';
		$html[] = '</script>';

		return implode("\n", $html);
	}

	private function getProjectId(): int
	{
		$value = (int) ($this->form?->getValue('project_id', $this->group) ?: 0);

		if ($value > 0) {
			return $value;
		}

		$menuItemId = Factory::getApplication()->getInput()->getInt('id');

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

			return (int) ($queryValues['project_id'] ?? 0);
		} catch (\Throwable $e) {
			return 0;
		}
	}

	private function getTeams(int $projectId): array
	{
		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select('pt.id AS value, t.name AS text')
			->from($db->quoteName('#__joomleague_project_team', 'pt'))
			->join('INNER', $db->quoteName('#__joomleague_team', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('pt.team_id'))
			->where($db->quoteName('pt.project_id') . ' = :project_id')
			->order($db->quoteName('pt.ordering') . ' ASC, ' . $db->quoteName('t.name') . ' ASC')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		return $db->setQuery($query)->loadAssocList() ?: [];
	}
}
