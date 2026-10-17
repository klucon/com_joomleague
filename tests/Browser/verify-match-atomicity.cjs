const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {execFileSync} = require('node:child_process');
const {firefox} = require('/home/ondrej/.npm-global/lib/node_modules/playwright');
(async () => {
    const base=process.env.JL_R1_BASE, app=process.env.JL_R1_APP;
    assert.match(base,/^http:\/\/127\.0\.0\.1:\d+$/); assert.match(app,/^jl-r1-/);
    const fixture=JSON.parse(process.env.JL_R1_FIXTURE), a=fixture.projects.a;
    const native=action=>execFileSync('docker',['exec',app,'php','/opt/r1-tests/match-atomicity-acceptance.php',action],{encoding:'utf8'});
    const snapshot=()=>JSON.parse(native('snapshot'));
    const browser=await firefox.launch({executablePath:'/opt/playwright/firefox-1532/firefox/firefox',headless:true});
    const admin=base+'/administrator/index.php', checks=[];
    const contexts=[];
    try {
        const login=async()=>{
            const context=await browser.newContext({viewport:{width:1440,height:1000}}); contexts.push(context);
            const page=await context.newPage();
            await page.goto(base+'/administrator/');
            await page.locator('#mod-login-username').fill('r1.editor.a');
            await page.locator('#mod-login-password').fill(process.env.JL_R1_PASSWORD);
            await Promise.all([page.waitForNavigation(),page.locator('#form-login button[type=submit]').click()]);
            assert.equal(await page.locator('#mod-login-username').count(),0);
            return {context,page};
        };
        const first=await login();
        const form=async(client,create=false)=>{
            await client.page.goto(admin+'?'+new URLSearchParams({option:'com_joomleague',task:create?'match.add':'match.edit',id:create?0:a.records.Match,round_id:a.round,project_id:a.id}));
            const data=await client.page.evaluate(()=>{
                const input=document.querySelector('[name="jform[id]"]');
                if (!input?.form) return null;
                return Object.fromEntries([...new FormData(input.form)].filter(([,v])=>typeof v==='string'));
            });
            assert(data,'Native match form missing');
            return {...data,option:'com_joomleague',task:'match.apply',id:create?0:a.records.Match,
                'jform[id]':create?0:a.records.Match,'jform[participant_slot_1]':a.entries[0],'jform[participant_slot_2]':a.entries[1],
                'jform[scheduled_date]':'2026-10-20','jform[scheduled_time]':'10:30','jform[description]':'R4 HTTP marker'};
        };
        const post=(client,data)=>client.context.request.post(admin,{form:data,maxRedirects:0});
        const reject=async(label,changes,create=false,late=false)=>{
            const data={...await form(first,create),...changes};
            const before=snapshot();
            if(late) native('fault-on');
            let response;
            try { response=await post(first,data); } finally { if(late) native('fault-off'); }
            assert.deepEqual(snapshot(),before,label+': partial write');
            assert(response.status()>=300,label+': unexpected success response');
            checks.push({label,status:response.status(),unchanged:true});
        };
        await reject('invalid participant edit',{'jform[participant_slot_2]':a.entries[0]});
        await reject('invalid participant creation',{'jform[participant_slot_2]':a.entries[0],'jform[code]':'r4_http_invalid'},true);
        await reject('late SQL edit failure',{'jform[participant_slot_1]':a.entries[1],'jform[participant_slot_2]':a.entries[0]},false,true);
        await reject('late SQL creation failure',{'jform[code]':'r4_http_fault'},true,true);
        native('lock');
        try { await reject('locked participant swap',{'jform[participant_slot_1]':a.entries[1],'jform[participant_slot_2]':a.entries[0]}); }
        finally { native('unlock'); }
        const noCsrf=await form(first);
        const token=Object.keys(noCsrf).find(k=>/^[a-f0-9]{32}$/.test(k)&&noCsrf[k]==='1'); assert(token);
        delete noCsrf[token];
        const before=snapshot(); await post(first,noCsrf); assert.deepEqual(snapshot(),before,'CSRF denial wrote data');
        checks.push({label:'missing CSRF',unchanged:true});
        const allowed=await form(first);
        const response=await post(first,allowed); assert(response.status()<400);
        let row=snapshot().project_match.find(r=>Number(r.id)===a.records.Match);
        assert.equal(row.description,'R4 HTTP marker');
        assert.equal(row.scheduled_start,'2026-10-20 10:30:00');
        checks.push({label:'valid HTTP edit',persisted:true});
        const create=await form(first,true); create['jform[code]']='r4_http_valid';
        const beforeIds=snapshot().project_match.map(r=>Number(r.id));
        assert((await post(first,create)).status()<400);
        const added=snapshot().project_match.filter(r=>!beforeIds.includes(Number(r.id))); assert.equal(added.length,1);
        assert.equal(snapshot().match_participant.filter(r=>Number(r.match_id)===Number(added[0].id)).length,2);
        checks.push({label:'valid HTTP creation',participants:2});
        const second=await login();
        const left=await form(first),right=await form(second);
        Object.assign(left,{'jform[description]':'R4 concurrent left','jform[participant_slot_1]':a.entries[1],'jform[participant_slot_2]':a.entries[0]});
        Object.assign(right,{'jform[description]':'R4 concurrent right'});
        const responses=await Promise.all([post(first,left),post(second,right)]);
        assert(responses.every(r=>r.status()<400),'Concurrent valid requests failed');
        const final=snapshot(); row=final.project_match.find(r=>Number(r.id)===a.records.Match);
        const slots=final.match_participant.filter(r=>Number(r.match_id)===a.records.Match).sort((x,y)=>Number(x.slot_number)-Number(y.slot_number));
        assert.equal(slots.length,2);
        assert(['R4 concurrent left','R4 concurrent right'].includes(row.description));
        assert.deepEqual(slots.map(r=>Number(r.project_entry_id)),row.description.endsWith('left')?[a.entries[1],a.entries[0]]:a.entries);
        checks.push({label:'simultaneous saves use one complete parent/participant payload',consistent:true});
        await first.page.screenshot({path:path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-match-editor.png'),fullPage:true});
        fs.writeFileSync(path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-http.json'),JSON.stringify({passed:true,checks},null,2));
        console.log('PASS: HTTP match create/edit rejection, late SQL rollback, lock, CSRF and concurrent consistent saves');
    } finally { await browser.close(); }
})().catch(error=>{console.error(error);process.exitCode=1;});
