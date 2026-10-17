import fs from 'node:fs';
import assert from 'node:assert/strict';
import {chromium} from '/mnt/disk-a/browser-lab/node_modules/playwright/index.mjs';

const stage = Number(process.env.JL_CAROM_STAGE || 23);
const project = Number(process.env.JL_CAROM_PROJECT || 23);
const output = new URL('../artifacts/carom-stage-rules-local/', import.meta.url).pathname;
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
  const url = `http://joomla62.local/administrator/index.php?option=com_joomleague&view=stage&layout=edit&id=${stage}&project_id=${project}`;
  await page.goto(url, {waitUntil: 'networkidle'});
  const policy = page.locator('#jform_encounter_decision_policy');
  assert.equal(await policy.inputValue(), 'inherit');
  assert.ok((await policy.innerText()).includes('Převzít z projektu'));
  for (const value of ['allow_draw', 'require_penalties', 'inherit']) {
    await policy.selectOption(value);
    await Promise.all([page.waitForNavigation({waitUntil: 'networkidle'}), page.evaluate(() => {
      const form = document.querySelector('#stage-form');
      form.querySelector('input[name="task"]').value = 'stage.apply';
      HTMLFormElement.prototype.submit.call(form);
    })]);
    assert.equal(await policy.inputValue(), value, 'Stage policy lost after save/reload');
    assert.ok(!await page.locator('.alert-danger').count(), 'Stage save failed');
  }
  assert.ok(!/COM_JOOMLEAGUE_[A-Z_]+/.test(await page.locator('#stage-form').innerText()));
  await page.screenshot({path: `${output}/desktop.png`, fullPage: true});
  await page.setViewportSize({width: 390, height: 844});
  await page.goto(url, {waitUntil: 'networkidle'});
  await page.screenshot({path: `${output}/mobile.png`, fullPage: true});
  assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
  const form = await page.evaluate(() => Object.fromEntries(new FormData(document.querySelector('#stage-form'))));
  form.task = 'stage.apply';
  form['jform[encounter_decision_policy]'] = 'require_penalties';
  for (const key of Object.keys(form)) if (/^[a-f0-9]{32}$/.test(key)) delete form[key];
  const rejected = await page.request.post(url, {form});
  assert.ok(/token|relace|session/i.test(await rejected.text()), 'Missing CSRF rejection message');
  await page.goto(url, {waitUntil: 'networkidle'});
  assert.equal(await policy.inputValue(), 'inherit');
  assert.deepEqual(errors, []);
  console.log('PASS stage decisions: translated choices, all three save/reload paths, desktop/mobile and CSRF rejection');
} finally { await browser.close(); }
