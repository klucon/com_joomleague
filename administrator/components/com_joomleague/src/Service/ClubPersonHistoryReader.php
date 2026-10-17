<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Builds a club-wide historical person directory from memberships and actual line-ups. */
final class ClubPersonHistoryReader
{
	public function __construct(private readonly DatabaseInterface $database) {}

	/** @param list<int> $viewLevels @return array<string,mixed> */
	public function read(int $clubId, array $filters, array $viewLevels): array
	{
		if ($clubId < 1) return ['error' => 'club_required'];
		$db = $this->database;
		$levels = array_values(array_unique(array_filter(array_map('intval', $viewLevels), static fn (int $id): bool => $id > 0))) ?: [1];
		$access = implode(',', $levels);
		$club = $db->setQuery($db->getQuery(true)->select(['id', 'name', 'short_name', 'logo'])->from($db->quoteName('#__joomleague_club'))->where('id = ' . $clubId)->where('published = 1')->where('access IN (' . $access . ')'))->loadObject();
		if (!$club) return ['error' => 'club_unavailable'];
		$normalized = [
			'project_id' => max(0, (int) ($filters['project_id'] ?? 0)),
			'season_id' => max(0, (int) ($filters['season_id'] ?? 0)),
			'project_type' => trim((string) ($filters['project_type'] ?? '')),
			'person_type' => trim((string) ($filters['person_type'] ?? '')),
		];
		$membership = $this->sql($this->membershipQuery($clubId, $access, $normalized));
		$lineup = $this->sql($this->lineupQuery($clubId, $access, $normalized));
		$events = $this->sql($this->eventQuery($clubId, $access, $normalized));
		$typeList = $db->getName() === 'pgsql'
			? "STRING_AGG(DISTINCT member_person_type, ' ' ORDER BY member_person_type)"
			: "GROUP_CONCAT(DISTINCT member_person_type ORDER BY member_person_type SEPARATOR ' ')";
		// Aggregate historical rows on the database server before selecting a person page.
		$with = 'WITH members AS (' . $membership . '), appearances AS (' . $lineup . '), history AS ('
			. 'SELECT person_id, first_name, last_name, nickname, picture, member_person_type, role_code, project_id, project_name, season_id, season_name,'
			. ' COALESCE(LEAST(valid_from,valid_until),valid_from,valid_until) AS first_date,'
			. ' COALESCE(GREATEST(valid_from,valid_until),valid_from,valid_until) AS last_date, NULL AS match_id FROM members UNION ALL '
			. 'SELECT person_id,first_name,last_name,nickname,picture,member_person_type,role_code,project_id,project_name,season_id,season_name,'
			. ' CAST(scheduled_start AS DATE),CAST(scheduled_start AS DATE),match_id FROM appearances),'
			. ' people AS (SELECT person_id,MIN(first_name) AS first_name,MIN(last_name) AS last_name,MIN(nickname) AS nickname,MIN(picture) AS picture,'
			. $typeList . ' AS type_sort,COUNT(DISTINCT match_id) AS appearance_count,MIN(first_date) AS first_date,MAX(last_date) AS last_date FROM history GROUP BY person_id),'
			. ' events AS (' . $events . ')';
		$metricLabels = [];
		$metricMetadata = [];
		$requestedSort = (string) ($filters['sort'] ?? 'name');
		$metric = str_starts_with($requestedSort, 'metric_') ? substr($requestedSort, 7) : '';
		foreach ($db->setQuery($with . ' SELECT DISTINCT e.profile_metadata_json,e.event_name_key FROM events e INNER JOIN people p ON p.person_id=e.person_id')->getIterator() as $definition) {
			$code = $this->metricCode((string) $definition->profile_metadata_json);
			if ($code === '') continue;
			$metricLabels[$code] ??= (string) $definition->event_name_key;
			if ($code === $metric) $metricMetadata[(string) $definition->profile_metadata_json] = true;
		}
		$manager = $this->managerQuery($clubId, $access, $normalized);
		$versions = clone $manager;
		$versionIds = array_map('intval', $db->setQuery($versions->clear('select')->select('DISTINCT profile.id'))->loadColumn());
		$conditions = [];
		$lowerWins = [];
		if ($versionIds !== []) {
			foreach ($db->setQuery($db->getQuery(true)->select(['id', 'payload_json'])->from($db->quoteName('#__joomleague_sport_profile_version'))->whereIn('id', $versionIds, ParameterType::INTEGER))->getIterator() as $version) {
				$profile = json_decode((string) $version->payload_json, true);
				$roles = [];
				foreach ($profile['positions'] ?? [] as $position) {
					if (is_array($position) && ($position['tracks_team_record'] ?? false) === true && is_string($position['code'] ?? null)) $roles[] = $position['code'];
				}
				if ($roles === []) continue;
				$conditions[] = '(profile.id = ' . (int) $version->id . ' AND lineup.role_code IN (' . implode(',', $db->quote($roles)) . '))';
				if (!(bool) ($profile['match']['score']['higher_is_better'] ?? true)) $lowerWins[] = (int) $version->id;
			}
		}
		$manager->where($conditions === [] ? '1 = 0' : '(' . implode(' OR ', $conditions) . ')');
		$manager->select('COUNT(*) OVER (PARTITION BY lineup.person_id, match.id) AS ambiguity_count');
		$direction = $lowerWins === [] ? '1' : '(CASE WHEN profile_version_id IN (' . implode(',', $lowerWins) . ') THEN -1 ELSE 1 END)';
		$outcome = 'CASE WHEN own_rank IS NOT NULL AND opponent_rank IS NOT NULL THEN CASE WHEN own_rank=opponent_rank THEN 0 WHEN own_rank<opponent_rank THEN 1 ELSE -1 END'
			. ' WHEN own_value IS NULL OR opponent_value IS NULL THEN NULL WHEN own_value=opponent_value THEN 0 WHEN own_value>opponent_value THEN ' . $direction . ' ELSE -1*' . $direction . ' END';
		$with .= ', manager_raw AS (' . $this->sql($manager) . '), manager_matches AS (SELECT manager_raw.*,' . $outcome . ' AS outcome FROM manager_raw WHERE ambiguity_count=1),'
			. ' records AS (SELECT person_id,COUNT(*) AS played,SUM(CASE WHEN outcome=1 THEN 1 ELSE 0 END) AS wins,'
			. ' SUM(CASE WHEN outcome=0 THEN 1 ELSE 0 END) AS draws,SUM(CASE WHEN outcome=-1 THEN 1 ELSE 0 END) AS losses,'
			. ' SUM(CASE WHEN own_value IS NOT NULL AND opponent_value IS NOT NULL THEN own_value ELSE 0 END) AS scored,'
			. ' SUM(CASE WHEN own_value IS NOT NULL AND opponent_value IS NOT NULL THEN opponent_value ELSE 0 END) AS conceded'
			. ' FROM manager_matches WHERE outcome IS NOT NULL GROUP BY person_id)';
		$metricSort = $metricMetadata === [] ? '0' : '(SELECT COUNT(*) FROM events e WHERE e.person_id=p.person_id AND e.profile_metadata_json IN (' . implode(',', $db->quote(array_keys($metricMetadata))) . '))';
		$staff = !empty($filters['staff_only']);
		$columns = $staff
			? ['name' => 'name', 'played' => 'COALESCE(r.played,0)', 'wins' => 'COALESCE(r.wins,0)', 'draws' => 'COALESCE(r.draws,0)', 'losses' => 'COALESCE(r.losses,0)', 'score' => '(COALESCE(r.scored,0)-COALESCE(r.conceded,0))', 'period' => "COALESCE(CAST(p.first_date AS CHAR(10)),'')"]
			: ['name' => 'name', 'type' => 'p.type_sort', 'appearances' => 'p.appearance_count', 'manager_record' => 'COALESCE(r.played,-1)', 'period' => "COALESCE(CAST(p.first_date AS CHAR(10)),'')"];
		if ($metric !== '' && isset($metricLabels[$metric]) && !$staff) $columns[$requestedSort] = $metricSort;
		$sort = isset($columns[$requestedSort]) ? $requestedSort : 'name';
		$sortDirection = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
		$search = trim((string) ($filters['search'] ?? ''));
		$from = ' FROM people p LEFT JOIN records r ON r.person_id=p.person_id WHERE 1=1';
		if ($staff) $from .= ' AND r.played>0';
		if ($search !== '') {
			$pattern = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($search)) . '%';
			$from .= ' AND LOWER(CONCAT(p.last_name,\' \',p.first_name,\' \',COALESCE(p.nickname,\'\'))) LIKE ' . $db->quote($pattern) . " ESCAPE '!'";
		}
		$total = (int) $db->setQuery($with . ' SELECT COUNT(*)' . $from)->loadResult();
		$requestedLimit = (int) ($filters['limit'] ?? 25);
		if (!in_array($requestedLimit, [0,10,25,50,100], true)) $requestedLimit = 25;
		$limit = $requestedLimit === 0 ? max(1, min(1000, $total)) : $requestedLimit;
		$start = min(max(0, (int) ($filters['start'] ?? 0)), max(0, (int) (floor(max(0, $total-1)/$limit)*$limit)));
		$nameOrder = 'LOWER(p.last_name),LOWER(p.first_name),p.person_id';
		$order = $sort === 'name' ? 'LOWER(p.last_name) ' . $sortDirection . ',LOWER(p.first_name) ' . $sortDirection . ',p.person_id'
			: $columns[$sort] . ' ' . $sortDirection . ',' . $nameOrder;
		$rows = $db->setQuery($with . ' SELECT p.*,r.played,r.wins,r.draws,r.losses,r.scored,r.conceded' . $from . ' ORDER BY ' . $order, $start, $limit)->loadObjectList();
		$people = [];
		foreach ($rows as $row) {
			$person =& $this->person($people, $row);
			$person['appearances'] = (int) $row->appearance_count;
			$person['first_date'] = $row->first_date;
			$person['last_date'] = $row->last_date;
			if ($row->played !== null) {
				$person['manager_record'] = ['played'=>(int)$row->played,'wins'=>(int)$row->wins,'draws'=>(int)$row->draws,'losses'=>(int)$row->losses,'scored'=>StandingsDecimal::normalize((string)$row->scored),'conceded'=>StandingsDecimal::normalize((string)$row->conceded),'matches'=>[]];
			}
			unset($person);
		}
		$ids = array_keys($people);
		if ($ids !== []) {
			// Only metadata for the selected people is hydrated; appearances remain SQL counts.
			$metadataSql = $with . ' SELECT DISTINCT person_id,member_person_type,role_code,project_id,project_name,season_id,season_name FROM history WHERE person_id IN (' . implode(',', $ids) . ')';
			foreach ($db->setQuery($metadataSql)->getIterator() as $row) {
				$person =& $people[(int)$row->person_id];
				$person['person_types'][(string)$row->member_person_type] = true;
				if ((string)$row->role_code !== '') $person['roles'][(string)$row->role_code] = true;
				$person['projects'][(int)$row->project_id] = (string)$row->project_name;
				$person['seasons'][(int)$row->season_id] = (string)$row->season_name;
				unset($person);
			}
			foreach ($db->setQuery($with . ' SELECT person_id,profile_metadata_json,COUNT(*) AS event_count FROM events WHERE person_id IN (' . implode(',', $ids) . ') GROUP BY person_id,profile_metadata_json')->getIterator() as $row) {
				$code = $this->metricCode((string)$row->profile_metadata_json);
				if ($code !== '') $people[(int)$row->person_id]['metrics'][$code] = ($people[(int)$row->person_id]['metrics'][$code] ?? 0) + (int)$row->event_count;
			}
		}
		foreach ($people as &$person) {
			foreach (['person_types','roles'] as $key) { $person[$key] = array_keys($person[$key]); sort($person[$key], SORT_NATURAL | SORT_FLAG_CASE); }
			foreach (['projects','seasons'] as $key) $person[$key] = array_values($person[$key]);
		}
		unset($person);
		$selected = null;
		$detail = null;
		$managerId = max(0, (int)($filters['manager_id'] ?? 0));
		if ($managerId > 0) {
			$row = $db->setQuery($with . ' SELECT p.*,r.played,r.wins,r.draws,r.losses,r.scored,r.conceded' . $from . ' AND p.person_id=' . $managerId . ' AND r.played>0')->loadObject();
			if ($row) {
				$holder = [];
				$selected = $this->person($holder,$row);
				$selected['manager_record'] = ['played'=>(int)$row->played,'wins'=>(int)$row->wins,'draws'=>(int)$row->draws,'losses'=>(int)$row->losses,'scored'=>StandingsDecimal::normalize((string)$row->scored),'conceded'=>StandingsDecimal::normalize((string)$row->conceded),'matches'=>[]];
				$detailTotal = (int)$row->played;
				$detailStart = min(max(0,(int)($filters['detail_start'] ?? 0)), (int)(floor(max(0,$detailTotal-1)/25)*25));
				foreach ($db->setQuery($with . ' SELECT * FROM manager_matches WHERE person_id=' . $managerId . ' AND outcome IS NOT NULL ORDER BY CASE WHEN scheduled_start IS NULL THEN 1 ELSE 0 END,scheduled_start DESC,match_id DESC', $detailStart, 25)->loadObjectList() as $match) {
					$selected['manager_record']['matches'][] = ['match_id'=>(int)$match->match_id,'scheduled_start'=>(string)($match->scheduled_start ?? ''),'timezone'=>(string)($match->timezone ?? 'UTC'),'project_name'=>$match->project_name,'competition_name'=>$match->competition_name,'season_name'=>$match->season_name,'own_team_name'=>$match->own_team_name,'opponent_team_name'=>$match->opponent_team_name,'scored'=>$match->own_value===null?null:StandingsDecimal::normalize((string)$match->own_value),'conceded'=>$match->opponent_value===null?null:StandingsDecimal::normalize((string)$match->opponent_value),'outcome'=>[1=>'win',0=>'draw',-1=>'loss'][(int)$match->outcome]];
				}
				$detail = ['total'=>$detailTotal,'start'=>$detailStart,'limit'=>25];
			}
		}
		return ['club'=>$club,'people'=>array_values($people),'metric_labels'=>$metricLabels,'has_manager_records'=>$db->setQuery($with . ' SELECT COUNT(*) FROM records')->loadResult()>0,'options'=>$this->options($clubId,$access),'filters'=>$normalized,
			'selected_manager'=>$selected,'detail_state'=>$detail,'sorting'=>['column'=>$sort,'direction'=>$sortDirection],
			'pagination_state'=>['total'=>$total,'start'=>$start,'limit'=>$limit,'requested_limit'=>$requestedLimit,'search'=>$search,'all_capped'=>$requestedLimit===0 && $total>1000]];
	}

	/** Render trusted query-builder bindings for composition into a single CTE statement. */
	private function sql(object $query): string
	{
		$values = [];
		foreach ($query->getBounded() as $name=>$binding) {
			$values[(string)$name] = $binding->value === null ? 'NULL' : (is_int($binding->value) ? (string)$binding->value : $this->database->quote((string)$binding->value));
		}
		return strtr((string)$query, $values);
	}

	private function metricCode(string $payload): string
	{
		$metadata = json_decode($payload, true);
		return is_array($metadata) && is_string($metadata['statistic_code'] ?? null) ? trim($metadata['statistic_code']) : '';
	}

	private function membershipQuery(int $clubId, string $access, array $filters): object
	{
		$db = $this->database;
		$query = $db->getQuery(true)->select(['person.id AS person_id', 'person.first_name', 'person.last_name', 'person.nickname', 'person.picture', 'member.member_person_type', 'member.role_code', 'member.valid_from', 'member.valid_until', 'project.id AS project_id', 'project.name AS project_name', 'season.id AS season_id', 'season.name AS season_name'])
			->from($db->quoteName('#__joomleague_project_entry_member', 'member'))
			->innerJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = member.person_id AND person.published = 1 AND person.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_project_entry', 'entry') . " ON entry.id = member.entry_id AND entry.entry_kind = 'team' AND entry.published = 1")
			->innerJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id AND team.club_id = :memberClub AND team.published = 1 AND team.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = entry.project_id AND project.published = 1 AND project.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id AND competition.published = 1 AND competition.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id AND season.published = 1 AND season.access IN (' . $access . ')')
			->where('member.published = 1')->bind(':memberClub', $clubId, ParameterType::INTEGER);
		return $this->filters($query, $filters, 'member.member_person_type');
	}

	private function lineupQuery(int $clubId, string $access, array $filters): object
	{
		$db = $this->database;
		$query = $db->getQuery(true)->select(['person.id AS person_id', 'person.first_name', 'person.last_name', 'person.nickname', 'person.picture', 'lineup.member_person_type', 'lineup.role_code', 'match.id AS match_id', 'match.scheduled_start', 'project.id AS project_id', 'project.name AS project_name', 'season.id AS season_id', 'season.name AS season_name'])
			->from($db->quoteName('#__joomleague_match_lineup_member', 'lineup'))
			->innerJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = lineup.person_id AND person.published = 1 AND person.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_match_participant', 'participant') . ' ON participant.id = lineup.match_participant_id AND participant.match_id = lineup.match_id AND participant.published = 1')
			->innerJoin($db->quoteName('#__joomleague_project_entry', 'entry') . " ON entry.id = participant.project_entry_id AND entry.entry_kind = 'team' AND entry.published = 1")
			->innerJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id AND team.club_id = :lineupClub AND team.published = 1 AND team.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_project_match', 'match') . ' ON match.id = lineup.match_id AND match.published = 1')
			->innerJoin($db->quoteName('#__joomleague_match_result', 'result') . " ON result.match_id = match.id AND result.status_code = 'final' AND match.status_code <> 'cancelled'")
			->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = match.project_id AND project.published = 1 AND project.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id AND competition.published = 1 AND competition.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id AND season.published = 1 AND season.access IN (' . $access . ')')
			->where('lineup.published = 1')->bind(':lineupClub', $clubId, ParameterType::INTEGER);
		return $this->filters($query, $filters, 'lineup.member_person_type');
	}

	private function eventQuery(int $clubId, string $access, array $filters): object
	{
		$db = $this->database;
		$query = $db->getQuery(true)->select(['event.primary_person_id AS person_id', 'event.event_name_key', 'event.profile_metadata_json'])
			->from($db->quoteName('#__joomleague_match_event', 'event'))
			->innerJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = event.primary_person_id AND person.published = 1 AND person.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_match_participant', 'participant') . ' ON participant.id = event.match_participant_id AND participant.match_id = event.match_id AND participant.published = 1')
			->innerJoin($db->quoteName('#__joomleague_project_entry', 'entry') . " ON entry.id = participant.project_entry_id AND entry.entry_kind = 'team' AND entry.published = 1")
			->innerJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id AND team.club_id = :eventClub AND team.published = 1 AND team.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_project_match', 'match') . ' ON match.id = event.match_id AND match.published = 1')
			->innerJoin($db->quoteName('#__joomleague_match_result', 'result') . " ON result.match_id = match.id AND result.status_code = 'final' AND match.status_code <> 'cancelled'")
			->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = match.project_id AND project.published = 1 AND project.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id AND competition.published = 1 AND competition.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id AND season.published = 1 AND season.access IN (' . $access . ')')
			->leftJoin($db->quoteName('#__joomleague_match_lineup_member', 'lineup') . ' ON lineup.id = event.primary_lineup_member_id')
			->where('event.published = 1')->where('event.primary_person_id IS NOT NULL')->bind(':eventClub', $clubId, ParameterType::INTEGER);
		return $this->filters($query, $filters, 'lineup.member_person_type');
	}

	private function managerQuery(int $clubId, string $access, array $filters): object
	{
		$db = $this->database;
		$query = $db->getQuery(true)->select(['lineup.person_id', 'lineup.role_code', 'match.id AS match_id', 'match.scheduled_start', 'match.timezone', 'own_team.name AS own_team_name', 'opponent_team.name AS opponent_team_name', 'project.name AS project_name', 'competition.name AS competition_name', 'season.name AS season_name', 'own_value.numeric_value AS own_value', 'own_value.result_rank AS own_rank', 'opponent_value.numeric_value AS opponent_value', 'opponent_value.result_rank AS opponent_rank', 'profile.id AS profile_version_id'])
			->from($db->quoteName('#__joomleague_match_lineup_member', 'lineup'))
			->innerJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = lineup.person_id AND person.published = 1 AND person.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_match_participant', 'participant') . ' ON participant.id = lineup.match_participant_id AND participant.match_id = lineup.match_id AND participant.published = 1')
			->innerJoin($db->quoteName('#__joomleague_project_entry', 'entry') . " ON entry.id = participant.project_entry_id AND entry.entry_kind = 'team' AND entry.published = 1")
			->innerJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id AND team.club_id = :managerClub AND team.published = 1 AND team.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_team', 'own_team') . ' ON own_team.id = entry.team_id')
			->innerJoin($db->quoteName('#__joomleague_project_match', 'match') . ' ON match.id = lineup.match_id AND match.published = 1')
			->innerJoin($db->quoteName('#__joomleague_match_result', 'result') . " ON result.match_id = match.id AND result.status_code = 'final' AND match.status_code <> 'cancelled'")
			->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = match.project_id AND project.published = 1 AND project.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'profile') . ' ON profile.id = project.profile_version_id')
			->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id AND competition.published = 1 AND competition.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id AND season.published = 1 AND season.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_match_participant', 'opponent') . ' ON opponent.match_id = match.id AND opponent.id <> participant.id AND opponent.published = 1')
			->innerJoin($db->quoteName('#__joomleague_project_entry', 'opponent_entry') . " ON opponent_entry.id = opponent.project_entry_id AND opponent_entry.entry_kind = 'team' AND opponent_entry.published = 1")
			->innerJoin($db->quoteName('#__joomleague_team', 'opponent_team') . ' ON opponent_team.id = opponent_entry.team_id AND opponent_team.published = 1 AND opponent_team.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_match_score_segment', 'root_segment') . ' ON root_segment.match_id = match.id AND root_segment.parent_id IS NULL')
			->innerJoin($db->quoteName('#__joomleague_match_score_value', 'own_value') . ' ON own_value.segment_id = root_segment.id AND own_value.participant_id = participant.id')
			->innerJoin($db->quoteName('#__joomleague_match_score_value', 'opponent_value') . ' ON opponent_value.segment_id = root_segment.id AND opponent_value.participant_id = opponent.id')
			->where('lineup.published = 1')->bind(':managerClub', $clubId, ParameterType::INTEGER);
		return $this->filters($query, $filters, 'lineup.member_person_type');
	}

	private function filters(object $query, array $filters, string $personTypeColumn): object
	{
		foreach (['project_id' => 'project.id', 'season_id' => 'season.id'] as $key => $column) if ($filters[$key] > 0) $query->where($column . ' = :' . $key)->bind(':' . $key, $filters[$key], ParameterType::INTEGER);
		if ($filters['project_type'] !== '') $query->where('project.project_type = :projectType')->bind(':projectType', $filters['project_type']);
		if ($filters['person_type'] !== '') $query->where($personTypeColumn . ' = :personType')->bind(':personType', $filters['person_type']);
		return $query;
	}

	/** @return array{projects:array<int,string>,seasons:array<int,string>,project_types:array<string,string>,person_types:array<string,string>} */
	private function options(int $clubId, string $access): array
	{
		$options = ['projects'=>[], 'seasons'=>[], 'project_types'=>[], 'person_types'=>[]];
		$filters = ['project_id'=>0,'season_id'=>0,'project_type'=>'','person_type'=>''];
		foreach (['membershipQuery'=>'member.member_person_type','lineupQuery'=>'lineup.member_person_type'] as $method=>$typeColumn) {
			$query = $this->{$method}($clubId,$access,$filters)->clear('select')->select(['DISTINCT project.id AS project_id','project.name AS project_name','season.id AS season_id','season.name AS season_name','project.project_type',$typeColumn . ' AS member_person_type']);
			foreach ($this->database->setQuery($query)->getIterator() as $row) {
				$options['projects'][(int)$row->project_id] = (string)$row->project_name;
				$options['seasons'][(int)$row->season_id] = (string)$row->season_name;
				$options['person_types'][(string)$row->member_person_type] = (string)$row->member_person_type;
				if ((string)$row->project_type !== '') $options['project_types'][(string)$row->project_type] = (string)$row->project_type;
			}
		}
		foreach ($options as &$values) asort($values,SORT_NATURAL | SORT_FLAG_CASE);
		return $options;
	}
	/** @param array<int,array<string,mixed>> $people @return array<string,mixed> */
	private function &person(array &$people, object $row): array
	{
		$id = (int) $row->person_id;
		$people[$id] ??= ['person_id' => $id, 'first_name' => (string) $row->first_name, 'last_name' => (string) $row->last_name, 'nickname' => (string) $row->nickname, 'picture' => (string) ($row->picture ?? ''), 'person_types' => [], 'roles' => [], 'projects' => [], 'seasons' => [], 'appearances' => 0, 'metrics' => [], 'manager_record' => null, 'first_date' => null, 'last_date' => null];
		return $people[$id];
	}

	private function outcome(object $row, bool $higherIsBetter): ?string
	{
		if ($row->own_rank !== null && $row->opponent_rank !== null) {
			if ((int) $row->own_rank === (int) $row->opponent_rank) return 'draws';
			return (int) $row->own_rank < (int) $row->opponent_rank ? 'wins' : 'losses';
		}
		if ($row->own_value === null || $row->opponent_value === null) return null;
		$comparison = StandingsDecimal::compare((string) $row->own_value, (string) $row->opponent_value);
		if ($comparison === 0) return 'draws';
		if (!$higherIsBetter) $comparison *= -1;
		return $comparison > 0 ? 'wins' : 'losses';
	}

	/** @param array<string,mixed> $person */
	private function period(array &$person, ?string $from, ?string $until): void
	{
		foreach ([$from, $until] as $date) if ($date !== null && $date !== '') {
			if ($person['first_date'] === null || $date < $person['first_date']) $person['first_date'] = $date;
			if ($person['last_date'] === null || $date > $person['last_date']) $person['last_date'] = $date;
		}
	}
}
