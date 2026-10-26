const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const {execFileSync}=require('node:child_process');
const {firefox}=require('/home/ondrej/.npm-global/lib/node_modules/playwright');
(async()=>{
    const base=process.env.JL_R1_BASE;
    assert.match(base,/^http:\/\/127\.0\.0\.1:\d+$/);
    const browser=await firefox.launch({executablePath:'/opt/playwright/firefox-1532/firefox/firefox',headless:true});
    const checks=[];
    try {
        for(const width of [1440,390]) {
            const context=await browser.newContext({viewport:{width,height:1000}});
            const page=await context.newPage();const errors=[];page.on('pageerror',e=>errors.push(String(e)));
            for(const [view,params,count] of [
                ['clubpeople',{},25],
                ['clubpeople',{start:1200},5],
                ['clubpeople',{sort:'metric_goals',direction:'desc'},25],
                ['clubpeople',{filter_search:'Person00002'},1],
                ['clubstaffhistory',{},2],
                ['clubstaffhistory',{manager_id:900001,detail_limitstart:25},27],
            ]) {
                const url=base+'/index.php?'+new URLSearchParams({option:'com_joomleague',view,club_id:9001,...params});
                const response=await page.goto(url,{waitUntil:'networkidle'});
                assert.equal(response.status(),200,url);
                const body=await page.locator('body').innerText();
                assert(!/Allowed memory size|Fatal error|SQLSTATE|COM_JOOMLEAGUE_[A-Z_]+/.test(body),body.slice(-2500));
                assert.equal(await page.locator('.com-joomleague-'+view+' tbody tr').count(),count,url);
                if(params.sort)assert.match(await page.locator('tbody tr').first().innerText(),/Person00002/);
                if(params.manager_id)assert(await page.locator('a[href*="detail_limitstart="]').count()>0,'Detail pagination links missing');
                checks.push({view,params,width,rows:count,status:200});
                if(params.manager_id || (view==='clubpeople' && Object.keys(params).length===0))await page.screenshot({path:path.join(process.env.JL_R1_ARTIFACTS,`${process.env.JL_R1_DRIVER}-${view}-${width}.png`),fullPage:true});
            }
            await page.goto(base+'/index.php?'+new URLSearchParams({option:'com_joomleague',view:'clubpeople',club_id:9001}));
            const next=page.locator('a[href*="start=25"]').first();
            assert(await next.count(),'People next-page link missing');
            await Promise.all([page.waitForNavigation(),next.click()]);
            assert.match(await page.locator('tbody tr').first().innerText(),/Person00025/,'Next-page link did not change records');
            await page.goto(base+'/index.php?'+new URLSearchParams({option:'com_joomleague',view:'clubstaffhistory',club_id:9001,manager_id:900001}));
            const firstDetail=await page.locator('table').last().locator('tbody tr').first().innerText();
            const detailNext=page.locator('a[href*="detail_limitstart=25"]').first();
            assert(await detailNext.count(),'Coach next-page link missing');
            await Promise.all([page.waitForNavigation(),detailNext.click()]);
            assert.notEqual(await page.locator('table').last().locator('tbody tr').first().innerText(),firstDetail,'Coach detail page did not change');
            checks.push({width,peopleNextPage:true,coachNextPage:true});
            const menu=JSON.parse(execFileSync('docker',['exec',process.env.JL_R1_APP,'cat','/tmp/jl-staff-menu.json'],{encoding:'utf8'}));
            const menuUrl=base+'/index.php?'+new URLSearchParams({option:'com_joomleague',view:'clubstaffhistory',club_id:menu.club_id,Itemid:menu.id,filter_team_id:menu.team_id,sort:'score'});
            await page.goto(menuUrl,{waitUntil:'networkidle'});
            const summary=page.locator('.com-joomleague-clubstaffhistory table').first();
            assert.equal(await summary.locator('thead th').count(),4,'Saved menu column visibility not applied');
            assert.equal(await page.locator('input[name="sort"]').inputValue(),'name','Hidden score column still controls ordering');
            await summary.locator('tbody th a').first().click();
            await page.waitForLoadState('networkidle');
            assert.equal(new URL(page.url()).searchParams.get('Itemid'),String(menu.id),'Coach link lost menu settings');
            assert.equal(await page.locator('.com-joomleague-clubstaffhistory table').first().locator('thead th').count(),4,'Coach navigation lost column visibility');
            assert.equal(new URL(page.url()).searchParams.get('filter_team_id'),String(menu.team_id),'Coach navigation lost selected squad');
            await page.goto(base+'/index.php?'+new URLSearchParams({option:'com_joomleague',view:'clubstaffhistory',club_id:menu.club_id,filter_team_id:menu.team_id}),{waitUntil:'networkidle'});
            const period=page.locator('.com-joomleague-clubstaffhistory table').first().locator('tbody tr').first().locator('td').last();
            const links=period.locator('a');
            assert.equal(await links.count(),2,'First and last event links missing');
            const firstHref=await links.first().getAttribute('href');
            assert(firstHref.includes(String(menu.first_match_id)),'First event link is incorrect');
            await links.first().click();await page.waitForLoadState('networkidle');
            assert(await page.locator('.com-joomleague-eventreport').count(),'Boundary event does not open an event report');
            checks.push({width,savedColumnOptions:true,menuAndTeamPreserved:true,boundaryEventOpened:true});
            assert.deepEqual(errors,[]);await context.close();
        }
        await require('./verify-club-people-team.cjs')(browser,base,checks);
        const menu=JSON.parse(execFileSync('docker',['exec',process.env.JL_R1_APP,'cat','/tmp/jl-staff-menu.json'],{encoding:'utf8'}));
        const adminContext=await browser.newContext({viewport:{width:1440,height:1000}});
        const adminPage=await adminContext.newPage();
        await adminPage.goto(base+'/administrator/',{waitUntil:'networkidle'});
        await adminPage.locator('#mod-login-username').fill('admin');
        await adminPage.locator('#mod-login-password').fill(process.env.JL_R1_PASSWORD);
        await Promise.all([adminPage.waitForNavigation({waitUntil:'networkidle'}),adminPage.locator('#form-login button[type=submit]').click()]);
        await adminPage.goto(base+'/administrator/index.php?option=com_menus&task=item.edit&id='+menu.id,{waitUntil:'networkidle'});
        assert.equal(await adminPage.locator('input[name="jform[params][show_score]"]').count(),2,'Menu display options missing');
        const pairs=await adminPage.locator('#item-form').evaluate(form=>[...new FormData(form)].filter(([,value])=>typeof value==='string'));
        const changed=['show_score','show_period','show_losses'].map(key=>'jform[params]['+key+']');
        const values=[...pairs.filter(([key])=>!changed.includes(key)&&key!=='task'),...changed.map(key=>[key,'1']),['task','item.apply']];
        const action=await adminPage.locator('#item-form').getAttribute('action');
        const response=await adminContext.request.post(new URL(action,adminPage.url()).href,{data:new URLSearchParams(values).toString(),headers:{'content-type':'application/x-www-form-urlencoded'},maxRedirects:0});
        assert([302,303].includes(response.status()),'Native menu options save failed');
        await adminPage.goto(base+'/administrator/index.php?option=com_menus&task=item.edit&id='+menu.id,{waitUntil:'networkidle'});
        for(const name of changed)assert.equal(await adminPage.locator('input[name="'+name+'"]:checked').inputValue(),'1','Saved menu option was not retained: '+name+'; redirect='+response.headers().location+'; messages='+await adminPage.locator('#system-message-container').innerText());
        const guestContext=await browser.newContext();const guestPage=await guestContext.newPage();
        await guestPage.goto(base+'/index.php?'+new URLSearchParams({option:'com_joomleague',view:'clubstaffhistory',club_id:menu.club_id,Itemid:menu.id,filter_team_id:menu.team_id}),{waitUntil:'networkidle'});
        assert.equal(await guestPage.locator('.com-joomleague-clubstaffhistory table').first().locator('thead th').count(),7,'Native menu save did not affect public table');
        await guestContext.close();await adminContext.close();
        checks.push({nativeMenuSaveAndReopen:true,publicColumnsUpdated:true});
        fs.writeFileSync(path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-http.json'),JSON.stringify({passed:true,checks},null,2));
        console.log('PASS: public people/staff pages, paging, search, sorting and bounded coach details at desktop/mobile widths');
    } finally {await browser.close();}
})().catch(error=>{console.error(error);process.exitCode=1;});
