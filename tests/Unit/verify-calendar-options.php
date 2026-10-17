<?php
declare(strict_types=1);
define('_JEXEC', 1);
require_once __DIR__ . '/../../modules/mod_joomleague_calendar/src/Helper/CalendarOptions.php';
use Joomleague\Module\Calendar\Site\Helper\CalendarOptions;
function check(bool $value, string $message): void { if (!$value) throw new RuntimeException($message); }
$tz = CalendarOptions::timezone('', 'Europe/Prague');
$fallback = new DateTimeImmutable('2026-10-02', $tz);
check(CalendarOptions::month('2026-13', $fallback)->format('Y-m') === '2026-10', 'Invalid month fallback');
check(CalendarOptions::month('../../etc/passwd', $fallback)->format('Y-m') === '2026-10', 'Month input validation');
foreach (['2028-02' => 29, '2026-03' => 31, '2026-10' => 31, '2026-02' => 28] as $value => $days) {
    $month = CalendarOptions::month($value, $fallback);
    [$from, $to] = CalendarOptions::monthWindow($month);
    check((new DateTimeImmutable('@' . $to))->setTimezone($tz)->format('j H:i:s') === $days . ' 23:59:59', 'Month ending ' . $value);
    foreach ([0, 1] as $weekday) {
        $grid = CalendarOptions::monthGrid($month, $weekday);
        $flat = array_merge(...$grid);
        check(count(array_filter($flat, static fn(array $d): bool => $d['current'])) === $days, 'Month grid coverage');
        check((int) (new DateTimeImmutable($flat[0]['date']))->format('w') === $weekday, 'First weekday');
        check(count($flat) % 7 === 0 && count($flat) <= 42, 'Complete grid weeks');
    }
}
check($tz->getName() === 'Europe/Prague', 'Site timezone inheritance');
check(CalendarOptions::timezone('invalid', 'UTC')->getName() === 'UTC', 'Invalid timezone fallback');
foreach (['2026-03-29' => 23, '2026-10-25' => 25, '2028-02-29' => 24] as $date => $hours) {
    $now = new DateTimeImmutable($date . ' 12:00:00', $tz);
    [$start] = CalendarOptions::window($now, 0, 1);
    $end = $now->setTime(23, 59, 59)->getTimestamp();
    check($end - $start + 1 === $hours * 3600, 'Local day boundary ' . $date);
}
$items = [
    ['id' => 1, 'timestamp' => 10, 'played' => true, 'status_code' => 'scheduled'],
    ['id' => 2, 'timestamp' => 20, 'played' => false, 'status_code' => 'scheduled'],
    ['id' => 3, 'timestamp' => 30, 'played' => false, 'status_code' => 'cancelled'],
    ['id' => 4, 'timestamp' => 5, 'played' => false, 'status_code' => 'scheduled'],
];
check(array_column(CalendarOptions::select($items, 'completed', 'asc', 0, 20), 'id') === [1], 'Final result determines completion');
check(array_column(CalendarOptions::select($items, 'upcoming', 'asc', 0, 20, 15), 'id') === [2], 'Future scheduled events only');
check(array_column(CalendarOptions::select($items, 'all', 'desc', 1, 2), 'id') === [2, 1], 'Sort before offset and limit');
check(count(CalendarOptions::select($items, 'all', 'asc', -1, -1)) === 1, 'Numeric bounds');
$root = __DIR__ . '/../../modules/mod_joomleague_calendar/';
$xml = simplexml_load_file($root . 'mod_joomleague_calendar.xml');
$en = parse_ini_file($root . 'language/en-GB/mod_joomleague_calendar.ini');
$cs = parse_ini_file($root . 'language/cs-CZ/mod_joomleague_calendar.ini');
foreach ($xml->xpath('//field/@label | //field/@description | //option') as $value) {
    $key = (string) $value;
    if (str_starts_with($key, 'MOD_JOOMLEAGUE_CALENDAR_')) check(isset($en[$key], $cs[$key]), 'Missing translation ' . $key);
}
echo "Calendar options OK: date boundaries, modes, ordering, bounds and language labels\n";
