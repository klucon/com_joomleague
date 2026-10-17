import {readFileSync, mkdirSync, openSync, closeSync, writeFileSync} from 'node:fs';
import {spawnSync} from 'node:child_process';
const root='/mnt/disk-a/dev/com_joomleague_6-2-0';
function run(args){const r=spawnSync('docker',args,{encoding:'utf8'});if(r.status!==0)throw Error(r.stderr);return r.stdout;}
const backup='/mnt/disk-a/dev/backups/calendar-review-'+new Date().toISOString().replaceAll(':','-');
mkdirSync(backup,{mode:0o700});
run(['exec','joomla62-dev-app','tar','-czf','/tmp/calendar-before.tar.gz','-C','/var/www/html','modules/mod_joomleague_calendar','language/en-GB/mod_joomleague_calendar.ini','language/en-GB/mod_joomleague_calendar.sys.ini','language/cs-CZ/mod_joomleague_calendar.ini','language/cs-CZ/mod_joomleague_calendar.sys.ini']);
run(['exec','joomla62-dev-app','tar','-tzf','/tmp/calendar-before.tar.gz']);
run(['cp','joomla62-dev-app:/tmp/calendar-before.tar.gz',backup+'/files.tar.gz']);
const config=JSON.parse(run(['exec','joomla62-dev-app','php','-r','require "/var/www/html/configuration.php"; $c=new JConfig; echo json_encode(["database"=>$c->db,"user"=>$c->user,"password"=>$c->password]);']));
const fd=openSync(backup+'/database.sql','w',0o600);
const dump=spawnSync('docker',['exec','-e','MYSQL_PWD='+config.password,'joomla62-dev-mariadb','mariadb-dump','--single-transaction','--no-tablespaces','-u',config.user,config.database],{stdio:['ignore',fd,'pipe']});closeSync(fd);
if(dump.status!==0)throw Error('Database backup failed');
if(!readFileSync(backup+'/database.sql','utf8').includes('Dump completed'))throw Error('Incomplete database backup');
const hash=spawnSync('sha256sum',[backup+'/files.tar.gz',backup+'/database.sql'],{encoding:'utf8'});
if(hash.status)throw Error('Backup checksum failed');writeFileSync(backup+'/SHA256SUMS',hash.stdout);
run(['cp',root+'/modules/mod_joomleague_calendar/.','joomla62-dev-app:/var/www/html/modules/mod_joomleague_calendar']);
for(const lang of ['en-GB','cs-CZ']) for(const ext of ['ini','sys.ini'])run(['cp',`${root}/modules/mod_joomleague_calendar/language/${lang}/mod_joomleague_calendar.${ext}`,`joomla62-dev-app:/var/www/html/language/${lang}/mod_joomleague_calendar.${ext}`]);
const targets = ['/var/www/html/modules/mod_joomleague_calendar'];
for (const lang of ['en-GB', 'cs-CZ']) for (const ext of ['ini', 'sys.ini'])
 targets.push(`/var/www/html/language/${lang}/mod_joomleague_calendar.${ext}`);
// docker cp preserves host ownership; include global language files as well.
run(['exec','joomla62-dev-app','chown','-R','33:33',...targets]);
for (const target of targets)
 run(['exec','--user','www-data','joomla62-dev-app','test','-w',target]);
console.log('Calendar deployed to development only; verified backup: '+backup);
