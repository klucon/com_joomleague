<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
foreach (['UuidFactory.php', 'CanonicalJson.php', 'ScheduleTemplateService.php', 'SchedulePlannerService.php'] as $service) require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\SchedulePlannerService;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias('JSession', 'session.cli')->alias(Joomla\CMS\Session\Session::class, 'session.cli')->alias(Joomla\Session\Session::class, 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
$db = $container->get(DatabaseInterface::class);
$suffix = bin2hex(random_bytes(5));
$name = 'Schedule fixture ' . $suffix;
$insert = static function (string $table, array $values) use ($db): int { $query=$db->getQuery(true)->insert($db->quoteName($table))->columns($db->quoteName(array_keys($values)));$holders=[];foreach($values as$key=>&$value){$holders[]=':'.$key;$query->bind(':'.$key,$value);}$query->values(implode(',',$holders));$db->setQuery($query)->execute();return(int)$db->insertid(); };
$delete = static function (string $table, string $column, mixed $value) use ($db): void { $query=$db->getQuery(true)->delete($db->quoteName($table))->where($db->quoteName($column).'=:value')->bind(':value',$value);$db->setQuery($query)->execute(); };

$query=$db->getQuery(true)->select('version.id')->from($db->quoteName('#__joomleague_sport_profile_version','version'))->innerJoin($db->quoteName('#__joomleague_sport_profile','profile').' ON profile.id=version.profile_id')->where('profile.code='.$db->quote('football'))->where('version.state='.$db->quote('active'))->order('version.id DESC');
$profileVersionId=(int)$db->setQuery($query,0,1)->loadResult();
$competitionId=$insert('#__joomleague_competition',['uuid'=>UuidFactory::v4(),'name'=>$name]);$seasonId=$insert('#__joomleague_season',['uuid'=>UuidFactory::v4(),'name'=>$name]);$sportTypeId=$insert('#__joomleague_sport_type',['profile_version_id'=>$profileVersionId,'code'=>'schedule-'.$suffix,'name'=>$name]);$projectId=$insert('#__joomleague_project',['uuid'=>UuidFactory::v4(),'competition_id'=>$competitionId,'season_id'=>$seasonId,'sport_type_id'=>$sportTypeId,'profile_version_id'=>$profileVersionId,'name'=>$name,'project_type'=>'league','timezone'=>'Europe/Prague']);

try {
	for($seed=1;$seed<=4;$seed++)$insert('#__joomleague_project_entry',['uuid'=>UuidFactory::v4(),'project_id'=>$projectId,'entry_kind'=>'group','display_name'=>$name.' '.$seed,'seed_number'=>$seed]);
	$stageId=$insert('#__joomleague_project_stage',['uuid'=>UuidFactory::v4(),'project_id'=>$projectId,'name'=>'League','code'=>'league','stage_type'=>'league']);
	$service=new SchedulePlannerService($db);$options=$service->defaults($stageId);$options['template_id']='round-robin-first-half-v1';$options['start_date']='2026-09-01';$options['start_time']='17:00';
	$preview=$service->preview($stageId,$options);if(count($preview['rounds'])!==3||$preview['match_count']!==6||$preview['blocking']!==0)throw new RuntimeException('Four-entry Berger preview is invalid.');
	foreach($preview['rounds'] as $round)if(str_contains((string)$round['name'],'COM_JOOMLEAGUE_')||!str_contains((string)$round['name'],(string)$round['sequence']))throw new RuntimeException('Generated round name was not translated in the CLI application.');
	$result=$service->apply($stageId,$options,0);$repeat=$service->apply($stageId,$options,0);if($result['reused']||!$repeat['reused'])throw new RuntimeException('Schedule application is not idempotent.');
	foreach(['#__joomleague_project_round'=>3,'#__joomleague_project_match'=>6,'#__joomleague_match_participant'=>12,'#__joomleague_schedule_generation'=>1]as$table=>$expected){$query=$db->getQuery(true)->select('COUNT(*)')->from($db->quoteName($table));if($table==='#__joomleague_schedule_generation')$query->where('project_id='.(int)$projectId);elseif($table==='#__joomleague_match_participant')$query->where('project_id='.(int)$projectId);else$query->where('project_id='.(int)$projectId);if((int)$db->setQuery($query)->loadResult()!==$expected)throw new RuntimeException('Unexpected generated row count in '.$table);}
	echo "Schedule generation integration passed.\n";
} finally {
	$delete('#__joomleague_project','id',$projectId);$delete('#__joomleague_sport_type','id',$sportTypeId);$delete('#__joomleague_season','id',$seasonId);$delete('#__joomleague_competition','id',$competitionId);
}
