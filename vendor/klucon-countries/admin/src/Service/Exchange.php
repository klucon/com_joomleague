<?php
declare(strict_types=1);
namespace Klucon\Component\Kluconcountries\Administrator\Service;
defined('_JEXEC') or die;

final class Exchange
{
    public function __construct(private readonly Registry $registry) {}
    public function export(): string {
        $rows=$this->registry->all();if(count($rows)>10000)throw new \RuntimeException('COM_KLUCONCOUNTRIES_ERROR_LIMIT');
        $records=array_map(fn($row)=>$this->registry->snapshot($row),$rows);
        return json_encode(['format'=>'kluconcountries.registry','schema_version'=>1,'exported_at'=>gmdate('c'),'records'=>$records],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    }
    public function preview(string $json,int $maxBytes=2097152,int $maxRecords=1000): array {
        if(strlen($json)>$maxBytes)throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_LIMIT');
        $data=json_decode($json,true,64,JSON_THROW_ON_ERROR);
        if(!is_array($data)||($data['format']??'')!=='kluconcountries.registry'||($data['schema_version']??null)!==1||!is_array($data['records']??null)||!array_is_list($data['records'])||!$data['records']||count($data['records'])>$maxRecords)throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_IMPORT');
        $revision=$this->registry->revision();$current=array_column($this->registry->all(),null,'uuid');$proposed=$current;$records=[];$seen=[];
        $fields=['uuid','version','kind','parent_uuid','names','identifiers','continents','ordering','state','valid_from','valid_to','flag','flag_mode','information'];
        foreach($data['records'] as $record){
            if(!is_array($record)||array_diff(array_keys($record),$fields))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_IMPORT');
            $record['uuid']??=Registry::newUuid();$id=$record['uuid'];
            if(!is_string($id)||!Registry::uuid($id)||isset($seen[$id]))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_UUID');$seen[$id]=true;
            $existing=$current[$id]??null;
            if($existing&&(!isset($record['version'])||(int)$record['version']!==(int)$existing->version))throw new \RuntimeException('COM_KLUCONCOUNTRIES_ERROR_STALE',409);
            if(!$existing)$record['version']=0;
            $record=array_replace($existing?$this->registry->snapshot($existing):['kind'=>'country','parent_uuid'=>null,'identifiers'=>[],'continents'=>[],'state'=>0,'ordering'=>0,'valid_from'=>null,'valid_to'=>null,'flag'=>null,'flag_mode'=>'source'],$record);
            $records[$id]=$record;$proposed[$id]=(object)$record;
        }
        foreach($records as $id=>$record)$records[$id]=$this->registry->validate($record,$current[$id]??null,$proposed);
        return ['revision'=>$revision,'records'=>array_values($records),'new'=>count(array_diff(array_keys($records),array_keys($current))),'updated'=>count(array_intersect(array_keys($records),array_keys($current)))];
    }
    public function apply(array $preview,int $actor,callable $authorise): void {
        $db=$this->registry->db;$db->transactionStart(true);
        try{$this->registry->lock();if($this->registry->revision()!==($preview['revision']??null))throw new \RuntimeException('COM_KLUCONCOUNTRIES_ERROR_STALE',409);
            foreach($preview['records'] as $record){$old=$this->registry->find($record['uuid']);if(!$authorise($old?'core.edit':'core.create')||($record['state']!==($old?(int)$old->state:0)&&!$authorise('core.edit.state'))||($record['state']===-2&&(!$old||(int)$old->state!==-2)&&!$authorise('core.delete')))throw new \RuntimeException('JERROR_ALERTNOAUTHOR',403);}
            $this->registry->saveMany($preview['records'],$actor,'import',$preview['revision']);
            $db->transactionCommit(true);
        }catch(\Throwable $error){$db->transactionRollback(true);throw $error;}
    }
}
