const assert = require('node:assert/strict');
const {execFileSync} = require('node:child_process');
const path = require('node:path');
const {firefox} = require('/home/ondrej/.npm-global/lib/node_modules/playwright');

(async () => {
    const html = execFileSync('php', [path.resolve(__dirname, '../Unit/verify-eventranking-display.php'), '--fixture'], {encoding: 'utf8'});
    const browser = await firefox.launch({executablePath: '/opt/playwright/firefox-1532/firefox/firefox', headless: true});
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        for (const width of [1440, 390, 320]) {
            await page.setViewportSize({width, height: 900});
            await page.setContent('<!doctype html><html><meta name="viewport" content="width=device-width, initial-scale=1"><body>' + html + '</body></html>');
            assert.equal(await page.locator('.list-group-item').count(), 1);
            assert.equal(await page.locator('.jl-event-rank').innerText(), '1');
            assert.equal(await page.locator('.jl-event-value').innerText(), '12');
            assert.match(await page.locator('.jl-event-appearances').innerText(), /4/);
            assert.equal(await page.locator('script,img').count(), 0);
            assert.equal(await page.locator('a.list-group-item').getAttribute('href'), 'index.php?option=com_joomleague&view=person&person_id=7');
            assert.equal(await page.locator('.list-group-horizontal.flex-wrap').count(), 1);
        }
        assert.deepEqual(errors, []);
        console.log('PASS: Firefox event ranking DOM at 1440/390/320px; fixture-only, not Joomla save/ACL acceptance.');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
