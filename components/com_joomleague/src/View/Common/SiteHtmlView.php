<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\View\Common;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomleague\Component\Joomleague\Site\Service\IcalFeedHelper;
use Joomleague\Component\Joomleague\Site\Service\PlayerStatsHelper;

class SiteHtmlView extends BaseHtmlView
{
	public object|null $project = null;
	public array $items = [];
	public array $matches = [];
	public array $matrix = [];
	public array $rounds = [];
	public array $teams = [];
	public array $raceCategories = [];
	public array $raceResults = [];
	public array $standings = [];
	public array $eventTypes = [];
	public array $statistics = [];
	public array $teamSeasons = [];
	public array $teamStats = [];
	public array $teamPlayerStats = [];
	public array $rivals = [];
	public array $treeNodes = [];
	public array $treeRounds = [];
	public array $curve = [];
	public array $divisions = [];
	public array $clubPlaygrounds = [];
	public array $matchSummary = [];
	public array $headToHeadMatches = [];
	public array $matchReferees = [];
	public array $matchRoster = [];
	public array $matchStaffList = [];
	public array $matchPlayerStatistics = [];
	public array $matchTeamComparison = [];
	public array $homeForm = [];
	public array $awayForm = [];
	public array $predictionMatches = [];
	public array $predictionTips = [];
	public array $predictionRanking = [];
	public array $templateParams = [];
	public string $rankingScope = 'total';
	public int $rankingDivisionId = 0;
	public int $rankingRoundId = 0;
	public array $standingsRounds = [];
	public array $matchesReferees = [];
	public array $matchesEvents = [];
	public int $resultsRoundId = 0;
	public object|null $item = null;
	public object|null $tree = null;
	public object|null $predictionGame = null;
	public object|null $scheduleTeam = null;
	public object|null $scheduleClub = null;

