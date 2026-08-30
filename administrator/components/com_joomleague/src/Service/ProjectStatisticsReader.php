<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Read-only project-wide statistics overview composed from canonical ranking readers. */
final class ProjectStatisticsReader
{
	public function __construct(private readonly DatabaseInterface $database) {}

	/** @param list<int> $viewLevels @return array<string,mixed> */
	public function forProject(int $projectId, array $viewLevels): array
	{
		$statistics=(new StatisticRankingReader($this->database))->forProject($projectId,null,1,$viewLevels);
		$events=(new EventRankingReader($this->database))->forProject($projectId,null,1,$viewLevels);
		$db=$this->database;
		$entries=(int)$db->setQuery($db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_project_entry'))->where('project_id=:project')->where('published=1')->bind(':project',$projectId,ParameterType::INTEGER))->loadResult();
		$programme=(int)$db->setQuery($db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_project_match'))->where('project_id=:project')->where('published=1')->bind(':project',$projectId,ParameterType::INTEGER))->loadResult();
		$completed=(int)$db->setQuery($db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_match_result','result'))->innerJoin($db->quoteName('#__joomleague_project_match','item').' ON item.id=result.match_id AND item.published=1')->where('item.project_id=:project')->where("result.status_code='final'")->bind(':project',$projectId,ParameterType::INTEGER))->loadResult();
		$attendance=$db->setQuery($db->getQuery(true)->select(['COUNT(attendance) AS recorded_count','COALESCE(SUM(attendance),0) AS total_value','COALESCE(AVG(attendance),0) AS average_value'])->from($db->quoteName('#__joomleague_project_match'))->where('project_id=:project')->where('published=1')->where('attendance IS NOT NULL')->bind(':project',$projectId,ParameterType::INTEGER))->loadObject();
		return['project'=>$statistics['project'],'summary'=>compact('entries','programme','completed')+['event_types'=>count($events['definitions']),'statistic_types'=>count($statistics['definitions'])],'attendance'=>$attendance,'event_definitions'=>$events['definitions'],'statistic_definitions'=>$statistics['definitions']];
	}
}
