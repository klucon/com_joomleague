<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

foreach (['UuidFactory.php', 'CanonicalJson.php', 'MatchResultValidationException.php', 'MatchResultDecimal.php', 'MatchResultAggregationValidator.php', 'MatchResultPayloadValidator.php', 'MatchResultRepository.php'] as $service) {
	require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service;
}

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultRepository;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
$database = $container->get(DatabaseInterface::class);
$repository = new MatchResultRepository($database);
$query = $database->getQuery(true)
	->select(['sport.code', 'MIN(match.id) AS match_id'])
	->from($database->quoteName('#__joomleague_project_match', 'match'))
	->innerJoin($database->quoteName('#__joomleague_project', 'project') . ' ON project.id = match.project_id')
	->innerJoin($database->quoteName('#__joomleague_sport_type', 'sport') . ' ON sport.id = project.sport_type_id')
	->innerJoin($database->quoteName('#__joomleague_match_result', 'result') . ' ON result.match_id = match.id')
	->group('sport.code')
	->order('sport.code ASC');
$templates = [];

foreach ($database->setQuery($query)->loadObjectList() as $row) {
	$result = $repository->get((int) $row->match_id);
	$participantQuery = $database->getQuery(true)
		->select(['id', 'slot_number'])
		->from($database->quoteName('#__joomleague_match_participant'))
		->where('match_id = ' . (int) $row->match_id)
		->order('slot_number ASC');
	$participantMap = [];
	foreach ($database->setQuery($participantQuery)->loadObjectList() as $participant) {
		$participantMap[(int) $participant->id] = '{{participant_' . (int) $participant->slot_number . '}}';
	}
	$replace = static function (array &$value) use (&$replace, $participantMap): void {
		foreach ($value as $key => &$item) {
			if ($key === 'participant_id' && isset($participantMap[(int) $item])) {
				$item = $participantMap[(int) $item];
			} elseif (is_array($item)) {
				$replace($item);
			}
		}
	};
	$replace($result);
	$templates[(string) $row->code] = $result;
}

echo json_encode($templates, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
