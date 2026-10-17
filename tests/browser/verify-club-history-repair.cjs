const {firefox} = require('/home/ondrej/.npm-global/lib/node_modules/playwright');
const fs = require('node:fs');
const path = require('node:path');
(async () => {
  const output = path.resolve(__dirname, '../artifacts/club-history-repair-20261011');
  fs.mkdirSync(output, {recursive: true});
  const browser = await firefox.launch({headless: true, executablePath: '/opt/playwright/firefox-1532/firefox/firefox'});
  const observations = [];
  try {
    for (const viewport of [{width:1440,height:1000}, {width:390,height:844}]) {
      const context = await browser.newContext({viewport});
      const page = await context.newPage();
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      for (const view of ['clubpeople', 'clubstaffhistory']) {
        const start = Date.now();
        const response = await page.goto(`http://joomla62.local/index.php?option=com_joomleague&view=${view}&club_id=133`, {waitUntil:'networkidle', timeout:60000});
        if (response.status() !== 200 || !await page.locator(`.com-joomleague-${view} h1`).count()) throw Error(`${view} did not render (${response.status()})`);
        const text = await page.locator('body').innerText();
        if (/Allowed memory size|Fatal error|COM_JOOMLEAGUE_[A-Z_]+/.test(text)) throw Error(`${view}: runtime or untranslated error`);
        await page.screenshot({path:path.join(output, `${view}-${viewport.width}.png`), fullPage:true});
        observations.push({view, width:viewport.width, status:response.status(), milliseconds:Date.now()-start, rows:await page.locator(`.com-joomleague-${view} tbody tr`).count()});
        const sortable = page.locator(`.com-joomleague-${view} thead a`).first();
        if (await sortable.count()) {
          await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}), sortable.click()]);
          if (!await page.locator(`.com-joomleague-${view} h1`).count()) throw Error('Sorting lost the view');
        }
        const select = page.locator(`.com-joomleague-${view} select[name="filter_project_id"]`);
        const value = await select.locator('option').nth(1).getAttribute('value');
        if (value) {
          await select.selectOption(value);
          await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}), page.locator(`.com-joomleague-${view} button[type="submit"]`).click()]);
          if (await page.locator(`select[name="filter_project_id"]`).inputValue() !== value) throw Error('Project filter not retained');
        }
      }
      if (errors.length) throw Error(errors.join('\n'));
      await context.close();
    }
    fs.writeFileSync(path.join(output, 'observations.json'), JSON.stringify(observations,null,2));
    console.log(JSON.stringify(observations,null,2));
  } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exitCode=1;});
