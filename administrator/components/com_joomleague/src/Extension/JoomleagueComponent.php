<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Factory;
use Joomla\CMS\Fields\FieldsFormServiceInterface;
use Joomla\CMS\Fields\FieldsServiceTrait;
use Joomla\CMS\Language\Text;

final class JoomleagueComponent extends MVCComponent implements RouterServiceInterface, FieldsFormServiceInterface
{
	use RouterServiceTrait;
	use FieldsServiceTrait;

	private const FIELD_SECTIONS = ['project', 'club', 'team', 'person', 'venue', 'match'];

	public function validateSection($section, $item = null): ?string
	{
		return in_array($section, self::FIELD_SECTIONS, true) ? $section : null;
	}

	/** @return array<string,string> */
	public function getContexts(): array
	{
		Factory::getApplication()->getLanguage()->load('com_joomleague', JPATH_ADMINISTRATOR);

		$contexts = [];
		foreach (self::FIELD_SECTIONS as $section) {
			$contexts['com_joomleague.' . $section] = Text::_('COM_JOOMLEAGUE_FIELDS_CONTEXT_' . strtoupper($section));
		}

		return $contexts;
	}
}
