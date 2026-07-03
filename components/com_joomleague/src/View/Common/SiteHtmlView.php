<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\View\Common;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;

class SiteHtmlView extends BaseHtmlView
{
	public object|null $project = null;
	public array $items = [];
	public array $matches = [];
	public array $matrix = [];
	public array $rounds = [];
	public array $teams = [];
	public array $standings = [];
	public array $eventTypes = [];
	public array $statistics = [];
	public array $teamSeasons = [];
	public array $teamStats = [];
	public array $teamPlayerStats = [];
	public array $rivals = [];
	public array $clubPlaygrounds = [];
	public array $matchSummary = [];
	public array $headToHeadMatches = [];
	public object|null $item = null;
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
		$projectId = (int) (($input->getInt('project_id') ?: $input->getInt('pid')) ?? 0);
		$id = $input->getInt('id');

		$this->project = method_exists($model, 'getProject') ? $model->getProject($projectId) : null;
		$projectId = $this->project ? (int) $this->project->id : $projectId;

		if ($view === 'projects') {
			$this->items = $model->getProjects();
		} elseif ($view === 'project') {
			$this->teams = $projectId > 0 ? $model->getProjectTeams($projectId) : [];
			$this->rounds = $projectId > 0 ? $model->getRounds($projectId) : [];
			$this->matches = $projectId > 0 ? $model->getMatches($projectId, 0, 0, 8, true) : [];
		} elseif ($view === 'ranking') {
			$this->standings = $projectId > 0 ? $model->getStandings($projectId) : [];
		} elseif ($view === 'results') {
			$this->rounds = $projectId > 0 ? $model->getRounds($projectId) : [];
			$this->matches = $projectId > 0 ? $model->getMatches($projectId, (int) ($input->getInt('round_id') ?? 0)) : [];
		} elseif ($view === 'resultsranking') {
			$this->rounds = $projectId > 0 ? $model->getRounds($projectId) : [];
			$this->matches = $projectId > 0 ? $model->getMatches($projectId, (int) ($input->getInt('round_id') ?? 0)) : [];
			$this->standings = $projectId > 0 ? $model->getStandings($projectId) : [];
		} elseif ($view === 'resultsmatrix') {
			$this->teams = $projectId > 0 ? $model->getProjectTeams($projectId) : [];
			$this->matrix = $projectId > 0 ? $model->getResultMatrix($projectId) : [];
		} elseif ($view === 'schedule') {
			$scheduleTeamId = (int) ($input->getInt('projectteam_id') ?? 0);
			$scheduleClubId = (int) ($input->getInt('club_id') ?? 0);

			if ($scheduleClubId > 0) {
				$this->scheduleClub = $model->getClub($scheduleClubId);
				$this->matches = $this->scheduleClub ? $model->getClubMatches((int) $this->scheduleClub->id, $input->getInt('project_id')) : [];
			} elseif ($scheduleTeamId > 0) {
				$this->scheduleTeam = $model->getTeam($scheduleTeamId);
				$this->matches = $this->scheduleTeam ? $model->getMatches((int) $this->scheduleTeam->project_id, 0, (int) $this->scheduleTeam->id) : [];
			} else {
				$this->matches = $projectId > 0 ? $model->getMatches($projectId) : [];
			}

			$this->matchSummary = $model->summarizeMatches($this->matches, $scheduleTeamId);
		} elseif ($view === 'ical') {
			$scheduleTeamId = (int) ($input->getInt('projectteam_id') ?? 0);
			$scheduleClubId = (int) ($input->getInt('club_id') ?? 0);

			if ($scheduleClubId > 0) {
				$this->scheduleClub = $model->getClub($scheduleClubId);
				$this->matches = $this->scheduleClub ? $model->getClubMatches((int) $this->scheduleClub->id, $input->getInt('project_id')) : [];
			} elseif ($scheduleTeamId > 0) {
				$this->scheduleTeam = $model->getTeam($scheduleTeamId);
				$this->matches = $this->scheduleTeam ? $model->getMatches((int) $this->scheduleTeam->project_id, 0, (int) $this->scheduleTeam->id) : [];
			} else {
				$this->matches = $projectId > 0 ? $model->getMatches($projectId) : [];
			}
		} elseif ($view === 'teams') {
			$this->teams = $projectId > 0 ? $model->getProjectTeams($projectId) : [];
		} elseif ($view === 'team' || $view === 'roster') {
			$this->item = $model->getTeam($id ?: $input->getInt('projectteam_id'));
			$this->items = $this->item ? $model->getRoster((int) $this->item->id) : [];
			$this->matches = $this->item ? $model->getMatches((int) $this->item->project_id, 0, (int) $this->item->id) : [];
			$this->teamSeasons = $this->item ? $model->getTeamSeasons((int) $this->item->team_id, (int) $this->item->id) : [];
		} elseif ($view === 'teamstats') {
			$this->item = $model->getTeam($id ?: $input->getInt('projectteam_id') ?: $input->getInt('tid'));
			$this->matches = $this->item ? $model->getMatches((int) $this->item->project_id, 0, (int) $this->item->id) : [];
			$this->teamStats = $this->item ? $model->getTeamStatsSummary((int) $this->item->id) : [];
			$this->teamPlayerStats = $this->item ? $model->getTeamPlayerStats((int) $this->item->project_id, (int) $this->item->id) : [];
		} elseif ($view === 'rivals') {
			$this->item = $model->getTeam($id ?: $input->getInt('projectteam_id') ?: $input->getInt('tid'));
			$this->rivals = $this->item ? $model->getTeamRivals((int) $this->item->project_id, (int) $this->item->id) : [];
		} elseif ($view === 'matchreport') {
			$this->item = $model->getMatch($id ?: $input->getInt('match_id'));
			$this->items = $this->item ? $model->getMatchEvents((int) $this->item->id) : [];
			$this->headToHeadMatches = $this->item ? $model->getHeadToHeadMatches((int) $this->item->projectteam1_id, (int) $this->item->projectteam2_id, (int) $this->item->id) : [];
		} elseif ($view === 'person') {
			$this->item = $model->getPerson($id ?: $input->getInt('person_id'));
			$this->playerHistory = $this->item ? $model->getPlayerHistory((int) $this->item->id) : [];
			$this->staffHistory = $this->item ? $model->getStaffHistory((int) $this->item->id) : [];
			$this->refereeHistory = $this->item ? $model->getRefereeHistory((int) $this->item->id) : [];
		} elseif ($view === 'clubs') {
			$this->items = $model->getClubs();
		} elseif ($view === 'club') {
			$this->item = $model->getClub($id ?: $input->getInt('club_id'));
			$this->items = $this->item ? $model->getClubTeams((int) $this->item->id) : [];
			$this->clubPlaygrounds = $this->item ? $model->getClubPlaygrounds((int) $this->item->id) : [];
		} elseif ($view === 'playground') {
			$this->item = $model->getPlayground($id ?: $input->getInt('playground_id'));
		} elseif ($view === 'referees') {
			$this->items = $projectId > 0 ? $model->getReferees($projectId) : [];
		} elseif ($view === 'stats') {
			$this->items = $projectId > 0 ? $model->getStats($projectId) : [];
		} elseif ($view === 'statsranking') {
			$this->statistics = $projectId > 0 ? $model->getProjectStatistics($projectId) : [];
			$this->items = $projectId > 0 ? $model->getStatsRankings(
				$projectId,
				(int) ($input->getInt('statistic_id') ?: $input->getInt('sid')),
				(int) ($input->getInt('projectteam_id') ?: $input->getInt('tid'))
			) : [];
		} elseif ($view === 'eventsranking') {
			$this->eventTypes = $projectId > 0 ? $model->getProjectEventTypes($projectId) : [];
			$this->items = $projectId > 0 ? $model->getEventRankings(
				$projectId,
				(int) ($input->getInt('event_type_id') ?: $input->getInt('evid')),
				(int) ($input->getInt('projectteam_id') ?: $input->getInt('tid')),
				(int) ($input->getInt('match_id') ?: $input->getInt('mid'))
			) : [];
		}

		parent::display($tpl);
	}
}
