<?php
declare(strict_types=1);

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultFormPayloadBuilder;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultRepository;
use Joomleague\Component\Joomleague\Domain\Service\MatchOutcomeResolver;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

/** Executes only inside the guarded disposable football-result-state bootstrap. */
function verifyResultFamily(DatabaseInterface $db, string $code, array $fixture, bool $browser = false): array
{
    if (!in_array($code, ['tennis', 'running_race', 'mma_boxing'], true)) throw new InvalidArgumentException('Unsupported fixture family');
    $checks = 0;
    $assert = static function(bool $condition, string $message) use (&$checks): void { if (!$condition) throw new RuntimeException($message); ++$checks; };
    $counts = static function() use ($db): array {
        $result=[];
        foreach (['sport_type','project','project_stage','project_round','project_entry','project_match','match_participant','match_result','match_score_segment','match_score_value'] as $table) {
            $result[$table]=(int)$db->setQuery($db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_'.$table)))->loadResult();
        }
        return $result;
    };
    $before=$counts();
    $insert = static function(string $table, array $data, bool $uuid = true) use ($db): int {
        if ($uuid) $data['uuid'] = UuidFactory::v4();
        $data['published'] ??= 1;
        $row = (object) $data; $db->insertObject('#__joomleague_'.$table, $row, 'id'); return (int) $row->id;
    };
    $query = $db->getQuery(true)->select(['version.id','version.payload_json'])
        ->from($db->quoteName('#__joomleague_sport_profile_version','version'))
        ->innerJoin($db->quoteName('#__joomleague_sport_profile','profile').' ON profile.id = version.profile_id')
        ->where('profile.code = :code')->where("version.state = 'active'")->order('version.id DESC')->bind(':code',$code);
    $version = $db->setQuery($query,0,1)->loadObject();
    $assert($version !== null, 'Required installed family profile missing: '.$code);
    $profile = json_decode($version->payload_json,true,512,JSON_THROW_ON_ERROR);
    $a = $fixture['projects']['a']; $id = (int) $a['id'];
    $base = $db->setQuery($db->getQuery(true)->select(['competition_id','season_id'])->from($db->quoteName('#__joomleague_project'))->where('id = :id')->bind(':id',$id,ParameterType::INTEGER))->loadObject();
    $sport = $insert('sport_type',['profile_version_id'=>$version->id,'code'=>'audit_family_'.$code,'name'=>'Audit '.$code],false);
    $project = null; $retained = false;
    try {
        $project = $insert('project',['competition_id'=>$base->competition_id,'season_id'=>$base->season_id,'sport_type_id'=>$sport,'profile_version_id'=>$version->id,'name'=>'Audit '.$code,'project_type'=>'tournament']);
        $stage = $insert('project_stage',['project_id'=>$project,'code'=>'main','name'=>'Main','stage_type'=>'knockout']);
        $round = $insert('project_round',['project_id'=>$project,'stage_id'=>$stage,'name'=>'Final','code'=>'final','sequence_number'=>1]);
        $match = $insert('project_match',['project_id'=>$project,'stage_id'=>$stage,'round_id'=>$round]);
        $participants = [];
        foreach (['First','Second'] as $i=>$name) {
            $entry = $insert('project_entry',['project_id'=>$project,'entry_kind'=>'group','display_name'=>$name]);
            $participants[] = $insert('match_participant',['match_id'=>$match,'project_id'=>$project,'project_entry_id'=>$entry,'slot_number'=>$i+1]);
        }
        $values = static function(array $scores, string $field='numeric_value') use ($participants): array {
            return array_map(static fn(int $index): array => ['participant_id'=>$participants[$index], $field=>$scores[$index]], [0,1]);
        };
        $make = static function(bool $reverse) use ($code,$values,$profile): array {
            $root = ['level_code'=>'result','values'=>[],'children'=>[]];
            if ($code === 'tennis') {
                $root['values']=$values($reverse?[1,2]:[2,1]);
                foreach ([[1,0],[0,1],[1,0]] as $i=>$scores) {
                    if ($reverse) $scores=array_reverse($scores);
                    $games=$scores[0]===1?[6,4]:[4,6];
                    $root['children'][]=['level_code'=>'set','sequence_number'=>$i+1,'values'=>$values($scores),'children'=>[['level_code'=>'game','sequence_number'=>1,'values'=>$values($games)]]];
                }
            } elseif ($code === 'running_race') {
                $root['values']=$values($reverse?['1:06.000','1:05.100']:['1:02.345','1:05.100'],'duration_value');
                foreach (['gun_time','chip_time'] as $level) $root['children'][]=['level_code'=>$level,'values'=>$root['values']];
            } else {
                $root['values']=$values($reverse?['loser','winner']:['winner','loser'],'status_code');
                foreach ($root['values'] as $i=>&$value) $value['result_rank']=$reverse?2-$i:$i+1;
                unset($value);
            }
            return (new MatchResultFormPayloadBuilder())->build(['result_type'=>$profile['match']['score']['type'],'status_code'=>'final','outcome_code'=>'completed','segments'=>[$root]],$profile['match']['score']);
        };
        $repo = new MatchResultRepository($db);
        $payload = $make(false); $repo->replace($match,$payload,0); $stored=$repo->get($match);
        $assert($stored !== null, 'Family save missing');
        $root=$stored['segments'][0];
        if ($code==='tennis') $assert(count($root['children'])===3 && $root['values'][0]['numeric_value']==='2.000000000','Tennis nested sets lost');
        if ($code==='running_race') $assert($root['values'][0]['numeric_value']==='62345.000000000' && count($root['children'])===2,'Duration precision/segments lost');
        $objects=array_map(static fn(array $value): object => (object)$value,$root['values']);
        $assert(MatchOutcomeResolver::resolve($objects,[],($profile['match']['score']['higher_is_better']??true)!==false)===[$participants[0]],'Initial family winner wrong');
        $repo->replace($match,$make(true),0); $corrected=$repo->get($match);
        $objects=array_map(static fn(array $value): object => (object)$value,$corrected['segments'][0]['values']);
        $assert(MatchOutcomeResolver::resolve($objects,[],($profile['match']['score']['higher_is_better']??true)!==false)===[$participants[1]],'Corrected family winner wrong');
        $bad=$make(true);$bad['segments'][0]['values'][0]['participant_id']=(int)$fixture['projects']['b']['records']['Match']+999999;
        $rejected=false;try{$repo->replace($match,$bad,0);}catch(InvalidArgumentException){$rejected=true;}
        $assert($rejected && $repo->get($match)===$corrected,'Rejected family replacement changed history');
        $draft=$make(true);$draft['status_code']='draft';$repo->replace($match,$draft,0);
        $assert($repo->get($match)['status_code']==='draft','Family draft not saved');
        if ($browser) {
            $repo->replace($match,$make(false),0);
            $metadata=compact('code','sport','project','match','participants','before');
            file_put_contents('/tmp/jl-family-'.$code.'.json',json_encode($metadata,JSON_THROW_ON_ERROR));
            $retained=true;
            return $metadata;
        }
        return ['family'=>$code,'checks'=>$checks,'passed'=>true];
    } finally {
        if (!$retained) {
        if ($project !== null) $db->setQuery($db->getQuery(true)->delete($db->quoteName('#__joomleague_project'))->where('id = :id')->bind(':id',$project,ParameterType::INTEGER))->execute();
        $db->setQuery($db->getQuery(true)->delete($db->quoteName('#__joomleague_sport_type'))->where('id = :id')->bind(':id',$sport,ParameterType::INTEGER))->execute();
        $assert($counts()===$before,'Family fixture cleanup left component rows');
        }
    }
}
