<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] = 'joomla62.local';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
$container = Joomla\CMS\Factory::getContainer();
$container->alias('session', 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
$app = $container->get(Joomla\CMS\Application\SiteApplication::class);
Joomla\CMS\Factory::$application = $app;
$app->createExtensionNamespaceMap();
$app->loadLanguage(Joomla\CMS\Language\Language::getInstance('en-GB'));
$factory = $app->bootComponent('com_joomleague')->getMVCFactory();
$db = $container->get(Joomla\Database\DatabaseInterface::class);
$candidate = getenv('JOOMLEAGUE_AUDIT_MODEL_DIR');
if (!$candidate || !str_starts_with($candidate, '/tmp/')) throw new RuntimeException('Temporary source candidate required.');
$user = new class extends Joomla\CMS\User\User {
    public int $permittedProject = 0;
    public bool $allowComponent = false;
    public function authorise($action, $assetname = null) { return ($this->allowComponent && $assetname === 'com_joomleague') || ($this->permittedProject > 0 && $assetname === 'com_joomleague.project.' . $this->permittedProject); }
};
$app->loadIdentity($user);
$tables = ['Match' => 'project_match', 'Round' => 'project_round', 'Projectentry' => 'project_entry', 'Entrymember' => 'project_entry_member', 'Standingadjustment' => 'standing_adjustment', 'Stagetransition' => 'stage_transition'];
foreach ($tables as $name => $table) {
    require $candidate . '/' . $name . 'Model.php';
    $record = $db->setQuery('SELECT * FROM #__joomleague_' . $table . ' ORDER BY id', 0, 1)->loadAssoc();
    if (!$record && !in_array($name, ['Standingadjustment', 'Stagetransition'], true)) throw new RuntimeException('Required fixture absent: ' . $name);
    $owner = $record ? (int) ($record['project_id'] ?? 0) : 1;
    if ($name === 'Entrymember') $owner = (int) $db->setQuery('SELECT project_id FROM #__joomleague_project_entry WHERE id=' . (int) $record['entry_id'])->loadResult();
    $user->permittedProject = (int) $db->setQuery('SELECT id FROM #__joomleague_project WHERE id <> ' . $owner . ' ORDER BY id', 0, 1)->loadResult();
    if (!$user->permittedProject) throw new RuntimeException('Two projects required.');
    $data = $record ?? ['id' => 0, 'project_id' => $owner];
    if ($record) $data['project_id'] = $user->permittedProject;
    $model = $factory->createModel($name, 'Administrator', ['ignore_request' => true]);
    $model->setCurrentUser($user);
    $user->permittedProject = $owner;
    $user->allowComponent = true;
    foreach (['canDelete', 'canEditState'] as $methodName) {
        $method = new ReflectionMethod($model, $methodName);
        if ($record && !$method->invoke($model, (object) $record)) {
            throw new RuntimeException($name . ' rejects permitted stored owner for ' . $methodName);
        }
        foreach ([0, -1] as $invalidOwner) {
            $invalid = (object) ['project_id' => $invalidOwner, 'entry_id' => $invalidOwner];
            if ($method->invoke($model, $invalid)) {
                throw new RuntimeException($name . ' accepts invalid owner for ' . $methodName);
            }
        }
        if ($record) {
            $user->permittedProject = 0;
            if ($method->invoke($model, (object) $record)) {
                throw new RuntimeException($name . ' accepts foreign owner for ' . $methodName);
            }
            $user->permittedProject = $owner;
        }
    }
    echo 'PASS: ' . $name . ' native delete/state guards reject invalid context despite component permission'
        . ($record ? '; permitted and foreign stored owners verified' : '; stored-owner positive case requires fixture') . "\n";
    $user->allowComponent = false;
    $user->permittedProject = (int) $db->setQuery('SELECT id FROM #__joomleague_project WHERE id <> ' . $owner . ' ORDER BY id', 0, 1)->loadResult();
    $countBefore = (int) $db->setQuery('SELECT COUNT(*) FROM #__joomleague_' . $table)->loadResult();
    if ($model->save($data) !== false || $model->getError() !== Joomla\CMS\Language\Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED')) {
        throw new RuntimeException($name . ' did not reject at authorization boundary: ' . $model->getError());
    }
    if ((int) $db->setQuery('SELECT COUNT(*) FROM #__joomleague_' . $table)->loadResult() !== $countBefore) throw new RuntimeException('Rejected save changed row count.');
    if ($record) {
        $after = $db->setQuery('SELECT * FROM #__joomleague_' . $table . ' WHERE id=' . (int) $record['id'])->loadAssoc();
        if ($after !== $record) throw new RuntimeException('Rejected save modified the stored record.');
    }
    echo 'PASS: ' . $name . ' native model rejects foreign-owner ' . ($record ? 'edit' : 'creation') . ' before writes' . "\n";
}
