<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


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
			'params' => ['layout' => 'default', 'show_title' => true, 'show_project_logo' => true, 'show_project_name' => true],
		],
		[
			'template' => 'ranking',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_RANKING',
			'params' => ['layout' => 'default', 'scope' => 'total', 'show_rank' => true, 'show_played' => true, 'show_won' => true, 'show_drawn' => true, 'show_lost' => true, 'show_goals' => true, 'show_goal_difference' => true, 'show_points' => true],
		],
		[
			'template' => 'results',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_RESULTS',
			'params' => ['layout' => 'default', 'show_date' => true, 'show_round' => true, 'show_score' => true, 'show_venue' => true, 'show_detail_link' => true],
		],
		[
			'template' => 'matches',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_MATCHES',
			'params' => ['layout' => 'default', 'show_title' => true, 'show_date' => true, 'show_time' => true, 'show_referees' => true],
		],
		[
			'template' => 'matchreport',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_MATCHREPORT',
			'params' => ['layout' => 'default', 'show_navigation' => true, 'show_meta' => true, 'show_split_results' => true, 'show_preview' => true, 'show_summary' => true, 'show_referees' => true, 'show_match_referees' => true, 'show_referee_position' => true, 'show_events' => true, 'show_head_to_head' => true],
		],
		[
			'template' => 'teaminfo',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_TEAMINFO',
			'params' => ['layout' => 'default', 'show_title' => true, 'show_logo' => true, 'show_club' => true, 'show_stadium' => true],
		],
		[
			'template' => 'teamplan',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_TEAMPLAN',
			'params' => ['layout' => 'default', 'show_title' => true, 'show_home_away' => true, 'show_results' => true],
		],
		[
			'template' => 'teamstats',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_TEAMSTATS',
			'params' => ['layout' => 'default', 'show_title' => true, 'show_events' => true, 'show_statistics' => true],
		],
		[
			'template' => 'roster',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_ROSTER',
			'params' => ['layout' => 'table', 'show_jersey_number' => true, 'show_country_flag' => true, 'show_position' => true],
		],
		[
			'template' => 'players',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_PLAYERS',
			'params' => ['layout' => 'default', 'show_title' => true, 'show_positions' => true, 'show_birthdays' => true],
		],
		[
			'template' => 'staff',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_STAFF',
			'params' => ['layout' => 'default', 'show_title' => true, 'show_positions' => true],
		],
		[
			'template' => 'playground',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_PLAYGROUND',
			'params' => ['layout' => 'default', 'show_title' => true, 'show_address' => true, 'show_map' => false, 'show_map_embed' => true],
		],
		[
			'template' => 'stats',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_STATS',
			'params' => ['layout' => 'default', 'show_title' => true, 'show_player_stats' => true, 'show_team_stats' => true],
		],
		[
			'template' => 'matrix',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_MATRIX',
			'params' => ['layout' => 'default', 'show_title' => true, 'show_results' => true],
		],
		[
			'template' => 'nextmatch',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_NEXTMATCH',
			'params' => ['layout' => 'default', 'show_title' => true, 'show_countdown' => false, 'show_venue' => true],
		],
		[
			'template' => 'overall',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_OVERALL',
			// Pozn.: overall.xml je legacy sada obecných voleb (ochrana e-mailu, časové pásmo,
			// obrázky projektu/divize atd.), ne přepínače pro sekce žebříček/výsledky/příští
			// zápas – žádná z nich zatím není na frontendu nikde čtená (viz projectheading pro
			// skutečné záhlaví domovské stránky projektu).
			'params' => ['show_project_heading' => true, 'show_project_country' => false, 'show_project_picture' => false, 'show_project_text' => false],
		],
		[
			'template' => 'clubinfo',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_CLUBINFO',
			'params' => ['show_sectionheader' => true, 'show_description' => true, 'show_maps' => true, 'show_map_embed' => true, 'show_teams_of_club' => true, 'show_club_logo' => true, 'show_playgrounds_of_club' => true],
		],
		[
			'template' => 'clubplan',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_CLUBPLAN',
			'params' => ['show_sectionheader' => true, 'show_playground' => true, 'show_ical_link' => true],
		],
		[
			'template' => 'tree',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_TREE',
			'params' => ['show_sectionheader' => true, 'show_teamname_link' => '1', 'name_team_type' => '2'],
		],
		[
			'template' => 'resultsranking',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_RESULTSRANKING',
			'params' => ['show_sectionheader' => true, 'show_results' => true, 'show_ranking' => true],
		],
		[
			'template' => 'clubs',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_CLUBS',
			'params' => ['show_sectionheader' => true, 'show_address' => true, 'show_club_teams' => true, 'show_small_logo' => true, 'show_medium_logo' => false, 'show_big_logo' => false],
		],
		[
			'template' => 'curve',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_CURVE',
			'params' => ['show_sectionheader' => true, 'show_curve' => true, 'show_colorlegend' => true],
		],
		[
			'template' => 'rivals',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_RIVALS',
			'params' => ['show_sectionheader' => true, 'show_team_link' => true, 'show_teamstats_link' => true, 'show_plan_link' => true],
		],
		[
			'template' => 'teams',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_TEAMS',
			'params' => ['show_sectionheader' => true, 'show_team_picture' => true, 'show_club_picture' => true],
		],
		[
			'template' => 'referees',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_REFEREES',
			'params' => ['show_sectionheader' => true, 'link_name' => true],
		],
		[
			'template' => 'eventsranking',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_EVENTSRANKING',
			'params' => ['show_sectionheader' => true, 'max_events' => '20', 'link_to_player' => true, 'link_to_team' => true],
		],
		[
			'template' => 'statsranking',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_STATSRANKING',
			'params' => ['show_sectionheader' => true, 'max_stats' => '20', 'link_to_player' => true, 'link_to_team' => true],
		],
		[
			'template' => 'projects',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_PROJECTS',
			'params' => ['show_sectionheader' => true],
		],
		[
			'template' => 'prediction',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_PREDICTION',
			'params' => ['show_sectionheader' => true],
		],
		[
			'template' => 'raceresults',
			'title' => 'COM_JOOMLEAGUE_TEMPLATE_RACERESULTS',
			'params' => ['show_sectionheader' => true],
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

	public function seedGlobalDefaults(): int
	{
		$existing = $this->getExistingTemplates(null);
		$created = 0;

		foreach (self::DEFAULT_TEMPLATES as $definition) {
			$key = $definition['template'] . ':';

			if (isset($existing[$key])) {
				continue;
			}

			$row = (object) [
				'project_id' => null,
				'template' => $definition['template'],
				'func' => '',
				'title' => $definition['title'],
				'params' => json_encode($definition['params'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
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

	public function seedProject(int $projectId): int
	{
		if ($projectId <= 0) {
			return 0;
		}

		$existing = $this->getExistingTemplates($projectId);
		$globalParams = $this->getGlobalParams();
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
				// Nový projekt dědí aktuální centrální (globální) nastavení; teprve pokud
				// pro danou šablonu žádné globální nastavení neexistuje, použije se
				// vestavěný PHP default jako záchranná síť.
				'params' => json_encode($globalParams[$definition['template']] ?? $definition['params'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
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

	/**
	 * @return  array<string, array<string, mixed>>
	 */
	private function getGlobalParams(): array
	{
		$query = $this->database->createQuery()
			->select([$this->database->quoteName('template'), $this->database->quoteName('params')])
			->from($this->database->quoteName('#__joomleague_template_config'))
			->where($this->database->quoteName('project_id') . ' IS NULL')
			->where($this->database->quoteName('published') . ' = 1');

		$rows = (array) $this->database->setQuery($query)->loadAssocList();
		$params = [];

		foreach ($rows as $row) {
			$decoded = json_validate((string) $row['params']) ? json_decode((string) $row['params'], true) : null;

			if (is_array($decoded)) {
				$params[(string) $row['template']] = $decoded;
			}
		}

		return $params;
	}

	private function getExistingTemplates(?int $projectId): array
	{
		$query = $this->database->createQuery()
			->select([
				$this->database->quoteName('template'),
				$this->database->quoteName('func'),
			])
			->from($this->database->quoteName('#__joomleague_template_config'));

		if ($projectId === null) {
			$query->where($this->database->quoteName('project_id') . ' IS NULL');
		} else {
			$query->where($this->database->quoteName('project_id') . ' = :project_id')->bind(':project_id', $projectId, ParameterType::INTEGER);
		}

		$rows = (array) $this->database->setQuery($query)->loadAssocList();
		$existing = [];

		foreach ($rows as $row) {
			$existing[(string) $row['template'] . ':' . (string) $row['func']] = true;
		}

		return $existing;
	}
}
