<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomleague\Component\Joomleague\Administrator\Service\SystemDiagnosticsService;

final class DiagnosticsModel extends BaseDatabaseModel
{
	/** @return array<string, mixed> */
	public function getReport(): array
	{
		return (new SystemDiagnosticsService($this->getDatabase()))->inspect();
	}
}
