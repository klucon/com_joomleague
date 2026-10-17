import fs from 'node:fs';
import assert from 'node:assert/strict';
import {chromium} from '/mnt/disk-a/browser-lab/node_modules/playwright/index.mjs';

const match = Number(process.env.JL_CAROM_MATCH || 7);
const output = new URL('../artifacts/carom-local/', import.meta.url).pathname;
fs.mkdirSync(output, {recursive: true});
const browser = await chromium.launch({headless: true});
try {
  for (const [name, width, height] of [['desktop', 1440, 1000], ['mobile', 390, 844]]) {
    const context = await browser.newContext({viewport: {width, height}, locale: 'cs-CZ'});
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const response = await page.goto(`http://joomla62.local/index.php?option=com_joomleague&view=eventreport&event_id=${match}`, {waitUntil: 'networkidle'});
    assert.equal(response.status(), 200);
    const report = page.locator('.com-joomleague-eventreport');
    const text = await report.innerText();
    assert.ok(text.includes('Herní statistiky'));
    assert.ok(text.includes('Statistiky hráčů'));
    assert.ok(text.includes('Náběhy'));
    assert.ok(!/COM_JOOMLEAGUE_[A-Z_]+/.test(text), 'Untranslated constants');
    assert.deepEqual(errors, []);
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'Page overflows viewport');
    await page.screenshot({path: `${output}/${name}-report.png`, fullPage: true});
    await context.close();
    console.log(`PASS ${name}: translated report, statistics, no page overflow/errors`);
  }
  const page = await browser.newPage({viewport: {width: 1440, height: 1000}});
  await page.goto('http://joomla62.local/administrator/');
  const password = fs.readFileSync('/mnt/disk-a/pswd/joomla62-local.md', 'utf8').match(/^Password: (.+)$/m)?.[1];
  assert.ok(password, 'Local credentials missing');
  await page.locator('#mod-login-username').fill('admin');
  await page.locator('#mod-login-password').fill(password);
  await page.locator('button[type=submit]').click();
  await page.waitForURL('**/administrator/index.php');
  await page.goto(`http://joomla62.local/administrator/index.php?option=com_joomleague&view=matchresult&match_id=${match}`, {waitUntil: 'networkidle'});
  assert.equal(await page.locator('input[name*="[metadata][innings]"]').count(), 8);
  assert.equal(await page.locator('input[name*="[metadata][high_run]"]').count(), 8);
  await page.screenshot({path: `${output}/desktop-editor.png`, fullPage: true});
  console.log('PASS administrator: innings/high-run controls for all four games');
  const editorUrl = `http://joomla62.local/administrator/index.php?option=com_joomleague&view=matchresult&match_id=${match}`;
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  const submit = async () => {
    await Promise.all([
      page.waitForNavigation({waitUntil: 'networkidle'}),
      page.evaluate(() => {
        const form = document.querySelector('#adminForm');
        form.querySelector('input[name="task"]').value = 'matchresult.apply';
        HTMLFormElement.prototype.submit.call(form);
      }),
    ]);
  };
  for (const field of await page.locator('input[name*="[metadata][innings]"]').all()) await field.fill('43');
  for (const field of await page.locator('input[name*="[metadata][high_run]"]').all()) await field.fill('1');
  await submit();
  const savedMessage = await page.locator('#system-message-container').innerText();
  await page.screenshot({path: `${output}/save-response.png`, fullPage: true});
  assert.ok(savedMessage.includes('uložen'), 'Valid result save did not succeed: ' + savedMessage);
  for (const field of await page.locator('input[name*="[metadata][innings]"]').all()) assert.equal(await field.inputValue(), '43');
  const publicPage = await browser.newPage();
  const publicUrl = `http://joomla62.local/index.php?option=com_joomleague&view=eventreport&event_id=${match}`;
  await publicPage.goto(publicUrl, {waitUntil: 'networkidle'});
  const baseline = await publicPage.locator('.com-joomleague-eventreport').innerText();
  assert.ok(baseline.includes('0.541'), 'Saved team average not reflected publicly');
  console.log('PASS save: metadata survives reload and public summary recalculates');
  for (const [kind, value] of [['high_run', '999'], ['innings', '0'], ['score', '41']]) {
    await page.goto(editorUrl, {waitUntil: 'networkidle'});
    const field = kind === 'score' ? page.locator('input[name*="[children][0]"][name$="[numeric_value]"]').first() : page.locator(`input[name*="[metadata][${kind}]"]`).first();
    await field.fill(value);
    await submit();
    const message = await page.locator('#system-message-container').innerText();
    assert.ok(message.trim().length > 0 && !message.includes('úspěšně uložen'), `Invalid ${kind} accepted`);
    await page.screenshot({path: `${output}/rejected-${kind}.png`, fullPage: true});
    await publicPage.reload({waitUntil: 'networkidle'});
    assert.equal(await publicPage.locator('.com-joomleague-eventreport').innerText(), baseline, `Rejected ${kind} changed published data`);
    console.log(`PASS rejection: ${kind} and unchanged published result`);
    // Remove the preserved invalid form without changing the stored result.
    await page.evaluate(() => {
      const form = document.querySelector('#adminForm');
      form.querySelector('input[name="task"]').value = 'matchresult.cancel';
    });
    await Promise.all([page.waitForNavigation({waitUntil: 'networkidle'}), page.evaluate(() => HTMLFormElement.prototype.submit.call(document.querySelector('#adminForm')))]);
  }
  await page.goto(editorUrl, {waitUntil: 'networkidle'});
  const formData = await page.evaluate(() => Object.fromEntries(new FormData(document.querySelector('#adminForm'))));
  formData.task = 'matchresult.apply';
  for (const key of Object.keys(formData)) if (/^[a-f0-9]{32}$/.test(key)) delete formData[key];
  const denied = await page.request.post('http://joomla62.local/administrator/index.php?option=com_joomleague', {form: formData});
  assert.equal(denied.status(), 403, 'Missing CSRF token was not rejected');
  await publicPage.reload({waitUntil: 'networkidle'});
  assert.equal(await publicPage.locator('.com-joomleague-eventreport').innerText(), baseline);
  assert.deepEqual(errors, []);
  console.log('PASS CSRF: tokenless submission rejected without result changes');
} finally {
  await browser.close();
}
