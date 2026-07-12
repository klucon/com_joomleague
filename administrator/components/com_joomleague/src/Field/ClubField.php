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
use Joomla\CMS\Uri\Uri;
use Joomla\Database\ParameterType;

/**
 * Autocomplete club selector for menu item request parameters.
 */
class ClubField extends FormField
{
	protected $type = 'Club';

	protected function getInput(): string
	{
		Factory::getApplication()->getLanguage()->load('com_joomleague', JPATH_ADMINISTRATOR);

		$value       = (int) $this->value;
		$title       = $value ? $this->getClubTitle($value) : '';
		$id          = $this->id;
		$searchId    = $id . '_search';
		$resultsId   = $id . '_results';
		$statusId    = $id . '_status';
		$endpoint    = (new Uri())->setPath(Uri::base(true) . '/index.php');
		$endpoint->setQuery([
			'option' => 'com_joomleague',
			'task'   => 'ajax.clubs',
			'format' => 'json',
		]);

		$strings = [
			'loading'   => Text::_('COM_JOOMLEAGUE_CLUB_AUTOCOMPLETE_LOADING'),
			'minChars'  => Text::_('COM_JOOMLEAGUE_CLUB_AUTOCOMPLETE_MIN_CHARS'),
			'noResults' => Text::_('COM_JOOMLEAGUE_CLUB_AUTOCOMPLETE_NO_RESULTS'),
		];

		$required = $this->required ? ' required' : '';
		$disabled = $this->disabled ? ' disabled' : '';
		$readonly = $this->readonly ? ' readonly' : '';
		$html = [];

		$html[] = '<div class="joomleague-club-autocomplete" style="position:relative;max-width:42rem">';
		$html[] = '<input type="hidden" name="' . htmlspecialchars($this->name, ENT_COMPAT, 'UTF-8') . '" id="' . htmlspecialchars($id, ENT_COMPAT, 'UTF-8') . '" value="' . htmlspecialchars((string) $value, ENT_COMPAT, 'UTF-8') . '">';
		$html[] = '<input type="text" id="' . htmlspecialchars($searchId, ENT_COMPAT, 'UTF-8') . '" class="form-control" value="' . htmlspecialchars($title, ENT_COMPAT, 'UTF-8') . '" placeholder="' . htmlspecialchars(Text::_('COM_JOOMLEAGUE_CLUB_AUTOCOMPLETE_PLACEHOLDER'), ENT_COMPAT, 'UTF-8') . '" autocomplete="off" aria-autocomplete="list" aria-controls="' . htmlspecialchars($resultsId, ENT_COMPAT, 'UTF-8') . '"' . $required . $disabled . $readonly . '>';
		$html[] = '<div id="' . htmlspecialchars($resultsId, ENT_COMPAT, 'UTF-8') . '" class="list-group" style="display:none;position:absolute;z-index:2000;left:0;right:0;max-height:16rem;overflow:auto;background:var(--body-bg,var(--template-bg-dark,#fff));color:var(--body-color,var(--template-text-dark,#212529));border:1px solid var(--border-color,#ced4da);border-radius:.25rem;box-shadow:0 .5rem 1rem rgba(0,0,0,.25);"></div>';
		$html[] = '<div id="' . htmlspecialchars($statusId, ENT_COMPAT, 'UTF-8') . '" class="form-text">' . Text::_('COM_JOOMLEAGUE_CLUB_AUTOCOMPLETE_HELP') . '</div>';
		$html[] = '</div>';
		$html[] = '<script>';
		$html[] = '(function(){';
		$html[] = 'const hidden=document.getElementById(' . json_encode($id) . ');';
		$html[] = 'const input=document.getElementById(' . json_encode($searchId) . ');';
		$html[] = 'const results=document.getElementById(' . json_encode($resultsId) . ');';
		$html[] = 'const status=document.getElementById(' . json_encode($statusId) . ');';
		$html[] = 'const endpoint=' . json_encode((string) $endpoint) . ';';
		$html[] = 'const messages=' . json_encode($strings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';';
		$html[] = 'let timer=null;let selectedLabel=' . json_encode($title, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';';
		$html[] = 'function hide(){results.style.display="none";results.replaceChildren();}';
		$html[] = 'function applyItemStyle(item){item.style.backgroundColor="var(--body-bg,var(--template-bg-dark,#fff))";item.style.color="var(--body-color,var(--template-text-dark,#212529))";item.style.borderColor="var(--border-color,#ced4da)";item.style.padding=".625rem .75rem";}';
		$html[] = 'function message(text){results.replaceChildren();const item=document.createElement("div");item.className="list-group-item";applyItemStyle(item);item.textContent=text;results.appendChild(item);results.style.display="block";}';
		$html[] = 'function choose(item){hidden.value=item.value;input.value=item.text;selectedLabel=item.text;hide();status.textContent=item.text;}';
		$html[] = 'function render(items){results.replaceChildren();if(!items.length){message(messages.noResults);return;}items.forEach(function(item){const button=document.createElement("button");button.type="button";button.className="list-group-item list-group-item-action";applyItemStyle(button);button.style.borderWidth="0 0 1px";button.style.textAlign="left";button.textContent=item.text;button.addEventListener("mouseenter",function(){button.style.backgroundColor="rgba(127,127,127,.18)";});button.addEventListener("mouseleave",function(){button.style.backgroundColor="var(--body-bg,var(--template-bg-dark,#fff))";});button.addEventListener("click",function(){choose(item);});results.appendChild(button);});results.style.display="block";}';
		$html[] = 'function search(){const term=input.value.trim();if(term!==selectedLabel){hidden.value="";}if(term.length<3){hide();status.textContent=messages.minChars;return;}message(messages.loading);fetch(endpoint+"&q="+encodeURIComponent(term),{credentials:"same-origin",headers:{"Accept":"application/json"}}).then(function(response){return response.ok?response.json():{items:[]};}).then(function(data){render(data.items||[]);}).catch(function(){render([]);});}';
		$html[] = 'input.addEventListener("input",function(){clearTimeout(timer);timer=setTimeout(search,250);});';
		$html[] = 'input.addEventListener("focus",function(){if(input.value.trim().length>=3){search();}});';
		$html[] = 'document.addEventListener("click",function(event){if(!input.contains(event.target)&&!results.contains(event.target)){hide();}});';
		$html[] = '})();';
		$html[] = '</script>';

		return implode("\n", $html);
	}

	private function getClubTitle(int $value): string
	{
		try {
			$db = $this->getDatabase();
			$query = $db->createQuery()
				->select($db->quoteName('name'))
				->from($db->quoteName('#__joomleague_club'))
				->where($db->quoteName('id') . ' = :id')
				->bind(':id', $value, ParameterType::INTEGER);

			return (string) ($db->setQuery($query)->loadResult() ?: $value);
		} catch (\Throwable $e) {
			return (string) $value;
		}
	}
}
