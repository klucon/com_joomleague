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

final class EntrymemberroleField extends ListField
{
	protected $type = 'Entrymemberrole';

	protected function getOptions(): array
	{
		try {
			$entry = (new EntryMemberPersonOptions(Factory::getContainer()->get(DatabaseInterface::class)))->context(
				(int) $this->form->getValue('entry_id'), (int) $this->form->getValue('id'), Factory::getApplication()->getIdentity()
			);
		} catch (\Throwable) {
			return [];
		}
		$memberTypes = $entry->profile['entry_model']['member_person_types'] ?? [];
		$options = [HTMLHelper::_('select.option', '', Text::_('COM_JOOMLEAGUE_OPTION_SELECT_ROLE'))];

		foreach ($entry->profile['positions'] ?? [] as $position) {
			$personType = (string) ($position['person_type'] ?? '');

			if (!in_array($personType, $memberTypes, true)) {
				continue;
			}

			$option = HTMLHelper::_('select.option', (string) $position['code'], Text::_((string) $position['name_key']));
			$option->attributes = 'data-person-type="' . htmlspecialchars($personType, ENT_QUOTES, 'UTF-8') . '"';
			$options[] = $option;
		}

		return array_merge(parent::getOptions(), $options);
	}

	protected function getInput(): string
	{
		return HTMLHelper::_('select.genericlist', $this->getOptions(), $this->name, [
			'id' => $this->id,
			'list.attr' => ['class' => 'form-select'],
			'list.select' => $this->value,
			'list.translate' => false,
			'option.attr' => 'attributes',
		]);
	}
}
