<?php
declare(strict_types=1);
namespace Klucon\Component\Kluconcountries\Administrator\Service;
defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;

final class Schema
{
    public static function install(DatabaseInterface $db, string $seedPath): void
    {
        $mysql = $db->getServerType() === 'mysql';
        if (!$mysql && $db->getServerType() !== 'postgresql') throw new \RuntimeException('Unsupported database.');
        $tail = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';
        $db->setQuery('CREATE TABLE IF NOT EXISTS '.$db->quoteName('#__kluconcountries_units').' (
            uuid VARCHAR(36) NOT NULL PRIMARY KEY,parent_uuid VARCHAR(36) NULL,kind VARCHAR(32) NOT NULL,
            ordering INTEGER NOT NULL DEFAULT 0,names TEXT NOT NULL,record_data TEXT NOT NULL,
            custom_names TEXT NOT NULL,custom_flag VARCHAR(255) NULL)'.$tail)->execute();
        $columns = $db->getTableColumns('#__kluconcountries_units');
        foreach (['state'=>'SMALLINT NOT NULL DEFAULT 1','version'=>'INTEGER NOT NULL DEFAULT 1',
            'custom_data'=>'TEXT NULL','name_en'=>"VARCHAR(190) NOT NULL DEFAULT ''",
            'valid_from'=>'VARCHAR(10) NULL','valid_to'=>'VARCHAR(10) NULL','created_at'=>'VARCHAR(30) NULL',
            'modified_at'=>'VARCHAR(30) NULL','created_by'=>'INTEGER NOT NULL DEFAULT 0','modified_by'=>'INTEGER NOT NULL DEFAULT 0'] as $name=>$type) {
            if (!isset($columns[$name])) $db->setQuery('ALTER TABLE '.$db->quoteName('#__kluconcountries_units').' ADD COLUMN '.$db->quoteName($name).' '.$type)->execute();
        }
        if(isset($columns['name_cs']))$db->setQuery('ALTER TABLE '.$db->quoteName('#__kluconcountries_units').' DROP COLUMN '.$db->quoteName('name_cs'))->execute();
        $tables = [
            'meta'=>'id INTEGER NOT NULL PRIMARY KEY,revision INTEGER NOT NULL DEFAULT 0,schema_version INTEGER NOT NULL DEFAULT 0',
            'identifiers'=>'scheme VARCHAR(64) NOT NULL,code VARCHAR(100) NOT NULL,unit_uuid VARCHAR(36) NOT NULL,PRIMARY KEY (scheme,code,unit_uuid)',
            'continents'=>'unit_uuid VARCHAR(36) NOT NULL,continent_uuid VARCHAR(36) NOT NULL,PRIMARY KEY (unit_uuid,continent_uuid)',
            'revisions'=>'unit_uuid VARCHAR(36) NOT NULL,version INTEGER NOT NULL,action VARCHAR(32) NOT NULL,actor_id INTEGER NOT NULL DEFAULT 0,created_at VARCHAR(30) NOT NULL,snapshot TEXT NOT NULL,PRIMARY KEY (unit_uuid,version)',
            'assets'=>'path VARCHAR(255) NOT NULL PRIMARY KEY,sha256 VARCHAR(64) NOT NULL,mime VARCHAR(64) NOT NULL,bytes INTEGER NOT NULL,created_at VARCHAR(30) NOT NULL,created_by INTEGER NOT NULL DEFAULT 0',
        ];
        foreach ($tables as $name=>$definition) $db->setQuery('CREATE TABLE IF NOT EXISTS '.$db->quoteName('#__kluconcountries_'.$name).' ('.$definition.')'.$tail)->execute();
        if(!isset($db->getTableColumns('#__kluconcountries_meta')['language_cleanup']))$db->setQuery('ALTER TABLE '.$db->quoteName('#__kluconcountries_meta').' ADD COLUMN language_cleanup SMALLINT NOT NULL DEFAULT 0')->execute();
        if (!$db->setQuery('SELECT id FROM #__kluconcountries_meta WHERE id=1')->loadResult()) self::insert($db,'#__kluconcountries_meta',['id'=>1,'revision'=>0,'schema_version'=>0]);
        $seed = json_decode(file_get_contents($seedPath),true,512,JSON_THROW_ON_ERROR);
        $worldPath=dirname($seedPath).'/world.json';
        if(is_file($worldPath)){
            $world=json_decode(file_get_contents($worldPath),true,512,JSON_THROW_ON_ERROR);
            $seed['units']=array_merge($seed['units'],$world['units']);
        }
        $db->transactionStart();
        try {
            $db->setQuery('SELECT id FROM #__kluconcountries_meta WHERE id=1 FOR UPDATE')->loadResult();
            $initialise = (int)$db->setQuery('SELECT schema_version FROM #__kluconcountries_meta WHERE id=1')->loadResult() < 2;
            $added = [];
            $seedRecords=array_column(array_merge($seed['continents'],$seed['units']),null,'uuid');
            foreach ($seedRecords as $record) {
                $id=$record['uuid'];
                if ($db->setQuery($db->createQuery()->select('uuid')->from('#__kluconcountries_units')->where('uuid=:id')->bind(':id',$id))->loadResult() !== null) continue;
                // Preserve manually created identities that already own the official code.
                $identifiers=$record['identifiers']??[];$scheme=isset($identifiers['iso3166-1-alpha2'])?'iso3166-1-alpha2':(isset($identifiers['sport-code'])?'sport-code':null);
                if($scheme!==null){$code=$identifiers[$scheme];if($db->setQuery($db->createQuery()->select('unit_uuid')->from('#__kluconcountries_identifiers')->where('scheme=:scheme')->where('code=:code')->bind(':scheme',$scheme)->bind(':code',$code))->loadResult()!==null)continue;}
                self::insert($db,'#__kluconcountries_units',[
                    'uuid'=>$id,'parent_uuid'=>$record['parent_uuid']??null,'kind'=>$record['type']??'continent',
                    'ordering'=>$record['ordering']??0,'names'=>self::json($record['names']),
                    'record_data'=>self::json($record),'custom_names'=>'{}','custom_flag'=>null]);
                $added[$id]=true;
            }
            foreach ($db->setQuery('SELECT * FROM #__kluconcountries_units')->loadAssocList() as $row) {
                if (!$initialise && !isset($added[$row['uuid']])) continue;
                $source=json_decode($row['record_data'],true,512,JSON_THROW_ON_ERROR);
                $base=$seedRecords[$row['uuid']]['names']??json_decode($row['names'],true,512,JSON_THROW_ON_ERROR);
                $names=array_replace($base,json_decode($row['custom_names'],true,512,JSON_THROW_ON_ERROR));
                $object=(object)['uuid'=>$row['uuid'],'name_en'=>$names['en-GB']??reset($names)?:'',
                    'names'=>self::json($base),'custom_data'=>self::json(['label_key'=>$seedRecords[$row['uuid']]['label_key']??null]),'valid_from'=>$source['valid_from']??null,'valid_to'=>$source['valid_to']??null];
                $db->updateObject('#__kluconcountries_units',$object,'uuid',true);
                $identifiers=$source['identifiers']??[];
                if ($row['kind']==='continent' && isset($source['code'])) $identifiers['continent-code']=$source['code'];
                foreach ($identifiers as $scheme=>$code) self::insert($db,'#__kluconcountries_identifiers',['scheme'=>$scheme,'code'=>(string)$code,'unit_uuid'=>$row['uuid']]);
                foreach ($source['continent_uuids']??[] as $continent) self::insert($db,'#__kluconcountries_continents',['unit_uuid'=>$row['uuid'],'continent_uuid'=>$continent]);
            }
            $db->setQuery('UPDATE #__kluconcountries_meta SET schema_version=2 WHERE id=1')->execute();
            if($added&&!$initialise)$db->setQuery('UPDATE #__kluconcountries_meta SET revision=revision+1 WHERE id=1')->execute();
            $db->transactionCommit();
        } catch (\Throwable $error) { $db->transactionRollback();throw $error; }
    }

    public static function json(array $value): string { return json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR); }
    public static function insert(DatabaseInterface $db,string $table,array $data): void {$row=(object)$data;$db->insertObject($table,$row);}
}
