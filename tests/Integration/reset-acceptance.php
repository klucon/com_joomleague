<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' || !str_starts_with((string) getenv('JL_R1_DB_HOST'), 'jl-r1-')) throw new RuntimeException('Disposable target required');
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
$container = Joomla\CMS\Factory::getContainer();
$container->alias('session', 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
$app = $container->get(Joomla\CMS\Application\SiteApplication::class);
Joomla\CMS\Factory::$application = $app;
if ($app->get('host') !== getenv('JL_R1_DB_HOST') || $app->get('db') !== 'r1_test' || $app->get('dbprefix') !== 'r1_') throw new RuntimeException('Unsafe target');
$app->createExtensionNamespaceMap();
$app->loadLanguage(Joomla\CMS\Language\Language::getInstance('en-GB'));
Joomla\CMS\Factory::$language = $app->getLanguage();
$app->loadIdentity(new Joomla\CMS\User\User());
$app->bootComponent('com_joomleague');
$app->getLanguage()->load('com_joomleague', JPATH_ADMINISTRATOR, 'en-GB', true);
$db = $container->get(Joomla\Database\DatabaseInterface::class);
$mode = $argv[1] ?? '';
if (!in_array($mode, ['seed', 'verify-blocked', 'verify', 'verify-final', 'web-seed', 'web-state'], true)) throw new RuntimeException('Unknown reset acceptance mode');
$file = '/tmp/jl-reset-state.json';
$reportFile = '/tmp/jl-reset-report.json';
$webMode = str_starts_with($mode, 'web-');
$observations = $webMode || $mode === 'seed' ? [] : json_decode(file_get_contents($reportFile), true, 512, JSON_THROW_ON_ERROR)['observations'];
if (!$webMode) register_shutdown_function(static function () use (&$observations, $reportFile): void {
    file_put_contents($reportFile, json_encode(['observations' => $observations], JSON_THROW_ON_ERROR));
});
$check = static function (string $id, bool $ok) use (&$observations): void {
    $observations[] = ['id' => $id, 'status' => 200, 'outcome' => $ok ? 'PASS' : 'FAIL', 'evidenceCount' => 1];
    if (!$ok) throw new RuntimeException('Failed: ' . $id);
    echo 'PASS: ' . $id . "\n";
};
$preserved = array_map(static fn (string $suffix): string => 'r1_joomleague_' . $suffix, [
    'sport_profile', 'sport_profile_version', 'sport_category', 'sport_category_lock',
    'sport_profile_category', 'sport_variant', 'sport_variant_version',
]);
$runtime = [];
$core = [];
foreach ($db->getTableList() as $table) {
    if (!str_starts_with($table, 'r1_')) throw new RuntimeException('Unexpected table prefix');
    if (str_starts_with($table, 'r1_joomleague_')) {
        if (!in_array($table, $preserved, true)) $runtime[] = $table;
    } else {
        $core[] = $table;
    }
}
sort($runtime);
$normalizeRow = static function (array $row): array {
    foreach ($row as &$value) {
        if (is_resource($value)) $value = ['binary' => base64_encode(stream_get_contents($value))];
    }
    unset($value);
    return $row;
};
$hash = static function (array $tables, bool $logical = true) use ($db, $normalizeRow): array {
    $snapshot = [];
    foreach ($tables as $table) {
        $rows = $db->setQuery('SELECT * FROM ' . $db->quoteName($table))->loadAssocList();
        if ($logical && $table === 'r1_assets') {
            $rows = array_values(array_filter($rows, static fn (array $row): bool => preg_match('/^com_joomleague\\.project\\.[1-9][0-9]*$/D', $row['name']) !== 1));
            foreach ($rows as &$asset) unset($asset['lft'], $asset['rgt']);
            unset($asset);
        }
        foreach ($rows as &$row) {
            $row = $normalizeRow($row);
            if ($logical && $table === 'r1_extensions' && ($row['element'] ?? '') === 'com_joomleague' && ($row['type'] ?? '') === 'component') {
                $params = json_decode($row['params'], true, 512, JSON_THROW_ON_ERROR);
                unset($params['allow_full_data_reset']);
                ksort($params);
                $row['params'] = json_encode($params, JSON_THROW_ON_ERROR);
            }
            ksort($row);
            $row = json_encode($row, JSON_THROW_ON_ERROR);
        }
        unset($row);
        sort($rows);
        $snapshot[$table] = hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR));
    }
    ksort($snapshot);
    return $snapshot;
};
$params = static fn () => new Joomla\Registry\Registry((string) $db->setQuery("SELECT params FROM #__extensions WHERE element='com_joomleague' AND type='component'")->loadResult());
$enable = static function () use ($db, $params): void {
    $settings = $params();
    if ($settings->get('telemetry_consent') !== 'never') throw new RuntimeException('Telemetry must remain disabled');
    $settings->set('allow_full_data_reset', 1);
    $db->setQuery('UPDATE #__extensions SET params=' . $db->quote($settings->toString()) . " WHERE element='com_joomleague' AND type='component'")->execute();
};
$empty = static function () use ($db, $runtime): bool {
    foreach ($runtime as $table) {
        if ((int) $db->setQuery('SELECT COUNT(*) FROM ' . $db->quoteName($table))->loadResult() !== 0) return false;
    }
    return true;
};
if ($mode === 'web-seed') {
    if (!$empty()) throw new RuntimeException('Web reset requires an empty disposable runtime');
    $generator = new Joomleague\Component\Joomleague\Administrator\Service\DemoDataGenerator($db);
    $summary = $generator->generate(['football'], 0);
    if ((int) ($summary['football']['project_id'] ?? 0) !== 1) throw new RuntimeException('Web reset positive fixture failed');
    $asset = new Joomla\CMS\Table\Asset($db);
    if (!$asset->loadByName('com_joomleague')) throw new RuntimeException('Component asset missing');
    $parent = (int) $asset->id;
    $asset = new Joomla\CMS\Table\Asset($db);
    $asset->setLocation($parent, 'last-child');
    $asset->parent_id = $parent;
    $asset->name = 'com_joomleague.project.1';
    $asset->title = 'Web reset positive fixture';
    $asset->rules = '{}';
    if (!$asset->check() || !$asset->store()) throw new RuntimeException('Project ACL fixture failed');
    $db->setQuery('UPDATE #__joomleague_project SET asset_id=' . (int) $asset->id . ' WHERE id=1')->execute();
    $app->bootComponent('com_finder');
    $finder = $app->bootPlugin('joomleague', 'finder');
    $items = (new ReflectionMethod($finder, 'getItems'))->invoke($finder, 0, 3);
    foreach ($items as $item) (new ReflectionMethod($finder, 'index'))->invoke($finder, $item);
    (new Joomla\Component\Finder\Administrator\Indexer\Indexer($db))->optimize();
    if ((int) $params()->get('allow_full_data_reset', 1) !== 0) throw new RuntimeException('Web reset must initially be disabled');
    echo json_encode(['seeded' => true], JSON_THROW_ON_ERROR);
    exit(0);
}
if ($mode === 'web-state') {
    $counts = [];
    foreach ($runtime as $table) $counts[$table] = (int) $db->setQuery('SELECT COUNT(*) FROM ' . $db->quoteName($table))->loadResult();
    $coreTables = array_map(static fn (string $suffix): string => 'r1_' . $suffix, ['users', 'usergroups', 'user_usergroup_map', 'assets', 'content', 'menu', 'extensions', 'schemas']);
    $assets = $db->setQuery('SELECT name FROM #__assets')->loadColumn();
    $finderTables = array_values(array_filter($core, static fn (string $table): bool => str_starts_with($table, 'r1_finder_')));
    echo json_encode([
        'runtime' => $hash($runtime, false), 'counts' => $counts, 'profiles' => $hash($preserved, false),
        'core' => $hash($coreTables), 'assets' => $hash(['r1_assets'], false), 'finder' => $hash($finderTables, false),
        'params' => $params()->toArray(), 'enabled' => (int) $params()->get('allow_full_data_reset', 0),
        'project_assets' => count(array_filter($assets, static fn (string $name): bool => preg_match('/^com_joomleague\\.project\\.[1-9][0-9]*$/D', $name) === 1)),
        'finder_links' => (int) $db->setQuery("SELECT COUNT(*) FROM #__finder_links WHERE url LIKE 'index.php?option=com_joomleague&%'")->loadResult(),
    ], JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
    exit(0);
}
if ($mode === 'seed') {
    $declared = (new ReflectionClass(Joomleague\Component\Joomleague\Administrator\Service\RuntimeDataResetter::class))->getConstant('TABLES');
    $declared = array_map($db->replacePrefix(...), $declared);
    sort($declared);
    $check('reset-inventory', count($runtime) > 40 && $declared === $runtime);
    $app->bootComponent('com_finder');
    $finder = $app->bootPlugin('joomleague', 'finder');
    $items = (new ReflectionMethod($finder, 'getItems'))->invoke($finder, 0, 1);
    if (count($items) !== 1) throw new RuntimeException('Search positive control missing');
    $control = $items[0];
    $control->url = 'index.php?option=com_joomleague_extra&view=article&id=98765';
    $control->route = $control->url;
    $control->context = 'com_content.article';
    $control->title = 'Unrelated search control';
    $control->body = 'Unrelated search control';
    $control->params = new Joomla\Registry\Registry();
    $control->metadata = new Joomla\Registry\Registry();
    $indexer = new Joomla\Component\Finder\Administrator\Indexer\Indexer($db);
    $indexer->index($control);
    // Complete the indexing batch before taking a healthy-index reset baseline.
    $indexer->optimize();
    $asset = new Joomla\CMS\Table\Asset($db);
    $asset->setLocation(1, 'last-child');
    $asset->parent_id = 1;
    $asset->name = 'comXjoomleague.project.98765';
    $asset->title = 'Unrelated permission control';
    $asset->rules = '{}';
    if (!$asset->check() || !$asset->store()) throw new RuntimeException('Unrelated permission control could not be created');
    $core = array_values(array_filter($core, static fn (string $table): bool => !str_starts_with($table, 'r1_finder_')));
    $enable();
    $searchControl = $normalizeRow($db->setQuery('SELECT * FROM #__finder_links WHERE url=' . $db->quote($control->url))->loadAssoc());
    if (!$searchControl || (int) $db->setQuery("SELECT COUNT(*) FROM #__assets WHERE name LIKE 'com_joomleague.project.%'")->loadResult() < 1) throw new RuntimeException('Reset reference positive controls missing');
    $allTables = $db->getTableList();
    $baseline = ['core' => $hash($core), 'core_tables' => $core, 'profiles' => $hash($preserved), 'runtime' => $runtime, 'search_control' => $searchControl, 'all_tables' => $allTables, 'raw' => $hash($allTables, false)];
    if ((int) $db->setQuery('SELECT COUNT(*) FROM #__finder_terms AS term WHERE term.links <> (SELECT COUNT(*) FROM #__finder_links_terms AS mapping WHERE mapping.term_id=term.term_id)')->loadResult() !== 0) throw new RuntimeException('Healthy index baseline required');
    file_put_contents($file, json_encode($baseline, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
    if ((int) $db->setQuery('SELECT COUNT(*) FROM #__joomleague_project')->loadResult() < 1) throw new RuntimeException('Positive reset requires existing data');
    $projectId = (int) $db->setQuery('SELECT MIN(id) FROM #__joomleague_project')->loadResult();
    $idType = $db->getName() === 'pgsql' ? 'BIGINT' : 'BIGINT UNSIGNED';
    $db->setQuery('CREATE TABLE r1_reset_external_reference (project_id ' . $idType . ' NOT NULL, CONSTRAINT r1_reset_external_fk FOREIGN KEY (project_id) REFERENCES r1_joomleague_project(id) ON DELETE CASCADE)')->execute();
    $externalReference = (object) ['project_id' => $projectId];
    $db->insertObject('r1_reset_external_reference', $externalReference);
    exit(0);
}
$baseline = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
$core = $baseline['core_tables'];
if ($baseline['runtime'] !== $runtime) throw new RuntimeException('Table inventory changed during reset');
$searchPreserved = static fn (): bool => $baseline['search_control'] === $normalizeRow($db->setQuery('SELECT * FROM #__finder_links WHERE link_id=' . (int) $baseline['search_control']['link_id'])->loadAssoc() ?: []);
if ($mode === 'verify-blocked') {
    $check('reset-external-reference-preserved', (int) $db->setQuery('SELECT COUNT(*) FROM r1_reset_external_reference')->loadResult() === 1
        && $baseline['raw'] === $hash($baseline['all_tables'], false));
    $db->setQuery('DROP TABLE r1_reset_external_reference')->execute();
    exit(0);
}
if ($mode === 'verify-final') {
    $check('reset-repeat-empty', $empty() && (int) $params()->get('allow_full_data_reset', 1) === 0);
    $check('reset-final-preserved', $baseline['core'] === $hash($core) && $baseline['profiles'] === $hash($preserved) && $searchPreserved());
    exit(0);
}
$check('reset-runtime-empty', $empty());
$assetNames = $db->setQuery('SELECT name FROM #__assets')->loadColumn();
$projectAssets = array_filter($assetNames, static fn (string $name): bool => preg_match('/^com_joomleague\\.project\\.[1-9][0-9]*$/D', $name) === 1);
$check('reset-references-clean', $projectAssets === []
    && (int) $db->setQuery("SELECT COUNT(*) FROM #__finder_links WHERE url LIKE 'index.php?option=com_joomleague&%'")->loadResult() === 0);
$check('reset-profiles-preserved', $baseline['profiles'] === $hash($preserved));
$check('reset-joomla-preserved', $baseline['core'] === $hash($core));
$check('reset-disabled-after', (int) $params()->get('allow_full_data_reset', 1) === 0);
$check('reset-search-control-preserved', $searchPreserved());
$constraintState = $db->getName() === 'pgsql'
    ? $db->setQuery('SHOW session_replication_role')->loadResult() === 'origin'
    : (int) $db->setQuery('SELECT @@FOREIGN_KEY_CHECKS')->loadResult() === 1;
$check('reset-constraints-active', $constraintState);
$assets = $db->setQuery('SELECT id, parent_id, lft, rgt FROM #__assets')->loadAssocList('id');
$edges = [];
$validTree = true;
foreach ($assets as $asset) {
    $left = (int) $asset['lft'];
    $right = (int) $asset['rgt'];
    $parent = $assets[(int) $asset['parent_id']] ?? null;
    $validTree = $validTree && $left < $right && ((int) $asset['parent_id'] === 0 || ($parent !== null && (int) $parent['lft'] < $left && (int) $parent['rgt'] > $right));
    $edges[] = $left;
    $edges[] = $right;
}
sort($edges);
$check('reset-assets-tree-valid', $validTree && count($assets) > 10 && $edges === range(0, count($edges) - 1));
$orphanTerms = (int) $db->setQuery('SELECT COUNT(*) FROM #__finder_links_terms AS mapping LEFT JOIN #__finder_links AS link ON link.link_id=mapping.link_id LEFT JOIN #__finder_terms AS term ON term.term_id=mapping.term_id WHERE link.link_id IS NULL OR term.term_id IS NULL')->loadResult();
$wrongCounts = (int) $db->setQuery('SELECT COUNT(*) FROM #__finder_terms AS term WHERE term.links <> (SELECT COUNT(*) FROM #__finder_links_terms AS mapping WHERE mapping.term_id=term.term_id)')->loadResult();
$check('reset-index-integrity', $orphanTerms === 0 && $wrongCounts === 0);
$identities = [];
foreach ($runtime as $table) {
    if (!array_key_exists('id', $db->getTableColumns($table))) continue;
    if ($db->getName() === 'pgsql') {
        $sequence = $db->setQuery('SELECT pg_get_serial_sequence(' . $db->quote($table) . ", 'id')")->loadResult();
        if ($sequence === null) continue;
        $state = $db->setQuery('SELECT last_value, is_called FROM ' . $db->quoteName($sequence))->loadAssoc();
        $identities[$table] = (int) $state['last_value'] === 1 && in_array($state['is_called'], [false, 0, '0', 'f'], true);
    } else {
        $next = $db->setQuery('SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=' . $db->quote($table))->loadResult();
        if ($next !== null) $identities[$table] = (int) $next === 1;
    }
}
$check('reset-identities', count($identities) > 30 && !in_array(false, $identities, true));
$generator = new Joomleague\Component\Joomleague\Administrator\Service\DemoDataGenerator($db);
$generated = $generator->generate(array_keys($generator::PROFILES), 0);
$check('reset-regenerate', count($generated) === count($generator::PROFILES) && array_map('intval', array_column($generated, 'project_id')) === range(1, count($generator::PROFILES)));
$enable();
