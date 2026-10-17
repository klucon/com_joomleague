<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
$count=0;
foreach(glob($root.'/modules/mod_joomleague_*/src/Helper/*.php')as$file){
    $source=file_get_contents($file);
    preg_match_all('/catch\s*\(\\\\Throwable(?:\s+\$\w+)?\)\s*\{/',$source,$catches);
    preg_match_all('/catch\s*\(\\\\Throwable\s+\$\w+\)\s*\{\s*\\\\Joomla\\\\CMS\\\\Log\\\\Log::add\([^;]+get_debug_type\(/',$source,$logged);
    if(count($catches[0])!==count($logged[0]))throw new RuntimeException('Missing safe failure diagnostic: '.basename($file));
    $count+=count($logged[0]);
}
if($count<22)throw new RuntimeException('Module catch coverage unexpectedly decreased');
echo 'PASS: '.$count." module Throwable handlers emit type-only diagnostics\n";
