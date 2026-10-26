<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Administrator\Service\MatchParticipantSummaryProvider;

/**
 * Data source for the JoomLeague dashboard. The dashboard is the first thing
 * anyone sees, including an administrator who has never touched the
 * component before, so this model only answers a few calm questions: "have
 * you set up the basics yet" (the getting-started checklist), "how much is
 * in here" (a neutral count of each domain), and, for installations that
 * have configured a home club, "what does that club have coming up" (a
 * compact shortcut, not a work queue). Nothing here is phrased as a problem
 * or a warning.
 */
final class DashboardModel extends BaseDatabaseModel
{
	public function getInstalledVersion(): string
	{
		$database = $this->getDatabase();
		$extensions = [
			['type' => 'package', 'element' => 'pkg_joomleague'],
			['type' => 'component', 'element' => 'com_joomleague'],
		];

		foreach ($extensions as $extension) {
			$query = $database->getQuery(true)
				->select($database->quoteName('manifest_cache'))
				->from($database->quoteName('#__extensions'))
				->where($database->quoteName('type') . ' = :type')
				->where($database->quoteName('element') . ' = :element')
				->bind(':type', $extension['type'])
				->bind(':element', $extension['element']);
			$manifest = json_decode((string) $database->setQuery($query)->loadResult(), true);

			if (is_array($manifest) && isset($manifest['version']) && trim((string) $manifest['version']) !== '') {
				return trim((string) $manifest['version']);
			}
		}

		return '';
	}

	/** @return array<string,int> */
	public function getOverview(): array
	{
		return [
			'competitions' => $this->countAll('#__joomleague_competition'),
			'projects' => $this->countAll('#__joomleague_project'),
			'clubs' => $this->countAll('#__joomleague_club'),
			'teams' => $this->countAll('#__joomleague_team'),
			'persons' => $this->countAll('#__joomleague_person'),
			'matches' => $this->countAll('#__joomleague_project_match'),
		];
	}

