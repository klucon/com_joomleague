<?php

declare(strict_types=1);

define('_JEXEC', 1);

require_once dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/DecimalDisplayFormatter.php';

use Joomleague\Component\Joomleague\Domain\Service\DecimalDisplayFormatter;

$cases = [
	'0' => '0',
	'0.000000000' => '0',
	'-0.000' => '0',
	'10' => '10',
	'10.000000000' => '10',
	'2.500000000' => '2.5',
	'-3.2500' => '-3.25',
	'0.123456789' => '0.123456789',
	'123456789012345678901.123456789' => '123456789012345678901.123456789',
	'100000000000000000000.000000000' => '100000000000000000000',
];

foreach ($cases as $input => $expected) {
	$actual = DecimalDisplayFormatter::format($input);

	if ($actual !== $expected) {
		throw new RuntimeException(sprintf('Decimal %s formatted as %s; expected %s.', $input, $actual, $expected));
	}
}

foreach (['', 'not-a-number', '1e3', '1,5'] as $invalid) {
	if (DecimalDisplayFormatter::format($invalid) !== '') {
		throw new RuntimeException(sprintf('Invalid decimal %s must not be rendered.', $invalid));
	}
}

echo "Decimal display formatter OK\n";
