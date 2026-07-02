<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Table;
\defined('_JEXEC') or die;
use Joomla\CMS\Filter\OutputFilter; use Joomla\CMS\Language\Text; use Joomla\CMS\Table\Asset; use Joomla\CMS\Table\Table; use Joomla\Database\DatabaseInterface; use Joomla\Database\ParameterType; use Joomla\Event\DispatcherInterface;
final class ProjectTable extends Table
{
 use MediaFieldTrait;

 protected $_supportNullValue=true;
 public function __construct(DatabaseInterface $db,?DispatcherInterface $dispatcher=null){parent::__construct('#__joomleague_project','id',$db,$dispatcher);}
 public function check():bool
 {
  if(!parent::check())return false; $this->name=trim((string)$this->name);$this->alias=OutputFilter::stringURLSafe(trim((string)$this->alias)?:$this->name);
  $this->normalizeMediaField('picture');
  if($this->name===''){return $this->fail('COM_JOOMLEAGUE_PROJECT_ERROR_NAME_REQUIRED');}
  foreach(['league_id','season_id','sports_type_id'] as $f){if((int)$this->$f<1)return $this->fail('COM_JOOMLEAGUE_PROJECT_ERROR_RELATIONS_REQUIRED');}
  if(!in_array($this->project_type,['SIMPLE_LEAGUE','DIVISIONS_LEAGUE','TOURNAMENT_MODE','FRIENDLY_MATCHES'],true))return $this->fail('COM_JOOMLEAGUE_PROJECT_ERROR_TYPE_INVALID');
  if(!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',(string)$this->start_time))return $this->fail('COM_JOOMLEAGUE_PROJECT_ERROR_TIME_INVALID');
  foreach(['points_after_regular_time','points_after_add_time','points_after_penalty'] as $f){if(!preg_match('/^\d+,\d+,\d+$/',(string)$this->$f))return $this->fail('COM_JOOMLEAGUE_PROJECT_ERROR_POINTS_INVALID');}
  foreach(['game_regular_time','game_parts','halftime','add_time'] as $f){if((int)$this->$f<0)return $this->fail('COM_JOOMLEAGUE_PROJECT_ERROR_DURATION_INVALID');}
  if((int)$this->master_template===(int)$this->id && (int)$this->id>0)return $this->fail('COM_JOOMLEAGUE_PROJECT_ERROR_TEMPLATE_SELF');
  $db=$this->getDatabase();$id=(int)$this->id;$league=(int)$this->league_id;$season=(int)$this->season_id;
  $q=$db->createQuery()->select('COUNT(*)')->from($db->quoteName('#__joomleague_project'))->where($db->quoteName('name').'=:name')->where($db->quoteName('league_id').'=:league')->where($db->quoteName('season_id').'=:season')->where($db->quoteName('id').'<>:id')->bind(':name',$this->name)->bind(':league',$league,ParameterType::INTEGER)->bind(':season',$season,ParameterType::INTEGER)->bind(':id',$id,ParameterType::INTEGER);$db->setQuery($q);
  if((int)$db->loadResult()>0)return $this->fail('COM_JOOMLEAGUE_PROJECT_ERROR_NOT_UNIQUE'); return true;
 }
 protected function _getAssetName():string{return 'com_joomleague.project.'.(int)$this->id;} protected function _getAssetTitle():string{return $this->name;}
 protected function _getAssetParentId(?Table $table=null,$id=null):int{$asset=new Asset($this->getDatabase(),$this->getDispatcher());return $asset->loadByName('com_joomleague')?(int)$asset->id:parent::_getAssetParentId($table,$id);}
 private function fail(string $key):bool{$this->setError(Text::_($key));return false;}
}
