<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' || !str_starts_with((string) getenv('JL_R1_DB_HOST'), 'jl-r1-')) throw new RuntimeException('Disposable import environment required.');
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST']='localhost'; $_SERVER['REQUEST_URI']='/administrator/index.php'; $_SERVER['SCRIPT_NAME']='/administrator/index.php';
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\ComponentTableCatalog;
use Joomleague\Component\Joomleague\Administrator\Service\SqlDataExchangeService;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

$container=Factory::getContainer();
$container->alias('session','session.cli')->alias(Joomla\Session\SessionInterface::class,'session.cli');
$app=$container->get(Joomla\CMS\Application\AdministratorApplication::class);
Factory::$application=$app;
if ($app->get('host')!==getenv('JL_R1_DB_HOST') || $app->get('db')!=='r1_test' || $app->get('dbprefix')!=='r1_') throw new RuntimeException('Refusing a non-disposable database.');
$app->createExtensionNamespaceMap();
$app->bootComponent('com_joomleague');
$app->loadLanguage(Joomla\CMS\Language\Language::getInstance('en-GB'));
$db=$container->get(DatabaseInterface::class);
$root=JPATH_ADMINISTRATOR . '/components/com_joomleague';
$service=new SqlDataExchangeService($db,$root);
$assert=static function(bool $condition,string $message):void { if (!$condition) throw new RuntimeException($message); };
$snapshot=static function(DatabaseInterface $connection):array {
    $result=[];
    foreach (ComponentTableCatalog::installed($connection) as $table) {
        $rows=$connection->setQuery('SELECT * FROM '.$connection->quoteName($table))->loadAssocList();
        usort($rows,static fn(array $a,array $b):int=>strcmp(json_encode($a,JSON_THROW_ON_ERROR),json_encode($b,JSON_THROW_ON_ERROR)));
        $result[$table]=$rows;
    }
    return $result;
};
$insert=static function(DatabaseInterface $connection,string $table,array $data):int {
    $object=(object)$data; $connection->insertObject($table,$object,'id'); return (int)$object->id;
};
$action=$argv[1]??'';
if ($action==='seed-upgrade') {
    $insert($db,'#__joomleague_club',['id'=>6000,'uuid'=>UuidFactory::v4(),'name'=>'R2 preserved through beta6 upgrade']);
    echo "PASS: beta6 business-data fixture created before candidate upgrade.\n"; exit;
}
if ($action==='verify-update') {
    $assert($snapshot($db)===json_decode(file_get_contents('/tmp/r2-before-update.json'),true,512,JSON_THROW_ON_ERROR),'Repeated candidate installation changed component data.');
    echo "PASS: repeated candidate update preserves complete component data.\n"; exit;
}
if ($action==='setup') {
    $assert($db->setQuery('SELECT name FROM #__joomleague_club WHERE id=6000')->loadResult()==='R2 preserved through beta6 upgrade','Beta6 business data lost during upgrade.');
    $group=(int)$db->setQuery('SELECT group_id FROM #__user_usergroup_map WHERE user_id=(SELECT id FROM #__users WHERE username='.$db->quote('r1.editor.b').')')->loadResult();
    $asset=new Joomla\CMS\Table\Asset($db); $assert($asset->loadByName('com_joomleague'),'Component asset missing.');
    $rules=json_decode($asset->rules,true); $rules['joomleague.database.import'][(string)$group]=1;
    $asset->rules=json_encode($rules,JSON_THROW_ON_ERROR); $assert($asset->store(),'Import ACL setup failed.');
    $literal="R2 quotes ' \\ ; -- SELECT marker FROM r2_external_marker; #__users\nCzech: \u{010D}\u{0159}\u{017E} \u{1F3BE}";
    $insert($db,'#__joomleague_club',['id'=>7000,'uuid'=>UuidFactory::v4(),'name'=>$literal,'published'=>1]);
    foreach ([[1002,null,'r2_parent'],[1001,1002,'r2_child']] as [$id,$parent,$code]) {
        $insert($db,'#__joomleague_sport_category',['id'=>$id,'uuid'=>UuidFactory::v4(),'parent_id'=>$parent,'code'=>$code,'name'=>$code,'description'=>'','created'=>Factory::getDate()->toSql()]);
    }
    $db->setQuery('CREATE TABLE r2_external_marker (marker VARCHAR(100) NOT NULL)')->execute();
    $db->setQuery('INSERT INTO r2_external_marker (marker) VALUES ('.$db->quote('outside-component-marker').')')->execute();
    echo "PASS: import-only account, literal and reverse-ID hierarchy fixtures.\n"; exit;
}
if ($action==='snapshot') {
    echo json_encode(['component'=>$snapshot($db),'external'=>$db->setQuery('SELECT marker FROM r2_external_marker')->loadColumn()],JSON_THROW_ON_ERROR)."\n"; exit;
}
if ($action!=='verify') throw new InvalidArgumentException('Unknown import test action.');
$before=$snapshot($db);
$attacks=[
    'INSERT INTO `#__joomleague_club` (`name`) SELECT marker FROM r2_external_marker',
    'INSERT INTO `#__joomleague_club` (`name`) VALUES (SLEEP(1))',
    'INSERT INTO `#__joomleague_club` (`name`) VALUES ((SELECT marker FROM r2_external_marker))',
    'INSERT INTO `#__joomleague_club` (`name`) VALUES (\'x\') RETURNING *',
    'INSERT INTO `#__joomleague_club` (`name`) VALUES (\'x\') ON CONFLICT DO NOTHING',
    'INSERT INTO `#__joomleague_club` (`name`) VALUES (\'x\') ON DUPLICATE KEY UPDATE name=\'changed\'',
    'INSERT INTO `#__joomleague_club` (`name`) VALUES (\'x\' || current_user)',
    'INSERT INTO `#__joomleague_club` (`name`) VALUES (DEFAULT)',
    'INSERT INTO `#__joomleague_club` (`unknown_column`) VALUES (1)',
    'INSERT INTO `#__joomleague_unknown_table` (`id`) VALUES (1)',
    'INSERT INTO `#__users` (`id`) VALUES (1)',
    'INSERT INTO `r2_external_marker` (`marker`) VALUES (\'changed\')',
    'UPDATE `#__joomleague_club` SET name=\'changed\'',
    'DELETE FROM `#__joomleague_club`',
    'DROP TABLE `#__joomleague_club`',
    'ALTER TABLE `#__joomleague_club` ADD injected TEXT',
    'CREATE TABLE IF NOT EXISTS `#__joomleague_club` (`id` INT) AS SELECT * FROM r2_external_marker',
    'CREATE VIEW `#__joomleague_probe` AS SELECT * FROM r2_external_marker',
    'SET FOREIGN_KEY_CHECKS=0',
    'COPY r2_external_marker TO STDOUT',
    "INSERT INTO `#__joomleague_club` (`name`) VALUES ('prevalidated'); DELETE FROM r2_external_marker",
    "INSERT INTO `#__joomleague_sport_category` (`id`,`uuid`,`code`,`name`,`description`,`created`,`parent_id`) VALUES (8001,'00000000-0000-4000-8000-000000008001','r2_cycle','R2 cycle','','2026-10-16 00:00:00',8001)",
];
foreach ($attacks as $attack) {
    try { $service->import($attack); throw new LogicException('Unsafe statement accepted: '.$attack); }
    catch (RuntimeException $error) { $assert($error->getMessage()==='COM_JOOMLEAGUE_DATAIMPORT_ERROR_STATEMENT','Unexpected attack error: '.$error->getMessage()); }
    $assert($snapshot($db)===$before,'Rejected statement modified component data.');
    $assert($db->setQuery('SELECT marker FROM r2_external_marker')->loadResult()==='outside-component-marker','External marker changed.');
}
echo 'PASS: '.count($attacks)." hostile imports rejected before mutation.\n";

