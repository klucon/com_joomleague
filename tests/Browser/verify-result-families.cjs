const assert=require('node:assert/strict');
const fs=require('node:fs'),path=require('node:path');
const {execFileSync}=require('node:child_process');
const {firefox}=require('/home/ondrej/.npm-global/lib/node_modules/playwright');
const {required}=require('../Integration/family-browser-contract.cjs');
const {summarize,runtimeFailurePattern}=require('./audit-result.cjs');
(async()=>{
    const base=process.env.JL_R1_BASE,app=process.env.JL_R1_APP,directory=process.env.JL_R1_ARTIFACTS,driver=process.env.JL_R1_DRIVER;
    assert.match(base,/^http:\/\/127\.0\.0\.1:\d+$/);assert.match(app,/^jl-r1-/);
    const native=action=>{try{return JSON.parse(execFileSync('docker',['exec',app,'php','/opt/r1-tests/football-result-state.php',action],{encoding:'utf8',maxBuffer:8*1024*1024}));}catch(error){fs.writeFileSync(path.join(directory,driver+'-families-native-error.txt'),String(error.stdout??'')+String(error.stderr??''));throw error;}};
    const observations=[],errors=[];
    const browser=await firefox.launch({executablePath:'/opt/playwright/firefox-1532/firefox/firefox',headless:true});
    try{
        const context=await browser.newContext({viewport:{width:1440,height:1000}}),page=await context.newPage();
        const guest=await browser.newContext(),publicPage=await guest.newPage();
        for(const p of [page,publicPage])p.on('pageerror',error=>errors.push(error.name));
        await page.goto(base+'/administrator/');await page.locator('#mod-login-username').fill('admin');await page.locator('#mod-login-password').fill(process.env.JL_R1_PASSWORD);
        await Promise.all([page.waitForNavigation(),page.locator('#form-login button[type=submit]').click()]);assert.equal(await page.locator('#mod-login-username').count(),0);
        const record=async(id,p=page)=>{const text=await p.locator('main').innerText();observations.push({id,status:200,fatal:runtimeFailurePattern.test(text),keys:text.match(/COM_JOOMLEAGUE_[A-Z0-9_]+/g)||[],errors:[...errors],evidenceCount:3});};
        for(const code of ['tennis','running_race','mma_boxing']){
            const fixture=native('family-browser-'+code);
            try{
                const url=base+'/administrator/index.php?'+new URLSearchParams({option:'com_joomleague',view:'matchresult',match_id:fixture.match});
                const open=async()=>{const r=await page.goto(url,{waitUntil:'networkidle'});assert.equal(r.status(),200);await page.locator('#jform_status_code').waitFor();const script=page.locator('script[src*="matchresult.js"]');assert.equal(await script.count(),1);assert.equal((await context.request.get(new URL(await script.getAttribute('src'),base).href)).status(),200);};
                const save=async()=>{await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),page.locator('#toolbar-apply button').click()]);const body=await page.locator('body').innerText();if(!/saved/i.test(body)){fs.writeFileSync(path.join(directory,driver+'-'+code+'-editor-error.txt'),body);await page.screenshot({path:path.join(directory,driver+'-'+code+'-editor-error.png'),fullPage:true});}assert.match(body,/saved/i);};
                await open();const initial=native('family-state-'+code);
                const quickData=await page.locator('#adminForm').evaluate(form=>Object.fromEntries(new FormData(form)));
                const token=Object.keys(quickData).find(key=>/^[a-f0-9]{32}$/.test(key)&&quickData[key]==='1');
                assert(token);
                const denied=await context.request.post(base+'/administrator/index.php',{form:{option:'com_joomleague',task:'matchresult.quickSave',format:'json',match_id:String(fixture.match),round_id:quickData.round_id,revision:'0'.repeat(64),[token]:'1','scores[1]':'3','scores[2]':'1'}});
                const rejected=await denied.json();assert.equal(rejected.success,false);assert.match(rejected.message,/full result editor/);
                assert.deepEqual(native('family-state-'+code),initial,'Unsupported quick editor changed family data');
                await save();const saved=native('family-state-'+code);assert(saved.finalized_at);assert.deepEqual({...saved,finalized_at:initial.finalized_at},initial);await record(code+'-save');
                await open();
                if(code==='tennis'){
                    const sets=()=>page.locator('input[name$="[level_code]"][value="set"]');assert.equal(await sets().count(),3);
                    await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),page.locator('button[data-result-task="matchresult.addSegment"][formaction*="segment_code=set"]').click()]);
                    assert.equal(await sets().count(),4);assert.deepEqual(native('family-state-'+code),saved);
                    const remove=sets().last().locator('xpath=..').locator('button[data-result-task="matchresult.removeSegment"]').last();
                    const removeAction=await remove.getAttribute('formaction');
                    await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),remove.click()]);if(await sets().count()!==3){fs.writeFileSync(path.join(directory,driver+'-mutation-error.txt'),removeAction+'\n'+await page.locator('body').innerText());}assert.equal(await sets().count(),3);assert.deepEqual(native('family-state-'+code),saved);
                }
                const field=code==='running_race'?'duration_value':code==='mma_boxing'?'result_rank':'numeric_value';
                const controls=page.locator('input[name$="['+field+']"]');
                const filled=await controls.evaluateAll(nodes=>nodes.filter(n=>n.value!=='').map(n=>({name:n.name,value:n.value})));
                assert(filled.length>=2&&filled.length%2===0);
                if(code==='running_race')assert.deepEqual(filled.slice(0,2).map(n=>n.value),['1:02.345','1:05.100']);
                if(code==='tennis')assert.deepEqual(filled.slice(0,2).map(n=>n.value),['2','1']);
                if(code==='mma_boxing')assert.deepEqual(filled.slice(0,2).map(n=>n.value),['1','2']);
                await record(code+'-reopen');
                for(let i=0;i<filled.length;i+=2){
                    await page.locator('input[name='+JSON.stringify(filled[i].name)+']').fill(filled[i+1].value);
                    await page.locator('input[name='+JSON.stringify(filled[i+1].name)+']').fill(filled[i].value);
                }
                if(code==='mma_boxing'){
                    const selects=page.locator('select[name^="jform[segments][0][values]"][name$="[status_code]"]');assert.equal(await selects.count(),2);
                    await selects.nth(0).selectOption('loser');await selects.nth(1).selectOption('winner');
                }
                await save();const corrected=native('family-state-'+code);const values=corrected.segments[0].values;
                if(code==='tennis')assert.deepEqual(values.map(v=>Number(v.numeric_value)),[1,2]);
                if(code==='running_race')assert.deepEqual(values.map(v=>Number(v.numeric_value)),[65100,62345]);
                if(code==='mma_boxing')assert.deepEqual(values.map(v=>v.status_code),['loser','winner']);
                await record(code+'-correct');
                const response=await publicPage.goto(base+'/index.php?'+new URLSearchParams({option:'com_joomleague',view:'eventreport',event_id:fixture.match}),{waitUntil:'networkidle'});assert.equal(response.status(),200);
                assert.deepEqual(await publicPage.locator('main .fs-3.fw-bold').allTextContents(),code==='tennis'?['1','2']:code==='running_race'?['1:05.100','1:02.345']:['Loser','Winner']);
                await publicPage.screenshot({path:path.join(directory,driver+'-'+code+'-public.png'),fullPage:true});
                await publicPage.setViewportSize({width:390,height:844});await publicPage.reload({waitUntil:'networkidle'});
                assert(await publicPage.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'Public result overflows mobile viewport');
                assert.deepEqual(await publicPage.locator('main .fs-3.fw-bold').allTextContents(),code==='tennis'?['1','2']:code==='running_race'?['1:05.100','1:02.345']:['Loser','Winner']);
                await publicPage.screenshot({path:path.join(directory,driver+'-'+code+'-mobile.png'),fullPage:true});
                await record(code+'-public',publicPage);await publicPage.setViewportSize({width:1440,height:1000});
            }finally{
                assert.equal(native('family-clean-'+code).clean,true);observations.push({id:code+'-clean',status:200,evidenceCount:10});
            }
        }
        assert(summarize(observations,required).passed);console.log('PASS: native browser editors for nested scores, duration and decisions');
    }finally{
        await browser.close();for(const id of required)if(!observations.some(row=>row.id===id))observations.push({id,status:0,outcome:'FAIL',evidenceCount:0});
        fs.writeFileSync(path.join(directory,driver+'-families-http.json'),JSON.stringify({observations,...summarize(observations,required)},null,2));
    }
})().catch(error=>{console.error('FAIL: result family browser '+error.name+' '+String(error.message).slice(0,300));process.exitCode=1;});
