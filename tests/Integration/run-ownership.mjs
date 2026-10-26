import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';
import {fileURLToPath} from 'node:url';
import {execFileSync,spawnSync} from 'node:child_process';
import {randomBytes,createHash} from 'node:crypto';
import {createRequire} from 'node:module';
const require=createRequire(import.meta.url);
const {evaluateProcess}=require('./audit-process.cjs');
const {required:auditRequired,browserRequired}=require('./audit-contract.cjs');
const {summarize}=require('../Browser/audit-result.cjs');
const {required:footballRequired}=require('./football-result-contract.cjs');
const {required:familyRequired}=require('./family-browser-contract.cjs');
const {required:assetRequired,nativeRequired:pluginRequired}=require('./assets-contract.cjs');
const {required:packageRequired}=require('./package-contract.cjs');
const {required:executionRequired,browserRequired:finderBrowserRequired}=require('./execution-plugin-contract.cjs');
const {required:migrationRequired}=require('./migration-pipeline-contract.cjs');
const {required:schedulerRequired}=require('./scheduler-contract.cjs');
const {required:resetRequired}=require('./reset-contract.cjs');
const {required:resetBrowserRequired}=require('./reset-browser-contract.cjs');
const {required:deliveryRequired}=require('./package-delivery-contract.cjs');

