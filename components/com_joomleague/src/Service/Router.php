<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterBase;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;

final class Router extends RouterBase
{
	private const PROJECT_SECTION_VIEWS = [
		'project' => '',
		'ranking' => 'ranking',
		'results' => 'results',
		'schedule' => 'schedule',
		'teams' => 'teams',
		'referees' => 'referees',
		'stats' => 'stats',
		'resultsmatrix' => 'resultsmatrix',
		'resultsranking' => 'resultsranking',
		'statsranking' => 'statsranking',
		'eventsranking' => 'eventsranking',
		'curve' => 'curve',
		'nextmatch' => 'nextmatch',
		'ical' => 'ical',
		'prediction' => 'prediction',
		'treetonode' => 'treetonode',
		'raceresults' => 'raceresults',
	];

	private const VIEW_ID_KEYS = [
		'project' => 'project_id',
		'ranking' => 'project_id',
		'results' => 'project_id',
		'schedule' => 'project_id',
		'teams' => 'project_id',
		'referees' => 'project_id',
		'stats' => 'project_id',
		'resultsmatrix' => 'project_id',
		'resultsranking' => 'project_id',
		'statsranking' => 'project_id',
		'eventsranking' => 'project_id',
		'curve' => 'project_id',
		'nextmatch' => 'project_id',
		'ical' => 'project_id',
		'prediction' => 'project_id',
		'treetonode' => 'project_id',
		'raceresults' => 'project_id',
		'rivals' => 'projectteam_id',
		'teamstats' => 'projectteam_id',
		'club' => 'id',
		'team' => 'id',
		'roster' => 'id',
		'matchreport' => 'id',
		'person' => 'id',
		'playground' => 'id',
	];

	private const PROJECT_SECTION_ROUTE_ALIASES = [
		'ranking' => ['standings', 'tabelle'],
		'results' => ['results', 'ergebnisse'],
		'schedule' => ['schedule', 'spielplan'],
		'teams' => ['teams'],
		'referees' => ['referees', 'schiedsrichter'],
		'stats' => ['statistics', 'statistiken'],
		'resultsmatrix' => ['result-matrix', 'ergebnismatrix'],
		'resultsranking' => ['results-standings', 'ergebnisse-tabelle'],
		'statsranking' => ['statistics-ranking', 'statistikrangliste'],
		'eventsranking' => ['events-ranking', 'ereignisrangliste'],
		'curve' => ['ranking-curve', 'ranglistenverlauf'],
		'nextmatch' => ['next-match', 'naechstes-spiel'],
		'ical' => ['ical'],
		'prediction' => ['prediction-game', 'prediction', 'tippspiel'],
		'treetonode' => ['tournament-tree', 'turnierbaum'],
		'raceresults' => ['race-results', 'laufergebnisse'],
	];

	public function __construct(
		CMSApplicationInterface $app,
		AbstractMenu $menu,
		private readonly ?CategoryFactoryInterface $categoryFactory,
		private readonly DatabaseInterface $db
	) {
		parent::__construct($app, $menu);

		$app->getLanguage()->load('com_joomleague', JPATH_SITE);
	}

	public function preprocess($query): array
	{
		$query = (array) $query;

		if (($query['option'] ?? '') !== 'com_joomleague' || empty($query['view'])) {
			return $query;
		}

		if (!empty($query['Itemid'])) {
			return $query;
		}

		$item = $this->shouldUseCanonicalRoute($query) ? null : $this->findExactMenuItem($query);

		if ($item !== null) {
			$query['Itemid'] = $item->id;

			return $query;
		}

		$base = $this->findBaseMenuItem((string) $query['view']);

		if ($base !== null) {
			$query['Itemid'] = $base->id;
		}

		return $query;
	}

