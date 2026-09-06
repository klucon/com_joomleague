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
use Joomleague\Component\Joomleague\Administrator\Service\OrganizationHistoryRepository;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
$db = $container->get(DatabaseInterface::class);
$history = new OrganizationHistoryRepository($db);

$baseDirectory = JPATH_ROOT . '/images/joomleague/demo';
foreach (['clubs', 'teams', 'persons', 'venues'] as $directory) {
	$path = $baseDirectory . '/' . $directory;
	if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
		throw new RuntimeException('Cannot create media directory: ' . $path);
	}
}

$palette = [
	['#0b6e4f', '#f7c948'], ['#154c79', '#f4f7fb'], ['#8b1e3f', '#f4d35e'], ['#3c2a78', '#54c6eb'],
	['#b0441e', '#f5f1e8'], ['#1f5f8b', '#f28f3b'], ['#285943', '#f6ae2d'], ['#671a2f', '#d9e5d6'],
];

$colour = static function (string $hex): array {
	$hex = ltrim($hex, '#');
	return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
};

$create = static function (string $absolutePath, string $title, string $subtitle, int $seed, string $shape = 'crest') use ($palette, $colour): void {
	$width = $shape === 'portrait' ? 480 : 720;
	$height = $shape === 'portrait' ? 600 : 420;
	$image = imagecreatetruecolor($width, $height);
	[$primaryHex, $accentHex] = $palette[$seed % count($palette)];
	[$pr, $pg, $pb] = $colour($primaryHex);
	[$ar, $ag, $ab] = $colour($accentHex);
	$primary = imagecolorallocate($image, $pr, $pg, $pb);
	$accent = imagecolorallocate($image, $ar, $ag, $ab);
	$ink = imagecolorallocate($image, 18, 24, 32);
	$white = imagecolorallocate($image, 248, 250, 252);
	imagefill($image, 0, 0, $primary);
	for ($x = -$height; $x < $width; $x += 90) imagefilledpolygon($image, [$x, 0, $x + 44, 0, $x + $height + 44, $height, $x + $height, $height], 4, $accent);
	if ($shape === 'portrait') {
		imagefilledellipse($image, $width / 2, 185, 170, 170, $accent);
		imagefilledellipse($image, $width / 2, 520, 390, 430, $accent);
		imagefilledellipse($image, $width / 2, 185, 122, 122, $ink);
	} elseif ($shape === 'venue') {
		imagefilledrectangle($image, 70, 95, $width - 70, $height - 65, $accent);
		imagefilledellipse($image, $width / 2, 245, 470, 210, $primary);
		imagerectangle($image, 120, 135, $width - 120, $height - 105, $ink);
	} else {
		imagefilledpolygon($image, [$width / 2, 45, $width - 155, 115, $width - 205, $height - 75, $width / 2, $height - 25, 205, $height - 75, 155, 115], 6, $accent);
		imagefilledellipse($image, $width / 2, 205, 145, 145, $primary);
		imagestring($image, 5, (int) ($width / 2 - 18), 196, strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $title) ?: 'JL', 0, 3)), $white);
	}
	$barTop = $height - 82;
	imagefilledrectangle($image, 0, $barTop, $width, $height, $ink);
	$title = mb_strimwidth($title, 0, 46, '');
	$subtitle = mb_strimwidth($subtitle, 0, 60, '');
	imagestring($image, 5, 18, $barTop + 15, $title, $white);
	imagestring($image, 3, 18, $barTop + 45, $subtitle, $accent);
	if (!imagepng($image, $absolutePath, 7)) throw new RuntimeException('Cannot write media: ' . $absolutePath);
	imagedestroy($image);
};

$update = static function (string $table, int $id, array $values) use ($db): void {
	$record = (object) (['id' => $id] + $values);
	$db->updateObject($table, $record, 'id');
};