$export=$service->export(array_reverse(ComponentTableCatalog::installed($db)));
$db->setQuery('CREATE DATABASE r2_restore')->execute();
$restore=(new Joomla\Database\DatabaseFactory())->getDriver($db->getName(),[
    'host'=>getenv('JL_R1_DB_HOST'),'user'=>getenv('JL_R1_DB_USER'),'password'=>getenv('JL_R1_PASSWORD'),'database'=>'r2_restore','prefix'=>'restore_','utf8mb4'=>true,
]);
$restorer=new SqlDataExchangeService($restore,$root);
$reversed=array_reverse($db->splitSql($export));
$reversed=implode(";\n",array_map(static fn(string $s):string=>rtrim(trim($s),';'),$reversed)).';';
$restorer->import($reversed);
$expected=$before;
$expected['#__joomleague_standing_freshness']=[];
$assert($snapshot($restore)===$expected,'Full schema/data restore differs from export source.');
$repeated=$restorer->import($export);
$assert($repeated['skipped']>0 && $snapshot($restore)===$expected,'Repeated full import changed existing rows or failed.');
$newId=$insert($restore,'#__joomleague_club',['uuid'=>UuidFactory::v4(),'name'=>'R2 next generated ID','published'=>1]);
$assert($newId>7000,'Regular restore left an identity sequence behind imported IDs.');
if ($restore->getName()==='pgsql') {
    $sequence=$restore->setQuery('SELECT pg_get_serial_sequence('.$restore->quote($restore->replacePrefix('#__joomleague_club')).", 'id')")->loadResult();
    $restore->setQuery('SELECT setval('.$restore->quote($sequence).',12000,TRUE)')->execute();
    $restorer->import($export);
    $next=$insert($restore,'#__joomleague_club',['uuid'=>UuidFactory::v4(),'name'=>'R2 sequence must not rewind','published'=>1]);
    $assert($next>12000,'Repeated import rewound an already advanced sequence.');
}
echo "PASS: full schema/data restore, repeated import, literal/hierarchy preservation and next generated ID.\n";

