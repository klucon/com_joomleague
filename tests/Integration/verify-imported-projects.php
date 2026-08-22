<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

foreach (['CanonicalJson.php','StandingsContractValidator.php','SportProfileSchemaValidator.php','EntryModelValidator.php','MatchResultValidationException.php','MatchResultDecimal.php','MatchResultAggregationValidator.php','MatchResultPayloadValidator.php','MatchResultRepository.php','ProjectPreflightService.php'] as $service) {
	require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service;
}

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectPreflightService;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
$database = $container->get(DatabaseInterface::class);
$projectIds = array_map('intval', $database->setQuery(
	$database->getQuery(true)->select('id')->from($database->quoteName('#__joomleague_project'))->order('id ASC')
)->loadColumn());
$service = new ProjectPreflightService($database);
$totals = ['projects' => count($projectIds), 'ready' => 0, 'errors' => 0, 'warnings' => 0];
$failures = [];
$projectOne = null;

foreach ($projectIds as $projectId) {
	$result = $service->inspect($projectId);
	$totals['ready'] += $result['ready'] ? 1 : 0;
	$totals['errors'] += $result['summary']['error'];
	$totals['warnings'] += $result['summary']['warning'];
	if ($projectId === 1) {
		$projectOne = [
			'ready' => $result['ready'],
			'errors' => $result['summary']['error'],
			'warnings' => $result['summary']['warning'],
		];
	}
	if (!$result['ready']) {
		$codes = [];
		foreach ($result['sections'] as $section) foreach ($section['checks'] as $check) if ($check['severity'] === 'error') $codes[] = $check['code'];
		$failures[(string) $projectId] = array_count_values($codes);
	}
}

echo json_encode(['summary' => $totals, 'project_1' => $projectOne, 'failures' => $failures], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . PHP_EOL;
if ($totals['errors'] > 0) exit(2);
