<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Compares two entries through completed programme events in which both participated. */
final class ParticipantComparisonReader
{
	public function __construct(private readonly DatabaseInterface $database) {}

	/** @param list<int> $viewLevels @return array<string,mixed> */
	public function forProject(int $projectId, ?int $firstId, ?int $secondId, array $viewLevels): array
	{
		if ($projectId < 1) throw new \InvalidArgumentException('A positive project ID is required.');
		$db=$this->database; $levels=array_values(array_unique(array_filter(array_map('intval',$viewLevels),static fn(int$id):bool=>$id>0)))?:[1]; $access=implode(',',$levels);
		$project=$db->setQuery($db->getQuery(true)->select(['project.id','project.name','version.payload_json'])->from($db->quoteName('#__joomleague_project','project'))
			->innerJoin($db->quoteName('#__joomleague_competition','competition').' ON competition.id=project.competition_id AND competition.published=1 AND competition.access IN ('.$access.')')
			->innerJoin($db->quoteName('#__joomleague_season','season').' ON season.id=project.season_id AND season.published=1 AND season.access IN ('.$access.')')
			->innerJoin($db->quoteName('#__joomleague_sport_profile_version','version').' ON version.id=project.profile_version_id')
			->where('project.id=:project')->where('project.published=1')->where('project.access IN ('.$access.')')->bind(':project',$projectId,ParameterType::INTEGER))->loadObject();
		if(!$project)throw new \RuntimeException('Comparison project is unavailable.');
		$entries=$db->setQuery($db->getQuery(true)->select(['entry.id','entry.entry_kind',"COALESCE(NULLIF(entry.display_name,''),team.name,NULLIF(TRIM(CONCAT(person.first_name,' ',person.last_name)),''),CONCAT('ID ',entry.id)) AS display_name"])
			->from($db->quoteName('#__joomleague_project_entry','entry'))->leftJoin($db->quoteName('#__joomleague_team','team').' ON team.id=entry.team_id AND team.published=1 AND team.access IN ('.$access.')')
			->leftJoin($db->quoteName('#__joomleague_person','person').' ON person.id=entry.person_id AND person.published=1 AND person.access IN ('.$access.')')
			->where('entry.project_id=:project')->where('entry.published=1')->where("(entry.entry_kind='group' OR (entry.entry_kind='team' AND team.id IS NOT NULL) OR (entry.entry_kind='person' AND person.id IS NOT NULL))")
			->bind(':project',$projectId,ParameterType::INTEGER)->order('display_name ASC, entry.id ASC'))->loadObjectList('id');
		$result=['project'=>$project,'entries'=>array_values($entries),'selected'=>null,'events'=>[],'summary'=>['first'=>0,'draw'=>0,'second'=>0,'unresolved'=>0]];
		if($firstId===null||$secondId===null)return$result;
		if($firstId===$secondId||!isset($entries[$firstId],$entries[$secondId]))throw new \InvalidArgumentException('Two distinct project entries are required.');
		$ids=[$firstId,$secondId]; $matchQuery=$db->getQuery(true)->select('participant.match_id')->from($db->quoteName('#__joomleague_match_participant','participant'))
			->innerJoin($db->quoteName('#__joomleague_project_match','item').' ON item.id=participant.match_id AND item.published=1')
			->innerJoin($db->quoteName('#__joomleague_match_result','result')." ON result.match_id=item.id AND result.status_code='final'")
			->where('item.project_id=:project')->where('participant.published=1')->whereIn('participant.project_entry_id',$ids,ParameterType::INTEGER)
			->group('participant.match_id')->having('COUNT(DISTINCT participant.project_entry_id)=2')->bind(':project',$projectId,ParameterType::INTEGER);
		$matchIds=array_map('intval',$db->setQuery($matchQuery)->loadColumn()); $profile=json_decode((string)$project->payload_json,true); unset($project->payload_json);
		$result['selected']=[$entries[$firstId],$entries[$secondId]]; if($matchIds===[])return$result;
		$matches=$db->setQuery($db->getQuery(true)->select(['item.id','item.scheduled_start','round.name AS round_name','stage.name AS stage_name'])->from($db->quoteName('#__joomleague_project_match','item'))
			->innerJoin($db->quoteName('#__joomleague_project_round','round').' ON round.id=item.round_id AND round.published=1')->innerJoin($db->quoteName('#__joomleague_project_stage','stage').' ON stage.id=item.stage_id AND stage.published=1')
			->whereIn('item.id',$matchIds,ParameterType::INTEGER)->order('item.scheduled_start DESC, item.id DESC'))->loadObjectList('id');
		$participants=$db->setQuery($db->getQuery(true)->select(['participant.id','participant.match_id','participant.project_entry_id'])->from($db->quoteName('#__joomleague_match_participant','participant'))
			->whereIn('participant.match_id',$matchIds,ParameterType::INTEGER)->whereIn('participant.project_entry_id',$ids,ParameterType::INTEGER)->where('participant.published=1'))->loadObjectList();
		$values=$db->setQuery($db->getQuery(true)->select(['value.participant_id','value.numeric_value','value.text_value','value.status_code','value.result_rank'])->from($db->quoteName('#__joomleague_match_score_value','value'))
			->innerJoin($db->quoteName('#__joomleague_match_score_segment','segment').' ON segment.id=value.segment_id AND segment.parent_id IS NULL')->whereIn('value.match_id',$matchIds,ParameterType::INTEGER))->loadObjectList('participant_id');
		$participantMap=[];foreach($participants as$p)$participantMap[(int)$p->match_id][(int)$p->project_entry_id]=(int)$p->id;
		$higher=(bool)($profile['match']['score']['higher_is_better']??true);
		foreach($matches as$match){$first=$this->value($values[$participantMap[(int)$match->id][$firstId]??0]??null);$second=$this->value($values[$participantMap[(int)$match->id][$secondId]??0]??null);$outcome=$this->outcome($first,$second,$higher);$result['summary'][$outcome]++;$result['events'][]=['event'=>$match,'first'=>$first,'second'=>$second,'outcome'=>$outcome];}
		return$result;
	}

	/** @return array{display:string,numeric:?float,rank:?int}|null */
	private function value(?object$value):?array{if(!$value)return null;$display=$value->numeric_value!==null?rtrim(rtrim((string)$value->numeric_value,'0'),'.'):(string)($value->text_value?:($value->status_code?:($value->result_rank!==null?'#'.(int)$value->result_rank:'')));return['display'=>$display,'numeric'=>$value->numeric_value!==null?(float)$value->numeric_value:null,'rank'=>$value->result_rank!==null?(int)$value->result_rank:null];}
	private function outcome(?array$a,?array$b,bool$higher):string{if(!$a||!$b)return'unresolved';if($a['rank']!==null&&$b['rank']!==null)return$a['rank']===$b['rank']?'draw':($a['rank']<$b['rank']?'first':'second');if($a['numeric']!==null&&$b['numeric']!==null){if($a['numeric']===$b['numeric'])return'draw';$first=$a['numeric']>$b['numeric'];return($higher===$first)?'first':'second';}return'unresolved';}
}
