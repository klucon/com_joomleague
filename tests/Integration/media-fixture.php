<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] = 'joomla62.local';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\CMS\Application\SiteApplication::class);
Factory::getApplication()->loadIdentity(new Joomla\CMS\User\User(0));
Factory::getApplication()->createExtensionNamespaceMap();
Factory::getApplication()->bootComponent('com_joomleague');
$db = $container->get(DatabaseInterface::class);
$key = $argv[2] ?? '';
if (!preg_match('/^[a-f0-9]{32}$/D', $key)) throw new RuntimeException('Invalid fixture key.');
$manifest = '/tmp/jl-media-' . $key . '.json';
$base = 'images/jl-media-' . $key;
$save = static function (array $data) use ($manifest): void { file_put_contents($manifest, json_encode($data, JSON_THROW_ON_ERROR)); chmod($manifest, 0600); };

if (($argv[1] ?? '') === 'inspect') {
	$form = Joomla\CMS\Form\Form::getInstance('media.test', JPATH_ADMINISTRATOR . '/components/com_joomleague/forms/club.xml');
	$field = $form->getField('logo');
	echo json_encode(['base' => Joomla\CMS\Component\ComponentHelper::getParams('com_joomleague')->get('images_base_path'), 'field' => get_class($field), 'directory' => $field->directory]);
	exit;
}

