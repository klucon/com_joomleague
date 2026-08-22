<?php

declare(strict_types=1);

define('_JEXEC', 1);

$root = dirname(__DIR__, 2);
require_once $root . '/administrator/components/com_joomleague/src/Service/UuidFactory.php';

use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

$values = [];

for ($i = 0; $i < 1000; $i++) {
	$uuid = UuidFactory::v4();

	if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) !== 1) {
		throw new RuntimeException('UUID factory generated an invalid RFC 4122 version 4 identifier: ' . $uuid);
	}

	$values[$uuid] = true;
}

if (count($values) !== 1000) {
	throw new RuntimeException('UUID factory generated a duplicate identifier in the test sample.');
}

echo "UUID factory OK: 1000 unique RFC 4122 version 4 identifiers generated\n";
