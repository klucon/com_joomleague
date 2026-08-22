<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\SportTypeProfileMaterializer;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);

foreach (['CanonicalJson', 'UuidFactory', 'SportTypeProfileMaterializer'] as $service) {
	require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service . '.php';
}

$database = $container->get(DatabaseInterface::class);
$query = $database->getQuery(true)
	->select('version.id')
	->from($database->quoteName('#__joomleague_sport_profile_version', 'version'))
	->innerJoin($database->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id = version.profile_id')
	->where('profile.code = ' . $database->quote('basketball'))
	->where('version.state = ' . $database->quote('active'))
	->order('version.id DESC');
$profileVersionId = (int) $database->setQuery($query, 0, 1)->loadResult();
if ($profileVersionId < 1) throw new RuntimeException('Basketball profile is unavailable.');

$database->transactionStart();
try {
	$row = (object) ['profile_version_id' => $profileVersionId, 'code' => 'materializer_' . bin2hex(random_bytes(4)), 'name' => 'Materializer test'];
	$database->insertObject('#__joomleague_sport_type', $row, 'id');
	$sportTypeId = (int) $row->id;
	$counts = (new SportTypeProfileMaterializer($database))->materialize($sportTypeId, $profileVersionId, [
		'positions' => true,
		'event_types' => false,
		'statistics' => true,
	], 0);

	if ($counts !== ['positions' => 8, 'event_types' => 0, 'statistics' => 8]) {
		throw new RuntimeException('Unexpected materialization counts: ' . json_encode($counts));
	}

	foreach (['sport_position' => 8, 'event_type' => 0, 'statistic' => 8] as $table => $expected) {
		$query = $database->getQuery(true)->select('COUNT(*)')->from($database->quoteName('#__joomleague_' . $table))->where('sport_type_id = :sportTypeId')->bind(':sportTypeId', $sportTypeId, Joomla\Database\ParameterType::INTEGER);
		if ((int) $database->setQuery($query)->loadResult() !== $expected) throw new RuntimeException('Selective materialization failed for ' . $table . '.');
	}

	printf("Sport-type materializer OK on %s: independent switches and provenance persisted\n", $database->getName());
} finally {
	$database->transactionRollback();
}
