<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\CMS\User\CurrentUserTrait;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

final class PersonModel extends AdminModel implements CurrentUserInterface
{
	use CurrentUserTrait;

	protected $text_prefix = 'COM_JOOMLEAGUE_PERSON';

	public function getTable($name = 'Person', $prefix = 'Administrator', $options = []): Table
	{
		return parent::getTable($name, $prefix, $options);
	}

	public function getForm($data = [], $loadData = true): Form|false
	{
		return $this->loadForm('com_joomleague.person', 'person', ['control' => 'jform', 'load_data' => $loadData]);
	}

	protected function loadFormData(): array|object
	{
		$data = Factory::getApplication()->getUserState('com_joomleague.edit.person.data', []);

		return $data ?: $this->getItem();
	}

	protected function prepareTable($table): void
	{
		$now = Factory::getDate()->toSql();
		$userId = (int) $this->getCurrentUser()->id;

		if ((int) $table->id === 0) {
			$table->uuid = UuidFactory::v4();
			$table->created = $now;
			$table->created_by = $userId;
			$table->ordering = $table->ordering ?: $table->getNextOrder();
		} else {
			$table->modified = $now;
			$table->modified_by = $userId;
		}
	}

	protected function canDelete($record): bool
	{
		if (!parent::canDelete($record)) {
			return false;
		}

		$personId = (int) $record->id;
		$db = $this->getDatabase();
		$count = 0;

		foreach ([
			'#__joomleague_project_entry',
			'#__joomleague_project_entry_member',
			'#__joomleague_project_actor_role',
			'#__joomleague_match_actor_role',
			'#__joomleague_match_lineup_member',
			'#__joomleague_match_statistic_value',
		] as $table) {
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->quoteName($table))
				->where($db->quoteName('person_id') . ' = :personId')
				->bind(':personId', $personId, ParameterType::INTEGER);
			$count += (int) $db->setQuery($query)->loadResult();
		}

		$eventQuery = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__joomleague_match_event'))
			->where('(' . $db->quoteName('primary_person_id') . ' = :primaryPersonId OR ' . $db->quoteName('secondary_person_id') . ' = :secondaryPersonId)')
			->bind(':primaryPersonId', $personId, ParameterType::INTEGER)
			->bind(':secondaryPersonId', $personId, ParameterType::INTEGER);
		$count += (int) $db->setQuery($eventQuery)->loadResult();

		if ($count > 0) {
			$this->setError(Text::sprintf('COM_JOOMLEAGUE_ERROR_PERSON_IN_USE', $count));

			return false;
		}

		return true;
	}
}
