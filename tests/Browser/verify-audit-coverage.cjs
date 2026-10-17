const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const {firefox}=require('/home/ondrej/.npm-global/lib/node_modules/playwright');
const {runtimeFailurePattern,summarize}=require('./audit-result.cjs');
const {browserRequired:required}=require('../Integration/audit-contract.cjs');
(async()=>{
    const base=process.env.JL_R1_BASE;assert.match(base,/^http:\/\/127\.0\.0\.1:\d+$/);
    const f=JSON.parse(process.env.JL_R1_FIXTURE),a=f.projects.a;
    const observations=[];
    const browser=await firefox.launch({executablePath:'/opt/playwright/firefox-1532/firefox/firefox',headless:true});
    try{
        const context=await browser.newContext();const page=await context.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
        for(const width of [1440,390]) {
            await page.setViewportSize({width,height:1000});
            for(const kind of ['participant','programme']) {
                const response=await page.goto(base+'/index.php?'+new URLSearchParams({option:'com_joomleague',view:kind==='participant'?'participant':'teamplan',project_id:a.id,entry_id:a.entries[0]}),{waitUntil:'networkidle'});
                const text=await page.locator('body').innerText();
                await page.screenshot({path:path.join(process.env.JL_R1_ARTIFACTS,`${process.env.JL_R1_DRIVER}-${kind}-${width}.png`),fullPage:true});
                fs.writeFileSync(path.join(process.env.JL_R1_ARTIFACTS,`${process.env.JL_R1_DRIVER}-${kind}-${width}.txt`),text);
                const keys=text.match(/\b(?:COM_JOOMLEAGUE|MOD_JOOMLEAGUE)_[A-Z0-9_]+\b/g)||[];
                assert.match(text,/R1 a home/,'Semantic participant fixture missing');
                assert.match(text,/R6 mod_joomleague_participant/,'Published participant module missing');
                assert.match(text,/R6 mod_joomleague_birthdays/,'Published birthday module missing');
                assert.match(text,/R1 Player/,'Non-empty birthday/person fixture missing');
                if(kind==='programme')assert.match(text,/R1 a away/,'Non-empty programme opponents missing');
                const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+2);
                observations.push({id:kind+'-'+width,status:response.status(),keys,fatal:runtimeFailurePattern.test(text),errors:[...errors],overflow,evidenceCount:kind==='programme'?5:4});
            }
        }
        await page.setViewportSize({width:1440,height:1000});
        await page.goto(base+'/administrator/');await page.locator('#mod-login-username').fill('r1.editor.a');await page.locator('#mod-login-password').fill(process.env.JL_R1_PASSWORD);
        await Promise.all([page.waitForNavigation(),page.locator('#form-login button[type=submit]').click()]);
        assert.equal(await page.locator('#mod-login-username').count(),0,'Restricted editor login failed');
        const admin=base+'/administrator/index.php';
        const url=admin+'?'+new URLSearchParams({option:'com_joomleague',view:'stageprogression',transition_id:a.records.Stagetransition});
        const response=await page.goto(url,{waitUntil:'networkidle'});
        assert.equal(await page.locator('#adminForm tbody tr').count(),2,'Required progression candidates missing');
        const text=await page.locator('main').innerText();
        observations.push({id:'progression-preview',status:response.status(),keys:text.match(/COM_JOOMLEAGUE_[A-Z0-9_]+/g)||[],fatal:runtimeFailurePattern.test(text),errors:[...errors],evidenceCount:2});
        const data=await page.locator('#adminForm').evaluate(form=>Object.fromEntries(new FormData(form)));
        Object.assign(data,{option:'com_joomleague',task:'stageprogression.apply',transition_id:a.records.Stagetransition});
        const apply=await context.request.post(admin,{form:data,maxRedirects:0});assert.equal(apply.status(),303);
        const follow=await page.goto(new URL(apply.headers().location,admin).href,{waitUntil:'networkidle'});
        const after=await page.locator('main').innerText();
        assert.match(after,/already|applied/i,'Apply/replay result message missing');
        assert.equal(await page.locator('#adminForm tbody tr').count(),2);
        observations.push({id:'progression-apply',status:follow.status(),keys:after.match(/COM_JOOMLEAGUE_[A-Z0-9_]+/g)||[],fatal:runtimeFailurePattern.test(after),errors:[...errors],evidenceCount:2});
        const result=summarize(observations,required);
        fs.writeFileSync(path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-http.json'),JSON.stringify({required,observations,...result},null,2));
        assert(result.passed,JSON.stringify(result));
        console.log('PASS: mandatory non-empty participant/birthday/programme and real stage preview/apply coverage');
    }finally{
        await browser.close();
        for(const id of required)if(!observations.some(item=>item.id===id))observations.push({id,status:0,outcome:'FAIL',evidenceCount:0,errors:['Mandatory observation did not complete']});
        fs.writeFileSync(path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-http.json'),JSON.stringify({required,observations,...summarize(observations,required)},null,2));
    }
})().catch(error=>{console.error('FAIL: mandatory browser audit ['+error.name+']');process.exitCode=1;});
