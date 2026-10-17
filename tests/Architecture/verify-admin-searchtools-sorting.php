<?php

declare(strict_types=1);

/**
 * Every administrator template using Joomla Search Tools must opt into the
 * JavaScript initializer that handles column ordering and filter submission.
 */

$root = dirname(__DIR__, 2);
$templateRoot = $root . '/administrator/components/com_joomleague/tmpl';
$templates = glob($templateRoot . '/*/default.php') ?: [];
$violations = [];

foreach ($templates as $template) {
	$contents = file_get_contents($template);

	if ($contents === false) {
		$violations[] = $template . ': cannot be read';
		continue;
	}

	if (str_contains($contents, "joomla.searchtools.default") && !str_contains($contents, 'class="js-stools-form"')) {
		$violations[] = $template . ': Search Tools form is missing class="js-stools-form"';
	}
	if (str_contains($contents, 'joomla.searchtools.default') && str_contains($contents, 'name="filter_order')) {
		$violations[] = $template . ': legacy ordering inputs override Search Tools ordering';
	}
}

foreach (glob($root . '/administrator/components/com_joomleague/src/Model/*Model.php') ?: [] as $model) {
	$contents = file_get_contents($model);
	if (preg_match('/getState\(\x27list.direction\x27\)\s*===/', $contents)) {
		$violations[] = $model . ': ordering direction must be case insensitive';
	}
}

if ($violations !== []) {
	fwrite(STDERR, implode(PHP_EOL, $violations) . PHP_EOL);
	exit(1);
}

echo "Administrator Search Tools sorting contract passed." . PHP_EOL;
