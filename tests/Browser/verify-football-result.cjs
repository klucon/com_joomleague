const assert=require('node:assert/strict');
const fs=require('node:fs'),path=require('node:path');
const {execFileSync}=require('node:child_process');
const {firefox}=require('/home/ondrej/.npm-global/lib/node_modules/playwright');
const {summarize,runtimeFailurePattern}=require('./audit-result.cjs');
const {required}=require('../Integration/football-result-contract.cjs');
(async()=>{
    const base=process.env.JL_R1_BASE,app=process.env.JL_R1_APP;
    assert.match(base,/^http:\/\/127\.0\.0\.1:\d+$/);assert.match(app,/^jl-r1-/);
    const fixture=JSON.parse(process.env.JL_R1_FIXTURE),a=fixture.projects.a,b=fixture.projects.b;
    const native=action=>{try{return JSON.parse(execFileSync('docker',['exec',app,'php','/opt/r1-tests/football-result-state.php',action],{encoding:'utf8',maxBuffer:8*1024*1024}));}catch(error){fs.writeFileSync(path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-football-native-error.txt'),String(error.stdout??'')+String(error.stderr??''));throw error;}};
    const observations=[],errors=[];
    const browser=await firefox.launch({executablePath:'/opt/playwright/firefox-1532/firefox/firefox',headless:true});
    const destination=path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-football-http.json');
    let page;
    try{
        const context=await browser.newContext({viewport:{width:1440,height:1000}});page=await context.newPage();page.on('pageerror',e=>errors.push(e.name));
        await page.goto(base+'/administrator/');await page.locator('#mod-login-username').fill('r1.editor.a');await page.locator('#mod-login-password').fill(process.env.JL_R1_PASSWORD);
        await Promise.all([page.waitForNavigation(),page.locator('#form-login button[type=submit]').click()]);
        assert.equal(await page.locator('#mod-login-username').count(),0);
        const admin=base+'/administrator/index.php';
        const open=async()=>{const r=await page.goto(admin+'?'+new URLSearchParams({option:'com_joomleague',view:'matchresult',match_id:a.records.Match}),{waitUntil:'networkidle'});assert.equal(r.status(),200);await page.locator('#jform_status_code').waitFor();};
        const record=async id=>{const text=await page.locator('main').innerText();observations.push({id,status:200,keys:text.match(/COM_JOOMLEAGUE_[A-Z0-9_]+/g)||[],fatal:runtimeFailurePattern.test(text),errors:[...errors],evidenceCount:1});};
        const scoreInputs=()=>page.locator('input[name$="[numeric_value]"]').filter({visible:true});
        const regulation=name=>name.startsWith('jform[segments][0][values]')||['0','1'].some(i=>name.startsWith('jform[segments][0][children]['+i+'][values]'));
        const fill=async(values,status='final',outcome='completed')=>{
            await page.locator('#jform_status_code').selectOption(status);await page.locator('#jform_outcome_code').selectOption(outcome);
            const controls=scoreInputs();const names=await controls.evaluateAll(nodes=>nodes.map(n=>n.name));
            let index=0;
            for(const name of names){
                if(!regulation(name))continue;
                const input=page.locator('input[name='+JSON.stringify(name)+']');
                if(await input.isVisible())await input.fill(String(values[index++]??''));
            }
            assert.equal(index,values.length,'Unexpected football score structure');
        };
        const save=async()=>{await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),page.locator('#toolbar-apply button').click()]);assert.match(await page.locator('body').innerText(),/saved/i);};
        const rootScores=state=>{const segment=state.match_score_segment.find(r=>Number(r.match_id)===a.records.Match&&r.level_code==='result');assert(segment);return state.match_score_value.filter(r=>Number(r.segment_id)===Number(segment.id)).sort((x,y)=>Number(x.participant_id)-Number(y.participant_id)).map(r=>Number(r.numeric_value));};
        const note='<img id="football-injection" src=x onerror="document.body.dataset.injected=1">';
        await open();await fill([2,1,1,0,1,1]);await page.locator('#jform_notes').fill(note);await save();assert.deepEqual(rootScores(native('state')),[2,1]);await record('save-final');
        await open();const reopened=await scoreInputs().evaluateAll(nodes=>nodes.map(n=>({name:n.name,value:n.value})));assert.deepEqual(reopened.filter(n=>regulation(n.name)).map(n=>n.value),['2','1','1','0','1','1']);await record('reopen-final');
        const guest=await browser.newContext();const publicPage=await guest.newPage();
        const pub=await publicPage.goto(base+'/index.php?'+new URLSearchParams({option:'com_joomleague',view:'eventreport',event_id:a.records.Match}),{waitUntil:'networkidle'});
        assert.equal(pub.status(),200);const publicText=await publicPage.locator('main').innerText();assert.match(publicText,/R1 a home/);assert.match(publicText,/R1 a away/);assert.deepEqual(await publicPage.locator('main .fs-3.fw-bold').allTextContents(),['2','1']);
        observations.push({id:'public-final',status:pub.status(),fatal:runtimeFailurePattern.test(publicText),keys:publicText.match(/COM_JOOMLEAGUE_[A-Z0-9_]+/g)||[],evidenceCount:3});
        assert(publicText.includes(note));assert.equal(await publicPage.locator('#football-injection').count(),0);assert.equal(await publicPage.evaluate(()=>document.body.dataset.injected),undefined);
        await publicPage.screenshot({path:path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-football-public.png'),fullPage:true});
        const beforeStanding=native('standings');
        await fill([0,1,0,0,0,1]);await save();assert.deepEqual(rootScores(native('state')),[0,1]);await record('correct-result');
        await publicPage.reload({waitUntil:'networkidle'});assert.deepEqual(await publicPage.locator('main .fs-3.fw-bold').allTextContents(),['0','1']);
        const freshness=native('state').standing_freshness.find(r=>Number(r.project_id)===a.id&&Number(r.stage_key)===0&&r.scope_code==='total');assert(freshness);assert.equal(Number(freshness.is_dirty),0,'Automatic standings refresh did not finish');
        const afterStanding=native('standings');
        const metrics=(state,id)=>state.rows.find(r=>Number(r.project_entry_id)===id).metrics;
        assert.notDeepEqual(metrics(beforeStanding,a.entries[0]),metrics(afterStanding,a.entries[0]));
        assert.equal(Number(metrics(afterStanding,a.entries[0]).losses),1);assert.equal(Number(metrics(afterStanding,a.entries[1]).wins),1);await record('standings-correction');
        const form=()=>page.locator('#adminForm').evaluate(form=>Object.fromEntries(new FormData(form)));
        const reject=async(id,mutate,status)=>{await open();const data=await form();Object.assign(data,{option:'com_joomleague',task:'matchresult.apply'});mutate(data);const before=native('state');const r=await context.request.post(admin,{form:data,maxRedirects:0});assert.equal(r.status(),status);assert.deepEqual(native('state'),before);observations.push({id,status:r.status(),expectedStatus:status,evidenceCount:2});};
        await reject('reject-invalid',data=>{const key=Object.keys(data).find(k=>k.endsWith('[numeric_value]'));assert(key);data[key]='not-a-score';},303);
        await reject('reject-foreign',data=>{data.match_id=b.records.Match;data.project_id=a.id;},403);
        await reject('reject-csrf',data=>{const token=Object.keys(data).find(k=>/^[a-f0-9]{32}$/.test(k));assert(token);delete data[token];},403);
        await open();await fill([0,1,0,0,0,1],'draft');await save();assert.equal(native('state').match_result.find(r=>Number(r.match_id)===a.records.Match).status_code,'draft');
        const draftStanding=native('standings');assert.equal(Number(metrics(draftStanding,a.entries[0]).played),0);assert.equal(Number(metrics(draftStanding,a.entries[1]).played),0);await record('draft-result');
        await fill([3,0,1,0,2,0],'final','forfeit');await save();assert.equal(native('state').match_result.find(r=>Number(r.match_id)===a.records.Match).outcome_code,'forfeit');await record('forfeit-result');
        await reject('reject-unsupported-status',data=>{data['jform[status_code]']='cancelled';},303);
        assert.equal(native('ranking-fixture').created,4);
        const rankingBefore=native('rankings');
        for(const kind of ['person','participant']){
            assert.equal(rankingBefore[kind].events.length,1);assert.equal(Number(rankingBefore[kind].events[0].total_value),1);
            assert.equal(rankingBefore[kind].statistics.length,1);assert.equal(Number(rankingBefore[kind].statistics[0].total_value),2);
        }
        await record('rankings-populated');
        native('rankings-private');const privateRanks=native('rankings');
        for(const kind of ['person','participant']){assert.deepEqual(privateRanks[kind].events,[]);assert.deepEqual(privateRanks[kind].statistics,[]);}
        native('rankings-public');await record('rankings-private');
        const resultHistory=native('state');
        const scheduleStatus=async status=>{
            const response=await page.goto(admin+'?'+new URLSearchParams({option:'com_joomleague',task:'match.edit',id:a.records.Match,round_id:a.round,project_id:a.id}),{waitUntil:'networkidle'});assert.equal(response.status(),200);
            await page.locator('#jform_status_code').fill(status);await save();
            assert.equal(native('state').project_match.find(r=>Number(r.id)===a.records.Match).status_code,status);
        };
        await scheduleStatus('cancelled');
        const cancelledRanks=native('rankings');
        for(const kind of ['person','participant']){assert.deepEqual(cancelledRanks[kind].events,[]);assert.deepEqual(cancelledRanks[kind].statistics,[]);}
        assert.deepEqual(cancelledRanks.stored,rankingBefore.stored);await record('rankings-cancelled');
        const cancelled=native('standings');assert.equal(Number(metrics(cancelled,a.entries[0]).played),0,'Cancelled home match still counted');assert.equal(Number(metrics(cancelled,a.entries[1]).played),0,'Cancelled away match still counted');
        const cancelledSummary=native('summary');assert.equal(cancelledSummary.statistics.summary.completed,0,'Cancelled match counted in summary');assert.equal(cancelledSummary.programme.length,1,'Cancelled item vanished from programme');assert.equal(cancelledSummary.programme[0].played,false,'Cancelled item still completed');assert(cancelledSummary.programme[0].participants.every(p=>p.score===null),'Cancelled programme retains effective score');
        assert.equal(cancelledSummary.program_module.error,'MOD_JOOMLEAGUE_PROGRAM_EMPTY');assert.equal(cancelledSummary.next_module.error,'MOD_JOOMLEAGUE_NEXT_EVENT_EMPTY');assert.equal(cancelledSummary.teamplan.next_match_id,null,'Cancelled match advertised as next match');
        for(const table of ['match_result','match_score_segment','match_score_value'])assert.deepEqual(native('state')[table],resultHistory[table],'Cancellation destroyed historical result');
        await publicPage.reload({waitUntil:'networkidle'});assert.match(await publicPage.locator('main').innerText(),/cancelled/i);await record('cancel-match');
        await scheduleStatus('completed');
        assert.deepEqual(native('rankings'),rankingBefore);await record('rankings-restored');
        const restored=native('standings');assert.equal(Number(metrics(restored,a.entries[0]).played),1);assert.equal(Number(metrics(restored,a.entries[0]).wins),1);
        assert.equal(native('summary').statistics.summary.completed,1);
        for(const table of ['match_result','match_score_segment','match_score_value'])assert.deepEqual(native('state')[table],resultHistory[table],'Restoration changed historical result');
        await record('restore-match');
        await open();
        const phases=async(values,shootout=false)=>{
            await page.locator('#jform_status_code').selectOption('final');await page.locator('#jform_outcome_code').selectOption('completed');
            await page.locator('#jform_condition_tied_after_regulation').setChecked(true);
            await page.locator('#jform_condition_tied_after_extra_time').setChecked(shootout);
            const controls=scoreInputs();assert.equal(await controls.count(),12,'Required football phase controls missing');
            for(let i=0;i<12;i++)await controls.nth(i).fill(values[i]===undefined?'':String(values[i]));
        };
        const segmentValues=(state,code)=>{
            const ids=state.match_score_segment.filter(r=>Number(r.match_id)===a.records.Match&&r.level_code===code).sort((x,y)=>Number(x.sequence_number)-Number(y.sequence_number)).map(r=>Number(r.id));
            return ids.map(id=>state.match_score_value.filter(r=>Number(r.segment_id)===id).sort((x,y)=>Number(x.participant_id)-Number(y.participant_id)).map(r=>Number(r.numeric_value)));
        };
        await phases([2,1,1,0,0,1,1,0,0,0]);await save();
        assert.deepEqual(segmentValues(native('state'),'extra_time'),[[1,0],[0,0]]);assert.deepEqual(rootScores(native('state')),[2,1]);
        await open();assert(await page.locator('#jform_condition_tied_after_regulation').isChecked());assert(!(await page.locator('#jform_condition_tied_after_extra_time').isChecked()));await record('extra-time');
        await phases([1,1,1,0,0,1,0,0,0,0,4,3],true);await save();
        assert.deepEqual(rootScores(native('state')),[1,1]);assert.deepEqual(segmentValues(native('state'),'shootout'),[[4,3]]);
        await publicPage.reload({waitUntil:'networkidle'});assert.deepEqual(await publicPage.locator('main .fs-3.fw-bold').allTextContents(),['1','1']);
        const shootoutRow=publicPage.locator('main table tr').filter({has:publicPage.getByRole('rowheader',{name:/^Shootout(?: 1)?$/})});
        assert.deepEqual((await shootoutRow.locator('td').allTextContents()).map(s=>s.trim()),['4','3']);await record('shootout');
        assert.deepEqual(native('progression'),{preview:[a.entries[0]],assigned:[a.entries[0]]});await record('shootout-progression');
        const bracketFixture=native('bracket-fixture');
        const bracketPage=await guest.newPage();bracketPage.on('pageerror',e=>errors.push(e.name));
        const verifyBracket=async(winner,id)=>{
            const response=await bracketPage.goto(base+'/index.php?'+new URLSearchParams({option:'com_joomleague',view:'bracket',project_id:a.id,stage_id:a.stage}),{waitUntil:'networkidle'});
            assert.equal(response.status(),200);assert.equal(await bracketPage.evaluate(()=>window.bracketInjected),undefined);
            const text=await bracketPage.locator('main').innerText();assert(!runtimeFailurePattern.test(text));assert.equal((text.match(/COM_JOOMLEAGUE_[A-Z0-9_]+/g)||[]).length,0);
            assert.equal(await bracketPage.locator('.jl-bracket-match').count(),3);
            const first=bracketPage.locator('.jl-bracket-match[data-id="'+a.records.Match+'"]');
            assert.deepEqual(await first.locator('.jl-bracket-match__participant--winner .jl-bracket-match__name').allTextContents(),winner===null?[]:[winner===a.entries[0]?'R1 a home':'R1 a away']);
            const second=bracketPage.locator('.jl-bracket-match[data-id="'+bracketFixture.matches[0]+'"]');
            assert.deepEqual(await second.locator('[data-entry]').evaluateAll(nodes=>nodes.map(n=>Number(n.dataset.entry))),winner===null?[]:[winner]);
            const third=bracketPage.locator('.jl-bracket-match[data-id="'+bracketFixture.matches[1]+'"]');
            assert.equal(await third.locator('[data-entry]').count(),0);
            observations.push({id,status:response.status(),fatal:false,keys:[],errors:[...errors],evidenceCount:5});
        };
        await verifyBracket(a.entries[0],'bracket-shootout');
        const firstName=bracketPage.locator('.jl-bracket-match[data-id="'+a.records.Match+'"] [data-entry="'+a.entries[0]+'"]');
        await firstName.click();assert.equal(await bracketPage.locator('.jl-bracket-match--active').count(),2);
        assert(await bracketPage.locator('.jl-bracket-svg line[stroke="#0d6efd"]').count()>0);
        await firstName.click();assert.equal(await bracketPage.locator('.jl-bracket-match--active').count(),0);
        await bracketPage.setViewportSize({width:390,height:844});
        await bracketPage.reload({waitUntil:'networkidle'});
        assert(await bracketPage.locator('#jl-bracket-prev').isDisabled());
        await bracketPage.locator('#jl-bracket-next').click();
        assert.equal(await bracketPage.locator('#jl-bracket-current').textContent(),'Bracket </script><script>window.bracketInjected=1</script>');
        await bracketPage.locator('#jl-bracket-next').click();
        assert.equal(await bracketPage.locator('#jl-bracket-current').textContent(),'Bracket final');
        assert(await bracketPage.locator('#jl-bracket-next').isDisabled());
        await bracketPage.waitForFunction(()=>{const node=document.querySelector('#jl-bracket-scroll');return node.scrollLeft>=Math.min(464,node.scrollWidth-node.clientWidth)-1;});
        const finalBox=await bracketPage.locator('.jl-bracket-match[data-id="'+bracketFixture.matches[1]+'"]').boundingBox();
        const viewportBox=await bracketPage.locator('#jl-bracket-scroll').boundingBox();
        assert(finalBox.x>=viewportBox.x-1&&finalBox.x+finalBox.width<=viewportBox.x+viewportBox.width+1,'Final round did not enter the viewport');
        assert(await bracketPage.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth+1),'Bracket overflows the mobile page');
        await bracketPage.screenshot({path:path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-bracket-mobile.png'),fullPage:true});
        observations.push({id:'bracket-navigation',status:200,fatal:false,keys:[],errors:[...errors],evidenceCount:5});
        await bracketPage.setViewportSize({width:1440,height:1000});await bracketPage.reload({waitUntil:'networkidle'});
        await bracketPage.screenshot({path:path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-bracket-desktop.png'),fullPage:true});
        await open();assert(await page.locator('#jform_condition_tied_after_extra_time').isChecked());
        await phases([1,1,1,0,0,1,0,0,0,0,3,4],true);await save();assert.deepEqual(segmentValues(native('state'),'shootout'),[[3,4]]);assert.deepEqual(rootScores(native('state')),[1,1]);
        const tiedTable=native('standings');assert.equal(Number(metrics(tiedTable,a.entries[0]).draws),1);assert.equal(Number(metrics(tiedTable,a.entries[1]).draws),1);await record('correct-shootout');
        assert.deepEqual(native('progression'),{preview:[a.entries[1]],assigned:[a.entries[1]]});await record('correct-progression');
        await verifyBracket(a.entries[1],'bracket-correction');
        await page.goto(admin+'?'+new URLSearchParams({option:'com_joomleague',view:'matchresult',match_id:bracketFixture.matches[0]}),{waitUntil:'networkidle'});
        await fill([2,0,1,0,1,0]);await save();
        await verifyBracket(a.entries[1],'bracket-stale-final');
        await fill([2,0,1,0,1,0],'draft');await save();
        await open();
        await reject('reject-phase-aggregate',data=>{const key=Object.keys(data).find(k=>k.startsWith('jform[segments][0][values]')&&k.endsWith('[numeric_value]'));assert(key);data[key]='9';},303);
        await open();await page.locator('#jform_condition_tied_after_regulation').setChecked(false);await page.locator('#jform_condition_tied_after_extra_time').setChecked(false);
        await fill([1,1,1,0,0,1]);await save();assert.deepEqual(native('progression'),{preview:[],assigned:[]});await record('remove-decider-progression');
        await verifyBracket(null,'bracket-remove-decider');
        await fill([2,0,1,0,1,0]);await save();assert.deepEqual(segmentValues(native('state'),'extra_time'),[]);assert.deepEqual(segmentValues(native('state'),'shootout'),[]);assert.deepEqual(rootScores(native('state')),[2,0]);
        const normalTable=native('standings');assert.equal(Number(metrics(normalTable,a.entries[0]).wins),1);assert.equal(Number(metrics(normalTable,a.entries[1]).losses),1);await record('remove-phases');
        for(const family of ['tennis','running_race','mma_boxing']){
            const result=native('family-'+family);assert.equal(result.passed,true);assert(result.checks>=6);
            observations.push({id:'family-'+family,status:200,fatal:false,keys:[],evidenceCount:result.checks});
        }
        assert(summarize(observations,required).passed);console.log('PASS: football result native form save/reopen/correction, standings and rejected writes');
    }finally{
        try{fs.writeFileSync(path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-football-state.json'),JSON.stringify({state:native('state'),summary:native('summary')},null,2));}catch{}
        if(page)await page.screenshot({path:path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-football-editor.png'),fullPage:true}).catch(()=>{});
        await browser.close();for(const id of required)if(!observations.some(r=>r.id===id))observations.push({id,status:0,outcome:'FAIL',evidenceCount:0});
        fs.writeFileSync(destination,JSON.stringify({observations,...summarize(observations,required)},null,2));
    }
})().catch(error=>{console.error('FAIL: football result workflow ['+error.name+'] '+String(error.message).split('\n')[0].slice(0,180));process.exitCode=1;});
