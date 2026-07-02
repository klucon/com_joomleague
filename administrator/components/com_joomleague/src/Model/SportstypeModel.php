<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

final class SportstypeModel extends AdminModel
{
	public function getTable($name = 'Sportstype', $prefix = 'Administrator', $options = []): Table
	{
		return parent::getTable($name, $prefix, $options);
	}

	public function getForm($data = [], $loadData = true): Form|false
	{
		return $this->loadForm(
			'com_joomleague.sportstype',
			'sportstype',
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
		$table->periods = max(1, (int) $table->periods);
		$table->icon = trim((string) $table->icon);
		$table->modified = (new Date())->toSql();
		$table->modified_by = (int) $this->getCurrentUser()->id ?: null;

		if ((int) $table->id === 0) {
			$table->ordering = $table->getNextOrder();
		}
	}
}
