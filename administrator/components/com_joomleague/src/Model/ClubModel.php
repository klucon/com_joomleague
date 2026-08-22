<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\CMS\User\CurrentUserTrait;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Administrator\Service\ClubRelatedRecordCreator;
use Joomleague\Component\Joomleague\Administrator\Service\OrganizationHistoryRepository;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

final class ClubModel extends AdminModel implements CurrentUserInterface
{
	use CurrentUserTrait;

	protected $text_prefix = 'COM_JOOMLEAGUE_CLUB';

	public function getTable($name = 'Club', $prefix = 'Administrator', $options = []): Table
	{
		return parent::getTable($name, $prefix, $options);
	}

	public function getForm($data = [], $loadData = true): Form|false
	{
		return $this->loadForm('com_joomleague.club', 'club', ['control' => 'jform', 'load_data' => $loadData]);
	}

	protected function loadFormData(): array|object
	{
		$data = Factory::getApplication()->getUserState('com_joomleague.edit.club.data', []);

		if ($data) {
			return $data;
		}

		$data = $this->getItem();

		if ((int) ($data->id ?? 0) > 0) {
			$history = (new OrganizationHistoryRepository($this->getDatabase()))->load('club', (int) $data->id);
			$data->name_history = $history['name_history'];
			$data->media_history = $history['media_history'];
		}

		return $data;
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

	public function save($data): bool
	{
		$isNew = (int) ($data['id'] ?? 0) === 0;
		$createTeam = $isNew && (int) ($data['create_team'] ?? 0) === 1;
		$createVenue = $isNew && (int) ($data['create_venue'] ?? 0) === 1;
		$database = $this->getDatabase();
		$database->transactionStart();

		try {
			if (!parent::save($data)) {
				$database->transactionRollback();

				return false;
			}

			$clubId = (int) $this->getState($this->getName() . '.id');

			if ($createTeam || $createVenue) {
				(new ClubRelatedRecordCreator($database))->create($clubId, $data, $createTeam, $createVenue, (int) $this->getCurrentUser()->id);
			}

			(new OrganizationHistoryRepository($database))->save('club', $clubId, $this->rows($data['name_history'] ?? []), $this->rows($data['media_history'] ?? []), (int) $this->getCurrentUser()->id);
			$database->transactionCommit();

			return true;
		} catch (\Throwable $exception) {
			$database->transactionRollback();
			Log::add($exception->getMessage(), Log::ERROR, 'com_joomleague');
			$this->setError(Text::_('COM_JOOMLEAGUE_ERROR_ORGANIZATION_HISTORY_SAVE'));

			return false;
		}
	}

	/** @return list<array<string,mixed>> */
	private function rows(mixed $rows): array
	{
		return is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
	}

	protected function canDelete($record): bool
	{
		if (!parent::canDelete($record)) {
			return false;
		}

		$clubId = (int) $record->id;
		$query = $this->getDatabase()->getQuery(true)
			->select('COUNT(*)')
			->from($this->getDatabase()->quoteName('#__joomleague_team'))
			->where($this->getDatabase()->quoteName('club_id') . ' = :clubId')
			->bind(':clubId', $clubId, ParameterType::INTEGER);

		$count = (int) $this->getDatabase()->setQuery($query)->loadResult();
		if ($count > 0) {
			$this->setError(Text::sprintf('COM_JOOMLEAGUE_ERROR_CLUB_IN_USE', $count));

			return false;
		}

		$personQuery = $this->getDatabase()->getQuery(true)
			->select('COUNT(*)')
			->from($this->getDatabase()->quoteName('#__joomleague_person'))
			->where($this->getDatabase()->quoteName('club_id') . ' = :clubId')
			->bind(':clubId', $clubId, ParameterType::INTEGER);

		$personCount = (int) $this->getDatabase()->setQuery($personQuery)->loadResult();
		if ($personCount > 0) {
			$this->setError(Text::sprintf('COM_JOOMLEAGUE_ERROR_CLUB_HAS_PERSONS', $personCount));

			return false;
		}

		return true;
	}
}
