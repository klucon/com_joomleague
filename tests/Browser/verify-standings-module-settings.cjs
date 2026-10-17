const assert = require('node:assert/strict');
const {chromium} = require('playwright');

(async () => {
  const {JOOMLA_BASE_URL: base, JOOMLA_USERNAME: username, JOOMLA_PASSWORD: password} = process.env;
  if (!base || !username || !password) throw Error('Development credentials required');
  const id = process.env.JOOMLEAGUE_STANDINGS_MODULE_ID || '132';
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const admin = await context.newPage();
  const edit = `${base}/administrator/index.php?option=com_modules&task=module.edit&id=${id}`;
  const fields = ['project_id','scope','stage_id','offset','limit','density','show_images','image_size','show_legend','show_stage_name','link_entry','show_empty','show_project_name','responsive_columns','cache','highlight_entry_id','highlight_style','highlight_color_row','highlight_color_text','highlight_bold','highlight_italic','highlight_underline','form_enabled','form_count','zone_top_enabled','zone_top_count','zone_top_color','zone_bottom_enabled','zone_bottom_count','zone_bottom_color','short_labels','short_label_tooltips','combined_score_format','template_show_score','template_show_goal_difference','template_show_sets','template_show_points'];
  let original;
  async function values() {
    const result = {};
    for (const name of fields) {
      const control = admin.locator('#jform_params_' + name);
      assert.equal(await control.count(), 1, name);
      const native = await control.evaluate(el => ['INPUT','SELECT','TEXTAREA'].includes(el.tagName));
      result[name] = native ? await control.inputValue() : await admin.locator(`input[name="jform[params][${name}]"]:checked`).inputValue();
    }
    return result;
  }
  async function apply(config) {
    for (const [name,value] of Object.entries(config)) {
      const c = admin.locator('#jform_params_' + name);
      const tag = await c.evaluate(el=>el.tagName);
      if (tag === 'SELECT') await c.selectOption(String(value), {force:true});
      else if (['INPUT','TEXTAREA'].includes(tag)) await c.evaluate((el,v)=>{el.value=v;el.dispatchEvent(new Event('input',{bubbles:true}));el.dispatchEvent(new Event('change',{bubbles:true}));},String(value));
      else await admin.locator(`input[name="jform[params][${name}]"][value="${value}"]`).evaluate(el=>{el.checked=true;el.dispatchEvent(new Event('change',{bubbles:true}));});
    }
    await Promise.all([admin.waitForNavigation({waitUntil:'domcontentloaded'}),admin.evaluate(()=>Joomla.submitbutton('module.apply'))]);
    const saved = await values();
    for (const [key,value] of Object.entries(config)) assert.equal(saved[key],String(value),key+' persistence');
  }
  async function site(width) {
    const page=await context.newPage();await page.setViewportSize({width,height:1000});
    await page.route('https://analysis.joomleague.eu/**',r=>r.abort());
    await page.goto(base,{waitUntil:'domcontentloaded'});
    if(await page.locator('#username').count() && await page.locator('#passwd').count()) {
      await page.locator('#username').fill(username);await page.locator('#passwd').fill(password);
      await Promise.all([page.waitForNavigation({waitUntil:'domcontentloaded'}),page.locator('form').filter({has:page.locator('#username')}).locator('button[type=submit],input[type=submit]').first().click()]);
    }
    return page;
  }
  try {
    await admin.goto(edit,{waitUntil:'domcontentloaded'});
    if(await admin.locator('#mod-login-username').count()) {
      await admin.locator('#mod-login-username').fill(username);await admin.locator('#mod-login-password').fill(password);
      await Promise.all([admin.waitForNavigation({waitUntil:'domcontentloaded'}),admin.locator('form#form-login button[type=submit]').click()]);
      await admin.goto(edit,{waitUntil:'domcontentloaded'});
    }
    original=await values();
    await apply({project_id:process.env.JOOMLEAGUE_STANDINGS_PROJECT_ID || '1'});
    assert.doesNotMatch(await admin.locator('main').innerText(),/MOD_JOOMLEAGUE_STANDINGS_[A-Z_]+/);
    assert.ok(await admin.locator('#jform_params_scope option').count()>1,'Profile scopes');
    assert.ok(await admin.locator('#jform_params_stage_id option').count()>1,'Project stages');
    const stage=await admin.locator('#jform_params_stage_id option').nth(1).getAttribute('value');
    const scope=await admin.locator('#jform_params_scope option').nth(1).getAttribute('value');
    await apply({stage_id:stage,scope});
    const favourite=await admin.locator('#jform_params_highlight_entry_id option').nth(1).getAttribute('value');
    await apply({highlight_entry_id:favourite,highlight_style:'row',highlight_color_row:'#ffffff',highlight_color_text:'#000000',highlight_bold:'1',highlight_italic:'1',highlight_underline:'0',form_enabled:'1',form_count:'3',zone_top_enabled:'1',zone_top_count:'2',zone_top_color:'#008000',zone_bottom_enabled:'1',zone_bottom_count:'2',zone_bottom_color:'#cc0000',short_labels:'1',short_label_tooltips:'1',combined_score_format:'1',template_show_score:'1',template_show_goal_difference:'1',template_show_sets:'1',template_show_points:'1'});
    await apply({scope:'',stage_id:'0',offset:'1',limit:'3',density:'standard',show_images:'1',image_size:'48',show_legend:'1',show_stage_name:'1',link_entry:'1',show_empty:'1',show_project_name:'1',responsive_columns:'1',cache:'0'});
    for(const width of [1440,390,320]) {
      const page=await site(width);const root=page.locator('.mod-joomleague-standings');
      assert.equal(await root.count(),1);assert.equal(await root.locator('tbody tr').count(),3,await root.innerText());
      assert.equal(await root.locator('dl').count(),1);assert.ok(await root.locator('tbody a').count()>0);
      assert.doesNotMatch(await root.innerText(),/MOD_JOOMLEAGUE_STANDINGS_[A-Z_]+/);
      assert.ok(await root.evaluate(el=>el.scrollWidth-el.clientWidth)<=1,'Overflow '+width);
      await page.screenshot({path:`/tmp/standings-live-${width}.png`,fullPage:true});
      await root.screenshot({path:`/tmp/standings-module-${width}.png`});await page.close();
    }
    await apply({show_images:'0',show_legend:'0',link_entry:'0',show_project_name:'0',show_stage_name:'0',density:'compact'});
    const page=await site(390);const root=page.locator('.mod-joomleague-standings');
    assert.equal(await root.locator('img,a,dl,.mod-joomleague-standings__title').count(),0,'Hidden output');
    assert.equal(await root.locator('table.table-sm').count(),1);await page.close();
    await apply({offset:'0',highlight_entry_id:favourite,highlight_style:'text',highlight_color_text:'#008000',link_entry:'1'});
    const highlightedPage=await site(390);
    const favouriteLink=highlightedPage.locator(`.mod-joomleague-standings tbody tr[data-entry-id="${favourite}"] a`);
    assert.equal(await favouriteLink.count(),1,'Highlight window includes participant');
    assert.equal(await favouriteLink.evaluate(el=>getComputedStyle(el).color),'rgb(0, 128, 0)','Highlight link colour');
    await highlightedPage.close();
    console.log('Standings admin save/render passed at 1440/390/320px');
  } finally {
    if(original){await admin.goto(edit,{waitUntil:'domcontentloaded'});await apply({project_id:original.project_id});await apply(original);}
    await browser.close();
  }
})().catch(error=>{console.error(error);process.exitCode=1;});
