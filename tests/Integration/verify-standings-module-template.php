<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] ??= 'localhost';
$_SERVER['REQUEST_URI'] ??= '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Extension/JoomleagueComponent.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomleague\Module\Standings\Site\Helper\StandingsHelper;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\CMS\Application\SiteApplication::class);
Factory::getApplication()->loadIdentity($container->get(Joomla\CMS\User\UserFactoryInterface::class)->loadUserById(0));
Factory::getApplication()->createExtensionNamespaceMap();
Factory::getApplication()->bootComponent('com_joomleague');

require_once JPATH_ROOT . '/components/com_joomleague/src/Service/ProjectTemplateProvider.php';
require_once JPATH_ROOT . '/components/com_joomleague/src/Service/RankingColumnFilter.php';
require_once JPATH_ROOT . '/components/com_joomleague/src/Service/PublicAccess.php';
$modulePath = getenv('STANDINGS_MODULE_PATH') ?: JPATH_ROOT . '/modules/mod_joomleague_standings';
require_once $modulePath . '/src/Helper/StandingsHelper.php';

$database = $container->get(DatabaseInterface::class);
$query = $database->getQuery(true)
	->select($database->quoteName('snapshot.project_id'))
	->from($database->quoteName('#__joomleague_standing_snapshot', 'snapshot'))
	->innerJoin($database->quoteName('#__joomleague_standing_snapshot_row', 'standing_row') . ' ON standing_row.snapshot_id = snapshot.id')
	->group($database->quoteName('snapshot.project_id'))
	->order($database->quoteName('snapshot.project_id') . ' ASC');
$projectId = (int) $database->setQuery($query, 0, 1)->loadResult();
if ($projectId < 1) {
	throw new RuntimeException('Required published standings fixture is missing.');
}

$helper = new StandingsHelper();
Factory::getApplication()->loadIdentity(new class extends Joomla\CMS\User\User {
	public function getAuthorisedViewLevels(): array { return [2147483647]; }
});
$denied = $helper->getStandings(new Registry(['project_id' => $projectId]));
if (($denied['error'] ?? '') !== 'MOD_JOOMLEAGUE_STANDINGS_UNAVAILABLE' || isset($denied['project']) || isset($denied['rows'])) {
	throw new RuntimeException('Unauthorized view level exposed standings.');
}
Factory::getApplication()->loadIdentity($container->get(Joomla\CMS\User\UserFactoryInterface::class)->loadUserById(0));
if (!isset($helper->getStandings(new Registry(['project_id' => 2147483647]))['error'])) {
	throw new RuntimeException('Unknown project was exposed.');
}
$inherited = $helper->getStandings(new Registry(['project_id' => $projectId]));
if (isset($inherited['error']) || ($inherited['columns'] ?? []) === []) {
	throw new RuntimeException('Standings module could not render inherited project-template columns.');
}

$hidden = $helper->getStandings(new Registry([
	'project_id' => $projectId,
	'template_show_score' => '0',
	'template_show_goal_difference' => '0',
	'template_show_sets' => '0',
	'template_show_points' => '0',
]));
if (isset($hidden['error'])) {
	throw new RuntimeException('Standings module failed with presentation overrides.');
}
if (count($hidden['columns']) >= count($inherited['columns'])) {
	throw new RuntimeException('Restrictive module presentation overrides did not reduce visible score, difference, set or points columns.');
}
$offset = $helper->getStandings(new Registry(['project_id' => $projectId, 'offset' => 1, 'limit' => 2]));
if (array_column($offset['rows'], 'project_entry_id') !== array_column(array_slice($inherited['rows'], 1, 2), 'project_entry_id')) {
	throw new RuntimeException('Row offset/limit not applied.');
}
$invalidScope = $helper->getStandings(new Registry(['project_id' => $projectId, 'scope' => 'invalid_scope']));
if (!isset($invalidScope['error'])) throw new RuntimeException('Unsupported scope accepted.');
$invalidStage = $helper->getStandings(new Registry(['project_id' => $projectId, 'stage_id' => 2147483647]));
if (!isset($invalidStage['error'])) throw new RuntimeException('Unrelated stage accepted.');
$imageTable = $helper->getStandings(new Registry(['project_id' => $projectId, 'show_images' => 1, 'limit' => 2]));
if (isset($imageTable['error'])) throw new RuntimeException('Participant image query failed.');
foreach ($imageTable['rows'] as $row) {
	if (!isset($row->image) || preg_match('~^(javascript:|data:|//)~i', $row->image)) throw new RuntimeException('Unsafe participant image.');
}
$reader = new Joomleague\Component\Joomleague\Domain\Service\StandingsReader($database);
foreach ($reader->describe($projectId, null)['available_scopes'] as $scope) {
	$table = $helper->getStandings(new Registry(['project_id' => $projectId, 'scope' => $scope]));
	if (($table['error'] ?? '') === 'MOD_JOOMLEAGUE_STANDINGS_UNAVAILABLE') throw new RuntimeException('Profile scope rejected: ' . $scope);
	if (isset($table['snapshot']) && $table['snapshot']->scope_code !== $scope) throw new RuntimeException('Wrong scope snapshot.');
}
$reflection = new ReflectionMethod($helper, 'markZoneBoundaries');
$ties = array_map(static fn ($rank) => (object) ['rank_number' => $rank], [1, 1, 3, 4]);
$reflection->invoke($helper, $ties, new Registry(['zone_top_enabled' => 1, 'zone_top_count' => 2]));
if ($ties[1]->zone_top === null || $ties[0]->zone_top !== null) throw new RuntimeException('Tied-rank zone boundary is incorrect.');

printf("Standings module template OK on %s: project %d, %d inherited/%d restricted columns\n", $database->getServerType(), $projectId, count($inherited['columns']), count($hidden['columns']));