$clubs = $db->setQuery($db->getQuery(true)->select(['id', 'name'])->from($db->quoteName('#__joomleague_club'))->order('id ASC'))->loadObjectList();
foreach ($clubs as $club) {
	$current = 'images/joomleague/demo/clubs/club-' . $club->id . '.png';
	$legacy = 'images/joomleague/demo/clubs/club-' . $club->id . '-legacy.png';
	$create(JPATH_ROOT . '/' . $current, (string) $club->name, 'Current fictional crest', (int) $club->id);
	$create(JPATH_ROOT . '/' . $legacy, (string) $club->name, 'Historic fictional crest', (int) $club->id + 3);
	$update('#__joomleague_club', (int) $club->id, ['logo' => $current]);
	$existing = $history->load('club', (int) $club->id);
	if ($existing['media_history'] === []) $history->save('club', (int) $club->id, $existing['name_history'], [
		['media_path' => $legacy, 'alt_text' => $club->name . ' historic crest', 'valid_from' => '1995-01-01', 'valid_to' => '2014-06-30', 'notes' => 'Fictional former crest.'],
		['media_path' => $current, 'alt_text' => $club->name . ' current crest', 'valid_from' => '2014-07-01', 'notes' => 'Fictional current crest.'],
	], 0);
}

$teams = $db->setQuery($db->getQuery(true)->select(['id', 'name'])->from($db->quoteName('#__joomleague_team'))->order('id ASC'))->loadObjectList();
foreach ($teams as $team) {
	$logo = 'images/joomleague/demo/teams/team-' . $team->id . '-logo.png';
	$legacy = 'images/joomleague/demo/teams/team-' . $team->id . '-legacy.png';
	$picture = 'images/joomleague/demo/teams/team-' . $team->id . '-squad.png';
	$create(JPATH_ROOT . '/' . $logo, (string) $team->name, 'Current team mark', (int) $team->id + 1);
	$create(JPATH_ROOT . '/' . $legacy, (string) $team->name, 'Historic team mark', (int) $team->id + 4);
	$create(JPATH_ROOT . '/' . $picture, (string) $team->name, 'Fictional squad visual', (int) $team->id + 2, 'venue');
	$update('#__joomleague_team', (int) $team->id, ['logo' => $logo, 'picture' => $picture]);
	$existing = $history->load('team', (int) $team->id);
	if ($existing['media_history'] === []) $history->save('team', (int) $team->id, $existing['name_history'], [
		['media_path' => $legacy, 'alt_text' => $team->name . ' historic logo', 'valid_from' => '2010-01-01', 'valid_to' => '2019-06-30', 'notes' => 'Fictional former team logo.'],
		['media_path' => $logo, 'alt_text' => $team->name . ' current logo', 'valid_from' => '2019-07-01', 'notes' => 'Fictional current team logo.'],
	], 0);
}

$persons = $db->setQuery($db->getQuery(true)->select(['id', 'first_name', 'last_name'])->from($db->quoteName('#__joomleague_person'))->order('id ASC'))->loadObjectList();
foreach ($persons as $person) {
	$path = 'images/joomleague/demo/persons/person-' . $person->id . '.png';
	$create(JPATH_ROOT . '/' . $path, trim($person->first_name . ' ' . $person->last_name), 'Fictional demo identity', (int) $person->id, 'portrait');
	$update('#__joomleague_person', (int) $person->id, ['picture' => $path]);
}

$venues = $db->setQuery($db->getQuery(true)->select(['id', 'name', 'city'])->from($db->quoteName('#__joomleague_venue'))->order('id ASC'))->loadObjectList();
foreach ($venues as $venue) {
	$path = 'images/joomleague/demo/venues/venue-' . $venue->id . '.png';
	$create(JPATH_ROOT . '/' . $path, (string) $venue->name, (string) $venue->city . ' fictional venue', (int) $venue->id, 'venue');
	$update('#__joomleague_venue', (int) $venue->id, ['picture' => $path]);
}

echo sprintf("Generated media: %d clubs, %d teams, %d persons, %d venues.\n", count($clubs), count($teams), count($persons), count($venues));
