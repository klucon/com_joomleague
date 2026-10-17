const assert = require('node:assert/strict');
const {chromium} = require('playwright');

(async () => {
  const {JOOMLA_BASE_URL: base, JOOMLA_USERNAME: username, JOOMLA_PASSWORD: password} = process.env;
  if (!base || !username || !password) throw Error('Development credentials required');
  const id = process.env.JOOMLEAGUE_CALENDAR_MODULE_ID || '143';
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const admin = await context.newPage();
  const edit = `${base}/administrator/index.php?option=com_modules&task=module.edit&id=${id}`;
  const fields = ['presentation','first_weekday','initial_date','project_id','season_id','stage_id','entry_id','venue_id','sport_type_id','club_id','mode','direction','offset','timezone','date_format','group_by_date','show_date','show_time','show_stage','show_empty','show_participants','show_images','image_size','show_status','show_calendar','days_before','days_after','limit','show_project','show_venue','show_result','template_show_match_detail_button','cache'];
  let original;
  async function values() {
    const out = {};
    for (const name of fields) {
      const control = admin.locator('#jform_params_' + name);
      assert.equal(await control.count(), 1, name + ' field');
      const native = await control.evaluate(el => ['INPUT','TEXTAREA','SELECT'].includes(el.tagName));
      out[name] = native ? await control.inputValue() : await admin.locator(`input[name="jform[params][${name}]"]:checked`).inputValue();
    }
    return out;
  }
  async function apply(config) {
    for (const [name, value] of Object.entries(config)) {
      const c = admin.locator('#jform_params_' + name);
      const tag = await c.evaluate(el => el.tagName);
      if (tag === 'SELECT') await c.selectOption(String(value), {force:true});
      else if (['INPUT','TEXTAREA'].includes(tag)) await c.evaluate((el,v) => {el.value=v;el.dispatchEvent(new Event('input',{bubbles:true}));el.dispatchEvent(new Event('change',{bubbles:true}));}, String(value));
      else await admin.locator(`input[name="jform[params][${name}]"][value="${value}"]`).evaluate(el => {el.checked=true;el.dispatchEvent(new Event('change',{bubbles:true}));});
    }
    await Promise.all([admin.waitForNavigation({waitUntil:'domcontentloaded'}),admin.evaluate(()=>Joomla.submitbutton('module.apply'))]);
    const saved = await values();
    for (const [name,value] of Object.entries(config)) assert.equal(saved[name],String(value),name+' persistence');
  }
  async function site(width) {
    const p = await context.newPage();
    await p.setViewportSize({width,height:1000});
    await p.route('https://analysis.joomleague.eu/**',r=>r.abort());
    await p.goto(base,{waitUntil:'domcontentloaded'});
    if (await p.locator('#username').count() && await p.locator('#passwd').count()) {
      await p.locator('#username').fill(username);await p.locator('#passwd').fill(password);
      await Promise.all([p.waitForNavigation({waitUntil:'domcontentloaded'}),p.locator('form').filter({has:p.locator('#username')}).locator('button[type=submit],input[type=submit]').first().click()]);
    }
    return p;
  }
  try {
    await admin.goto(edit,{waitUntil:'domcontentloaded'});
    if (await admin.locator('#mod-login-username').count()) {
      await admin.locator('#mod-login-username').fill(username);await admin.locator('#mod-login-password').fill(password);
      await Promise.all([admin.waitForNavigation({waitUntil:'domcontentloaded'}),admin.locator('form#form-login button[type=submit]').click()]);
      await admin.goto(edit,{waitUntil:'domcontentloaded'});
    }
    original = await values();
    assert.doesNotMatch(await admin.locator('main').innerText(),/MOD_JOOMLEAGUE_CALENDAR_[A-Z_]+/);
    const common={sport_type_id:'0',club_id:'0',project_id:'0',season_id:'0',stage_id:'0',entry_id:'0',venue_id:'0',cache:'0',days_before:'365',days_after:'5000',limit:'20',offset:'0',mode:'all',direction:'asc',timezone:'Europe/Prague',date_format:'short',show_empty:'1'};
    await apply({...common,presentation:'month',initial_date:process.env.JOOMLEAGUE_CALENDAR_DATE || '2026-10-01',first_weekday:'1',show_calendar:'1',show_status:'1',show_time:'1',show_participants:'1',show_images:'0'});
    for (const width of [1440,390,320]) {
      const p = await site(width);const root=p.locator('.mod-joomleague-calendar');
      assert.equal(await root.count(),1);assert.equal(await root.locator('table').count(),1);
      const next=root.locator('nav a').last();const href=await next.getAttribute('href');
      assert.match(href,new RegExp('jl_calendar_'+id+'_month='));
      await next.click();await p.waitForLoadState('domcontentloaded');
      assert.equal(await root.locator('table').count(),1,'Month navigation');
      assert.doesNotMatch(await root.innerText(),/MOD_JOOMLEAGUE_CALENDAR_[A-Z_]+/);
      const overflow=await root.evaluate(el=>el.scrollWidth-el.clientWidth);assert.ok(overflow<=1,'Module overflow '+width);
      await p.screenshot({path:`/tmp/calendar-live-${width}.png`,fullPage:true});await p.close();
    }
    await apply({...common,presentation:'list',show_project:'0',show_venue:'0',show_stage:'0',show_time:'0',show_calendar:'0',show_status:'0',show_participants:'1',show_images:'0',show_result:'0',template_show_match_detail_button:'0'});
    const p=await site(390);const root=p.locator('.mod-joomleague-calendar');
    assert.equal(await root.locator('table,time,a,.badge,h3').count(),0,'Hidden output controls');
    assert.ok(await root.locator('.list-group-item').count()>0,'List contains fixture events');
    await p.close();
    console.log('Calendar browser save/render passed at 1440/390/320px');
  } finally {
    if (original) {await admin.goto(edit,{waitUntil:'domcontentloaded'});await apply(original);}
    await browser.close();
  }
})().catch(e=>{console.error(e);process.exitCode=1});
