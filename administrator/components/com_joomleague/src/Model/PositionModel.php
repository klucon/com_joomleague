<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Model;
\defined('_JEXEC') or die;
use Throwable;
final class PositionModel extends EntityAdminModel
{
    protected string $entityName = 'position';
    public function getItem($pk = null): object|false { $item=parent::getItem($pk); if($item && (int)$item->id){$item->eventtype_ids=$this->relationIds('position_eventtype','eventtype_id',(int)$item->id);$item->statistic_ids=$this->relationIds('position_statistic','statistic_id',(int)$item->id);} return $item; }
    public function save($data): bool { $events=array_values(array_unique(array_map('intval',(array)($data['eventtype_ids']??[])))); $stats=array_values(array_unique(array_map('intval',(array)($data['statistic_ids']??[])))); unset($data['eventtype_ids'],$data['statistic_ids']); $db=$this->getDatabase();$db->transactionStart();try{if(!parent::save($data)){throw new \RuntimeException((string)$this->getError());}$id=(int)$this->getState('position.id');$this->sync('position_eventtype','eventtype_id',$id,$events);$this->sync('position_statistic','statistic_id',$id,$stats);$db->transactionCommit();return true;}catch(Throwable $e){$db->transactionRollback();$this->setError($e->getMessage());return false;} }
    protected function prepareTable($table): void { $table->name=trim((string)$table->name);$table->parent_id=(int)$table->parent_id?:null;$table->sports_type_id=(int)$table->sports_type_id;$table->persontype=(int)$table->persontype;parent::prepareTable($table); }
    private function relationIds(string $table,string $column,int $id): array {$db=$this->getDatabase();$q=$db->createQuery()->select($db->quoteName($column))->from($db->quoteName('#__joomleague_'.$table))->where($db->quoteName('position_id').' = '.(int)$id);return array_map('intval',$db->setQuery($q)->loadColumn());}
    private function sync(string $table,string $column,int $id,array $ids): void {$db=$this->getDatabase();$q=$db->createQuery()->delete($db->quoteName('#__joomleague_'.$table))->where($db->quoteName('position_id').' = '.(int)$id);$db->setQuery($q)->execute();foreach(array_filter($ids) as $value){$o=(object)['position_id'=>$id,$column=>$value];$db->insertObject('#__joomleague_'.$table,$o);}}
}
