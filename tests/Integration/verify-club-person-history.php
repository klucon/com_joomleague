<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/StandingsDecimal.php';
$candidate = getenv('JOOMLEAGUE_AUDIT_HISTORY_SOURCE');
require_once $candidate ?: JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/ClubPersonHistoryReader.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Domain\Service\ClubPersonHistoryReader;

$database = Factory::getContainer()->get(DatabaseInterface::class);
$query = $database->getQuery(true)
	->select('team.club_id')
	->from($database->quoteName('#__joomleague_project_entry_member', 'member'))
	->innerJoin($database->quoteName('#__joomleague_project_entry', 'entry') . ' ON entry.id = member.entry_id')
	->innerJoin($database->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id')
	->where('member.published = 1')
	->order('team.club_id ASC');
$clubId = (int) $database->setQuery($query, 0, 1)->loadResult();
$fixture = false;
if ($clubId < 1) {
	$fixture = true;
	$database->transactionStart();
	$uuid = static fn (): string => sprintf('%s-%s-4%s-%s%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), substr(bin2hex(random_bytes(2)), 1), dechex(random_int(8, 11)), substr(bin2hex(random_bytes(2)), 1), bin2hex(random_bytes(6)));
	$insert = static function (string $table, array $values) use ($database): int {
		$query = $database->getQuery(true)->insert($database->quoteName($table))->columns($database->quoteName(array_keys($values)));
		$placeholders = [];
		foreach ($values as $key => &$value) { $placeholders[] = ':' . $key; $query->bind(':' . $key, $value); }
		$query->values(implode(',', $placeholders));
		$database->setQuery($query)->execute();
		return (int) $database->insertid();
	};
	$profileVersion = (int) $database->setQuery($database->getQuery(true)->select('version.id')->from($database->quoteName('#__joomleague_sport_profile_version', 'version'))->innerJoin($database->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id = version.profile_id')->where('profile.code = ' . $database->quote('football'))->where('version.state = ' . $database->quote('active')))->loadResult();
	$name = 'Club history fixture';
	$competition = $insert('#__joomleague_competition', ['uuid' => $uuid(), 'name' => $name, 'published' => 1, 'access' => 1]);
	$season = $insert('#__joomleague_season', ['uuid' => $uuid(), 'name' => $name, 'published' => 1, 'access' => 1]);
	$sport = $insert('#__joomleague_sport_type', ['profile_version_id' => $profileVersion, 'code' => 'club-history-' . bin2hex(random_bytes(3)), 'name' => $name]);
	$project = $insert('#__joomleague_project', ['uuid' => $uuid(), 'competition_id' => $competition, 'season_id' => $season, 'sport_type_id' => $sport, 'profile_version_id' => $profileVersion, 'name' => $name, 'project_type' => 'league', 'published' => 1, 'access' => 1]);
	$clubId = $insert('#__joomleague_club', ['uuid' => $uuid(), 'name' => $name, 'published' => 1, 'access' => 1]);
	$team = $insert('#__joomleague_team', ['uuid' => $uuid(), 'club_id' => $clubId, 'name' => $name, 'published' => 1, 'access' => 1]);
	$person = $insert('#__joomleague_person', ['uuid' => $uuid(), 'first_name' => 'Test', 'last_name' => 'Person', 'published' => 1, 'access' => 1]);
	$entry = $insert('#__joomleague_project_entry', ['uuid' => $uuid(), 'project_id' => $project, 'entry_kind' => 'team', 'team_id' => $team, 'display_name' => $name, 'published' => 1]);
	$insert('#__joomleague_project_entry_member', ['uuid' => $uuid(), 'entry_id' => $entry, 'person_id' => $person, 'member_person_type' => 'player', 'role_code' => 'forward', 'published' => 1]);
}

try {
	$started = microtime(true);
	$data = (new ClubPersonHistoryReader($database))->read($clubId, [], [1, 2, 3, 4, 5, 6, 7, 8]);
	if ($candidate) echo json_encode(['club_id' => $clubId, 'people' => count($data['people'] ?? []), 'peak_memory_bytes' => memory_get_peak_usage(true), 'seconds' => round(microtime(true) - $started, 3)]) . "\n";
	if (isset($data['error']) || $data['people'] === []) throw new RuntimeException('Club person history did not return the available club members.');
	$ids = [];
	$previous = '';
	foreach ($data['people'] as $person) {
		$id = (int) $person['person_id'];
		if ($id < 1 || isset($ids[$id])) throw new RuntimeException('Club person history contains an invalid or duplicate person.');
		$ids[$id] = true;
		$sortName = mb_strtolower((string) $person['last_name'] . ' ' . (string) $person['first_name']);
		if ($previous !== '' && strnatcasecmp($previous, $sortName) > 0) throw new RuntimeException('Club person history is not sorted by surname and first name.');
		$previous = $sortName;
		if ((int) $person['appearances'] < 0) throw new RuntimeException('Club person history contains a negative appearance count.');
		foreach ($person['metrics'] as $value) if ((int) $value < 0) throw new RuntimeException('Club person history contains a negative event count.');
		if ($person['manager_record'] !== null) {
			$record = $person['manager_record'];
			if ((int) $record['played'] !== (int) $record['wins'] + (int) $record['draws'] + (int) $record['losses']) throw new RuntimeException('Team-staff record totals are inconsistent.');
		}
	}
	echo sprintf("Club person history OK: club %d, %d people, %d metrics.\n", $clubId, count($data['people']), count($data['metric_labels']));
} finally {
	if ($fixture) $database->transactionRollback();
}
