const assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path');
const {execFileSync}=require('node:child_process');
const {firefox}=require('/home/ondrej/.npm-global/lib/node_modules/playwright');
const {required}=require('../Integration/assets-contract.cjs');
const {summarize,runtimeFailurePattern}=require('./audit-result.cjs');
(async()=>{
 const base=process.env.JL_R1_BASE,app=process.env.JL_R1_APP,directory=process.env.JL_R1_ARTIFACTS,driver=process.env.JL_R1_DRIVER;
 assert.match(base,/^http:\/\/127\.0\.0\.1:\d+$/);assert.match(app,/^jl-r1-/);
 const fixture=JSON.parse(process.env.JL_R1_FIXTURE),a=fixture.projects.a;
 const native=action=>JSON.parse(execFileSync('docker',['exec',app,'php','/opt/r1-tests/football-result-state.php',action],{encoding:'utf8'}));
 const meta=native('assets-fixture'),observations=[],errors=[];
 const browser=await firefox.launch({executablePath:'/opt/playwright/firefox-1532/firefox/firefox',headless:true});
 try{
  const context=await browser.newContext({viewport:{width:1440,height:1000}}),page=await context.newPage();page.on('pageerror',e=>errors.push(e.name));
  const record=async(id,p=page)=>{const text=await p.locator('main').innerText();observations.push({id,status:200,keys:text.match(/COM_JOOMLEAGUE_[A-Z0-9_]+/g)||[],fatal:runtimeFailurePattern.test(text),errors:[...errors],evidenceCount:2});};
  await page.goto(base+'/administrator/');await page.locator('#mod-login-username').fill('admin');await page.locator('#mod-login-password').fill(process.env.JL_R1_PASSWORD);
  await Promise.all([page.waitForNavigation(),page.locator('#form-login button[type=submit]').click()]);assert.equal(await page.locator('#mod-login-username').count(),0);
  for(const file of ['matchresult.js','duallist.js','entrymember.js','matches-autosave.js','admin-lists.css'])assert.equal((await context.request.get(base+'/media/com_joomleague/'+(file.endsWith('.css')?'css/':'js/')+file)).status(),200);
  await page.goto(base+'/administrator/index.php?'+new URLSearchParams({option:'com_joomleague',view:'position',layout:'edit',id:meta.position}),{waitUntil:'networkidle'});
  const script=page.locator('script[src*="duallist.js"]');assert.equal(await script.count(),1);assert.equal((await context.request.get(new URL(await script.getAttribute('src'),base).href)).status(),200);await record('asset-http');
  await page.getByRole('tab',{name:'Event Types',exact:true}).click();
  const available=page.locator('#position-assigned_events-available'),assigned=page.locator('#position-assigned_events-assigned');
  await available.selectOption(String(meta.event));await available.locator('..').locator('..').locator('[data-add]').click();assert.equal(await assigned.locator('option[value="'+meta.event+'"]').count(),1);
  const save=async()=>{await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),page.locator('#toolbar-apply button').click()]);const text=await page.locator('body').innerText();if(!/saved/i.test(text))fs.writeFileSync(path.join(directory,driver+'-assets-error.txt'),text);assert.match(text,/saved/i);};
  await page.getByRole('tab',{name:'Statistics',exact:true}).click();
  const statAvailable=page.locator('#position-assigned_statistics-available'),statAssigned=page.locator('#position-assigned_statistics-assigned');
  await statAvailable.selectOption(String(meta.statistic));await statAvailable.locator('..').locator('..').locator('[data-add]').click();
  await save();assert.deepEqual(native('assets-links').map(row=>Number(row.id)),[meta.event]);assert.deepEqual(native('assets-stat-links').map(row=>Number(row.id)),[meta.statistic]);await record('duallist-assign');
  const data=await page.locator('#position-form').evaluate(form=>Object.fromEntries(new FormData(form)));data.task='position.apply';data['jform[assigned_events][]']=String(meta.foreign);data['jform[assigned_statistics][]']=String(meta.statistic);
  const rejected=await context.request.post(base+'/administrator/index.php?option=com_joomleague',{form:data,maxRedirects:0});assert.equal(rejected.status(),303);assert.deepEqual(native('assets-links').map(row=>Number(row.id)),[meta.event]);assert.deepEqual(native('assets-stat-links').map(row=>Number(row.id)),[meta.statistic]);observations.push({id:'reject-cross-sport',status:303,expectedStatus:303,evidenceCount:2});
  await page.getByRole('tab',{name:'Event Types',exact:true}).click();await assigned.selectOption(String(meta.event));await assigned.locator('..').locator('..').locator('[data-remove]').click();
  await page.getByRole('tab',{name:'Statistics',exact:true}).click();await statAssigned.selectOption(String(meta.statistic));await statAssigned.locator('..').locator('..').locator('[data-remove]').click();
  await save();assert.deepEqual(native('assets-links'),[]);assert.deepEqual(native('assets-stat-links'),[]);await record('duallist-remove');
  await page.goto(base+'/administrator/index.php?'+new URLSearchParams({option:'com_joomleague',view:'entrymember',layout:'edit',id:a.records.Entrymember,entry_id:a.entries[0]}),{waitUntil:'networkidle'});
  const memberScript=page.locator('script[src*="entrymember.js"]');assert.equal(await memberScript.count(),1);assert.equal((await context.request.get(new URL(await memberScript.getAttribute('src'),base).href)).status(),200);
  const type=page.locator('#jform_member_person_type'),role=page.locator('#jform_role_code');
  await type.selectOption('player');assert(await role.locator('option[value="goalkeeper"]').count());assert.equal(await role.locator('option[value="head_coach"]').count(),0);
  await type.selectOption('staff');assert(await role.locator('option[value="head_coach"]').count());assert.equal(await role.locator('option[value="goalkeeper"]').count(),0);await record('roles-filter');
  const guest=await browser.newContext(),publicPage=await guest.newPage();publicPage.on('pageerror',e=>errors.push(e.name));
  await publicPage.setContent(native('plugin-link').html);const href=await publicPage.locator('a').getAttribute('href');assert(href);const target=new URL(href,base+'/');assert.equal(target.origin,new URL(base).origin);
  const response=await publicPage.goto(target.href,{waitUntil:'networkidle'});assert.equal(response.status(),200);assert.match(await publicPage.locator('main').innerText(),/R1 Player/);await record('person-link',publicPage);
  const dashboard=await page.goto(base+'/administrator/index.php?option=com_joomleague&view=dashboard',{waitUntil:'networkidle'});assert.equal(dashboard.status(),200);await record('dashboard-link');
  await page.screenshot({path:path.join(directory,driver+'-assets-dashboard.png'),fullPage:true});assert(summarize(observations,required).passed);
  console.log('PASS: registered assets, persisted dual lists, role filtering and plugin link targets');
 }finally{
  await browser.close();for(const id of required)if(!observations.some(row=>row.id===id))observations.push({id,status:0,outcome:'FAIL',evidenceCount:0});
  fs.writeFileSync(path.join(directory,driver+'-assets-http.json'),JSON.stringify({observations,...summarize(observations,required)},null,2));
 }
})().catch(error=>{console.error('FAIL: admin asset browser '+error.name+' '+String(error.message).slice(0,300));process.exitCode=1;});
