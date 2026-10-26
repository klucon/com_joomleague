<?php
declare(strict_types=1);
// Included only by the guarded, disposable catalogue acceptance fixture.
if (!isset($db, $service, $sql) || $app->get('db') !== 'r1_test' || !str_starts_with((string) $app->get('host'), 'jl-r1-')) throw new RuntimeException('Disposable target required.');
if ((int) $db->setQuery('SELECT COUNT(*) FROM #__joomleague_sport_type')->loadResult() !== 0) throw new RuntimeException('Conflict suite requires an empty test catalogue.');
$versions = [];
foreach (['football', 'tennis'] as $code) {
    $versions[$code] = (int) $db->setQuery('SELECT v.id FROM #__joomleague_sport_profile_version v INNER JOIN #__joomleague_sport_profile p ON p.id=v.profile_id WHERE p.code=' . $db->quote($code) . ' ORDER BY v.id DESC', 0, 1)->loadResult();
    if ($versions[$code] < 1) throw new RuntimeException('Missing test profile.');
}
$reset = static function () use ($db): void {
    if ((int) $db->setQuery('SELECT COUNT(*) FROM #__joomleague_sport_type WHERE id NOT IN (1,2,7)')->loadResult() !== 0) throw new RuntimeException('Unexpected test catalogue owner.');
    $db->setQuery('DELETE FROM #__joomleague_sport_type WHERE id IN (1,2,7)')->execute();
};
$sport = static function (int $id, ?int $version, string $code) use ($db): void {
    $row = (object) ['id'=>$id, 'profile_version_id'=>$version, 'code'=>$code, 'name'=>'Existing '.$code, 'published'=>1];
    $db->insertObject('#__joomleague_sport_type', $row);
};
$position = static function (int $sportId, string $code='analityk', ?string $uuid=null) use ($db): int {
    $row = (object) ['uuid'=>$uuid ?? Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4(), 'sport_type_id'=>$sportId, 'code'=>$code, 'name'=>'Existing analyst', 'person_type'=>'staff', 'role_domain'=>'team_staff', 'source'=>'local', 'published'=>1];
    $db->insertObject('#__joomleague_sport_position', $row, 'id');
    return (int) $row->id;
};
$inventory = static fn (): array => $db->setQuery('SELECT id,uuid,sport_type_id,code,name,person_type,role_domain,source,published FROM #__joomleague_sport_position ORDER BY id')->loadAssocList();
$collisionCases = [];
$record = static function (string $id, array $details) use (&$collisionCases): void { $collisionCases[]=['id'=>$id]+$details; };
$catalogueSnapshot = static function () use ($db): array {
    $state=[];
    foreach(['sport_type','sport_position','event_type','statistic'] as $table) $state[$table]=$db->setQuery('SELECT * FROM #__joomleague_'.$table.' ORDER BY id')->loadAssocList();
    return $state;
};
$reject = static function (string $input, string $case) use ($service, $catalogueSnapshot, $record): void {
    $before=$catalogueSnapshot();$error=null;
    try{$service->import($input);}catch(Joomleague\Component\Joomleague\Administrator\Service\ImportCatalogueConflict $caught){$error=$caught;}
    if($error===null||$catalogueSnapshot()!==$before)throw new RuntimeException('Conflict was not rejected without mutation: '.$case);
    $record($case,['rejected'=>true,'original_preserved'=>true,'diagnostic'=>$error->getMessage(),'entity'=>$error->entity]);
};