	public function build(&$query): array
	{
		$segments = [];
		$view = (string) ($query['view'] ?? '');

		if ($view === '') {
			return $segments;
		}

		if ($this->isExactMenuQuery($query)) {
			$this->unsetJoomleagueQuery($query);

			return $segments;
		}

		if ($view === 'projects') {
			$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_COMPETITIONS', 'souteze');
			unset($query['view']);

			return $segments;
		}

		if ($view === 'clubs') {
			$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_CLUBS', 'kluby');
			unset($query['view']);

			return $segments;
		}

		if (array_key_exists($view, self::PROJECT_SECTION_VIEWS)) {
			$projectId = (int) ($query['project_id'] ?? $query['pid'] ?? 0);
			$projectId = $projectId > 0 ? $projectId : $this->lookupProjectIdFromProjectSectionQuery($view, $query);

			if ($projectId > 0) {
				if ($this->activeMenuView($query) !== 'projects') {
					$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_COMPETITIONS', 'souteze');
				}

				$segments[] = $this->getAlias('project', $projectId);

				$section = self::PROJECT_SECTION_VIEWS[$view];

				if ($section !== '') {
					$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_' . strtoupper($section), $this->defaultSectionSegment($section));
				}

				$this->appendProjectSectionFilterSegments($segments, $query, $view, $projectId);
				unset($query['view'], $query['project_id'], $query['pid']);
			} else {
				if ($this->activeMenuView($query) !== 'projects') {
					$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_COMPETITIONS', 'souteze');
				}

				$section = self::PROJECT_SECTION_VIEWS[$view];

				if ($section !== '') {
					$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_' . strtoupper($section), $this->defaultSectionSegment($section));
				}

				unset($query['view'], $query['project_id'], $query['pid']);
			}

			return $segments;
		}

		if ($view === 'club') {
			$id = (int) ($query['id'] ?? $query['club_id'] ?? 0);

			if ($id > 0) {
				if ($this->activeMenuView($query) !== 'club') {
					$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_CLUB', 'klub');
				}

				$segments[] = $this->getAlias('club', $id);
				unset($query['view'], $query['id'], $query['club_id']);
			}

			return $segments;
		}

		if ($view === 'team' || $view === 'roster') {
			$id = (int) ($query['id'] ?? $query['projectteam_id'] ?? 0);

			if ($id > 0) {
				$projectId = $this->lookupProjectIdByProjectTeam($id);

				if ($projectId > 0 && $this->activeMenuView($query) !== 'projects') {
					$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_COMPETITIONS', 'souteze');
				}

				if ($projectId > 0) {
					$segments[] = $this->getAlias('project', $projectId);
				}

				$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_TEAMS', 'tymy');
				$segments[] = $this->getAlias('projectteam', $id);

				if ($view === 'roster') {
					$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_ROSTER', 'soupiska');
				}

				unset($query['view'], $query['id'], $query['projectteam_id'], $query['project_id'], $query['pid']);
			}

			return $segments;
		}

		if ($view === 'rivals' || $view === 'teamstats') {
			$id = (int) ($query['id'] ?? $query['projectteam_id'] ?? $query['tid'] ?? 0);

			if ($id > 0) {
				$projectId = $this->lookupProjectIdByProjectTeam($id);

				if ($projectId > 0 && $this->activeMenuView($query) !== 'projects') {
					$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_COMPETITIONS', 'souteze');
				}

				if ($projectId > 0) {
					$segments[] = $this->getAlias('project', $projectId);
				}

				$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_TEAMS', 'tymy');
				$segments[] = $this->getAlias('projectteam', $id);
				$segments[] = $view === 'rivals'
					? $this->segment('COM_JOOMLEAGUE_ROUTE_RIVALS', 'souperi')
					: $this->segment('COM_JOOMLEAGUE_ROUTE_TEAMSTATS', 'statistiky-tymu');
				unset($query['view'], $query['id'], $query['projectteam_id'], $query['tid'], $query['project_id'], $query['pid']);
			}

			return $segments;
		}

		if ($view === 'matchreport') {
			$id = (int) ($query['id'] ?? $query['match_id'] ?? 0);

			if ($id > 0) {
				$projectId = $this->lookupProjectIdByMatch($id);

				if ($projectId > 0 && $this->activeMenuView($query) !== 'projects') {
					$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_COMPETITIONS', 'souteze');
				}

				if ($projectId > 0) {
					$segments[] = $this->getAlias('project', $projectId);
				}

				$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_MATCHES', 'zapasy');
				$segments[] = $this->getAlias('match', $id);
				unset($query['view'], $query['id'], $query['match_id'], $query['project_id'], $query['pid']);
			}

			return $segments;
		}

		if ($view === 'person') {
			$id = (int) ($query['id'] ?? $query['person_id'] ?? 0);

			if ($id > 0) {
				$projectId = (int) ($query['project_id'] ?? $query['pid'] ?? 0);
				$projectId = $projectId > 0 ? $projectId : $this->lookupProjectIdByPerson($id);

				if ($projectId > 0 && $this->activeMenuView($query) !== 'projects') {
					$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_COMPETITIONS', 'souteze');
				}

				if ($projectId > 0) {
					$segments[] = $this->getAlias('project', $projectId);
				}

				$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_PERSONS', 'osoby');
				$segments[] = $this->getAlias('person', $id);
				unset($query['view'], $query['id'], $query['person_id'], $query['project_id'], $query['pid']);
			}

			return $segments;
		}

		if ($view === 'playground') {
			$id = (int) ($query['id'] ?? $query['playground_id'] ?? 0);

			if ($id > 0) {
				if ($this->activeMenuView($query) !== 'playground') {
					$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_PLAYGROUNDS', 'stadiony');
				}

				$segments[] = $this->getAlias('playground', $id);
				unset($query['view'], $query['id'], $query['playground_id']);
			}

			return $segments;
		}

		return $segments;
	}

