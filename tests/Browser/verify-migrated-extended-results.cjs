const { chromium } = require('/home/ondrej/.npm-global/lib/node_modules/playwright');
const fs = require('node:fs');
const path = require('node:path');
(async () => {
 const browser = await chromium.launch({headless:true});
 const errors=[];const observations=[];
 try {
  const page=await browser.newPage();page.on('pageerror',e=>errors.push(e.message));
  for(const width of [1440,390]){
   await page.setViewportSize({width,height:900});
   for(const id of [2,3,4,...(process.env.JL_CONFLICT_RESULT_FIXTURE==='1'?[5,6]:[])]){
    const response=await page.goto(process.env.JL_R1_BASE+'/index.php?option=com_joomleague&view=eventreport&event_id='+id,{waitUntil:'networkidle'});
    const text=await page.locator('body').innerText();
    if(response.status()!==200||/Fatal error|SQLSTATE|Uncaught|COM_JOOMLEAGUE_|JGLOBAL_[A-Z_]+/.test(text))throw Error('Broken migrated report '+id);
    const table=page.locator('table').filter({hasText:'Result'}).first();
    if(id===5){
     if(await table.count())throw Error('Unverified draft result leaked into public report');
    }else{
     if(!(await table.count()))throw Error('Result tree not rendered');
     const rendered=await table.innerText();
     if(id<5&&!rendered.includes(id===2?'Extra time':'Shootout'))throw Error('Missing result phase '+id+': '+rendered);
     if(id===6&&(!text.includes('Played score: 1:0')||!text.includes('Ineligible player <img src=x onerror="window.legacyInjected=true">')||await page.evaluate(()=>Boolean(window.legacyInjected))||await page.locator('img[onerror]').count()))throw Error('Awarded score explanation missing or unsafe');
    }
    await page.screenshot({path:path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-extended-'+id+'-'+width+'.png'),fullPage:true});
    observations.push({id,width,status:response.status()});
   }
  }
  if(errors.length)throw Error(JSON.stringify(errors));
  fs.writeFileSync(path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-extended-browser.json'),JSON.stringify({observations,errors},null,2));
  console.log('PASS: migrated extra-time/shootout reports, desktop/mobile, no JS errors.');
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
