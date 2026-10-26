const assert=require('node:assert/strict');
const {firefox}=require('/home/ondrej/.npm-global/lib/node_modules/playwright');
(async()=>{
 const base=process.env.JL_R1_BASE;assert.match(base,/^http:\/\/127\.0\.0\.1:\d+$/);
 const browser=await firefox.launch({executablePath:'/opt/playwright/firefox-1532/firefox/firefox',headless:true});
 try {
  const context=await browser.newContext(),page=await context.newPage();
  await page.goto(base+'/administrator/',{waitUntil:'networkidle'});
  await page.locator('#mod-login-username').fill('r1.editor.a');await page.locator('#mod-login-password').fill(process.env.JL_R1_PASSWORD);
  await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),page.locator('#form-login button[type=submit]').click()]);
  assert.equal(await page.locator('#mod-login-username').count(),0);
  await require('./verify-member-selectors.cjs')(context,page,base,JSON.parse(process.env.JL_R1_FIXTURE));
  await context.close();
 } finally { await browser.close(); }
})().catch(error=>{console.error(error);process.exitCode=1;});