	public function display($tpl = null): void
	{
		$wa = $this->getDocument()->getWebAssetManager();

		if (!$wa->assetExists('style', 'com_joomleague.site')) {
			$wa->registerStyle('com_joomleague.site', 'com_joomleague/css/site.css', [], ['version' => 'auto']);
		}

		$wa->useStyle('com_joomleague.site');
		$this->getDocument()->addStyleSheet(Uri::root(true) . '/media/com_joomleague/css/site.css');

		$model = $this->getModel();
		$input = Factory::getApplication()->getInput();
		$classParts = explode('\\', static::class);
		$view = strtolower($classParts[count($classParts) - 2] ?? 'projects');
		$projectId = (int) (($input->getInt('project_id', 0) ?: $input->getInt('pid', 0)) ?? 0);
		$id = $input->getInt('id', 0);

		$this->project = $projectId > 0 && method_exists($model, 'getProject') ? $model->getProject($projectId) : null;
		$projectId = $this->project ? (int) $this->project->id : $projectId;
		// I bez project_id (views bez kontextu jednoho projektu, např. 'clubs') se má použít
		// aspoň centrální (globální) nastavení – getTemplateParameters(0, ...) na to sama
		// padá zpět, proto se nesmí volání úplně přeskočit jen kvůli chybějícímu project_id.
		$this->templateParams = method_exists($model, 'getTemplateParameters')
			? $model->getTemplateParameters($projectId, $view)
			: [];

		if ($view === 'projects') {
			$this->items = $model->getProjects();
		} elseif ($view === 'project') {
			$isRunningRaceProject = $this->project && ($this->project->project_type ?? '') === 'RUNNING_RACE';
			$this->teams = $projectId > 0 ? $model->getProjectTeams($projectId) : [];
			$this->rounds = $projectId > 0 ? $model->getRounds($projectId) : [];
			$this->matches = $projectId > 0 ? $model->getMatches($projectId, 0, 0, 8, true) : [];
			if ($isRunningRaceProject) {
				$this->raceCategories = $model->getRaceCategories($projectId);
				$this->raceResults = $model->getRaceResults($projectId);
			}

			// Záhlaví domovské stránky projektu používá konfiguraci 'projectheading'.
			$this->templateParams = $projectId > 0 ? $model->getTemplateParameters($projectId, 'projectheading') : [];
		} elseif ($view === 'ranking') {
			$scope = (string) $input->getCmd('scope', (string) ($this->templateParams['scope'] ?? 'total'));
			$this->rankingScope = in_array($scope, ['total', 'home', 'away'], true) ? $scope : 'total';
			$rankingOrder = (string) ($this->templateParams['ranking_order'] ?? '');
			$this->divisions = $projectId > 0 ? $model->getProjectDivisions($projectId) : [];

			if (count($this->divisions) > 1) {
				$requestedDivisionId = (int) ($input->getInt('division_id', 0) ?: $input->getInt('division', 0));
				$validDivisionIds = array_map(static fn (object $d): int => (int) $d->id, $this->divisions);
				$this->rankingDivisionId = in_array($requestedDivisionId, $validDivisionIds, true)
					? $requestedDivisionId
					: (int) $this->divisions[0]->id;
			}

			$this->standingsRounds = $projectId > 0 ? $model->getStandingsRounds($projectId, $this->rankingDivisionId) : [];
			$requestedRoundId = (int) $input->getInt('round_id', 0);
			$validRoundIds = array_map(static fn (array $r): int => $r['id'], $this->standingsRounds);
			$this->rankingRoundId = in_array($requestedRoundId, $validRoundIds, true) ? $requestedRoundId : 0;
			$this->standings = $projectId > 0 ? $model->getStandings($projectId, $this->rankingScope, $rankingOrder, $this->rankingDivisionId, $this->rankingRoundId) : [];
		} elseif ($view === 'raceresults') {
			$this->rounds = $projectId > 0 ? $model->getRounds($projectId) : [];
			$this->raceCategories = $projectId > 0 ? $model->getRaceCategories($projectId) : [];
			$this->raceResults = $projectId > 0 ? $model->getRaceResults(
				$projectId,
				(int) $input->getInt('round_id', 0),
				(int) $input->getInt('category_id', 0),
				(string) $input->getCmd('sex'),
				(string) $input->getCmd('status')
			) : [];
		} elseif ($view === 'results') {
			$this->rounds = $projectId > 0 ? $model->getRounds($projectId) : [];
			$validRoundIds = array_map(static fn (object $r): int => (int) $r->id, $this->rounds);
			$requestedRoundId = (int) $input->getInt('round_id', 0);
			$this->resultsRoundId = in_array($requestedRoundId, $validRoundIds, true) ? $requestedRoundId : 0;
			$this->matches = $projectId > 0 ? $model->getMatches($projectId, $this->resultsRoundId) : [];
			$matchIds = array_map(static fn (object $m): int => (int) $m->id, $this->matches);

			if ($matchIds !== [] && (bool) ($this->templateParams['show_referee'] ?? false)) {
				$this->matchesReferees = $model->getMatchesReferees($matchIds);
			}

			if ($matchIds !== [] && (bool) ($this->templateParams['show_events'] ?? false)) {
				$this->matchesEvents = $model->getMatchesEvents($matchIds);
			}

			if ($this->resultsRoundId > 0 && (bool) ($this->templateParams['show_dnp_teams'] ?? true)) {
				$this->teams = $projectId > 0 ? $model->getProjectTeams($projectId) : [];
			}
		} elseif ($view === 'resultsranking') {
			$this->rounds = $projectId > 0 ? $model->getRounds($projectId) : [];
			$this->matches = $projectId > 0 ? $model->getMatches($projectId, (int) ($input->getInt('round_id', 0) ?? 0)) : [];
			$this->standings = $projectId > 0 ? $model->getStandings($projectId) : [];
		} elseif ($view === 'resultsmatrix') {
			$this->teams = $projectId > 0 ? $model->getProjectTeams($projectId) : [];
			$this->matrix = $projectId > 0 ? $model->getResultMatrix($projectId) : [];
			$this->divisions = $projectId > 0 ? $model->getProjectDivisions($projectId) : [];

			// Křížová tabulka používá konfiguraci 'matrix', ne doslovný název view.
			$this->templateParams = $projectId > 0 ? $model->getTemplateParameters($projectId, 'matrix') : [];
		} elseif ($view === 'schedule') {
			$scheduleTeamId = (int) ($input->getInt('projectteam_id', 0) ?? 0);
			$scheduleClubId = (int) ($input->getInt('club_id', 0) ?? 0);

			if ($scheduleClubId > 0) {
				$this->scheduleClub = $model->getClub($scheduleClubId);
				$this->matches = $this->scheduleClub ? $model->getClubMatches((int) $this->scheduleClub->id, (int) ($input->getInt('project_id', 0) ?? 0)) : [];
			} elseif ($scheduleTeamId > 0) {
				$this->scheduleTeam = $model->getTeam($scheduleTeamId);
				$this->matches = $this->scheduleTeam ? $model->getMatches((int) $this->scheduleTeam->project_id, 0, (int) $this->scheduleTeam->id) : [];
			} else {
				$this->matches = $projectId > 0 ? $model->getMatches($projectId) : [];
			}

			$this->matchSummary = $model->summarizeMatches($this->matches, $scheduleTeamId);

			// Rozpis týmu používá konfiguraci 'teamplan', rozpis klubu 'clubplan' —
			// doslovný klíč view 'schedule' žádnou konfiguraci nemá.
			if ($this->scheduleTeam) {
				$this->templateParams = $model->getTemplateParameters((int) $this->scheduleTeam->project_id, 'teamplan');
			} elseif ($this->scheduleClub) {
				$this->templateParams = $model->getTemplateParameters(0, 'clubplan');
			}
		} elseif ($view === 'ical') {
			$scheduleTeamId = (int) ($input->getInt('projectteam_id', 0) ?? 0);
			$scheduleClubId = (int) ($input->getInt('club_id', 0) ?? 0);

			if ($scheduleClubId > 0) {
				$this->scheduleClub = $model->getClub($scheduleClubId);
				$this->matches = $this->scheduleClub ? $model->getClubMatches((int) $this->scheduleClub->id, (int) ($input->getInt('project_id', 0) ?? 0)) : [];
			} elseif ($scheduleTeamId > 0) {
				$this->scheduleTeam = $model->getTeam($scheduleTeamId);
				$this->matches = $this->scheduleTeam ? $model->getMatches((int) $this->scheduleTeam->project_id, 0, (int) $this->scheduleTeam->id) : [];
			} else {
				$this->matches = $projectId > 0 ? $model->getMatches($projectId) : [];
			}
		} elseif ($view === 'prediction') {
			$gameId = (int) ($input->getInt('game_id', 0) ?: $id);
			$roundId = (int) $input->getInt('round_id', 0);
			$userId = (int) Factory::getApplication()->getIdentity()->id;
			$this->predictionGame = $model->getPredictionGame($projectId, $gameId);
			$this->project = $this->predictionGame ? $model->getProject((int) $this->predictionGame->project_id) : $this->project;

			// project_id nemusí být v URL (jen game_id) – jakmile známe reálný projekt hry,
			// templateParams se musí znovu načíst, ať se použije správné projektové nastavení.
			if ($this->predictionGame) {
				$this->templateParams = $model->getTemplateParameters((int) $this->predictionGame->project_id, 'prediction');
			}

			$this->rounds = $this->predictionGame ? $model->getRounds((int) $this->predictionGame->project_id) : [];
			$this->predictionMatches = $this->predictionGame ? $model->getPredictionMatches((int) $this->predictionGame->id, $roundId) : [];
			$this->predictionTips = $this->predictionGame && $userId > 0 ? $model->getPredictionTips((int) $this->predictionGame->id, $userId) : [];
			$this->predictionRanking = $this->predictionGame ? $model->getPredictionRanking((int) $this->predictionGame->id, $roundId) : [];
		} elseif ($view === 'teams') {
			$this->teams = $projectId > 0 ? $model->getProjectTeams($projectId) : [];
		} elseif ($view === 'team' || $view === 'roster') {
			$this->item = $model->getTeam($id ?: $input->getInt('projectteam_id', 0));
			$this->items = $this->item ? $model->getRoster((int) $this->item->id) : [];
			$this->matches = $this->item ? $model->getMatches((int) $this->item->project_id, 0, (int) $this->item->id) : [];
			$this->teamSeasons = $this->item ? $model->getTeamSeasons((int) $this->item->team_id, (int) $this->item->id) : [];

			// Konfigurace týmové stránky žije pod klíčem 'teaminfo', ne pod doslovným
			// názvem view 'team' (a project_id se u tohoto view zjistí až podle týmu).
			if ($view === 'team' && $this->item) {
				$this->templateParams = $model->getTemplateParameters((int) $this->item->project_id, 'teaminfo');
			}

			if ($view === 'roster' && $this->item) {
				$this->rosterStaff = (bool) ($this->templateParams['show_staff'] ?? true)
					? $model->getRosterStaff((int) $this->item->id)
					: [];

				if ((bool) ($this->templateParams['show_stats'] ?? true)) {
					$rosterStatsData = $model->getRosterPlayerStats((int) $this->item->id);
					$this->rosterPlayerStats = PlayerStatsHelper::aggregate(
						$rosterStatsData['appearances'],
						$rosterStatsData['subOut'],
						$rosterStatsData['events'],
						static fn (object $row) => (int) $row->teamplayer_id
					);
				} else {
					$this->rosterPlayerStats = [];
				}
			} else {
				$this->rosterStaff = [];
				$this->rosterPlayerStats = [];
			}
		} elseif ($view === 'teamstats') {
			$this->item = $model->getTeam($id ?: $input->getInt('projectteam_id', 0) ?: $input->getInt('tid', 0));
			$this->matches = $this->item ? $model->getMatches((int) $this->item->project_id, 0, (int) $this->item->id) : [];
			$this->teamStats = $this->item ? $model->getTeamStatsSummary((int) $this->item->id) : [];
			$this->teamPlayerStats = $this->item ? $model->getTeamPlayerStats((int) $this->item->project_id, (int) $this->item->id) : [];
		} elseif ($view === 'rivals') {
			$this->item = $model->getTeam($id ?: $input->getInt('projectteam_id', 0) ?: $input->getInt('tid', 0));
			$this->rivals = $this->item ? $model->getTeamRivals((int) $this->item->project_id, (int) $this->item->id) : [];
		} elseif ($view === 'treetonode') {
			$treeId = (int) ($input->getInt('treeto_id', 0) ?: $input->getInt('tnid', 0) ?: $id);
			$this->tree = $model->getTree($treeId, $projectId);
			$this->project = $this->tree ? $model->getProject((int) $this->tree->project_id) : $this->project;
			$this->treeNodes = $this->tree ? $model->getTreeNodes((int) $this->tree->id) : [];
			$this->treeRounds = $this->tree ? $model->getTreeRounds((int) $this->tree->id) : [];

			// Pavouk používá konfiguraci 'tree', ne doslovný název view 'treetonode'.
			if ($this->tree) {
				$this->templateParams = $model->getTemplateParameters((int) $this->tree->project_id, 'tree');
			}
		} elseif ($view === 'curve') {
			$divisionId = (int) ($input->getInt('division_id', 0) ?: $input->getInt('division', 0));
			$this->divisions = $projectId > 0 ? $model->getProjectDivisions($projectId) : [];
			$this->teams = $projectId > 0 ? $model->getProjectTeams($projectId, $divisionId) : [];
			$this->rounds = $projectId > 0 ? $model->getRounds($projectId) : [];
			$this->curve = $projectId > 0 ? $model->getRankingCurve(
				$projectId,
				$divisionId,
				(int) ($input->getInt('projectteam1_id', 0) ?: $input->getInt('tid1', 0)),
				(int) ($input->getInt('projectteam2_id', 0) ?: $input->getInt('tid2', 0))
			) : [];
		} elseif ($view === 'matchreport') {
			$this->item = $model->getMatch($id ?: $input->getInt('match_id', 0));
			$this->items = $this->item ? $model->getMatchEvents((int) $this->item->id) : [];
			$this->headToHeadMatches = $this->item ? $model->getHeadToHeadMatches((int) $this->item->projectteam1_id, (int) $this->item->projectteam2_id, (int) $this->item->id) : [];
			$this->matchReferees = $this->item ? $model->getMatchReferees((int) $this->item->id) : [];
			$this->matchTeamComparison = $this->item ? $model->getMatchTeamComparison($this->item) : [];

			if ($this->item && (bool) ($this->templateParams['show_roster'] ?? true)) {
				$this->matchRoster = $model->getMatchRoster((int) $this->item->id);
				$this->matchStaffList = $model->getMatchStaffList((int) $this->item->id);
			}

			if ($this->item && (bool) ($this->templateParams['show_stats'] ?? true)) {
				$this->matchPlayerStatistics = $model->getMatchPlayerStatistics((int) $this->item->id);
			}
		} elseif ($view === 'nextmatch') {
			$this->item = $model->getNextMatch(
				$projectId,
				(int) ($input->getInt('division_id', 0) ?: $input->getInt('division', 0)),
				(int) ($input->getInt('projectteam_id', 0) ?: $input->getInt('ptid', 0)),
				(int) ($input->getInt('team_id', 0) ?: $input->getInt('tid', 0)),
				(int) ($id ?: $input->getInt('match_id', 0) ?: $input->getInt('mid', 0))
			);
			$this->project = $this->item ? $model->getProject((int) $this->item->project_id) : $this->project;

			// Project se u nextmatch často zjistí až podle vyřešeného zápasu (přes tým/divizi,
			// bez project_id v URL) – templateParams výše proto mohly dopadnout na globální
			// konfiguraci místo projektové; jakmile známe skutečný projekt, načteme je znovu.
			if ($this->item) {
				$this->templateParams = $model->getTemplateParameters((int) $this->item->project_id, $view);
			}

			$this->items = $this->item ? $model->getMatchEvents((int) $this->item->id) : [];
			$this->headToHeadMatches = $this->item ? $model->getHeadToHeadMatches((int) $this->item->projectteam1_id, (int) $this->item->projectteam2_id, (int) $this->item->id) : [];
			$this->matchReferees = $this->item ? $model->getMatchReferees((int) $this->item->id) : [];
			$this->matchTeamComparison = $this->item ? $model->getMatchTeamComparison($this->item) : [];
			$nbPrevious = max(1, (int) ($this->templateParams['nb_previous'] ?? 5));
			$this->homeForm = $this->item ? $this->recentForm($model, (int) $this->item->project_id, (int) $this->item->projectteam1_id, $nbPrevious) : [];
			$this->awayForm = $this->item ? $this->recentForm($model, (int) $this->item->project_id, (int) $this->item->projectteam2_id, $nbPrevious) : [];
		} elseif ($view === 'person') {
			$this->item = $model->getPerson($id ?: $input->getInt('person_id', 0));
			$this->playerHistory = $this->item ? $model->getPlayerHistory((int) $this->item->id) : [];
			$this->staffHistory = $this->item ? $model->getStaffHistory((int) $this->item->id) : [];
			$this->refereeHistory = $this->item ? $model->getRefereeHistory((int) $this->item->id) : [];
			$this->personStats = $this->item && method_exists($model, 'getPersonStats') ? $model->getPersonStats((int) $this->item->id) : [];
			$this->playerMatches = $this->item && method_exists($model, 'getPlayerMatches') ? $model->getPlayerMatches((int) $this->item->id) : [];

			// player.xml/staff.xml/referee.xml – tři samostatné konfigurace, protože sjednocený
			// pohled 'person' může na jedné stránce zobrazovat hráčskou, realizační i rozhodcovskou historii najednou.
			$this->playerTemplateParams = $model->getTemplateParameters($projectId, 'player');
			$this->staffTemplateParams = $model->getTemplateParameters($projectId, 'staff');
			$this->refereeTemplateParams = $model->getTemplateParameters($projectId, 'referee');

			if ($this->item && method_exists($model, 'getPlayerCareerData')) {
				$careerData = $model->getPlayerCareerData((int) $this->item->id);
				$this->playerMatchStats = PlayerStatsHelper::aggregate(
					$careerData['appearances'],
					$careerData['subOut'],
					$careerData['events'],
					static fn (object $row) => (int) $row->match_id
				);
				$this->playerCareerStats = PlayerStatsHelper::aggregate(
					$careerData['appearances'],
					$careerData['subOut'],
					$careerData['events'],
					static fn (object $row) => (int) $row->projectteam_id
				);
			} else {
				$this->playerMatchStats = [];
				$this->playerCareerStats = [];
			}
		} elseif ($view === 'clubs') {
			$this->items = $model->getClubs();
		} elseif ($view === 'club') {
			$this->item = $model->getClub($id ?: $input->getInt('club_id', 0));
			$this->items = $this->item ? $model->getClubTeams((int) $this->item->id) : [];
			$this->clubPlaygrounds = $this->item ? $model->getClubPlaygrounds((int) $this->item->id) : [];

			// Klub není vázaný na jeden konkrétní projekt (může hrát ve více soutěžích
			// zároveň) — konfigurace 'clubinfo' proto vždy jen centrální (globální).
			$this->templateParams = $model->getTemplateParameters(0, 'clubinfo');
		} elseif ($view === 'playground') {
			$this->item = $model->getPlayground($id ?: $input->getInt('playground_id', 0));
		} elseif ($view === 'referees') {
			$this->items = $projectId > 0 ? $model->getReferees($projectId) : [];
		} elseif ($view === 'stats') {
			$this->items = $projectId > 0 ? $model->getStats($projectId) : [];
		} elseif ($view === 'statsranking') {
			$this->statistics = $projectId > 0 ? $model->getProjectStatistics($projectId) : [];
			$this->items = $projectId > 0 ? $model->getStatsRankings(
				$projectId,
				(int) ($input->getInt('statistic_id', 0) ?: $input->getInt('sid', 0)),
				(int) ($input->getInt('projectteam_id', 0) ?: $input->getInt('tid', 0))
			) : [];
		} elseif ($view === 'eventsranking') {
			$this->eventTypes = $projectId > 0 ? $model->getProjectEventTypes($projectId) : [];
			$this->items = $projectId > 0 ? $model->getEventRankings(
				$projectId,
				(int) ($input->getInt('event_type_id', 0) ?: $input->getInt('evid', 0)),
				(int) ($input->getInt('projectteam_id', 0) ?: $input->getInt('tid', 0)),
				(int) ($input->getInt('match_id', 0) ?: $input->getInt('mid', 0))
			) : [];
		}

		if ($view === 'ical') {
			$app = Factory::getApplication();
			$host = (string) (parse_url(Uri::root(), PHP_URL_HOST) ?: '');
			$calendarName = $this->project->name ?? $this->scheduleTeam->team_name ?? $this->scheduleClub->name ?? 'JoomLeague';
			$filename = preg_replace('/[^a-z0-9-]+/i', '-', strtolower((string) $calendarName)) ?: 'joomleague';

			$app->clearHeaders();
			$app->setHeader('Content-Type', 'text/calendar; charset=utf-8', true);
			$app->setHeader('Content-Disposition', 'inline; filename="' . $filename . '.ics"', true);
			$app->setHeader('Cache-Control', 'no-cache, must-revalidate', true);

			if (!headers_sent()) {
				header('Content-Type: text/calendar; charset=utf-8', true);
				header('Content-Disposition: inline; filename="' . $filename . '.ics"', true);
				header('Cache-Control: no-cache, must-revalidate', true);
			}

			echo IcalFeedHelper::render($this->matches, (string) $calendarName, $host);
			$app->close();
		}

		$this->preparePathway($view);
		$this->prepareDocumentTitle($view);
		$this->prepareCanonicalLink($view);

		parent::display($tpl);

		if ($view !== 'ical') {
			echo '<div class="jl-site-powered">Powered by <a href="https://klucon.cz" target="_blank" rel="noopener noreferrer">JoomLeague</a></div>';
		}
	}

