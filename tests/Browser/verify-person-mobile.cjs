const fs = require('node:fs');
const assert = require('node:assert/strict');
const {execFileSync} = require('node:child_process');
const {randomBytes} = require('node:crypto');
const {firefox} = require('/home/ondrej/.npm-global/lib/node_modules/playwright');
const key = randomBytes(16).toString('hex');
const container = 'local-joomla62-app';
const fixture = '/tmp/jl-person-mobile-fixture.php';
execFileSync('docker', ['cp', __dirname + '/../Integration/person-mobile-fixture.php', container + ':' + fixture]);
(async () => {
    const data = JSON.parse(execFileSync('docker', ['exec', container, 'php', fixture, 'setup', key], {encoding:'utf8'}));
    let browser;
    try {
        browser = await firefox.launch({executablePath:'/opt/playwright/firefox-1532/firefox/firefox'});
        const page = await browser.newPage({viewport:{width:1440,height:1000}});
        await page.goto('http://joomla62.local/administrator/');
        await page.locator('#mod-login-username').fill('admin');
        await page.locator('#mod-login-password').fill(fs.readFileSync('/mnt/disk-a/pswd/joomla62-local.md', 'utf8').match(/^Password: (.+)$/m)[1]);
        await page.locator('button[type=submit]').click();
        await page.waitForURL('**/administrator/index.php');
        await page.goto('http://joomla62.local/administrator/index.php?option=com_joomleague&view=persons');
        await page.locator('#filter_search').fill(data.last_name);
        await page.locator('#filter_search').press('Enter');
        await page.waitForLoadState('networkidle');
        const rows = page.locator('#personList tbody tr');
        assert.equal(await rows.count(), 2);
        for (const width of [1440, 390, 767, 768]) {
            await page.setViewportSize({width,height:1000});
            for (const row of await rows.all()) {
                const firstName = row.locator('td').nth(1);
                const mobileName = row.locator('th span.d-md-none');
                assert.equal(await firstName.isVisible(), width >= 768);
                assert.equal(await mobileName.isVisible(), width < 768);
                assert.equal(await mobileName.textContent(), await firstName.textContent());
                assert.equal(await row.locator('em').count(), 0);
                assert.ok((await row.locator('th a').getAttribute('href')).includes('task=person.edit'));
            }
        }
        console.log('PASS: both names visible on mobile, separate desktop columns, 767/768 breakpoint, escaped names and preserved edit links.');
    } finally {
        if (browser) await browser.close();
        execFileSync('docker', ['exec', container, 'php', fixture, 'cleanup', key]);
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
