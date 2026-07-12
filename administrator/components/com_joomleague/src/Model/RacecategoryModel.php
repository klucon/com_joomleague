<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

final class RacecategoryModel extends EntityAdminModel
{
	protected string $entityName = 'racecategory';

	protected function prepareTable($table): void
	{
		$table->project_id = (int) $table->project_id;
		$table->name = trim((string) $table->name);
		$table->alias = trim((string) $table->alias);
		$table->sex = strtoupper(trim((string) $table->sex)) ?: 'ANY';
		$table->age_min = $table->age_min === '' ? null : (int) $table->age_min;
		$table->age_max = $table->age_max === '' ? null : (int) $table->age_max;
		parent::prepareTable($table);
	}
}