	public function parse(&$segments): array
	{
		$vars = [];
		$segments = array_values(array_filter($segments, static fn ($segment): bool => $segment !== ''));

		if ($segments === []) {
			return $vars;
		}

		$first = $this->normaliseSegment((string) array_shift($segments));
		$activeView = $this->activeMenuView([]);

		if ($this->routeMatches($first, 'COM_JOOMLEAGUE_ROUTE_COMPETITIONS', 'souteze', ['competitions', 'wettbewerbe'])) {
			if ($segments === []) {
				return ['view' => 'projects'];
			}

			$section = $this->normaliseSegment((string) ($segments[0] ?? ''));
			$sectionView = $this->viewByProjectSection($section);

			if ($sectionView !== 'project') {
				array_shift($segments);
				$vars = ['view' => $sectionView];
				$this->parseProjectSectionFilterSegments($vars, $segments, 0);

				return $vars;
			}

			$projectId = $this->getIdByAlias('project', (string) array_shift($segments));

			if ($projectId < 1) {
				return ['view' => 'projects'];
			}

			return $this->parseProjectRoute($projectId, $segments);
		}

		if ($activeView === 'projects') {
			$sectionView = $this->viewByProjectSection($first);

			if ($sectionView !== 'project') {
				$vars = ['view' => $sectionView];
				$this->parseProjectSectionFilterSegments($vars, $segments, 0);

				return $vars;
			}

			array_unshift($segments, $first);
			$projectId = $this->getIdByAlias('project', (string) array_shift($segments));

			if ($projectId < 1) {
				return ['view' => 'projects'];
			}

			return $this->parseProjectRoute($projectId, $segments);
		}

		if ($this->routeMatches($first, 'COM_JOOMLEAGUE_ROUTE_CLUB', 'klub', ['club', 'verein'])) {
			return [
				'view' => 'club',
				'id' => $segments !== [] ? $this->getIdByAlias('club', (string) array_shift($segments)) : 0,
			];
		}

		if ($this->routeMatches($first, 'COM_JOOMLEAGUE_ROUTE_CLUBS', 'kluby', ['clubs', 'vereine'])) {
			if ($segments === []) {
				return ['view' => 'clubs'];
			}

			return [
				'view' => 'club',
				'id' => $this->getIdByAlias('club', (string) array_shift($segments)),
			];
		}

		if ($activeView === 'clubs') {
			return [
				'view' => 'club',
				'id' => $this->getIdByAlias('club', $first),
			];
		}

		if ($this->routeMatches($first, 'COM_JOOMLEAGUE_ROUTE_TEAMS', 'tymy', ['teams'])) {
			$id = $segments !== [] ? $this->getIdByAlias('projectteam', (string) array_shift($segments)) : 0;
			$section = $segments[0] ?? '';

			return [
				'view' => $this->routeMatches((string) $section, 'COM_JOOMLEAGUE_ROUTE_ROSTER', 'soupiska', ['roster', 'kader']) ? 'roster' : 'team',
				'id' => $id,
			];
		}

		if ($this->routeMatches($first, 'COM_JOOMLEAGUE_ROUTE_MATCHES', 'zapasy', ['matches', 'spiele'])) {
			return [
				'view' => 'matchreport',
				'id' => $segments !== [] ? $this->getIdByAlias('match', (string) array_shift($segments)) : 0,
			];
		}

		if ($this->routeMatches($first, 'COM_JOOMLEAGUE_ROUTE_PERSONS', 'osoby', ['people', 'persons', 'personen'])) {
			return [
				'view' => 'person',
				'id' => $segments !== [] ? $this->getIdByAlias('person', (string) array_shift($segments)) : 0,
			];
		}

		if ($this->routeMatches($first, 'COM_JOOMLEAGUE_ROUTE_PLAYGROUNDS', 'stadiony', ['venues', 'playgrounds', 'spielstaetten'])) {
			return [
				'view' => 'playground',
				'id' => $segments !== [] ? $this->getIdByAlias('playground', (string) array_shift($segments)) : 0,
			];
		}

		return $vars;
	}

