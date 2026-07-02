<?php

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
	];

	private const VIEW_ID_KEYS = [
		'project' => 'project_id',
		'ranking' => 'project_id',
		'results' => 'project_id',
		'schedule' => 'project_id',
		'teams' => 'project_id',
		'referees' => 'project_id',
		'stats' => 'project_id',
		'club' => 'id',
		'team' => 'id',
		'roster' => 'id',
		'matchreport' => 'id',
		'person' => 'id',
		'playground' => 'id',
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

		$item = $this->findExactMenuItem($query);

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

			if ($projectId > 0) {
				if ($this->activeMenuView($query) !== 'projects') {
					$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_COMPETITIONS', 'souteze');
				}

				$segments[] = $this->getAlias('project', $projectId);

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
				if ($this->activeMenuView($query) !== 'clubs') {
					$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_CLUBS', 'kluby');
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

				unset($query['view'], $query['id'], $query['projectteam_id']);
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
				unset($query['view'], $query['id'], $query['match_id']);
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
				$segments[] = $this->segment('COM_JOOMLEAGUE_ROUTE_PLAYGROUNDS', 'stadiony');
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

		if ($first === $this->segment('COM_JOOMLEAGUE_ROUTE_COMPETITIONS', 'souteze')) {
			if ($segments === []) {
				return ['view' => 'projects'];
			}

			$projectId = $this->getIdByAlias('project', (string) array_shift($segments));

			if ($projectId < 1) {
				return ['view' => 'projects'];
			}

			return $this->parseProjectRoute($projectId, $segments);
		}

		if ($activeView === 'projects') {
			array_unshift($segments, $first);
			$projectId = $this->getIdByAlias('project', (string) array_shift($segments));

			if ($projectId < 1) {
				return ['view' => 'projects'];
			}

			return $this->parseProjectRoute($projectId, $segments);
		}

		if ($first === $this->segment('COM_JOOMLEAGUE_ROUTE_CLUBS', 'kluby')) {
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

		if ($first === $this->segment('COM_JOOMLEAGUE_ROUTE_TEAMS', 'tymy')) {
			$id = $segments !== [] ? $this->getIdByAlias('projectteam', (string) array_shift($segments)) : 0;
			$section = $segments[0] ?? '';

			return [
				'view' => $this->normaliseSegment((string) $section) === $this->segment('COM_JOOMLEAGUE_ROUTE_ROSTER', 'soupiska') ? 'roster' : 'team',
				'id' => $id,
			];
		}

		if ($first === $this->segment('COM_JOOMLEAGUE_ROUTE_MATCHES', 'zapasy')) {
			return [
				'view' => 'matchreport',
				'id' => $segments !== [] ? $this->getIdByAlias('match', (string) array_shift($segments)) : 0,
			];
		}

		if ($first === $this->segment('COM_JOOMLEAGUE_ROUTE_PERSONS', 'osoby')) {
			return [
				'view' => 'person',
				'id' => $segments !== [] ? $this->getIdByAlias('person', (string) array_shift($segments)) : 0,
			];
		}

		if ($first === $this->segment('COM_JOOMLEAGUE_ROUTE_PLAYGROUNDS', 'stadiony')) {
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
			'club' => 'clubs',
			'project', 'ranking', 'results', 'schedule', 'teams', 'team', 'roster', 'referees', 'stats', 'matchreport', 'person' => 'projects',
			default => $view,
		};

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

		return true;
	}

	private function unsetJoomleagueQuery(array &$query): void
	{
		unset($query['view'], $query['project_id'], $query['pid'], $query['id'], $query['club_id'], $query['projectteam_id'], $query['match_id'], $query['playground_id'], $query['person_id']);
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

		if ($section === $this->segment('COM_JOOMLEAGUE_ROUTE_TEAMS', 'tymy')) {
			if ($segments === []) {
				$vars['view'] = 'teams';

				return $vars;
			}

			$vars['id'] = $this->getIdByAlias('projectteam', (string) array_shift($segments), $projectId);
			$next = $segments[0] ?? '';
			$isRoster = $this->normaliseSegment((string) $next) === $this->segment('COM_JOOMLEAGUE_ROUTE_ROSTER', 'soupiska');

			if ($isRoster) {
				array_shift($segments);
			}

			$vars['view'] = $isRoster ? 'roster' : 'team';

			return $vars;
		}

		if ($section === $this->segment('COM_JOOMLEAGUE_ROUTE_MATCHES', 'zapasy')) {
			$vars['view'] = 'matchreport';
			$vars['id'] = $segments !== [] ? $this->getIdByAlias('match', (string) array_shift($segments), $projectId) : 0;

			return $vars;
		}

		if ($section === $this->segment('COM_JOOMLEAGUE_ROUTE_PERSONS', 'osoby')) {
			$vars['view'] = 'person';
			$vars['id'] = $segments !== [] ? $this->getIdByAlias('person', (string) array_shift($segments), $projectId) : 0;

			return $vars;
		}

		$vars['view'] = $this->viewByProjectSection($section);

		return $vars;
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
			default => $section,
		};
	}

	private function viewByProjectSection(string $section): string
	{
		return match ($section) {
			$this->segment('COM_JOOMLEAGUE_ROUTE_RANKING', 'tabulka') => 'ranking',
			$this->segment('COM_JOOMLEAGUE_ROUTE_RESULTS', 'vysledky') => 'results',
			$this->segment('COM_JOOMLEAGUE_ROUTE_SCHEDULE', 'rozpis') => 'schedule',
			$this->segment('COM_JOOMLEAGUE_ROUTE_TEAMS', 'tymy') => 'teams',
			$this->segment('COM_JOOMLEAGUE_ROUTE_REFEREES', 'rozhodci') => 'referees',
			$this->segment('COM_JOOMLEAGUE_ROUTE_STATS', 'statistiky') => 'stats',
			default => 'project',
		};
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
