<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] ??= 'localhost';
$_SERVER['REQUEST_URI'] ??= '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Extension/JoomleagueComponent.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomleague\Module\Birthdays\Site\Helper\BirthdaysHelper;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\CMS\Application\SiteApplication::class);
Factory::getApplication()->createExtensionNamespaceMap();
Factory::getApplication()->bootComponent('com_joomleague');
require_once JPATH_ROOT . '/modules/mod_joomleague_birthdays/src/Helper/BirthdaysHelper.php';
require_once JPATH_ROOT . '/modules/mod_joomleague_birthdays/src/Configuration/BirthdaysConfiguration.php';

$database = $container->get(DatabaseInterface::class);
$suffix = bin2hex(random_bytes(6));
$uuid = static function (): string {
	$hex = bin2hex(random_bytes(16));

	return sprintf('%s-%s-4%s-a%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 13, 3), substr($hex, 17, 3), substr($hex, 20, 12));
};
$today = Factory::getDate('now', Factory::getApplication()->get('offset', 'UTC'));
$birthYear = (int) $today->format('Y') - 25;
$fixtureClub = (object) ['uuid' => $uuid(), 'name' => 'Birthday club ' . $suffix, 'published' => 1, 'access' => 1];
$database->insertObject('#__joomleague_club', $fixtureClub, 'id');
$person = (object) [
	'uuid' => $uuid(),
	'first_name' => 'Birthday',
	'last_name' => 'Fixture ' . $suffix,
	'birth_date' => sprintf('%04d-%s', $birthYear, $today->format('m-d')),
	'published' => 1,
	'access' => 1,
];
$deceased = (object) [
	'uuid' => $uuid(),
	'first_name' => 'Historical',
	'last_name' => 'Birthday ' . $suffix,
	'birth_date' => sprintf('%04d-%s', $birthYear - 50, $today->format('m-d')),
	'death_date' => sprintf('%04d-%s', (int) $today->format('Y') - 1, $today->format('m-d')),
	'published' => 1,
	'access' => 1,
];
$privateYear = (object) ['uuid' => $uuid(), 'first_name' => 'Private', 'last_name' => 'Year ' . $suffix, 'birth_date' => sprintf('%04d-%s', $birthYear - 5, $today->format('m-d')), 'metadata_json' => '{"privacy":{"birth_year_public":false}}', 'published' => 1, 'access' => 1];
$leap = (object) ['uuid' => $uuid(), 'club_id' => (int) $fixtureClub->id, 'first_name' => 'Leap', 'last_name' => 'Birthday ' . $suffix, 'birth_date' => '2000-02-29', 'published' => 1, 'access' => 1];

$database->insertObject('#__joomleague_person', $person, 'id');
$database->insertObject('#__joomleague_person', $deceased, 'id');
$database->insertObject('#__joomleague_person', $privateYear, 'id');
$database->insertObject('#__joomleague_person', $leap, 'id');
$member = null;

try {
	$result = (new BirthdaysHelper())->getBirthdays(new Registry(['days' => 0, 'limit' => 100, 'show_age' => 1]));
	$matches = array_values(array_filter($result['items'] ?? [], static fn (object $item): bool => (int) $item->id === (int) $person->id));
	if (count($matches) !== 1 || (int) $matches[0]->days_until !== 0 || (int) $matches[0]->age !== 25) {
		throw new RuntimeException('Birthday module did not return the current-day fixture with the expected age.');
	}
	if (array_filter($result['items'] ?? [], static fn (object $item): bool => (int) $item->id === (int) $deceased->id)) {
		throw new RuntimeException('Birthday module exposed a deceased person without explicit opt-in.');
	}
	$privateMatches = array_values(array_filter($result['items'] ?? [], static fn (object $item): bool => (int) $item->id === (int) $privateYear->id));
	if (count($privateMatches) !== 1 || $privateMatches[0]->age !== null || $privateMatches[0]->birth_year !== null || isset($privateMatches[0]->birth_date)) throw new RuntimeException('Hidden birth year leaked through age, year or source date.');
	$offset = (new BirthdaysHelper())->getBirthdays(new Registry(['days' => 0, 'start_offset' => 1, 'limit' => 100]));
	if (array_filter($offset['items'] ?? [], static fn (object $item): bool => (int) $item->id === (int) $person->id)) throw new RuntimeException('Birthday start offset did not change the result.');
	$currentMonth = (new BirthdaysHelper())->getBirthdays(new Registry(['period_mode' => 'current_month', 'limit' => 100]));
	if (!array_filter($currentMonth['items'] ?? [], static fn (object $item): bool => (int) $item->id === (int) $person->id)) throw new RuntimeException('Current-month mode omitted a current-month birthday.');
	$leapResult = (new BirthdaysHelper())->getBirthdays(new Registry(['club_id' => (int) $fixtureClub->id, 'period_mode' => 'selected_month', 'selected_month' => 2, 'limit' => 100]));
	$leapMatches = array_values(array_filter($leapResult['items'] ?? [], static fn (object $item): bool => (int) $item->id === (int) $leap->id));
	$leapDate = count($leapMatches) === 1 ? $leapMatches[0]->next_birthday->format('Y-m-d') : 'missing';
	$expectedLeapDate = ((int) $today->format('Y') + 1) . '-02-' . (checkdate(2, 29, (int) $today->format('Y') + 1) ? '29' : '28');
	if ($leapDate !== $expectedLeapDate) throw new RuntimeException('Leap-day birthday policy failed: ' . $leapDate . ' expected ' . $expectedLeapDate);

	$withDeceased = (new BirthdaysHelper())->getBirthdays(new Registry(['days' => 0, 'limit' => 100, 'include_deceased' => 1]));
	if (!array_filter($withDeceased['items'] ?? [], static fn (object $item): bool => (int) $item->id === (int) $deceased->id)) {
		throw new RuntimeException('Birthday module did not return a deceased person after explicit opt-in.');
	}

	$scopeQuery = $database->getQuery(true)
		->select(['entry.id AS entry_id', 'entry.project_id', 'entry.team_id', 'team.club_id'])
		->from($database->quoteName('#__joomleague_project_entry', 'entry'))
		->innerJoin($database->quoteName('#__joomleague_project', 'project') . ' ON project.id = entry.project_id AND project.published = 1 AND project.access = 1')
		->innerJoin($database->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id AND team.published = 1 AND team.access = 1')
		->innerJoin($database->quoteName('#__joomleague_club', 'club') . ' ON club.id = team.club_id AND club.published = 1 AND club.access = 1')
		->where('entry.published = 1');
	$scope = $database->setQuery($scopeQuery, 0, 1)->loadObject();

	if ($scope) {
		$member = (object) [
			'uuid' => $uuid(),
			'entry_id' => (int) $scope->entry_id,
			'person_id' => (int) $person->id,
			'member_person_type' => 'player',
			'role_code' => 'captain',
			'published' => 1,
		];
		$database->insertObject('#__joomleague_project_entry_member', $member, 'id');

		foreach ([
			['club_id' => (int) $scope->club_id],
			['team_id' => (int) $scope->team_id],
			['project_id' => (int) $scope->project_id],
		] as $filter) {
			$scoped = (new BirthdaysHelper())->getBirthdays(new Registry($filter + ['days' => 0, 'limit' => 100]));
			if (!array_filter($scoped['items'] ?? [], static fn (object $item): bool => (int) $item->id === (int) $person->id)) {
				throw new RuntimeException('Birthday module did not resolve a person through project membership.');
			}
		}
		$typed = (new BirthdaysHelper())->getBirthdays(new Registry(['project_id' => (int) $scope->project_id, 'person_types' => ['player'], 'role_codes' => ['captain'], 'days' => 0, 'limit' => 100]));
		$typedMatch = array_values(array_filter($typed['items'] ?? [], static fn (object $item): bool => (int) $item->id === (int) $person->id));
		if (count($typedMatch) !== 1 || $typedMatch[0]->person_type !== 'player' || $typedMatch[0]->role_code !== 'captain' || !$typedMatch[0]->team_name || !$typedMatch[0]->project_name) throw new RuntimeException('Type/role filter or team/project context failed.');
		$wrongType = (new BirthdaysHelper())->getBirthdays(new Registry(['project_id' => (int) $scope->project_id, 'person_types' => ['official'], 'days' => 0, 'limit' => 100]));
		if (array_filter($wrongType['items'] ?? [], static fn (object $item): bool => (int) $item->id === (int) $person->id)) throw new RuntimeException('Person-type filter leaked a different assignment type.');
	}

	printf("Birthdays module OK on %s: date, deceased and membership filters verified\n", $database->getServerType());
} finally {
	if ($member) {
		$query = $database->getQuery(true)->delete($database->quoteName('#__joomleague_project_entry_member'))->where('id = ' . (int) $member->id);
		$database->setQuery($query)->execute();
	}
	$query = $database->getQuery(true)->delete($database->quoteName('#__joomleague_person'))->where('id IN (' . implode(',', array_map('intval', [$person->id, $deceased->id, $privateYear->id, $leap->id])) . ')');
	$database->setQuery($query)->execute();
	$database->setQuery($database->getQuery(true)->delete($database->quoteName('#__joomleague_club'))->where('id = ' . (int) $fixtureClub->id))->execute();
}
