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
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Administrator\Service\ScheduleTemplateService;

final class ScheduleModel extends BaseDatabaseModel
{
	private ScheduleTemplateService $templateService;

	public function setScheduleTemplateService(ScheduleTemplateService $templateService): void
	{
		$this->templateService = $templateService;
	}

	public function getProject(int $id): ?object
	{
		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select('*')
			->from($db->quoteName('#__joomleague_project'))
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		return $db->setQuery($query)->loadObject();
	}

	public function getTemplates(): array
	{
		return $this->templateService->getTemplates();
	}
}
