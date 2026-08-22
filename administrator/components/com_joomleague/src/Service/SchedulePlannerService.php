<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\CanonicalJson;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

final class SchedulePlannerService
{
	public function __construct(private readonly DatabaseInterface $database, private readonly ScheduleTemplateService $templates = new ScheduleTemplateService()) {}

	/** @return array<string,mixed> */
	public function defaults(int $stageId): array
	{
		$context = $this->context($stageId); $structure = $context['profile']['match']['structure'] ?? [];
		$contestType = (string) ($context['profile']['contest']['type'] ?? 'head_to_head');
		$available = array_filter($this->templates->all(), fn(object $template): bool => $this->templates->supports($template, $contestType, count($context['entries'])));
		$first = reset($available);
		return [
			'template_id' => $first ? $first->templateId : '',
			'start_date' => (string) ($context['stage']->start_date ?: $context['project']->start_date ?: gmdate('Y-m-d')),
			'start_time' => (string) ($context['project']->default_start_time ?: ($structure['default_start_time'] ?? '12:00')),
			'round_interval_days' => 7, 'match_interval_minutes' => 0, 'return_legs' => 0, 'race_rounds' => 1,
			'first_match_number' => 1, 'assign_home_venues' => 1, 'published' => 0, 'allow_conflicts' => 0,
		];
	}

