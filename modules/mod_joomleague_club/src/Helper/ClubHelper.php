<?php
declare(strict_types=1);namespace Joomleague\Module\Club\Site\Helper;defined('_JEXEC')or die;
use Joomla\CMS\Factory;use Joomla\Database\DatabaseInterface;use Joomla\Registry\Registry;use Joomleague\Component\Joomleague\Domain\Service\ClubSummaryReader;use Joomleague\Component\Joomleague\Domain\Service\ProgrammeReader;use Joomleague\Component\Joomleague\Domain\Service\ProgrammeScopeResolver;
final class ClubHelper
{
	/** @return array<string,mixed> */
	public function getSummary(Registry$p):array
	{
		$id=(int)$p->get('club_id',0);if($id<1)return['error'=>'MOD_JOOMLEAGUE_CLUB_NOT_CONFIGURED'];$app=Factory::getApplication();$app->bootComponent('com_joomleague');$lang=Factory::getLanguage();$lang->load('com_joomleague',JPATH_SITE)||$lang->load('com_joomleague',JPATH_SITE.'/components/com_joomleague');$levels=$app->getIdentity()->getAuthorisedViewLevels();$db=Factory::getContainer()->get(DatabaseInterface::class);
		try{$data=(new ClubSummaryReader($db))->read($id,$levels);}catch (\Throwable $moduleFailure) { \Joomla\CMS\Log\Log::add('mod_joomleague_club failure: ' . get_debug_type($moduleFailure), \Joomla\CMS\Log\Log::WARNING, 'com_joomleague.modules');return['error'=>'MOD_JOOMLEAGUE_CLUB_UNAVAILABLE'];}if(isset($data['error']))return['error'=>$data['error']==='club_required'?'MOD_JOOMLEAGUE_CLUB_NOT_CONFIGURED':'MOD_JOOMLEAGUE_CLUB_UNAVAILABLE'];
		$data['club']->logo = \Joomleague\Component\Joomleague\Site\Service\MediaResolver::image('club', $data['club']->logo);
		$projects=[];foreach($data['teams']as$team)foreach($team->projects as$project)$projects[(int)$project->project_id]=true;$events=[];$now=Factory::getDate()->toUnix();$resolver=new ProgrammeScopeResolver($db);$reader=new ProgrammeReader($db);foreach(array_keys($projects)as$projectId){$entries=$resolver->resolve($projectId,'club',$id,$levels);foreach($reader->forProject($projectId,$entries,$levels)as$event)if(!$event['played']&&$event['status_code']!=='cancelled'&&$event['scheduled_start']!==null&&Factory::getDate($event['scheduled_start'],'UTC')->toUnix()>=$now)$events[]=$event;}usort($events,static fn(array$a,array$b):int=>strcmp((string)$a['scheduled_start'],(string)$b['scheduled_start'])?:$a['id']<=>$b['id']);$data['events']=array_slice($events,0,max(0,min(10,(int)$p->get('event_limit',3))));return$data;
	}
}
