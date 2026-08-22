<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\CMS\Language\Text;

final class ProjectPreflightService
{
	public function __construct(private readonly DatabaseInterface $database) {}

	/** @return array<string,mixed> */
	public function inspect(int $projectId): array
	{
		if ($projectId < 1) throw new \InvalidArgumentException(Text::_('COM_JOOMLEAGUE_PREFLIGHT_ERROR_PROJECT_ID'));
		[$project, $profile] = $this->context($projectId);
		$sections = [
			$this->profileSection($projectId, $profile),
			$this->entrySection($projectId, $profile),
			$this->stageSection($projectId),
			$this->scheduleSection($projectId, (string) ($profile['contest']['type'] ?? '')),
			$this->officialSection($projectId, $profile),
			$this->resultSection($projectId, $profile),
		];
		$summary = ['error' => 0, 'warning' => 0, 'success' => 0, 'info' => 0];
		foreach ($sections as &$section) {
			$section['status'] = 'success';
			foreach ($section['checks'] as $check) {
				$summary[$check['severity']]++;
				if ($check['severity'] === 'error' || ($check['severity'] === 'warning' && $section['status'] !== 'error')) $section['status'] = $check['severity'];
			}
			if ($section['checks'] === []) $summary['success']++;
		}
		unset($section);
		return ['project' => $project, 'sections' => $sections, 'summary' => $summary, 'ready' => $summary['error'] === 0, 'checked_at' => gmdate('Y-m-d H:i:s')];
	}

	/** @return array{0:object,1:array<string,mixed>} */
	private function context(int $projectId): array
	{
		$bound = $projectId;
		$query = $this->database->getQuery(true)->select(['project.id','project.name','project.lifecycle_state','project.published','version.payload_json','profile.code AS profile_code','version.profile_version'])
			->from($this->database->quoteName('#__joomleague_project','project'))->innerJoin($this->database->quoteName('#__joomleague_sport_profile_version','version').' ON version.id=project.profile_version_id')->innerJoin($this->database->quoteName('#__joomleague_sport_profile','profile').' ON profile.id=version.profile_id')->where('project.id=:project')->bind(':project',$bound,ParameterType::INTEGER);
		$row=$this->database->setQuery($query)->loadObject();
		if(!$row)throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_PREFLIGHT_ERROR_PROJECT_MISSING'));
		$profile=json_decode((string)$row->payload_json,true,512,JSON_THROW_ON_ERROR);unset($row->payload_json);return[$row,$profile];
	}

	private function profileSection(int $projectId,array $profile):array
	{
		$checks=[];
		try{(new SportProfileSchemaValidator())->validate($profile);(new EntryModelValidator())->validate($profile);}catch(\Throwable $error){$checks[]=$this->check('profile_contract','error','COM_JOOMLEAGUE_PREFLIGHT_PROFILE_INVALID',[],'index.php?option=com_joomleague&view=sportprofiles');}
		return$this->section('profile','COM_JOOMLEAGUE_PREFLIGHT_SECTION_PROFILE',$checks,['profile_code'=>(string)($profile['code']??''),'schema_version'=>(string)($profile['schema_version']??'')]);
	}

	private function entrySection(int $projectId,array $profile):array
	{
		$bound=$projectId;$query=$this->database->getQuery(true)->select(['entry.id','entry.entry_kind','entry.display_name','entry.team_id','entry.person_id','team.name AS team_name','person.first_name','person.last_name'])->from($this->database->quoteName('#__joomleague_project_entry','entry'))->leftJoin($this->database->quoteName('#__joomleague_team','team').' ON team.id=entry.team_id')->leftJoin($this->database->quoteName('#__joomleague_person','person').' ON person.id=entry.person_id')->where('entry.project_id=:project')->where('entry.published=1')->bind(':project',$bound,ParameterType::INTEGER)->order('entry.id ASC');$rows=$this->database->setQuery($query)->loadObjectList();$checks=[];$allowed=$profile['entry_model']['allowed_kinds']??[];
		if($rows===[])$checks[]=$this->check('entries_empty','error','COM_JOOMLEAGUE_PREFLIGHT_ENTRIES_EMPTY',[],$this->projectUrl('projectentries',$projectId));
		foreach($rows as$row){$name=match((string)$row->entry_kind){'team'=>(string)$row->team_name,'person'=>trim((string)$row->first_name.' '.(string)$row->last_name),default=>(string)$row->display_name};if(!in_array((string)$row->entry_kind,$allowed,true)||trim($name)==='')$checks[]=$this->check('entry_invalid','error','COM_JOOMLEAGUE_PREFLIGHT_ENTRY_INVALID',[(int)$row->id],$this->projectUrl('projectentries',$projectId));}
		return$this->section('entries','COM_JOOMLEAGUE_PREFLIGHT_SECTION_ENTRIES',$checks,['total'=>count($rows)]);
	}

