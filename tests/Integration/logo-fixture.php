<?php
declare(strict_types=1);
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
$key = $argv[2] ?? '';
if (!preg_match('/^[a-f0-9]{32}$/', $key)) throw new RuntimeException('Invalid fixture key');
$db = Factory::getContainer()->get(DatabaseInterface::class);
$manifest = '/tmp/jl-logo-' . $key . '.json';
$image = 'images/jl-logo-' . $key . '.png';
if (($argv[1] ?? '') === 'restrict') {
	$data = json_decode(file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
	$id = (int) $data['club'];
	$query = $db->getQuery(true)->update($db->quoteName('#__joomleague_club'))->set('access = 2')->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
	$db->setQuery($query)->execute();
	exit;
}
if (($argv[1] ?? '') === 'cleanup') {
	$data = json_decode(file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
	foreach (['team', 'club'] as $table) {
		$id = (int) $data[$table];
		$query = $db->getQuery(true)->delete($db->quoteName('#__joomleague_' . $table))->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
		$db->setQuery($query)->execute();
	}
	unlink(JPATH_ROOT . '/' . $image);
	unlink($manifest);
	exit;
}
if (file_exists($manifest) || file_exists(JPATH_ROOT . '/' . $image)) throw new RuntimeException('Fixture exists');
$db->transactionStart();
try {
	if (!copy(JPATH_ROOT . '/media/com_joomleague/brand/joomleague-logo.png', JPATH_ROOT . '/' . $image)) throw new RuntimeException('Cannot create fixture image');
	$club = (object) ['uuid' => substr($key, 0, 8) . '-' . substr($key, 8, 4) . '-4000-8000-' . substr($key, 20, 12), 'name' => 'Logo test ' . $key, 'logo' => $image, 'published' => 1, 'access' => 1];
	$db->insertObject('#__joomleague_club', $club, 'id');
	$team = (object) ['uuid' => substr($key, 0, 8) . '-' . substr($key, 8, 4) . '-4001-8000-' . substr($key, 20, 12), 'name' => 'Inherited logo test ' . $key, 'club_id' => $club->id, 'published' => 1, 'access' => 1];
	$db->insertObject('#__joomleague_team', $team, 'id');
	$data = ['club' => (int) $club->id, 'team' => (int) $team->id, 'image' => '/' . $image];
	file_put_contents($manifest, json_encode($data, JSON_THROW_ON_ERROR));
	$db->transactionCommit();
	echo json_encode($data, JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
	$db->transactionRollback();
	if (file_exists(JPATH_ROOT . '/' . $image)) unlink(JPATH_ROOT . '/' . $image);
	if (file_exists($manifest)) unlink($manifest);
	throw $error;
}
