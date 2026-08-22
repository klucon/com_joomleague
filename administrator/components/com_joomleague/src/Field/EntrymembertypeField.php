<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectEntryContextRepository;

final class EntrymembertypeField extends ListField
{
	protected $type = 'Entrymembertype';

	protected function getOptions(): array
	{
		$entryId = Factory::getApplication()->getInput()->getInt('entry_id');

		if ($entryId < 1 && is_object($this->form->getData())) {
			$entryId = (int) $this->form->getData()->get('entry_id');
		}

		if ($entryId < 1) {
			return [];
		}

		$entry = (new ProjectEntryContextRepository(Factory::getContainer()->get(DatabaseInterface::class)))->get($entryId);
		$options = [];

		foreach ($entry->profile['entry_model']['member_person_types'] ?? [] as $personType) {
			$options[] = HTMLHelper::_('select.option', $personType, Text::_('COM_JOOMLEAGUE_PERSON_TYPE_' . strtoupper($personType)));
		}

		return array_merge(parent::getOptions(), $options);
	}
}
