<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

final class JlxmlexportsModel extends BaseDatabaseModel
{
	public function getProjects(): array
	{
		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select('p.id, p.name, l.name AS league, s.name AS season')
			->from('#__joomleague_project p')
			->join('LEFT', '#__joomleague_league l ON l.id = p.league_id')
			->join('LEFT', '#__joomleague_season s ON s.id = p.season_id')
			->order('p.id DESC');

		return $db->setQuery($query)->loadObjectList() ?: [];
	}

	public function buildProjectExport(int $projectId): array
	{
		$db = $this->getDatabase();
		$data = [
			'format' => 'joomleague-v6-json',
			'version' => 1,
			'project_id' => $projectId,
			'exported_at' => gmdate('c'),
			'tables' => [],
		];

		$tables = [
			'project' => ['table' => '#__joomleague_project', 'where' => 'id = ' . $projectId],
			'project_team' => ['table' => '#__joomleague_project_team', 'where' => 'project_id = ' . $projectId],
			'project_referee' => ['table' => '#__joomleague_project_referee', 'where' => 'project_id = ' . $projectId],
			'project_position' => ['table' => '#__joomleague_project_position', 'where' => 'project_id = ' . $projectId],
			'division' => ['table' => '#__joomleague_division', 'where' => 'project_id = ' . $projectId],
			'round' => ['table' => '#__joomleague_round', 'where' => 'project_id = ' . $projectId],
			'match' => ['table' => '#__joomleague_match', 'where' => 'round_id IN (SELECT id FROM #__joomleague_round WHERE project_id = ' . $projectId . ')'],
			'template_config' => ['table' => '#__joomleague_template_config', 'where' => 'project_id = ' . $projectId],
		];

		foreach ($tables as $key => $definition) {
			$query = $db->createQuery()
				->select('*')
				->from($definition['table'])
				->where($definition['where']);
			$data['tables'][$key] = $db->setQuery($query)->loadAssocList() ?: [];
		}

		return $data;
	}
}
