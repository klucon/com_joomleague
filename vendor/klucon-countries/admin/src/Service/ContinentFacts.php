<?php
declare(strict_types=1);
namespace Klucon\Component\Kluconcountries\Administrator\Service;
defined('_JEXEC') or die;

final class ContinentFacts
{
    public const GROUPS=[
        'area'=>['area_km2','area_scope','area_source'],
        'population'=>['population','population_year','population_status','population_min','population_max','population_scope','population_source'],
        'highest'=>['highest_name','highest_elevation_m','highest_scope','highest_source'],
        'lowest'=>['lowest_name','lowest_elevation_m','lowest_year','lowest_scope','lowest_source'],
        'river'=>['river_name','river_length_km','river_scope','river_source'],
        'lake'=>['lake_name','lake_area_km2','lake_scope','lake_source'],
    ];
    public const NUMBERS=[
        'area_km2'=>[0.001,200000000,3], 'population'=>[0,100000000000,0],
        'population_year'=>[1800,2200,0], 'population_min'=>[0,100000000000,0], 'population_max'=>[0,100000000000,0],
        'highest_elevation_m'=>[-500,10000,3], 'lowest_elevation_m'=>[-12000,10000,3],
        'lowest_year'=>[1800,2200,0], 'river_length_km'=>[0.001,20000,3], 'lake_area_km2'=>[0.001,1000000,3],
    ];
    private static ?array $data=null;
    public static function reference(object $row): array {
        $reference=ContinentInformation::reference($row);if(!$reference)return [];
        if(self::$data===null){$path=dirname(__DIR__,3).'/data/continent-facts.json';if(!is_file($path))$path=JPATH_ADMINISTRATOR.'/components/com_kluconcountries/data/continent-facts.json';self::$data=json_decode(file_get_contents($path),true,64,JSON_THROW_ON_ERROR);}
        return self::validate(self::$data['continents'][$reference['code']]??[]);
    }
    public static function get(object $row,string $language='en-GB'): array {
        if($row->kind!=='continent')return [];
        $reference=self::reference($row);$values=array_replace($reference,$row->data['information']['facts']??[]);
        $code=ContinentInformation::reference($row)['code']??'';
        foreach($values as $field=>&$value)if(is_string($value)&&$value===($reference[$field]??null)&&!str_ends_with($field,'_source')&&$field!=='population_status')$value=Labels::get('COM_KLUCONCOUNTRIES_FACT_'.strtoupper(str_replace('-','_',$code)).'_'.strtoupper($field),$value,$language);
        unset($value);return self::validate($values);
    }
    public static function density(array $facts): ?float {
        if(($facts['population_status']??'')==='no_permanent_population'||!isset($facts['population'],$facts['area_km2'])||(float)$facts['area_km2']<=0)return null;
        return round((float)$facts['population']/(float)$facts['area_km2'],3);
    }
    public static function validate(mixed $input): array {
        $allowed=array_merge(...array_values(self::GROUPS));
        if(!is_array($input)||array_diff(array_keys($input),$allowed))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_FACTS');
        $result=[];
        foreach($input as $field=>$value){
            if(isset(self::NUMBERS[$field])){
                if($value===null||$value===''){$result[$field]=null;continue;}
                [$min,$max,$decimals]=self::NUMBERS[$field];
                if((!is_string($value)&&!is_int($value)&&!is_float($value))||!preg_match('/\A-?[0-9]+'.($decimals?'(?:\.[0-9]{1,'.$decimals.'})?':'').'\z/D',(string)$value)||!is_finite((float)$value)||(float)$value<$min||(float)$value>$max)throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_FACTS');
                $result[$field]=$decimals?(float)$value:(int)$value;continue;
            }
            if(!is_string($value)||!mb_check_encoding($value,'UTF-8')||mb_strlen($value)>2000||preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f<>]/u',$value))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_FACTS');
            $value=trim($value);
            if(str_ends_with($field,'_source')&&$value!==''){
                $url=parse_url($value);if(!filter_var($value,FILTER_VALIDATE_URL)||($url['scheme']??'')!=='https'||empty($url['host'])||isset($url['user'])||isset($url['pass']))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_FACTS');
            }
            if($field==='population_status'&&!in_array($value,['','estimate','projection','no_permanent_population'],true))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_FACTS');
            $result[$field]=$value;
        }
        foreach(['area'=>['area_km2'],'population'=>['population','population_min','population_max'],'highest'=>['highest_elevation_m'],'lowest'=>['lowest_elevation_m'],'river'=>['river_length_km'],'lake'=>['lake_area_km2']] as $group=>$numbers){
            $filled=false;foreach($numbers as $field)if(isset($result[$field]))$filled=true;
            if($filled&&(empty($result[$group.'_source'])||empty($result[$group.'_scope'])))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_FACTS');
            if($filled&&in_array($group,['highest','lowest','river','lake'],true)&&empty($result[$group.'_name']))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_FACTS');
        }
        if(isset($result['population'])&&(empty($result['population_year'])||empty($result['population_status'])))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_FACTS');
        if(isset($result['highest_elevation_m'],$result['lowest_elevation_m'])&&$result['highest_elevation_m']<$result['lowest_elevation_m'])throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_FACTS');
        if(isset($result['population_min'],$result['population_max'])&&$result['population_min']>$result['population_max'])throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_FACTS');
        return $result;
    }
}
