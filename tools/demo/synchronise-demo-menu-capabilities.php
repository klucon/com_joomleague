<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);

$db = $container->get(DatabaseInterface::class);
$projects = $db->setQuery(
	$db->getQuery(true)
		->select(['project.id', 'version.payload_json'])
		->from($db->quoteName('#__joomleague_project', 'project'))
		->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')
		->where('project.published = 1')
)->loadObjectList();

$capabilities = [];
foreach ($projects as $project) {
	$projectId = (int) $project->id;
	$profile = json_decode((string) $project->payload_json, true, 512, JSON_THROW_ON_ERROR);
	$boundProjectId = $projectId;
	$statisticCount = (int) $db->setQuery(
		$db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__joomleague_match_statistic_value'))
			->where('project_id = :projectId')
			->where('published = 1')
			->bind(':projectId', $boundProjectId, ParameterType::INTEGER)
	)->loadResult();
	$capabilities[$projectId] = [
		'statranking' => $statisticCount > 0,
		'resultmatrix' => ($profile['contest']['type'] ?? null) === 'head_to_head',
	];
}

$items = $db->setQuery(
	$db->getQuery(true)
		->select(['id', 'link', 'published'])
		->from($db->quoteName('#__menu'))
		->where('client_id = 0')
		->where('link LIKE ' . $db->quote('index.php?option=com_joomleague%'))
)->loadObjectList();

$changed = 0;
foreach ($items as $item) {
	parse_str((string) parse_url((string) $item->link, PHP_URL_QUERY), $query);
	$projectId = (int) ($query['project_id'] ?? 0);
	$view = (string) ($query['view'] ?? '');
	if ($projectId < 1 || !isset($capabilities[$projectId][$view])) {
		continue;
	}
	$published = $capabilities[$projectId][$view] ? 1 : 0;
	if ((int) $item->published === $published) {
		continue;
	}
	$record = (object) ['id' => (int) $item->id, 'published' => $published];
	$db->updateObject('#__menu', $record, 'id');
	$changed++;
}

echo "Demo menu capability synchronisation: {$changed} publication states changed.\n";
