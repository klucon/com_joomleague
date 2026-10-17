<?php
declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] = getenv('JOOMLA_TEST_HOST') ?: 'joomla62.klucon.cz';
$_SERVER['HTTPS'] = 'on';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
require_once getenv('JOOMLEAGUE_TEST_PROVIDER') ?: JPATH_ROOT . '/components/com_joomleague/src/Service/PublicUrlProvider.php';
foreach (['Administrator' => JPATH_ADMINISTRATOR, 'Site' => JPATH_ROOT] as $client => $root) {
	JLoader::registerNamespace('Joomleague\\Component\\Joomleague\\' . $client, $root . '/components/com_joomleague/src', false, false, 'psr4');
}

use Joomla\CMS\Factory;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Site\Service\PublicUrlProvider;

$container = Factory::getContainer();
$container->get('config')->set('session_metadata', false);
$container->get('config')->set('caching', 0);
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
$app = $container->get(SiteApplication::class);
Factory::$application = $app;
$language = getenv('JOOMLEAGUE_TEST_LANGUAGE') ?: 'en-GB';
$languageMetadata = getenv('JOOMLEAGUE_TEST_LANGUAGE_METADATA');
if ($languageMetadata) {
	$xml = simplexml_load_file($languageMetadata, SimpleXMLElement::class, LIBXML_NONET);
	if (!$xml || (string) $xml->metadata->tag !== $language) throw new RuntimeException('Invalid isolated language metadata.');
	$metadata = [];
	foreach ($xml->metadata->children() as $key => $value) $metadata[$key] = (string) $value;
	$testLanguage = new class($metadata) extends Joomla\CMS\Language\Language {
		public function __construct(array $metadata) {
			parent::__construct('en-GB');
			$this->lang = $metadata['tag'];
			$this->metadata = $metadata;
		}
	};
	$app->loadLanguage($testLanguage);
} else {
	if (!is_file(JPATH_ROOT . '/language/' . $language . '/langmetadata.xml')) {
		throw new RuntimeException('Requested Joomla site language is not installed: ' . $language);
	}
	$app->loadLanguage(Joomla\CMS\Language\Language::getInstance($language));
}
$app->set('unicodeslugs', getenv('JOOMLEAGUE_TEST_UNICODE') === '1');
if ($routerPath = getenv('JOOMLEAGUE_TEST_ROUTER_PATH')) {
	foreach (['RouteAliasRegistry', 'LocalizedNomenuRules', 'Router'] as $class) {
		require_once $routerPath . '/' . $class . '.php';
	}
}
$app->loadIdentity(new User());
$db = $container->get(DatabaseInterface::class);
$guest = new User();
$levels = array_map('intval', $guest->getAuthorisedViewLevels());
$testUsername = getenv('JOOMLEAGUE_TEST_USERNAME') ?: 'ai-developer';
$adminId = (int) $db->setQuery('SELECT MIN(id) FROM ' . $db->quoteName('#__users') . ' WHERE username = ' . $db->quote($testUsername))->loadResult();
if ($adminId < 1) throw new RuntimeException('Existing development account is required.');
$fixtures = getenv('JOOMLEAGUE_TEMP_FIXTURES') === '1';
if ($fixtures) {
	if (!in_array(1, $levels, true) || in_array(2, $levels, true) || !in_array(5, $levels, true)) {
		throw new RuntimeException('Fixture requires guest access levels 1 and 5, but not 2.');
	}
	// Connection-local tables shadow component tables; no persistent records are changed.
	$columns = 'id INTEGER PRIMARY KEY, published INTEGER, access INTEGER, alias VARCHAR(255), name VARCHAR(255)';
	$tables = ['club', 'team', 'person', 'venue', 'competition', 'season', 'sport_type', 'sport_profile_version', 'project_stage', 'project_round', 'project', 'project_match'];
	foreach ($tables as $table) {
		$extra = match ($table) {
			'project' => ', competition_id INTEGER, season_id INTEGER, sport_type_id INTEGER, profile_version_id INTEGER',
			'project_match' => ', project_id INTEGER, stage_id INTEGER, round_id INTEGER, match_number VARCHAR(255)',
			default => '',
		};
		$db->setQuery('CREATE TEMPORARY TABLE ' . $db->quoteName('#__joomleague_' . $table) . ' (' . $columns . $extra . ')')->execute();
	}
	$insert = static function (string $table, array $values) use ($db): void {
		$query = $db->getQuery(true)->insert($db->quoteName('#__joomleague_' . $table))
			->columns($db->quoteName(array_keys($values)))
			->values(implode(',', array_map(static fn ($value): string => $db->quote((string) $value), $values)));
		$db->setQuery($query)->execute();
	};
	foreach (range(1001, 1009) as $id) {
		$base = ['id' => $id, 'published' => 1, 'access' => 1, 'alias' => 'cesky-klub-' . $id, 'name' => 'Český klub ' . $id];
		foreach (['club', 'team', 'person', 'venue'] as $table) {
			$entity = $base;
			if ($id === 1002) $entity['published'] = 0;
			if ($id === 1003 || $id === 1004) $entity['access'] = 2;
			$insert($table, $entity);
		}
		foreach (['competition', 'season', 'sport_type', 'sport_profile_version', 'project_stage', 'project_round'] as $table) {
			$parent = $base;
			if (($id === 1004 && $table === 'competition') || ($id === 1005 && $table === 'season')) $parent['access'] = 2;
			if (($id === 1006 && $table === 'sport_type') || ($id === 1008 && $table === 'project_stage') || ($id === 1009 && $table === 'project_round')) $parent['published'] = 0;
			$insert($table, $parent);
		}
		$project = $base + ['competition_id' => $id, 'season_id' => $id, 'sport_type_id' => $id, 'profile_version_id' => $id];
		if ($id === 1002) $project['published'] = 0;
		if ($id === 1003) $project['access'] = 2;
		// Guest level 5 is independent of the inaccessible lower-numbered level 2.
		if ($id === 1004) $project['access'] = 5;
		$insert('project', $project);
		$insert('project_match', $base + ['project_id' => $id, 'stage_id' => $id, 'round_id' => $id, 'match_number' => 'Round ' . $id]);
	}
	// Minimal connection-local Joomla menu: a private direct link must never win.
	$db->setQuery('CREATE TEMPORARY TABLE ' . $db->quoteName('#__menu') . ' ('
		. 'id INTEGER PRIMARY KEY, menutype VARCHAR(255), title VARCHAR(255), alias VARCHAR(255), note VARCHAR(255), '
		. 'link VARCHAR(255), type VARCHAR(255), level INTEGER, language VARCHAR(255), '
		. $db->quoteName('browserNav') . ' INTEGER, access INTEGER, params TEXT, home INTEGER, img VARCHAR(255), '
		. 'template_style_id INTEGER, component_id INTEGER, parent_id INTEGER, path VARCHAR(255), '
		. 'published INTEGER, client_id INTEGER, publish_up TIMESTAMP NULL, publish_down TIMESTAMP NULL, lft INTEGER)')->execute();
	$componentId = (int) $db->setQuery('SELECT extension_id FROM ' . $db->quoteName('#__extensions')
		. ' WHERE element = ' . $db->quote('com_joomleague') . ' AND type = ' . $db->quote('component'))->loadResult();
	$menu = (object) [
		'id' => 900001, 'menutype' => 'test', 'title' => 'Private sitemap fixture',
		'alias' => 'private-sitemap-fixture', 'note' => '',
		'link' => 'index.php?option=com_joomleague&view=club&club_id=1001',
		'type' => 'component', 'level' => 1, 'language' => '*', 'browserNav' => 0,
		'access' => 2, 'params' => '{}', 'home' => 0, 'img' => '',
		'template_style_id' => 0, 'component_id' => $componentId, 'parent_id' => 1,
		'path' => 'private-sitemap-fixture', 'published' => 1, 'client_id' => 0,
		'publish_up' => null, 'publish_down' => null, 'lft' => 2,
	];
	$db->insertObject('#__menu', $menu);
}
$db->setQuery($db->getName() === 'pgsql'
	? 'SET SESSION CHARACTERISTICS AS TRANSACTION READ ONLY'
	: 'SET SESSION TRANSACTION READ ONLY')->execute();
