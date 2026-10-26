const {chromium}=require('/home/ondrej/.npm-global/lib/node_modules/playwright');
const fs=require('node:fs');const path=require('node:path');
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--disable-features=PaintHolding']});const errors=[];
 try{
  const page=await browser.newPage({viewport:{width:1440,height:1000}});page.on('pageerror',e=>errors.push(e.message));
  const base=process.env.JL_R1_BASE;
  await page.goto(base+'/administrator/index.php',{waitUntil:'networkidle'});
  await page.locator('#mod-login-username').fill('admin');await page.locator('#mod-login-password').fill(process.env.JL_R1_PASSWORD);
  await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),page.locator('form#form-login button[type=submit]').click()]);
  const response=await page.goto(base+'/administrator/index.php?option=com_joomleague&view=positions&filter_search=&filter_sport_type_id=&filter_person_type=&filter_role_domain=&filter_published=&limit=100',{waitUntil:'networkidle'});
  const text=await page.locator('body').innerText();
  await page.screenshot({path:path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-position-import.png'),fullPage:true});
  if(response.status()!==200||/Fatal error|Warning:|SQLSTATE/.test(text))throw Error('Broken administrator list');
  const rows=page.locator('table tbody tr').filter({has:page.locator('input[name="cid[]"]')});
  if(await rows.count()!==29)throw Error('Expected 29 visible rows, found '+await rows.count()+' at '+page.url()+': '+text.slice(0,1600));
  for(const code of ['goalkeeper','defender','midfielder','forward','head_coach','referee','analityk'])if(!text.includes(code))throw Error('Missing visible role '+code);
  await page.goto(base+'/administrator/index.php?option=com_joomleague&view=dataimport',{waitUntil:'networkidle'});
  const form=page.locator('form#adminForm');
  const token=await form.locator('input[type=hidden][value="1"]').first().getAttribute('name');
  if(!/^[a-f0-9]{32}$/.test(token||''))throw Error('Native CSRF token missing');
  const conflict="-- Canonical JoomLeague 6.2 migration package\nINSERT INTO `#__joomleague_sport_type` (`id`,`profile_version_id`,`code`,`name`) VALUES ('1',NULL,'pilka_nozna','Untrusted <img src=x onerror=alert(1)>');";
  const rejected=await page.request.post(base+'/administrator/index.php?option=com_joomleague&task=dataimport.import',{multipart:{[token]:'1',sql_file:{name:'catalogue-conflict.sql',mimeType:'application/sql',buffer:Buffer.from(conflict)}}});
  const message=await rejected.text();
  if(!message.includes('Catalogue conflict in sport_type (1)')||message.includes('Untrusted &lt;img')||message.includes('Untrusted <img'))throw Error('Conflict diagnostic missing or reflects imported content');
  await page.goto(base+'/administrator/index.php?option=com_joomleague&view=positions&limit=100',{waitUntil:'networkidle'});
  if(await rows.count()!==29)throw Error('Rejected browser import changed catalogue');
  if(errors.length)throw Error(JSON.stringify(errors));
  fs.writeFileSync(path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-position-browser.json'),JSON.stringify({passed:true,rows:29,conflictRejected:true,diagnosticSafe:true,errors}));
  console.log('PASS: native administrator displays 29 positions, including 3 preserved custom roles.');
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
