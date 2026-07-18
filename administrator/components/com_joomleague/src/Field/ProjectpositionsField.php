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

final class ProjectpositionsField extends FormField
{
	protected $type = 'Projectpositions';

	protected function getInput(): string
	{
		$projectId = $this->getProjectId();
		$personType = (int) ($this->element['persontype'] ?? 0);
		$options = $this->getOptions($projectId, $personType);
		$currentValue = (int) $this->value;
		$html = [];

		$html[] = '<select name="' . htmlspecialchars($this->name, ENT_COMPAT, 'UTF-8') . '" id="' . htmlspecialchars($this->id, ENT_COMPAT, 'UTF-8') . '" class="form-select">';
		$html[] = '<option value="">' . Text::_('JNONE') . '</option>';

		foreach ($options as $option) {
			$value = (int) $option['value'];
			$selected = $value === $currentValue ? ' selected' : '';
			$html[] = '<option value="' . $value . '"' . $selected . '>' . htmlspecialchars($option['text'], ENT_COMPAT, 'UTF-8') . '</option>';
		}

		$html[] = '</select>';

		return implode("\n", $html);
	}

	private function getProjectId(): int
	{
		$projectTeamId = (int) ($this->form?->getValue('projectteam_id') ?: 0);

		if ($projectTeamId < 1) {
			$projectTeamId = Factory::getApplication()->getInput()->getInt('projectteam_id', 0);
		}

		if ($projectTeamId < 1) {
			return 0;
		}

		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select($db->quoteName('project_id'))
			->from($db->quoteName('#__joomleague_project_team'))
			->where($db->quoteName('id') . ' = :projectteam_id')
			->bind(':projectteam_id', $projectTeamId, ParameterType::INTEGER);

		return (int) $db->setQuery($query)->loadResult();
	}

	/**
	 * @return array<int, array{value:int,text:string}>
	 */
	private function getOptions(int $projectId, int $personType): array
	{
		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select($db->quoteName('pp.id', 'value') . ', ' . $db->quoteName('p.name', 'text'))
			->from($db->quoteName('#__joomleague_project_position', 'pp'))
			->join('INNER', $db->quoteName('#__joomleague_position', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('pp.position_id'));

		if ($projectId > 0) {
			$query->where($db->quoteName('pp.project_id') . ' = :project_id')
				->bind(':project_id', $projectId, ParameterType::INTEGER);
		}

		if ($personType > 0) {
			$query->where($db->quoteName('p.persontype') . ' = :persontype')
				->bind(':persontype', $personType, ParameterType::INTEGER);
		}

		$rows = $db->setQuery($query)->loadAssocList() ?: [];
		$options = [];

		foreach ($rows as $row) {
			$options[] = [
				'value' => (int) $row['value'],
				'text' => Text::_((string) $row['text']),
			];
		}

		$collator = class_exists(\Collator::class) ? new \Collator(Factory::getApplication()->getLanguage()->getTag()) : null;

		usort($options, static function (array $a, array $b) use ($collator): int {
			if ($collator instanceof \Collator) {
				return $collator->compare($a['text'], $b['text']);
			}

			return strnatcasecmp($a['text'], $b['text']);
		});

		return $options;
	}
}
