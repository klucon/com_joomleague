import fs from 'node:fs';
import assert from 'node:assert/strict';
import {chromium} from '/mnt/disk-a/browser-lab/node_modules/playwright/index.mjs';

const browser = await chromium.launch({headless: true});
try {
  const page = await browser.newPage({viewport: {width: 1440, height: 1000}});
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  const password = fs.readFileSync('/mnt/disk-a/pswd/joomla61-local.md', 'utf8').match(/^Password: (.+)$/m)?.[1];
  assert.ok(password);
  await page.goto('http://joomla61.local/administrator/');
  await page.locator('#mod-login-username').fill('admin');
  await page.locator('#mod-login-password').fill(password);
  if (await page.locator('#lang').count()) await page.locator('#lang').selectOption('en-GB');
  await page.locator('button[type=submit]').click();
  await page.waitForURL('**/administrator/index.php');
  await page.goto('http://joomla61.local/administrator/index.php?option=com_joomleague&view=dashboard', {waitUntil: 'networkidle'});
  let text = await page.locator('body').innerText();
  assert.ok(text.includes('Sport categories') && text.includes('Custom fields'));
  assert.ok(!text.includes('COM_JOOMLEAGUE_SPORTCATEGORIES_') && !text.includes('COM_JOOMLEAGUE_DASHBOARD_CUSTOM_FIELDS_') && !text.includes('COM_JOOMLEAGUE_DASHBOARD_DESC_CUSTOM_FIELDS'));
  await page.goto('http://joomla61.local/administrator/index.php?option=com_config&view=component&component=com_joomleague', {waitUntil: 'networkidle'});
  text = await page.locator('body').innerText();
  assert.ok(text.includes('Safety'));
  assert.ok(!text.includes('COM_JOOMLEAGUE_CONFIG_FIELDSET_SAFETY_'));
  assert.deepEqual(errors, []);
  console.log('PASS English dashboard and component settings after beta6 to RC1 upgrade.');
} finally {
  await browser.close();
}
