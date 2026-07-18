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
use Joomla\Database\ParameterType;

final class ProjectfavteamsField extends FormField
{
	protected $type = 'Projectfavteams';

	protected function getInput(): string
	{
		$projectId = $this->getProjectId();
		$options = $projectId > 0 ? $this->getProjectTeams($projectId) : [];
		$selectedValues = $this->selectedValues();
		$name = str_ends_with($this->name, '[]') ? $this->name : $this->name . '[]';
		$html = [];

		if ($options === []) {
			$html[] = '<input type="hidden" name="' . htmlspecialchars($this->name, ENT_COMPAT, 'UTF-8') . '" value="">';
			$html[] = '<select id="' . htmlspecialchars($this->id, ENT_COMPAT, 'UTF-8') . '" class="form-select" disabled>';
			$html[] = '<option value="">' . Text::_('COM_JOOMLEAGUE_PROJECT_NO_TEAMS_ASSIGNED') . '</option>';
		} else {
			$html[] = '<select name="' . htmlspecialchars($name, ENT_COMPAT, 'UTF-8') . '" id="' . htmlspecialchars($this->id, ENT_COMPAT, 'UTF-8') . '" class="form-select" multiple size="8">';

			foreach ($options as $option) {
				$value = (int) $option->value;
				$selected = \in_array($value, $selectedValues, true) ? ' selected' : '';
				$html[] = '<option value="' . $value . '"' . $selected . '>' . htmlspecialchars((string) $option->text, ENT_COMPAT, 'UTF-8') . '</option>';
			}
		}

		$html[] = '</select>';

		if ($options !== []) {
			$html[] = '<div class="form-text">' . Text::_('COM_JOOMLEAGUE_PROJECT_FIELD_FAV_TEAM_HELP') . '</div>';
		}

		return implode("\n", $html);
	}

	private function getProjectId(): int
	{
		$value = (int) ($this->form?->getValue('id') ?: 0);

		if ($value > 0) {
			return $value;
		}

		return Factory::getApplication()->getInput()->getInt('id', 0);
	}

	/**
	 * @return int[]
	 */
	private function selectedValues(): array
	{
		$value = $this->value;

		if (\is_array($value)) {
			$parts = $value;
		} else {
			$parts = explode(',', (string) $value);
		}

		return array_values(array_unique(array_filter(array_map('intval', $parts))));
	}

	/**
	 * @return object[]
	 */
	private function getProjectTeams(int $projectId): array
	{
		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select($db->quoteName('pt.id', 'value') . ', ' . $db->quoteName('t.name', 'text'))
			->from($db->quoteName('#__joomleague_project_team', 'pt'))
			->join('INNER', $db->quoteName('#__joomleague_team', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('pt.team_id'))
			->where($db->quoteName('pt.project_id') . ' = :project_id')
			->order($db->quoteName('pt.ordering') . ' ASC, ' . $db->quoteName('t.name') . ' ASC')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		return $db->setQuery($query)->loadObjectList() ?: [];
	}
}
