<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||!str_starts_with((string)getenv('JL_R1_DB_HOST'),'jl-r1-'))throw new RuntimeException('Disposable target required');
require '/var/www/html/configuration.php';$config=new JConfig();
if($config->dbprefix!=='r1_'||$config->db!=='r1_test')throw new RuntimeException('Unsafe target');
$state='/tmp/jl-legacy-language-preservation.json';
if(($argv[1]??'')==='seed'){
 $files=glob('/var/www/html/administrator/components/com_joomleague/language/cs-CZ/*.ini')?:[];
 if(!$files)throw new RuntimeException('Actual legacy Czech component payload required');
 foreach(['/var/www/html/administrator/language/cs-CZ/com_joomleague.ini','/var/www/html/language/cs-CZ/mod_joomleague_standings.ini']as$file){if(!is_dir(dirname($file)))mkdir(dirname($file),0775,true);file_put_contents($file,"; Independently maintained Czech translation\nCOM_JOOMLEAGUE_LANGUAGE_TEST=\"Keep Czech\"\n");$files[]=$file;}
 $hashes=[];foreach($files as$file)$hashes[$file]=hash_file('sha256',$file);
 file_put_contents($state,json_encode($hashes,JSON_THROW_ON_ERROR));echo 'PASS: actual RC1 Czech files and external translation markers recorded'.PHP_EOL;exit;
}
if(($argv[1]??'')!=='verify')throw new RuntimeException('Invalid mode');
$hashes=json_decode(file_get_contents($state),true,512,JSON_THROW_ON_ERROR);foreach($hashes as$file=>$hash)if(!is_file($file)||hash_file('sha256',$file)!==$hash)throw new RuntimeException('Upgrade changed independent Czech translation: '.$file);
echo 'PASS: '.count($hashes).' Czech language files preserved through actual legacy-to-English-only upgrade'.PHP_EOL;
