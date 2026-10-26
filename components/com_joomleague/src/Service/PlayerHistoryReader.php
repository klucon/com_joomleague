<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Site\Service;
defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\PlayerParticipation;
use Joomleague\Component\Joomleague\Domain\Service\ProgrammeReader;
use Joomleague\Component\Joomleague\Domain\Service\StandingsDecimal;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectRuleConfigRepository;

/** Sport-neutral player history. Reads published, authorised final appearances only. */
final class PlayerHistoryReader
{
	public function __construct(private readonly DatabaseInterface $database) {}

	public function forPerson(int $personId, array $viewLevels): array
	{
		$levels = array_values(array_filter(array_unique(array_map('intval', $viewLevels)), static fn(int $id): bool => $id > 0));
		if ($personId < 1 || $levels === []) return ['history' => [], 'profiles' => [], 'projects' => []];
		$db = $this->database;
		$access = static fn(string $alias): string => $db->quoteName($alias . '.access') . ' IN (' . implode(',', $levels) . ')';
		$query = $db->getQuery(true)->select([
			'member.*', 'item.project_id', 'item.scheduled_start', 'item.timezone', 'item.duration_minutes',
			'project.name AS project_name', 'project.metadata_json AS project_metadata', 'season.name AS season_name',
			'version.payload_json', 'participant.project_entry_id AS entry_id',
		])->from($db->quoteName('#__joomleague_match_lineup_member', 'member'))
			->innerJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id=member.person_id AND person.published=1')
			->innerJoin($db->quoteName('#__joomleague_project_match', 'item') . ' ON item.id=member.match_id AND item.published=1')
			->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id=item.project_id AND project.published=1')
			->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id=project.competition_id AND competition.published=1')
			->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id=project.season_id AND season.published=1')
			->innerJoin($db->quoteName('#__joomleague_sport_type', 'sport') . ' ON sport.id=project.sport_type_id AND sport.published=1')
			->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id=project.profile_version_id')
			->innerJoin($db->quoteName('#__joomleague_project_stage', 'stage') . ' ON stage.id=item.stage_id AND stage.project_id=project.id AND stage.published=1')
			->innerJoin($db->quoteName('#__joomleague_project_round', 'round') . ' ON round.id=item.round_id AND round.project_id=project.id AND round.published=1')
			->innerJoin($db->quoteName('#__joomleague_match_result', 'result') . " ON result.match_id=item.id AND result.status_code='final' AND item.status_code <> 'cancelled'")
			->innerJoin($db->quoteName('#__joomleague_match_participant', 'participant') . ' ON participant.id=member.match_participant_id AND participant.match_id=item.id AND participant.published=1')
			->innerJoin($db->quoteName('#__joomleague_project_entry', 'entry') . ' ON entry.id=participant.project_entry_id AND entry.project_id=project.id AND entry.published=1')
			->leftJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id=entry.team_id AND team.published=1 AND ' . $access('team'))
			->leftJoin($db->quoteName('#__joomleague_person', 'entry_person') . ' ON entry_person.id=entry.person_id AND entry_person.published=1 AND ' . $access('entry_person'))
			->where('member.person_id=:person')->where('member.published=1')->where("member.member_person_type='player'")
			->where($access('person'))->where($access('project'))->where($access('competition'))->where($access('season'))
			->where("(entry.entry_kind='group' OR (entry.entry_kind='team' AND team.id IS NOT NULL) OR (entry.entry_kind='person' AND entry_person.id IS NOT NULL))")
			->bind(':person', $personId, ParameterType::INTEGER)->order('item.scheduled_start DESC,item.id DESC,member.id ASC');
		$members = $db->setQuery($query)->loadObjectList();
		if ($members === []) return ['history' => [], 'profiles' => [], 'projects' => []];
		$matchIds = array_values(array_unique(array_map(static fn(object $row): int => (int) $row->match_id, $members)));
		$lineups = []; $changes = []; $events = []; $statistics = [];
		foreach (array_chunk($matchIds, 300) as $ids) {
			foreach ($db->setQuery($db->getQuery(true)->select('lineup.*')->from($db->quoteName('#__joomleague_match_lineup_member', 'lineup'))->whereIn('lineup.match_id', $ids, ParameterType::INTEGER)->where('lineup.published=1'))->loadObjectList() as $row) $lineups[(int) $row->match_participant_id][] = $row;
			foreach ($db->setQuery($db->getQuery(true)->select('lineup_change.*')->from($db->quoteName('#__joomleague_match_lineup_change', 'lineup_change'))->whereIn('lineup_change.match_id', $ids, ParameterType::INTEGER)->order('lineup_change.sequence_number ASC,lineup_change.id ASC'))->loadObjectList() as $row) $changes[(int) $row->match_participant_id][] = $row;
			foreach ($db->setQuery($db->getQuery(true)->select('event.*')->from($db->quoteName('#__joomleague_match_event', 'event'))->whereIn('event.match_id', $ids, ParameterType::INTEGER)->where('event.primary_person_id=:person')->where('event.published=1')->bind(':person', $personId, ParameterType::INTEGER))->loadObjectList() as $row) $events[(int) $row->match_id][] = $row;
			foreach ($db->setQuery($db->getQuery(true)->select('value.*')->from($db->quoteName('#__joomleague_match_statistic_value', 'value'))->whereIn('value.match_id', $ids, ParameterType::INTEGER)->where('value.person_id=:person')->where('value.published=1')->where('value.segment_key=0')->where("value.target_kind='person'")->bind(':person', $personId, ParameterType::INTEGER))->loadObjectList() as $row) $statistics[(int) $row->match_id][] = $row;
		}
		$profiles = []; $programme = []; $profileCache = []; $projects = []; $history = [];
		foreach ($members as $member) {
			$projectId = (int) $member->project_id; $matchId = (int) $member->match_id; $participantId = (int) $member->match_participant_id;
			if (!isset($profileCache[$projectId])) {
				$profileCache[$projectId] = (new ProjectRuleConfigRepository($db))->resolve($projectId, json_decode((string) $member->payload_json, true, 512, JSON_THROW_ON_ERROR));
				foreach ((new ProgrammeReader($db))->forProject($projectId, null, $levels) as $item) $programme[(int) $item['id']] = $item;
			}
			$profile = $profileCache[$projectId]; $item = $programme[$matchId] ?? null;
			if ($item === null) continue;
			$participant = null;
			foreach ($item['participants'] as $candidate) if ($candidate['participant_id'] === $participantId) $participant = $candidate;
			if ($participant === null) continue;
			$structure = $profile['match']['structure'] ?? [];
			$duration = ($structure['type'] ?? '') === 'timed_periods' && isset($structure['period_count'], $structure['period_length_minutes']) ? (float) $structure['period_count'] * (float) $structure['period_length_minutes'] : null;
			$legacyProject = json_decode((string) $member->project_metadata, true)['legacy'] ?? [];
			$legacyMember = json_decode((string) $member->metadata_json, true)['legacy_source_table'] ?? null;
			if ($legacyMember === 'match_player' && isset($legacyProject['game_regular_time']) && is_numeric($legacyProject['game_regular_time'])) $duration = (float) $legacyProject['game_regular_time'];
			$dismissal = null;
			foreach ($profile['event_types'] ?? [] as $definition) {
				if (empty($definition['leads_to_suspension'])) continue;
				foreach ($events[$matchId] ?? [] as $event) {
					if ($event->event_code !== $definition['code'] || ($event->match_participant_id !== null && (int) $event->match_participant_id !== $participantId)) continue;
					$clock = PlayerParticipation::clock($event->clock_value, $event->clock_unit);
					if ($clock === null || ($event->phase_sequence !== null && (int) $event->phase_sequence > 1)) { $duration = null; continue; }
					$dismissal = $dismissal === null ? $clock : min($dismissal, $clock);
				}
			}
			$participation = PlayerParticipation::calculate($member, $lineups[$participantId] ?? [], $changes[$participantId] ?? [], $duration, $dismissal);
			if (!$participation['played']) continue;
			$profileCode = (string) $profile['code'];
			$profiles[$profileCode] ??= ['name_key' => (string) $profile['name_key'], 'columns' => [], 'projects' => [], 'totals' => self::emptyTotals()];
			$values = [];
			foreach ($profile['statistics'] ?? [] as $definition) {
				if (($definition['scope'] ?? '') !== 'player' || !in_array($definition['source'] ?? '', ['event', 'manual_or_import'], true)) continue;
				$code = (string) $definition['code']; $value = null;
				if ($definition['source'] === 'event') {
					$value = '0';
					foreach ($events[$matchId] ?? [] as $event) if (($event->match_participant_id === null || (int) $event->match_participant_id === $participantId) && in_array($event->event_code, $definition['event_codes'] ?? [], true)) $value = StandingsDecimal::add($value, $event->numeric_value !== null ? (string) $event->numeric_value : '1');
				}
				foreach ($statistics[$matchId] ?? [] as $statistic) if ((int) $statistic->match_participant_id === $participantId && $statistic->statistic_code === $code && $statistic->numeric_value !== null) $value = (string) $statistic->numeric_value;
				if ($value === null) continue;
				$values[$code] = $value;
				$profiles[$profileCode]['columns'][$code] = ['name_key' => (string) $definition['name_key'], 'abbreviation_key' => (string) ($definition['abbreviation_key'] ?? $definition['name_key']), 'additive' => $definition['source'] === 'event' || ($definition['aggregation'] ?? '') === 'sum' || in_array($definition['value_type'] ?? 'integer', ['integer', 'duration'], true)];
			}
			if (isset($values['minutes_played'])) $participation['minutes'] = (float) $values['minutes_played'];
			$key = $projectId . ':' . (int) $member->entry_id;
			$summary = &$profiles[$profileCode]['projects'][$key];
			$summary ??= ['project_id' => $projectId, 'entry_id' => (int) $member->entry_id, 'project_name' => (string) $member->project_name, 'season_name' => (string) $member->season_name, 'team_name' => $participant['name'], 'totals' => self::emptyTotals()];
			$additive = array_filter($values, static fn(string $code): bool => $profiles[$profileCode]['columns'][$code]['additive'], ARRAY_FILTER_USE_KEY);
			self::add($summary['totals'], $participation, $additive);
			self::add($profiles[$profileCode]['totals'], $participation, $additive);
			unset($summary);
			$projects[$projectId] = (string) $member->project_name . ' · ' . (string) $member->season_name;
			$history[] = ['match_id' => $matchId, 'project_id' => $projectId, 'entry_id' => (int) $member->entry_id, 'project_name' => (string) $member->project_name, 'season_name' => (string) $member->season_name, 'date' => $member->scheduled_start, 'timezone' => $member->timezone ?: 'UTC', 'participants' => $item['participants'], 'profile_code' => $profileCode, 'participation' => $participation, 'values' => $values];
		}
		foreach ($profiles as &$profile) uasort($profile['projects'], static fn(array $a, array $b): int => strnatcasecmp($b['season_name'], $a['season_name']) ?: strnatcasecmp($a['team_name'], $b['team_name']));
		unset($profile);
		return compact('history', 'profiles', 'projects');
	}

