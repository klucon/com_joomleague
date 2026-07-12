<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Factory;
use Joomla\CMS\Fields\FieldsServiceInterface;
use Joomla\CMS\Language\Text;

final class JoomleagueComponent extends MVCComponent implements RouterServiceInterface, FieldsServiceInterface
{
	use RouterServiceTrait;

	/**
	 * Sekce, které v této komponentě podporují Joomla Custom Fields (com_fields).
	 * Kontext má tvar com_joomleague.<sekce>, např. com_joomleague.club.
	 *
	 * @var string[]
	 */
	private const FIELD_SECTIONS = ['club', 'team', 'person'];

	/**
	 * Ověří, že sekce kontextu je pro custom fields podporovaná.
	 * Vrací normalizovaný název sekce, nebo null pokud sekci neznáme.
	 */
	public function validateSection($section, $item = null)
	{
		return \in_array($section, self::FIELD_SECTIONS, true) ? $section : null;
	}

	/**
	 * Kontexty, které com_fields nabídne ve správě polí.
	 *
	 * @return array<string,string>
	 */
	public function getContexts(): array
	{
		Factory::getApplication()->getLanguage()->load('com_joomleague', JPATH_ADMINISTRATOR);

		return [
			'com_joomleague.club'   => Text::_('COM_JOOMLEAGUE_FIELDS_CONTEXT_CLUB'),
			'com_joomleague.team'   => Text::_('COM_JOOMLEAGUE_FIELDS_CONTEXT_TEAM'),
			'com_joomleague.person' => Text::_('COM_JOOMLEAGUE_FIELDS_CONTEXT_PERSON'),
		];
	}
}
