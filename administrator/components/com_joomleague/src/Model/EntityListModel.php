<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Model;
\defined('_JEXEC') or die;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
abstract class EntityListModel extends ListModel
{
    protected array $searchColumns=['a.name']; protected string $defaultOrdering='a.id'; protected string $defaultDirection='DESC';
    public function __construct($config=[],?MVCFactoryInterface $factory=null){$config['filter_fields']??=['id','a.id','name','a.name','published','a.published','ordering','a.ordering'];parent::__construct($config,$factory);}
    protected function populateState($ordering='',$direction=''):void{parent::populateState($ordering?:$this->defaultOrdering,$direction?:$this->defaultDirection);}
    abstract protected function buildQuery(): QueryInterface;
    protected function getListQuery():QueryInterface{$db=$this->getDatabase();$q=$this->buildQuery();$search=trim((string)$this->getState('filter.search'));if($search!==''){if(str_starts_with(strtolower($search),'id:')){$id=(int)substr($search,3);$q->where($db->quoteName('a.id').' = :id')->bind(':id',$id,ParameterType::INTEGER);}else{$search='%'.str_replace(' ','%',$search).'%';$parts=array_map(fn($c)=>$db->quoteName($c).' LIKE :search',$this->searchColumns);$q->where('('.implode(' OR ',$parts).')')->bind(':search',$search);}}$state=$this->getState('filter.published');if($state!==''&&$state!==null){$state=(int)$state;$q->where($db->quoteName('a.published').' = :state')->bind(':state',$state,ParameterType::INTEGER);} $ordering=(string)$this->getState('list.ordering',$this->defaultOrdering);$direction=strtoupper((string)$this->getState('list.direction',$this->defaultDirection));$direction=in_array($direction,['ASC','DESC'],true)?$direction:$this->defaultDirection;$q->order($db->quoteName($ordering).' '.$db->escape($direction));return $q;}
}