try {
    $sport(1,$versions['football'],'pilka_nozna');$position(1);
    $existing=$inventory()[0];$result=$service->import($sql);$rows=$inventory();
    if (count($rows)!==29 || !in_array($existing,$rows,true)) throw new RuntimeException('Compatible partial catalogue did not preserve/fill roles.');
    $again=$service->import($sql);if($inventory()!==$rows)throw new RuntimeException('Partial catalogue retry was not idempotent.');
    $record('compatible-partial-catalogue',['total'=>count($rows),'existing_preserved'=>true,'retry_unchanged'=>true,'import'=>$result]);
    $reset();

    $sport(1,null,'pilka_nozna');$position(1);
    $reject($sql,'existing-owner-without-profile');
    $reset();

    $sport(1,$versions['tennis'],'existing_tennis');
    $reject($sql,'different-sport-same-id');
    $reset();

    $sport(7,$versions['football'],'pilka_nozna');$position(7);
    $reject($sql,'same-code-different-id');
    $reset();

    $sport(1,$versions['football'],'pilka_nozna');$position(1);$before=$inventory();
    $allBefore=$catalogueSnapshot();
    $broken=$sql."\nINSERT INTO `#__joomleague_sport_position` (`uuid`,`sport_type_id`,`code`,`name`,`person_type`,`role_domain`) VALUES ('11111111-1111-4111-8111-111111111111','999999','invalid_reference','Invalid fixture','staff','team_staff');\n";
    $backendBefore=$db->getName()==='pgsql'?(int)$db->getConnection()->query('SELECT pg_backend_pid()')->fetchColumn():null;
    $failed=false;try{$service->import($broken);}catch(Throwable){$failed=true;}
    $afterFailure=$inventory();
    if(!$failed)throw new RuntimeException('Broken reference was not rejected.');
    $backendAfter=$db->getName()==='pgsql'?(int)$db->getConnection()->query('SELECT pg_backend_pid()')->fetchColumn():null;
    if($afterFailure!==$before||$catalogueSnapshot()!==$allBefore||$backendBefore!==$backendAfter)throw new RuntimeException('Failed import escaped its original transaction.');
    $record('late-failure-retains-one-position',['error_propagated'=>true,'before_positions'=>count($before),'after_positions'=>count($afterFailure),'original_preserved'=>$afterFailure===$before,'profile_rows_after'=>count(array_filter($afterFailure,static fn($r)=>$r['source']==='profile')),'backend_changed'=>$backendBefore!==$backendAfter]);
    $reset();

    $sport(2,$versions['football'],'other_football');
    $position(2,'other_analyst','f7ba7297-d410-5838-8a1e-5501731da0b9');
    $reject($sql,'position-uuid-owned-by-another-sport');
    $reset();

    $sport(2,$versions['football'],'other_football');$position(2,'untouched_analyst');
    $other=$inventory()[0];$service->import($sql);
    if((int)$db->setQuery('SELECT COUNT(*) FROM #__joomleague_sport_position WHERE sport_type_id=1')->loadResult()!==29 || $db->setQuery('SELECT id,uuid,sport_type_id,code,name,person_type,role_domain,source,published FROM #__joomleague_sport_position WHERE sport_type_id=2')->loadAssocList()!==[$other])throw new RuntimeException('Unrelated sport catalogue was materialized.');
    foreach(['event_type','statistic'] as $table)if((int)$db->setQuery('SELECT COUNT(*) FROM #__joomleague_'.$table.' WHERE sport_type_id=2')->loadResult()!==0)throw new RuntimeException('Unrelated default catalogue changed.');
    $record('only-imported-owner-materialized',['unrelated_owner_unchanged'=>true]);
    $reset();

    $sport(1,$versions['football'],'pilka_nozna');$position(1,'renamed_analyst','f7ba7297-d410-5838-8a1e-5501731da0b9');
    $reject($sql,'same-uuid-incompatible-code');
    $reset();

    foreach (['event_type', 'statistic'] as $table) {
        $sport(2,$versions['football'],'other_football');
        $uuid='22222222-2222-4222-8222-222222222222';
        $row=(object)['uuid'=>$uuid,'sport_type_id'=>2,'code'=>'existing_catalogue','name'=>'Existing catalogue'];
        if($table==='statistic')$row->scope='person';
        $db->insertObject('#__joomleague_'.$table,$row);
        $columns='`uuid`,`sport_type_id`,`code`,`name`';
        $values="'$uuid','1','existing_catalogue','Incoming catalogue'";
        if($table==='statistic'){$columns.=',`scope`';$values.=",'person'";}
        $reject($sql."\nINSERT INTO `#__joomleague_$table` ($columns) VALUES ($values);",$table.'-uuid-owned-by-another-sport');
        $reset();
    }
} finally { $reset(); }