const root = fileURLToPath(new URL('../../', import.meta.url));
const suite = process.env.JL_ACCEPTANCE_SUITE || 'ownership';
if (!['ownership','sql-import','ical','atomicity','history','audit','families','assets','package','delivery','plugins','migration','lineup','eventranking','rival-order','final-score','dashboard-scope','referee-scope','position-import'].includes(suite)) throw new Error('Unknown acceptance suite.');
if(suite==='delivery'&&!process.env.JL_LEGACY_PACKAGE_ZIP)throw new Error('Actual legacy package ZIP required for delivery tests');
if(suite==='migration'&&(!process.env.JL_MIGRATION_ZIP||!path.isAbsolute(process.env.JL_MIGRATION_ZIP)||!fs.statSync(process.env.JL_MIGRATION_ZIP).isFile()))throw new Error('Absolute migration ZIP required');
const id = Date.now().toString(36);
const network = `jl-r1-${id}`;
const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'jl-r1-'));
const artifacts = path.join(root, 'tests/artifacts', `${suite}-${id}`);
fs.mkdirSync(artifacts, {recursive: true});
const cmsArchive=process.env.JL_CMS_ZIP||'/mnt/disk-a/docker/local-joomla/packages/6.2.0-rc2.zip';
if(!path.isAbsolute(cmsArchive)||!fs.statSync(cmsArchive).isFile())throw new Error('Existing absolute Joomla archive required');
fs.writeFileSync(path.join(artifacts,'environment.json'),JSON.stringify({suite,selectorsOnly:process.env.JL_SELECTORS_ONLY==='1',cmsArchive,sha256:createHash('sha256').update(fs.readFileSync(cmsArchive)).digest('hex')},null,2));
const password = randomBytes(24).toString('hex');
const created = [];
const run = (command, args, options = {}) => {
    try { return execFileSync(command, args, {encoding: 'utf8', maxBuffer: 16 * 1024 * 1024, ...options}); }
    catch (error) { throw new Error(`${command} failed: ${error.stdout ?? ''}\n${error.stderr ?? ''}`); }
};
const docker = args => run('docker', args);
const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
const removeTestConfig = app => docker(['exec',app,'php','-r',"$file='/var/www/html/configuration.php'; if (is_file($file) && !unlink($file)) exit(1);"]);
let networkCreated = false;
try {
    docker(['network', 'create', network]);
    networkCreated = true;
    const candidate = path.join(temp, 'candidate');
    fs.mkdirSync(candidate);
    if(!process.env.JL_CANDIDATE_COMPONENT_ZIP) {
    for (const [source, destination] of [['administrator/components/com_joomleague','admin'], ['components/com_joomleague','site'], ['media/com_joomleague','media']]) {
        fs.cpSync(path.join(root, source), path.join(candidate, destination), {recursive: true});
    }
    for (const name of ['joomleague.xml','script.php']) fs.copyFileSync(path.join(candidate,'admin',name),path.join(candidate,name));
    }
    if(process.env.JL_CANDIDATE_COMPONENT_ZIP){
        const archive=process.env.JL_CANDIDATE_COMPONENT_ZIP;
        if(!path.isAbsolute(archive)||!fs.statSync(archive).isFile())throw new Error('Existing absolute component archive required');
        const members=run('unzip',['-Z1',archive]).trim().split('\n');
        if(members.some(name=>name.startsWith('/')||name.includes('\\')||name.split('/').includes('..')))throw new Error('Unsafe component archive path');
        run('unzip',['-oq',archive,'-d',candidate]);
        fs.writeFileSync(path.join(artifacts,'candidate-identity.json'),JSON.stringify({archive,sha256:createHash('sha256').update(fs.readFileSync(archive)).digest('hex')}));
    }
    if(['audit','eventranking'].includes(suite))fs.cpSync(path.join(root,'modules'),path.join(candidate,'modules'),{recursive:true});
    if(['audit','assets','plugins'].includes(suite))fs.cpSync(path.join(root,'plugins'),path.join(candidate,'plugins'),{recursive:true});
    const packageStage=path.join(temp,'suite'),legacyPackageStage=path.join(temp,'legacy-suite');
    if(['package','delivery'].includes(suite))for(const [archive,destination]of [[process.env.JL_PACKAGE_ZIP,packageStage],...(process.env.JL_LEGACY_PACKAGE_ZIP?[[process.env.JL_LEGACY_PACKAGE_ZIP,legacyPackageStage]]:[])]){
        if(!archive||!path.isAbsolute(archive)||!fs.statSync(archive).isFile())throw new Error('Existing absolute package archive required');
        const members=run('unzip',['-Z1',archive]).trim().split('\n');
        if(members.some(name=>name.startsWith('/')||name.includes('\\')||name.split('/').includes('..')))throw new Error('Unsafe package path');
        fs.mkdirSync(destination);run('unzip',['-q',archive,'-d',destination]);
    }
    const feedRoot=path.join(temp,'feed'),feedApp=network+'-feed',feedUrl=`http://${feedApp}:8080/update.xml`;
    if(suite==='delivery'){
        fs.mkdirSync(feedRoot);
        docker(['run','-d','--rm','--name',feedApp,'--network',network,'--memory','256m','--cpus','1','-v',`${feedRoot}:/opt/feed:ro`,'--entrypoint','php','klucon/joomla-local-runtime:php85-20261021','-S','0.0.0.0:8080','-t','/opt/feed']);
        created.push(feedApp);
    }
    const legacy = path.join(temp,'legacy');
    if (suite === 'sql-import') {
        fs.mkdirSync(legacy);
        run('unzip',['-q',path.join(root,'dist/com_joomleague-6.2.0-beta6.zip'),'-d',legacy]);
    }
    for (const driver of ['mysqli', 'pgsql']) {
        const name = `${network}-${driver}`;
        const app = name + '-app';
        const db = name + '-db';
        const env = path.join(temp, driver + '.env');
        fs.writeFileSync(env, driver === 'mysqli'
            ? `MARIADB_ROOT_PASSWORD=${password}\nMARIADB_ROOT_HOST=%\nMARIADB_DATABASE=r1_test\n`
            : `POSTGRES_PASSWORD=${password}\nPOSTGRES_DB=r1_test\n`, {mode: 0o600});
        docker(['run','-d','--rm','--name',db,'--network',network,'--memory','768m','--cpus','2','--tmpfs',driver === 'mysqli' ? '/var/lib/mysql' : '/var/lib/postgresql','--env-file',env,driver === 'mysqli' ? 'mariadb:11.4' : 'postgres:18-alpine']);
        created.push(db);
        let ready = false;
        for (let attempt = 0; attempt < 90; attempt++) {
            try { docker(driver === 'mysqli' ? ['exec',db,'healthcheck.sh','--connect','--innodb_initialized'] : ['exec',db,'pg_isready','-U','postgres','-d','r1_test']); ready=true; break; }
            catch { await pause(1000); }
        }
        if (!ready) throw new Error('Disposable database did not become ready.');
        const web = path.join(temp,driver);
        fs.mkdirSync(web);
        const cmsMembers=run('unzip',['-Z1',cmsArchive]).trim().split('\n');
        if(cmsMembers.some(name=>name.startsWith('/')||name.includes('\\')||name.split('/').includes('..')))throw new Error('Unsafe Joomla archive path');
        run('unzip',['-q',cmsArchive,'-d',web]);
        docker(['run','-d','--rm','--name',app,'--network',network,'--memory','768m','--cpus','2',
            '-p','127.0.0.1::80','-v',`${web}:/var/www/html`,'-v',`${candidate}:/opt/candidate:ro`,
            ...(suite === 'sql-import' ? ['-v',`${legacy}:/opt/legacy:ro`] : []),
            ...(suite === 'migration' ? ['-v',`${process.env.JL_MIGRATION_ZIP}:/opt/migration.zip:ro`] : []),
            ...(suite === 'migration' ? ['-v',`${path.join(root,'tools')}:/opt/maintenance:ro`] : []),
            ...(suite === 'migration' ? ['-v',`${path.join(root,'tests/Fixtures')}:/opt/r1-fixtures:ro`] : []),
            ...(suite === 'position-import' ? ['-v',`${process.env.JL_IMPORT_CATALOGUE_SQL}:/opt/position-catalogue.sql:ro`] : []),
            ...(['package','delivery'].includes(suite) ? ['-v',`${packageStage}:/opt/suite:ro`,...(process.env.JL_LEGACY_PACKAGE_ZIP?['-v',`${legacyPackageStage}:/opt/legacy-suite:ro`]:[])] : []),
            ...(suite === 'history' ? ['-v',`${path.join(root,'tests/Fixtures/history-memory.ini')}:/usr/local/etc/php/conf.d/zz-history-memory.ini:ro`] : []),
            '-v',`${path.join(root,'tests/Integration')}:/opt/r1-tests:ro`,
            '-e',`JL_R1_DB_HOST=${db}`,'-e',`JL_R1_DRIVER=${driver}`,'-e',`JL_R1_DB_USER=${driver==='mysqli'?'root':'postgres'}`,
            '-e',`JL_R1_PASSWORD=${password}`,'klucon/joomla-local-runtime:php85-20261021']);
        created.push(app);
        console.log(docker(['exec',app,'php','/opt/r1-tests/ownership-install.php']));
        if (suite === 'sql-import') {
            console.log(docker(['exec',app,'php','/opt/r1-tests/ownership-install.php','/opt/legacy']));
            console.log(docker(['exec',app,'php','/opt/r1-tests/sql-import-acceptance.php','seed-upgrade']));
        }
        if(['package','delivery'].includes(suite))docker(['exec',app,'chown','-R','www-data:www-data','/var/www/html']);
        console.log(docker(['exec',...(['package','delivery'].includes(suite)?['-u','www-data']:[]),app,'php','/opt/r1-tests/ownership-install.php',...(['package','delivery'].includes(suite)?[process.env.JL_LEGACY_PACKAGE_ZIP?'/opt/legacy-suite':'/opt/suite']:[])]));
        if(suite==='package'&&process.env.JL_LEGACY_PACKAGE_ZIP){
            console.log(docker(['exec',app,'php','/opt/r1-tests/legacy-language-preservation.php','seed']));
            console.log(docker(['exec','-u','www-data',app,'php','/opt/r1-tests/ownership-install.php','/opt/suite']));
            const preserved=docker(['exec',app,'php','/opt/r1-tests/legacy-language-preservation.php','verify']);
            fs.writeFileSync(path.join(artifacts,driver+'-legacy-language-preservation.txt'),preserved);console.log(preserved);
        }
        if (suite === 'sql-import') console.log(docker(['exec',app,'php','/opt/r1-tests/database-schema-contract.php']));
        if(['audit','eventranking'].includes(suite))for(const mod of fs.readdirSync(path.join(candidate,'modules')).filter(n=>n.startsWith('mod_joomleague_')&&(suite!=='eventranking'||n==='mod_joomleague_eventranking'))) console.log(docker(['exec',app,'php','/opt/r1-tests/ownership-install.php','/opt/candidate/modules/'+mod]));
        if(['audit','assets','plugins'].includes(suite))for(const group of ['content','quickicon','finder','console','task']) console.log(docker(['exec',app,'php','/opt/r1-tests/ownership-install.php','/opt/candidate/plugins/'+group+'/joomleague']));
        docker(['exec',app,'chown','-R','www-data:www-data','/var/www/html']);
        const fixture = ['migration','position-import'].includes(suite)?{}:JSON.parse(docker(['exec',app,'php','/opt/r1-tests/ownership-workflows.php','setup']));
        if (suite === 'sql-import') console.log(docker(['exec',app,'php','/opt/r1-tests/sql-import-acceptance.php','setup']));
        let modelResults;
        if(suite==='position-import') {
            modelResults=docker(['exec',app,'php','/opt/r1-tests/position-import-acceptance.php']);
            const report=JSON.parse(docker(['exec',app,'cat','/tmp/jl-position-import.json']));
            if(!report.passed||report.total!==29||report.custom!==3||report.profile!==26)throw new Error('Position import coverage incomplete');
            fs.writeFileSync(path.join(artifacts,driver+'-position-native.json'),JSON.stringify(report,null,2));
        } else if(suite==='referee-scope') {
            modelResults=docker(['exec',app,'php','/opt/r1-tests/referee-scope-acceptance.php']);
            modelResults+=docker(['exec',app,'php','/opt/r1-tests/match-officials.php']);
            const report=JSON.parse(docker(['exec',app,'cat','/tmp/jl-referee-scope.json']));
            if(!report.passed||report.checks.length!==16)throw new Error('Incomplete referee scope acceptance');
            fs.writeFileSync(path.join(artifacts,driver+'-referee-native.json'),JSON.stringify(report,null,2));
        } else if(suite==='dashboard-scope') {
            modelResults=docker(['exec',app,'php','/opt/r1-tests/dashboard-scope-acceptance.php']);
            if(!modelResults.includes('dashboard scope 23 assertions.'))throw new Error('Incomplete dashboard scope acceptance');
        } else if(suite==='rival-order') {
            modelResults=docker(['exec',app,'php','/opt/r1-tests/rival-order-acceptance.php']);
            if(!modelResults.includes('8 mandatory assertions.'))throw new Error('Incomplete rival order acceptance');
            const rivalScope=JSON.parse(docker(['exec',app,'cat','/tmp/jl-rival-scope-native.json']));
            if(!rivalScope.passed || rivalScope.checks.length<25)throw new Error('Incomplete rival scope acceptance');
            fs.writeFileSync(path.join(artifacts,driver+'-rival-scope-native.json'),JSON.stringify(rivalScope,null,2));
        } else if(suite==='eventranking') {
            modelResults=docker(['exec',app,'php','/opt/r1-tests/eventranking-acceptance.php']);
            if(!modelResults.includes('33 mandatory assertions.'))throw new Error('Incomplete event ranking acceptance');
        } else if(suite==='migration') {
            modelResults=docker(['exec',app,'php','/opt/r1-tests/migration-pipeline-acceptance.php']);
            const report=JSON.parse(docker(['exec',app,'cat','/tmp/jl-migration-report.json']));
            fs.writeFileSync(path.join(artifacts,driver+'-migration.json'),JSON.stringify(report,null,2));
            const extendedRequired=[2,3,4].flatMap(id=>['extended-root-'+id,'extended-winner-'+id,'extended-native-validation-'+id,'repair-preview-'+id,'repair-segment-reference-'+id,'repair-manual-conflict-'+id,'repair-preserve-edit-'+id,'repair-overwrite-preview-'+id,'repair-overwrite-preserves-notes-'+id,'repair-final-'+id,'repair-idempotent-'+id]).concat(['extended-programme','extended-standings-goals','repair-reimport-preserved','repair-installer-update','repair-installer-idempotent']);
            if(process.env.JL_EXTENDED_RESULT_FIXTURE==='1'&&!report.extendedResults)throw new Error('Extended result fixture missing');
            if(process.env.JL_CONFLICT_RESULT_FIXTURE==='1'&&!report.conflictResults)throw new Error('Conflict result fixture missing');
            if(report.extendedResults){
                const reviewed=JSON.parse(fs.readFileSync(path.join(root,'tests/Fixtures/legacy-football-reviewed.json'),'utf8'));
                if(reviewed.length!==18)throw new Error('Reviewed archive regression fixture incomplete');
                extendedRequired.push(...reviewed.map(row=>'reviewed-roundtrip-'+row.source.id));
            }
            if(report.conflictResults)extendedRequired.push('incomplete-import-isolated','awarded-import','incomplete-update-quarantined','incomplete-excluded-from-public-final','incomplete-excluded-from-standings','incomplete-update-idempotent');
            if(!summarize(report.observations,migrationRequired.concat(report.extendedResults?extendedRequired:[])).passed)throw new Error('Migration acceptance failed');
            if(report.extendedResults){
                const command=['exec',app,'php','/opt/maintenance/repair-legacy-football-result.php','--root=/var/www/html','--actor='+report.actorId,'--match=2'];
                const preview=JSON.parse(docker(command));
                if(preview.status!=='already_repaired')throw new Error('Maintenance CLI preview failed');
                const rejected=spawnSync('docker',[...command,'--apply','--revision='+preview.revision],{encoding:'utf8'});
                if(rejected.status!==1||!rejected.stderr.includes('backup'))throw new Error('Maintenance CLI did not require backup');
                fs.writeFileSync(path.join(artifacts,driver+'-repair-cli.json'),JSON.stringify({preview:preview.status,missingBackupRejected:true}));
            }
            console.log(docker(['exec',app,'php','/opt/r1-tests/database-schema-contract.php']));
        } else if(suite==='plugins') {
            modelResults=run('docker',['exec',app,'php','/opt/r1-tests/scheduler-lifecycle.php'],{timeout:60000});
            const schedulerReport=JSON.parse(docker(['exec',app,'cat','/tmp/jl-scheduler-report.json']));
            fs.writeFileSync(path.join(artifacts,driver+'-scheduler.json'),JSON.stringify(schedulerReport,null,2));
            if(!Array.isArray(schedulerReport.observations)||!summarize(schedulerReport.observations,schedulerRequired).passed)throw new Error('Scheduler lifecycle coverage incomplete');
            const report=JSON.parse(docker(['exec',app,'cat','/tmp/jl-execution-report.json']));
            const counts=()=>docker(['exec',app,'php','/opt/r1-tests/execution-plugins.php','snapshot']);
            const before=counts();
            for(const [id,args,expected,text]of [
                ['console-help',['help','joomleague:reset-demo-data'],0,'--force'],
                ['console-disabled',['joomleague:reset-demo-data','--force'],1,'Reset is disabled'],
                ['console-force-required',['joomleague:reset-demo-data'],1,'--force option is required']
            ]) {
                if(id==='console-force-required')docker(['exec',app,'php','/opt/r1-tests/execution-plugins.php','enable-reset']);
                const result=spawnSync('docker',['exec',app,'php','cli/joomla.php',...args],{encoding:'utf8',timeout:30000});
                fs.writeFileSync(path.join(artifacts,driver+'-'+id+'.txt'),(result.stdout||'')+(result.stderr||''));
                report.observations.push({id,status:200,outcome:!result.error&&result.status===expected&&((result.stdout||'')+(result.stderr||'')).includes(text)?'PASS':'FAIL',evidenceCount:1});
            }
            report.observations.push({id:'console-data-preserved',status:200,outcome:before===counts()?'PASS':'FAIL',evidenceCount:1});
            fs.writeFileSync(path.join(artifacts,driver+'-execution.json'),JSON.stringify(report,null,2));
            if(!summarize(report.observations,executionRequired).passed)throw new Error('Execution plugin coverage failed');
        } else if(suite==='package') {
            console.log(docker(['exec','-u','www-data',app,'php','/opt/r1-tests/package-acceptance.php','seed']));
            console.log(docker(['exec','-u','www-data',app,'php','/opt/r1-tests/ownership-install.php','/opt/suite']));
            modelResults=docker(['exec','-u','www-data',app,'php','/opt/r1-tests/package-acceptance.php','verify']);
            const report=JSON.parse(docker(['exec',app,'cat','/tmp/jl-package-report.json']));
            fs.writeFileSync(path.join(artifacts,driver+'-package.json'),JSON.stringify(report,null,2));
            if(!Array.isArray(report.observations)||!summarize(report.observations,packageRequired).passed)throw new Error('Package acceptance incomplete');
            console.log(docker(['exec','-u','www-data',app,'php','/opt/r1-tests/database-schema-contract.php']));
        } else if(suite==='audit') {
            const processResult=spawnSync('docker',['exec',app,'php','/opt/r1-tests/audit-coverage-acceptance.php'],{encoding:'utf8',timeout:180000,maxBuffer:8*1024*1024});
            fs.writeFileSync(path.join(artifacts,driver+'-native-output.txt'),String(processResult.stdout||'')+'\n'+String(processResult.stderr||''));
            let report=null;try{report=JSON.parse(docker(['exec',app,'cat','/tmp/jl-r6-native.json']));}catch{}
            fs.writeFileSync(path.join(artifacts,driver+'-native.json'),JSON.stringify(report,null,2));
            const outcome=evaluateProcess(processResult,report,auditRequired);
            if(!outcome.passed)throw new Error('Native audit rejected: '+outcome.reasons.join(',')+'; report: '+path.join(artifacts,driver+'-native.json'));
            modelResults=processResult.stdout;
        } else if(['families','assets','lineup','delivery','final-score'].includes(suite)) modelResults='PASS: disposable setup; mandatory browser assertions required.\n';
        else modelResults=docker(['exec',app,'php',suite === 'history' ? '/opt/r1-tests/club-history-acceptance.php' : suite === 'atomicity' ? '/opt/r1-tests/match-atomicity-acceptance.php' : suite === 'ical' ? '/opt/r1-tests/ical-acceptance.php' : suite === 'ownership' ? '/opt/r1-tests/ownership-workflows.php' : '/opt/r1-tests/sql-import-acceptance.php',suite === 'ownership' ? 'models' : 'verify']);
        if(suite==='ownership') {
            modelResults+=docker(['exec',app,'php','/opt/r1-tests/person-context-acceptance.php']);
            const selectors=JSON.parse(docker(['exec',app,'cat','/tmp/jl-member-selector-native.json']));
            if(!selectors.passed || selectors.checks.length<14)throw new Error('Incomplete member selector acceptance');
            fs.writeFileSync(path.join(artifacts,driver+'-member-selector-native.json'),JSON.stringify(selectors,null,2));
        }
        console.log(modelResults);
        fs.writeFileSync(path.join(artifacts,driver+'-models.txt'),modelResults);
        const address = docker(['port',app,'80/tcp']).trim();
        if(suite==='position-import')run('node',[path.join(root,'tests/Browser/verify-imported-position-catalogue.cjs')],{env:{...process.env,JL_R1_BASE:`http://${address}`,JL_R1_PASSWORD:password,JL_R1_ARTIFACTS:artifacts,JL_R1_DRIVER:driver},stdio:'inherit',timeout:180000});
        if(suite==='referee-scope'){
            const report=JSON.parse(docker(['exec',app,'cat','/tmp/jl-referee-scope.json']));
            run('node',[path.join(root,'tests/Browser/verify-referee-team-scope.cjs')],{env:{...process.env,JL_R1_BASE:`http://${address}`,JL_R1_ARTIFACTS:artifacts,JL_R1_DRIVER:driver,JL_REFEREE_FIXTURE:JSON.stringify(report.fixture)},stdio:'inherit',timeout:180000});
        }
        if(suite==='migration'&&process.env.JL_EXTENDED_RESULT_FIXTURE==='1')run('node',[path.join(root,'tests/Browser/verify-migrated-extended-results.cjs')],{env:{...process.env,JL_R1_BASE:`http://${address}`,JL_R1_ARTIFACTS:artifacts,JL_R1_DRIVER:driver},stdio:'inherit',timeout:180000});
        if(suite==='dashboard-scope'){
            const dashboardFixture=JSON.parse(docker(['exec',app,'cat','/tmp/jl-dashboard-scope.json']));
            run('node',[path.join(root,'tests/Browser/verify-dashboard-scope.cjs')],{env:{...process.env,JL_R1_BASE:`http://${address}`,JL_R1_APP:app,JL_R1_PASSWORD:password,JL_R1_ARTIFACTS:artifacts,JL_R1_DRIVER:driver,JL_DASHBOARD_FIXTURE:JSON.stringify(dashboardFixture)},stdio:'inherit',timeout:180000});
        }
        if(suite==='final-score'){
            run('node',[path.join(root,'tests/Browser/verify-final-score-only.cjs')],{env:{...process.env,JL_R1_BASE:`http://${address}`,JL_R1_APP:app,JL_R1_PASSWORD:password,JL_R1_FIXTURE:JSON.stringify(fixture),JL_R1_ARTIFACTS:artifacts,JL_R1_DRIVER:driver},stdio:'inherit',timeout:180000});
        }
        if(suite==='rival-order'){
            run('node',[path.join(root,'tests/Browser/verify-rival-order.cjs')],{env:{...process.env,JL_R1_BASE:`http://${address}`,JL_R1_APP:app,JL_R1_PASSWORD:password,JL_R1_ARTIFACTS:artifacts,JL_R1_DRIVER:driver},stdio:'inherit',timeout:180000});
        }
        if(suite==='eventranking'){
            const moduleFixture=JSON.parse(docker(['exec',app,'cat','/tmp/jl-eventranking-browser.json']));
            run('node',[path.join(root,'tests/Browser/verify-eventranking-settings.cjs')],{env:{...process.env,JL_R1_BASE:`http://${address}`,JL_R1_APP:app,JL_R1_PASSWORD:password,JL_R1_ARTIFACTS:artifacts,JL_R1_DRIVER:driver,JL_EVENTRANKING_MODULE:String(moduleFixture.module),JL_EVENTRANKING_STAGE:String(moduleFixture.stage),JL_EVENTRANKING_PERSON:String(moduleFixture.person)},stdio:'inherit',timeout:180000});
        }
        if(suite==='delivery'){
            run('node',[path.join(root,'tests/Browser/verify-package-delivery.cjs')],{env:{...process.env,JL_R1_BASE:`http://${address}`,JL_R1_APP:app,JL_R1_PASSWORD:password,JL_R1_ARTIFACTS:artifacts,JL_R1_DRIVER:driver,JL_R1_FEED_ROOT:feedRoot,JL_R1_FEED_APP:feedApp,JL_R1_FEED_URL:feedUrl},stdio:'inherit',timeout:300000});
            const report=JSON.parse(fs.readFileSync(path.join(artifacts,driver+'-delivery-http.json'),'utf8'));
            if(!summarize(report.observations,deliveryRequired).passed)throw new Error('Package browser/update delivery incomplete');
            const packageReport=JSON.parse(docker(['exec',app,'cat','/tmp/jl-package-report.json']));
            fs.writeFileSync(path.join(artifacts,driver+'-package.json'),JSON.stringify(packageReport,null,2));
            if(!summarize(packageReport.observations,packageRequired).passed)throw new Error('Browser reinstall preservation incomplete');
            console.log(docker(['exec','-u','www-data',app,'php','/opt/r1-tests/database-schema-contract.php']));
            const feedLog=spawnSync('docker',['logs',feedApp],{encoding:'utf8',timeout:10000,maxBuffer:8*1024*1024});
            if(feedLog.status!==0)throw new Error('Cannot retain update feed request evidence');
            fs.writeFileSync(path.join(artifacts,driver+'-feed-log.txt'),(feedLog.stdout||'')+(feedLog.stderr||''));
        }
        if(suite==='plugins'){
            run('node',[path.join(root,'tests/Browser/verify-finder-search.cjs')],{env:{...process.env,JL_R1_BASE:`http://${address}`,JL_R1_APP:app,JL_R1_PASSWORD:password,JL_R1_ARTIFACTS:artifacts,JL_R1_DRIVER:driver},stdio:'inherit',timeout:120000});
            const finderReport=JSON.parse(fs.readFileSync(path.join(artifacts,driver+'-finder-http.json'),'utf8'));
            if(!Array.isArray(finderReport.observations)||!summarize(finderReport.observations,finderBrowserRequired).passed)throw new Error('Finder browser coverage incomplete.');
            const resetCommands=[];
            let resetReport;
            try {
                console.log(run('docker',['exec',app,'php','/opt/r1-tests/reset-acceptance.php','seed'],{timeout:30000}));
                for(const [caseId,verification,expected,message] of [
                    ['reset-external-reference-cli','verify-blocked',1,'Reset refused: another table references'],
                    ['reset-positive-cli','verify',0,'runtime tables reset.'],
                    ['reset-repeat-cli','verify-final',0,'runtime tables reset.']
                ]){
                    const result=spawnSync('docker',['exec',app,'php','cli/joomla.php','joomleague:reset-demo-data','--force'],{encoding:'utf8',timeout:60000});
                    const output=(result.stdout||'')+(result.stderr||'');
                    fs.writeFileSync(path.join(artifacts,driver+'-'+caseId+'.txt'),output);
                    resetCommands.push({id:caseId,status:200,outcome:!result.error&&result.status===expected&&output.includes(message)?'PASS':'FAIL',evidenceCount:1});
                    if(resetCommands.at(-1).outcome!=='PASS')throw new Error('Reset command failed: '+caseId);
                    console.log(run('docker',['exec',app,'php','/opt/r1-tests/reset-acceptance.php',verification],{timeout:180000}));
                }
            } finally {
                try {
                    resetReport=JSON.parse(docker(['exec',app,'cat','/tmp/jl-reset-report.json']));
                    resetReport.observations.push(...resetCommands);
                    fs.writeFileSync(path.join(artifacts,driver+'-reset.json'),JSON.stringify(resetReport,null,2));
                } catch {
                    fs.writeFileSync(path.join(artifacts,driver+'-reset-incomplete.json'),JSON.stringify({observations:resetCommands,incomplete:true},null,2));
                }
            }
            if(!resetReport||!summarize(resetReport.observations,resetRequired).passed)throw new Error('Positive reset coverage incomplete');
            run('node',[path.join(root,'tests/Browser/verify-reset-security.cjs')],{env:{...process.env,JL_R1_BASE:`http://${address}`,JL_R1_APP:app,JL_R1_PASSWORD:password,JL_R1_ARTIFACTS:artifacts,JL_R1_DRIVER:driver},stdio:'inherit',timeout:180000});
            const resetBrowserReport=JSON.parse(fs.readFileSync(path.join(artifacts,driver+'-reset-http.json'),'utf8'));
            if(!summarize(resetBrowserReport.observations,resetBrowserRequired).passed)throw new Error('Browser reset security coverage incomplete');
        }
        if (suite === 'sql-import') {
            console.log(docker(['exec',app,'php','/opt/r1-tests/ownership-install.php']));
            console.log(docker(['exec',app,'php','/opt/r1-tests/sql-import-acceptance.php','verify-update']));
        }
        if(!['package','delivery','plugins','migration','eventranking','rival-order','final-score','dashboard-scope','referee-scope','position-import'].includes(suite))run('node',[path.join(root,'tests/Browser',suite === 'ownership' && process.env.JL_SELECTORS_ONLY==='1' ? 'verify-member-selectors-only.cjs' : suite === 'lineup' ? 'verify-lineup-actions.cjs' : suite === 'assets' ? 'verify-admin-assets.cjs' : suite === 'families' ? 'verify-result-families.cjs' : suite === 'audit' ? 'verify-audit-coverage.cjs' : suite === 'history' ? 'verify-club-history.cjs' : suite === 'atomicity' ? 'verify-match-atomicity.cjs' : suite === 'ical' ? 'verify-ical.cjs' : suite === 'ownership' ? 'verify-project-ownership.cjs' : 'verify-sql-import.cjs')], {env: {...process.env,
            JL_R1_BASE:`http://${address}`,JL_R1_APP:app,JL_R1_PASSWORD:password,
            JL_R1_FIXTURE:JSON.stringify(fixture),JL_R1_ARTIFACTS:artifacts,JL_R1_DRIVER:driver},stdio:'inherit',...(suite==='audit'?{timeout:180000}:{})});
        if(suite==='audit') {
            const browserReport=JSON.parse(fs.readFileSync(path.join(artifacts,driver+'-http.json'),'utf8'));
            if(!Array.isArray(browserReport.observations)||!summarize(browserReport.observations,browserRequired).passed)throw new Error('Browser audit has incomplete/failed mandatory coverage.');
            run('node',[path.join(root,'tests/Browser/verify-football-result.cjs')],{env:{...process.env,
                JL_R1_BASE:`http://${address}`,JL_R1_APP:app,JL_R1_PASSWORD:password,
                JL_R1_FIXTURE:JSON.stringify(fixture),JL_R1_ARTIFACTS:artifacts,JL_R1_DRIVER:driver},stdio:'inherit',timeout:180000});
            const footballReport=JSON.parse(fs.readFileSync(path.join(artifacts,driver+'-football-http.json'),'utf8'));
            if(!Array.isArray(footballReport.observations)||!summarize(footballReport.observations,footballRequired).passed)throw new Error('Football result workflow coverage incomplete.');
            run('node',[path.join(root,'tests/Browser/verify-result-families.cjs')],{env:{...process.env,
                JL_R1_BASE:`http://${address}`,JL_R1_APP:app,JL_R1_PASSWORD:password,
                JL_R1_ARTIFACTS:artifacts,JL_R1_DRIVER:driver},stdio:'inherit',timeout:180000});
            const familyReport=JSON.parse(fs.readFileSync(path.join(artifacts,driver+'-families-http.json'),'utf8'));
            if(!Array.isArray(familyReport.observations)||!summarize(familyReport.observations,familyRequired).passed)throw new Error('Result family browser coverage incomplete.');
        }
        if(suite==='families') {
            const familyReport=JSON.parse(fs.readFileSync(path.join(artifacts,driver+'-families-http.json'),'utf8'));
            if(!Array.isArray(familyReport.observations)||!summarize(familyReport.observations,familyRequired).passed)throw new Error('Result family browser coverage incomplete.');
        }
        if(['assets','audit'].includes(suite)) {
            console.log(docker(['exec',app,'php','/opt/r1-tests/audit-content-plugin.php']));
            const pluginReport=JSON.parse(docker(['exec',app,'cat','/tmp/jl-plugin-report.json']));
            fs.writeFileSync(path.join(artifacts,driver+'-plugins-native.json'),JSON.stringify(pluginReport,null,2));
            if(!Array.isArray(pluginReport.observations)||!summarize(pluginReport.observations,pluginRequired).passed)throw new Error('Plugin coverage incomplete.');
            if(suite==='audit')run('node',[path.join(root,'tests/Browser/verify-admin-assets.cjs')],{env:{...process.env,JL_R1_BASE:`http://${address}`,JL_R1_APP:app,JL_R1_PASSWORD:password,JL_R1_FIXTURE:JSON.stringify(fixture),JL_R1_ARTIFACTS:artifacts,JL_R1_DRIVER:driver},stdio:'inherit',timeout:180000});
            const assetReport=JSON.parse(fs.readFileSync(path.join(artifacts,driver+'-assets-http.json'),'utf8'));
            if(!Array.isArray(assetReport.observations)||!summarize(assetReport.observations,assetRequired).passed)throw new Error('Asset browser coverage incomplete.');
        }
        fs.writeFileSync(path.join(artifacts,driver+'-result.txt'), suite==='package'?'PASS: full package install/update, preserved data, file overwrite and schema checks.\n':`PASS: fresh installation, real scoped identities, native and HTTP ${suite} workflows.\n`);
        console.log(`PASS: ${driver} ${suite} acceptance.`);
        removeTestConfig(app);
        docker(['stop','-t','3',app]); created.splice(created.indexOf(app),1);
        docker(['stop','-t','3',db]); created.splice(created.indexOf(db),1);
    }
    console.log(`Evidence: ${artifacts}`);
} finally {
    for (const name of created.reverse()) {
        if (name.endsWith('-app')) { try { removeTestConfig(name); } catch {} }
        try { docker(['stop','-t','3',name]); } catch {}
    }
    if (networkCreated) { try { docker(['network','rm',network]); } catch {} }
    // Retain disposable webroots for diagnosis, but remove generated password files.
    for (const driver of ['mysqli','pgsql']) {
        for (const file of [path.join(temp,driver+'.env'),path.join(temp,driver,'configuration.php')]) {
            try { fs.unlinkSync(file); } catch {}
        }
    }
}
