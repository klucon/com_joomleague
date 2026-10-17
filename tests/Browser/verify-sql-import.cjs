const fs=require('node:fs');
const path=require('node:path');
const assert=require('node:assert/strict');
const {execFileSync}=require('node:child_process');
const {firefox}=require('/home/ondrej/.npm-global/lib/node_modules/playwright');

(async()=>{
    const base=process.env.JL_R1_BASE, app=process.env.JL_R1_APP;
    assert.match(base,/^http:\/\/127\.0\.0\.1:\d+$/); assert.match(app,/^jl-r1-/);
    const admin=base+'/administrator/index.php';
    const snapshot=()=>JSON.parse(execFileSync('docker',['exec',app,'php','/opt/r1-tests/sql-import-acceptance.php','snapshot'],{encoding:'utf8',maxBuffer:16*1024*1024}));
    const browser=await firefox.launch({executablePath:'/opt/playwright/firefox-1532/firefox/firefox',headless:true});
    const checks=[];
    try {
        const login=async letter=>{
            const context=await browser.newContext({viewport:{width:1440,height:1000}});
            const page=await context.newPage();
            await page.goto(base+'/administrator/',{waitUntil:'networkidle'});
            await page.locator('#mod-login-username').fill('r1.editor.'+letter);
            await page.locator('#mod-login-password').fill(process.env.JL_R1_PASSWORD);
            await Promise.all([page.waitForNavigation(),page.locator('#form-login button[type=submit]').click()]);
            assert.equal(await page.locator('#mod-login-username').count(),0,'Login failed.');
            return {context,page};
        };
        const token=async page=>page.evaluate(()=>Array.from(document.querySelectorAll('input[type=hidden]')).find(input=>/^[a-f0-9]{32}$/.test(input.name)&&input.value==='1')?.name);
        const upload=async(context,key,sql,name='import.sql')=>context.request.post(admin+'?option=com_joomleague&task=dataimport.import',{
            multipart:{...(key?{[key]:'1'}:{}),sql_file:{name,mimeType:'application/sql',buffer:Buffer.from(sql)}},maxRedirects:0,
        });
        const safe="INSERT INTO `#__joomleague_club` (`id`,`uuid`,`name`) VALUES (7101,'00000000-0000-4000-8000-000000007101','R2 HTTP import');";
        const a=await login('a');
        const fixture=JSON.parse(process.env.JL_R1_FIXTURE);
        await a.page.goto(admin+'?option=com_joomleague&view=stages&project_id='+fixture.projects.a.id,{waitUntil:'networkidle'});
        const aToken=await token(a.page); assert(aToken,'Non-importer token absent.');
        const before=snapshot();
        const denied=await upload(a.context,aToken,safe);
        assert.equal(denied.status(),403,'Component access without import permission accepted.');
        assert.deepEqual(snapshot(),before,'ACL denial changed component data.');
        checks.push('real backend user without import permission denied');
        const b=await login('b');
        await b.page.goto(admin+'?option=com_joomleague&view=dataimport',{waitUntil:'networkidle'});
        assert.equal(await b.page.locator('#sql_file').count(),1,'Import-only user cannot open native form.');
        const bToken=await token(b.page); assert(bToken,'Importer token absent.');
        const csrf=await upload(b.context,null,safe);
        assert.match(await csrf.text(),/invalid (?:security )?token/i,'Missing CSRF not rejected.');
        assert.deepEqual(snapshot(),before,'CSRF denial changed data.');
        checks.push('missing CSRF rejected without data changes');
        const guest=await browser.newContext();
        await upload(guest,bToken,safe); assert.deepEqual(snapshot(),before,'Guest import changed data.'); await guest.close();
        checks.push('guest import denied');
        const ok=await upload(b.context,bToken,safe); assert.equal(ok.status(),303,'Native import did not redirect.');
        let saved=snapshot();
        assert.equal(saved.component['#__joomleague_club'].find(row=>Number(row.id)===7101)?.name,'R2 HTTP import','Allowed import did not persist.');
        checks.push('non-superuser with explicit import permission saved uploaded SQL');
        const invalids=[
            ['attack.sql',"INSERT INTO `#__joomleague_club` (`uuid`,`name`) VALUES ('00000000-0000-4000-8000-000000007102','R2 partial'); INSERT INTO `#__joomleague_club` (`name`) SELECT marker FROM r2_external_marker;",/not permitted by the restricted importer/i],
            ['wrong.php',safe,/valid uploaded \.sql file/i],
            ['empty.sql','',/not permitted by the restricted importer/i],
            ['conversion.sql',"INSERT INTO `#__joomleague_club` (`id`,`uuid`,`name`) VALUES ('<img src=x onerror=alert(1)>','00000000-0000-4000-8000-000000007103','R2 invalid integer');",/SQL import (?:failed|could not be completed)/i],
        ];
        for(const [name,sql,message] of invalids) {
            const response=await upload(b.context,bToken,sql,name); assert.equal(response.status(),303,name+': unexpected response.');
            await b.page.goto(admin+'?option=com_joomleague&view=dataimport',{waitUntil:'networkidle'});
            assert.match(await b.page.locator('body').innerText(),message,name+': expected error missing.');
            assert.equal(await b.page.locator('img[onerror]').count(),0,'Raw database error rendered executable markup.');
            assert.deepEqual(snapshot(),saved,name+': rejected upload changed data.');
            checks.push(name+' rejected, no partial writes or external changes');
        }
        const second=await login('b');
        await second.page.goto(admin+'?option=com_joomleague&view=dataimport',{waitUntil:'networkidle'});
        const secondToken=await token(second.page);
        const concurrentSql=id=>`INSERT INTO \`#__joomleague_club\` (\`id\`,\`uuid\`,\`name\`) VALUES (${id},'00000000-0000-4000-8000-00000000${id}','R2 concurrent ${id}');`;
        const responses=await Promise.all([upload(b.context,bToken,concurrentSql(7110)),upload(second.context,secondToken,concurrentSql(7111))]);
        assert(responses.every(response=>response.status()===303),'Concurrent import did not complete.');
        saved=snapshot();
        for(const id of [7110,7111]) assert.equal(saved.component['#__joomleague_club'].find(row=>Number(row.id)===id)?.name,'R2 concurrent '+id,'Concurrent import lost rows.');
        checks.push('independent authenticated sessions import concurrently without losing rows');
        await second.context.close();
        await b.page.screenshot({path:path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-import.png'),fullPage:true});
        fs.writeFileSync(path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-http.json'),JSON.stringify({checks,passed:true},null,2)+'\n');
        console.log('PASS: HTTP SQL upload, scoped import ACL, guest/CSRF denial, unsafe/file/type rejection and safe diagnostics.');
        await a.context.close(); await b.context.close();
    } finally { await browser.close(); }
})().catch(error=>{console.error(error);process.exitCode=1;});
