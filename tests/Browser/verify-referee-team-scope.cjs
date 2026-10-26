const {chromium}=require('/home/ondrej/.npm-global/lib/node_modules/playwright');
const fs=require('node:fs');const path=require('node:path');
(async()=>{
 const fixture=JSON.parse(process.env.JL_REFEREE_FIXTURE);
 const browser=await chromium.launch({headless:true,args:['--disable-background-timer-throttling','--disable-renderer-backgrounding','--disable-features=PaintHolding']});const checks=[];const errors=[];
 try{
  const page=await browser.newPage();page.on('pageerror',e=>errors.push(e.message));
  for(const width of [1440,390]){
   await page.setViewportSize({width,height:1000});
   const url=process.env.JL_R1_BASE+'/index.php?option=com_joomleague&view=person&person_id='+fixture.person;
   const response=await page.goto(url,{waitUntil:'networkidle'});
   if(response.status()!==200)throw Error('Profile unavailable');
   const body=await page.locator('body').innerText();
   if(/COM_JOOMLEAGUE_|Fatal error|SQLSTATE|Warning:/.test(body))throw Error('Invalid profile');
   const select=page.locator('#referee-filter-team');
   if(await select.locator('option').count()!==3)throw Error('Team options missing or leaking opponents');
   for(const [key,expected,excluded] of [['ownA',['R82-1','R82-3'],['R82-2','R82-4','R82-5']],['ownB',['R82-2','R82-4'],['R82-1','R82-3','R82-5']]]){
    await select.selectOption(String(fixture.teams[key]));
    await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),page.locator('form').filter({has:select}).locator('button[type=submit]').click()]);
    const section=page.locator('section').filter({has:page.locator('#person-event-assignments')});
    const text=await section.innerText();
    if(expected.some(s=>!text.includes(s))||excluded.some(s=>text.includes(s)))throw Error('Team match history crossed scope: '+key);
    if(await select.inputValue()!==String(fixture.teams[key]))throw Error('Selection lost');
    await page.bringToFront();
    await page.keyboard.press('Escape');
    const opponentLinks=section.locator('a[href*="view=team"]');
    if(!(await opponentLinks.count()))throw Error('Opponent link still targets club');
    const link=opponentLinks.first();const href=await link.getAttribute('href');
    const dimensions=await page.evaluate(async()=>({width:document.documentElement.scrollWidth,height:document.documentElement.scrollHeight,visibility:document.visibilityState,focused:document.hasFocus(),frames:await Promise.race([new Promise(r=>requestAnimationFrame(()=>r(true))),new Promise(r=>setTimeout(()=>r(false),1000))])}));
    fs.writeFileSync(path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-referee-'+key+'-'+width+'-dimensions.json'),JSON.stringify(dimensions));
    await section.scrollIntoViewIfNeeded();
    await page.screenshot({path:path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-referee-'+key+'-'+width+'.png'),fullPage:false,timeout:15000});
    const target=await page.request.get(new URL(href,process.env.JL_R1_BASE).href);if(target.status()!==200)throw Error('Broken team link');
    if(await page.evaluate(()=>document.documentElement.scrollWidth>document.documentElement.clientWidth+1))throw Error('Page overflow');
    checks.push({width,team:key,passed:true});
   }
   await page.locator('a[title="Reset filters"]').click();await page.waitForLoadState('networkidle');
   if(await select.inputValue()!=='0')throw Error('Reset did not clear team filter');
  }
  if(errors.length)throw Error(JSON.stringify(errors));
  fs.writeFileSync(path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-referee-browser.json'),JSON.stringify({checks,errors},null,2));
  console.log('PASS: referee team filter, isolated rows, team links, reset, desktop/mobile.');
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
