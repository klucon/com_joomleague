const fs = require('node:fs');
const cp = require('node:child_process');
const backup = '/mnt/disk-a/dev/backups/standings-review-2026-10-02T09-43-25.952Z/database.sql';
const staging = 'lechia_stage_20261010';
const dbContainer = 'local-joomla62-db';
function sql(query, database = '') {
    const args = ['exec', '-i', dbContainer, 'sh', '-c', 'exec mariadb -uroot -p"$(cat /run/secrets/root-password)" --batch --skip-column-names ' + database];
    const result = cp.spawnSync('docker', args, {input:query, encoding:'utf8', maxBuffer:64*1024*1024});
    if (result.status !== 0) throw Error((result.stderr || '').split('\n').filter(line=>line.startsWith('ERROR')).join('\n') || 'Database command failed');
    return result.stdout;
}
if (process.argv[2] === 'stage') {
    const exists = sql("SHOW DATABASES LIKE '" + staging + "';").trim();
    if (exists && !process.argv.includes('--resume')) throw Error('Staging database already exists; refusing to overwrite.');
    const lines = fs.readFileSync(backup, 'utf8').split('\n');
    let selected = false;
    const output = ['SET NAMES utf8mb4;', "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';", 'SET FOREIGN_KEY_CHECKS=0;'];
    const tables = [];
    for (const line of lines) {
        const match = line.match(/^-- Table structure for table `([^`]+)`$/);
        if (match) {
            selected = /^jl62_joomleague_[a-z_]+$/.test(match[1]);
            if (selected) tables.push(match[1]);
        }
        if (selected && !line.startsWith('-- Dump completed')) output.push(line);
    }
    if (tables.length < 30) throw Error('Incomplete source catalogue.');
    if (!exists) sql('CREATE DATABASE `' + staging + '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
    sql(output.join('\n'), staging);
    console.log('Staged ' + tables.length + ' JoomLeague tables only.');
}
console.log(sql("SELECT COUNT(*) AS clubs FROM jl62_joomleague_club; SELECT COUNT(*) AS persons FROM jl62_joomleague_person; SELECT COUNT(*) AS projects FROM jl62_joomleague_project; SELECT name FROM jl62_joomleague_club WHERE name LIKE '%Lechia%';", staging));
if (process.argv[2] === 'inspect') {
    console.log(sql("SELECT id,code FROM joomla62_joomleague_sport_profile ORDER BY id;", 'joomla'));
    console.log(sql("SELECT id,code FROM jl62_joomleague_sport_profile ORDER BY id;", staging));
    console.log(sql("SELECT sp.code,sv.profile_version FROM `"+staging+"`.jl62_joomleague_sport_profile_version sv JOIN `"+staging+"`.jl62_joomleague_sport_profile sp ON sp.id=sv.profile_id JOIN joomla62_joomleague_sport_profile tp ON tp.code=sp.code JOIN joomla62_joomleague_sport_profile_version tv ON tv.profile_id=tp.id AND tv.profile_version=sv.profile_version WHERE tv.payload_checksum<>sv.payload_checksum;", 'joomla'));
}
if (process.argv[2] === 'import') {
    const targetPrefix = 'joomla62_joomleague_';
    const sourcePrefix = 'jl62_joomleague_';
    const quote = name => {
        if (!/^[a-z0-9_]+$/.test(name)) throw Error('Invalid schema identifier');
        return '`' + name + '`';
    };
    const metadata = schema => JSON.parse(sql("SELECT JSON_ARRAYAGG(JSON_OBJECT('table',TABLE_NAME,'column',COLUMN_NAME,'extra',EXTRA)) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='" + schema + "' AND TABLE_NAME LIKE '%joomleague%' ORDER BY ORDINAL_POSITION;").trim());
    const targetColumns = metadata('joomla');
    const sourceColumns = metadata(staging);
    const sourceTables = new Set(sourceColumns.map(c=>c.table));
    const keep = new Set(['sport_profile','sport_profile_version','sport_category','sport_category_lock','sport_profile_category','sport_variant','sport_variant_version']);
    const tables = [...new Set(targetColumns.map(c=>c.table))].filter(t=>t.startsWith(targetPrefix) && !keep.has(t.slice(targetPrefix.length)));
    const foreign = JSON.parse(sql("SELECT JSON_ARRAYAGG(JSON_OBJECT('table',TABLE_NAME,'column',COLUMN_NAME,'parent',REFERENCED_TABLE_NAME,'parent_column',REFERENCED_COLUMN_NAME,'constraint',CONSTRAINT_NAME)) FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA='joomla' AND REFERENCED_TABLE_NAME IS NOT NULL AND TABLE_NAME LIKE '%joomleague%';").trim());
    const qualified = table => quote(staging) + '.' + quote(table);
    const statements = ['SET FOREIGN_KEY_CHECKS=0;', 'START TRANSACTION;'];
    // Keep installed catalogue IDs; append historical versions without replacing existing snapshots.
    statements.push('INSERT INTO joomla62_joomleague_sport_profile (code,name_key,description_key,published) SELECT s.code,s.name_key,s.description_key,s.published FROM ' + qualified(sourcePrefix+'sport_profile') + ' s LEFT JOIN joomla62_joomleague_sport_profile t ON t.code=s.code WHERE t.id IS NULL;');
    statements.push('INSERT INTO joomla62_joomleague_sport_profile_version (profile_id,schema_version,profile_version,payload_json,payload_checksum,source,state) SELECT p.id,s.schema_version,s.profile_version,s.payload_json,s.payload_checksum,s.source,\'superseded\' FROM '+qualified(sourcePrefix+'sport_profile_version')+' s JOIN '+qualified(sourcePrefix+'sport_profile')+' sp ON sp.id=s.profile_id JOIN joomla62_joomleague_sport_profile p ON p.code=sp.code LEFT JOIN joomla62_joomleague_sport_profile_version v ON v.profile_id=p.id AND v.profile_version=s.profile_version WHERE v.id IS NULL;');
    statements.push('CREATE TEMPORARY TABLE _jl_profile_map AS SELECT s.id AS old_id,t.id AS new_id FROM '+qualified(sourcePrefix+'sport_profile')+' s JOIN joomla62_joomleague_sport_profile t ON t.code=s.code;');
    statements.push('CREATE TEMPORARY TABLE _jl_version_map AS SELECT s.id AS old_id,t.id AS new_id FROM '+qualified(sourcePrefix+'sport_profile_version')+' s JOIN _jl_profile_map p ON p.old_id=s.profile_id JOIN joomla62_joomleague_sport_profile_version t ON t.profile_id=p.new_id AND t.profile_version=s.profile_version AND t.payload_checksum=s.payload_checksum;');
    statements.push('CREATE TEMPORARY TABLE _jl_import_check (n BIGINT CHECK (n=0));');
    statements.push('INSERT INTO _jl_import_check SELECT (SELECT COUNT(*) FROM '+qualified(sourcePrefix+'sport_profile_version')+')-(SELECT COUNT(*) FROM _jl_version_map);');
    for (const table of tables) statements.push('DELETE FROM '+quote(table)+';');
    const copied = [];
    for (const table of tables) {
        const source = sourcePrefix+table.slice(targetPrefix.length);
        if (!sourceTables.has(source)) continue;
        const available = new Set(sourceColumns.filter(c=>c.table===source).map(c=>c.column));
        const columns = targetColumns.filter(c=>c.table===table && !c.extra.includes('GENERATED') && available.has(c.column)).map(c=>c.column);
        const joins = [];
        const values = columns.map(column=>{
            if (column==='checked_out') return '0';
            if (column==='checked_out_time') return 'NULL';
            const fk = foreign.find(f=>f.table===table && f.column===column && ['sport_profile','sport_profile_version'].some(s=>f.parent===targetPrefix+s));
            if (!fk) return 's.'+quote(column);
            const alias = 'map_'+column;
            const map = fk.parent===targetPrefix+'sport_profile' ? '_jl_profile_map' : '_jl_version_map';
            joins.push('LEFT JOIN '+map+' '+alias+' ON '+alias+'.old_id=s.'+quote(column));
            return alias+'.new_id';
        });
        statements.push('INSERT INTO '+quote(table)+' ('+columns.map(quote).join(',')+') SELECT '+values.join(',')+' FROM '+qualified(source)+' s '+joins.join(' ')+';');
        statements.push('INSERT INTO _jl_import_check SELECT (SELECT COUNT(*) FROM '+quote(table)+')-(SELECT COUNT(*) FROM '+qualified(source)+');');
        copied.push(table);
    }
    const groups = new Map();
    for (const fk of foreign.filter(f=>tables.includes(f.table))) {
        const key = fk.table+':'+fk.constraint;
        groups.set(key, [...(groups.get(key)||[]),fk]);
    }
    for (const group of groups.values()) {
        const conditions = group.map(f=>'p.'+quote(f.parent_column)+'=c.'+quote(f.column)).join(' AND ');
        const nonNull = group.map(f=>'c.'+quote(f.column)+' IS NOT NULL').join(' AND ');
        statements.push('INSERT INTO _jl_import_check SELECT COUNT(*) FROM '+quote(group[0].table)+' c WHERE '+nonNull+' AND NOT EXISTS (SELECT 1 FROM '+quote(group[0].parent)+' p WHERE '+conditions+');');
    }
    statements.push('COMMIT;', 'SET FOREIGN_KEY_CHECKS=1;');
    sql(statements.join('\n'), 'joomla');
    console.log('Imported '+copied.length+' runtime tables; source counts, profile maps and foreign keys verified before commit.');
    console.log(sql('SELECT COUNT(*) FROM joomla62_joomleague_club; SELECT COUNT(*) FROM joomla62_joomleague_person; SELECT COUNT(*) FROM joomla62_joomleague_project;', 'joomla'));
}
if (process.argv[2] === 'cleanup') {
    sql('DROP DATABASE `' + staging + '`;');
    console.log('Removed temporary staging database only.');
}
