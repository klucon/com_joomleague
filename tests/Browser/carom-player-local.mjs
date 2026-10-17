import fs from 'node:fs';
import assert from 'node:assert/strict';
import {chromium} from '/mnt/disk-a/browser-lab/node_modules/playwright/index.mjs';

const person = Number(process.env.JL_CAROM_PERSON || 21);
const output = new URL('../artifacts/carom-player-local/', import.meta.url).pathname;
fs.mkdirSync(output, {recursive: true});
const browser = await chromium.launch({headless: true});
try {
  for (const [name, width, height] of [['desktop', 1440, 1000], ['mobile', 390, 844]]) {
    const context = await browser.newContext({viewport: {width, height}, locale: 'cs-CZ'});
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const url = `http://joomla62.local/index.php?option=com_joomleague&view=person&person_id=${person}`;
    const response = await page.goto(url, {waitUntil: 'networkidle'});
    assert.equal(response.status(), 200);
    const section = page.locator('section[aria-labelledby="person-carom-title"]');
    assert.ok((await section.innerText()).includes('Statistiky hráče karambolu'));
    assert.ok(!/COM_JOOMLEAGUE_[A-Z_]+/.test(await section.innerText()), 'Untranslated constants');
    assert.equal(await section.locator('table').nth(1).locator('tbody tr').count(), 4);
    assert.ok((await section.locator('table').first().innerText()).includes('0.541'));
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'Page overflow');
    const project = await page.locator('#carom-project option').nth(1).getAttribute('value');
    await page.locator('#carom-project').selectOption(project);
    await Promise.all([page.waitForNavigation({waitUntil: 'networkidle'}), section.locator('button[type=submit]').click()]);
    assert.equal(await page.locator('#carom-project').inputValue(), project);
    assert.equal(await section.locator('table').nth(1).locator('tbody tr').count(), 4);
    await page.screenshot({path: `${output}/${name}.png`, fullPage: true});
    const reportLink = section.locator('a[href*="eventreport"], a[href*="report-udalosti"]').first();
    assert.ok(await reportLink.count(), 'No public report link');
    await reportLink.click();
    await page.waitForLoadState('networkidle');
    assert.ok(await page.locator('.com-joomleague-eventreport').count(), 'Result link did not open report');
    await page.goto(`${url}&carom_project_id=2147483647`, {waitUntil: 'networkidle'});
    assert.ok((await section.innerText()).includes('Zvoleným filtrům neodpovídají'));
    assert.deepEqual(errors, []);
    await context.close();
    console.log(`PASS ${name}: player statistics, filters, report links, missing-filter state, no errors/overflow`);
  }
} finally {
  await browser.close();
}