	private function stageSection(int $projectId):array
	{
		$bound=$projectId;$query=$this->database->getQuery(true)->select(['stage.id','stage.name','stage.entry_selection_mode','COUNT(stage_entry.entry_id) AS assigned'])->from($this->database->quoteName('#__joomleague_project_stage','stage'))->leftJoin($this->database->quoteName('#__joomleague_stage_entry','stage_entry').' ON stage_entry.stage_id=stage.id')->where('stage.project_id=:project')->bind(':project',$bound,ParameterType::INTEGER)->group(['stage.id','stage.name','stage.entry_selection_mode'])->order('stage.ordering ASC,stage.id ASC');$rows=$this->database->setQuery($query)->loadObjectList();$checks=[];
		if($rows===[])$checks[]=$this->check('stages_empty','error','COM_JOOMLEAGUE_PREFLIGHT_STAGES_EMPTY',[],$this->projectUrl('stages',$projectId));
		foreach($rows as$row)if($row->entry_selection_mode==='explicit'&&(int)$row->assigned===0)$checks[]=$this->check('stage_entries_empty','error','COM_JOOMLEAGUE_PREFLIGHT_STAGE_ENTRIES_EMPTY',[(string)$row->name],'index.php?option=com_joomleague&view=stageentries&stage_id='.(int)$row->id);
		return$this->section('stages','COM_JOOMLEAGUE_PREFLIGHT_SECTION_STAGES',$checks,['total'=>count($rows)]);
	}

	private function scheduleSection(int $projectId,string $contestType):array
	{
		$bound=$projectId;$query=$this->database->getQuery(true)->select(['match.id','match.round_id','match.match_number','match.scheduled_start','match.venue_id','round.name AS round_name','COUNT(participant.id) AS participants'])->from($this->database->quoteName('#__joomleague_project_match','match'))->innerJoin($this->database->quoteName('#__joomleague_project_round','round').' ON round.id=match.round_id')->leftJoin($this->database->quoteName('#__joomleague_match_participant','participant').' ON participant.match_id=match.id AND participant.published=1')->where('match.project_id=:project')->bind(':project',$bound,ParameterType::INTEGER)->group(['match.id','match.round_id','match.match_number','match.scheduled_start','match.venue_id','round.name'])->order('match.id ASC');$matches=$this->database->setQuery($query)->loadObjectList();$roundCount=$this->count('#__joomleague_project_round',$projectId);$checks=[];
		if($roundCount===0)$checks[]=$this->check('rounds_empty','error','COM_JOOMLEAGUE_PREFLIGHT_ROUNDS_EMPTY',[],$this->projectUrl('stages',$projectId));elseif($matches===[])$checks[]=$this->check('matches_empty','error','COM_JOOMLEAGUE_PREFLIGHT_MATCHES_EMPTY',[],$this->projectUrl('stages',$projectId));
		foreach($matches as$match){$participants=(int)$match->participants;$valid=$contestType==='head_to_head'?$participants===2:$participants>=1;if(!$valid)$checks[]=$this->check('match_participants','error','COM_JOOMLEAGUE_PREFLIGHT_MATCH_PARTICIPANTS',[$this->matchLabel($match),$participants],$this->matchUrl((int)$match->id));if($match->scheduled_start===null)$checks[]=$this->check('match_date','warning','COM_JOOMLEAGUE_PREFLIGHT_MATCH_DATE',[$this->matchLabel($match)],$this->matchUrl((int)$match->id));}
		return$this->section('schedule','COM_JOOMLEAGUE_PREFLIGHT_SECTION_SCHEDULE',$checks,['rounds'=>$roundCount,'matches'=>count($matches)]);
	}

