<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' || !str_starts_with((string) getenv('JL_R1_DB_HOST'), 'jl-r1-')) throw new RuntimeException('Disposable target required');
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/administrator/index.php';
$_SERVER['REQUEST_URI'] = '/administrator/';
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
$container = Joomla\CMS\Factory::getContainer();
$container->alias('session', 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
$app = $container->get(Joomla\CMS\Application\AdministratorApplication::class);
Joomla\CMS\Factory::$application = $app;
if ($app->get('host') !== getenv('JL_R1_DB_HOST') || $app->get('db') !== 'r1_test' || $app->get('dbprefix') !== 'r1_') throw new RuntimeException('Unsafe target');
$app->createExtensionNamespaceMap();
$db = $container->get(Joomla\Database\DatabaseInterface::class);
$mode = $argv[1] ?? '';
$manifest = simplexml_load_file('/opt/suite/pkg_joomleague.xml');
if (!$manifest) throw new RuntimeException('Target package manifest required');
$packageId = (int) $db->setQuery("SELECT extension_id FROM #__extensions WHERE type='package' AND element='pkg_joomleague'")->loadResult();
if ($packageId < 1) throw new RuntimeException('Installed package required');
$rows = static function (string $table) use ($db): array {
    $records = $db->setQuery('SELECT * FROM ' . $db->quoteName($table))->loadAssocList();
    if ($table === 'r1_menu') {
        $componentIds = array_map('intval', $db->setQuery("SELECT extension_id FROM #__extensions WHERE element IN ('com_joomleague','com_kluconcountries') AND type='component'")->loadColumn());
        $records = array_values(array_filter($records, static fn (array $row): bool => (int) $row['client_id'] !== 1 || !in_array((int) $row['component_id'], $componentIds, true)));
        foreach ($records as &$record) unset($record['lft'], $record['rgt']);
        unset($record);
    }
    if ($table === 'r1_assets') {
        foreach ($records as &$record) unset($record['lft'], $record['rgt']);
        unset($record);
    }
    foreach ($records as &$row) {
        foreach ($row as &$value) if (is_resource($value)) $value = base64_encode(stream_get_contents($value));
        unset($value);
        ksort($row);
    }
    unset($row);
    usort($records, static fn (array $a, array $b): int => isset($a['id'], $b['id']) ? (int) $a['id'] <=> (int) $b['id'] : strcmp(json_encode($a, JSON_THROW_ON_ERROR), json_encode($b, JSON_THROW_ON_ERROR)));
    return $records;
};
$snapshot = static function () use ($db, $rows): array {
    $preserved = ['sport_profile', 'sport_profile_version', 'sport_category', 'sport_category_lock', 'sport_profile_category', 'sport_variant', 'sport_variant_version'];
    $result = [];
    foreach ($db->getTableList() as $table) {
        if (!str_starts_with($table, 'r1_joomleague_') || in_array(substr($table, strlen('r1_joomleague_')), $preserved, true)) continue;
        $result[$table] = $rows($table);
    }
    foreach (['r1_users', 'r1_usergroups', 'r1_user_usergroup_map', 'r1_assets', 'r1_content', 'r1_menu'] as $table) $result[$table] = $rows($table);
    ksort($result);
    return $result;
};
$versions = static function () use ($db, $packageId): array {
    $items = $db->setQuery('SELECT element, folder, manifest_cache FROM #__extensions WHERE extension_id=' . $packageId . ' OR package_id=' . $packageId)->loadAssocList();
    $result = [];
    foreach ($items as $item) $result[$item['element'] . ':' . $item['folder']] = json_decode($item['manifest_cache'], true, 512, JSON_THROW_ON_ERROR)['version'];
    ksort($result);
    return $result;
};
$expectedVersions = static function (string $directory): array {
    $package = simplexml_load_file($directory . '/pkg_joomleague.xml');
    if (!$package) throw new RuntimeException('Package identity unavailable');
    $expected = ['pkg_joomleague:' => (string) $package->version];
    foreach ($package->files->file as $file) {
        $name = (string) $file;
        if (basename($name) !== $name) throw new RuntimeException('Unsafe fixture archive');
        $zip = new ZipArchive();
        if ($zip->open($directory . '/' . $name) !== true) throw new RuntimeException('Missing child archive');
        $matches = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $member = $zip->getNameIndex($i);
            if (str_contains($member, '/') || !str_ends_with($member, '.xml')) continue;
            $xml = simplexml_load_string($zip->getFromIndex($i));
            if ($xml && $xml->getName() === 'extension' && isset($xml->version)) $matches[] = (string) $xml->version;
        }
        $zip->close();
        if (count($matches) !== 1) throw new RuntimeException('Ambiguous child manifest');
        $expected[(string) $file['id'] . ':' . (string) $file['group']] = $matches[0];
    }
    ksort($expected);
    return $expected;
};
if ($mode === 'disconnect-feed') {
    $host = preg_replace('/-(mysqli|pgsql)-db$/D', '-feed', (string) getenv('JL_R1_DB_HOST'));
    $db->setQuery('UPDATE #__update_sites SET enabled=0 WHERE location=' . $db->quote('http://' . $host . ':8080/update.xml'))->execute();
    $sites = json_decode(file_get_contents('/tmp/jl-delivery-sites.json'), true, 512, JSON_THROW_ON_ERROR);
    foreach ($sites as $site) {
        $db->setQuery('UPDATE #__update_sites SET enabled=' . (int) $site['enabled'] . ' WHERE update_site_id=' . (int) $site['update_site_id'])->execute();
    }
    echo json_encode(['disconnected' => true], JSON_THROW_ON_ERROR);
    exit(0);
}
if ($mode === 'configure') {
    $host = preg_replace('/-(mysqli|pgsql)-db$/D', '-feed', (string) getenv('JL_R1_DB_HOST'));
    $url = 'http://' . $host . ':8080/update.xml';
    if (($argv[2] ?? '') !== $url) throw new RuntimeException('Owned feed required');
    if (!is_file('/tmp/jl-delivery-sites.json')) {
        file_put_contents('/tmp/jl-delivery-sites.json', json_encode($db->setQuery('SELECT update_site_id, enabled FROM #__update_sites')->loadAssocList(), JSON_THROW_ON_ERROR));
    }
    $db->setQuery('UPDATE #__update_sites SET enabled=0')->execute();
    $site = (object) ['name' => 'Isolated JoomLeague delivery acceptance', 'type' => 'extension', 'location' => $url, 'enabled' => 1, 'last_check_timestamp' => 0];
    $existing = (int) $db->setQuery('SELECT update_site_id FROM #__update_sites WHERE location=' . $db->quote($url))->loadResult();
    if ($existing > 0) {
        $site->update_site_id = $existing;
        $db->updateObject('#__update_sites', $site, 'update_site_id');
    } else {
        $db->insertObject('#__update_sites', $site, 'update_site_id');
    }
    $linkExists = (int) $db->setQuery('SELECT COUNT(*) FROM #__update_sites_extensions WHERE update_site_id=' . (int) $site->update_site_id . ' AND extension_id=' . $packageId)->loadResult();
    if (!$linkExists) {
        $link = (object) ['update_site_id' => (int) $site->update_site_id, 'extension_id' => $packageId];
        $db->insertObject('#__update_sites_extensions', $link);
    }
    $params = new Joomla\Registry\Registry((string) $db->setQuery("SELECT params FROM #__extensions WHERE element='com_joomleague' AND type='component'")->loadResult());
    $params->set('telemetry_consent', 'never');
    $db->setQuery('UPDATE #__extensions SET params=' . $db->quote($params->toString()) . " WHERE element='com_joomleague' AND type='component'")->execute();
    echo json_encode(['site' => (int) $site->update_site_id, 'url' => $url, 'target' => (string) $manifest->version], JSON_THROW_ON_ERROR);
    exit(0);
}
if ($mode === 'seed') {
    // Component reinstalls rebuild the entire Joomla menu tree, including generated paths.
    $menu = new Joomla\CMS\Table\Menu($db);
    if ($menu->rebuild() === false) throw new RuntimeException('Menu baseline rebuild failed');
    $state = ['rows' => $snapshot(), 'versions' => $versions()];
    if (count($state['versions']) !== 23 || count($state['rows']['r1_joomleague_project']) !== 2) throw new RuntimeException('Legacy positive controls missing');
    $legacy = $expectedVersions('/opt/legacy-suite');
    if ($state['versions'] !== $legacy || !in_array($legacy['pkg_joomleague:'], ['6.2.0-beta6', '6.2.0-rc1'], true)) throw new RuntimeException('Actual supported legacy installation required');
    file_put_contents('/tmp/jl-delivery-before.json', json_encode($state, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
    echo json_encode(['seeded' => true, 'versions' => $state['versions']], JSON_THROW_ON_ERROR);
    exit(0);
}
if ($mode === 'state') {
    $files = [];
    $roots = [JPATH_ADMINISTRATOR . '/components/com_joomleague', JPATH_BASE . '/components/com_joomleague', JPATH_BASE . '/media/com_joomleague'];
    foreach ($manifest->files->file as $child) {
        if ((string) $child['type'] === 'module') $roots[] = JPATH_BASE . '/modules/' . (string) $child['id'];
        if ((string) $child['type'] === 'plugin') $roots[] = JPATH_PLUGINS . '/' . (string) $child['group'] . '/' . (string) $child['id'];
    }
    foreach ($roots as $root) {
      if (!is_dir($root)) { $files[substr($root, strlen(JPATH_BASE))] = '__absent__'; continue; }
      foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->isLink()) throw new RuntimeException('Unexpected extension symlink');
        if ($file->isFile()) $files[substr($file->getPathname(), strlen(JPATH_BASE))] = hash_file('sha256', $file->getPathname());
      }
    }
    ksort($files);
    $data = $snapshot();
    echo json_encode(['data' => hash('sha256', json_encode($data, JSON_THROW_ON_ERROR)), 'table_hashes' => array_map(static fn (array $rows): string => hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR)), $data), 'versions' => $versions(), 'files' => hash('sha256', json_encode($files, JSON_THROW_ON_ERROR))], JSON_THROW_ON_ERROR);
    exit(0);
}
if (in_array($mode, ['verify', 'preserved'], true)) {
    $before = json_decode(file_get_contents('/tmp/jl-delivery-before.json'), true, 512, JSON_THROW_ON_ERROR);
    $after = $snapshot();
    foreach ($before['rows'] as $table => $records) {
        if (count($records) !== count($after[$table] ?? []) && $table !== 'r1_assets') throw new RuntimeException('Update changed row count: ' . $table);
        foreach ($records as $index => $row) {
            if (array_intersect_key($after[$table][$index], $row) !== $row) {
                $changed = array_keys(array_filter($row, static fn ($value, $key): bool => ($after[$table][$index][$key] ?? null) !== $value, ARRAY_FILTER_USE_BOTH));
                throw new RuntimeException('Update changed stored data: ' . $table . ', fields: ' . implode(', ', $changed));
            }
        }
    }
    $installed = $versions();
    $expected = $expectedVersions('/opt/suite');
    if ($mode === 'verify' && $installed !== $expected) throw new RuntimeException('Not all package versions advanced');
    echo json_encode(['preserved' => true, 'versions' => $installed, 'expected_versions' => $expected], JSON_THROW_ON_ERROR);
    exit(0);
}
throw new RuntimeException('Unknown delivery fixture mode');
