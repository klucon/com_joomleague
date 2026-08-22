<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomleague\Component\Joomleague\Administrator\Service\ComponentTableCatalog;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectAssetRebuilder;
use Joomleague\Component\Joomleague\Administrator\Service\SqlDataExchangeService;

final class DatabasetoolsModel extends BaseDatabaseModel
{
	/** @return list<array{name: string, rows: int}> */
	public function getTables(): array
	{
		$items = [];
		foreach (ComponentTableCatalog::installed($this->getDatabase()) as $table) {
			$query = $this->getDatabase()->getQuery(true)->select('COUNT(*)')->from($this->getDatabase()->quoteName($table));
			$items[] = ['name' => $table, 'rows' => (int) $this->getDatabase()->setQuery($query)->loadResult()];
		}
		return $items;
	}

	/** @param list<string> $tables */
	public function export(array $tables): string
	{
		return $this->service()->export($tables);
	}

	private function service(): SqlDataExchangeService
	{
		return new SqlDataExchangeService($this->getDatabase(), JPATH_ADMINISTRATOR . '/components/com_joomleague');
	}

	/** @return array{orphans_removed:int, projects_linked:int} */
	public function rebuildProjectAssets(): array
	{
		return (new ProjectAssetRebuilder($this->getDatabase()))->rebuild();
	}
}
