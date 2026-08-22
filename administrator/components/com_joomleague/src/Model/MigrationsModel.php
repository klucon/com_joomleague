<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomleague\Component\Joomleague\Administrator\Service\SourceSchemaInventory;
use Joomleague\Component\Joomleague\Administrator\Service\SourceVersionInspector;

final class MigrationsModel extends BaseDatabaseModel
{
	/** @return list<object> */
	public function getItems(): array
	{
		$query = $this->getDatabase()->getQuery(true)
			->select('*')
			->from($this->getDatabase()->quoteName('#__joomleague_migration_batch'))
			->order('created DESC');

		return $this->getDatabase()->setQuery($query)->loadObjectList();
	}

	/** @return array<string, mixed> */
	public function getSourceInventory(): array
	{
		$inventory = (new SourceSchemaInventory($this->getDatabase()))->inspectCurrentDatabase();
		$inventory['source_version'] = (new SourceVersionInspector($this->getDatabase()))
			->inspect($inventory['classification']);

		return $inventory;
	}
}
