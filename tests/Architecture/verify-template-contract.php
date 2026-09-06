<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$admin = $root . '/administrator/components/com_joomleague';
$site = $root . '/components/com_joomleague';
$definitions = json_decode((string) file_get_contents($admin . '/resources/template-definitions/templates.json'), true, 512, JSON_THROW_ON_ERROR);

if (($definitions['schema_version'] ?? null) !== '1.0.0' || !is_array($definitions['definitions'] ?? null)) {
	throw new RuntimeException('The template definition registry is invalid.');
}

$requiredFiles = [
	$admin . '/forms/profiletemplates.xml',
	$admin . '/src/Controller/ProfiletemplatesController.php',
	$admin . '/src/Model/ProfiletemplatesModel.php',
	$admin . '/src/Service/ProfileTemplateConfigRepository.php',
	$admin . '/src/View/Profiletemplates/HtmlView.php',
	$admin . '/tmpl/profiletemplates/default.php',
	$admin . '/src/Controller/ProjecttemplatesController.php',
	$admin . '/src/Service/ProjectTemplateConfigRepository.php',
	$site . '/src/Service/ProjectTemplateProvider.php',
];

foreach ($requiredFiles as $file) {
	if (!is_file($file)) {
		throw new RuntimeException(sprintf('Template contract file is missing: %s.', $file));
	}
}

$profileController = (string) file_get_contents($admin . '/src/Controller/ProfiletemplatesController.php');
$projectController = (string) file_get_contents($admin . '/src/Controller/ProjecttemplatesController.php');

foreach ([
	[$profileController, "Session::checkToken()", 'profile CSRF'],
	[$profileController, "authorise('core.options', 'com_joomleague')", 'profile ACL'],
	[$projectController, "Session::checkToken()", 'project CSRF'],
	[$projectController, "authorise('joomleague.project.edit.rules'", 'project ACL'],
] as [$source, $needle, $label]) {
	if (!str_contains($source, $needle)) {
		throw new RuntimeException(sprintf('Template %s contract is missing.', $label));
	}
}

$provider = (string) file_get_contents($site . '/src/Service/ProjectTemplateProvider.php');

foreach (['template_defaults', 'profile_params_json', 'project_params_json', 'presentationOverrides'] as $layer) {
	if (!str_contains($provider, $layer)) {
		throw new RuntimeException(sprintf('Frontend template layer is missing: %s.', $layer));
	}
}

$raceConsumers = [
	$site . '/src/View/Standings/HtmlView.php' => ['show_categories', 'show_team'],
	$site . '/tmpl/standings/default.php' => ['show_filters'],
	$site . '/tmpl/results/default.php' => ['show_splits'],
];
foreach ($raceConsumers as $file => $settings) {
	$source = (string) file_get_contents($file);
	foreach ($settings as $setting) {
		if (!str_contains($source, $setting)) {
			throw new RuntimeException(sprintf('Race template setting %s has no frontend consumer.', $setting));
		}
	}
}

$eventReport = (string) file_get_contents($site . '/tmpl/eventreport/default.php');
foreach (['section_order', 'activity_first', 'statistics_first', "sectionOrder['lineup']", "sectionOrder['statistics']"] as $needle) {
	if (!str_contains($eventReport, $needle)) {
		throw new RuntimeException(sprintf('Event-report section ordering consumer is incomplete: %s.', $needle));
	}
}

$languageKeys = [];

foreach ($definitions['definitions'] as $code => $definition) {
	if (!is_array($definition) || !is_array($definition['fields'] ?? null) || !is_array($definition['defaults'] ?? null)) {
		throw new RuntimeException(sprintf('Template definition %s is incomplete.', $code));
	}

	$languageKeys[(string) $definition['name_key']] = true;
	$languageKeys[(string) $definition['description_key']] = true;

	foreach ($definition['fields'] as $name => $field) {
		if (!array_key_exists($name, $definition['defaults'])) {
			throw new RuntimeException(sprintf('Template %s field %s has no registry default.', $code, $name));
		}

		$languageKeys[(string) $field['label_key']] = true;
		$languageKeys[(string) $field['description_key']] = true;
		foreach ($field['enum'] ?? [] as $option) {
			$languageKeys['COM_JOOMLEAGUE_TEMPLATE_OPTION_' . strtoupper((string) $option)] = true;
		}
	}
}

foreach (['en-GB', 'cs-CZ'] as $tag) {
	$ini = parse_ini_file($admin . '/language/' . $tag . '/com_joomleague.ini', false, INI_SCANNER_RAW);

	if (!is_array($ini)) {
		throw new RuntimeException(sprintf('Cannot parse %s administrator language.', $tag));
	}

	foreach (array_keys($languageKeys) as $key) {
		if (!array_key_exists($key, $ini)) {
			throw new RuntimeException(sprintf('%s is missing template language key %s.', $tag, $key));
		}
	}
}

printf("Template contract OK: %d definitions, profile/project editors and five resolution layers validated\n", count($definitions['definitions']));