	public static function route(string $view, array $query = []): string
	{
		$query = array_merge(['option' => 'com_joomleague', 'view' => $view], $query);
		$parts = [];

		foreach ($query as $key => $value) {
			if ($value === null || $value === '') {
				continue;
			}

			$parts[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
		}

		return Route::_('index.php?' . implode('&', $parts));
	}

	private function findExactMenuItem(array $query): ?object
	{
		$items = (array) $this->menu->getItems(['component'], ['com_joomleague']);

		foreach ($items as $item) {
			if ($this->menuQueryMatches($item->query ?? [], $query)) {
				return $item;
			}
		}

		return null;
	}

	private function findBaseMenuItem(string $view): ?object
	{
		$preferred = match ($view) {
			'club' => 'club',
			'playground' => 'playground',
			'project', 'ranking', 'results', 'schedule', 'teams', 'team', 'roster', 'rivals', 'teamstats', 'referees', 'stats', 'resultsmatrix', 'resultsranking', 'statsranking', 'eventsranking', 'curve', 'nextmatch', 'ical', 'prediction', 'treetonode', 'matchreport', 'person' => 'projects',
			default => $view,
		};

		if ($preferred === '') {
			return null;
		}

		$items = (array) $this->menu->getItems(['component'], ['com_joomleague']);

		foreach ($items as $item) {
			if (($item->query['view'] ?? '') === $preferred) {
				return $item;
			}
		}

		return null;
	}

	private function isExactMenuQuery(array $query): bool
	{
		if (empty($query['Itemid'])) {
			return false;
		}

		if ($this->shouldUseCanonicalRoute($query)) {
			return false;
		}

		$item = $this->menu->getItem($query['Itemid']);

		if (!$item || ($item->component ?? '') !== 'com_joomleague') {
			return false;
		}

		return $this->menuQueryMatches($item->query ?? [], $query);
	}

	private function activeMenuView(array $query): string
	{
		$item = null;

		if (!empty($query['Itemid'])) {
			$item = $this->menu->getItem($query['Itemid']);
		}

		if ($item === null) {
			$item = $this->menu->getActive();
		}

		if (!$item || ($item->component ?? '') !== 'com_joomleague') {
			return '';
		}

		return (string) ($item->query['view'] ?? '');
	}

	private function menuQueryMatches(array $menuQuery, array $query): bool
	{
		if (($menuQuery['option'] ?? '') !== 'com_joomleague') {
			return false;
		}

		foreach ($menuQuery as $key => $value) {
			if ($key === 'option') {
				continue;
			}

			if (!array_key_exists($key, $query)) {
				return false;
			}

			if ((string) $query[$key] !== (string) $value) {
				return false;
			}
		}

		foreach (self::VIEW_ID_KEYS as $view => $idKey) {
			if (($menuQuery['view'] ?? '') !== $view) {
				continue;
			}

			$aliases = $this->identityKeysForView($view, $idKey);

			foreach ($aliases as $alias) {
				if (array_key_exists($alias, $query) && !array_key_exists($alias, $menuQuery)) {
					return false;
				}
			}
		}

		return true;
	}

	private function identityKeysForView(string $view, string $idKey): array
	{
		return match ($view) {
			'project', 'ranking', 'results', 'schedule', 'teams', 'referees', 'stats', 'resultsmatrix', 'resultsranking', 'statsranking', 'eventsranking', 'curve', 'nextmatch', 'ical', 'prediction', 'treetonode', 'raceresults' => ['project_id', 'pid'],
			'club' => ['id', 'club_id'],
			'team', 'roster', 'rivals', 'teamstats' => ['id', 'projectteam_id', 'tid'],
			'matchreport' => ['id', 'match_id'],
			'person' => ['id', 'person_id'],
			'playground' => ['id', 'playground_id'],
			default => [$idKey],
		};
	}

	private function shouldUseCanonicalProjectRoute(array $query): bool
	{
		$view = (string) ($query['view'] ?? '');

		if (!array_key_exists($view, self::PROJECT_SECTION_VIEWS)) {
			return false;
		}

		return $view !== 'project'
			|| (int) ($query['project_id'] ?? $query['pid'] ?? 0) > 0
			|| $this->lookupProjectIdFromProjectSectionQuery($view, $query) > 0;
	}

	private function shouldUseCanonicalRoute(array $query): bool
	{
		if ($this->shouldUseCanonicalProjectRoute($query)) {
			return true;
		}

		$view = (string) ($query['view'] ?? '');

		return match ($view) {
			'club' => (int) ($query['id'] ?? $query['club_id'] ?? 0) > 0,
			'team', 'roster', 'rivals', 'teamstats' => (int) ($query['id'] ?? $query['projectteam_id'] ?? $query['tid'] ?? 0) > 0,
			'matchreport' => (int) ($query['id'] ?? $query['match_id'] ?? 0) > 0,
			'person' => (int) ($query['id'] ?? $query['person_id'] ?? 0) > 0,
			'playground' => (int) ($query['id'] ?? $query['playground_id'] ?? 0) > 0,
			default => false,
		};
	}

	private function unsetJoomleagueQuery(array &$query): void
	{
		unset($query['view'], $query['project_id'], $query['pid'], $query['id'], $query['club_id'], $query['projectteam_id'], $query['tid'], $query['match_id'], $query['playground_id'], $query['person_id'], $query['game_id']);
	}

	private function parseProjectRoute(int $projectId, array &$segments): array
	{
		$vars = ['project_id' => $projectId];
		$section = $segments[0] ?? '';
		$section = $this->normaliseSegment((string) $section);

		if ($section === '') {
			$vars['view'] = 'project';

			return $vars;
		}

		array_shift($segments);

		if ($this->routeMatches($section, 'COM_JOOMLEAGUE_ROUTE_TEAMS', 'tymy', ['teams'])) {
			if ($segments === []) {
				$vars['view'] = 'teams';

				return $vars;
			}

			$vars['id'] = $this->getIdByAlias('projectteam', (string) array_shift($segments), $projectId);
			$next = $segments[0] ?? '';
			$next = $this->normaliseSegment((string) $next);
			$isRoster = $this->routeMatches($next, 'COM_JOOMLEAGUE_ROUTE_ROSTER', 'soupiska', ['roster', 'kader']);

			if ($isRoster) {
				array_shift($segments);
			}

			if ($this->routeMatches($next, 'COM_JOOMLEAGUE_ROUTE_RIVALS', 'souperi', ['rivals', 'rivalen'])) {
				array_shift($segments);
				$vars['view'] = 'rivals';
				$vars['projectteam_id'] = $vars['id'];
				unset($vars['id']);

				return $vars;
			}

			if ($this->routeMatches($next, 'COM_JOOMLEAGUE_ROUTE_TEAMSTATS', 'statistiky-tymu', ['team-statistics', 'teamstatistiken'])) {
				array_shift($segments);
				$vars['view'] = 'teamstats';
				$vars['projectteam_id'] = $vars['id'];
				unset($vars['id']);

				return $vars;
			}

			$vars['view'] = $isRoster ? 'roster' : 'team';

			return $vars;
		}

		if ($this->routeMatches($section, 'COM_JOOMLEAGUE_ROUTE_MATCHES', 'zapasy', ['matches', 'spiele'])) {
			$vars['view'] = 'matchreport';
			$vars['id'] = $segments !== [] ? $this->getIdByAlias('match', (string) array_shift($segments), $projectId) : 0;

			return $vars;
		}

		if ($this->routeMatches($section, 'COM_JOOMLEAGUE_ROUTE_PERSONS', 'osoby', ['people', 'persons', 'personen'])) {
			$vars['view'] = 'person';
			$vars['id'] = $segments !== [] ? $this->getIdByAlias('person', (string) array_shift($segments), $projectId) : 0;

			return $vars;
		}

		$vars['view'] = $this->viewByProjectSection($section);
		$this->parseProjectSectionFilterSegments($vars, $segments, $projectId);

		return $vars;
	}

	private function appendProjectSectionFilterSegments(array &$segments, array &$query, string $view, int $projectId): void
	{
		if ($view === 'ranking') {
			$scope = (string) ($query['scope'] ?? '');

			if ($scope !== '' && $scope !== 'total') {
				$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_SCOPE', 'rozsah');
				$segments[] = $this->rankingScopeSegment($scope);
			}

			unset($query['scope']);

			return;
		}

		if ($view === 'schedule' || $view === 'ical') {
			$clubId = (int) ($query['club_id'] ?? 0);
			$projectTeamId = (int) ($query['projectteam_id'] ?? $query['ptid'] ?? 0);

			if ($clubId > 0) {
				$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_CLUB', 'klub');
				$segments[] = $this->getAlias('club', $clubId);
			} elseif ($projectTeamId > 0) {
				$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_TEAM', 'tym');
				$segments[] = $this->getAlias('projectteam', $projectTeamId);
			}

			unset($query['club_id'], $query['projectteam_id'], $query['ptid']);

			if ($view === 'schedule') {
				$this->appendScheduleDisplaySegments($segments, $query);
			}

			return;
		}

		if (in_array($view, ['statsranking', 'eventsranking', 'nextmatch'], true)) {
			$projectTeamId = (int) ($query['projectteam_id'] ?? $query['ptid'] ?? 0);

			if ($projectTeamId > 0) {
				$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_TEAM', 'tym');
				$segments[] = $this->getAlias('projectteam', $projectTeamId);
			}

			unset($query['projectteam_id'], $query['ptid']);

			if ($view === 'nextmatch' && $projectTeamId > 0) {
				unset($query['team_id'], $query['tid']);
			}

			return;
		}

		if ($view === 'curve') {
			$team1Id = (int) ($query['projectteam1_id'] ?? $query['tid1'] ?? 0);
			$team2Id = (int) ($query['projectteam2_id'] ?? $query['tid2'] ?? 0);

			if ($team1Id > 0) {
				$segments[] = $this->getAlias('projectteam', $team1Id);
			}

			if ($team2Id > 0) {
				$segments[] = $this->getAlias('projectteam', $team2Id);
			}

			unset($query['projectteam1_id'], $query['projectteam2_id'], $query['tid1'], $query['tid2']);
		}
	}

	private function appendScheduleDisplaySegments(array &$segments, array &$query): void
	{
		$plan = $this->normaliseSegment((string) ($query['plan'] ?? ''));
		$filter = $this->normaliseSegment((string) ($query['filter'] ?? ''));

		if ($plan === 'date') {
			$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_SCHEDULE_BY_DATE', 'podle-data');
		}

		if ($filter === 'home') {
			$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_SCHEDULE_HOME', 'doma');
		} elseif ($filter === 'away') {
			$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_SCHEDULE_AWAY', 'venku');
		}

		unset($query['plan'], $query['filter']);
	}

	private function rankingScopeSegment(string $scope): string
	{
		return match ($this->normaliseSegment($scope)) {
			'home' => $this->segment('COM_JOOMLEAGUE_ROUTE_SCOPE_HOME', 'doma'),
			'away' => $this->segment('COM_JOOMLEAGUE_ROUTE_SCOPE_AWAY', 'venku'),
			default => $this->segment('COM_JOOMLEAGUE_ROUTE_SCOPE_TOTAL', 'celkem'),
		};
	}

	private function parseProjectSectionFilterSegments(array &$vars, array &$segments, int $projectId): void
	{
		$view = (string) ($vars['view'] ?? '');
		$first = $segments[0] ?? '';
		$first = $this->normaliseSegment((string) $first);

		if ($view === 'ranking' && $this->routeMatches($first, 'COM_JOOMLEAGUE_ROUTE_SCOPE', 'rozsah', ['scope', 'bereich'])) {
			array_shift($segments);
			$scope = $segments !== [] ? $this->normaliseSegment((string) array_shift($segments)) : '';
			$scope = $this->rankingScopeFromSegment($scope);

			if ($scope !== '') {
				$vars['scope'] = $scope;
			}

			return;
		}

		$isClubFilter = $this->routeMatches($first, 'COM_JOOMLEAGUE_ROUTE_CLUB', 'klub', ['club', 'verein']);
		$isTeamFilter = $this->routeMatches($first, 'COM_JOOMLEAGUE_ROUTE_TEAM', 'tym', ['team']);

		if (($view === 'schedule' || $view === 'ical') && ($isClubFilter || $isTeamFilter)) {
			array_shift($segments);
			$value = $segments !== [] ? (string) array_shift($segments) : '';

			if ($isClubFilter) {
				$vars['club_id'] = $this->getIdByAlias('club', $value);
			} else {
				$vars['projectteam_id'] = $this->getIdByAlias('projectteam', $value, $projectId);
			}
		}

		if ($view === 'schedule') {
			$this->parseScheduleDisplaySegments($vars, $segments);
		}

		if ($view === 'schedule' || $view === 'ical') {
			return;
		}

		if (in_array($view, ['statsranking', 'eventsranking', 'nextmatch'], true) && $isTeamFilter) {
			array_shift($segments);
			$vars['projectteam_id'] = $segments !== [] ? $this->getIdByAlias('projectteam', (string) array_shift($segments), $projectId) : 0;

			return;
		}

		if ($view === 'curve') {
			if ($segments !== []) {
				$vars['projectteam1_id'] = $this->getIdByAlias('projectteam', (string) array_shift($segments), $projectId);
			}

			if ($segments !== []) {
				$vars['projectteam2_id'] = $this->getIdByAlias('projectteam', (string) array_shift($segments), $projectId);
			}
		}
	}

	private function parseScheduleDisplaySegments(array &$vars, array &$segments): void
	{
		while ($segments !== []) {
			$segment = $this->normaliseSegment((string) $segments[0]);

			if ($this->routeMatches($segment, 'COM_JOOMLEAGUE_ROUTE_SCHEDULE_BY_DATE', 'podle-data', ['by-date', 'nach-datum'])) {
				$vars['plan'] = 'date';
				array_shift($segments);

				continue;
			}

			if ($this->routeMatches($segment, 'COM_JOOMLEAGUE_ROUTE_SCHEDULE_BY_ROUND', 'podle-kol', ['by-round', 'nach-runden'])) {
				$vars['plan'] = 'round';
				array_shift($segments);

				continue;
			}

			if ($this->routeMatches($segment, 'COM_JOOMLEAGUE_ROUTE_SCHEDULE_HOME', 'doma', ['home', 'heim'])) {
				$vars['filter'] = 'home';
				array_shift($segments);

				continue;
			}

			if ($this->routeMatches($segment, 'COM_JOOMLEAGUE_ROUTE_SCHEDULE_AWAY', 'venku', ['away', 'auswaerts'])) {
				$vars['filter'] = 'away';
				array_shift($segments);

				continue;
			}

			break;
		}
	}

	private function rankingScopeFromSegment(string $segment): string
	{
		if ($this->routeMatches($segment, 'COM_JOOMLEAGUE_ROUTE_SCOPE_HOME', 'doma', ['home', 'heim'])) {
			return 'home';
		}

		if ($this->routeMatches($segment, 'COM_JOOMLEAGUE_ROUTE_SCOPE_AWAY', 'venku', ['away', 'auswaerts'])) {
			return 'away';
		}

		if ($this->routeMatches($segment, 'COM_JOOMLEAGUE_ROUTE_SCOPE_TOTAL', 'celkem', ['total', 'gesamt'])) {
			return 'total';
		}

		return '';
	}

	private function getAlias(string $type, int $id): string
	{
		$alias = match ($type) {
			'project' => $this->lookupAlias('#__joomleague_project', $id, 'alias', 'name'),
			'club' => $this->lookupAlias('#__joomleague_club', $id, 'alias', 'name'),
			'playground' => $this->lookupAlias('#__joomleague_playground', $id, 'alias', 'name'),
			'projectteam' => $this->lookupProjectTeamAlias($id),
			'match' => $this->lookupMatchAlias($id),
			'person' => $this->lookupPersonAlias($id),
			default => '',
		};

		return $id . '-' . ($alias !== '' ? $alias : 'item');
	}

	private function getIdByAlias(string $type, string $segment, int $projectId = 0): int
	{
		if (preg_match('/^([0-9]+)(?:-|:|$)/', $segment, $matches)) {
			return (int) $matches[1];
		}

		$alias = $this->normaliseSegment($segment);

		return match ($type) {
			'project' => $this->lookupId('#__joomleague_project', $alias),
			'club' => $this->lookupId('#__joomleague_club', $alias),
			'playground' => $this->lookupId('#__joomleague_playground', $alias),
			'projectteam' => $this->lookupProjectTeamId($alias, $projectId),
			'match' => $this->lookupMatchId($alias, $projectId),
			'person' => $this->lookupId('#__joomleague_person', $alias),
			default => 0,
		};
	}

	private function lookupAlias(string $table, int $id, string $aliasColumn, string $fallbackColumn): string
	{
		$query = $this->db->getQuery(true)
			->select([
				$this->db->quoteName($aliasColumn, 'alias'),
				$this->db->quoteName($fallbackColumn, 'name'),
			])
			->from($this->db->quoteName($table))
			->where($this->db->quoteName('id') . ' = ' . (int) $id);

		$row = $this->db->setQuery($query, 0, 1)->loadObject();

		if (!$row) {
			return '';
		}

		return $this->normaliseSegment((string) ($row->alias ?: $row->name));
	}

	private function lookupId(string $table, string $alias): int
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('id'))
			->from($this->db->quoteName($table))
			->where($this->db->quoteName('alias') . ' = ' . $this->db->quote($alias));

