import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';
import {fileURLToPath} from 'node:url';
import {execFileSync,spawnSync} from 'node:child_process';
import {randomBytes} from 'node:crypto';
import {createRequire} from 'node:module';
const require=createRequire(import.meta.url);
const {evaluateProcess}=require('./audit-process.cjs');
const {required:auditRequired,browserRequired}=require('./audit-contract.cjs');
const {summarize}=require('../Browser/audit-result.cjs');
const {required:footballRequired}=require('./football-result-contract.cjs');
const {required:familyRequired}=require('./family-browser-contract.cjs');
const {required:assetRequired,nativeRequired:pluginRequired}=require('./assets-contract.cjs');
const {required:packageRequired}=require('./package-contract.cjs');
const {required:executionRequired}=require('./execution-plugin-contract.cjs');

const root = fileURLToPath(new URL('../../', import.meta.url));
const suite = process.env.JL_ACCEPTANCE_SUITE || 'ownership';
if (!['ownership','sql-import','ical','atomicity','history','audit','families','assets','package','plugins'].includes(suite)) throw new Error('Unknown acceptance suite.');
const id = Date.now().toString(36);
const network = `jl-r1-${id}`;
const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'jl-r1-'));
const artifacts = path.join(root, 'tests/artifacts', `${suite}-${id}`);
fs.mkdirSync(artifacts, {recursive: true});
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
    for (const [source, destination] of [['administrator/components/com_joomleague','admin'], ['components/com_joomleague','site'], ['media/com_joomleague','media']]) {
        fs.cpSync(path.join(root, source), path.join(candidate, destination), {recursive: true});
    }
    for (const name of ['joomleague.xml','script.php']) fs.copyFileSync(path.join(candidate,'admin',name),path.join(candidate,name));
    if(suite==='audit')fs.cpSync(path.join(root,'modules'),path.join(candidate,'modules'),{recursive:true});
    if(['audit','assets','plugins'].includes(suite))fs.cpSync(path.join(root,'plugins'),path.join(candidate,'plugins'),{recursive:true});
    const packageStage=path.join(temp,'suite'),legacyPackageStage=path.join(temp,'legacy-suite');
    if(suite==='package')for(const [archive,destination]of [[process.env.JL_PACKAGE_ZIP,packageStage],...(process.env.JL_LEGACY_PACKAGE_ZIP?[[process.env.JL_LEGACY_PACKAGE_ZIP,legacyPackageStage]]:[])]){
        if(!archive||!path.isAbsolute(archive)||!fs.statSync(archive).isFile())throw new Error('Existing absolute package archive required');
        const members=run('unzip',['-Z1',archive]).trim().split('\n');
        if(members.some(name=>name.startsWith('/')||name.includes('\\')||name.split('/').includes('..')))throw new Error('Unsafe package path');
        fs.mkdirSync(destination);run('unzip',['-q',archive,'-d',destination]);
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
        run('unzip',['-q','/mnt/disk-a/docker/local-joomla/packages/6.2.0-rc2.zip','-d',web]);
        docker(['run','-d','--rm','--name',app,'--network',network,'--memory','768m','--cpus','2',
            '-p','127.0.0.1::80','-v',`${web}:/var/www/html`,'-v',`${candidate}:/opt/candidate:ro`,
            ...(suite === 'sql-import' ? ['-v',`${legacy}:/opt/legacy:ro`] : []),
            ...(suite === 'package' ? ['-v',`${packageStage}:/opt/suite:ro`,...(process.env.JL_LEGACY_PACKAGE_ZIP?['-v',`${legacyPackageStage}:/opt/legacy-suite:ro`]:[])] : []),
            ...(suite === 'history' ? ['-v',`${path.join(root,'tests/Fixtures/history-memory.ini')}:/usr/local/etc/php/conf.d/zz-history-memory.ini:ro`] : []),
            '-v',`${path.join(root,'tests/Integration')}:/opt/r1-tests:ro`,
            '-e',`JL_R1_DB_HOST=${db}`,'-e',`JL_R1_DRIVER=${driver}`,'-e',`JL_R1_DB_USER=${driver==='mysqli'?'root':'postgres'}`,
            '-e',`JL_R1_PASSWORD=${password}`,'klucon/joomla-local-runtime:php84-20261004']);
        created.push(app);
        console.log(docker(['exec',app,'php','/opt/r1-tests/ownership-install.php']));
        if (suite === 'sql-import') {
            console.log(docker(['exec',app,'php','/opt/r1-tests/ownership-install.php','/opt/legacy']));
            console.log(docker(['exec',app,'php','/opt/r1-tests/sql-import-acceptance.php','seed-upgrade']));
        }
        if(suite==='package')docker(['exec',app,'chown','-R','www-data:www-data','/var/www/html']);
        console.log(docker(['exec',...(suite==='package'?['-u','www-data']:[]),app,'php','/opt/r1-tests/ownership-install.php',...(suite==='package'?[process.env.JL_LEGACY_PACKAGE_ZIP?'/opt/legacy-suite':'/opt/suite']:[])]));
        if (suite === 'sql-import') console.log(docker(['exec',app,'php','/opt/r1-tests/database-schema-contract.php']));
        if(suite==='audit')for(const mod of fs.readdirSync(path.join(candidate,'modules')).filter(n=>n.startsWith('mod_joomleague_'))) console.log(docker(['exec',app,'php','/opt/r1-tests/ownership-install.php','/opt/candidate/modules/'+mod]));
        if(['audit','assets','plugins'].includes(suite))for(const group of ['content','quickicon','finder','console','task']) console.log(docker(['exec',app,'php','/opt/r1-tests/ownership-install.php','/opt/candidate/plugins/'+group+'/joomleague']));
        docker(['exec',app,'chown','-R','www-data:www-data','/var/www/html']);
        const fixture = JSON.parse(docker(['exec',app,'php','/opt/r1-tests/ownership-workflows.php','setup']));
        if (suite === 'sql-import') console.log(docker(['exec',app,'php','/opt/r1-tests/sql-import-acceptance.php','setup']));
        let modelResults;
        if(suite==='plugins') {
            modelResults=docker(['exec',app,'php','/opt/r1-tests/execution-plugins.php']);
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
            let report=null;try{report=JSON.parse(docker(['exec',app,'cat','/tmp/jl-r6-native.json']));}catch{}
            fs.writeFileSync(path.join(artifacts,driver+'-native.json'),JSON.stringify(report,null,2));
            const outcome=evaluateProcess(processResult,report,auditRequired);
            if(!outcome.passed)throw new Error('Native audit rejected: '+outcome.reasons.join(',')+'; report: '+path.join(artifacts,driver+'-native.json'));
            modelResults=processResult.stdout;
        } else if(['families','assets'].includes(suite)) modelResults='PASS: disposable setup; mandatory browser assertions required.\n';
        else modelResults=docker(['exec',app,'php',suite === 'history' ? '/opt/r1-tests/club-history-acceptance.php' : suite === 'atomicity' ? '/opt/r1-tests/match-atomicity-acceptance.php' : suite === 'ical' ? '/opt/r1-tests/ical-acceptance.php' : suite === 'ownership' ? '/opt/r1-tests/ownership-workflows.php' : '/opt/r1-tests/sql-import-acceptance.php',suite === 'ownership' ? 'models' : 'verify']);
        console.log(modelResults);
        fs.writeFileSync(path.join(artifacts,driver+'-models.txt'),modelResults);
        const address = docker(['port',app,'80/tcp']).trim();
        if (suite === 'sql-import') {
            console.log(docker(['exec',app,'php','/opt/r1-tests/ownership-install.php']));
            console.log(docker(['exec',app,'php','/opt/r1-tests/sql-import-acceptance.php','verify-update']));
        }
        if(!['package','plugins'].includes(suite))run('node',[path.join(root,'tests/Browser',suite === 'assets' ? 'verify-admin-assets.cjs' : suite === 'families' ? 'verify-result-families.cjs' : suite === 'audit' ? 'verify-audit-coverage.cjs' : suite === 'history' ? 'verify-club-history.cjs' : suite === 'atomicity' ? 'verify-match-atomicity.cjs' : suite === 'ical' ? 'verify-ical.cjs' : suite === 'ownership' ? 'verify-project-ownership.cjs' : 'verify-sql-import.cjs')], {env: {...process.env,
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
