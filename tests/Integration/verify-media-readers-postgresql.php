<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli' || getenv('MEDIA_TEST_PG_HOST') !== 'jl-media-test-pg') exit(1);
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] = 'joomla62.local'; $_SERVER['REQUEST_URI'] = '/'; $_SERVER['SCRIPT_NAME'] = '/index.php';
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;
use Joomleague\Component\Joomleague\Domain\Service\ProgrammeReader;
use Joomleague\Component\Joomleague\Domain\Service\ParticipantSummaryReader;
use Joomleague\Component\Joomleague\Domain\Service\SpotlightReader;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\CMS\Application\SiteApplication::class);
Factory::getApplication()->createExtensionNamespaceMap();
Factory::getApplication()->bootComponent('com_joomleague');
$db = (new Joomla\Database\DatabaseFactory())->getDriver('pgsql', [
	'host' => getenv('MEDIA_TEST_PG_HOST'), 'user' => 'postgres', 'password' => getenv('MEDIA_TEST_PG_PASSWORD'),
	'database' => 'media_test', 'prefix' => 'media_',
]);
$sql = file_get_contents(JPATH_ADMINISTRATOR . '/components/com_joomleague/sql/install.postgresql.sql');
foreach (Joomla\Database\DatabaseDriver::splitSql($sql) as $statement) if (trim($statement) !== '') $db->setQuery($statement)->execute();
$insert = static function (string $table, array $data, bool $uuid = true) use ($db): int {
	if ($uuid || $table === 'sport_type') $data['published'] = 1;
	if ($uuid) $data['uuid'] = UuidFactory::v4();
	$object = (object) $data;
	$db->insertObject('#__joomleague_' . $table, $object, 'id');
	return (int) $object->id;
};
$assert = static function (bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); };
$profile = $insert('sport_profile', ['code' => 'media_test', 'name_key' => 'Test', 'description_key' => 'Test'], false);
$version = $insert('sport_profile_version', ['profile_id' => $profile, 'schema_version' => '1', 'profile_version' => '1', 'payload_json' => '{}', 'payload_checksum' => hash('sha256', '{}')], false);
$competition = $insert('competition', ['name' => 'Test']);
$season = $insert('season', ['name' => 'Test']);
$sport = $insert('sport_type', ['name' => 'Test', 'code' => 'media_test', 'profile_version_id' => $version], false);
$club = $insert('club', ['name' => 'Visible club', 'logo' => 'images/com_joomleague/clubs/crest.png']);
$team = $insert('team', ['name' => 'Team', 'club_id' => $club, 'logo' => 'missing.png']);
$project = $insert('project', ['name' => 'Project', 'competition_id' => $competition, 'season_id' => $season, 'sport_type_id' => $sport, 'profile_version_id' => $version, 'project_type' => 'league']);
$entry = $insert('project_entry', ['project_id' => $project, 'entry_kind' => 'team', 'team_id' => $team]);
$stage = $insert('project_stage', ['project_id' => $project, 'name' => 'Stage', 'code' => 'stage', 'stage_type' => 'league']);
$round = $insert('project_round', ['project_id' => $project, 'stage_id' => $stage, 'name' => 'Round', 'code' => 'round', 'sequence_number' => 1]);
$match = $insert('project_match', ['project_id' => $project, 'stage_id' => $stage, 'round_id' => $round, 'contest_type' => 'head_to_head']);
$insert('match_participant', ['match_id' => $match, 'project_id' => $project, 'project_entry_id' => $entry, 'slot_number' => 1]);
$programme = (new ProgrammeReader($db))->forProject($project, null, [1]);
$assert(($programme[0]['participants'][0]['club_logo'] ?? '') === 'images/com_joomleague/clubs/crest.png', 'Programme logo inheritance query failed on PostgreSQL.');
$participant = (new ParticipantSummaryReader($db))->read($project, $entry, [1], date('Y-m-d'));
$assert(($participant['participant']->club_logo ?? '') === 'images/com_joomleague/clubs/crest.png', 'Participant logo inheritance query failed on PostgreSQL.');
$spotlight = (new SpotlightReader($db))->read('team', $team, false, 'test', [1]);
$assert(($spotlight->club_logo ?? '') === 'images/com_joomleague/clubs/crest.png', 'Spotlight logo inheritance query failed on PostgreSQL.');
$db->setQuery($db->getQuery(true)->update($db->quoteName('#__joomleague_club'))->set('access = 2')->where('id = ' . $club))->execute();
$programme = (new ProgrammeReader($db))->forProject($project, null, [1]);
$assert(($programme[0]['participants'][0]['club_logo'] ?? null) === null, 'Programme leaked restricted club logo on PostgreSQL.');
$participant = (new ParticipantSummaryReader($db))->read($project, $entry, [1], date('Y-m-d'));
$assert(($participant['participant']->club_logo ?? null) === null, 'Participant leaked restricted club logo on PostgreSQL.');
$spotlight = (new SpotlightReader($db))->read('team', $team, false, 'test', [1]);
$assert(($spotlight->club_logo ?? null) === null, 'Spotlight leaked restricted club logo on PostgreSQL.');
echo "PASS: fresh PostgreSQL schema, programme/participant/spotlight media fields and restricted-club ACL.\n";
