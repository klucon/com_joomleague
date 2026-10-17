import fs from 'node:fs';
import assert from 'node:assert/strict';
import {chromium} from '/mnt/disk-a/browser-lab/node_modules/playwright/index.mjs';

const match = Number(process.env.JL_CAROM_MATCH || 21);
const nominations = process.env.JL_CAROM_NOMINATIONS === '1';
const cycles = process.env.JL_CAROM_CYCLES === '1';
const roundCount = cycles ? 4 : 2;
const output = new URL('../artifacts/carom-penalties-local/', import.meta.url).pathname;
fs.mkdirSync(output, {recursive: true});
const browser = await chromium.launch({headless: true});
const errors = [];
try {
  const page = await browser.newPage({viewport: {width: 1440, height: 1000}});
  page.on('pageerror', error => errors.push(error.message));
  await page.goto('http://joomla62.local/administrator/');
  const password = fs.readFileSync('/mnt/disk-a/pswd/joomla62-local.md', 'utf8').match(/^Password: (.+)$/m)?.[1];
  assert.ok(password);
  await page.locator('#mod-login-username').fill('admin');
  await page.locator('#mod-login-password').fill(password);
  await page.locator('button[type=submit]').click();
  await page.waitForURL('**/administrator/index.php');
  if (cycles) {
    await page.goto(`http://joomla62.local/administrator/index.php?option=com_joomleague&view=projectrules&project_id=${Number(process.env.JL_CAROM_PROJECT)}`, {waitUntil: 'networkidle'});
    assert.equal(await page.locator('tr').filter({hasText: 'Vyhodnocení penaltové série'}).locator('select').inputValue(), 'team_cycle');
    assert.equal(await page.locator('tr').filter({hasText: 'Pořadí hráčů penaltové série'}).locator('select').inputValue(), 'descending');
    assert.ok(!/COM_JOOMLEAGUE_[A-Z_]+/.test(await page.locator('#adminForm').innerText()));
  }
  await page.goto(`http://joomla62.local/administrator/index.php?option=com_joomleague&view=matchresult&match_id=${match}`, {waitUntil: 'networkidle'});
  const levels = page.locator('input[value="penalty_round"]');
  assert.equal(await levels.count(), roundCount, 'Stored penalty rounds absent from editor');
  const nominationFields = page.locator('select[name$="[metadata][lineup_member_id]"]');
  if (nominations) {
    assert.equal(await nominationFields.count(), roundCount * 2);
    for (const field of await nominationFields.all()) {
      assert.notEqual(await field.inputValue(), '', 'Stored penalty player missing');
      assert.equal(await field.locator('option').count(), 3, 'Selector must contain only two eligible team players and empty choice');
    }
  }
  assert.equal(await page.locator('input[name*="[metadata][innings]"]').count(), 8, 'Penalties must not add game metadata');
  assert.ok(!/COM_JOOMLEAGUE_[A-Z_]+/.test(await page.locator('#adminForm').innerText()));
  await Promise.all([page.waitForNavigation({waitUntil: 'networkidle'}), page.evaluate(() => {
    const form = document.querySelector('#adminForm');
    form.querySelector('input[name="task"]').value = 'matchresult.apply';
    HTMLFormElement.prototype.submit.call(form);
  })]);
  assert.ok((await page.locator('#system-message-container').innerText()).includes('uložen'));
  assert.equal(await levels.count(), roundCount, 'Save/reload lost penalty rounds');
  if (nominations) for (const field of await nominationFields.all()) assert.notEqual(await field.inputValue(), '');
  await page.screenshot({path: `${output}/editor.png`, fullPage: true});
  for (const [name, width, height] of [['desktop', 1440, 1000], ['mobile', 390, 844]]) {
    const context = await browser.newContext({viewport: {width, height}});
    const publicPage = await context.newPage();
    publicPage.on('pageerror', error => errors.push(error.message));
    const response = await publicPage.goto(`http://joomla62.local/index.php?option=com_joomleague&view=eventreport&event_id=${match}`, {waitUntil: 'networkidle'});
    assert.equal(response.status(), 200);
    const text = await publicPage.locator('.com-joomleague-eventreport').innerText();
    assert.ok(/Team A\s+scheduled\s+4\s+Team B\s+scheduled\s+4/.test(text), 'Regular draw missing');
    assert.ok(text.includes('1.750'), 'Penalty scores contaminated average');
    assert.ok(!/COM_JOOMLEAGUE_[A-Z_]+/.test(text));
    const table = publicPage.locator('table').filter({has: publicPage.locator('caption', {hasText: /Rozhodovací/})});
    assert.equal(await table.count(), 1);
    assert.equal(await table.locator('tbody tr').count(), roundCount);
    if (nominations) {
      assert.equal(await table.locator('tbody a').count(), roundCount * 2, 'Penalty player profile links missing');
      assert.ok((await table.locator('tbody').innerText()).includes('Player 2 Team A'));
      assert.ok((await table.locator('tbody').innerText()).includes('Player 2 Team B'));
    }
    if (cycles) {
      assert.ok(text.includes('Vítěz po penaltové sérii: Team B'), 'Cycle winner not rendered');
      assert.match(await table.locator('tfoot').innerText(), /Součet cyklu 1\s+5\s+6/, 'Complete cycle totals missing');
    }
    assert.ok(await publicPage.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'Viewport overflow');
    await publicPage.screenshot({path: `${output}/${name}.png`, fullPage: true});
    await context.close();
  }
  assert.deepEqual(errors, []);
  console.log('PASS penalty editor save/reload and desktop/mobile: separate rounds, unchanged draw, unchanged average, translated UI');
} finally {
  await browser.close();
}
