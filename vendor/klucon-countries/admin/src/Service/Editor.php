<?php
declare(strict_types=1);
namespace Klucon\Component\Kluconcountries\Administrator\Service;
defined('_JEXEC') or die;

final class Editor
{
    public static function input(array $data): array {
        $information=$data['information']??[];if(!is_array($information))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_INFORMATION');
        if(array_key_exists('facts',$data))$information['facts']=$data['facts'];
        $names=['en-GB'=>$data['name_en']??''];$identifiers=[];
        foreach($data['identifiers']??[] as $row){if(!is_array($row)||!is_string($row['scheme']??null)||isset($identifiers[$row['scheme']]))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_CODE');$identifiers[$row['scheme']]=$row['code']??'';}
        return ['names'=>$names,'identifiers'=>$identifiers,'kind'=>$data['kind']??'country','parent_uuid'=>$data['parent_uuid']??null,
            'continents'=>$data['continents']??[],'state'=>$data['state']??0,'ordering'=>$data['ordering']??0,
            'valid_from'=>$data['valid_from']??null,'valid_to'=>$data['valid_to']??null,'flag'=>$data['flag']??null,'flag_mode'=>$data['flag_mode']??'source','information'=>$information];
    }
    public static function bind(Registry $registry,?object $item): array {
        if(!$item)return ['name_en'=>'','kind'=>'country','parent_uuid'=>'','continents'=>[],'state'=>0,'ordering'=>0,'flag_mode'=>'none','identifiers'=>[],'localised_names'=>[]];
        $data=$registry->snapshot($item);$data['name_en']=$data['names']['en-GB']??'';$data['localised_names']=[];
        $data['facts']=$data['information']['facts']??[];
        $data['identifiers']=array_map(static fn($scheme,$code)=>['scheme'=>$scheme,'code'=>$code],array_keys($item->identifiers),array_values($item->identifiers));return $data;
    }
}