	/**
	 * Posledních N odehraných zápasů projektového týmu (nejnovější první),
	 * s pohledem daného týmu (skóre a výsledek W/D/L). Reużívá getMatches.
	 *
	 * @return  array<int, object>
	 */
	private function recentForm($model, int $projectId, int $projectTeamId, int $limit = 5): array
	{
		if ($projectId < 1 || $projectTeamId < 1) {
			return [];
		}

		$played = [];

		foreach ($model->getMatches($projectId, 0, $projectTeamId) as $match) {
			if ($match->team1_result === null || $match->team2_result === null) {
				continue;
			}

			$isHome  = (int) ($match->home_projectteam_id ?? 0) === $projectTeamId;
			$own     = (int) ($isHome ? $match->team1_result : $match->team2_result);
			$against = (int) ($isHome ? $match->team2_result : $match->team1_result);

			$match->form_is_home  = $isHome;
			$match->form_opponent = (string) ($isHome ? ($match->away_name ?? '') : ($match->home_name ?? ''));
			$match->form_score    = $own . ':' . $against;
			$match->form_result   = $own > $against ? 'w' : ($own < $against ? 'l' : 'd');
			$played[] = $match;
		}

		usort($played, static fn ($a, $b) => strcmp((string) $b->match_date, (string) $a->match_date));

		return array_slice($played, 0, $limit);
	}