$provider = new PublicUrlProvider($db);
$samples = [];
$summary = [];

// Prime the normal cached router as an administrator before public discovery.
$app->loadIdentity(new User($adminId));
$adminMenu = new Joomla\CMS\Menu\SiteMenu(['app' => $app, 'db' => $db, 'language' => $app->getLanguage(), 'user' => new User($adminId)]);
$adminRouter = new Joomla\CMS\Router\SiteRouter($app, $adminMenu);
Joomla\CMS\Router\Route::link('site', 'index.php?option=com_joomleague&view=projects', false, 0, true);
if ($fixtures) {
	$privateUrl = $adminRouter->build('index.php?option=com_joomleague&view=club&club_id=1001')->toString();
	if (!str_contains($privateUrl, 'private-sitemap-fixture')) throw new RuntimeException('Private menu fixture did not affect administrator router: ' . $privateUrl);
}

foreach ($provider->types() as $type) {
	$app->loadIdentity(new User($adminId));
	$adminFirst = $provider->read($type, 0, 2);
	$app->loadIdentity($guest);
	$first = $provider->read($type, 0, 2);
	if ($adminFirst !== $first) throw new RuntimeException('Administrator-first routing differs: ' . $type);
	$repeat = $provider->read($type, 0, 2);
	if ($first !== $repeat) throw new RuntimeException('Unstable batch: ' . $type . ' ' . json_encode([$first, $repeat], JSON_THROW_ON_ERROR));
	$cursor = $first === [] ? 0 : end($first)['id'];
	$next = $provider->read($type, $cursor, 2);
	$app->loadIdentity(new User($adminId));
	if ($first !== $provider->read($type, 0, 2)) throw new RuntimeException('Caller identity leaked into discovery: ' . $type);
	$rows = array_merge($first, $next);
	$ids = array_column($rows, 'id');
	$sorted = $ids;
	sort($sorted);
	if ($ids !== $sorted || count($ids) !== count(array_unique($ids))) throw new RuntimeException('Invalid cursor boundary: ' . $type);
	if ($fixtures) {
		$expected = match ($type) { 'project' => [1001, 1007, 1008, 1009], 'eventreport' => [1001, 1007], default => [1001, 1005, 1006, 1007] };
		if ($ids !== $expected) throw new RuntimeException('Fixture publication/parent ACL mismatch: ' . $type);
	}
	if ($provider->read($type, 2147483647, 2) !== []) throw new RuntimeException('Terminal cursor failed: ' . $type);
	foreach ($rows as $row) {
		if (str_contains($row['url'], 'private-sitemap-fixture')) throw new RuntimeException('Private menu leaked into public URL.');
		if ($fixtures && $routerPath) {
			$aliases = new Joomleague\Component\Joomleague\Site\Service\RouteAliasRegistry($language, (bool) $app->get('unicodeslugs'));
			if (!str_contains(rawurldecode($row['url']), '/' . $aliases->aliasFor($type) . '/')) {
				throw new RuntimeException('Localized route mismatch: ' . $type);
			}
		}
		if (!str_starts_with($row['url'], 'https://' . $_SERVER['HTTP_HOST'] . '/') || str_contains($row['url'], '&amp;') || $row['lastmod'] !== null) throw new RuntimeException('Invalid routed URL: ' . $type);
		$table = $type === 'eventreport' ? 'project_match' : $type;
		$item = $db->setQuery('SELECT * FROM ' . $db->quoteName('#__joomleague_' . $table) . ' WHERE id = ' . (int) $row['id'])->loadObject();
		if (!$item || (int) $item->published !== 1) throw new RuntimeException('Unpublished item leaked.');
		if (!in_array($type, ['project', 'eventreport'], true) && !in_array((int) $item->access, $levels, true)) throw new RuntimeException('Private entity leaked.');
		if (in_array($type, ['project', 'eventreport'], true)) {
			$project = $type === 'project' ? $item : $db->setQuery('SELECT * FROM ' . $db->quoteName('#__joomleague_project') . ' WHERE id = ' . (int) $item->project_id)->loadObject();
			if (!$project || (int) $project->published !== 1 || !in_array((int) $project->access, $levels, true)) throw new RuntimeException('Private project leaked.');
			foreach (['competition', 'season', 'sport_type'] as $parent) {
				$field = $parent . '_id';
				$record = $db->setQuery('SELECT * FROM ' . $db->quoteName('#__joomleague_' . $parent) . ' WHERE id = ' . (int) $project->$field)->loadObject();
				if (!$record || (int) $record->published !== 1 || ($parent !== 'sport_type' && !in_array((int) $record->access, $levels, true))) throw new RuntimeException('Private parent leaked.');
			}
		}
	}
	$summary[$type] = count($rows);
	$samples[$type] = $rows;
}
foreach ([['users', 0, 2], ['club', -1, 2], ['club', 0, 1001]] as $invalid) {
	try { $provider->read(...$invalid); } catch (InvalidArgumentException) { continue; }
	throw new RuntimeException('Invalid batch accepted.');
}
echo json_encode(['driver' => $db->getName(), 'language' => $language, 'isolatedLanguageMetadata' => (bool) $languageMetadata, 'unicode' => (bool) $app->get('unicodeslugs'), 'fixtures' => $fixtures, 'guestLevels' => $levels, 'counts' => $summary, 'samples' => $samples], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . PHP_EOL;
