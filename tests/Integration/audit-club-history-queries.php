<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
require JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/ClubPersonHistoryReader.php';
$db = Joomla\CMS\Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
if ($db->getServerType() !== 'mysql') throw new RuntimeException('MariaDB diagnostic only.');
// Bound each read on this connection; no business data or server configuration is changed.
$db->setQuery('SET SESSION max_statement_time=5')->execute();
$clubId = (int) $db->setQuery('SELECT t.club_id FROM #__joomleague_project_entry_member AS m INNER JOIN #__joomleague_project_entry AS e ON e.id=m.entry_id INNER JOIN #__joomleague_team AS t ON t.id=e.team_id WHERE m.published=1 ORDER BY t.club_id', 0, 1)->loadResult();
if ($clubId < 1) throw new RuntimeException('Existing club required; no fixtures are created.');
$reader = new Joomleague\Component\Joomleague\Domain\Service\ClubPersonHistoryReader($db);
$filters = ['project_id' => 0, 'season_id' => 0, 'project_type' => '', 'person_type' => ''];
$observations = [];
foreach (['1', '1,2,3,4,5,6,7,8'] as $levels) {
    foreach (['membershipQuery', 'lineupQuery', 'eventQuery', 'managerQuery'] as $name) {
        $query = (new ReflectionMethod($reader, $name))->invoke($reader, $clubId, $levels, $filters);
        $query->clear('select')->select('COUNT(*) AS row_count');
        if ($name === 'managerQuery') $query->select('COALESCE(SUM(OCTET_LENGTH(profile.payload_json)),0) AS profile_bytes');
        $start = microtime(true);
        try {
            $row = $db->setQuery($query)->loadAssoc();
            $observations[] = ['levels' => $levels, 'query' => $name, 'rows' => (int) $row['row_count'], 'profileBytes' => isset($row['profile_bytes']) ? (int) $row['profile_bytes'] : null, 'seconds' => round(microtime(true) - $start, 3)];
        } catch (Throwable $error) {
            $observations[] = ['levels' => $levels, 'query' => $name, 'errorClass' => get_class($error), 'seconds' => round(microtime(true) - $start, 3)];
        }
    }
}
echo json_encode(['clubId' => $clubId, 'mode' => 'read-only bounded aggregate diagnostic', 'observations' => $observations], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
