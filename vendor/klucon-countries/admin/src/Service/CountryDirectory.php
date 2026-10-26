<?php
declare(strict_types=1);
namespace Klucon\Component\Kluconcountries\Administrator\Service;
defined('_JEXEC') or die;

final class CountryDirectory
{
    private array $catalogue;private array $aliases;
    public function __construct(private readonly Registry $registry) {
        $data=json_decode(file_get_contents(JPATH_ADMINISTRATOR.'/components/com_kluconcountries/data/countries.json'),true,64,JSON_THROW_ON_ERROR);
        $this->catalogue=$data['countries']+$data['territories'];$this->aliases=$data['aliases'];
    }
    private function language(string $language): string {return Labels::language($language);}
    private function translated(string $key,string $fallback,string $language): string {
        return Labels::get($key,$fallback,$language);
    }
    private function name(object $row,string $language): string {
        $key=$row->data['label_key']??$row->source['label_key']??('COM_KLUCONCOUNTRIES_UNIT_'.strtoupper(str_replace('-','_',$row->uuid)));
        return $this->translated($key,$row->labels['en-GB']??$row->uuid,$language);
    }
    private function date(?string $date): ?string {
        if($date===null)return gmdate('Y-m-d');
        if(!preg_match('/\A(\d{4})-(\d{2})-(\d{2})\z/D',$date,$m)||!checkdate((int)$m[2],(int)$m[3],(int)$m[1]))return null;return $date;
    }
    public function getCountry(string $code,string $language='en-GB',?string $date=null): ?array {
        $code=strtoupper(trim($code));$language=$this->language($language);$date=$this->date($date);
        if(!$date||!preg_match('/\A(?:[A-Z]{2,3}|[0-9]{3}|GB-[A-Z]{3})\z/D',$code))return null;
        $canonical=$this->aliases[$code]??$code;
        if(strlen($canonical)===3)foreach($this->catalogue as $key=>$row)if(($row['alpha3']??null)===$canonical||($row['numeric']??null)===$canonical){$canonical=$key;break;}
        $source=$this->catalogue[$canonical]??null;$alpha3=$source['alpha3']??(strlen($code)===3&&!ctype_digit($code)?$code:'');$numeric=$source['numeric']??(ctype_digit($code)?$code:'');
        $db=$this->registry->db;$q=$db->createQuery()->select('unit_uuid')->from('#__kluconcountries_identifiers')
            ->where('((scheme='.$db->quote('iso3166-1-alpha2').' AND code=:canonical) OR (scheme='.$db->quote('sport-code').' AND code=:original) OR (scheme='.$db->quote('iso3166-1-alpha3').' AND code=:alpha3) OR (scheme='.$db->quote('iso3166-1-numeric').' AND code=:numeric))')->bind(':canonical',$canonical)->bind(':original',$code)->bind(':alpha3',$alpha3)->bind(':numeric',$numeric)
            ->order('CASE WHEN scheme='.$db->quote('sport-code').' THEN 1 ELSE 0 END');
        $uuids=$db->setQuery($q)->loadColumn();
        if($uuids){foreach($uuids as $uuid){$row=$this->registry->find($uuid);if(!$row||!in_array($row->kind,['country','territory'],true)||!$this->registry->visible($row,$date))continue;
            return ['code'=>$row->identifiers['iso3166-1-alpha2']??$canonical,'name'=>$this->name($row,$language),'flag'=>$this->registry->flagUrl($row->flag)];}return null;}
        $source=$this->catalogue[$canonical]??null;if(!$source||$date!==gmdate('Y-m-d'))return null;
        return ['code'=>$canonical,'name'=>$this->translated('COM_KLUCONCOUNTRIES_COUNTRY_'.str_replace('-','_',$canonical),$source['name'],$language),'flag'=>$this->registry->flagUrl('images/countries/flags/supplied/'.$source['asset'].'.svg')];
    }
    public function getTerritory(string $uuid,string $language='en-GB',?string $date=null): ?array {
        $date=$this->date($date);$row=$this->registry->find($uuid);if(!$date||!$row||!$this->registry->visible($row,$date))return null;
        return ['uuid'=>$row->uuid,'kind'=>$row->kind,'parent_uuid'=>$row->parent_uuid,'name'=>$this->name($row,$this->language($language)),
            'identifiers'=>$row->identifiers,'continents'=>$this->registry->continentsFor($row),'valid_from'=>$row->valid_from,'valid_to'=>$row->valid_to,'flag'=>$this->registry->flagUrl($row->flag),'information'=>ContinentInformation::get($row,$this->language($language)),
            'indicative_population_density_per_km2'=>$row->kind==='continent'?ContinentFacts::density(ContinentFacts::get($row)):null];
    }
    public function getChildren(string $uuid,string $language='en-GB',?string $date=null): array {
        $parent=$this->getTerritory($uuid,$language,$date);if(!$parent)return [];$result=[];
        foreach($this->registry->all() as $row)if($row->parent_uuid===$uuid||($parent['kind']==='continent'&&in_array($uuid,$row->continents,true))){$item=$this->getTerritory($row->uuid,$language,$date);if($item)$result[]=$item;}
        return $result;
    }
    public function getContinents(string $language='en-GB',?string $date=null): array {
        $result=[];foreach($this->registry->all() as $row)if($row->kind==='continent'){ $item=$this->getTerritory($row->uuid,$language,$date);if($item)$result[]=$item; }return $result;
    }
    public function findTerritories(string $scheme,string $code,string $language='en-GB',?string $date=null): array {
        if(!preg_match('/\A[a-z][a-z0-9.-]{1,63}\z/D',$scheme)||strlen($code)>100)return [];
        if(in_array($scheme,['iso3166-1-alpha2','iso3166-1-alpha3','iso3166-2','sport-code'],true))$code=strtoupper(trim($code));
        $db=$this->registry->db;$ids=$db->setQuery($db->createQuery()->select('unit_uuid')->from('#__kluconcountries_identifiers')->where('scheme=:scheme')->where('code=:code')->bind(':scheme',$scheme)->bind(':code',$code)->order('unit_uuid ASC'))->loadColumn();
        $result=[];foreach($ids as $id){$row=$this->getTerritory($id,$language,$date);if($row)$result[]=$row;}return $result;
    }
}