	/** Filter already authorised summaries without changing the full project selector. */
	public static function profilesForProject(array $profiles, int $projectId): array
	{
		if ($projectId === 0) return $profiles;
		$result = [];
		foreach ($profiles as $code => $profile) {
			$profile['projects'] = array_filter($profile['projects'], static fn(array $row): bool => $row['project_id'] === $projectId);
			if ($profile['projects'] === []) continue;
			$totals = self::emptyTotals();
			foreach ($profile['projects'] as $row) {
				foreach (['played', 'started', 'in', 'out', 'minutes', 'unknown_minutes'] as $metric) $totals[$metric] += $row['totals'][$metric];
				foreach ($row['totals']['values'] as $metric => $value) $totals['values'][$metric] = StandingsDecimal::add($totals['values'][$metric] ?? '0', $value);
			}
			$profile['totals'] = $totals;
			$result[$code] = $profile;
		}
		return $result;
	}

	private static function emptyTotals(): array { return ['played' => 0, 'started' => 0, 'in' => 0, 'out' => 0, 'minutes' => 0.0, 'unknown_minutes' => 0, 'values' => []]; }
	private static function add(array &$totals, array $participation, array $values): void
	{
		$totals['played']++;
		foreach (['started', 'in', 'out'] as $key) $totals[$key] += $participation[$key];
		if ($participation['minutes'] === null) $totals['unknown_minutes']++; else $totals['minutes'] += $participation['minutes'];
		foreach ($values as $code => $value) $totals['values'][$code] = StandingsDecimal::add($totals['values'][$code] ?? '0', $value);
	}
}
