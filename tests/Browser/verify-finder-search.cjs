const assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path');
const {execFileSync}=require('node:child_process');
const {firefox}=require('/home/ondrej/.npm-global/lib/node_modules/playwright');
const {browserRequired:required}=require('../Integration/execution-plugin-contract.cjs');
const {summarize,runtimeFailurePattern}=require('./audit-result.cjs');
(async()=>{
 const base=process.env.JL_R1_BASE,app=process.env.JL_R1_APP,directory=process.env.JL_R1_ARTIFACTS,driver=process.env.JL_R1_DRIVER;
 assert.match(base,/^http:\/\/127\.0\.0\.1:\d+$/);assert.match(app,/^jl-r1-/);assert(['mysqli','pgsql'].includes(driver));
 const fixture=JSON.parse(execFileSync('docker',['exec',app,'cat','/tmp/jl-finder-fixture.json'],{encoding:'utf8'}));
 const observations=[],errors=[];
 const browser=await firefox.launch({executablePath:'/opt/playwright/firefox-1532/firefox/firefox'});
 try{
  const guest=await browser.newContext({viewport:{width:1440,height:1000}}),page=await guest.newPage();page.on('pageerror',e=>errors.push(e.name));
  const record=async(id,response,p=page)=>{
   const text=await p.locator('main').innerText();
   observations.push({id,status:response.status(),fatal:runtimeFailurePattern.test(text),keys:text.match(/\b(?:COM_JOOMLEAGUE|PLG_FINDER_JOOMLEAGUE)_[A-Z0-9_]+\b/g)||[],errors:[...errors],overflow:await p.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1),evidenceCount:2});
  };
  const search=async(p,kind)=>{
   const response=await p.goto(base+'/index.php?'+new URLSearchParams({option:'com_finder',view:'search',q:fixture[kind].token}),{waitUntil:'networkidle'});
   assert.equal(response.status(),200);return response;
  };
  for(const [width,id] of [[1440,'finder-public-desktop'],[390,'finder-public-mobile']]){
   await page.setViewportSize({width,height:1000});const response=await search(page,'public');
   assert.equal(await page.locator('.result__title a').count(),1);assert.match(await page.locator('.result__title').innerText(),new RegExp(fixture.public.token,'i'));
   await record(id,response);await page.screenshot({path:path.join(directory,driver+'-'+id+'.png'),fullPage:true});
  }
  const href=await page.locator('.result__title a').getAttribute('href'),target=new URL(href,base);
  assert.equal(target.origin,new URL(base).origin);
  const detail=await page.goto(target.href,{waitUntil:'networkidle'});assert.equal(detail.status(),200);assert((await page.locator('main').innerText()).includes(fixture.public.token));await record('finder-link-public',detail);
  for(const [kind,id] of [['private','finder-private-guest'],['hidden','finder-unpublished-guest']]){
   const response=await search(page,kind);assert.equal(await page.locator('.result__title a').count(),0);await record(id,response);
  }
  const registered=await browser.newContext({viewport:{width:1440,height:1000}}),member=await registered.newPage();member.on('pageerror',e=>errors.push(e.name));
  await member.goto(base+'/index.php?option=com_users&view=login');const login=member.locator('#com-users-login__form');
  await login.locator('[name=username]').fill('r1.editor.a');await login.locator('[name=password]').fill(process.env.JL_R1_PASSWORD);
  await Promise.all([member.waitForNavigation({waitUntil:'networkidle'}),login.locator('button[type=submit]').click()]);
  assert.equal(await member.locator('#com-users-login__form').count(),0);
  const privateSearch=await search(member,'private');assert.equal(await member.locator('.result__title a').count(),1);await record('finder-registered-private',privateSearch,member);
  const privateTarget=new URL(await member.locator('.result__title a').getAttribute('href'),base);assert.equal(privateTarget.origin,new URL(base).origin);
  const privateDetail=await member.goto(privateTarget.href,{waitUntil:'networkidle'});assert.equal(privateDetail.status(),200);assert((await member.locator('main').innerText()).includes(fixture.private.token));await record('finder-link-registered',privateDetail,member);
  const hidden=await search(member,'hidden');assert.equal(await member.locator('.result__title a').count(),0);await record('finder-unpublished-registered',hidden,member);
  assert(summarize(observations,required).passed);console.log('PASS: actual Joomla Finder public/registered searches, unpublished/access exclusion and detail links, desktop/mobile.');
 }finally{
  await browser.close();
  for(const id of required)if(!observations.some(item=>item.id===id))observations.push({id,status:0,outcome:'FAIL',evidenceCount:0});
  fs.writeFileSync(path.join(directory,driver+'-finder-http.json'),JSON.stringify({observations,...summarize(observations,required)},null,2));
 }
})().catch(error=>{console.error('FAIL: Finder browser '+error.name+' '+String(error.message).slice(0,300));process.exitCode=1;});
