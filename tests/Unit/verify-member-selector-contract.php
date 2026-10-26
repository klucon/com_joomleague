<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);$admin=$root.'/administrator/components/com_joomleague';
$form=simplexml_load_file($admin.'/forms/entrymember.xml');
$names=array_map(static fn($f)=>(string)$f['name'],$form->xpath('//fieldset[@name="details"]/field'));
if (array_search('member_person_type',$names,true)>=array_search('person_id',$names,true)) throw new RuntimeException('Person type must precede person');
$person=$form->xpath('//field[@name="person_id"]')[0];
if ((string)$person['type']!=='Entrymemberperson'||(string)$person['filter']!=='uint') throw new RuntimeException('Bounded person field required');
foreach(['Clubs'=>'a.name','Teams'=>'a.name','Persons'=>'a.last_name']as$model=>$column){
	$source=file_get_contents($admin.'/src/Model/'.$model.'Model.php');
	if(!str_contains($source,"populateState(\$ordering = '$column', \$direction = 'asc')"))throw new RuntimeException('Alphabetical default missing');
}
$count=0;foreach(glob($admin.'/forms/*.xml')as$file){$xml=simplexml_load_file($file);foreach($xml->xpath('//field[@type="sql"]')as$field){
	if((string)$field['layout']!=='joomla.form.field.list-fancy-select')throw new RuntimeException('Search missing in '.basename($file));$count++;
}}
$js=file_get_contents($root.'/media/com_joomleague/js/entrymember.js');
if(str_contains($js,'innerHTML')||!str_contains($js,'AbortController')||!str_contains($js,'revision !== sequence')||!str_contains($js,'new Option('))throw new RuntimeException('Safe asynchronous selector contract missing');
echo "PASS: type-first, bounded person field, three alphabetical directories, $count native searchable SQL fields, stale-response and text-only rendering contracts.\n";
