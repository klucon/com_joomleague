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

	/** @param int[] $viewLevels @return object[] */
	public function upcoming(DateTimeImmutable $today, int $days, int $limit, array $viewLevels, int $clubId = 0): array
	{
		$levels = array_values(array_unique(array_filter(array_map('intval', $viewLevels), static fn (int $level): bool => $level > 0))) ?: [1];
		$query = $this->database->getQuery(true)
			->select(['person.id', 'person.first_name', 'person.last_name', 'person.nickname', 'person.birth_date', 'person.picture', 'club.name AS club_name'])
			->from($this->database->quoteName('#__joomleague_person', 'person'))
			->leftJoin($this->database->quoteName('#__joomleague_club', 'club') . ' ON club.id = person.club_id AND club.published = 1 AND club.access IN (' . implode(',', $levels) . ')')
			->where('person.published = 1')
			->where('person.access IN (' . implode(',', $levels) . ')')
			->where('person.birth_date IS NOT NULL')
			->where('person.death_date IS NULL');

		if ($clubId > 0) {
			$query->where('person.club_id = :clubId')->bind(':clubId', $clubId, ParameterType::INTEGER);
		}

		$end = $today->modify('+' . max(0, min(366, $days)) . ' days');
		$items = [];

		foreach ($this->database->setQuery($query)->loadObjectList() as $person) {
			$birth = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $person->birth_date, $today->getTimezone());

			if (!$birth) continue;

			$next = $this->birthdayInYear($birth, (int) $today->format('Y'));
			if ($next < $today) $next = $this->birthdayInYear($birth, (int) $today->format('Y') + 1);
			if ($next > $end) continue;

			$person->next_birthday = $next;
			$person->days_until = (int) $today->diff($next)->format('%a');
			$person->age = (int) $next->format('Y') - (int) $birth->format('Y');
			$items[] = $person;
		}

		usort($items, static fn (object $a, object $b): int => [$a->next_birthday, $a->last_name, $a->first_name, $a->id] <=> [$b->next_birthday, $b->last_name, $b->first_name, $b->id]);

		return array_slice($items, 0, max(1, min(100, $limit)));
	}

	private function birthdayInYear(DateTimeImmutable $birth, int $year): DateTimeImmutable
	{
		$month = (int) $birth->format('m');
		$day = (int) $birth->format('d');
		if ($month === 2 && $day === 29 && !checkdate(2, 29, $year)) $day = 28;

		return $birth->setDate($year, $month, $day);
	}
}
