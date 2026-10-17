<?php
declare(strict_types=1);
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
$key = $argv[2] ?? '';
if (!preg_match('/^[a-f0-9]{32}$/', $key)) throw new RuntimeException('Invalid fixture key');
$db = Factory::getContainer()->get(DatabaseInterface::class);
$name = 'Mobile test ' . $key;
if (($argv[1] ?? '') === 'cleanup') {
	$query = $db->getQuery(true)->delete($db->quoteName('#__joomleague_person'))->where('last_name = :name')->bind(':name', $name);
	$db->setQuery($query)->execute();
	exit;
}
$db->transactionStart();
try {
	foreach (['Alice <em>Example</em>', 'Bob'] as $index => $firstName) {
		$person = (object) ['uuid' => substr($key, 0, 8) . '-' . substr($key, 8, 4) . '-400' . $index . '-8000-' . substr($key, 20, 12), 'last_name' => $name, 'first_name' => $firstName, 'published' => 1, 'access' => 1];
		$db->insertObject('#__joomleague_person', $person, 'id');
	}
	$db->transactionCommit();
	echo json_encode(['last_name' => $name], JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
	$db->transactionRollback();
	throw $error;
}