		return (int) $this->db->setQuery($query, 0, 1)->loadResult();
	}

	private function lookupProjectTeamAlias(int $id): string
	{
		$query = $this->db->getQuery(true)
			->select([
				$this->db->quoteName('t.alias', 'alias'),
				$this->db->quoteName('t.name', 'name'),
			])
			->from($this->db->quoteName('#__joomleague_project_team', 'pt'))
			->join('INNER', $this->db->quoteName('#__joomleague_team', 't') . ' ON ' . $this->db->quoteName('t.id') . ' = ' . $this->db->quoteName('pt.team_id'))
			->where($this->db->quoteName('pt.id') . ' = ' . (int) $id);

		$row = $this->db->setQuery($query, 0, 1)->loadObject();

		return $row ? $this->normaliseSegment((string) ($row->alias ?: $row->name)) : '';
	}

	private function lookupProjectTeamId(string $alias, int $projectId = 0): int
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('pt.id'))
			->from($this->db->quoteName('#__joomleague_project_team', 'pt'))
			->join('INNER', $this->db->quoteName('#__joomleague_team', 't') . ' ON ' . $this->db->quoteName('t.id') . ' = ' . $this->db->quoteName('pt.team_id'))
			->where('(' . $this->db->quoteName('t.alias') . ' = ' . $this->db->quote($alias) . ' OR ' . $this->db->quoteName('t.name') . ' = ' . $this->db->quote($alias) . ')');

		if ($projectId > 0) {
			$query->where($this->db->quoteName('pt.project_id') . ' = ' . (int) $projectId);
		}

		$query->order($this->db->quoteName('pt.id') . ' DESC');

		return (int) $this->db->setQuery($query, 0, 1)->loadResult();
	}

	private function lookupProjectIdByProjectTeam(int $projectTeamId): int
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('project_id'))
			->from($this->db->quoteName('#__joomleague_project_team'))
			->where($this->db->quoteName('id') . ' = ' . (int) $projectTeamId);

		return (int) $this->db->setQuery($query, 0, 1)->loadResult();
	}

	private function lookupProjectIdFromProjectSectionQuery(string $view, array $query): int
	{
		foreach (['projectteam_id', 'ptid', 'projectteam1_id', 'tid1', 'projectteam2_id', 'tid2'] as $key) {
			$projectTeamId = (int) ($query[$key] ?? 0);

			if ($projectTeamId > 0) {
				$projectId = $this->lookupProjectIdByProjectTeam($projectTeamId);

				if ($projectId > 0) {
					return $projectId;
				}
			}
		}

		return 0;
	}

	private function lookupProjectIdByMatch(int $matchId): int
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('r.project_id'))
			->from($this->db->quoteName('#__joomleague_match', 'm'))
			->join('INNER', $this->db->quoteName('#__joomleague_round', 'r') . ' ON ' . $this->db->quoteName('r.id') . ' = ' . $this->db->quoteName('m.round_id'))
			->where($this->db->quoteName('m.id') . ' = ' . (int) $matchId);

		return (int) $this->db->setQuery($query, 0, 1)->loadResult();
	}

	private function lookupMatchAlias(int $id): string
	{
		$query = $this->db->getQuery(true)
			->select([
				$this->db->quoteName('m.match_date'),
				$this->db->quoteName('ht.name', 'home_name'),
				$this->db->quoteName('at.name', 'away_name'),
			])
			->from($this->db->quoteName('#__joomleague_match', 'm'))
			->join('LEFT', $this->db->quoteName('#__joomleague_project_team', 'home') . ' ON ' . $this->db->quoteName('home.id') . ' = ' . $this->db->quoteName('m.projectteam1_id'))
			->join('LEFT', $this->db->quoteName('#__joomleague_project_team', 'away') . ' ON ' . $this->db->quoteName('away.id') . ' = ' . $this->db->quoteName('m.projectteam2_id'))
			->join('LEFT', $this->db->quoteName('#__joomleague_team', 'ht') . ' ON ' . $this->db->quoteName('ht.id') . ' = ' . $this->db->quoteName('home.team_id'))
			->join('LEFT', $this->db->quoteName('#__joomleague_team', 'at') . ' ON ' . $this->db->quoteName('at.id') . ' = ' . $this->db->quoteName('away.team_id'))
			->where($this->db->quoteName('m.id') . ' = ' . (int) $id);

		$row = $this->db->setQuery($query, 0, 1)->loadObject();

		if (!$row) {
			return '';
		}

		return $this->normaliseSegment(trim((string) $row->home_name . '-' . (string) $row->away_name . '-' . substr((string) $row->match_date, 0, 10), '-'));
	}

	private function lookupMatchId(string $alias, int $projectId = 0): int
	{
		return 0;
	}

	private function lookupPersonAlias(int $id): string
	{
		$query = $this->db->getQuery(true)
			->select([
				$this->db->quoteName('alias'),
				$this->db->quoteName('firstname'),
				$this->db->quoteName('lastname'),
			])
			->from($this->db->quoteName('#__joomleague_person'))
			->where($this->db->quoteName('id') . ' = ' . (int) $id);

		$row = $this->db->setQuery($query, 0, 1)->loadObject();

		if (!$row) {
			return '';
		}

		return $this->normaliseSegment((string) ($row->alias ?: trim((string) $row->firstname . ' ' . (string) $row->lastname)));
	}

	private function lookupProjectIdByPerson(int $personId): int
	{
		$personId = (int) $personId;

		if ($personId < 1) {
			return 0;
		}

		$query = '
			SELECT project_id FROM (
				SELECT pt.project_id, tp.id AS row_id
				FROM ' . $this->db->quoteName('#__joomleague_team_player') . ' tp
				INNER JOIN ' . $this->db->quoteName('#__joomleague_project_team') . ' pt ON pt.id = tp.projectteam_id
				WHERE tp.person_id = ' . $personId . '
				UNION ALL
				SELECT pt.project_id, ts.id AS row_id
				FROM ' . $this->db->quoteName('#__joomleague_team_staff') . ' ts
				INNER JOIN ' . $this->db->quoteName('#__joomleague_project_team') . ' pt ON pt.id = ts.projectteam_id
				WHERE ts.person_id = ' . $personId . '
				UNION ALL
				SELECT pr.project_id, pr.id AS row_id
				FROM ' . $this->db->quoteName('#__joomleague_project_referee') . ' pr
				WHERE pr.person_id = ' . $personId . '
			) x
			ORDER BY project_id DESC, row_id DESC';

		return (int) $this->db->setQuery($query, 0, 1)->loadResult();
	}

	private function segment(string $constant, string $fallback): string
	{
		$text = Text::_($constant);

		if ($text === $constant || trim($text) === '') {
			$text = $fallback;
		}

		return $this->normaliseSegment($text);
	}

	private function defaultSectionSegment(string $section): string
	{
		return match ($section) {
			'ranking' => 'tabulka',
			'results' => 'vysledky',
			'schedule' => 'rozpis',
			'teams' => 'tymy',
			'referees' => 'rozhodci',
			'stats' => 'statistiky',
			'resultsmatrix' => 'matice-vysledku',
			'resultsranking' => 'vysledky-tabulka',
			'statsranking' => 'poradi-statistik',
			'eventsranking' => 'poradi-udalosti',
			'curve' => 'krivka-poradi',
			'nextmatch' => 'nejblizsi-zapas',
			'ical' => 'ical',
			'prediction' => 'tipovaci-soutez',
			'treetonode' => 'turnajovy-strom',
			'raceresults' => 'vysledky-behu',
			default => $section,
		};
	}

	private function viewByProjectSection(string $section): string
	{
		$section = $this->normaliseSegment($section);

		foreach (self::PROJECT_SECTION_VIEWS as $view => $routeSection) {
			if ($routeSection === '') {
				continue;
			}

			if (\in_array($section, $this->projectSectionRouteAliases($routeSection), true)) {
				return $view;
			}
		}

		return 'project';
	}

	/**
	 * Accept translated route constants plus stable canonical aliases when parsing SEF URLs.
	 */
	private function projectSectionRouteAliases(string $section): array
	{
		$aliases = [
			$this->segment('COM_JOOMLEAGUE_ROUTE_' . strtoupper($section), $this->defaultSectionSegment($section)),
			$this->normaliseSegment($this->defaultSectionSegment($section)),
			$this->normaliseSegment($section),
		];

		foreach (self::PROJECT_SECTION_ROUTE_ALIASES[$section] ?? [] as $alias) {
			$aliases[] = $this->normaliseSegment($alias);
		}

		return array_values(array_unique(array_filter($aliases)));
	}

	private function routeMatches(string $segment, string $constant, string $fallback, array $aliases = []): bool
	{
		$segment = $this->normaliseSegment($segment);
		$valid = [
			$this->segment($constant, $fallback),
			$this->normaliseSegment($fallback),
		];

		foreach ($aliases as $alias) {
			$valid[] = $this->normaliseSegment((string) $alias);
		}

		return \in_array($segment, array_values(array_unique(array_filter($valid))), true);
	}

	private function normaliseSegment(string $value): string
	{
		$value = trim($value);

		if ($value === '') {
			return '';
		}

		$value = Uri::getInstance('index.php?tmp=' . rawurlencode($value))->getVar('tmp', $value);
		$value = strtolower(\Joomla\CMS\Filter\OutputFilter::stringURLSafe($value));

		return $value !== '' ? $value : 'item';
	}
}
