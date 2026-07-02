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
	public array $rounds = [];
	public array $teams = [];
	public array $standings = [];
	public object|null $item = null;

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
		} elseif ($view === 'schedule') {
			$this->matches = $projectId > 0 ? $model->getMatches($projectId, 0, (int) ($input->getInt('projectteam_id') ?? 0), 0, true) : [];
		} elseif ($view === 'teams') {
			$this->teams = $projectId > 0 ? $model->getProjectTeams($projectId) : [];
		} elseif ($view === 'team' || $view === 'roster') {
			$this->item = $model->getTeam($id ?: $input->getInt('projectteam_id'));
			$this->items = $this->item ? $model->getRoster((int) $this->item->id) : [];
			$this->matches = $this->item ? $model->getMatches((int) $this->item->project_id, 0, (int) $this->item->id) : [];
		} elseif ($view === 'matchreport') {
			$this->item = $model->getMatch($id ?: $input->getInt('match_id'));
			$this->items = $this->item ? $model->getMatchEvents((int) $this->item->id) : [];
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
		} elseif ($view === 'playground') {
			$this->item = $model->getPlayground($id ?: $input->getInt('playground_id'));
		} elseif ($view === 'referees') {
			$this->items = $projectId > 0 ? $model->getReferees($projectId) : [];
		} elseif ($view === 'stats') {
			$this->items = $projectId > 0 ? $model->getStats($projectId) : [];
		}

		parent::display($tpl);
	}
}