$baseline=$snapshot($db);
try {
    $service->import("INSERT INTO `#__joomleague_club` (`uuid`,`name`) VALUES ('00000000-0000-4000-8000-000000009201','R2 rollback');\nINSERT INTO `#__joomleague_club` (`uuid`,`name`) VALUES ('00000000-0000-4000-8000-000000009202',NULL);");
    throw new LogicException('Invalid data did not fail.');
} catch (RuntimeException) {}
$assert($snapshot($db)===$baseline,'Data failure left a partial committed import.');
echo "PASS: data failure rolls back the entire batch and preserves existing data.\n";

$db->setQuery('CREATE DATABASE r2_recovery')->execute();
$recovery=(new Joomla\Database\DatabaseFactory())->getDriver($db->getName(),[
    'host'=>getenv('JL_R1_DB_HOST'),'user'=>getenv('JL_R1_DB_USER'),'password'=>getenv('JL_R1_PASSWORD'),'database'=>'r2_recovery','prefix'=>'recovery_','utf8mb4'=>true,
]);
$ddl=array_values(array_filter($db->splitSql($service->export(['#__joomleague_club','#__joomleague_team'])),static fn($s)=>preg_match('/^CREATE\s/i',trim($s))===1));
$ddl=array_map(static fn(string $s):string=>rtrim(trim($s),';'),$ddl);
$bad=implode(";\n",$ddl).";\nINSERT INTO `#__joomleague_club` (`id`,`uuid`,`name`) VALUES (1,'00000000-0000-4000-8000-000000009203','R2 recovery');\nINSERT INTO `#__joomleague_team` (`uuid`,`name`,`club_id`) VALUES ('00000000-0000-4000-8000-000000009204','R2 invalid reference',999999);";
$recoverer=new SqlDataExchangeService($recovery,$root);
try { $recoverer->import($bad); throw new LogicException('Invalid trusted-DDL batch accepted.'); }
catch (RuntimeException $error) { $assert($error->getMessage()==='COM_JOOMLEAGUE_DATAIMPORT_ERROR_RECOVERED','Trusted-DDL recovery failed: '.$error->getMessage()); }
$assert(ComponentTableCatalog::installed($recovery)===[],'Failed import left newly created tables.');
$unknownColumn=implode(";\n",$ddl).'; INSERT INTO `#__joomleague_club` (`r2_unknown_column`) VALUES (1);';
try { $recoverer->import($unknownColumn); throw new LogicException('Unknown column accepted.'); }
catch (RuntimeException $error) { $assert($error->getMessage()==='COM_JOOMLEAGUE_DATAIMPORT_ERROR_STATEMENT','Column rejection happened after DDL execution.'); }
$assert(ComponentTableCatalog::installed($recovery)===[],'Unknown column validation created tables.');
foreach ($ddl as $statement) if (preg_match('/^CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+[`"]#__joomleague_club[`"]/', $statement)===1) $recovery->setQuery($recovery->replacePrefix($statement))->execute();
$insert($recovery,'#__joomleague_club',['id'=>10,'uuid'=>UuidFactory::v4(),'name'=>'R2 pre-existing club']);
$existing=$snapshot($recovery);
try { $recoverer->import($bad); throw new LogicException('Invalid trusted-DDL batch accepted with existing data.'); }
catch (RuntimeException $error) { $assert($error->getMessage()==='COM_JOOMLEAGUE_DATAIMPORT_ERROR_RECOVERED','Existing-table recovery failed: '.$error->getMessage().' / '.($error->getPrevious()?->getMessage()??'')); }
$assert($snapshot($recovery)===$existing,'Recovery removed or changed a pre-existing table.');
echo "PASS: trusted-DDL recovery removes only newly created empty objects.\n";

