<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' || !str_starts_with((string) getenv('JL_R1_DB_HOST'), 'jl-r1-')) {
    throw new RuntimeException('Disposable database required.');
}
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/administrator/index.php';
$_SERVER['SCRIPT_NAME'] = '/administrator/index.php';
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
$container = Joomla\CMS\Factory::getContainer();
$container->alias('session', 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
$app = $container->get(Joomla\CMS\Application\AdministratorApplication::class);
Joomla\CMS\Factory::$application = $app;
if ($app->get('host') !== getenv('JL_R1_DB_HOST') || $app->get('db') !== 'r1_test' || $app->get('dbprefix') !== 'r1_') {
    throw new RuntimeException('Non-disposable configuration.');
}
$app->createExtensionNamespaceMap();
$db = $container->get(Joomla\Database\DatabaseInterface::class);
$factory = $app->bootComponent('com_joomleague')->getMVCFactory();
$fixture = json_decode(file_get_contents('/tmp/jl-r1-fixture.json'), true, 512, JSON_THROW_ON_ERROR);
$app->loadIdentity($container->get(Joomla\CMS\User\UserFactoryInterface::class)->loadUserById((int) $fixture['users']['a']));
$personId = (int) $fixture['person'];
$model = $factory->createModel('Persons', 'Administrator', ['ignore_request' => true]);
$model->setState('filter.search', 'id:' . $personId);
$model->setState('filter.published', '');
$model->setState('list.ordering', 'assignment_count');
$model->setState('list.direction', 'DESC');
$items = $model->getItems();
if (count($items) !== 1 || (int) $items[0]->membership_count !== 2 || (int) $items[0]->assignment_count !== 2) {
    throw new RuntimeException('Roster memberships must count across both projects.');
}
$db->transactionStart();
try {
    $memberModel = $factory->createModel('Entrymember', 'Administrator', ['ignore_request' => true]);
    $memberData = (array) $memberModel->getItem((int) $fixture['projects']['a']['records']['Entrymember']);
    $before = $db->setQuery('SELECT role_code FROM #__joomleague_project_entry_member WHERE id=' . (int) $memberData['id'])->loadResult();
    if ($memberModel->save(array_replace($memberData, ['role_code' => '<script>invalid</script>']))) {
        throw new RuntimeException('Unknown role accepted outside the pinned profile.');
    }
    $after = $db->setQuery('SELECT role_code FROM #__joomleague_project_entry_member WHERE id=' . (int) $memberData['id'])->loadResult();
    if ($before !== $after) throw new RuntimeException('Rejected role modified the membership.');
    // A historical/unpublished record still belongs to the assignment inventory.
    $member = (int) $fixture['projects']['a']['records']['Entrymember'];
    $db->setQuery('UPDATE #__joomleague_project_entry_member SET published=0 WHERE id=' . $member)->execute();
    $entry = (object) ['uuid' => Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4(),
        'project_id' => (int) $fixture['projects']['a']['id'], 'entry_kind' => 'person', 'person_id' => $personId];
    $db->insertObject('#__joomleague_project_entry', $entry);
    $fresh = $factory->createModel('Persons', 'Administrator', ['ignore_request' => true]);
    $fresh->setState('filter.search', 'id:' . $personId);
    $fresh->setState('filter.published', '');
    $fresh->setState('list.ordering', 'assignment_count');
    $fresh->setState('list.direction', 'ASC');
    $row = $fresh->getItems()[0] ?? null;
    if (!$row || (int) $row->project_count !== 1 || (int) $row->membership_count !== 2 || (int) $row->assignment_count !== 3) {
        throw new RuntimeException('Assignments are record counts, not distinct project counts.');
    }
} finally {
    $db->transactionRollback();
}
$form = simplexml_load_file(JPATH_ADMINISTRATOR . '/components/com_joomleague/forms/person.xml');
if ($form->xpath('//field[@name="role_code" or @name="position_id" or @name="member_person_type"]')) {
    throw new RuntimeException('Contextual roles must not become global person fields.');
}
echo "PASS: S99 roster count, direct entry count, historical membership, same-project multiplicity, contextual identity and forged-role rejection.\n";
require __DIR__ . '/member-selector-acceptance.php';