	private function officialSection(int $projectId,array $profile):array
	{
		$definitions=count(array_filter($profile['positions']??[],static fn(mixed $position):bool=>is_array($position)&&($position['person_type']??null)==='official'));$projectOfficials=$this->count('#__joomleague_project_actor_role',$projectId);$matchCount=$this->count('#__joomleague_project_match',$projectId);$bound=$projectId;$query=$this->database->getQuery(true)->select('COUNT(DISTINCT match.id)')->from($this->database->quoteName('#__joomleague_project_match','match'))->innerJoin($this->database->quoteName('#__joomleague_match_actor_role','role').' ON role.match_id=match.id')->where('match.project_id=:project')->bind(':project',$bound,ParameterType::INTEGER);$covered=(int)$this->database->setQuery($query)->loadResult();$checks=[];
		if($definitions>0&&$projectOfficials===0)$checks[]=$this->check('project_officials_empty','warning','COM_JOOMLEAGUE_PREFLIGHT_PROJECT_OFFICIALS_EMPTY',[],$this->projectUrl('projectofficials',$projectId));
		if($definitions>0&&$matchCount>0&&$covered<$matchCount)$checks[]=$this->check('match_officials_incomplete','warning','COM_JOOMLEAGUE_PREFLIGHT_MATCH_OFFICIALS',[$matchCount-$covered],$this->projectUrl('stages',$projectId));
		return$this->section('officials','COM_JOOMLEAGUE_PREFLIGHT_SECTION_OFFICIALS',$checks,['definitions'=>$definitions,'project_assignments'=>$projectOfficials,'covered_matches'=>$covered]);
	}

	private function resultSection(int $projectId,array $profile):array
	{
		$bound=$projectId;$query=$this->database->getQuery(true)->select(['match.id','match.match_number','round.name AS round_name','result.id AS result_id','result.status_code'])->from($this->database->quoteName('#__joomleague_project_match','match'))->innerJoin($this->database->quoteName('#__joomleague_project_round','round').' ON round.id=match.round_id')->leftJoin($this->database->quoteName('#__joomleague_match_result','result').' ON result.match_id=match.id')->where('match.project_id=:project')->bind(':project',$bound,ParameterType::INTEGER)->order('match.id ASC');$rows=$this->database->setQuery($query)->loadObjectList();$checks=[];$final=0;$repository=new MatchResultRepository($this->database);$validator=new MatchResultPayloadValidator();
		foreach($rows as$row){if($row->result_id===null)continue;try{$payload=$repository->get((int)$row->id);$participants=$this->participantIds((int)$row->id);if($payload===null)throw new \UnexpectedValueException();$validator->validate($profile,$participants,$payload);if($payload['status_code']==='final')$final++;}catch(\Throwable $error){$checks[]=$this->check('result_invalid','error','COM_JOOMLEAGUE_PREFLIGHT_RESULT_INVALID',[$this->matchLabel($row)],'index.php?option=com_joomleague&view=matchresult&match_id='.(int)$row->id);}}
		return$this->section('results','COM_JOOMLEAGUE_PREFLIGHT_SECTION_RESULTS',$checks,['matches'=>count($rows),'final_results'=>$final]);
	}

	private function participantIds(int $matchId):array{$bound=$matchId;$query=$this->database->getQuery(true)->select('id')->from($this->database->quoteName('#__joomleague_match_participant'))->where('match_id=:match')->where('published=1')->bind(':match',$bound,ParameterType::INTEGER)->order('slot_number ASC');return array_map('intval',$this->database->setQuery($query)->loadColumn());}
	private function count(string $table,int $projectId):int{$bound=$projectId;$query=$this->database->getQuery(true)->select('COUNT(*)')->from($this->database->quoteName($table))->where('project_id=:project')->bind(':project',$bound,ParameterType::INTEGER);return(int)$this->database->setQuery($query)->loadResult();}
	private function section(string $code,string $label,array $checks,array $metrics):array{return compact('code','label','checks','metrics');}
	private function check(string $code,string $severity,string $message,array $arguments,string $url):array{return compact('code','severity','message','arguments','url');}
	private function projectUrl(string $view,int $projectId):string{return'index.php?option=com_joomleague&view='.$view.'&project_id='.$projectId;}
	private function matchUrl(int $matchId):string{return'index.php?option=com_joomleague&task=match.edit&id='.$matchId;}
	private function matchLabel(object $match):string{return(string)($match->match_number?:($match->round_name.' / #'.$match->id));}
}