$canonical=<<<'SQL'
-- Canonical JoomLeague 6.2 migration package
INSERT INTO `#__joomleague_sport_type` (`id`,`profile_version_id`,`code`,`name`) VALUES (9001,{{profile_version:football}},'r2_canonical','R2 canonical');
INSERT INTO `#__joomleague_sport_position` (`uuid`,`sport_type_id`,`code`,`name`,`person_type`,`role_domain`,`source`,`published`,`ordering`) VALUES ('00000000-0000-4000-8000-000000009205',9001,'r2_legacy','R2 custom role','staff','team_staff','local',1,1);
SQL;
$restorer->import($canonical);
foreach (['sport_position','event_type','statistic'] as $suffix) {
    $count=(int)$restore->setQuery('SELECT COUNT(*) FROM #__joomleague_'.$suffix.' WHERE sport_type_id=9001')->loadResult();
    $assert($count>1,'Canonical catalog materialization missing: '.$suffix);
}
$assert((int)$restore->setQuery("SELECT COUNT(*) FROM #__joomleague_sport_position WHERE sport_type_id=9001 AND code='r2_legacy' AND source='local'")->loadResult()===1,'Canonical materialization lost custom role.');
$canonicalBefore=$snapshot($restore); $restorer->import($canonical);
$assert($snapshot($restore)===$canonicalBefore,'Repeated canonical import changed or duplicated rows.');
$missing="-- Canonical JoomLeague 6.2 migration package\nINSERT INTO `#__joomleague_club` (`uuid`,`name`) VALUES ('00000000-0000-4000-8000-000000009206','R2 rollback profile'); INSERT INTO `#__joomleague_sport_type` (`profile_version_id`,`code`,`name`) VALUES ({{profile_version:r2_missing}},'r2_missing','R2 missing');";
try { $restorer->import($missing); throw new LogicException('Missing canonical profile accepted.'); }
catch (RuntimeException $error) { $assert($error->getMessage()==='COM_JOOMLEAGUE_DATAIMPORT_ERROR_PROFILE','Incorrect missing-profile diagnostic.'); }
$assert($snapshot($restore)===$canonicalBefore,'Missing profile left partial rows.');
$quoted='-- Canonical JoomLeague 6.2 migration package {{profile_version:r2_missing}} #__users';
$restorer->import('INSERT INTO `#__joomleague_club` (`uuid`,`name`) VALUES (\'00000000-0000-4000-8000-000000009207\','.$restore->quote($quoted).');');
$assert($restore->setQuery("SELECT name FROM #__joomleague_club WHERE uuid='00000000-0000-4000-8000-000000009207'")->loadResult()===$quoted,'Quoted marker/token was interpreted as migration control.');
echo "PASS: canonical placeholders, profile catalogs, preserved local role and idempotent retry.\n";
$db->setQuery('CREATE DATABASE r2_views')->execute();
$viewDb=(new Joomla\Database\DatabaseFactory())->getDriver($db->getName(),[
    'host'=>getenv('JL_R1_DB_HOST'),'user'=>getenv('JL_R1_DB_USER'),'password'=>getenv('JL_R1_PASSWORD'),'database'=>'r2_views','prefix'=>'view_','utf8mb4'=>true,
]);
$viewDb->setQuery('CREATE TABLE r2_external_data (id INTEGER PRIMARY KEY, name VARCHAR(255) NOT NULL)')->execute();
$viewDb->setQuery("INSERT INTO r2_external_data (id,name) VALUES (1,'outside-component')")->execute();
$viewDb->setQuery('CREATE VIEW '.$viewDb->quoteName('#__joomleague_club').' AS SELECT id,name FROM r2_external_data')->execute();
try { (new SqlDataExchangeService($viewDb,$root))->import("INSERT INTO `#__joomleague_club` (`id`,`name`) VALUES (2,'forbidden view write');"); throw new LogicException('View target accepted.'); }
catch (RuntimeException $error) { $assert($error->getMessage()==='COM_JOOMLEAGUE_DATAIMPORT_ERROR_STATEMENT','Incorrect view-target rejection.'); }
$assert((int)$viewDb->setQuery('SELECT COUNT(*) FROM r2_external_data')->loadResult()===1,'Import through a view wrote outside component tables.');
echo "PASS: component-looking views cannot write to non-component storage.\n";
file_put_contents('/tmp/r2-before-update.json',json_encode($snapshot($db),JSON_THROW_ON_ERROR));
