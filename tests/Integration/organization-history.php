<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/UuidFactory.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/OrganizationHistoryRepository.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Administrator\Service\OrganizationHistoryRepository;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

$database = Factory::getContainer()->get(DatabaseInterface::class);
$fixture = 'Organization history fixture ' . bin2hex(random_bytes(4));
$club = (object) [
	'uuid' => UuidFactory::v4(),
	'name' => $fixture,
	'short_name' => 'History fixture',
	'created' => gmdate('Y-m-d H:i:s'),
];
$database->insertObject('#__joomleague_club', $club, 'id');
$clubId = (int) $club->id;
$team = (object) [
	'uuid' => UuidFactory::v4(),
	'club_id' => $clubId,
	'name' => $fixture . ' team',
	'created' => gmdate('Y-m-d H:i:s'),
];
$database->insertObject('#__joomleague_team', $team, 'id');
$teamId = (int) $team->id;
$repository = new OrganizationHistoryRepository($database);

try {
	$repository->save('club', $clubId, [
		['name' => 'First club name', 'short_name' => 'First', 'valid_from' => '1950-01-01', 'valid_to' => '1979-12-31'],
		['name' => 'Second club name', 'short_name' => 'Second', 'valid_from' => '1980-01-01', 'valid_to' => '2000-12-31'],
	], [
		['media_path' => 'images/com_joomleague/clubs/old-logo.png', 'alt_text' => 'Old club logo', 'valid_to' => '2000-12-31'],
	], 0);
	$repository->save('team', $teamId, [
		['name' => 'Former team name', 'valid_from' => '1990-01-01'],
	], [
		['media_path' => 'images/com_joomleague/teams/logos/former-logo.png', 'alt_text' => 'Former team logo'],
	], 0);

	$clubHistory = $repository->load('club', $clubId);
	$teamHistory = $repository->load('team', $teamId);

	if (count($clubHistory['name_history']) !== 2 || count($clubHistory['media_history']) !== 1) {
		throw new RuntimeException('Club history was not stored completely.');
	}

	if (count($teamHistory['name_history']) !== 1 || count($teamHistory['media_history']) !== 1) {
		throw new RuntimeException('Team history was not stored completely.');
	}

	try {
		$repository->save('club', $clubId, [
			['name' => 'Overlapping club name', 'valid_from' => '1979-12-31', 'valid_to' => '1980-01-01'],
		], [], 0);
		throw new RuntimeException('Overlapping name history was accepted.');
	} catch (InvalidArgumentException) {
	}

	try {
		$repository->save('team', $teamId, [], [
			['media_path' => 'images/com_joomleague/teams/logos/overlap.png', 'valid_from' => '2000-01-01'],
		], 0);
		throw new RuntimeException('Overlapping open-ended logo history was accepted.');
	} catch (InvalidArgumentException) {
	}

	$formerTeamLogo = $teamHistory['media_history'][0];
	$formerTeamLogo['remove_record'] = 1;
	$repository->save('team', $teamId, [], [
		['media_path' => 'images/com_joomleague/teams/logos/replacement.png', 'valid_from' => '2000-01-01'],
		$formerTeamLogo,
	], 0);
	$teamLogos = $repository->load('team', $teamId)['media_history'];

	if (count($teamLogos) !== 1 || $teamLogos[0]['media_path'] !== 'images/com_joomleague/teams/logos/replacement.png') {
		throw new RuntimeException('Same-request history replacement depends on submitted row ordering.');
	}

	$firstClubName = $clubHistory['name_history'][1];
	$firstClubName['name'] = 'Corrected first club name';
	$repository->save('club', $clubId, [$firstClubName], [], 0);

	$clubHistory = $repository->load('club', $clubId);
	$namesById = array_column($clubHistory['name_history'], 'name', 'id');

	if (($namesById[(int) $firstClubName['id']] ?? null) !== 'Corrected first club name') {
		throw new RuntimeException('Existing club history was not updated.');
	}

	try {
		$foreignRow = $clubHistory['name_history'][0];
		$repository->save('team', $teamId, [$foreignRow], [], 0);
		throw new RuntimeException('A history row owned by another organization was accepted.');
	} catch (InvalidArgumentException) {
	}

	try {
		$foreignRow['remove_record'] = 1;
		$repository->save('team', $teamId, [$foreignRow], [], 0);
		throw new RuntimeException('A history row owned by another organization was deleted.');
	} catch (InvalidArgumentException) {
	}

	$clubLogo = $clubHistory['media_history'][0];
	$clubLogo['remove_record'] = 1;
	$repository->save('club', $clubId, [], [$clubLogo], 0);

	if ($repository->load('club', $clubId)['media_history'] !== []) {
		throw new RuntimeException('An explicitly selected owned history row was not deleted.');
	}

	$repository->save('club', $clubId, [['remove_record' => 1]], [['remove_record' => 1]], 0);

	$repository->save('club', $clubId, [], [], 0);

	if (count($repository->load('club', $clubId)['name_history']) !== 2) {
		throw new RuntimeException('Omitted history rows were deleted.');
	}

	echo "Organization name and logo history OK\n";
} finally {
	foreach ([['#__joomleague_team', $teamId], ['#__joomleague_club', $clubId]] as [$table, $id]) {
		$query = $database->getQuery(true)
			->delete($database->quoteName($table))
			->where($database->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);
		$database->setQuery($query)->execute();
	}
}
