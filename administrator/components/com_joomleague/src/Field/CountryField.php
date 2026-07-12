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
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

/**
 * Výběr státu z číselníku #__joomleague_country.
 * Hodnota = kód (např. "cz", "gb-eng"), který je zároveň názvem souboru vlajky.
 * Popisky se překládají přes jazykovou konstantu a řadí A-Z dle aktuálního jazyka.
 */
final class CountryField extends ListField
{
	protected $type = 'Country';

	protected function getOptions(): array
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->createQuery()
			->select($db->quoteName(['code', 'name']))
			->from($db->quoteName('#__joomleague_country'))
			->where($db->quoteName('published') . ' = 1');

		$rows = $db->setQuery($query)->loadObjectList() ?: [];

		$options = [];

		foreach ($rows as $row) {
			$options[] = (object) ['value' => $row->code, 'text' => Text::_($row->name)];
		}

		// Řazení A-Z dle přeloženého názvu v aktuálním jazyce (korektní kolace vč. češtiny).
		$tag = Factory::getApplication()->getLanguage()->getTag();

		if (class_exists('Collator')) {
			$collator = new \Collator(str_replace('-', '_', $tag));
			usort($options, static fn ($a, $b): int => (int) $collator->compare($a->text, $b->text));
		} else {
			usort($options, static fn ($a, $b): int => strcasecmp($a->text, $b->text));
		}

		// Prázdná volba na začátku.
		array_unshift($options, (object) ['value' => '', 'text' => Text::_('COM_JOOMLEAGUE_SELECT_COUNTRY')]);

		return array_merge(parent::getOptions(), $options);
	}
}
