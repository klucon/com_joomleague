<?php

declare(strict_types=1);

define('_JEXEC', 1);
require_once __DIR__ . '/../../components/com_joomleague/src/Service/EntitySchemaBuilder.php';

use Joomleague\Component\Joomleague\Site\Service\EntitySchemaBuilder;

$builder = new EntitySchemaBuilder();
$club = $builder->build('club', (object) ['name' => 'Example Club', 'description' => '<p>Club description</p>', 'founded_date' => '1980-01-01', 'logo' => 'images/club.png'], 'https://example.test/club');
$team = $builder->build('team', (object) ['name' => 'Example Team', 'club_name' => 'Example Club'], 'https://example.test/team');
$person = $builder->build('person', (object) ['first_name' => 'Alex', 'last_name' => 'Example', 'nickname' => 'Ace', 'country_code' => 'CZ'], 'https://example.test/person');
$venue = $builder->build('venue', (object) ['name' => 'Example Venue', 'city' => 'Brno', 'country_code' => 'CZ', 'capacity' => 1200], 'https://example.test/venue');

if (($club['@type'] ?? '') !== 'SportsOrganization' || ($club['description'] ?? '') !== 'Club description' || ($club['image'] ?? '') !== 'https://example.test/images/club.png') throw new RuntimeException('Club schema is invalid.');
if (($team['@type'] ?? '') !== 'SportsTeam' || ($team['memberOf']['name'] ?? '') !== 'Example Club') throw new RuntimeException('Team schema is invalid.');
if (($person['@type'] ?? '') !== 'Person' || ($person['name'] ?? '') !== 'Alex Example' || ($person['nationality']['identifier'] ?? '') !== 'CZ') throw new RuntimeException('Person schema is invalid.');
if (($venue['@type'] ?? '') !== 'Place' || ($venue['address']['addressLocality'] ?? '') !== 'Brno' || ($venue['maximumAttendeeCapacity'] ?? 0) !== 1200) throw new RuntimeException('Venue schema is invalid.');
if ($builder->build('team', (object) ['name' => ''], 'https://example.test') !== []) throw new RuntimeException('Empty entity must not produce schema.');

echo "Universal public entity Schema.org builder OK\n";
