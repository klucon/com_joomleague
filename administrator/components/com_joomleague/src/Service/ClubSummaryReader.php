<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Domain\Service;
defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
final class ClubSummaryReader
{
	public function __construct(private readonly DatabaseInterface $db) {}
	/** @param list<int> $viewLevels @return array<string,mixed> */
	public function read(int $clubId,array $viewLevels):array
	{
		if($clubId<1)return['error'=>'club_required'];
		$levels=array_values(array_unique(array_filter(array_map('intval',$viewLevels),static fn(int$id):bool=>$id>0)));$access=implode(',',$levels?:[1]);$db=$this->db;
		$club=$db->setQuery($db->getQuery(true)->select(['id','name','short_name','country_code','website','logo','founded_date','dissolved_date','description'])->from($db->quoteName('#__joomleague_club','club'))->where('id=:clubId')->where('club.published=1')->where('club.access IN ('.$access.')')->bind(':clubId',$clubId,ParameterType::INTEGER))->loadObject();
		if(!$club)return['error'=>'club_unavailable'];
		$teams=$db->setQuery($db->getQuery(true)->select(['id','name','middle_name','short_name','website','logo','picture','description'])->from($db->quoteName('#__joomleague_team','team'))->where('club_id=:clubId')->where('team.published=1')->where('team.access IN ('.$access.')')->bind(':clubId',$clubId,ParameterType::INTEGER)->order('ordering ASC,name ASC,id ASC'))->loadObjectList();
		$teamsById=[];foreach($teams as$team){$team->projects=[];$teamsById[(int)$team->id]=$team;}
		if($teamsById!==[]){$entries=$db->setQuery($db->getQuery(true)->select(['entry.id','entry.team_id','project.id AS project_id','project.name AS project_name','competition.name AS competition_name','season.name AS season_name','sport_type.name AS sport_type_name'])->from($db->quoteName('#__joomleague_project_entry','entry'))->innerJoin($db->quoteName('#__joomleague_project','project').' ON project.id=entry.project_id AND project.published=1')->innerJoin($db->quoteName('#__joomleague_competition','competition').' ON competition.id=project.competition_id AND competition.published=1')->innerJoin($db->quoteName('#__joomleague_season','season').' ON season.id=project.season_id AND season.published=1')->innerJoin($db->quoteName('#__joomleague_sport_type','sport_type').' ON sport_type.id=project.sport_type_id AND sport_type.published=1')->whereIn('entry.team_id',array_keys($teamsById),ParameterType::INTEGER)->where("entry.entry_kind='team'")->where('entry.published=1')->where('project.access IN ('.$access.')')->where('competition.access IN ('.$access.')')->where('season.access IN ('.$access.')')->order('season.name DESC,project.name ASC,entry.id ASC'))->loadObjectList();foreach($entries as$entry)if(isset($teamsById[(int)$entry->team_id]))$teamsById[(int)$entry->team_id]->projects[]=$entry;}
		return['club'=>$club,'teams'=>array_values($teamsById)];
	}
}
