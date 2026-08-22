<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectContextRepository;

final class ProjectpanelModel extends BaseDatabaseModel
{
	public function getProject(int $projectId): object
	{
		return (new ProjectContextRepository($this->getDatabase()))->get($projectId);
	}

	/** @return array{rules:int,templates:int} */
	public function getOverrideCounts(int $projectId): array
	{
		$db = $this->getDatabase();
		$counts = [];

		foreach (['rules' => '#__joomleague_project_rule_config', 'templates' => '#__joomleague_project_template_config'] as $key => $table) {
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->quoteName($table))
				->where($db->quoteName('project_id') . ' = :projectId')
				->bind(':projectId', $projectId);
			$counts[$key] = (int) $db->setQuery($query)->loadResult();
		}

		return $counts;
	}

	/** @return array{stages:int,entries:int,officials:int,rounds:int,matches:int} */
	public function getAggregateCounts(int $projectId): array
	{
		$db = $this->getDatabase();
		$counts = [];

		foreach ([
			'stages' => '#__joomleague_project_stage',
			'entries' => '#__joomleague_project_entry',
			'officials' => '#__joomleague_project_actor_role',
			'rounds' => '#__joomleague_project_round',
			'matches' => '#__joomleague_project_match',
		] as $key => $table) {
			$boundProjectId = $projectId;
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->quoteName($table))
				->where($db->quoteName('project_id') . ' = :projectId')
				->bind(':projectId', $boundProjectId, ParameterType::INTEGER);
			$counts[$key] = (int) $db->setQuery($query)->loadResult();
		}

		return $counts;
	}
}
