<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Administrator\Service\ClubProvisioningService;
use RuntimeException;
use Throwable;

final class ClubModel extends AdminModel
{
	private ?ClubProvisioningService $provisioningService = null;

	public function setProvisioningService(ClubProvisioningService $provisioningService): void
	{
		$this->provisioningService = $provisioningService;
	}

	public function getTable($name = 'Club', $prefix = 'Administrator', $options = []): Table
	{
		return parent::getTable($name, $prefix, $options);
	}

	public function getForm($data = [], $loadData = true): Form|false
	{
		return $this->loadForm('com_joomleague.club', 'club', ['control' => 'jform', 'load_data' => $loadData]);
	}

	public function save($data): bool
	{
		$isNew = empty($data['id']);
		$createTeam = $isNew ? ((string) ($data['create_team'] ?? '1') !== '0') : !empty($data['create_team']);
		$createStadium = $isNew ? ((string) ($data['create_stadium'] ?? '1') !== '0') : !empty($data['create_stadium']);
		unset($data['create_team'], $data['create_stadium']);

		$db = $this->getDatabase();
		$db->transactionStart();

		try {
			if (!parent::save($data)) {
				$db->transactionRollback();

				return false;
			}

			if ($createTeam || $createStadium) {
				if ($this->provisioningService === null) {
					throw new RuntimeException(Text::_('COM_JOOMLEAGUE_CLUB_ERROR_PROVISIONING_SERVICE'));
				}

				$clubId = (int) $this->getState($this->getName() . '.id');

				if ($clubId < 1 && !empty($data['id'])) {
					$clubId = (int) $data['id'];
				}

				if ($clubId < 1) {
					throw new RuntimeException(Text::_('COM_JOOMLEAGUE_CLUB_ERROR_SAVED_ID_MISSING'));
				}
				$modified = (new Date())->toSql();
				$modifiedBy = (int) $this->getCurrentUser()->id ?: null;

				if ($createTeam && !$this->clubHasRows('#__joomleague_team', 'club_id', $clubId)) {
					$this->provisioningService->createTeam($clubId, trim((string) $data['name']), $modified, $modifiedBy);
				}

				if ($createStadium && !$this->clubHasRows('#__joomleague_playground', 'club_id', $clubId)) {
					$this->provisioningService->createStadium(
						$clubId,
						Text::sprintf('COM_JOOMLEAGUE_CLUB_DEFAULT_STADIUM_NAME', trim((string) $data['name'])),
						$data,
						$modified,
						$modifiedBy
					);
				} elseif ($createStadium) {
					$this->provisioningService->assignFirstClubStadiumAsDefault($clubId);
				}
			}

			$db->transactionCommit();

			return true;
		} catch (Throwable $error) {
			$db->transactionRollback();
			$this->setError($error->getMessage());

			return false;
		}
	}

	private function clubHasRows(string $table, string $column, int $clubId): bool
	{
		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select('COUNT(*)')
			->from($db->quoteName($table))
			->where($db->quoteName($column) . ' = :club_id')
			->bind(':club_id', $clubId, ParameterType::INTEGER);

		return (int) $db->setQuery($query)->loadResult() > 0;
	}

	protected function loadFormData(): object
	{
		$item = $this->getItem();

		if ((int) $item->id === 0) {
			$item->create_team = 1;
			$item->create_stadium = 1;
		}

		// Naplní do formuláře hodnoty Joomla Custom Fields (com_fields) pro kontext klubu.
		$this->preprocessData('com_joomleague.club', $item);

		return $item;
	}

	protected function prepareTable($table): void
	{
		foreach (['name', 'address', 'zipcode', 'location', 'state', 'phone', 'fax', 'email', 'website', 'president', 'manager', 'logo_big', 'logo_middle', 'logo_small'] as $field) {
			$table->$field = trim((string) $table->$field);
		}

		$table->country = trim((string) $table->country) ?: null;
		$table->founded = trim((string) $table->founded) ?: null;
		$table->dissolved = trim((string) $table->dissolved) ?: null;
		$table->latitude = trim((string) $table->latitude) !== '' ? (float) $table->latitude : null;
		$table->longitude = trim((string) $table->longitude) !== '' ? (float) $table->longitude : null;
		$table->standard_playground = (int) $table->standard_playground ?: null;
		$table->notes = trim((string) $table->notes);
		$table->extended = trim((string) $table->extended) ?: null;
		$table->modified = (new Date())->toSql();
		$table->modified_by = (int) $this->getCurrentUser()->id ?: null;

		if ((int) $table->id === 0) {
			$table->ordering = $table->getNextOrder();
		}
	}

	protected function canDelete($record): bool
	{
		$db = $this->getDatabase();
		$id = (int) $record->id;

		foreach ([
			['#__joomleague_team', 'club_id', 'COM_JOOMLEAGUE_CLUB_ERROR_TEAM_EXISTS'],
			['#__joomleague_playground', 'club_id', 'COM_JOOMLEAGUE_CLUB_ERROR_STADIUM_EXISTS'],
		] as [$table, $column, $message]) {
			$query = $db->createQuery()->select('COUNT(*)')->from($db->quoteName($table))
				->where($db->quoteName($column) . ' = :clubId')->bind(':clubId', $id, ParameterType::INTEGER);
			$db->setQuery($query);

			if ((int) $db->loadResult() > 0) {
				$this->setError(Text::_($message));

				return false;
			}
		}

		return parent::canDelete($record);
	}
}
