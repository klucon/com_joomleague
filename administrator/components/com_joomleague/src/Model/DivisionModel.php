<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\AdministratorApplication;

final class DivisionModel extends EntityAdminModel
{
	protected string $entityName = 'division';
	private AdministratorApplication $application;

	public function setApplication(AdministratorApplication $application): void
	{
		$this->application = $application;
	}

	protected function loadFormData(): object
	{
		$item = $this->getItem();

		if (!$item->project_id) {
			$item->project_id = $this->application->getInput()->getInt('project_id') ?: $this->application->getUserState('com_joomleague.project_context.project_id');
		}

		return $item;
	}

	protected function prepareTable($table): void
	{
		$table->project_id = (int) $table->project_id ?: null;
		$table->parent_id = (int) $table->parent_id ?: null;
		$table->published = (int) $table->published;
		$table->ordering = (int) $table->ordering;

		foreach (['name', 'alias', 'shortname', 'notes', 'picture'] as $field) {
			$table->$field = trim((string) $table->$field);
		}

		parent::prepareTable($table);
	}
}
