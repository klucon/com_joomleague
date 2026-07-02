<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Model;
\defined('_JEXEC') or die;
use Joomla\CMS\Application\AdministratorApplication; use Joomla\CMS\MVC\Model\BaseDatabaseModel; use Joomla\Database\ParameterType;
final class ProjectpanelModel extends BaseDatabaseModel
{
 private AdministratorApplication $application;
 public function setApplication(AdministratorApplication $application):void{$this->application=$application;}
 public function getProject():?object
 {
  $input=$this->application->getInput();
  $id=$input->getInt('project_id');
  if(!$id){$pid=$input->get('pid',[],'array');$id=(int)($pid[0]??0);}
  if(!$id)return null;$d=$this->getDatabase();
  $q=$d->createQuery()->select('p.*,l.name AS league,s.name AS season,st.name AS sport,(SELECT COUNT(*) FROM #__joomleague_project_position pp WHERE pp.project_id=p.id) AS position_count,(SELECT COUNT(*) FROM #__joomleague_project_referee pr WHERE pr.project_id=p.id) AS referee_count,(SELECT COUNT(*) FROM #__joomleague_project_team pt WHERE pt.project_id=p.id) AS team_count,(SELECT COUNT(*) FROM #__joomleague_division dv WHERE dv.project_id=p.id) AS division_count,(SELECT COUNT(*) FROM #__joomleague_round r WHERE r.project_id=p.id) AS round_count,(SELECT COUNT(*) FROM #__joomleague_match m JOIN #__joomleague_round r2 ON r2.id=m.round_id WHERE r2.project_id=p.id) AS match_count')->from('#__joomleague_project p')->join('LEFT','#__joomleague_league l ON l.id=p.league_id')->join('LEFT','#__joomleague_season s ON s.id=p.season_id')->join('LEFT','#__joomleague_sports_type st ON st.id=p.sports_type_id')->where('p.id=:id')->bind(':id',$id,ParameterType::INTEGER);return $d->setQuery($q)->loadObject();
 }
}
