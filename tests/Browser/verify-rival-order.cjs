const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {execFileSync} = require('node:child_process');
const {firefox} = require('/home/ondrej/.npm-global/lib/node_modules/playwright');
(async () => {
    const base = process.env.JL_R1_BASE;
    const app = process.env.JL_R1_APP;
    assert.match(base, /^http:\/\/127\.0\.0\.1:\d+$/);
    assert.match(app, /^jl-r1-[a-z0-9]+-(mysqli|pgsql)-app$/);
    const fixture = JSON.parse(execFileSync('docker', ['exec', app, 'cat', '/tmp/jl-rival-order.json'], {encoding: 'utf8'}));
    const browser = await firefox.launch({executablePath: '/opt/playwright/firefox-1532/firefox/firefox', headless: true});
    const observations = [];
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        for (const width of [1440, 390]) {
            await page.setViewportSize({width, height: 1000});
            const response = await page.goto(base + '/index.php?option=com_joomleague&view=clubrivals&club_id=' + fixture.club + '&filter_opponent_club_id=' + fixture.opponent, {waitUntil: 'networkidle'});
            assert.equal(response.status(), 200);
            for (const [kind, expected, score] of [['home', 'R104 own team - R104 opponent team', '2:1'], ['away', 'R104 opponent team - R104 own team', '1:4']]) {
                const link = page.locator('a').filter({hasText: expected});
                assert.equal(await link.count(), 1);
                const row = link.locator('xpath=ancestor::tr');
                assert.equal(await row.locator('.badge').innerText(), score);
                assert.equal(await row.locator('.text-bg-success').count(), 1);
                assert.match(await link.getAttribute('href'), new RegExp('event_id=' + fixture.matches[kind] + '(?:&|$)'));
                observations.push({kind, width, score, status: response.status()});
            }
            assert.doesNotMatch(await page.locator('main').innerText(), /COM_JOOMLEAGUE_|Fatal error|Warning:/);
            await page.screenshot({path: path.join(process.env.JL_R1_ARTIFACTS, process.env.JL_R1_DRIVER + '-rival-order-' + width + '.png'), fullPage: true});
        }
        await page.goto(base + '/index.php?option=com_joomleague&view=clubrivals&club_id=' + fixture.club, {waitUntil: 'networkidle'});
        await page.locator('#clubrivals-team').selectOption(String(fixture.teams[0]));
        await Promise.all([page.waitForNavigation({waitUntil: 'networkidle'}), page.locator('.com-joomleague-clubrivals form button[type=submit]').click()]);
        const rival = page.getByRole('link', {name: 'R104 opponent team', exact: true});
        assert.equal(await rival.count(), 1);
        assert.match(await rival.getAttribute('href'), new RegExp('filter_team_id=' + fixture.teams[0] + '(?:&|$)'));
        await rival.click();
        await page.waitForLoadState('networkidle');
        assert.equal(await page.locator('#clubrivals-team').inputValue(), String(fixture.teams[0]));
        assert.equal(await page.getByRole('link', {name: 'R104 opponent team - R104 own team', exact: true}).count(), 1);
        assert.deepEqual(errors, []);
        assert.equal(observations.length, 4);
        await require('./verify-rival-scope.cjs')(browser,base,app);
        fs.writeFileSync(path.join(process.env.JL_R1_ARTIFACTS, process.env.JL_R1_DRIVER + '-rival-order-browser.json'), JSON.stringify({passed: true, observations}, null, 2));
        console.log('PASS: rival match names, scores, links and club-relative outcome on desktop/mobile.');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
