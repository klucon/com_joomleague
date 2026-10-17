<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

use DateTimeImmutable;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class BirthdayReader
{
	public function __construct(private readonly DatabaseInterface $database) {}

	/** @param int[] $viewLevels @param string[] $personTypes @param string[] $roleCodes @return object[] */
	public function upcoming(DateTimeImmutable $today, int $days, int $limit, array $viewLevels, int $clubId = 0, int $projectId = 0, int $teamId = 0, bool $includeDeceased = false, array $personTypes = [], array $roleCodes = [], string $periodMode = 'rolling', int $selectedMonth = 1, int $startOffset = 0, string $order = 'next_birthday', string $direction = 'asc'): array
	{
		$levels = array_values(array_unique(array_filter(array_map('intval', $viewLevels), static fn (int $level): bool => $level > 0))) ?: [1];
		$access = implode(',', $levels);
		$query = $this->database->getQuery(true)
			->select(['person.id', 'person.first_name', 'person.last_name', 'person.nickname', 'person.birth_date', 'person.death_date', 'person.picture', 'person.metadata_json'])
			->select('COALESCE(club.name, (' . $this->membershipValue('membership_club.name', $access, true) . ')) AS club_name')
			->select('(' . $this->membershipValue('membership_team.name', $access) . ') AS team_name')
			->select('(' . $this->membershipValue('membership_project.name', $access) . ') AS project_name')
			->select('(' . $this->assignmentValue('member_person_type', $access) . ') AS person_type')
			->select('(' . $this->assignmentValue('role_code', $access) . ') AS role_code')
			->from($this->database->quoteName('#__joomleague_person', 'person'))
			->leftJoin($this->database->quoteName('#__joomleague_club', 'club') . ' ON club.id = person.club_id AND club.published = 1 AND club.access IN (' . $access . ')')
			->where('person.published = 1')->where('person.access IN (' . $access . ')')->where('person.birth_date IS NOT NULL');
		if (!$includeDeceased) $query->where('person.death_date IS NULL');

		if ($clubId > 0) {
			$exists = $this->membershipExists($access, [], [], $clubId, 0, 0);
			$query->where('(club.id = :directClubId OR EXISTS (' . $exists . '))')->bind(':directClubId', $clubId, ParameterType::INTEGER);
		}
		if ($projectId > 0) {
			$membership = $this->membershipExists($access, [], [], 0, $projectId, 0);
			$actor = $this->actorExists($access, [], [], $projectId);
			$query->where('(EXISTS (' . $membership . ') OR EXISTS (' . $actor . '))');
		}
		if ($teamId > 0) $query->where('EXISTS (' . $this->membershipExists($access, [], [], 0, 0, $teamId) . ')');

		$personTypes = $this->codes($personTypes); $roleCodes = $this->codes($roleCodes);
		if ($personTypes !== [] || $roleCodes !== []) {
			$assignmentChecks = ['EXISTS (' . $this->membershipExists($access, $personTypes, $roleCodes, $clubId, $projectId, $teamId) . ')'];
			if ($clubId === 0 && $teamId === 0) $assignmentChecks[] = 'EXISTS (' . $this->actorExists($access, $personTypes, $roleCodes, $projectId) . ')';
			$query->where('(' . implode(' OR ', $assignmentChecks) . ')');
		}

		$periodMode = in_array($periodMode, ['rolling', 'current_month', 'selected_month'], true) ? $periodMode : 'rolling';
		$start = $today->modify('+' . max(0, min(366, $startOffset)) . ' days');
		$end = $start->modify('+' . max(0, min(366, $days)) . ' days');
		$month = $periodMode === 'current_month' ? (int) $today->format('n') : max(1, min(12, $selectedMonth));
		$monthYear = (int) $today->format('Y') + ($periodMode === 'selected_month' && $month < (int) $today->format('n') ? 1 : 0);
		$items = [];
		foreach ($this->database->setQuery($query)->loadObjectList() as $person) {
			$birth = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $person->birth_date, $today->getTimezone());
			if (!$birth) continue;
			if ($periodMode === 'rolling') {
				$next = $this->birthdayInYear($birth, (int) $start->format('Y'));
				if ($next < $start) $next = $this->birthdayInYear($birth, (int) $start->format('Y') + 1);
				if ($next > $end) continue;
			} else {
				if ((int) $birth->format('n') !== $month) continue;
				$next = $this->birthdayInYear($birth, $monthYear);
			}
			$metadata = json_decode((string) ($person->metadata_json ?? ''), true);
			$yearPublic = !is_array($metadata) || (($metadata['privacy']['birth_year_public'] ?? $metadata['birth_year_public'] ?? true) !== false && ($metadata['birth_date_precision'] ?? 'full') === 'full');
			$person->next_birthday = $next;
			$person->days_until = (int) $today->diff($next)->format('%r%a');
			$person->birth_year = $yearPublic ? (int) $birth->format('Y') : null;
			$person->age = $yearPublic ? (int) $next->format('Y') - (int) $birth->format('Y') : null;
			unset($person->birth_date, $person->metadata_json);
			$items[] = $person;
		}

		$order = in_array($order, ['next_birthday', 'name', 'age'], true) ? $order : 'next_birthday'; $factor = $direction === 'desc' ? -1 : 1;
		usort($items, static function (object $a, object $b) use ($order, $factor): int {
			if ($order === 'age' && (($a->age === null) !== ($b->age === null))) {
				return $a->age === null ? 1 : -1;
			}

			$result = match ($order) {
				'name' => strnatcasecmp(trim($a->last_name . ' ' . $a->first_name), trim($b->last_name . ' ' . $b->first_name)) ?: ($a->id <=> $b->id),
				'age' => (($a->age ?? 0) <=> ($b->age ?? 0)) ?: ($a->id <=> $b->id),
				default => [$a->next_birthday, $a->last_name, $a->first_name, $a->id] <=> [$b->next_birthday, $b->last_name, $b->first_name, $b->id],
			};
			return $factor * $result;
		});
		return array_slice($items, 0, max(1, min(100, $limit)));
	}

	private function membershipValue(string $column, string $access, bool $requireClub = false): string
	{
		$query = $this->database->getQuery(true)->select('MIN(' . $column . ')')->from($this->database->quoteName('#__joomleague_project_entry_member', 'membership'))->innerJoin($this->database->quoteName('#__joomleague_project_entry', 'membership_entry') . ' ON membership_entry.id = membership.entry_id AND membership_entry.published = 1')->innerJoin($this->database->quoteName('#__joomleague_project', 'membership_project') . ' ON membership_project.id = membership_entry.project_id AND membership_project.published = 1 AND membership_project.access IN (' . $access . ')')->leftJoin($this->database->quoteName('#__joomleague_team', 'membership_team') . ' ON membership_team.id = membership_entry.team_id AND membership_team.published = 1 AND membership_team.access IN (' . $access . ')')->leftJoin($this->database->quoteName('#__joomleague_club', 'membership_club') . ' ON membership_club.id = membership_team.club_id AND membership_club.published = 1 AND membership_club.access IN (' . $access . ')')->where('membership.person_id = person.id')->where('membership.published = 1');
		if ($requireClub) $query->where('membership_club.id IS NOT NULL');
		return (string) $query;
	}

	private function assignmentValue(string $column, string $access): string
	{
		$membership = $this->membershipValue('membership.' . $column, $access);
		$actor = $this->database->getQuery(true)->select('MIN(actor.' . ($column === 'member_person_type' ? 'person_type' : $column) . ')')->from($this->database->quoteName('#__joomleague_project_actor_role', 'actor'))->innerJoin($this->database->quoteName('#__joomleague_project', 'actor_project') . ' ON actor_project.id = actor.project_id AND actor_project.published = 1 AND actor_project.access IN (' . $access . ')')->where('actor.person_id = person.id')->where("actor.actor_kind = 'person'")->where('actor.published = 1');
		return 'COALESCE((' . $membership . '), (' . $actor . '))';
	}

	/** @param list<string> $types @param list<string> $roles */
	private function membershipExists(string $access, array $types, array $roles, int $clubId = 0, int $projectId = 0, int $teamId = 0): string
	{
		$q = $this->database->getQuery(true)->select('1')->from($this->database->quoteName('#__joomleague_project_entry_member', 'filter_member'))->innerJoin($this->database->quoteName('#__joomleague_project_entry', 'filter_entry') . ' ON filter_entry.id = filter_member.entry_id AND filter_entry.published = 1')->innerJoin($this->database->quoteName('#__joomleague_project', 'filter_project') . ' ON filter_project.id = filter_entry.project_id AND filter_project.published = 1 AND filter_project.access IN (' . $access . ')')->leftJoin($this->database->quoteName('#__joomleague_team', 'filter_team') . ' ON filter_team.id = filter_entry.team_id AND filter_team.published = 1 AND filter_team.access IN (' . $access . ')')->leftJoin($this->database->quoteName('#__joomleague_club', 'filter_club') . ' ON filter_club.id = filter_team.club_id AND filter_club.published = 1 AND filter_club.access IN (' . $access . ')')->where('filter_member.person_id = person.id')->where('filter_member.published = 1');
		if ($types !== []) $q->where('filter_member.member_person_type IN (' . $this->quoteList($types) . ')'); if ($roles !== []) $q->where('filter_member.role_code IN (' . $this->quoteList($roles) . ')'); if ($clubId > 0) $q->where('filter_club.id = ' . $clubId); if ($projectId > 0) $q->where('filter_entry.project_id = ' . $projectId); if ($teamId > 0) $q->where('filter_team.id = ' . $teamId);
		return (string) $q;
	}

	/** @param list<string> $types @param list<string> $roles */
	private function actorExists(string $access, array $types, array $roles, int $projectId = 0): string
	{
		$q = $this->database->getQuery(true)->select('1')->from($this->database->quoteName('#__joomleague_project_actor_role', 'filter_actor'))->innerJoin($this->database->quoteName('#__joomleague_project', 'filter_actor_project') . ' ON filter_actor_project.id = filter_actor.project_id AND filter_actor_project.published = 1 AND filter_actor_project.access IN (' . $access . ')')->where('filter_actor.person_id = person.id')->where("filter_actor.actor_kind = 'person'")->where('filter_actor.published = 1');
		if ($types !== []) $q->where('filter_actor.person_type IN (' . $this->quoteList($types) . ')'); if ($roles !== []) $q->where('filter_actor.role_code IN (' . $this->quoteList($roles) . ')'); if ($projectId > 0) $q->where('filter_actor.project_id = ' . $projectId);
		return (string) $q;
	}

	/** @return list<string> */ private function codes(array $values): array { return array_values(array_unique(array_filter(array_map(static function (mixed $value): string { $value = trim((string) $value); return preg_match('/^[a-z0-9][a-z0-9_.-]{0,99}$/i', $value) === 1 ? $value : ''; }, $values)))); }
	/** @param list<string> $values */ private function quoteList(array $values): string { return implode(',', array_map(fn (string $value): string => $this->database->quote($value), $values)); }
	private function birthdayInYear(DateTimeImmutable $birth, int $year): DateTimeImmutable { $month = (int) $birth->format('m'); $day = (int) $birth->format('d'); if ($month === 2 && $day === 29 && !checkdate(2, 29, $year)) $day = 28; return $birth->setDate($year, $month, $day); }
}
