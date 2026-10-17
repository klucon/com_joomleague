const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
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
            assert.deepEqual(errors,[]);await context.close();
        }
        fs.writeFileSync(path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-http.json'),JSON.stringify({passed:true,checks},null,2));
        console.log('PASS: public people/staff pages, paging, search, sorting and bounded coach details at desktop/mobile widths');
    } finally {await browser.close();}
})().catch(error=>{console.error(error);process.exitCode=1;});
