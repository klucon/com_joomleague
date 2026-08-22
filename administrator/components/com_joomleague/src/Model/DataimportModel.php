<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomleague\Component\Joomleague\Administrator\Service\SqlDataExchangeService;

final class DataimportModel extends BaseDatabaseModel
{
	/** @return array{executed: int, skipped: int} */
	public function import(string $sql): array
	{
		return (new SqlDataExchangeService($this->getDatabase(), JPATH_ADMINISTRATOR . '/components/com_joomleague'))->import($sql);
	}
}