	private function prepareDocumentTitle(string $view): void
	{
		if ($view === 'ical') {
			return;
		}

		$title = $this->pathwayTitle($view);
		$title = $title !== '' ? Text::_($title) : '';
		$projectName = $this->pageTitleLabel();
		$teamName = $this->teamPathwayName();

		if ($view === 'project' && $projectName !== '') {
			$this->getDocument()->setTitle($projectName);
			return;
		}

		if ($view === 'team' && $teamName !== '') {
			$this->getDocument()->setTitle($projectName !== '' ? $teamName . ' - ' . $projectName : $teamName);
			return;
		}

		if (in_array($view, ['roster', 'teamstats', 'rivals'], true) && $title !== '' && $teamName !== '') {
			$parts = [$title, $teamName];

			if ($projectName !== '') {
				$parts[] = $projectName;
			}

			$this->getDocument()->setTitle(implode(' - ', $parts));
			return;
		}

		if ($title !== '' && $projectName !== '') {
			$this->getDocument()->setTitle($title . ' - ' . $projectName);
			return;
		}

		if ($title !== '') {
			$this->getDocument()->setTitle($title);
		}
	}

	/**
	 * Sestaví "název projektu" pro titulek stránky podle šablonového parametru page_title_format
	 * (COM_JOOMLEAGUE_FES_PARAM_LABEL_PAGE_TITLE_FORMAT): 0=projekt, 1=projekt+liga, 2=projekt+liga+sezóna,
	 * 3=projekt+sezóna, 4=liga, 5=liga+sezóna, 6=sezóna, 7=žádné. Beze změny (0) odpovídá
	 * původnímu chování, takže views bez tohoto parametru nejsou ovlivněny.
	 */
	private function pageTitleLabel(): string
	{
		$format = (string) ($this->templateParams['page_title_format'] ?? '0');
		$projectName = $this->project !== null ? trim((string) ($this->project->name ?? '')) : '';
		$leagueName = $this->project !== null ? trim((string) ($this->project->league_name ?? '')) : '';
		$seasonName = $this->project !== null ? trim((string) ($this->project->season_name ?? '')) : '';

		$parts = match ($format) {
			'1' => [$projectName, $leagueName],
			'2' => [$projectName, $leagueName, $seasonName],
			'3' => [$projectName, $seasonName],
			'4' => [$leagueName],
			'5' => [$leagueName, $seasonName],
			'6' => [$seasonName],
			'7' => [],
			default => [$projectName],
		};

		return implode(' - ', array_filter($parts, static fn (string $p): bool => $p !== ''));
	}

