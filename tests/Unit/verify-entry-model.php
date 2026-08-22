<?php

declare(strict_types=1);

define('_JEXEC', 1);

require_once __DIR__ . '/../../administrator/components/com_joomleague/src/Service/EntryModelValidator.php';

use Joomleague\Component\Joomleague\Administrator\Service\EntryModelValidator;

$validator = new EntryModelValidator();
$directory = __DIR__ . '/../../administrator/components/com_joomleague/resources/sport-profiles';
$profiles = [];

foreach (glob($directory . '/*.json') ?: [] as $file) {
	$profile = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
	$validator->validate($profile);
	$profiles[$profile['code']] = $profile['entry_model'];
}

if (count($profiles) !== 15) {
	throw new RuntimeException('Expected 15 validated entry models.');
}

if ($profiles['football']['allowed_kinds'] !== ['team']) {
	throw new RuntimeException('Football must remain a team-entry profile.');
}

if (!in_array('group', $profiles['tennis']['allowed_kinds'], true) || !in_array('person', $profiles['running_race']['allowed_kinds'], true)) {
	throw new RuntimeException('Mixed individual/group profile stress tests failed.');
}

$invalid = $profiles['football'];
$invalid['default_kind'] = 'person';

try {
	$validator->validate(['entry_model' => $invalid, 'positions' => [['person_type' => 'player']]]);
	throw new RuntimeException('Invalid default entry kind was accepted.');
} catch (UnexpectedValueException) {
}

printf("Entry models OK: %d profiles, team/person/group contracts validated\n", count($profiles));
