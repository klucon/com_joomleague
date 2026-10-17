import fs from 'node:fs';
import assert from 'node:assert/strict';
import {chromium} from '/mnt/disk-a/browser-lab/node_modules/playwright/index.mjs';
const project = Number(process.env.JL_CAROM_PROJECT || 13);
const firstCriterion = process.env.JL_CAROM_FIRST || 'average';
const output = new URL('../artifacts/carom-standings-local/', import.meta.url).pathname;
fs.mkdirSync(output, {recursive: true});
const browser = await chromium.launch({headless: true});
try {
  const page = await browser.newPage({viewport: {width: 1440, height: 1000}});
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.goto('http://joomla62.local/administrator/');
  const password = fs.readFileSync('/mnt/disk-a/pswd/joomla62-local.md', 'utf8').match(/^Password: (.+)$/m)?.[1];
  assert.ok(password);
  await page.locator('#mod-login-username').fill('admin');
  await page.locator('#mod-login-password').fill(password);
  await page.locator('button[type=submit]').click();
  await page.waitForURL('**/administrator/index.php');
  await page.goto(`http://joomla62.local/administrator/index.php?option=com_joomleague&view=projectrules&project_id=${project}`, {waitUntil: 'networkidle'});
  const first = page.locator('tr').filter({hasText: 'První kritérium při shodě bodů'});
  assert.equal(await first.count(), 1);
  assert.equal(await first.locator('select').inputValue(), firstCriterion);
  assert.ok((await first.locator('select').innerText()).includes('Celkový průměr'));
  assert.ok((await first.innerText()).includes('Získané body z partií'));
  const untranslated = (await page.locator('#adminForm').innerText()).match(/COM_JOOMLEAGUE_[A-Z_]+/g) || [];
  assert.deepEqual(untranslated, []);
  if (firstCriterion.startsWith('head_to_head_')) {
    assert.ok((await first.locator('select').innerText()).includes('Týmové body ze vzájemných zápasů'));
    const submit = async () => {
      await Promise.all([page.waitForNavigation({waitUntil: 'networkidle'}), page.evaluate(() => {
        const form = document.querySelector('#adminForm');
        form.querySelector('input[name="task"]').value = 'projectrules.apply';
        HTMLFormElement.prototype.submit.call(form);
      })]);
    };
    await first.locator('input[type=checkbox]').check();
    await first.locator('select').selectOption('head_to_head_points');
    await submit();
    assert.equal(await first.locator('select').inputValue(), 'head_to_head_points');
    assert.ok(!await page.locator('.alert-danger').count());
    await first.locator('select').selectOption(firstCriterion);
    await submit();
    assert.equal(await first.locator('select').inputValue(), firstCriterion);
  }
  await page.screenshot({path: `${output}/project-rules.png`, fullPage: true});
  for (const [name, width, height] of [['desktop', 1440, 1000], ['mobile', 390, 844]]) {
    const context = await browser.newContext({viewport: {width, height}});
    const publicPage = await context.newPage();
    publicPage.on('pageerror', error => errors.push(error.message));
    const response = await publicPage.goto(`http://joomla62.local/index.php?option=com_joomleague&view=standings&project_id=${project}`, {waitUntil: 'networkidle'});
    assert.equal(response.status(), 200);
    const content = await publicPage.locator('main').innerText();
    assert.ok(content.includes('Karamboly') && content.includes('Náběhy') && content.includes('Celkový průměr'));
    assert.ok(!/COM_JOOMLEAGUE_[A-Z_]+/.test(content));
    assert.ok(await publicPage.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
    await publicPage.screenshot({path: `${output}/${name}.png`, fullPage: true});
    await context.close();
  }
  assert.deepEqual(errors, []);
  console.log('PASS carom criteria form and translated desktop/mobile standings');
} finally { await browser.close(); }
