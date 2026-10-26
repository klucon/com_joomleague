<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\EntryMemberPersonOptions;

final class EntrymembertypeField extends ListField
{
	protected $type = 'Entrymembertype';

	protected function getOptions(): array
	{
		try {
			$entry = (new EntryMemberPersonOptions(Factory::getContainer()->get(DatabaseInterface::class)))->context(
				(int) $this->form->getValue('entry_id'), (int) $this->form->getValue('id'), Factory::getApplication()->getIdentity()
			);
		} catch (\Throwable) {
			return [];
		}
		$options = [];

		foreach ($entry->profile['entry_model']['member_person_types'] ?? [] as $personType) {
			$options[] = HTMLHelper::_('select.option', $personType, Text::_('COM_JOOMLEAGUE_PERSON_TYPE_' . strtoupper($personType)));
		}

		return array_merge(parent::getOptions(), $options);
	}
}
