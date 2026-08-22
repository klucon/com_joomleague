<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/UuidFactory.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/ClubRelatedRecordCreator.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Administrator\Service\ClubRelatedRecordCreator;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

$container = Factory::getContainer();
$db = $container->get(DatabaseInterface::class);
$name = 'Club related records fixture ' . bin2hex(random_bytes(4));
$club = (object) ['uuid' => UuidFactory::v4(), 'name' => $name, 'short_name' => 'Fixture', 'created' => gmdate('Y-m-d H:i:s')];
$db->insertObject('#__joomleague_club', $club, 'id');
$clubId = (int) $club->id;

try {
	(new ClubRelatedRecordCreator($db))->create($clubId, ['name' => $name, 'short_name' => 'Fixture'], true, true, 0);
	$query = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_team'))->where($db->quoteName('club_id') . ' = :clubId')->bind(':clubId', $clubId, ParameterType::INTEGER);
	if ((int) $db->setQuery($query)->loadResult() !== 1) throw new RuntimeException('Related team was not created.');
	$query = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_venue'))->where($db->quoteName('owner_club_id') . ' = :clubId')->bind(':clubId', $clubId, ParameterType::INTEGER);
	if ((int) $db->setQuery($query)->loadResult() !== 1) throw new RuntimeException('Related venue was not created.');
	echo "Club related team and venue creation OK\n";
} finally {
	foreach ([['#__joomleague_team', 'club_id'], ['#__joomleague_venue', 'owner_club_id']] as [$table, $column]) {
		$query = $db->getQuery(true)->delete($db->quoteName($table))->where($db->quoteName($column) . ' = :clubId')->bind(':clubId', $clubId, ParameterType::INTEGER);
		$db->setQuery($query)->execute();
	}
	$query = $db->getQuery(true)->delete($db->quoteName('#__joomleague_club'))->where($db->quoteName('id') . ' = :clubId')->bind(':clubId', $clubId, ParameterType::INTEGER);
	$db->setQuery($query)->execute();
}
