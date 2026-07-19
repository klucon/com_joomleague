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

final class PlaygroundModel extends AdminModel
{
	public function getTable($name = 'Playground', $prefix = 'Administrator', $options = []): Table
	{
		return parent::getTable($name, $prefix, $options);
	}

	public function getForm($data = [], $loadData = true): Form|false
	{
		return $this->loadForm('com_joomleague.playground', 'playground', ['control' => 'jform', 'load_data' => $loadData]);
	}

	protected function loadFormData(): object
	{
		return $this->getItem();
	}

	protected function prepareTable($table): void
	{
		foreach (['name', 'short_name', 'address', 'zipcode', 'city', 'website', 'picture'] as $field) {
			$table->$field = trim((string) $table->$field);
		}

		$table->country = trim((string) $table->country) ?: null;
		$table->latitude = trim((string) $table->latitude) !== '' ? (float) $table->latitude : null;
		$table->longitude = trim((string) $table->longitude) !== '' ? (float) $table->longitude : null;
			$table->club_id = (int) $table->club_id ?: null;
			$table->max_visitors = (int) $table->max_visitors ?: null;
			$table->info = trim((string) $table->info);
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
			['#__joomleague_project_team', 'standard_playground', 'COM_JOOMLEAGUE_PLAYGROUND_ERROR_PROJECT_TEAM_EXISTS'],
			['#__joomleague_match', 'playground_id', 'COM_JOOMLEAGUE_PLAYGROUND_ERROR_MATCH_EXISTS'],
		] as [$table, $column, $message]) {
			$query = $db->createQuery()->select('COUNT(*)')->from($db->quoteName($table))
				->where($db->quoteName($column) . ' = :playgroundId')->bind(':playgroundId', $id, ParameterType::INTEGER);
			$db->setQuery($query);

			if ((int) $db->loadResult() > 0) {
				$this->setError(Text::_($message));

				return false;
			}
		}

		return parent::canDelete($record);
	}
}
