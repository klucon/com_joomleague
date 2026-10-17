<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' || !str_starts_with((string) getenv('JL_R1_DB_HOST'), 'jl-r1-')) {
    throw new RuntimeException('Disposable R1 database required.');
}
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/administrator/index.php';
$_SERVER['SCRIPT_NAME'] = '/administrator/index.php';
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Table\Asset;
use Joomla\CMS\Table\Usergroup;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
$app = $container->get(Joomla\CMS\Application\AdministratorApplication::class);
Factory::$application = $app;
if ($app->get('host') !== getenv('JL_R1_DB_HOST') || $app->get('db') !== 'r1_test' || $app->get('dbprefix') !== 'r1_') {
    throw new RuntimeException('Refusing to use a non-disposable Joomla database.');
}
$app->createExtensionNamespaceMap();
$app->loadLanguage(Joomla\CMS\Language\Language::getInstance('en-GB'));
$db = $container->get(DatabaseInterface::class);
$factory = $app->bootComponent('com_joomleague')->getMVCFactory();
$users = $container->get(UserFactoryInterface::class);
$tables = ['Stage'=>'project_stage','Round'=>'project_round','Match'=>'project_match','Projectentry'=>'project_entry','Entrymember'=>'project_entry_member','Standingadjustment'=>'standing_adjustment','Stagetransition'=>'stage_transition'];
$assert = static function (bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); };
$insert = static function (string $table, array $data, bool $uuid = true) use ($db): int {
    if ($uuid) { $data['uuid'] ??= UuidFactory::v4(); $data['published'] ??= 1; }
    $object = (object) $data;
    $db->insertObject('#__' . $table, $object, 'id');
    return (int) $object->id;
};
$row = static function (string $name, int $id) use ($db, $tables): ?array {
    if (!isset($tables[$name])) throw new InvalidArgumentException('Unknown fixture entity.');
    return $db->setQuery('SELECT * FROM ' . $db->quoteName('#__joomleague_' . $tables[$name]) . ' WHERE id=' . $id)->loadAssoc();
};
$action = $argv[1] ?? '';
$stateFile = '/tmp/jl-r1-fixture.json';
if ($action === 'setup') {
    $db->setQuery('UPDATE #__extensions SET enabled=0 WHERE type=' . $db->quote('plugin') . ' AND folder=' . $db->quote('system') . ' AND element=' . $db->quote('guidedtours'))->execute();
    $adminId = (int) $db->setQuery('SELECT id FROM #__users WHERE username=' . $db->quote('admin'))->loadResult();
    $app->loadIdentity($users->loadUserById($adminId));
    $version = (int) $db->setQuery('SELECT v.id FROM #__joomleague_sport_profile_version AS v INNER JOIN #__joomleague_sport_profile AS p ON p.id=v.profile_id WHERE p.code=' . $db->quote('football') . ' ORDER BY v.id DESC',0,1)->loadResult();
    $assert($version > 0, 'Football profile missing.');
    $competition = $insert('joomleague_competition',['name'=>'R1 competition']);
    $season = $insert('joomleague_season',['name'=>'R1 season']);
    $sport = $insert('joomleague_sport_type',['name'=>'R1 football','code'=>'r1_football','profile_version_id'=>$version],false);
    $person = $insert('joomleague_person',['first_name'=>'R1','last_name'=>'Player']);
    $groups = [];
    $userIds = [];
    foreach (['a','b'] as $letter) {
        $group = new Usergroup($db);
        $group->parent_id = 2;
        $group->title = 'R1 editor ' . $letter;
        $assert($group->check() && $group->store(), 'Group setup failed.');
        $groups[$letter] = (int) $group->id;
        $userIds[$letter] = $insert('users', [
            'name'=>'R1 editor ' . $letter,'username'=>'r1.editor.' . $letter,
            'email'=>'r1.' . $letter . '@example.invalid',
            'password'=>Joomla\CMS\User\UserHelper::hashPassword(getenv('JL_R1_PASSWORD')),
            'block'=>0,'sendEmail'=>0,'registerDate'=>Factory::getDate()->toSql(),
            'params'=>'{}','activation'=>'','requireReset'=>0,
        ],false);
        $mapping = (object) ['user_id'=>$userIds[$letter],'group_id'=>$groups[$letter]];
        $db->insertObject('#__user_usergroup_map',$mapping);
    }
    // Viewing the native admin toolbar is separate from project mutation permissions.
    $toolbarLevel=(int)$db->setQuery('SELECT access FROM #__modules WHERE module='.$db->quote('mod_toolbar').' AND client_id=1',0,1)->loadResult();
    $assert($toolbarLevel>1,'Restricted administrator toolbar view level missing.');
    $levelRules=json_decode((string)$db->setQuery('SELECT rules FROM #__viewlevels WHERE id='.$toolbarLevel)->loadResult(),true,512,JSON_THROW_ON_ERROR);
    $level=(object)['id'=>$toolbarLevel,'rules'=>json_encode(array_values(array_unique(array_merge($levelRules,array_values($groups)))),JSON_THROW_ON_ERROR)];
    $db->updateObject('#__viewlevels',$level,'id');
    $actions = ['core.create','core.edit','core.delete','core.edit.state','joomleague.project.edit.schedule','joomleague.project.edit.results','joomleague.project.run.transitions'];
    foreach (['root.1'=>['core.login.admin'], 'com_joomleague'=>array_merge($actions,['core.manage'])] as $assetName=>$grants) {
        $asset = new Asset($db);
        $assert($asset->loadByName($assetName), 'ACL asset missing: ' . $assetName);
        $rules = json_decode($asset->rules,true) ?: [];
        foreach ($grants as $grant) foreach ($groups as $group) $rules[$grant][(string) $group] = 1;
        $asset->rules = json_encode($rules,JSON_THROW_ON_ERROR);
        $assert($asset->store(), 'ACL setup failed.');
    }
    $componentAsset = (int) $db->setQuery('SELECT id FROM #__assets WHERE name=' . $db->quote('com_joomleague'))->loadResult();
    $fixture = ['users'=>$userIds,'projects'=>[],'person'=>$person];
    foreach (['a','b'] as $letter) {
        $project = $insert('joomleague_project',['name'=>'R1 project ' . $letter,'competition_id'=>$competition,'season_id'=>$season,'sport_type_id'=>$sport,'profile_version_id'=>$version,'project_type'=>'league','timezone'=>'UTC']);
        $asset = new Asset($db);
        $asset->setLocation($componentAsset,'last-child');
        $asset->parent_id = $componentAsset;
        $asset->name = 'com_joomleague.project.' . $project;
        $asset->title = 'R1 project ' . $letter;
        $rules = [];
        foreach ($actions as $grant) foreach ($groups as $key=>$group) $rules[$grant][(string) $group] = $key === $letter ? 1 : 0;
        $asset->rules = json_encode($rules,JSON_THROW_ON_ERROR);
        $assert($asset->check() && $asset->store(), 'Project ACL asset failed.');
        $projectAsset = (object) ['id'=>$project,'asset_id'=>(int)$asset->id];
        $db->updateObject('#__joomleague_project',$projectAsset,'id');
        $stage = $insert('joomleague_project_stage',['project_id'=>$project,'name'=>'R1 stage ' . $letter,'code'=>'r1_stage','stage_type'=>'league_phase']);
        $target = $insert('joomleague_project_stage',['project_id'=>$project,'name'=>'R1 target ' . $letter,'code'=>'r1_target','stage_type'=>'league_phase']);
        $round = $insert('joomleague_project_round',['project_id'=>$project,'stage_id'=>$stage,'name'=>'R1 round ' . $letter,'code'=>'r1_round','sequence_number'=>1]);
        $entries = [];
        foreach (['home','away'] as $side) {
            $team = $insert('joomleague_team',['name'=>'R1 ' . $letter . ' ' . $side]);
            $entries[] = $insert('joomleague_project_entry',['project_id'=>$project,'entry_kind'=>'team','team_id'=>$team]);
        }
        $match = $insert('joomleague_project_match',['project_id'=>$project,'stage_id'=>$stage,'round_id'=>$round,'match_number'=>'R1 ' . $letter,'code'=>'r1_match']);
        foreach ($entries as $index=>$entry) $insert('joomleague_match_participant',['match_id'=>$match,'project_id'=>$project,'project_entry_id'=>$entry,'slot_number'=>$index+1]);
        $member = $insert('joomleague_project_entry_member',['entry_id'=>$entries[0],'person_id'=>$person,'member_person_type'=>'player']);
        $adjustment = $insert('joomleague_standing_adjustment',['project_id'=>$project,'stage_id'=>$stage,'stage_key'=>$stage,'project_entry_id'=>$entries[0],'metric_code'=>'points','adjustment_value'=>1,'reason'=>'R1 fixture']);
        $transition = $insert('joomleague_stage_transition',['project_id'=>$project,'source_stage_id'=>$stage,'target_stage_id'=>$target,'code'=>'r1_transition','name'=>'R1 transition ' . $letter,'selector_type'=>'all_entries']);
        $spareTeam = $insert('joomleague_team',['name'=>'R1 spare HTTP team ' . $letter]);
        $fixture['projects'][$letter] = ['id'=>$project,'stage'=>$stage,'target'=>$target,'round'=>$round,'entries'=>$entries,'spareTeam'=>$spareTeam,'records'=>[
            'Stage'=>$stage,'Round'=>$round,'Match'=>$match,'Projectentry'=>$entries[0],'Entrymember'=>$member,'Standingadjustment'=>$adjustment,'Stagetransition'=>$transition,
        ]];
    }
    Access::clearStatics();
    file_put_contents($stateFile,json_encode($fixture,JSON_THROW_ON_ERROR));
    echo json_encode($fixture,JSON_THROW_ON_ERROR) . "\n";
    exit;
}
$fixture = json_decode(file_get_contents($stateFile),true,512,JSON_THROW_ON_ERROR);
$a = $fixture['projects']['a']; $b = $fixture['projects']['b'];
$user = $users->loadUserById($fixture['users']['a']);
$app->loadIdentity($user);
$assert(!$user->authorise('core.admin') && $user->authorise('core.login.admin'), 'Fixture user is not a restricted backend user.');
foreach (['core.edit','core.create','joomleague.project.edit.schedule','joomleague.project.edit.results','joomleague.project.run.transitions'] as $permission) {
    $assert($user->authorise($permission,'com_joomleague.project.' . $a['id']), 'Allowed project permission missing.');
    $assert(!$user->authorise($permission,'com_joomleague.project.' . $b['id']), 'Foreign project permission leaked.');
}
if ($action === 'snapshot') {
    $snapshot = [];
    foreach ($tables as $name=>$table) $snapshot[$name] = $db->setQuery('SELECT * FROM ' . $db->quoteName('#__joomleague_' . $table) . ' ORDER BY id')->loadAssocList();
    $snapshot['participants'] = $db->setQuery('SELECT * FROM #__joomleague_match_participant ORDER BY id')->loadAssocList();
    echo json_encode($snapshot,JSON_THROW_ON_ERROR) . "\n";
    exit;
}
if ($action !== 'models') throw new InvalidArgumentException('Unknown action.');
$model = static function (string $name) use ($factory,$user) {
    $model = $factory->createModel($name,'Administrator',['ignore_request'=>true]);
    $model->setCurrentUser($user);
    return $model;
};
foreach ($tables as $name=>$table) {
    $allowed = $row($name,$a['records'][$name]);
    $foreign = $row($name,$b['records'][$name]);
    $assert($allowed !== null && $foreign !== null, 'Missing mandatory fixture for ' . $name);
    $forged = $foreign;
    $forged['project_id'] = $a['id'];
    if ($name === 'Entrymember') $forged['entry_id'] = $a['entries'][0];
    $forged['description'] = 'unauthorized write';
    $assert($model($name)->save($forged) === false, $name . ': foreign edit accepted.');
    $assert($row($name,(int)$foreign['id']) === $foreign, $name . ': denied edit changed row.');
    $stateEdit = $allowed;
    unset($stateEdit['id'], $stateEdit['uuid']);
    $stateModel = $model($name);
    $stateModel->setState(strtolower($name) . '.id', (int) $foreign['id']);
    $assert($stateModel->save($stateEdit) === false, $name . ': model-state foreign edit accepted.');
    $assert(in_array($stateModel->getError(), [Joomla\CMS\Language\Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), Joomla\CMS\Language\Text::_('JERROR_ALERTNOAUTHOR')], true), $name . ': model-state edit was not rejected at the authorization boundary: ' . $stateModel->getError());
    $assert($row($name,(int)$foreign['id']) === $foreign, $name . ': model-state edit changed foreign row.');
    $ids = [(int)$foreign['id']];
    $model($name)->publish($ids,0);
    $assert($row($name,(int)$foreign['id']) === $foreign, $name . ': foreign publication changed row.');
    $ids = [(int)$foreign['id']];
    $model($name)->delete($ids);
    $assert($row($name,(int)$foreign['id']) === $foreign, $name . ': foreign deletion changed row.');
    $model($name)->saveorder([(int)$foreign['id']], [99]);
    $assert($row($name,(int)$foreign['id']) === $foreign, $name . ': foreign ordering changed row.');
    $assert($model($name)->saveorder([(int)$allowed['id']], [99]), $name . ': permitted ordering failed.');
    $assert((int)$row($name,(int)$allowed['id'])['ordering'] !== (int)$allowed['ordering'], $name . ': permitted ordering did not persist.');
    $assert($row($name,(int)$foreign['id']) === $foreign, $name . ': permitted ordering renumbered foreign project.');

    $edit = $allowed;
    $edit['project_id'] = $b['id'];
    if ($name === 'Stage') $edit['name'] = 'R1 allowed stage edit';
    if ($name === 'Round') { $edit['stage_id']=$b['stage']; $edit['name']='R1 allowed round edit'; }
    if ($name === 'Match') {
        $edit['round_id']=$b['round']; $edit['stage_id']=$b['stage'];
        $edit['participant_slot_1']=$a['entries'][0]; $edit['participant_slot_2']=$a['entries'][1];
        $edit['match_number']='R1 allowed match edit';
    }
    if ($name === 'Projectentry') $edit['notes']='R1 allowed entry edit';
    if ($name === 'Entrymember') { $edit['entry_id']=$b['entries'][0]; $edit['shirt_number']='19'; }
    if ($name === 'Standingadjustment') { $edit['stage_id']=$b['stage']; $edit['reason']='R1 allowed correction edit'; }
    if ($name === 'Stagetransition') $edit['name']='R1 allowed transition edit';
    $current = $model($name);
    $assert($current->save($edit), $name . ': permitted save failed: ' . $current->getError());
    $stateAllowed = $edit;
    unset($stateAllowed['id'], $stateAllowed['uuid']);
    $stateModel = $model($name);
    $stateModel->setState(strtolower($name) . '.id', (int) $allowed['id']);
    $assert($stateModel->save($stateAllowed), $name . ': permitted model-state edit failed: ' . $stateModel->getError());
    $after = $row($name,(int)$allowed['id']);
    foreach (['project_id','stage_id','round_id','entry_id'] as $field) {
        if (array_key_exists($field,$allowed)) $assert($after[$field] === $allowed[$field], $name . ': immutable owner changed: ' . $field);
    }
    $ids = [(int)$allowed['id']];
    $assert($model($name)->publish($ids,0), $name . ': permitted unpublish failed.');
    $assert((int)$row($name,(int)$allowed['id'])['published'] === 0, $name . ': unpublish not persisted.');
    $ids = [(int)$allowed['id']];
    $assert($model($name)->publish($ids,1), $name . ': permitted publish failed.');
    $assert((int)$row($name,(int)$allowed['id'])['published'] === 1, $name . ': publish not persisted.');

    $create = $allowed;
    foreach (['id','uuid','created','created_by','modified','modified_by','checked_out','checked_out_time','asset_id'] as $field) unset($create[$field]);
    $create['id'] = 0; $create['code'] = 'r1_new_' . strtolower($name);
    if ($name === 'Round') $create['sequence_number'] = 2;
    if ($name === 'Match') { $create['participant_slot_1']=$a['entries'][0]; $create['participant_slot_2']=$a['entries'][1]; }
    if ($name === 'Projectentry') {
        $create['team_id'] = $insert('joomleague_team',['name'=>'R1 new model team']);
    }
    $forbiddenCreate = $create;
    $forbiddenCreate['project_id'] = $b['id'];
    if ($name === 'Round') $forbiddenCreate['stage_id']=$b['stage'];
    if ($name === 'Match') { $forbiddenCreate['stage_id']=$b['stage']; $forbiddenCreate['round_id']=$b['round']; }
    if ($name === 'Entrymember') $forbiddenCreate['entry_id']=$b['entries'][0];
    if (in_array($name,['Round','Match','Entrymember'],true)) $forbiddenCreate['project_id']=$a['id'];
    $count = (int)$db->setQuery('SELECT COUNT(*) FROM #__joomleague_' . $table)->loadResult();
    $assert($model($name)->save($forbiddenCreate) === false, $name . ': foreign creation accepted.');
    $assert((int)$db->setQuery('SELECT COUNT(*) FROM #__joomleague_' . $table)->loadResult() === $count, $name . ': denied create inserted a row.');
    $current=$model($name);
    $assert($current->save($create), $name . ': permitted creation failed: ' . $current->getError());
    $newId = (int)$current->getState(strtolower($name) . '.id');
    $assert($newId > 0 && $newId !== (int)$allowed['id'], $name . ': new ID missing.');
    $ids=[$newId];
    $assert($model($name)->delete($ids), $name . ': permitted delete failed.');
    $assert($row($name,$newId) === null, $name . ': deletion not persisted.');
    echo 'PASS: ' . $name . ' real ACL create/edit/publish/delete and immutable ownership.' . "\n";
}
