<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class TemplateConfigBootstrapService
{
	public const DEFAULT_TEMPLATES = [
		[
			'template' => 'projectheading',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_PROJECTHEADING',
			'params' => "layout=default\nshow_title=1\nshow_project_logo=1\nshow_project_name=1",
		],
		[
			'template' => 'ranking',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_RANKING',
			'params' => "layout=default\nshow_title=1\nshow_logo=1\nshow_form=1\nshow_points=1",
		],
		[
			'template' => 'results',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_RESULTS',
			'params' => "layout=default\nshow_title=1\nshow_match_number=1\nshow_venue=1",
		],
		[
			'template' => 'matches',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_MATCHES',
			'params' => "layout=default\nshow_title=1\nshow_date=1\nshow_time=1\nshow_referees=1",
		],
		[
			'template' => 'matchreport',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_MATCHREPORT',
			'params' => "layout=default\nshow_title=1\nshow_lineups=1\nshow_events=1\nshow_statistics=1",
		],
		[
			'template' => 'teaminfo',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_TEAMINFO',
			'params' => "layout=default\nshow_title=1\nshow_logo=1\nshow_club=1\nshow_stadium=1",
		],
		[
			'template' => 'teamplan',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_TEAMPLAN',
			'params' => "layout=default\nshow_title=1\nshow_home_away=1\nshow_results=1",
		],
		[
			'template' => 'teamstats',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_TEAMSTATS',
			'params' => "layout=default\nshow_title=1\nshow_events=1\nshow_statistics=1",
		],
		[
			'template' => 'roster',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_ROSTER',
			'params' => "layout=default\nshow_title=1\nshow_positions=1\nshow_staff=1",
		],
		[
			'template' => 'players',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_PLAYERS',
			'params' => "layout=default\nshow_title=1\nshow_positions=1\nshow_birthdays=1",
		],
		[
			'template' => 'staff',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_STAFF',
			'params' => "layout=default\nshow_title=1\nshow_positions=1",
		],
		[
			'template' => 'playground',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_PLAYGROUND',
			'params' => "layout=default\nshow_title=1\nshow_address=1\nshow_map=0",
		],
		[
			'template' => 'stats',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_STATS',
			'params' => "layout=default\nshow_title=1\nshow_player_stats=1\nshow_team_stats=1",
		],
		[
			'template' => 'matrix',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_MATRIX',
			'params' => "layout=default\nshow_title=1\nshow_results=1",
		],
		[
			'template' => 'nextmatch',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_NEXTMATCH',
			'params' => "layout=default\nshow_title=1\nshow_countdown=0\nshow_venue=1",
		],
		[
			'template' => 'overall',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_OVERALL',
			'params' => "layout=default\nshow_title=1\nshow_ranking=1\nshow_results=1\nshow_nextmatch=1",
		],
	];

	public function __construct(private readonly DatabaseInterface $database)
	{
	}

	public function seedAllProjects(): int
	{
		$query = $this->database->createQuery()
			->select($this->database->quoteName('id'))
			->from($this->database->quoteName('#__joomleague_project'))
			->order($this->database->quoteName('id') . ' ASC');

		$projectIds = array_map('intval', (array) $this->database->setQuery($query)->loadColumn());
		$created = 0;

		foreach ($projectIds as $projectId) {
			$created += $this->seedProject($projectId);
		}

		return $created;
	}

	public function seedProject(int $projectId): int
	{
		if ($projectId <= 0) {
			return 0;
		}

		$existing = $this->getExistingTemplates($projectId);
		$created = 0;

		foreach (self::DEFAULT_TEMPLATES as $definition) {
			$key = $definition['template'] . ':';

			if (isset($existing[$key])) {
				continue;
			}

			$row = (object) [
				'project_id' => $projectId,
				'template' => $definition['template'],
				'func' => '',
				'title' => $definition['title'],
				'params' => $definition['params'],
				'published' => 1,
				'checked_out' => null,
				'checked_out_time' => null,
				'modified' => null,
				'modified_by' => null,
			];

			$this->database->insertObject('#__joomleague_template_config', $row);
			$created++;
		}

		return $created;
	}

	private function getExistingTemplates(int $projectId): array
	{
		$query = $this->database->createQuery()
			->select([
				$this->database->quoteName('template'),
				$this->database->quoteName('func'),
			])
			->from($this->database->quoteName('#__joomleague_template_config'))
			->where($this->database->quoteName('project_id') . ' = :project_id')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		$rows = (array) $this->database->setQuery($query)->loadAssocList();
		$existing = [];

		foreach ($rows as $row) {
			$existing[(string) $row['template'] . ':' . (string) $row['func']] = true;
		}

		return $existing;
	}
}
