<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

final class DatabasetoolsModel extends BaseDatabaseModel
{
	public function getTables(): array
	{
		$db = $this->getDatabase();
		$prefix = $db->getPrefix() . 'joomleague_%';
		$query = 'SHOW TABLE STATUS LIKE ' . $db->quote($prefix);
		$rows = $db->setQuery($query)->loadObjectList() ?: [];

		return array_map(static fn(object $row): object => (object) [
			'name' => (string) $row->Name,
			'engine' => (string) $row->Engine,
			'rows' => (int) $row->Rows,
			'data_length' => (int) $row->Data_length,
			'index_length' => (int) $row->Index_length,
			'collation' => (string) $row->Collation,
		], $rows);
	}

	public function optimize(): int
	{
		return $this->runMaintenance('OPTIMIZE TABLE');
	}

	public function repair(): int
	{
		return $this->runMaintenance('REPAIR TABLE');
	}

	private function runMaintenance(string $operation): int
	{
		$db = $this->getDatabase();
		$count = 0;

		foreach ($this->getTables() as $table) {
			$db->setQuery($operation . ' ' . $db->quoteName($table->name))->execute();
			$count++;
		}

		return $count;
	}
}