	/** @return array<string,mixed> */
	public function preview(int $stageId, array $rawOptions): array
	{
		$context = $this->context($stageId); $options = $this->options($rawOptions); $template = $this->templates->get($options['template_id']);
		$contestType = (string) ($context['profile']['contest']['type'] ?? 'head_to_head');
		if (!$this->templates->supports($template, $contestType, count($context['entries']))) throw new \DomainException(Text::_('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_CONTEXT_UNSUPPORTED'));
		$roundSets = [$this->templates->rounds($template, count($context['entries']))];
		if ($options['return_legs'] && $template->type === 'round-robin') {
			$mirror = $this->mirrorFor($template->templateId);
			if (!$mirror) throw new \DomainException(Text::_('COM_JOOMLEAGUE_SCHEDULE_TEMPLATE_RETURN_UNAVAILABLE'));
			$roundSets[] = $this->templates->rounds($mirror, count($context['entries']));
		}
		if ($template->type === 'race') $roundSets = array_fill(0, $options['race_rounds'], $roundSets[0]);
		$timezone = new \DateTimeZone($context['timezone']);
		$start = new \DateTimeImmutable($options['start_date'] . ' ' . $options['start_time'], $timezone);
		$maxSequence = $this->maxRoundSequence($stageId); $rounds = []; $sequenceOffset = 0; $matchNumber = $options['first_match_number'];
		foreach ($roundSets as $set) foreach ($set as $sourceRound) {
			$sequenceOffset++; $sequence = $maxSequence + $sequenceOffset; $localStart = $start->modify('+' . (($sequenceOffset - 1) * $options['round_interval_days']) . ' days');
			$round = ['sequence' => $sequence, 'name' => Text::sprintf('COM_JOOMLEAGUE_SCHEDULE_ROUND_NAME_PATTERN', $sequence), 'code' => 'schedule_round_' . $sequence, 'date' => $localStart->format('Y-m-d'), 'bye' => null, 'matches' => []];
			if (isset($sourceRound->bye->seed)) $round['bye'] = $context['entries'][(int) $sourceRound->bye->seed - 1] ?? null;
			foreach ($sourceRound->matches ?? [] as $matchIndex => $sourceMatch) {
				$participants = [];
				if (($sourceMatch->participants ?? null) === 'all') $participants = $context['entries'];
				else foreach (['home','away'] as $slot => $side) { $seed = (int) ($sourceMatch->$side->seed ?? 0); if ($seed > 0 && isset($context['entries'][$seed - 1])) $participants[] = $context['entries'][$seed - 1] + ['slot' => $slot + 1]; }
				if ($participants === []) continue;
				$venue = $options['assign_home_venues'] && $template->type === 'round-robin' ? ($participants[0]['venue'] ?? null) : null;
				$scheduled = $localStart->modify('+' . ((int) $matchIndex * $options['match_interval_minutes']) . ' minutes');
				$round['matches'][] = ['sequence' => (int) $matchIndex + 1, 'code' => 'schedule_match_' . $sequence . '_' . ((int) $matchIndex + 1), 'number' => $matchNumber > 0 ? (string) $matchNumber++ : null, 'scheduled_local' => $scheduled->format('Y-m-d H:i:s'), 'scheduled_utc' => $scheduled->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'), 'venue' => $venue, 'participants' => $participants];
			}
			$rounds[] = $round;
		}
		$conflicts = $this->conflicts($context, $rounds); $blocking = count(array_filter($conflicts, static fn(array $conflict): bool => $conflict['severity'] === 'error'));
		$checksumRounds = array_map(static fn(array $round): array => [
			'date' => $round['date'],
			'bye' => $round['bye']['id'] ?? null,
			'matches' => array_map(static fn(array $match): array => [
				'number' => $match['number'], 'scheduled_utc' => $match['scheduled_utc'], 'venue_id' => $match['venue']['id'] ?? null,
				'participants' => array_column($match['participants'], 'id'),
			], $round['matches']),
		], $rounds);
		$checksum = CanonicalJson::checksum(['stage_id' => $stageId, 'template_id' => $template->templateId, 'template_checksum' => $template->checksum, 'entries' => array_map(static fn(array $entry): array => ['id' => $entry['id'], 'seed' => $entry['seed']], $context['entries']), 'options' => $options, 'rounds' => $checksumRounds]);
		return compact('context','options','template','rounds','conflicts','blocking','checksum') + ['match_count' => array_sum(array_map(static fn(array $round): int => count($round['matches']), $rounds))];
	}

	/** @return array{generation_id:int,reused:bool,rounds:int,matches:int} */
	public function apply(int $stageId, array $options, int $actorId): array
	{
		$preview = $this->preview($stageId, $options);
		$query = $this->database->getQuery(true)->select('id')->from($this->database->quoteName('#__joomleague_schedule_generation'))->where('stage_id = :stage')->where('input_checksum = :checksum');
		$typedStage = $stageId; $checksum = $preview['checksum']; $query->bind(':stage',$typedStage,ParameterType::INTEGER)->bind(':checksum',$checksum); $existing = (int) $this->database->setQuery($query)->loadResult();
		if ($existing > 0) return ['generation_id'=>$existing,'reused'=>true,'rounds'=>count($preview['rounds']),'matches'=>$preview['match_count']];
		if ($preview['blocking'] > 0 && !$preview['options']['allow_conflicts']) throw new \DomainException(Text::_('COM_JOOMLEAGUE_SCHEDULE_BLOCKING_CONFLICTS'));
		$context = $preview['context']; $projectId = (int) $context['project']->id; $this->database->transactionStart();
		try {
			$generation = (object) ['uuid'=>UuidFactory::v4(),'project_id'=>$projectId,'stage_id'=>$stageId,'input_checksum'=>$checksum,'options_json'=>CanonicalJson::encodeObject($preview['options']),'round_count'=>count($preview['rounds']),'match_count'=>$preview['match_count'],'conflict_count'=>count($preview['conflicts']),'status'=>'applied','created_by'=>$actorId];
			$this->database->insertObject('#__joomleague_schedule_generation',$generation,'id'); $generationId=(int)$generation->id;
			foreach ($preview['rounds'] as $roundData) {
				$round=(object)['uuid'=>UuidFactory::v4(),'project_id'=>$projectId,'stage_id'=>$stageId,'name'=>$roundData['name'],'alias'=>'','code'=>$roundData['code'],'round_type'=>'standard','sequence_number'=>$roundData['sequence'],'start_date'=>$roundData['date'],'end_date'=>$roundData['date'],'lifecycle_state'=>'draft','published'=>$preview['options']['published'],'ordering'=>$roundData['sequence'],'created_by'=>$actorId];
				$this->database->insertObject('#__joomleague_project_round',$round,'id');
				foreach ($roundData['matches'] as $matchData) {
					$match=(object)['uuid'=>UuidFactory::v4(),'project_id'=>$projectId,'stage_id'=>$stageId,'round_id'=>(int)$round->id,'code'=>$matchData['code'],'match_number'=>$matchData['number'],'contest_type'=>(string)($context['profile']['contest']['type']??'head_to_head'),'scheduled_start'=>$matchData['scheduled_utc'],'timezone'=>null,'duration_minutes'=>$context['duration'],'venue_id'=>$matchData['venue']['id']??null,'status_code'=>'scheduled','metadata_json'=>CanonicalJson::encodeObject(['schedule_generation_id'=>$generationId,'template_id'=>$preview['template']->templateId]),'published'=>$preview['options']['published'],'ordering'=>$matchData['sequence'],'created_by'=>$actorId];
					$this->database->insertObject('#__joomleague_project_match',$match,'id');
					foreach ($matchData['participants'] as $slot => $entry) { $participant=(object)['uuid'=>UuidFactory::v4(),'match_id'=>(int)$match->id,'project_id'=>$projectId,'project_entry_id'=>(int)$entry['id'],'role_code'=>'participant','slot_number'=>$slot+1,'result_status'=>'scheduled','published'=>1,'ordering'=>$slot,'created_by'=>$actorId]; $this->database->insertObject('#__joomleague_match_participant',$participant,'id'); }
					$link=(object)['generation_id'=>$generationId,'match_id'=>(int)$match->id,'project_id'=>$projectId,'round_sequence'=>$roundData['sequence'],'match_sequence'=>$matchData['sequence']]; $this->database->insertObject('#__joomleague_schedule_generation_match',$link);
				}
			}
			$this->database->transactionCommit(); return ['generation_id'=>$generationId,'reused'=>false,'rounds'=>count($preview['rounds']),'matches'=>$preview['match_count']];
		} catch (\Throwable $error) { $this->database->transactionRollback(); throw $error; }
	}

	/** @return array<string,mixed> */
	private function context(int $stageId): array
	{
		$typedStageId = $stageId;
		$query=$this->database->getQuery(true)->select(['stage.*','project.name AS project_name','project.start_date AS project_start_date','project.end_date AS project_end_date','project.timezone AS project_timezone','project.default_start_time','version.payload_json'])
			->from($this->database->quoteName('#__joomleague_project_stage','stage'))->innerJoin($this->database->quoteName('#__joomleague_project','project').' ON project.id=stage.project_id')->innerJoin($this->database->quoteName('#__joomleague_sport_profile_version','version').' ON version.id=project.profile_version_id')->where('stage.id=:stage')->bind(':stage',$typedStageId,ParameterType::INTEGER);
		$row=$this->database->setQuery($query)->loadObject(); if(!$row)throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_ROUND_STAGE_INVALID'));
		$project=(object)['id'=>(int)$row->project_id,'name'=>$row->project_name,'start_date'=>$row->project_start_date,'end_date'=>$row->project_end_date,'timezone'=>$row->project_timezone,'default_start_time'=>$row->default_start_time];
		$profile=json_decode((string)$row->payload_json,true,512,JSON_THROW_ON_ERROR); $timezone=(string)($project->timezone?:Factory::getApplication()->get('offset','UTC')); new \DateTimeZone($timezone);
		$structure=$profile['match']['structure']??[]; $duration=(int)($structure['default_match_duration_minutes']??$this->durationFromStructure($structure)); $duration=$duration>0?$duration:60;
		$stage=(object)['id'=>(int)$row->id,'name'=>$row->name,'start_date'=>$row->start_date,'end_date'=>$row->end_date,'entry_selection_mode'=>$row->entry_selection_mode];
		return compact('project','stage','profile','timezone','duration')+['entries'=>$this->entries($stageId,(int)$row->project_id,(string)$row->entry_selection_mode)];
	}

	private function durationFromStructure(array $structure): int
	{
		$count=(int)($structure['period_count']??0); $length=(int)($structure['period_length_minutes']??0); $breaks=0; foreach($structure['breaks']??[] as $break)$breaks+=(int)($break['length_minutes']??0); return $count*$length+$breaks;
	}

	private function entries(int $stageId,int $projectId,string $mode): array
	{
		$query=$this->database->getQuery(true)->select(['entry.id','entry.seed_number','entry.ordering','entry.entry_kind','entry.display_name','team.name AS team_name','team.club_id','person.first_name','person.last_name','venue.id AS venue_id','venue.name AS venue_name'])
			->from($this->database->quoteName('#__joomleague_project_entry','entry'))->leftJoin($this->database->quoteName('#__joomleague_team','team').' ON team.id=entry.team_id')->leftJoin($this->database->quoteName('#__joomleague_person','person').' ON person.id=entry.person_id')->leftJoin($this->database->quoteName('#__joomleague_venue','venue').' ON venue.owner_club_id=team.club_id AND venue.published=1')->where('entry.project_id=:project')->where('entry.published=1')->bind(':project',$projectId,ParameterType::INTEGER);
		if($mode==='explicit')$query->innerJoin($this->database->quoteName('#__joomleague_stage_entry','stage_entry').' ON stage_entry.entry_id=entry.id AND stage_entry.stage_id='.(int)$stageId)->order(['COALESCE(stage_entry.seed_number,entry.seed_number,2147483647) ASC','stage_entry.ordering ASC','entry.id ASC','venue.id ASC']);else$query->order(['COALESCE(entry.seed_number,2147483647) ASC','entry.ordering ASC','entry.id ASC','venue.id ASC']);
		$result=[];$seen=[];foreach($this->database->setQuery($query)->loadObjectList() as $row){if(isset($seen[(int)$row->id]))continue;$seen[(int)$row->id]=true;$name=match((string)$row->entry_kind){'team'=>(string)$row->team_name,'person'=>trim((string)$row->first_name.' '.(string)$row->last_name),default=>(string)$row->display_name};$result[]=['id'=>(int)$row->id,'name'=>$name,'seed'=>count($result)+1,'venue'=>$row->venue_id===null?null:['id'=>(int)$row->venue_id,'name'=>(string)$row->venue_name]];}
		return $result;
	}

	private function options(array $raw): array
	{
		$template=trim((string)($raw['template_id']??''));$date=(string)($raw['start_date']??'');$time=(string)($raw['start_time']??'');
		if(preg_match('/^[a-z][a-z0-9-]*-v[0-9]+$/',$template)!==1||!$this->date($date)||preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/',$time)!==1)throw new \InvalidArgumentException(Text::_('COM_JOOMLEAGUE_SCHEDULE_OPTIONS_INVALID'));
		$interval=max(1,min(365,(int)($raw['round_interval_days']??7)));$matchInterval=max(0,min(1440,(int)($raw['match_interval_minutes']??0)));$raceRounds=max(1,min(200,(int)($raw['race_rounds']??1)));$first=max(0,(int)($raw['first_match_number']??1));
		return ['template_id'=>$template,'start_date'=>$date,'start_time'=>$time,'round_interval_days'=>$interval,'match_interval_minutes'=>$matchInterval,'return_legs'=>(int)!empty($raw['return_legs']),'race_rounds'=>$raceRounds,'first_match_number'=>$first,'assign_home_venues'=>(int)!empty($raw['assign_home_venues']),'published'=>(int)!empty($raw['published']),'allow_conflicts'=>(int)!empty($raw['allow_conflicts'])];
	}

	private function date(string $value):bool{$date=\DateTimeImmutable::createFromFormat('!Y-m-d',$value);return$date!==false&&$date->format('Y-m-d')===$value;}
	private function mirrorFor(string $templateId):?object{foreach($this->templates->all() as $candidate)if(($candidate->secondHalf->mirrorOf??null)===$templateId)return$candidate;return null;}
	private function maxRoundSequence(int $stageId):int{$query=$this->database->getQuery(true)->select('COALESCE(MAX(sequence_number),0)')->from($this->database->quoteName('#__joomleague_project_round'))->where('stage_id=:stage')->bind(':stage',$stageId,ParameterType::INTEGER);return(int)$this->database->setQuery($query)->loadResult();}

	private function conflicts(array $context,array $rounds):array
	{
		$conflicts=[];$generated=[];foreach($rounds as $round)foreach($round['matches'] as $match){$start=strtotime($match['scheduled_utc'].' UTC');$end=$start+$context['duration']*60;foreach($generated as $other){$overlap=$start<$other['end']&&$end>$other['start'];if(!$overlap)continue;$shared=array_intersect(array_column($match['participants'],'id'),$other['entries']);if($shared!==[])$conflicts[]=['severity'=>'error','code'=>'participant_overlap','message'=>Text::sprintf('COM_JOOMLEAGUE_SCHEDULE_CONFLICT_PARTICIPANT',$match['scheduled_local'])];if(($match['venue']['id']??0)>0&&($match['venue']['id']??0)===$other['venue'])$conflicts[]=['severity'=>'error','code'=>'venue_overlap','message'=>Text::sprintf('COM_JOOMLEAGUE_SCHEDULE_CONFLICT_VENUE',$match['venue']['name'],$match['scheduled_local'])];}$generated[]=['start'=>$start,'end'=>$end,'entries'=>array_column($match['participants'],'id'),'venue'=>$match['venue']['id']??0];if(($context['profile']['contest']['type']??'')==='head_to_head'&&$match['venue']===null)$conflicts[]=['severity'=>'warning','code'=>'venue_missing','message'=>Text::sprintf('COM_JOOMLEAGUE_SCHEDULE_WARNING_VENUE_MISSING',$match['participants'][0]['name']??'')];}
		$query=$this->database->getQuery(true)->select(['match.id','match.scheduled_start','match.duration_minutes','match.venue_id','participant.project_entry_id'])->from($this->database->quoteName('#__joomleague_project_match','match'))->leftJoin($this->database->quoteName('#__joomleague_match_participant','participant').' ON participant.match_id=match.id')->where('match.project_id=:project')->where('match.scheduled_start IS NOT NULL')->bind(':project',$context['project']->id,ParameterType::INTEGER)->order('match.id ASC');$existing=[];foreach($this->database->setQuery($query)->loadObjectList() as$row){$key=(int)$row->id;$existing[$key]['start']=strtotime((string)$row->scheduled_start.' UTC');$existing[$key]['end']=$existing[$key]['start']+((int)($row->duration_minutes?:$context['duration']))*60;$existing[$key]['venue']=(int)$row->venue_id;if($row->project_entry_id!==null)$existing[$key]['entries'][]=(int)$row->project_entry_id;$existing[$key]['entries']??=[];}foreach($generated as$item)foreach($existing as$other)if($item['start']<$other['end']&&$item['end']>$other['start']){if(array_intersect($item['entries'],$other['entries'])!==[])$conflicts[]=['severity'=>'error','code'=>'existing_participant_overlap','message'=>Text::_('COM_JOOMLEAGUE_SCHEDULE_CONFLICT_EXISTING_PARTICIPANT')];if($item['venue']>0&&$item['venue']===$other['venue'])$conflicts[]=['severity'=>'error','code'=>'existing_venue_overlap','message'=>Text::_('COM_JOOMLEAGUE_SCHEDULE_CONFLICT_EXISTING_VENUE')];}
		$endDate=$rounds===[]?null:end($rounds)['date'];if($endDate&&$context['project']->end_date&&$endDate>$context['project']->end_date)$conflicts[]=['severity'=>'warning','code'=>'project_end','message'=>Text::_('COM_JOOMLEAGUE_SCHEDULE_WARNING_PROJECT_END')];if($endDate&&$context['stage']->end_date&&$endDate>$context['stage']->end_date)$conflicts[]=['severity'=>'warning','code'=>'stage_end','message'=>Text::_('COM_JOOMLEAGUE_SCHEDULE_WARNING_STAGE_END')];return$conflicts;
	}
}
