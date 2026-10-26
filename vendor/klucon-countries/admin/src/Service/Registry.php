<?php
declare(strict_types=1);
namespace Klucon\Component\Kluconcountries\Administrator\Service;
defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;

final class Registry
{
    private static ?array $suppliedAssets=null;
    public const KINDS=['continent','country','territory','region','district','capital_territory','municipality','locality','subdivision'];
    public function __construct(public readonly DatabaseInterface $db) {}
    public static function uuid(string $value): bool {return (bool)preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/D',$value);}
    public static function newUuid(): string {
        $bytes=random_bytes(16);$bytes[6]=chr((ord($bytes[6])&15)|64);$bytes[8]=chr((ord($bytes[8])&63)|128);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($bytes),4));
    }
    public function revision(): int {return (int)$this->db->setQuery('SELECT revision FROM #__kluconcountries_meta WHERE id=1')->loadResult();}
    public function lock(): void {$this->db->setQuery('SELECT id FROM #__kluconcountries_meta WHERE id=1 FOR UPDATE')->loadResult();}
    public function all(): array {
        $rows=$this->db->setQuery('SELECT * FROM #__kluconcountries_units ORDER BY ordering,uuid')->loadObjectList();$codes=[];$continents=[];
        foreach($this->db->setQuery('SELECT * FROM #__kluconcountries_identifiers ORDER BY scheme,code')->loadAssocList() as $item)$codes[$item['unit_uuid']][$item['scheme']]=$item['code'];
        foreach($this->db->setQuery('SELECT * FROM #__kluconcountries_continents ORDER BY continent_uuid')->loadAssocList() as $item)$continents[$item['unit_uuid']][]=$item['continent_uuid'];
        foreach($rows as $row){$this->decode($row);$row->identifiers=$codes[$row->uuid]??[];$row->continents=$continents[$row->uuid]??[];}return $rows;
    }
    public function find(string $uuid): ?object {
        if(!self::uuid($uuid))return null;
        $row=$this->db->setQuery($this->db->createQuery()->select('*')->from('#__kluconcountries_units')->where('uuid=:uuid')->bind(':uuid',$uuid))->loadObject();
        if(!$row)return null;$this->decode($row);
        $row->identifiers=$this->db->setQuery($this->db->createQuery()->select(['scheme','code'])->from('#__kluconcountries_identifiers')->where('unit_uuid=:uuid')->bind(':uuid',$uuid))->loadAssocList('scheme','code');
        $row->continents=$this->db->setQuery($this->db->createQuery()->select('continent_uuid')->from('#__kluconcountries_continents')->where('unit_uuid=:uuid')->bind(':uuid',$uuid))->loadColumn();return $row;
    }
    private function decode(object $row): void {
        $row->source=json_decode($row->record_data,true,64,JSON_THROW_ON_ERROR);
        $row->labels=array_intersect_key(array_replace(json_decode($row->names,true,64,JSON_THROW_ON_ERROR),json_decode($row->custom_names,true,64,JSON_THROW_ON_ERROR)),['en-GB'=>true]);
        $row->data=json_decode($row->custom_data?:'{}',true,64,JSON_THROW_ON_ERROR);
        $row->flag=$row->custom_flag??($row->source['flag']??null);if(($row->data['flag_mode']??'')==='none')$row->flag=null;
    }
    public static function label(object $row,string $language): string {
        $key=$row->data['label_key']??$row->source['label_key']??('COM_KLUCONCOUNTRIES_UNIT_'.strtoupper(str_replace('-','_',$row->uuid)));
        return Labels::get($key,$row->labels['en-GB']??$row->uuid,$language);
    }
    public static function active(object $row,?string $date=null): bool {
        $date??=gmdate('Y-m-d');return (int)$row->state===1&&(!$row->valid_from||$row->valid_from<=$date)&&(!$row->valid_to||$row->valid_to>$date);
    }
    public function visible(object $row,?string $date=null): bool {
        $seen=[];do{if(isset($seen[$row->uuid])||!self::active($row,$date))return false;$seen[$row->uuid]=true;
            if(!$row->parent_uuid)return true;$row=$this->find($row->parent_uuid);
        }while($row&&count($seen)<=32);return false;
    }
    public function continentsFor(object $row): array {
        $seen=[];do{if(isset($seen[$row->uuid]))return [];$seen[$row->uuid]=true;if($row->continents)return $row->continents;
            $row=$row->parent_uuid?$this->find($row->parent_uuid):null;
        }while($row&&count($seen)<=32);return [];
    }
    public function snapshot(object $row): array {
        return ['uuid'=>$row->uuid,'version'=>(int)$row->version,'kind'=>$row->kind,'parent_uuid'=>$row->parent_uuid,'names'=>$row->labels,
            'identifiers'=>$row->identifiers,'continents'=>$row->continents,'ordering'=>(int)$row->ordering,'state'=>(int)$row->state,
            'valid_from'=>$row->valid_from,'valid_to'=>$row->valid_to,'flag'=>$row->flag,'flag_mode'=>$row->data['flag_mode']??($row->custom_flag?'custom':'source'),'information'=>ContinentInformation::get($row)];
    }
    private function names(array $input): array {
        if(!isset($input['en-GB'])||!is_string($input['en-GB'])||trim($input['en-GB'])===''||array_diff(array_keys($input),['en-GB']))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_NAME');$names=[];
        foreach($input as $language=>$name){
            if(!is_string($language)||!preg_match('/\A[a-z]{2,3}(?:-[A-Za-z0-9]{2,8}){0,3}\z/D',$language)||!is_string($name)||!mb_check_encoding($name,'UTF-8'))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_NAME');
            $name=trim($name);if(mb_strlen($name)>190||preg_match('/[\x00-\x1f\x7f<>]/u',$name))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_NAME');if($name!=='')$names[$language]=$name;
        }ksort($names);return $names;
    }
    private function identifiers(array $input): array {
        if(count($input)>30)throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_CODE');$result=[];
        foreach($input as $scheme=>$code){
            if(!is_string($scheme)||!preg_match('/\A[a-z][a-z0-9.-]{1,63}\z/D',$scheme)||(!is_string($code)&&!is_int($code)))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_CODE');
            $code=trim((string)$code);if($code===''||strlen($code)>100||!preg_match('/\A[A-Za-z0-9][A-Za-z0-9._:\/-]*\z/D',$code))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_CODE');
            if(in_array($scheme,['iso3166-1-alpha2','iso3166-1-alpha3','iso3166-2','sport-code'],true))$code=strtoupper($code);
            if(($scheme==='iso3166-1-alpha2'&&!preg_match('/\A[A-Z]{2}\z/D',$code))||($scheme==='iso3166-1-alpha3'&&!preg_match('/\A[A-Z]{3}\z/D',$code))||($scheme==='iso3166-1-numeric'&&!preg_match('/\A[0-9]{3}\z/D',$code))||($scheme==='iso3166-2'&&!preg_match('/\A[A-Z]{2}-[A-Z0-9]{1,6}\z/D',$code)))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_CODE');$result[$scheme]=$code;
        }ksort($result);return $result;
    }
    private function date(mixed $value): ?string {
        if($value===null||$value==='')return null;
        if(!is_string($value)||!preg_match('/\A(\d{4})-(\d{2})-(\d{2})\z/D',$value,$m)||!checkdate((int)$m[2],(int)$m[3],(int)$m[1]))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_DATE');return $value;
    }
    public function validate(array $input,?object $current=null,?array $universe=null): array {
        $uuid=$current?->uuid??($input['uuid']??self::newUuid());
        if(!is_string($uuid)||!self::uuid($uuid)||($current&&isset($input['uuid'])&&$input['uuid']!==$uuid))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_UUID');
        $row=array_replace($current?$this->snapshot($current):['uuid'=>$uuid,'version'=>0,'kind'=>'country','parent_uuid'=>null,'names'=>[],'identifiers'=>[],'continents'=>[],'ordering'=>0,'state'=>0,'valid_from'=>null,'valid_to'=>null,'flag'=>null,'flag_mode'=>'source'],$input);
        if(!is_array($row['names'])||!is_array($row['identifiers']))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_INPUT');
        $row['names']=$this->names($row['names']);$row['identifiers']=$this->identifiers($row['identifiers']);
        if(!is_string($row['kind'])||!in_array($row['kind'],self::KINDS,true)||!in_array((string)$row['state'],['-2','0','1'],true)||filter_var($row['ordering'],FILTER_VALIDATE_INT)===false||abs((int)$row['ordering'])>1000000)throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_INPUT');
        $row['information']=ContinentInformation::validate($row['information']??[],$row['kind']);
        $row['state']=(int)$row['state'];$row['ordering']=(int)$row['ordering'];$row['valid_from']=$this->date($row['valid_from']);$row['valid_to']=$this->date($row['valid_to']);
        if($row['valid_from']&&$row['valid_to']&&$row['valid_from']>=$row['valid_to'])throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_DATE');
        if(!in_array($row['flag_mode'],['source','custom','none'],true))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_FLAG');
        if($row['flag_mode']==='custom'&&(!is_string($row['flag'])||!$this->flagAllowed($row['flag'])))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_FLAG');if($row['flag_mode']!=='custom')$row['flag']=null;
        if($row['parent_uuid']==='')$row['parent_uuid']=null;if($row['parent_uuid']!==null&&(!is_string($row['parent_uuid'])||!self::uuid($row['parent_uuid'])))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_PARENT');
        if(!is_array($row['continents'])||count($row['continents'])>20)throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_CONTINENT');
        foreach($row['continents'] as $continent)if(!is_string($continent)||!self::uuid($continent))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_CONTINENT');$row['continents']=array_values(array_unique($row['continents']));
        $universe??=array_column($this->all(),null,'uuid');
        foreach($row['continents'] as $continent)if(!isset($universe[$continent])||$universe[$continent]->kind!=='continent'||(int)$universe[$continent]->state===-2)throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_CONTINENT');
        if(in_array($row['kind'],['country','territory'],true)!==(bool)$row['continents'])throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_CONTINENT');
        if(in_array($row['kind'],['continent','country'],true)&&$row['parent_uuid']!==null)throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_PARENT');
        if(!in_array($row['kind'],['continent','country','territory'],true)&&!$row['parent_uuid'])throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_PARENT');
        $universe[$uuid]=(object)$row;
        foreach($universe as $node){
            foreach($node->continents as $continent)if(!isset($universe[$continent])||$universe[$continent]->kind!=='continent'||((int)$universe[$continent]->state===-2&&(int)$node->state!==-2))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_CONTINENT');
            $parent=$node->parent_uuid?($universe[$node->parent_uuid]??null):null;
            if($node->parent_uuid&&(!$parent||$parent->kind==='continent'||((int)$parent->state===-2&&(int)$node->state!==-2)))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_PARENT');
            if($node->kind==='country'&&$parent)throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_PARENT');
            $seen=[];$cursor=$node;while($cursor){if(isset($seen[$cursor->uuid])||count($seen)>32)throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_CYCLE');$seen[$cursor->uuid]=true;$cursor=$cursor->parent_uuid?($universe[$cursor->parent_uuid]??null):null;}
            // Statistical classifications may identify the same area at different administrative levels.
            if($node->uuid!==$uuid)foreach($row['identifiers'] as $scheme=>$code)if(!in_array($scheme,['nuts','ruian-nuts-lau'],true)&&isset($node->identifiers[$scheme])&&$node->identifiers[$scheme]===$code&&(!$row['valid_to']||!$node->valid_from||$row['valid_to']>$node->valid_from)&&(!$node->valid_to||!$row['valid_from']||$node->valid_to>$row['valid_from']))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_DUPLICATE_CODE');
        }
        foreach($row['identifiers'] as $scheme=>$code)if(str_starts_with($scheme,'iso3166-1-')&&!in_array($row['kind'],['country','territory'],true))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_CODE');
        return $row;
    }
    public function save(array $input,int $actor=0,string $action='save'): string {
        return $this->saveMany([$input],$actor,$action)[0];
    }
    public function saveMany(array $inputs,int $actor=0,string $action='save',?int $expectedRevision=null): array {
        if(!$inputs||count($inputs)>10000||!in_array($action,['save','state','restore','upload','import'],true)||$actor<0)throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_INPUT');
        $this->db->transactionStart(true);
        try{
            $this->lock();if($expectedRevision!==null&&$this->revision()!==$expectedRevision)throw new \RuntimeException('COM_KLUCONCOUNTRIES_ERROR_STALE',409);
            $current=array_column($this->all(),null,'uuid');$proposed=$current;$prepared=[];
            foreach($inputs as $input){
                if(!is_array($input))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_INPUT');$input['uuid']??=self::newUuid();$id=$input['uuid'];
                if(!is_string($id)||!self::uuid($id)||isset($prepared[$id]))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_UUID');
                $old=$current[$id]??null;
                if(($old&&(!isset($input['version'])||(int)$input['version']!==(int)$old->version))||(!$old&&!empty($input['version'])))throw new \RuntimeException('COM_KLUCONCOUNTRIES_ERROR_STALE',409);
                $record=array_replace($old?$this->snapshot($old):['uuid'=>$id,'version'=>0,'kind'=>'country','parent_uuid'=>null,'names'=>[],'identifiers'=>[],'continents'=>[],'ordering'=>0,'state'=>0,'valid_from'=>null,'valid_to'=>null,'flag'=>null,'flag_mode'=>'none'],$input);
                if(!is_array($record['names'])||!is_array($record['identifiers'])||!is_array($record['continents'])||($record['parent_uuid']!==null&&!is_string($record['parent_uuid']))||!is_string($record['kind'])||!is_scalar($record['state']))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_INPUT');
                $prepared[$id]=$record;$proposed[$id]=(object)$record;
            }
            foreach($prepared as $id=>$record)$prepared[$id]=$this->validate($record,$current[$id]??null,$proposed);
            // Validate the final graph first, then persist it atomically, independent of input order.
            foreach($prepared as $id=>$record)$this->persist($record,$current[$id]??null,$actor,$action);
            $this->db->setQuery('UPDATE #__kluconcountries_meta SET revision=revision+1 WHERE id=1')->execute();
            $this->db->transactionCommit(true);return array_keys($prepared);
        }catch(\Throwable $error){$this->db->transactionRollback(true);throw $error;}
    }
    private function persist(array $row,?object $current,int $actor,string $action): void {
            $id=$row['uuid'];$time=gmdate('c');
            if($current&&!$this->history($id,1))$this->record($current,'baseline',0);
            $data=(object)['uuid'=>$id,'parent_uuid'=>$row['parent_uuid'],'kind'=>$row['kind'],'ordering'=>$row['ordering'],'state'=>$row['state'],'version'=>($current?(int)$current->version:0)+1,
                'custom_names'=>Schema::json($row['names']),'custom_flag'=>$row['flag_mode']==='custom'?$row['flag']:null,'custom_data'=>Schema::json(['flag_mode'=>$row['flag_mode'],'label_key'=>$current?->data['label_key']??$current?->source['label_key']??null,'information'=>$row['information']]),
                'name_en'=>$row['names']['en-GB'],'valid_from'=>$row['valid_from'],'valid_to'=>$row['valid_to'],'modified_at'=>$time,'modified_by'=>$actor];
            if($current)$this->db->updateObject('#__kluconcountries_units',$data,'uuid',true);
            else{$data->names='{}';$data->record_data='{}';$data->created_at=$time;$data->created_by=$actor;$this->db->insertObject('#__kluconcountries_units',$data);}
            foreach(['identifiers','continents'] as $table)$this->db->setQuery($this->db->createQuery()->delete('#__kluconcountries_'.$table)->where('unit_uuid=:id')->bind(':id',$id))->execute();
            foreach($row['identifiers'] as $scheme=>$code)Schema::insert($this->db,'#__kluconcountries_identifiers',['scheme'=>$scheme,'code'=>$code,'unit_uuid'=>$id]);
            foreach($row['continents'] as $continent)Schema::insert($this->db,'#__kluconcountries_continents',['unit_uuid'=>$id,'continent_uuid'=>$continent]);
            $this->record($this->find($id),$action,$actor);
    }
    public function history(string $uuid,int $limit=50,int $offset=0): array {
        return $this->db->setQuery($this->db->createQuery()->select('*')->from('#__kluconcountries_revisions')->where('unit_uuid=:uuid')->bind(':uuid',$uuid)->order('version DESC'),max(0,$offset),max(1,min(200,$limit)))->loadAssocList();
    }
    public function historyTotal(string $uuid): int {
        return (int)$this->db->setQuery($this->db->createQuery()->select('COUNT(*)')->from('#__kluconcountries_revisions')->where('unit_uuid=:uuid')->bind(':uuid',$uuid))->loadResult();
    }
    private function record(object $row,string $action,int $actor): void {
        Schema::insert($this->db,'#__kluconcountries_revisions',['unit_uuid'=>$row->uuid,'version'=>(int)$row->version,'action'=>$action,'actor_id'=>$actor,'created_at'=>gmdate('c'),'snapshot'=>Schema::json($this->snapshot($row))]);
    }
    public function restore(string $uuid,int $revision,int $version,int $actor): void {
        $snapshot=$this->db->setQuery($this->db->createQuery()->select('snapshot')->from('#__kluconcountries_revisions')->where('unit_uuid=:uuid')->where('version=:revision')->bind(':uuid',$uuid)->bind(':revision',$revision))->loadResult();
        if(!$snapshot)throw new \RuntimeException('JERROR_PAGE_NOT_FOUND',404);$data=json_decode($snapshot,true,64,JSON_THROW_ON_ERROR);$data['version']=$version;$this->save($data,$actor,'restore');
    }
    public function state(array $ids,int $state,int $actor): void {
        if(!$ids||count($ids)>500||!in_array($state,[-2,0,1],true))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_INPUT');
        foreach($ids as $id)if(!is_string($id)||!self::uuid($id))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_UUID');
        $inputs=[];foreach(array_unique($ids) as $id){$item=$this->find($id);if(!$item)throw new \RuntimeException('JERROR_PAGE_NOT_FOUND',404);$inputs[]=['uuid'=>$id,'version'=>$item->version,'state'=>$state];}
        $this->saveMany($inputs,$actor,'state');
    }
    public function saveLabels(string $uuid,array $input): void {
        $current=$this->find($uuid);if(!$current)throw new \RuntimeException('JERROR_PAGE_NOT_FOUND',404);$names=array_intersect_key(json_decode($current->names,true,64,JSON_THROW_ON_ERROR),['en-GB'=>true]);
        if(isset($input['name_en'])&&!is_string($input['name_en']))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_NAME');$name=trim((string)($input['name_en']??''));if($name!=='')$names['en-GB']=$name;
        if(isset($input['flag'])&&!is_string($input['flag']))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_FLAG');$flag=trim((string)($input['flag']??''));
        $this->save(['uuid'=>$uuid,'version'=>$current->version,'names'=>$names,'flag'=>$flag?:null,'flag_mode'=>$flag?'custom':'source']);
    }
    public static function flags(): array {
        return array_keys(self::assets());
    }
    private static function assets(): array {
        if(self::$suppliedAssets===null){$manifest=json_decode(file_get_contents(JPATH_ADMINISTRATOR.'/components/com_kluconcountries/data/flags-manifest.json'),true,64,JSON_THROW_ON_ERROR);self::$suppliedAssets=array_column($manifest['assets'],null,'path');}return self::$suppliedAssets;
    }
    public function flagAllowed(string $flag): bool {
        if(in_array($flag,self::flags(),true))return true;
        return (bool)$this->db->setQuery($this->db->createQuery()->select('path')->from('#__kluconcountries_assets')->where('path=:flag')->bind(':flag',$flag))->loadResult();
    }
    public function flagUrl(?string $flag): ?string {
        if(!$flag||!$this->flagAllowed($flag))return null;$relative=str_starts_with($flag,'images/com_kluconcountries/')?$flag:'media/com_kluconcountries/'.$flag;
        $root=realpath(JPATH_ROOT);$path=realpath(JPATH_ROOT.'/'.$relative);
        if(!$root||!$path||!str_starts_with($path,$root.DIRECTORY_SEPARATOR)||!is_file($path)||is_link(JPATH_ROOT.'/'.$relative))return null;
        if(str_starts_with($flag,'images/com_kluconcountries/'))$hash=$this->db->setQuery($this->db->createQuery()->select('sha256')->from('#__kluconcountries_assets')->where('path=:flag')->bind(':flag',$flag))->loadResult();
        else $hash=self::assets()[$flag]['sha256']??null;
        if(!$hash||!hash_equals($hash,hash_file('sha256',$path)))return null;return $relative;
    }
}
