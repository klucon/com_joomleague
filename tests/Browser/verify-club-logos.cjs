const {execFileSync} = require('node:child_process');
const {randomBytes} = require('node:crypto');
const assert = require('node:assert/strict');
const {firefox} = require('/home/ondrej/.npm-global/lib/node_modules/playwright');
const key = randomBytes(16).toString('hex');
const container = 'local-joomla62-app';
const fixture = '/tmp/jl-logo-fixture.php';
execFileSync('docker', ['cp', __dirname + '/../Integration/logo-fixture.php', container + ':' + fixture]);
(async () => {
    const data = JSON.parse(execFileSync('docker', ['exec', container, 'php', fixture, 'setup', key], {encoding:'utf8'}));
    let browser;
    try {
        browser = await firefox.launch({executablePath:'/opt/playwright/firefox-1532/firefox/firefox'});
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        for (const view of ['club', 'team']) {
            await page.goto('http://joomla62.local/index.php?option=com_joomleague&view=' + view + '&' + view + '_id=' + data[view]);
            const logo = page.locator('.com-joomleague-' + view + ' header img').first();
            assert.equal(await logo.getAttribute('src'), data.image);
            await page.waitForFunction(selector => {
                const image = document.querySelector(selector);
                return image && image.complete && image.naturalWidth > 0;
            }, '.com-joomleague-' + view + ' header img');
            if (view === 'club') assert.equal(await page.locator('.com-joomleague-club article img').first().getAttribute('src'), data.image);
        }
        execFileSync('docker', ['exec', container, 'php', fixture, 'restrict', key]);
        await page.goto('http://joomla62.local/index.php?option=com_joomleague&view=team&team_id=' + data.team);
        assert.equal(await page.locator('.com-joomleague-team header img').count(), 0);
        assert.deepEqual(errors, []);
        console.log('PASS: public club logo, inherited team logo, club team list, image HTTP loading, restricted-club ACL and no browser errors.');
    } finally {
        if (browser) await browser.close();
        execFileSync('docker', ['exec', container, 'php', fixture, 'cleanup', key]);
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
