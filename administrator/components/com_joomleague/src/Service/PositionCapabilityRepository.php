<?php
declare(strict_types=1); namespace Joomleague\Component\Joomleague\Administrator\Service; defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface; use Joomla\Database\ParameterType;
final class PositionCapabilityRepository
{
	public function __construct(private readonly DatabaseInterface $db){}
	/** @return array{available:list<object>,assigned:list<object>} */
	public function events(int $positionId):array{return $this->options($positionId,'event_type','event_type_id');}
	/** @return array{available:list<object>,assigned:list<object>} */
	public function statistics(int $positionId):array{return $this->options($positionId,'statistic','statistic_id');}
	/** @param list<int> $ids */
	public function replaceEvents(int $positionId,array $ids,int $actorId):void{$this->replace($positionId,'event_type','event_type_id',$ids,$actorId);}
	/** @param list<int> $ids */
	public function replaceStatistics(int $positionId,array $ids,int $actorId):void{$this->replace($positionId,'statistic','statistic_id',$ids,$actorId);}
	private function sportTypeId(int $positionId):int{$q=$this->db->getQuery(true)->select('sport_type_id')->from($this->db->quoteName('#__joomleague_sport_position'))->where('id=:id')->bind(':id',$positionId,ParameterType::INTEGER);$id=(int)$this->db->setQuery($q)->loadResult();if($id<1)throw new \InvalidArgumentException('Position not found.');return $id;}
	private function options(int $positionId,string $entity,string $foreign):array{$sid=$this->sportTypeId($positionId);$junction=$entity==='event_type'?'position_event_type':'position_statistic';$q=$this->db->getQuery(true)->select(['item.id','item.name','item.name_key','item.code','link.ordering'])->from($this->db->quoteName('#__joomleague_'.$entity,'item'))->leftJoin($this->db->quoteName('#__joomleague_'.$junction,'link').' ON link.'.$foreign.'=item.id AND link.position_id=:positionId')->where('item.sport_type_id=:sid')->where('item.published=1')->order(['CASE WHEN link.position_id IS NULL THEN 1 ELSE 0 END ASC','link.ordering ASC','item.ordering ASC','item.id ASC'])->bind(':positionId',$positionId,ParameterType::INTEGER)->bind(':sid',$sid,ParameterType::INTEGER);$all=$this->db->setQuery($q)->loadObjectList();return ['assigned'=>array_values(array_filter($all,fn($x)=>$x->ordering!==null)),'available'=>array_values(array_filter($all,fn($x)=>$x->ordering===null))];}
	/** @param list<int> $ids */
	private function replace(int $positionId,string $entity,string $foreign,array $ids,int $actorId):void{$sid=$this->sportTypeId($positionId);$ids=array_values(array_unique(array_filter(array_map('intval',$ids),fn($id)=>$id>0)));if($ids){$q=$this->db->getQuery(true)->select('id')->from($this->db->quoteName('#__joomleague_'.$entity))->where('sport_type_id=:sid')->whereIn('id',$ids)->bind(':sid',$sid,ParameterType::INTEGER);$valid=array_map('intval',$this->db->setQuery($q)->loadColumn());sort($valid);$check=$ids;sort($check);if($valid!==$check)throw new \InvalidArgumentException('Invalid cross-sport capability assignment.');}$junction=$entity==='event_type'?'position_event_type':'position_statistic';$q=$this->db->getQuery(true)->delete($this->db->quoteName('#__joomleague_'.$junction))->where('position_id=:positionId')->bind(':positionId',$positionId,ParameterType::INTEGER);$this->db->setQuery($q)->execute();foreach($ids as $ordering=>$id){$row=(object)['position_id'=>$positionId,$foreign=>$id,'sport_type_id'=>$sid,'ordering'=>$ordering+1,'created'=>gmdate('Y-m-d H:i:s'),'created_by'=>$actorId];$this->db->insertObject('#__joomleague_'.$junction,$row);}}
}
