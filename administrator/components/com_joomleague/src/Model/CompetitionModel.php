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
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

final class CompetitionModel extends AdminModel implements CurrentUserInterface
{
	use CurrentUserTrait;

	protected $text_prefix = 'COM_JOOMLEAGUE_COMPETITION';

	public function getTable($name = 'Competition', $prefix = 'Administrator', $options = []): Table
	{
		return parent::getTable($name, $prefix, $options);
	}

	public function getForm($data = [], $loadData = true): Form|false
	{
		return $this->loadForm('com_joomleague.competition', 'competition', ['control' => 'jform', 'load_data' => $loadData]);
	}

	protected function loadFormData(): array|object
	{
		$data = Factory::getApplication()->getUserState('com_joomleague.edit.competition.data', []);

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

		$query = $this->getDatabase()->getQuery(true)
			->select('COUNT(*)')
			->from($this->getDatabase()->quoteName('#__joomleague_project'))
			->where($this->getDatabase()->quoteName('competition_id') . ' = :competitionId')
			->bind(':competitionId', $record->id, \Joomla\Database\ParameterType::INTEGER);

		$count = (int) $this->getDatabase()->setQuery($query)->loadResult();
		if ($count > 0) {
			$this->setError(Text::sprintf('COM_JOOMLEAGUE_ERROR_COMPETITION_IN_USE', $count));

			return false;
		}

		return true;
	}
}