	/**
	 * The club configured as this installation's home club, if any - just
	 * enough to label the club-matches shortcut with its name.
	 *
	 * @return array{id:int,name:string}|null
	 */
	public function getSiteClub(): ?array
	{
		$clubId = $this->getSiteClubId();

		if ($clubId <= 0) {
			return null;
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([$db->quoteName('id'), $db->quoteName('name')])
			->from($db->quoteName('#__joomleague_club'))
			->where($db->quoteName('id') . ' = :clubId')->bind(':clubId', $clubId, ParameterType::INTEGER);
		$query->where('published = 1')->whereIn('access', $this->viewLevels(), ParameterType::INTEGER);
		$club = $db->setQuery($query)->loadAssoc();

		return $club === null ? null : ['id' => (int) $club['id'], 'name' => (string) $club['name']];
	}

	/**
	 * Matches involving the home club's teams, if a home club is configured.
	 * Recently played, dated matches still missing a final result come first (the
	 * most useful shortcut back to unfinished work), then the nearest
	 * upcoming dated matches, capped at 50 and the configured limit. The optional
	 * project selection and bounded result lookback apply to this dashboard only.
	 * Undated records remain in their projects, not in this shortcut. Returns an empty
	 * list whenever there is nothing to show - the caller decides whether to
	 * render the section at all.
	 *
	 * @return list<array{id:int,round_id:int,project_id:int,project_name:string,scheduled_start:?string,status_code:string,played_without_result:bool,home:string,away:string,our_slot:int}>
	 */
	public function getClubMatches(int $limit): array
	{
		$limit = min(50, $limit);
		if ($limit <= 0) {
			return [];
		}

		$clubId = $this->getSiteClubId();

		if ($clubId <= 0 || $this->getSiteClub() === null) {
			return [];
		}

		$entryIds = $this->ownEntryIds($clubId);

		if ($entryIds === []) {
			return [];
		}

		$played = $this->clubMatchCandidates($entryIds, 'played', $limit);
		$remaining = $limit - count($played);
		$upcoming = $remaining > 0 ? $this->clubMatchCandidates($entryIds, 'upcoming', $remaining) : [];
		$matches = array_merge($played, $upcoming);

		if ($matches === []) {
			return [];
		}

		$db = $this->getDatabase();
		$ids = array_map(static fn (object $match): int => (int) $match->id, $matches);
		$participants = (new MatchParticipantSummaryProvider($db))->loadDetails($ids);
		$playedIds = array_map(static fn (object $match): int => (int) $match->id, $played);

		$result = [];

		foreach ($matches as $match) {
			$slots = $participants[(int) $match->id] ?? [];
			$ourSlot = 0;

			foreach ($slots as $slot) {
				if (in_array($slot['entry_id'], $entryIds, true)) {
					$ourSlot = (int) $slot['slot_number'];
					break;
				}
			}

			$result[] = [
				'id' => (int) $match->id,
				'round_id' => (int) $match->round_id,
				'project_id' => (int) $match->project_id,
				'project_name' => (string) $match->project_name,
				'scheduled_start' => $match->scheduled_start,
				'status_code' => (string) $match->status_code,
				'played_without_result' => in_array((int) $match->id, $playedIds, true),
				'home' => $slots[0]['name'] ?? '',
				'away' => $slots[1]['name'] ?? '',
				'our_slot' => $ourSlot,
			];
		}

		return $result;
	}

	private function getSiteClubId(): int
	{
		return (int) ComponentHelper::getParams('com_joomleague')->get('site_club_id', 0);
	}

	/** @return list<int> */
	private function viewLevels(): array
	{
		$levels = Factory::getApplication()->getIdentity()?->getAuthorisedViewLevels() ?? [1];
		return array_values(array_unique(array_filter(array_map('intval', $levels), static fn (int $id): bool => $id > 0))) ?: [1];
	}

	/** @return list<int> */
	private function ownEntryIds(int $clubId): array
	{
		$db = $this->getDatabase();

		$teamQuery = $db->getQuery(true)
			->select($db->quoteName('entry.id'))
			->from($db->quoteName('#__joomleague_project_entry', 'entry'))
			->innerJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id')
			->where($db->quoteName('team.club_id') . ' = :teamClubId')->bind(':teamClubId', $clubId, ParameterType::INTEGER);
		$teamQuery->where('entry.published = 1')->where('team.published = 1')->whereIn('team.access', $this->viewLevels(), ParameterType::INTEGER);
		$teamEntryIds = array_map('intval', $db->setQuery($teamQuery)->loadColumn());

		$personQuery = $db->getQuery(true)
			->select($db->quoteName('entry.id'))
			->from($db->quoteName('#__joomleague_project_entry', 'entry'))
			->innerJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id')
			->where($db->quoteName('person.club_id') . ' = :personClubId')->bind(':personClubId', $clubId, ParameterType::INTEGER);
		$personQuery->where('entry.published = 1')->where('person.published = 1')->whereIn('person.access', $this->viewLevels(), ParameterType::INTEGER);
		$personEntryIds = array_map('intval', $db->setQuery($personQuery)->loadColumn());

		return array_values(array_unique(array_merge($teamEntryIds, $personEntryIds)));
	}

	/**
	 * @param list<int> $entryIds
	 * @return list<object>
	 */
	private function clubMatchCandidates(array $entryIds, string $mode, int $limit): array
	{
		if ($limit <= 0) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select('DISTINCT ' . implode(', ', [
				$db->quoteName('a.id'),
				$db->quoteName('a.round_id'),
				$db->quoteName('a.project_id'),
				$db->quoteName('project.name', 'project_name'),
				$db->quoteName('a.scheduled_start'),
				$db->quoteName('a.status_code'),
			]))
			->from($db->quoteName('#__joomleague_project_match', 'a'))
			->innerJoin($db->quoteName('#__joomleague_match_participant', 'mp') . ' ON mp.match_id = a.id')
			->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = a.project_id')
			->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id')
			->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id')
			->innerJoin($db->quoteName('#__joomleague_project_stage', 'stage') . ' ON stage.id = a.stage_id AND stage.project_id = a.project_id')
			->innerJoin($db->quoteName('#__joomleague_project_round', 'match_round') . ' ON match_round.id = a.round_id AND match_round.project_id = a.project_id AND match_round.stage_id = a.stage_id')
			->whereIn($db->quoteName('mp.project_entry_id'), $entryIds, ParameterType::INTEGER)
			->where($db->quoteName('a.published') . ' = 1')->where('mp.project_id = a.project_id');
		foreach (['project', 'competition', 'season'] as $parent) {
			$query->where($parent . '.published = 1')->whereIn($parent . '.access', $this->viewLevels(), ParameterType::INTEGER);
		}
		$query->where('stage.published = 1')->where('match_round.published = 1');
		// Suppress the whole tile if any participant identity is not visible.
		$levels = implode(',', $this->viewLevels());
		$hidden = $db->getQuery(true)->select('1')
			->from($db->quoteName('#__joomleague_match_participant', 'hidden_mp'))
			->leftJoin($db->quoteName('#__joomleague_project_entry', 'hidden_entry') . ' ON hidden_entry.id = hidden_mp.project_entry_id AND hidden_entry.project_id = a.project_id')
			->leftJoin($db->quoteName('#__joomleague_team', 'hidden_team') . ' ON hidden_team.id = hidden_entry.team_id')
			->leftJoin($db->quoteName('#__joomleague_person', 'hidden_person') . ' ON hidden_person.id = hidden_entry.person_id')
			->leftJoin($db->quoteName('#__joomleague_club', 'hidden_club') . ' ON hidden_club.id = COALESCE(hidden_team.club_id, hidden_person.club_id)')
			->where('hidden_mp.match_id = a.id')
			->where('(hidden_entry.id IS NULL OR hidden_entry.published <> 1 OR hidden_mp.published <> 1'
				. ' OR (hidden_entry.team_id IS NOT NULL AND (hidden_team.id IS NULL OR hidden_team.published <> 1 OR hidden_team.access NOT IN (' . $levels . ')))'
				. ' OR (hidden_entry.person_id IS NOT NULL AND (hidden_person.id IS NULL OR hidden_person.published <> 1 OR hidden_person.access NOT IN (' . $levels . ')))'
				. ' OR (hidden_club.id IS NOT NULL AND (hidden_club.published <> 1 OR hidden_club.access NOT IN (' . $levels . '))))');
		$query->where('NOT EXISTS (' . $hidden . ')');
		$params = ComponentHelper::getParams('com_joomleague');
		$selected = $params->get('dashboard_project_ids', []);
		if ($selected === null || $selected === '') $selected = [];
		$selected = is_array($selected) ? $selected : [$selected];
		$projectIds = [];
		foreach ($selected as $value) {
			if (is_scalar($value) && ctype_digit((string) $value) && (int) $value > 0) $projectIds[] = (int) $value;
		}
		if ($selected !== [] && $projectIds === []) return [];
		if ($projectIds !== []) $query->whereIn('a.project_id', array_values(array_unique($projectIds)), ParameterType::INTEGER);
		$now = Factory::getDate()->toSql();
		$query->where('a.scheduled_start IS NOT NULL');

		if ($mode === 'played') {
			$days = max(0, min(365, (int) $params->get('dashboard_result_lookback_days', 30)));
			if ($days === 0) return [];
			$since = Factory::getDate('-' . $days . ' days')->toSql();
			$query->where($db->quoteName('a.status_code') . ' = ' . $db->quote('completed'))
				->where('a.scheduled_start >= :since')->bind(':since', $since)
				->where('a.scheduled_start <= :now')->bind(':now', $now)
				->where('NOT EXISTS (SELECT 1 FROM ' . $db->quoteName('#__joomleague_match_result') . ' AS mr WHERE mr.match_id = a.id AND mr.status_code = ' . $db->quote('final') . ')')
				->order($db->quoteName('a.scheduled_start') . ' DESC')->order('a.id DESC');
		} else {
			$query->where($db->quoteName('a.status_code') . ' = ' . $db->quote('scheduled'))
				->where('a.scheduled_start >= :now')->bind(':now', $now)
				->order($db->quoteName('a.scheduled_start') . ' ASC')->order('a.id ASC');
		}

		return $db->setQuery($query, 0, $limit)->loadObjectList();
	}

	private function countAll(string $table): int
	{
		$query = $this->getDatabase()->getQuery(true)->select('COUNT(*)')->from($this->getDatabase()->quoteName($table));

		return (int) $this->getDatabase()->setQuery($query)->loadResult();
	}

}