	private function prepareCanonicalLink(string $view): void
	{
		if ($view === 'ical') {
			return;
		}

		$url = $this->canonicalRoute($view);

		if ($url === '') {
			return;
		}

		$this->getDocument()->addHeadLink($url, 'canonical');
	}

	private function canonicalRoute(string $view): string
	{
		$input = Factory::getApplication()->getInput();
		$query = [
			'option' => 'com_joomleague',
			'view' => $view,
		];

		foreach ($this->canonicalInputKeys($view) as $key) {
			$value = (string) $input->get($key, '', 'cmd');

			if ($value === '' || $value === '0') {
				continue;
			}

			if ($key === 'scope' && $value === 'total') {
				continue;
			}

			$query[$key] = $value;
		}

		if ($this->project !== null && !isset($query['project_id'])) {
			$query['project_id'] = (int) $this->project->id;
		}

		if ($this->item !== null && !isset($query['id'])) {
			$query['id'] = (int) ($this->item->id ?? 0);
		}

		$parts = [];

		foreach ($query as $key => $value) {
			if ($value === null || $value === '' || $value === 0) {
				continue;
			}

			$parts[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
		}

		if ($parts === []) {
			return '';
		}

		return Route::_('index.php?' . implode('&', $parts), false, Route::TLS_IGNORE, true);
	}

	/**
	 * @return  array<int, string>
	 */
	private function canonicalInputKeys(string $view): array
	{
		return match ($view) {
			'project', 'teams', 'referees', 'stats', 'resultsmatrix' => ['project_id'],
			'ranking' => ['project_id', 'scope', 'division_id', 'round_id'],
			'results' => ['project_id', 'round_id'],
			'resultsranking' => ['project_id', 'round_id'],
			'schedule' => ['project_id', 'club_id', 'projectteam_id', 'ptid', 'plan', 'filter'],
			'statsranking' => ['project_id', 'statistic_id', 'sid', 'projectteam_id', 'tid'],
			'eventsranking' => ['project_id', 'event_type_id', 'evid', 'projectteam_id', 'tid', 'match_id', 'mid'],
			'curve' => ['project_id', 'division_id', 'division', 'projectteam1_id', 'tid1', 'projectteam2_id', 'tid2'],
			'nextmatch' => ['project_id', 'division_id', 'division', 'projectteam_id', 'ptid', 'team_id', 'tid', 'match_id', 'mid'],
			'prediction' => ['project_id', 'game_id', 'round_id'],
			'treetonode' => ['project_id', 'treeto_id', 'tnid', 'id'],
			'raceresults' => ['project_id', 'round_id', 'category_id', 'sex', 'status'],
			'team', 'roster', 'rivals', 'teamstats' => ['project_id', 'id', 'projectteam_id', 'tid'],
			'matchreport' => ['project_id', 'id', 'match_id'],
			'person' => ['project_id', 'id', 'person_id'],
			'club' => ['id', 'club_id'],
			'playground' => ['id', 'playground_id'],
			default => [],
		};
	}

	private function preparePathway(string $view): void
	{
		if ($view === 'ical') {
			return;
		}

		$app = Factory::getApplication();

		if (!method_exists($app, 'getPathway')) {
			return;
		}

		$pathway = $app->getPathway();

		if ($this->project !== null && !empty($this->project->name)) {
			$link = $view === 'project'
				? ''
				: Route::_('index.php?option=com_joomleague&view=project&project_id=' . (int) $this->project->id);

			$pathway->addItem((string) $this->project->name, $link);
		}

		if ($this->prepareTeamPathway($pathway, $view)) {
			return;
		}

		$title = $this->pathwayTitle($view);

		if ($title !== '' && $view !== 'projects' && $view !== 'project') {
			$pathway->addItem(Text::_($title));
		}
	}

	private function prepareTeamPathway(object $pathway, string $view): bool
	{
		if (!in_array($view, ['team', 'roster', 'teamstats', 'rivals'], true) || $this->item === null) {
			return false;
		}

		$projectId = (int) ($this->item->project_id ?? $this->project->id ?? 0);
		$teamId = (int) ($this->item->id ?? 0);
		$teamName = $this->teamPathwayName();

		if ($projectId > 0) {
			$pathway->addItem(
				Text::_('COM_JOOMLEAGUE_SITE_TEAMS'),
				Route::_('index.php?option=com_joomleague&view=teams&project_id=' . $projectId)
			);
		}

		if ($teamName !== '') {
			$pathway->addItem(
				$teamName,
				$view === 'team' || $teamId < 1 ? '' : Route::_('index.php?option=com_joomleague&view=team&id=' . $teamId)
			);
		}

		if ($view !== 'team') {
			$title = $this->pathwayTitle($view);

			if ($title !== '') {
				$pathway->addItem(Text::_($title));
			}
		}

		return true;
	}

	private function teamPathwayName(): string
	{
		if ($this->item === null) {
			return '';
		}

		foreach (['team_name', 'name', 'team_short_name'] as $property) {
			$value = trim((string) ($this->item->{$property} ?? ''));

			if ($value !== '') {
				return $value;
			}
		}

		return '';
	}

	private function pathwayTitle(string $view): string
	{
		return match ($view) {
			'club' => 'COM_JOOMLEAGUE_SITE_CLUB',
			'clubs' => 'COM_JOOMLEAGUE_SITE_CLUBS',
			'curve' => 'COM_JOOMLEAGUE_SITE_CURVE',
			'eventsranking' => 'COM_JOOMLEAGUE_SITE_EVENTS_RANKING',
			'matchreport' => 'COM_JOOMLEAGUE_SITE_MATCHREPORT',
			'nextmatch' => 'COM_JOOMLEAGUE_SITE_NEXT_MATCH',
			'person' => 'COM_JOOMLEAGUE_SITE_PERSON',
			'playground' => 'COM_JOOMLEAGUE_SITE_PLAYGROUND',
			'prediction' => 'COM_JOOMLEAGUE_SITE_PREDICTION',
			'project' => 'COM_JOOMLEAGUE_SITE_PROJECT',
			'projects' => 'COM_JOOMLEAGUE_SITE_PROJECTS_TITLE',
			'raceresults' => 'COM_JOOMLEAGUE_SITE_RACE_RESULTS',
			'ranking' => 'COM_JOOMLEAGUE_SITE_RANKING',
			'referees' => 'COM_JOOMLEAGUE_SITE_REFEREES',
			'results' => 'COM_JOOMLEAGUE_SITE_RESULTS',
			'resultsmatrix' => 'COM_JOOMLEAGUE_SITE_RESULT_MATRIX',
			'resultsranking' => 'COM_JOOMLEAGUE_SITE_RESULTS_RANKING',
			'rivals' => 'COM_JOOMLEAGUE_SITE_RIVALS',
			'roster' => 'COM_JOOMLEAGUE_SITE_ROSTER',
			'schedule' => 'COM_JOOMLEAGUE_SITE_SCHEDULE',
			'stats' => 'COM_JOOMLEAGUE_SITE_STATS',
			'statsranking' => 'COM_JOOMLEAGUE_SITE_STATS_RANKING',
			'team' => 'COM_JOOMLEAGUE_SITE_TEAM',
			'teams' => 'COM_JOOMLEAGUE_SITE_TEAMS',
			'teamstats' => 'COM_JOOMLEAGUE_SITE_TEAM_STATS',
			'treetonode' => 'COM_JOOMLEAGUE_SITE_TREE',
			default => '',
		};
	}
}
