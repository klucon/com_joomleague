<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$admin = $root . '/administrator/components/com_joomleague';
$site = $root . '/components/com_joomleague';
$contexts = ['project', 'club', 'team', 'person', 'venue', 'match'];

$component = (string) file_get_contents($admin . '/src/Extension/JoomleagueComponent.php');

foreach (['FieldsFormServiceInterface', 'FieldsServiceTrait', 'validateSection', 'getContexts'] as $needle) {
	if (!str_contains($component, $needle)) {
		throw new RuntimeException(sprintf('Native Joomla Fields component contract is missing %s.', $needle));
	}
}

$manifest = (string) file_get_contents($admin . '/joomleague.xml');

if (!str_contains($manifest, 'context=com_joomleague.project') || substr_count($manifest, '<folder>layouts</folder>') < 2) {
	throw new RuntimeException('Custom Fields menu or packaged layout directories are missing.');
}

$adminLayout = (string) file_get_contents($admin . '/layouts/joomleague/edit/customfields.php');
$siteLayout = (string) file_get_contents($site . '/layouts/joomleague/fields.php');

foreach (["getFieldsets('com_fields')", 'renderFieldset'] as $needle) {
	if (!str_contains($adminLayout, $needle)) {
		throw new RuntimeException(sprintf('Administrator Custom Fields layout is missing %s.', $needle));
	}
}

foreach (['FieldsHelper::getFields', "'fields.render'", "JPATH_ROOT . '/components/com_fields/layouts'"] as $needle) {
	if (!str_contains($siteLayout, $needle)) {
		throw new RuntimeException(sprintf('Frontend Custom Fields layout is missing %s.', $needle));
	}
}

foreach ($contexts as $context) {
	if (!str_contains($component, "'{$context}'")) {
		throw new RuntimeException(sprintf('Component field context %s is not registered.', $context));
	}

	$adminTemplate = (string) file_get_contents($admin . '/tmpl/' . $context . '/edit.php');
	if (!str_contains($adminTemplate, "'joomleague.edit.customfields'")) {
		throw new RuntimeException(sprintf('Administrator %s form does not render Custom Fields.', $context));
	}

	$siteView = $context === 'match' ? 'eventreport' : $context;
	$siteTemplate = (string) file_get_contents($site . '/tmpl/' . $siteView . '/default.php');
	if (!str_contains($siteTemplate, "'context' => 'com_joomleague.{$context}'")) {
		throw new RuntimeException(sprintf('Frontend %s detail does not render Custom Fields.', $siteView));
	}
}

foreach (['en-GB', 'cs-CZ'] as $tag) {
	$ini = parse_ini_file($admin . '/language/' . $tag . '/com_joomleague.ini', false, INI_SCANNER_RAW);
	$sys = parse_ini_file($admin . '/language/' . $tag . '/com_joomleague.sys.ini', false, INI_SCANNER_RAW);

	if (!is_array($ini) || !is_array($sys) || !array_key_exists('COM_JOOMLEAGUE_MENU_CUSTOM_FIELDS', $sys)) {
		throw new RuntimeException(sprintf('%s Custom Fields language files are incomplete.', $tag));
	}

	foreach ($contexts as $context) {
		$key = 'COM_JOOMLEAGUE_FIELDS_CONTEXT_' . strtoupper($context);
		if (!array_key_exists($key, $ini)) {
			throw new RuntimeException(sprintf('%s is missing %s.', $tag, $key));
		}
	}
}


$sqlFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($admin . '/sql'));
foreach ($sqlFiles as $sqlFile) {
	if (!$sqlFile->isFile() || strtolower($sqlFile->getExtension()) !== 'sql') {
		continue;
	}

	if (preg_match('/CREATE\s+TABLE[^;]*joomleague_(?:field|custom_field)/is', (string) file_get_contents($sqlFile->getPathname()))) {
		throw new RuntimeException('Custom Fields must use Joomla core tables, not component-owned tables.');
	}
}

printf("Custom Fields contract OK: %d native Joomla contexts with admin and frontend consumers\n", count($contexts));
