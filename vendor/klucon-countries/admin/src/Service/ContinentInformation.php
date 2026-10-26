<?php
declare(strict_types=1);
namespace Klucon\Component\Kluconcountries\Administrator\Service;
defined('_JEXEC') or die;

final class ContinentInformation
{
    public const FIELDS=['overview','location','boundaries','oceans','terrain','climate','classification','registry_note'];
    private static ?array $reference=null;
    public static function reference(object $row): array {
        if($row->kind!=='continent')return [];
        if(self::$reference===null){
            $path=dirname(__DIR__,3).'/data/continents.json';
            if(!is_file($path))$path=JPATH_ADMINISTRATOR.'/components/com_kluconcountries/data/continents.json';
            self::$reference=json_decode(file_get_contents($path),true,64,JSON_THROW_ON_ERROR);
        }
        $code=$row->source['code']??$row->identifiers['continent-code']??'';
        $data=self::$reference['continents'][$code]??[];
        return $data?array_merge($data,['reviewed_on'=>self::$reference['reviewed_on'],'code'=>$code]):[];
    }
    public static function get(object $row,string $language='en-GB'): array {
        if($row->kind!=='continent')return [];
        $reference=self::reference($row);$values=array_replace(array_intersect_key($reference,array_flip(self::FIELDS)),$row->data['information']??[]);
        foreach($values as $field=>&$value){
            if(!is_string($value))continue;
            if($value!==($reference[$field]??null))continue;
            $key='COM_KLUCONCOUNTRIES_CONTINENT_'.strtoupper(str_replace('-','_',$reference['code'])).'_'.strtoupper($field);
            $value=Labels::get($key,$value,$language);
        }unset($value);$values['facts']=ContinentFacts::get($row,$language);return $values;
    }
    public static function validate(mixed $data,string $kind): array {
        if(!is_array($data)||array_diff(array_keys($data),array_merge(self::FIELDS,['facts']))||($data&&$kind!=='continent'))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_INFORMATION');
        $hasFacts=array_key_exists('facts',$data);$facts=$hasFacts?ContinentFacts::validate($data['facts']):[];unset($data['facts']);
        foreach($data as &$value){
            if(!is_string($value)||!mb_check_encoding($value,'UTF-8')||mb_strlen($value)>4000||preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f<>]/u',$value))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_INFORMATION');
            $value=trim($value);
        }unset($value);if($hasFacts)$data['facts']=$facts;return $data;
    }
}
