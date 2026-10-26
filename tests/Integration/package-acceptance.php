<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||!str_starts_with((string)getenv('JL_R1_DB_HOST'),'jl-r1-'))throw new RuntimeException('Disposable target required');
define('_JEXEC',1);define('JPATH_BASE','/var/www/html');
$_SERVER['HTTP_HOST']='localhost';$_SERVER['SCRIPT_NAME']='/administrator/index.php';$_SERVER['REQUEST_URI']='/administrator/';
require JPATH_BASE.'/includes/defines.php';require JPATH_BASE.'/includes/framework.php';
$container=Joomla\CMS\Factory::getContainer();$container->alias('session','session.cli')->alias(Joomla\Session\SessionInterface::class,'session.cli');
$app=$container->get(Joomla\CMS\Application\AdministratorApplication::class);Joomla\CMS\Factory::$application=$app;
if($app->get('host')!==getenv('JL_R1_DB_HOST')||$app->get('db')!=='r1_test'||$app->get('dbprefix')!=='r1_')throw new RuntimeException('Unsafe target');
$db=$container->get(Joomla\Database\DatabaseInterface::class);
$assert=static function(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);};
$manifest=simplexml_load_file('/opt/suite/pkg_joomleague.xml');$assert($manifest!==false,'Package manifest unreadable');$version=(string)$manifest->version;
$nested=static function(string $id)use($manifest):string{$nodes=$manifest->xpath('/extension/files/file[@id="'.$id.'"]');if(count($nodes)!==1)throw new RuntimeException('Package child not unique');return '/opt/suite/'.(string)$nodes[0];};
$hash=static function(string $zip,string $member):string{$archive=new ZipArchive();if($archive->open($zip)!==true)throw new RuntimeException('Nested archive unreadable');try{$bytes=$archive->getFromName($member);if($bytes===false)throw new RuntimeException('Nested file missing');return hash('sha256',$bytes);}finally{$archive->close();}};
$snapshots=static function()use($db):array{$state=[];foreach(['project','project_entry','project_entry_member','project_match','match_participant','person','project_stage','project_round','stage_transition','match_result','match_score_segment','match_score_value']as$table)$state[$table]=$db->setQuery($db->getQuery(true)->select('*')->from($db->quoteName('#__joomleague_'.$table))->order('id'))->loadAssocList();return $state;};
$component=$nested('com_joomleague');$module=$nested('mod_joomleague_standings');
$languages=[
 JPATH_BASE.'/language/en-GB/mod_joomleague_standings.ini'=>$hash($module,'language/en-GB/mod_joomleague_standings.ini'),
 JPATH_ADMINISTRATOR.'/language/en-GB/com_joomleague.ini'=>$hash($component,'admin/language/en-GB/com_joomleague.ini'),
 JPATH_ADMINISTRATOR.'/components/com_joomleague/language/en-GB/com_joomleague.ini'=>$hash($component,'admin/language/en-GB/com_joomleague.ini'),
];
$expectedVersions=[];
$registerLanguages=static function(SimpleXMLElement $xml,string $archive,string $localRoot,string $globalRoot)use(&$languages,$hash):void{
 foreach($xml->language??[]as$language){
  $member=trim((string)$xml['folder'].'/'.(string)$language,'/');$tag=(string)$language['tag'];
  if($tag!=='en-GB'||!preg_match('~^[A-Za-z0-9_./-]+\.ini$~D',$member)||in_array('..',explode('/',$member),true))throw new RuntimeException('Unsafe/non-English core language inventory');
  $expected=$archive===''?hash_file('sha256','/opt/suite/'.$member):$hash($archive,$member);
  $languages[$globalRoot.'/language/'.$tag.'/'.basename($member)]=$expected;
  $relative=(string)$language;
  if($localRoot!==''&&is_file($localRoot.'/'.$relative))$languages[$localRoot.'/'.$relative]=$expected;
 }
};
$registerLanguages($manifest->languages,'','',JPATH_BASE);
foreach($manifest->files->file as$child){
 $archive='/opt/suite/'.(string)$child;$zip=new ZipArchive();if($zip->open($archive)!==true)throw new RuntimeException('Child archive unreadable');
 try{$roots=[];for($i=0;$i<$zip->numFiles;$i++){$name=$zip->getNameIndex($i);if(!str_contains($name,'/')&&str_ends_with($name,'.xml'))$roots[]=$name;}if(count($roots)!==1)throw new RuntimeException('Child root manifest missing');$xml=simplexml_load_string($zip->getFromName($roots[0]));}finally{$zip->close();}
 $type=(string)$child['type'];$element=(string)$child['id'];
 $expectedVersions[$element.':'.(string)$child['group']] = (string)$xml->version;
 if($type==='component'){
  $registerLanguages($xml->languages,$archive,JPATH_BASE.'/components/'.$element,JPATH_BASE);
  $registerLanguages($xml->administration->languages,$archive,JPATH_ADMINISTRATOR.'/components/'.$element,JPATH_ADMINISTRATOR);
 }else{
  $local=$type==='module'?JPATH_BASE.'/modules/'.$element:JPATH_PLUGINS.'/'.(string)$child['group'].'/'.$element;
  $registerLanguages($xml->languages,$archive,$local,$type==='plugin'?JPATH_ADMINISTRATOR:JPATH_BASE);
 }
}
$action=$argv[1]??'';
if($action==='seed'){
 $app->createExtensionNamespaceMap();$app->bootComponent('com_joomleague');
 $fixture=json_decode(file_get_contents('/tmp/jl-r1-fixture.json'),true,512,JSON_THROW_ON_ERROR);$match=(int)$fixture['projects']['a']['records']['Match'];
 $participants=array_map('intval',$db->setQuery($db->getQuery(true)->select('id')->from($db->quoteName('#__joomleague_match_participant'))->where('match_id = :match')->order('slot_number')->bind(':match',$match,Joomla\Database\ParameterType::INTEGER))->loadColumn());
 $values=static fn(array $scores):array=>array_map(static fn(int $index):array=>['participant_id'=>$participants[$index],'numeric_value'=>(string)$scores[$index]],[0,1]);
 $payload=['result_type'=>'numeric_score','status_code'=>'final','outcome_code'=>'completed','notes'=>'Package preservation fixture','segments'=>[['level_code'=>'result','values'=>$values([2,1]),'children'=>[['level_code'=>'period','sequence_number'=>1,'values'=>$values([1,0])],['level_code'=>'period','sequence_number'=>2,'values'=>$values([1,1])]]]]];
 (new Joomleague\Component\Joomleague\Administrator\Service\MatchResultRepository($db))->replace((int)$match,$payload,0);
 $untouched=[];
 foreach([JPATH_BASE.'/language/overrides/en-GB.override.ini',JPATH_ADMINISTRATOR.'/language/overrides/en-GB.override.ini',JPATH_ADMINISTRATOR.'/language/de-DE/com_joomleague.ini',JPATH_ADMINISTRATOR.'/components/com_joomleague/language/de-DE/com_joomleague.ini',JPATH_BASE.'/language/pl-PL/mod_joomleague_standings.ini',JPATH_ADMINISTRATOR.'/language/cs-CZ/com_joomleague.ini',JPATH_ADMINISTRATOR.'/components/com_joomleague/language/cs-CZ/com_joomleague.ini',JPATH_BASE.'/language/cs-CZ/mod_joomleague_standings.ini']as$file){
  if(!is_dir(dirname($file)))mkdir(dirname($file),0775,true);
  $bytes="COM_JOOMLEAGUE_PACKAGE_TEST=\"Keep independently maintained translation\"\n";
  $assert(file_put_contents($file,$bytes)!==false,'Cannot create preservation fixture');$untouched[$file]=hash('sha256',$bytes);
 }
 $freshEnglish=0;
 foreach($languages as$file=>$expected){
  if(str_contains($file,'/language/en-GB/')){$assert(is_file($file)&&hash_file('sha256',$file)===$expected,'English language was not freshly installed: '.$file);$freshEnglish++;}
  $assert(file_put_contents($file,"; Stale disposable language fixture\n")!==false,'Cannot simulate stale language');
 }
 $assert($freshEnglish>=51,'Incomplete fresh English inventory');
 $before=['rows'=>$snapshots(),'languages'=>$languages,'untouched'=>$untouched,'fresh_english'=>$freshEnglish];
 $assert(file_put_contents('/tmp/jl-package-before.json',json_encode($before,JSON_THROW_ON_ERROR))!==false,'Cannot save package baseline');echo "PASS: package fixture and stale language files prepared\n";exit;
}
if($action!=='verify')throw new InvalidArgumentException('Unknown package check');
$observations=[];$record=static function(string $id,int $count)use(&$observations):void{$observations[]=['id'=>$id,'status'=>200,'evidenceCount'=>$count];echo 'PASS: '.$id."\n";};
$package=$db->setQuery($db->getQuery(true)->select(['extension_id','manifest_cache'])->from($db->quoteName('#__extensions'))->where("type = 'package'")->where("element = 'pkg_joomleague'"))->loadObject();
$assert($package!==null,'Package extension missing');$assert((json_decode($package->manifest_cache,true,512,JSON_THROW_ON_ERROR)['version']??null)===$version,'Package version mismatch');
$packageId=(int)$package->extension_id;
$children=$db->setQuery($db->getQuery(true)->select(['element','folder','manifest_cache'])->from($db->quoteName('#__extensions'))->where('package_id = :package')->bind(':package',$packageId,Joomla\Database\ParameterType::INTEGER))->loadObjectList();
$assert(count($children)===23,'Package child ownership count mismatch');
$expected=[];foreach($manifest->files->file as $file)$expected[]=(string)$file['id'].':'.(string)$file['group'];
$actual=[];foreach($children as $child){$key=$child->element.':'.(string)$child->folder;$actual[]=$key;$assert((json_decode($child->manifest_cache,true,512,JSON_THROW_ON_ERROR)['version']??null)===($expectedVersions[$key]??null),'Child version mismatch: '.$key);}
sort($actual);sort($expected);$assert($actual===$expected,'Package children differ from manifest');$record('extensions',24);
$before=json_decode(file_get_contents('/tmp/jl-package-before.json'),true,512,JSON_THROW_ON_ERROR);$after=$snapshots();
$assert($before['fresh_english']>=51,'Fresh English baseline missing');$record('language-install',$before['fresh_english']);
foreach($before['rows'] as $table=>$rows){$assert(count($rows)===count($after[$table]),'Update changed fixture count: '.$table);foreach($rows as $index=>$row)$assert(array_intersect_key($after[$table][$index],$row)===$row,'Update changed existing fixture data: '.$table);}$record('data-preserved',count($after));
foreach($before['languages'] as $file=>$expectedHash)$assert(hash_file('sha256',$file)===$expectedHash,'Update left a stale language file: '.$file);$record('language-overwrite',count($languages));
foreach($before['untouched']as$file=>$expectedHash)$assert(is_file($file)&&hash_file('sha256',$file)===$expectedHash,'Update altered independently maintained language: '.$file);
$record('separate-languages-preserved',6);$record('overrides-preserved',2);
foreach(['joomla.asset.json','js/matchresult.js'] as $file)$assert(hash_file('sha256',JPATH_BASE.'/media/com_joomleague/'.$file)===$hash($component,'media/'.$file),'Installed media mismatch: '.$file);$record('media-overwrite',2);
$query=$db->getQuery(true)->select('site.location')->from($db->quoteName('#__update_sites','site'))->innerJoin($db->quoteName('#__update_sites_extensions','link').' ON link.update_site_id = site.update_site_id')->where('link.extension_id = :package')->where('site.enabled = 1')->bind(':package',$packageId,Joomla\Database\ParameterType::INTEGER);
$assert($db->setQuery($query)->loadColumn()===['https://downloads.joomleague.eu/update.xml'],'Package update server is missing or duplicated');$record('update-server',1);
file_put_contents('/tmp/jl-package-report.json',json_encode(['observations'=>$observations],JSON_THROW_ON_ERROR));
