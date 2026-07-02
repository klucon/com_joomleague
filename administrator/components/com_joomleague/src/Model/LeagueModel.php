<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;

final class LeagueModel extends AdminModel
{
	public function getTable($name = 'League', $prefix = 'Administrator', $options = []): Table
	{
		return parent::getTable($name, $prefix, $options);
	}

	public function getForm($data = [], $loadData = true): Form|false
	{
		return $this->loadForm(
			'com_joomleague.league',
			'league',
			['control' => 'jform', 'load_data' => $loadData]
		);
	}

	protected function loadFormData(): object
	{
		return $this->getItem();
	}

	protected function prepareTable($table): void
	{
		$table->name = trim((string) $table->name);
		$table->middle_name = trim((string) $table->middle_name);
		$table->short_name = trim((string) $table->short_name);
		$table->country = trim((string) $table->country) ?: null;
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
		$query = $db->createQuery()
			->select('COUNT(*)')
			->from($db->quoteName('#__joomleague_project'))
			->where($db->quoteName('league_id') . ' = :leagueId')
			->bind(':leagueId', $id, ParameterType::INTEGER);
		$db->setQuery($query);

		if ((int) $db->loadResult() > 0) {
			$this->setError(Text::_('COM_JOOMLEAGUE_LEAGUE_ERROR_PROJECT_EXISTS'));

			return false;
		}

		return parent::canDelete($record);
	}
}
