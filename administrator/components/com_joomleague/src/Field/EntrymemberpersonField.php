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

final class EntrymemberpersonField extends ListField
{
	protected $type = 'Entrymemberperson';
	protected function getOptions(): array
	{
		$options = parent::getOptions();
		try {
			$app = Factory::getApplication();
			$lookup = new EntryMemberPersonOptions(Factory::getContainer()->get(DatabaseInterface::class));
			$entry = $lookup->context((int) $this->form->getValue('entry_id'), (int) $this->form->getValue('id'), $app->getIdentity());
			$rows = $lookup->search($entry, $app->getIdentity(), '');
			if ((int) $this->value > 0) {
				$selected = $lookup->search($entry, $app->getIdentity(), '', true, (int) $this->value);
				$rows = array_values(array_column(array_merge($rows, $selected), null, 'value'));
			}
			foreach ($rows as $row) $options[] = HTMLHelper::_('select.option', $row->value, $row->text);
		} catch (\Throwable) {
			// Do not disclose records when the stored parent cannot be authorised.
		}
		return $options;
	}

	protected function getInput(): string
	{
		$escape = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
		return '<label for="entrymember-person-search">' . Text::_('COM_JOOMLEAGUE_ENTRYMEMBER_SEARCH_PERSON') . '</label>'
			. '<input type="search" id="entrymember-person-search" class="form-control mb-2" maxlength="100" autocomplete="off">'
			. '<label class="mb-2"><input type="checkbox" id="entrymember-person-all"> ' . Text::_('COM_JOOMLEAGUE_ENTRYMEMBER_SEARCH_ALL') . '</label>'
			. parent::getInput()
			. '<div id="entrymember-person-status" class="form-text" role="status" aria-live="polite">' . $escape(Text::_('COM_JOOMLEAGUE_ENTRYMEMBER_SEARCH_HELP')) . '</div>';
	}
}
