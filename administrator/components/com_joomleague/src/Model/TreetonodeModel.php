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
use Joomla\CMS\Date\Date;

final class TreetonodeModel extends EntityAdminModel
{
	protected string $entityName = 'treetonode';
	private AdministratorApplication $application;

	public function setApplication(AdministratorApplication $application): void
	{
		$this->application = $application;
	}

	protected function loadFormData(): object
	{
		$item = $this->getItem();

		if (empty($item->treeto_id)) {
			$item->treeto_id = $this->application->getInput()->getInt('treeto_id') ?: (int) $this->application->getUserState('com_joomleague.treetonodes.treeto_id');
		}

		return $item;
	}

	protected function prepareTable($table): void
	{
		$table->treeto_id = (int) $table->treeto_id;
		$table->node = (int) $table->node;
		$table->row = (int) $table->row;
		$table->bestof = (int) $table->bestof;
		$table->team_id = (int) $table->team_id ?: null;
		$table->title = trim((string) $table->title);
		$table->content = trim((string) $table->content);
		$table->published = (int) $table->published;
		$table->modified = (new Date())->toSql();
		$table->modified_by = (int) $this->getCurrentUser()->id ?: null;
	}
}
