const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const {execFileSync} = require('node:child_process');
const {firefox} = require('/home/ondrej/.npm-global/lib/node_modules/playwright');

(async () => {
    const base = process.env.JL_R1_BASE;
    const app = process.env.JL_R1_APP;
    assert.match(base, /^http:\/\/127\.0\.0\.1:\d+$/);
    assert.match(app, /^jl-r1-/);
    const fixture = JSON.parse(process.env.JL_R1_FIXTURE);
    const a = fixture.projects.a, b = fixture.projects.b;
    const snapshot = () => JSON.parse(execFileSync('docker',['exec',app,'php','/opt/r1-tests/ownership-workflows.php','snapshot'],{encoding:'utf8'}));
    const record = (name,id) => snapshot()[name].find(row => Number(row.id) === Number(id));
    const browser = await firefox.launch({executablePath:'/opt/playwright/firefox-1532/firefox/firefox',headless:true});
    const observations=[];
    const admin = base + '/administrator/index.php';
    try {
        const guest = await browser.newContext();
        const beforeGuest=snapshot();
        for (const name of Object.keys(a.records)) {
            await guest.request.post(admin,{form:{option:'com_joomleague',task:name.toLowerCase()+'.save','jform[id]':b.records[name],'jform[project_id]':a.id}});
        }
        assert.deepEqual(snapshot(),beforeGuest,'Guest request changed component records.');
        await guest.close();
        const context=await browser.newContext({viewport:{width:1440,height:1000}});
        const page=await context.newPage();
        await page.goto(base+'/administrator/',{waitUntil:'networkidle'});
        await page.locator('#mod-login-username').fill('r1.editor.a');
        await page.locator('#mod-login-password').fill(process.env.JL_R1_PASSWORD);
        await Promise.all([page.waitForNavigation(),page.locator('#form-login button[type=submit]').click()]);
        assert.equal(await page.locator('#mod-login-username').count(),0,'Restricted login failed.');
        const params=(name,project) => ({project_id:project.id, ...(name==='Round'?{stage_id:project.stage}:{}),...(name==='Match'?{round_id:project.round}:{}),...(name==='Entrymember'?{entry_id:project.entries[0]}:{})});
        const edit=async(name,project,id,target=page)=>{
            const query=new URLSearchParams({option:'com_joomleague',task:name.toLowerCase()+'.edit',id,...params(name,project)});
            const response=await target.goto(admin+'?'+query,{waitUntil:'networkidle'});
            assert(response.status()<500,name+': edit generated server error.');
        };
        const formData=async(target=page)=>target.evaluate(()=>{
            const input=document.querySelector('[name="jform[id]"]');
            if(!input?.form) return null;
            const data={};
            for(const [key,value] of new FormData(input.form)) if(typeof value==='string') data[key]=value;
            return data;
        });
        for(const name of Object.keys(a.records)) {
            const task=name.toLowerCase();
            const foreignBefore=record(name,b.records[name]);
            await edit(name,a,b.records[name]);
            assert.equal(await formData(),null,name+': forged project opened foreign edit form.');
            assert.deepEqual(record(name,b.records[name]),foreignBefore,name+': denied edit touched foreign row.');
            await edit(name,a,a.records[name]);
            const data=await formData();
            assert(data,name+': permitted edit form missing at '+page.url()+'; '+(await page.locator('body').innerText()).slice(-2400));
            const token=Object.keys(data).find(key=>/^[a-f0-9]{32}$/.test(key)&&data[key]==='1');
            assert(token,name+': native CSRF token missing.');
            const ownBefore=record(name,a.records[name]);
            const noCsrf={...data,option:'com_joomleague',task:task+'.apply',id:a.records[name],'jform[name]':'R1 CSRF forbidden','jform[reason]':'R1 CSRF forbidden','jform[shirt_number]':'99','jform[match_number]':'R1 CSRF forbidden','jform[entry_code]':'csrf_forbidden'};
            delete noCsrf[token];
            const csrfResponse=await context.request.post(admin,{form:noCsrf,maxRedirects:0});
            assert.equal(csrfResponse.status(),303,name+': missing CSRF did not use native rejection redirect.');
            const rejection=await context.request.get(new URL(csrfResponse.headers().location,admin).href);
            assert.match(await rejection.text(),/security token|invalid token|most recent request was denied/i,name+': CSRF rejection notice missing.');
            assert.deepEqual(record(name,a.records[name]),ownBefore,name+': CSRF failure changed row.');
            const forged={...data,option:'com_joomleague',task:task+'.apply',id:b.records[name],'jform[id]':b.records[name],'jform[project_id]':a.id};
            if(name==='Entrymember') forged['jform[entry_id]']=a.entries[0];
            await context.request.post(admin,{form:forged,maxRedirects:0});
            assert.deepEqual(record(name,b.records[name]),foreignBefore,name+': forged HTTP save changed foreign row.');
            await edit(name,a,a.records[name]);
            const allowed=await formData();
            const field=({Stage:'name',Round:'name',Match:'match_number',Projectentry:'entry_code',Entrymember:'shirt_number',Standingadjustment:'reason',Stagetransition:'name'})[name];
            const value=name==='Entrymember'?'27':'R1 HTTP '+name;
            allowed['jform['+field+']']=value;
            allowed.option='com_joomleague'; allowed.task=task+'.apply'; allowed.id=a.records[name];
            const response=await context.request.post(admin,{form:allowed,maxRedirects:0});
            assert(response.status()<400,name+': permitted HTTP save failed with '+response.status());
            assert.equal(String(record(name,a.records[name])[field]),value,name+': permitted HTTP save did not persist.');
            await page.goto(admin+'?'+new URLSearchParams({option:'com_joomleague',task:task+'.cancel',...params(name,a),[token]:'1'}),{waitUntil:'networkidle'});
            // A fresh form supplies the current token. List actions must resolve stored ownership too.
            await edit(name,a,a.records[name]);
            const current=await formData();
            const currentToken=Object.keys(current).find(key=>/^[a-f0-9]{32}$/.test(key)&&current[key]==='1');
            const list=({Stage:'stages',Round:'rounds',Match:'matches',Projectentry:'projectentries',Entrymember:'entrymembers',Standingadjustment:'standingadjustments',Stagetransition:'stagetransitions'})[name];
            for(const action of ['unpublish','delete']) {
                await context.request.post(admin,{form:{option:'com_joomleague',task:list+'.'+action,'cid[]':b.records[name],...params(name,a),[currentToken]:'1'},maxRedirects:0});
                assert.deepEqual(record(name,b.records[name]),foreignBefore,name+': foreign '+action+' changed row.');
            }
            const deniedCreate={...current,option:'com_joomleague',task:task+'.save',id:0,'jform[id]':0,'jform[project_id]':b.id};
            if(name==='Round') deniedCreate['jform[stage_id]']=b.stage;
            if(name==='Match') { deniedCreate['jform[stage_id]']=b.stage; deniedCreate['jform[round_id]']=b.round; }
            if(name==='Entrymember') deniedCreate['jform[entry_id]']=b.entries[0];
            if(['Round','Match','Entrymember'].includes(name)) deniedCreate['jform[project_id]']=a.id;
            const beforeDeniedCreate=snapshot();
            const deniedResponse=await context.request.post(admin,{form:deniedCreate,maxRedirects:0});
            assert(deniedResponse.status()<500,name+': denied creation caused server error.');
            assert.deepEqual(snapshot(),beforeDeniedCreate,name+': denied creation changed data.');

            await page.goto(admin+'?'+new URLSearchParams({option:'com_joomleague',task:task+'.add',...params(name,a)}),{waitUntil:'networkidle'});
            const create=await formData();
            assert(create,name+': permitted add form missing.');
            Object.assign(create,current,{option:'com_joomleague',task:task+'.save',id:0,'jform[id]':0,'jform[uuid]':'','jform[code]':'r1_http_'+task});
            create['jform['+field+']']=name==='Entrymember'?'28':'R1 HTTP created '+name;
            if(name==='Round') create['jform[sequence_number]']='10';
            if(name==='Projectentry') create['jform[team_id]']=a.spareTeam;
            const beforeCreate=snapshot()[name].map(row=>Number(row.id));
            const createResponse=await context.request.post(admin,{form:create,maxRedirects:0});
            assert(createResponse.status()<400,name+': permitted HTTP create failed.');
            const inserted=snapshot()[name].filter(row=>!beforeCreate.includes(Number(row.id)));
            assert.equal(inserted.length,1,name+': HTTP creation did not insert exactly one row.');
            assert.equal(String(inserted[0][field]),String(create['jform['+field+']']),name+': HTTP creation did not retain submitted value.');
            assert.equal(Number(inserted[0][name==='Entrymember'?'entry_id':'project_id']),name==='Entrymember'?a.entries[0]:a.id,name+': created owner differs.');
            assert.deepEqual(record(name,b.records[name]),foreignBefore,name+': own creation touched foreign record.');
            const deleteResponse=await context.request.post(admin,{form:{option:'com_joomleague',task:list+'.delete','cid[]':inserted[0].id,...params(name,a),[currentToken]:'1'},maxRedirects:0});
            assert(deleteResponse.status()<400,name+': HTTP delete failed.');
            assert.equal(record(name,inserted[0].id),undefined,name+': HTTP delete was not persisted.');
            observations.push({entity:name,guestDenied:true,foreignEditDenied:true,forgedSaveDenied:true,csrfDenied:true,allowedSavePersisted:true,foreignStateDeleteDenied:true,foreignCreateDenied:true,allowedCreateDeletePersisted:true});
            console.log('PASS: HTTP '+name+' scoped login, foreign ownership, CSRF and permitted save.');
        }
        const second=await browser.newContext();
        const secondPage=await second.newPage();
        await secondPage.goto(base+'/administrator/',{waitUntil:'networkidle'});
        await secondPage.locator('#mod-login-username').fill('r1.editor.b');
        await secondPage.locator('#mod-login-password').fill(process.env.JL_R1_PASSWORD);
        await Promise.all([secondPage.waitForNavigation(),secondPage.locator('#form-login button[type=submit]').click()]);
        for(const name of Object.keys(a.records)) {
            await edit(name,a,a.records[name]);
            await edit(name,b,b.records[name],secondPage);
            const attacker=await formData();
            const owner=await formData(secondPage);
            assert(owner,name+': second project editor cannot open own form.');
            const field=({Stage:'name',Round:'name',Match:'match_number',Projectentry:'entry_code',Entrymember:'shirt_number',Standingadjustment:'reason',Stagetransition:'name'})[name];
            const value=name==='Entrymember'?'29':'R1 concurrent owner '+name;
            Object.assign(owner,{option:'com_joomleague',task:name.toLowerCase()+'.apply',id:b.records[name],['jform['+field+']']:value});
            Object.assign(attacker,{option:'com_joomleague',task:name.toLowerCase()+'.apply',id:b.records[name],'jform[id]':b.records[name],'jform[project_id]':a.id,['jform['+field+']']:'forbidden'});
            const responses=await Promise.all([second.request.post(admin,{form:owner,maxRedirects:0}),context.request.post(admin,{form:attacker,maxRedirects:0})]);
            assert(responses[0].status()<400 && responses[1].status()<500,name+': concurrent requests generated unexpected error.');
            assert.equal(String(record(name,b.records[name])[field]),value,name+': concurrent foreign write replaced owner data.');
            observations.find(item=>item.entity===name).concurrentScopedWrites=true;
            console.log('PASS: HTTP '+name+' simultaneous requests from two scoped users.');
        }
        await second.close();
        await page.screenshot({path:path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-restricted-editor.png'),fullPage:true});
        fs.writeFileSync(path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-http.json'),JSON.stringify(observations,null,2)+'\n');
        await context.close();
    } finally { await browser.close(); }
})().catch(error=>{console.error(error);process.exitCode=1;});