if (($argv[1] ?? '') === 'cleanup') {
	if (!is_file($manifest)) exit;
	$data = json_decode(file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
	foreach (array_reverse($data['records']) as [$table, $id]) {
		$query = $db->getQuery(true)->delete($db->quoteName('#__joomleague_' . $table))->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
		$db->setQuery($query)->execute();
	}
	foreach ($data['files'] as $file) if (is_file(JPATH_ROOT . '/' . $file)) unlink(JPATH_ROOT . '/' . $file);
	if (is_dir(JPATH_ROOT . '/' . $base)) {
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(JPATH_ROOT . '/' . $base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($iterator as $entry) if ($entry->isDir() && !$entry->isLink()) rmdir($entry->getPathname());
		rmdir(JPATH_ROOT . '/' . $base);
	}
	unlink($manifest);
	exit;
}

if (($argv[1] ?? '') === 'modules') {
	$data = json_decode(file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
	foreach (['club' => 'Club', 'participant' => 'Participant', 'venue_program' => 'VenueProgram', 'personnel' => 'Personnel', 'spotlight' => 'Spotlight', 'birthdays' => 'Birthdays', 'program' => 'Program', 'next_event' => 'NextEvent', 'latest_results' => 'LatestResults', 'programme_ticker' => 'ProgrammeTicker', 'calendar' => 'Calendar', 'standings' => 'Standings'] as $module => $class) {
		$path = JPATH_ROOT . '/modules/mod_joomleague_' . $module . '/src/';
		foreach (glob($path . 'Configuration/*.php') as $file) require_once $file;
		foreach (glob($path . 'Helper/*.php') as $file) require_once $file;
	}
	$params = new Registry(['project_id' => $data['project'], 'club_id' => $data['club'], 'entry_id' => $data['entry'], 'venue_id' => $data['venue'], 'show_images' => 1, 'mode' => 'all', 'previous_limit' => 5, 'upcoming_limit' => 5]);
	$assert = static function (bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); };
	$expectedTeam = '/' . $data['placeholders']['team'];
	$expectedPerson = '/' . $data['placeholders']['person'];
	$club = (new \Joomleague\Module\Club\Site\Helper\ClubHelper())->getSummary($params);
	$assert(($club['club']->logo ?? '') === '/' . $data['placeholders']['club'], 'Club module fallback.');
	$participant = (new \Joomleague\Module\Participant\Site\Helper\ParticipantHelper())->getSummary($params);
	$assert(($participant['participant']->team_logo ?? '') === $expectedTeam, 'Participant module fallback.');
	$venue = (new \Joomleague\Module\VenueProgram\Site\Helper\VenueProgramHelper())->getProgram($params);
	$assert(($venue['venue']->picture ?? '') === '/' . $data['placeholders']['venue'], 'Venue module fallback.');
	$people = (new \Joomleague\Module\Personnel\Site\Helper\PersonnelHelper())->getPersonnel($params);
	$assert(($people['groups']['staff'][0]->picture ?? '') === $expectedPerson, 'Personnel module fallback.');
	$spotlightParams = new Registry(['entity_type' => 'person', 'selection_mode' => 'manual', 'person_id' => $data['person']]);
	$spotlight = (new \Joomleague\Module\Spotlight\Site\Helper\SpotlightHelper())->getSpotlight($spotlightParams);
	$assert(($spotlight['item']->image ?? '') === $expectedPerson, 'Spotlight person fallback.');
	$spotlightParams->set('entity_type', 'team'); $spotlightParams->set('team_id', $data['team']);
	$spotlight = (new \Joomleague\Module\Spotlight\Site\Helper\SpotlightHelper())->getSpotlight($spotlightParams);
	$assert(($spotlight['item']->image ?? '') === $expectedTeam, 'Spotlight team fallback.');
	$birthdays = (new \Joomleague\Module\Birthdays\Site\Helper\BirthdaysHelper())->getBirthdays(new Registry(['club_id' => $data['club'], 'days' => 10]));
	$assert(($birthdays['items'][0]->picture ?? '') === $expectedPerson, 'Birthdays fallback.');
	$program = (new \Joomleague\Module\Program\Site\Helper\ProgramHelper())->getProgramme($params);
	$next = (new \Joomleague\Module\NextEvent\Site\Helper\NextEventHelper())->getEvent($params);
	$results = (new \Joomleague\Module\LatestResults\Site\Helper\LatestResultsHelper())->getResults($params);
	$ticker = (new \Joomleague\Module\ProgrammeTicker\Site\Helper\ProgrammeTickerHelper())->getTicker($params);
	$calendar = (new \Joomleague\Module\Calendar\Site\Helper\CalendarHelper())->getCalendar($params);
	foreach (['programme' => $program['items'] ?? [], 'next' => $next['upcoming'] ?? [], 'results' => $results['items'] ?? [], 'ticker' => $ticker['items'] ?? [], 'calendar' => $calendar['groups'][0]['items'] ?? []] as $name => $items) {
		$assert($items !== [], 'Empty module fixture: ' . $name);
		foreach ($items as $item) foreach ($item['participants'] as $person) $assert($person['image'] === $expectedTeam, 'Image propagation failed: ' . $name);
	}
	$standings = (new \Joomleague\Module\Standings\Site\Helper\StandingsHelper())->getStandings($params);
	$assert(!empty($standings['rows']), 'Standings fixture missing.');
	foreach ($standings['rows'] as $row) $assert($row->image === $expectedTeam, 'Standings image propagation.');
	echo "PASS: all 12 image-bearing module helpers use configured media fallbacks with actual database records.\n";
	exit;
}

if (is_file($manifest)) throw new RuntimeException('Fixture already exists.');
$data = ['records' => [], 'files' => [], 'base' => $base, 'placeholders' => []];
$insert = static function (string $table, array $values) use ($db, &$data, $save): int {
	$values['uuid'] ??= UuidFactory::v4();
	$object = (object) $values;
	$db->insertObject('#__joomleague_' . $table, $object, 'id');
	$data['records'][] = [$table, (int) $object->id];
	$save($data);
	return (int) $object->id;
};
if (!mkdir(JPATH_ROOT . '/' . $base . '/placeholders', 0755, true)) throw new RuntimeException('Fixture directory creation failed.');
foreach (['club', 'team', 'person', 'venue'] as $kind) {
	$file = $base . '/placeholders/jl-media-' . $key . '-' . $kind . '.png';
	if (!copy(JPATH_ROOT . '/media/com_joomleague/brand/joomleague-logo.png', JPATH_ROOT . '/' . $file)) throw new RuntimeException('Fixture image copy failed.');
	$data['files'][] = $file;
	$data['placeholders'][$kind] = $file;
	$save($data);
}
$profile = (int) $db->setQuery($db->getQuery(true)->select('version.id')->from($db->quoteName('#__joomleague_sport_profile_version', 'version'))->innerJoin($db->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id = version.profile_id')->where("profile.code = 'football'")->where("version.state = 'active'")->order('version.id DESC'), 0, 1)->loadResult();
if ($profile < 1) throw new RuntimeException('Football profile required.');
$data['club'] = $insert('club', ['name' => 'Media check ' . $key, 'logo' => 'images/missing-club.png', 'published' => 1, 'access' => 1]);
$data['team'] = $insert('team', ['name' => 'Media team ' . $key, 'club_id' => $data['club'], 'published' => 1, 'access' => 1]);
$data['person'] = $insert('person', ['first_name' => 'Media', 'last_name' => 'Check ' . $key, 'club_id' => $data['club'], 'birth_date' => '1990-' . date('m-d'), 'published' => 1, 'access' => 1]);
$data['venue'] = $insert('venue', ['name' => 'Media venue ' . $key, 'published' => 1, 'access' => 1]);
$competition = $insert('competition', ['name' => 'Media competition ' . $key, 'published' => 1, 'access' => 1]);
$season = $insert('season', ['name' => 'Media season ' . $key, 'published' => 1, 'access' => 1]);
$sport = $insert('sport_type', ['profile_version_id' => $profile, 'code' => 'media_' . $key, 'name' => 'Media sport', 'published' => 1]);
$data['project'] = $insert('project', ['name' => 'Media project ' . $key, 'competition_id' => $competition, 'season_id' => $season, 'sport_type_id' => $sport, 'profile_version_id' => $profile, 'project_type' => 'league', 'published' => 1, 'access' => 1]);
$stage = $insert('project_stage', ['project_id' => $data['project'], 'name' => 'Media stage', 'code' => 'main', 'stage_type' => 'league', 'published' => 1]);
$round = $insert('project_round', ['project_id' => $data['project'], 'stage_id' => $stage, 'name' => 'Media round', 'code' => 'main', 'round_type' => 'regular', 'sequence_number' => 1, 'published' => 1]);
$secondTeam = $insert('team', ['name' => 'Second media team ' . $key, 'club_id' => $data['club'], 'published' => 1, 'access' => 1]);
$entries = [];
foreach ([$data['team'], $secondTeam] as $number => $team) $entries[] = $insert('project_entry', ['project_id' => $data['project'], 'entry_kind' => 'team', 'team_id' => $team, 'entry_code' => 'entry_' . $number, 'display_name' => 'Media entry ' . $number, 'published' => 1]);
$data['entry'] = $entries[0];
$insert('project_entry_member', ['entry_id' => $entries[0], 'person_id' => $data['person'], 'member_person_type' => 'staff', 'role_code' => 'head_coach', 'published' => 1]);
foreach (['-1 day' => true, '+1 day' => false] as $time => $final) {
	$match = $insert('project_match', ['project_id' => $data['project'], 'stage_id' => $stage, 'round_id' => $round, 'venue_id' => $data['venue'], 'contest_type' => 'head_to_head', 'scheduled_start' => (new DateTimeImmutable($time))->format('Y-m-d H:i:s'), 'status_code' => $final ? 'finished' : 'scheduled', 'published' => 1]);
	$participants = [];
	foreach ($entries as $slot => $entry) $participants[] = $insert('match_participant', ['match_id' => $match, 'project_id' => $data['project'], 'project_entry_id' => $entry, 'slot_number' => $slot + 1, 'published' => 1]);
	if ($final) {
		$insert('match_result', ['match_id' => $match, 'result_type' => 'numeric_score', 'status_code' => 'final', 'outcome_code' => 'completed']);
		$segment = $insert('match_score_segment', ['match_id' => $match, 'level_code' => 'result', 'sequence_number' => 1]);
		foreach ($participants as $index => $participant) $insert('match_score_value', ['match_id' => $match, 'segment_id' => $segment, 'participant_id' => $participant, 'numeric_value' => (string) (2 - $index), 'result_rank' => $index + 1]);
	}
}
$reader = new \Joomleague\Component\Joomleague\Domain\Service\StandingsReader($db);
(new \Joomleague\Component\Joomleague\Domain\Service\StandingsRecalculator($db, $reader))->recalculate($data['project'], null, 'total', 0);
$save($data);
echo json_encode($data, JSON_THROW_ON_ERROR);
