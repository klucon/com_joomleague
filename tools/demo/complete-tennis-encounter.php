<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit(1); }
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
$db = Joomla\CMS\Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
$name = 'Tennis team encounter demo 2026';
$q = $db->getQuery(true)->select('m.id')->from('#__joomleague_project_match AS m')->join('INNER', '#__joomleague_project AS p ON p.id=m.project_id')->where('p.name = :name')->bind(':name', $name);
$ids = $db->setQuery($q)->loadColumn();
if (count($ids) !== 1) { throw new RuntimeException('Expected exactly one owned demo match.'); }
$id = (int)$ids[0];
$db->transactionStart();
try {
    $db->setQuery($db->getQuery(true)->update('#__joomleague_project_match')->set("status_code='completed'")->set("scheduled_start='2026-10-03 10:00:00'")->set("timezone='Europe/Prague'")->set('description=' . $db->quote('Fictional tennis team encounter: four singles and one doubles rubber. Demonstration data only.'))->where('id = :id')->bind(':id', $id))->execute();
    $db->setQuery($db->getQuery(true)->update('#__joomleague_match_participant')->set("result_status='completed'")->where('match_id = :id')->bind(':id', $id))->execute();
    $db->transactionCommit();
    echo "Completed demo match $id\n";
} catch (Throwable $e) { $db->transactionRollback(); throw $e; }
