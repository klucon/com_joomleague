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

final class PositionsField extends FormField
{
	protected $type = 'Positions';

	protected function getInput(): string
	{
		$options = $this->getOptions();
		$currentValue = (int) $this->value;
		$html = [];

		$html[] = '<select name="' . htmlspecialchars($this->name, ENT_COMPAT, 'UTF-8') . '" id="' . htmlspecialchars($this->id, ENT_COMPAT, 'UTF-8') . '" class="form-select">';
		$html[] = '<option value="">' . Text::_('COM_JOOMLEAGUE_SELECT_POSITION') . '</option>';

		foreach ($options as $option) {
			$value = (int) $option['value'];
			$selected = $value === $currentValue ? ' selected' : '';
			$html[] = '<option value="' . $value . '"' . $selected . '>' . htmlspecialchars($option['text'], ENT_COMPAT, 'UTF-8') . '</option>';
		}

		$html[] = '</select>';

		return implode("\n", $html);
	}

	/**
	 * @return array<int, array{value:int,text:string}>
	 */
	private function getOptions(): array
	{
		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select($db->quoteName('id', 'value') . ', ' . $db->quoteName('name', 'text'))
			->from($db->quoteName('#__joomleague_position'));

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
