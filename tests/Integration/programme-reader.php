<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] ??= 'localhost';
$_SERVER['REQUEST_URI'] ??= '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/ProgrammeReader.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Domain\Service\ProgrammeReader;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
$database = $container->get(DatabaseInterface::class);
$created = [];
$fixtureTransaction = false;

$uuid = static function (): string {
	$data = random_bytes(16);
	$data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
	$data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
	return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
};

$insert = static function (string $table, array $values) use ($database): int {
	$query = $database->getQuery(true)->insert($database->quoteName($table))->columns($database->quoteName(array_keys($values)));
	$placeholders = [];
	foreach ($values as $key => &$value) {
		$placeholders[] = ':' . $key;
		$query->bind(':' . $key, $value);
	}
	$query->values(implode(',', $placeholders));
	$database->setQuery($query)->execute();
	return (int) $database->insertid();
};

$projectId = (int) $database->setQuery(
	$database->getQuery(true)
		->select('id')
		->from($database->quoteName('#__joomleague_project'))
		->where('published = 1')
		->order('id DESC'),
	0,
	1
)->loadResult();
$requestedProject = (int) getenv('JOOMLEAGUE_TEST_PROJECT_ID');
if ($requestedProject > 0) $projectId = $requestedProject;

if ($projectId < 1) {
	$database->transactionStart();
	$fixtureTransaction = true;
	register_shutdown_function(static function () use ($database, &$fixtureTransaction): void {
		if ($fixtureTransaction) {
			$database->transactionRollback();
		}
	});
	$profileVersionId = (int) $database->setQuery(
		$database->getQuery(true)->select('id')->from($database->quoteName('#__joomleague_sport_profile_version'))->order('id ASC'),
		0,
		1
	)->loadResult();
	if ($profileVersionId < 1) {
		throw new RuntimeException('Programme reader fixture requires a bundled sport profile.');
	}

	$name = 'Programme integration ' . bin2hex(random_bytes(4));
	$competitionId = $insert('#__joomleague_competition', ['uuid' => $uuid(), 'name' => $name]);
	$seasonId = $insert('#__joomleague_season', ['uuid' => $uuid(), 'name' => $name]);
	$sportTypeId = $insert('#__joomleague_sport_type', ['profile_version_id' => $profileVersionId, 'code' => 'programme-' . bin2hex(random_bytes(4)), 'name' => $name]);
	$projectId = $insert('#__joomleague_project', ['uuid' => $uuid(), 'competition_id' => $competitionId, 'season_id' => $seasonId, 'sport_type_id' => $sportTypeId, 'profile_version_id' => $profileVersionId, 'name' => $name, 'project_type' => 'league', 'published' => 1]);
	$stageId = $insert('#__joomleague_project_stage', ['uuid' => $uuid(), 'project_id' => $projectId, 'name' => 'Stage', 'code' => 'stage', 'stage_type' => 'league', 'published' => 1]);
	$roundId = $insert('#__joomleague_project_round', ['uuid' => $uuid(), 'project_id' => $projectId, 'stage_id' => $stageId, 'name' => 'Round', 'code' => 'round', 'round_type' => 'regular', 'sequence_number' => 1, 'published' => 1]);
	$entries = [
		$insert('#__joomleague_project_entry', ['uuid' => $uuid(), 'project_id' => $projectId, 'entry_kind' => 'group', 'display_name' => 'Entry A', 'published' => 1]),
		$insert('#__joomleague_project_entry', ['uuid' => $uuid(), 'project_id' => $projectId, 'entry_kind' => 'group', 'display_name' => 'Entry B', 'published' => 1]),
	];
	$matchId = $insert('#__joomleague_project_match', ['uuid' => $uuid(), 'project_id' => $projectId, 'stage_id' => $stageId, 'round_id' => $roundId, 'contest_type' => 'head_to_head', 'scheduled_start' => '2026-09-01 12:00:00', 'published' => 1]);
	foreach ($entries as $slot => $entryId) {
		$insert('#__joomleague_match_participant', ['uuid' => $uuid(), 'match_id' => $matchId, 'project_id' => $projectId, 'project_entry_id' => $entryId, 'slot_number' => $slot + 1, 'published' => 1]);
	}
	$created = compact('competitionId', 'seasonId', 'sportTypeId', 'projectId');
}

$reader = new ProgrammeReader($database);
$all = $reader->forProject($projectId, null, [1, 2, 3, 4, 5, 6, 7]);
if ($all === [] || $all[0]['participants'] === []) {
	throw new RuntimeException('Non-empty programme and participants required for acceptance.');
}
$scoped = [];
$filtered = [];

if ($all !== [] && $all[0]['participants'] !== []) {
	$scopedEntryId = (int) $all[0]['participants'][0]['entry_id'];
	$scoped = $reader->forProject($projectId, [$scopedEntryId], [1, 2, 3, 4, 5, 6, 7]);
	if ($scoped === []) throw new RuntimeException('Existing participant scope returned no events.');
	foreach ($scoped as $event) {
		if (!in_array($scopedEntryId, array_map('intval', array_column($event['participants'], 'entry_id')), true)) {
			throw new RuntimeException('Participant scope leaked an unrelated event.');
		}
	}
	$foreignEntryId = (int) $database->setQuery(
		$database->getQuery(true)->select('id')->from($database->quoteName('#__joomleague_project_entry'))->where('project_id <> ' . (int) $projectId)->order('id'), 0, 1
	)->loadResult();
	if ($foreignEntryId > 0 && $reader->forProject($projectId, [$foreignEntryId], [1, 2, 3, 4, 5, 6, 7]) !== []) {
		throw new RuntimeException('Foreign-project participant scope returned events.');
	}
	$filtered = $reader->forProject(
		$projectId,
		null,
		[1, 2, 3, 4, 5, 6, 7],
		$all[0]['venue_id'],
		(int) $all[0]['stage_id'],
		(int) $all[0]['round_id']
	);
}

foreach ($all as $event) {
	if (empty($event['uuid']) || empty($event['created']) || empty($event['modified'])) {
		throw new RuntimeException('Programme event is missing persistent calendar identity or modification metadata.');
	}
	if ((int) $event['stage_id'] < 1 || (int) $event['round_id'] < 1) {
		throw new RuntimeException('Programme event is missing its public stage or round context.');
	}
	if (!$event['played']) {
		foreach ($event['participants'] as $participant) {
			if ($participant['score'] !== null) {
				throw new RuntimeException('A non-final programme event exposed a score.');
			}
		}
	}
}

if ($all !== [] && $filtered === []) throw new RuntimeException('Valid stage and round filters removed the selected programme event.');
foreach ($filtered as $event) {
	if ((int) $event['stage_id'] !== (int) $all[0]['stage_id'] || (int) $event['round_id'] !== (int) $all[0]['round_id']) {
		throw new RuntimeException('Programme reader stage or round filter leaked another event.');
	}
}

echo sprintf("Programme reader OK: project %d, %d events, %d scoped events, %d filtered events\n", $projectId, count($all), count($scoped), count($filtered));

if ($created !== []) {
	$database->transactionRollback();
	$fixtureTransaction = false;
}
